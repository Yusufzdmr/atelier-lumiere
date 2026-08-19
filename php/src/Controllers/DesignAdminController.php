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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handle($locale);
            return;
        }

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

    /** POST-Zweig: aendert etwas und leitet auf dieselbe Adresse zurueck. */
    private function handle(string $locale): void
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            $this->zurueck($locale, 'fehler=csrf');
        }

        $was = (string) ($_POST['was'] ?? '');

        if ($was === 'kopyala') {
            $this->zurueck($locale, $this->kopiere());
        }
        if ($was === 'temadan') {
            $this->zurueck($locale, $this->ausThema());
        }

        $this->zurueck($locale, 'fehler=unbekannt');
    }

    /** 303, damit ein Neuladen die Aktion nicht wiederholt. */
    private function zurueck(string $locale, string $query): never
    {
        header('Location: ' . I18n::path('/admin/designs', $locale) . ($query !== '' ? '?' . $query : ''), true, 303);
        exit;
    }

    /** Eine vorhandene Vorlage als neuer Entwurf. */
    private function kopiere(): string
    {
        $quelle = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        $name   = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($quelle === null) {
            return 'fehler=quelle';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        $neu = Design::copy($quelle, $name, ['de' => $name, 'en' => $name]);

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        Design::save($neu);
        return 'ok=kopiert';
    }

    /** Ein altes Thema als neues Dokument. */
    private function ausThema(): string
    {
        $thema = Themes::find(Security::clean($_POST['thema'] ?? '', 64));
        $name  = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($thema === null) {
            return 'fehler=thema';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        $neu = Design::copy(Design::fromTheme($thema), $name, ['de' => $name, 'en' => $name]);

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        // Die gezeichnete Szene liegt als Datei vor, nicht im Dokument. Fehlt
        // sie, entsteht die Vorlage trotzdem - aber die Meldung sagt, was noch
        // fehlt, sonst sucht jemand eine halbe Stunde nach leeren Ecken.
        $kunst = glob(__DIR__ . '/../../public/assets/designs/' . $thema['id'] . '-*.svg') ?: [];

        Design::save($neu);
        return $kunst === [] ? 'ok=uebernommen_ohne_kunst' : 'ok=uebernommen';
    }
}
