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
        if ($was === 'durum') {
            $this->zurueck($locale, $this->durum());
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

    /**
     * Ein altes Thema ueber die Anordnung einer vorhandenen Vorlage.
     *
     * Nicht fromTheme() allein: das Thema kennt Farben, Schriften und Bewegung,
     * aber keine Karte. Die Anordnung kommt deshalb aus einer Vorlage, die
     * jemand gemessen hat - erfunden wird nichts.
     */
    private function ausThema(): string
    {
        $thema = Themes::find(Security::clean($_POST['thema'] ?? '', 64));
        $basis = Design::findById(Security::clean($_POST['basis'] ?? '', 64));
        $name  = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($thema === null) {
            return 'fehler=thema';
        }
        if ($basis === null) {
            return 'fehler=basis';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        // Die gezeichnete Szene liegt als Datei vor, nicht im Dokument. Hat das
        // Thema keine, behaelt die neue Vorlage die der Basis - und die Meldung
        // sagt es, sonst sucht jemand die Ecken.
        $kunst = glob(__DIR__ . '/../../public/assets/designs/' . $thema['id'] . '-*.svg') ?: [];
        sort($kunst);
        $pfade = array_map(static fn (string $p): string => '/assets/designs/' . basename($p), $kunst);

        $neu = Design::copy(
            Design::dress($basis, Design::fromTheme($thema), $pfade),
            $name,
            ['de' => $name, 'en' => $name]
        );

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        Design::save($neu);

        // Wie viele Ecken hat die Basis, und wie viele bringt das Thema mit?
        // Zwei Teile ueber drei Ecken heisst: eine bleibt fremd, und das gehoert
        // gesagt statt entdeckt.
        $ecken = 0;
        foreach ($basis['layers'] as $ebene) {
            if ($ebene['type'] === 'image' && str_starts_with((string) $ebene['src'], '/assets/designs/')) {
                $ecken++;
            }
        }

        if ($pfade === []) {
            return 'ok=uebernommen_ohne_kunst';
        }
        return count($pfade) < $ecken ? 'ok=uebernommen_teilweise' : 'ok=uebernommen';
    }

    /** Aktiv/inaktiv. Hinweise halten nicht auf, aber sie werden gesagt. */
    private function durum(): string
    {
        $design = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        if ($design === null) {
            return 'fehler=quelle';
        }

        $ziel = (string) $design['status'] === 'active' ? 'inactive' : 'active';

        // Beim Abschalten fragt niemand. Beim Einschalten schon - und die
        // Hinweise werden hier neu gerechnet, nicht aus dem Formular geglaubt.
        if ($ziel === 'active' && !isset($_POST['bestaetigt'])) {
            $meldungen = Design::warnings($design);
            if ($meldungen !== []) {
                return 'frage=aktivieren&id=' . rawurlencode((string) $design['id']) . '&n=' . count($meldungen);
            }
        }

        $design['status'] = $ziel;
        Design::save($design);

        return 'ok=' . ($ziel === 'active' ? 'aktiv' : 'inaktiv');
    }

    /**
     * Editor einer Vorlage. GET zeigt, POST speichert.
     *
     * @param array<string,string> $params
     */
    public function edit(array $params): void
    {
        $locale = (string) ($params['locale'] ?? 'de');
        Admin::requireLogin($locale);

        $design = Design::find(Security::clean($params['slug'] ?? '', 96));
        if ($design === null) {
            (new PageController())->notFound($locale);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->speichere($locale, $design);
            return;
        }

        $scope  = '.d-' . $design['id'];
        $werte  = Design::bindValues(self::BEISPIEL, $locale);

        View::page('admin/design-edit', [
            'layout'   => 'admin/layout',
            'locale'   => $locale,
            'current'  => '/designs',
            'meta'     => [
                'title'   => 'Design: ' . ($design['name']['de'] ?? ''),
                'noindex' => true,
                // Nur die Vorschau braucht ein Skript. Der Rest ist ein
                // Formular und bleibt ohne.
                'scripts' => ['/assets/design-editor.js'],
            ],
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            'styles'   => Design::css($design, $scope),
            'seite'    => Design::html($design, $werte, $locale, 'page'),
            'karte'    => Design::html($design, $werte, $locale, 'card'),
            'warnings' => Design::warnings($design),
            'csrf'     => Security::csrf(),
        ]);
    }

    /** @param array<string,mixed> $design */
    private function speichere(string $locale, array $design): void
    {
        $ziel = I18n::path('/admin/designs/' . $design['slug'], $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        // Wer das Formular geoeffnet hat, hat eine Fassungsnummer mitbekommen.
        // Ist sie kleiner als die gespeicherte, hat jemand anders dazwischen
        // gespeichert - dann wird hier nichts ueberschrieben.
        $gesehen = (int) ($_POST['version'] ?? 0);
        if ($gesehen > 0 && $gesehen < (int) $design['version']) {
            header('Location: ' . $ziel . '?fehler=veraltet', true, 303);
            exit;
        }

        Design::save(Design::fromPost($design, $_POST));

        header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
        exit;
    }
}
