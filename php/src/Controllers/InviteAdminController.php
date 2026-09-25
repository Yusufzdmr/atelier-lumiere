<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\I18n;
use Atelier\InvitationsV2;
use Atelier\Security;
use Atelier\View;

/**
 * Der Einladungsreiter: was mit dem neuen Assistenten erstellt wurde.
 *
 * Bis 2026-09-25 stand hier zusaetzlich die erste Fassung (Invitations.php)
 * mit Zusagen, persoenlichen Gastlinks und Gutscheinen. Sie ist raus — der
 * neue Assistent hat noch keine Zusagen und keine Gutscheine (Phase D),
 * deshalb ist diese Seite bis dahin kürzer als sie war.
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
                /*
                 * Eine Einladung an- oder abschalten.
                 *
                 * Kein Loeschen daneben: eine verschickte Adresse loescht man
                 * nicht, man schaltet sie ab. Wer sie loescht, gibt sie zur
                 * Wiederverwendung frei - und der naechste Gast, der den alten
                 * Link oeffnet, landet auf einer fremden Hochzeit.
                 */
                'v2-zustand'       => InvitationsV2::setStatus(
                    Security::clean($_POST['slug'] ?? '', 96),
                    Security::clean($_POST['zustand'] ?? '', 16)
                ),
                'entwurf-loeschen' => InvitationsV2::deleteDraft(Security::clean($_POST['token'] ?? '', 64)),
                default            => null,
            };

            Admin::back($this->locale, self::TAB);
        }

        View::page('admin/invitations', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . self::TAB),
            'current' => self::TAB,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'v2'      => InvitationsV2::all(),
            'drafts'  => InvitationsV2::drafts(),
        ]);
    }
}
