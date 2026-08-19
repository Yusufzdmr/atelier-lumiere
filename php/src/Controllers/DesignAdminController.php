<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Design;
use Atelier\I18n;
use Atelier\Security;
use Atelier\Themes;
use Atelier\View;

/**
 * Der Katalog der zweiten Fassung im Panel und sein Editor.
 *
 * Liegt neben DesignController und nicht darin: der eine bedient Gaeste, der
 * andere den Betrieb. Zwei Leser, zwei Dateien.
 */
final class DesignAdminController
{
    /** Beispieldaten fuer die Kacheln - dieselben wie in der Vorschau. */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
    ];

    public function index(string $locale): void
    {
        Admin::requireLogin($locale);

        $designs = Design::all();

        // Der Filter steht in der Adresse, nicht in der Sitzung: ein Link auf
        // "nur luxury" soll denselben Blick oeffnen wie beim Absender.
        $filter = Security::clean($_GET['kategorie'] ?? '', 48);
        $kategorien = [];
        foreach ($designs as $design) {
            $k = (string) $design['category'];
            if ($k !== '' && !in_array($k, $kategorien, true)) {
                $kategorien[] = $k;
            }
        }
        sort($kategorien);

        if ($filter !== '') {
            $designs = array_values(array_filter(
                $designs,
                static fn (array $d): bool => (string) $d['category'] === $filter
            ));
        }

        $styles = '';
        $warnings = [];
        foreach ($designs as $design) {
            $styles .= Design::css($design, '.d-' . $design['id']);
            $warnings[$design['id']] = Design::warnings($design);
        }

        View::page('admin/designs', [
            'layout'     => 'admin/layout',
            'locale'     => $locale,
            'current'    => '/designs',
            'meta'       => ['title' => 'Designs (v2)', 'noindex' => true],
            'designs'    => $designs,
            'warnings'   => $warnings,
            'styles'     => $styles,
            'values'     => Design::bindValues(self::BEISPIEL, $locale),
            'kategorien' => $kategorien,
            'filter'     => $filter,
            'themen'     => Themes::all(),
            'csrf'       => Security::csrf(),
        ]);
    }
}
