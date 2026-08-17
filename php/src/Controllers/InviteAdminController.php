<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Config;
use Atelier\Customers;
use Atelier\Db;
use Atelier\Guests;
use Atelier\I18n;
use Atelier\Invitations;
use Atelier\Security;
use Atelier\Themes;
use Atelier\View;

/**
 * Der Einladungsreiter: was erstellt wurde, wer zugesagt hat, was liegen blieb.
 *
 * Bezahlt oder mit Gutschein – beides steht hier nebeneinander, weil beim
 * Nachfragen genau das die Frage ist.
 */
final class InviteAdminController
{
    private const TAB = '/einladungen';

    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            match (Security::clean($_POST['was'] ?? '', 20)) {
                'loeschen'         => Invitations::delete(Security::clean($_POST['slug'] ?? '', 96)),
                'entwurf-loeschen' => Invitations::deleteDraft(Security::clean($_POST['token'] ?? '', 64)),
                'gast-loeschen'    => Guests::delete(
                    Security::clean($_POST['slug'] ?? '', 96),
                    Security::clean($_POST['token'] ?? '', 96)
                ),
                default            => null,
            };

            Admin::back($this->locale, self::TAB);
        }

        // Zusagen einmal holen und nach Einladung sortieren – sonst wäre es
        // eine Abfrage je Zeile.
        $rsvpsBySlug = [];
        foreach (Invitations::rsvps() as $rsvp) {
            $rsvpsBySlug[(string) ($rsvp['slug'] ?? '')][] = $rsvp;
        }

        // Welcher Gutschein gehört zu welcher Einladung?
        $customerBySlug = [];
        foreach (Customers::all() as $customer) {
            foreach ($customer['coupon']['usedFor'] as $use) {
                $customerBySlug[(string) ($use['slug'] ?? '')] = $customer;
            }
        }

        $themes = [];
        foreach (Themes::all() as $theme) {
            $themes[(string) $theme['id']] = (string) $theme['name'];
        }

        $rows = [];
        foreach (Invitations::all() as $invitation) {
            $slug = (string) ($invitation['slug'] ?? '');
            $rsvps = $rsvpsBySlug[$slug] ?? [];
            $customer = $customerBySlug[$slug] ?? null;

            // „Kommt“ zählt Personen, nicht Antworten: eine Zusage kann vier
            // Gäste mitbringen, und danach wird die Tischordnung gemacht.
            $guests = 0;
            $yes = 0;
            $no = 0;
            foreach ($rsvps as $rsvp) {
                if (empty($rsvp['coming'])) {
                    $no++;
                    continue;
                }
                $yes++;
                $guests += max(1, (int) ($rsvp['count'] ?? 1));
            }

            // Persönlich adressierte Fassungen – nicht zu verwechseln mit den
            // Personen aus den Zusagen oben.
            $personal = [];
            foreach (Guests::all($slug) as $guest) {
                $personal[] = $guest + ['url' => Guests::url($slug, (string) $guest['token'], (string) ($invitation['locale'] ?? $this->locale))];
            }

            $rows[] = [
                'invitation' => $invitation,
                'personal'   => $personal,
                'manage'     => Invitations::manageUrl($invitation, (string) ($invitation['locale'] ?? $this->locale)),
                'slug'       => $slug,
                'url'        => Config::url() . I18n::sitePath('/einladung/' . $slug, (string) ($invitation['locale'] ?? $this->locale)),
                'theme'      => $themes[(string) ($invitation['theme'] ?? '')] ?? (string) ($invitation['theme'] ?? ''),
                'rsvps'      => $rsvps,
                'yes'        => $yes,
                'no'         => $no,
                'guests'     => $guests,
                'customer'   => $customer,
            ];
        }

        View::page('admin/invitations', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . self::TAB),
            'current' => self::TAB,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'rows'    => $rows,
            'drafts'  => Invitations::drafts(),
            'total'   => (int) (Db::one('SELECT COUNT(*) AS n FROM rsvps')['n'] ?? 0),
        ]);
    }
}
