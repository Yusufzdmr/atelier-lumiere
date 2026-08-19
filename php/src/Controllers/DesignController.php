<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Design;
use Atelier\I18n;
use Atelier\Seo;
use Atelier\View;

/**
 * Der Katalog der zweiten Fassung und eine einzelne Vorlage darin.
 *
 * Liegt bewusst neben InviteController statt darin: die beiden Fassungen
 * sollen sich nicht beruehren, solange verglichen wird.
 */
final class DesignController
{
    /** Testdaten fuer die Vorschau: lang genug, um Umbrueche zu zeigen. */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
    ];

    public function index(): void
    {
        $locale = I18n::locale();
        $designs = Design::all('active');

        $styles = '';
        foreach ($designs as $design) {
            $styles .= Design::css($design, '.d-' . $design['id']);
        }

        View::page('pages/designs-v2', [
            'locale'  => $locale,
            'path'    => I18n::path('/v2/designs', $locale),
            'meta'    => Seo::forPage('designs-v2', [
                'title'     => $locale === 'de' ? 'Designs (zweite Fassung)' : 'Designs (second version)',
                'noindex'   => true,
                'canonical' => Config::url() . I18n::path('/v2/designs', $locale),
            ]),
            'designs' => $designs,
            'styles'  => $styles,
            'values'  => Design::bindValues(self::BEISPIEL, $locale),
        ]);
    }

    /** @param array<string,string> $params */
    public function preview(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $design = Design::find($slug);

        if ($design === null || $design['status'] === 'inactive') {
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => I18n::locale(),
                // layout.php braucht $path unbedingt (hreflang, canonical,
                // Kopfzeile) - ohne ihn wirft es fuer jede der drei Stellen
                // eine Warnung. Die Vorlage im Auftrag hatte das vergessen.
                'path'   => I18n::path(''),
                'meta'   => ['title' => '404', 'noindex' => true],
            ]);
            return;
        }

        $locale = I18n::locale();
        $scope = '.d-' . $design['id'];
        $values = Design::bindValues(self::BEISPIEL, $locale);

        View::page('pages/design-preview', [
            'locale'   => $locale,
            'path'     => I18n::path('/v2/designs/' . $design['slug'], $locale),
            'meta'     => Seo::forPage('design-preview', [
                'title'     => $design['name'][$locale] ?? $design['name']['de'],
                'noindex'   => true,
                'canonical' => Config::url() . I18n::path('/v2/designs/' . $design['slug'], $locale),
                // Die Choreografie wird geteilt, nicht nachgebaut: dasselbe
                // Skript wie bei der ersten Fassung. Es kennt keine Farben und
                // keine Formen - es oeffnet ein Kuvert und laesst eine Karte
                // aufsteigen. Das ist Verhalten des Betrachters, nicht Teil
                // des Designs, und gehoert deshalb nicht ins Dokument.
                'scripts'   => ['/assets/invitation.js'],
            ]),
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            'styles'   => Design::css($design, $scope),
            // Drei Ebenenlisten statt einer: die Vorschau schachtelt sie.
            'initialen' => $values['initials'],
            'seite'    => Design::html($design, $values, $locale, 'page'),
            'kuvert'   => Design::html($design, $values, $locale, 'envelope'),
            'karte'    => Design::html($design, $values, $locale, 'card'),
            'warnings' => Design::warnings($design),
        ]);
    }
}
