<?php

declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Design;
use Atelier\DesignWizard;
use Atelier\I18n;
use Atelier\Security;
use Atelier\Seo;
use Atelier\View;

/**
 * Der Assistent der zweiten Fassung.
 *
 * Er steht neben dem alten (InviteController) und beruehrt ihn nicht: der alte
 * traegt Zahlung, Gutschein und Zusagen, und die kommen erst in Phase D
 * herueber. Bis dahin ist diese Seite von nirgends verlinkt - der Knopf im
 * Schaufenster zeigt weiter auf den alten Assistenten, damit hier keine
 * unbezahlte Einladung entsteht.
 */
final class InviteV2Controller
{
    /**
     * Damit die Vorschau ueberhaupt Knoten hat.
     *
     * Ein gebundenes Textelement ohne Wert wird nicht gezeichnet. Stuende hier
     * nichts, faende das Skript in Aufgabe 9 keine [data-bind]-Knoten und die
     * Live-Vorschau bliebe still - ein Fehler, den man auf einem Bildschirmfoto
     * nicht sieht. Dieselben Felder wie im Schaufenster.
     */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
        'hashtag' => '#sophiaundmaximilian',
    ];

    public function wizard(): void
    {
        $locale = I18n::locale();

        $designs = Design::all('active');
        if ($designs === []) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in welcher
            // Sprache sie aufgerufen wurde. DesignController::preview() gibt sie
            // aus genau diesem Grund mit.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        // Aus dem Schaufenster kommt die Wahl mit. Was wir nicht kennen, wird
        // nicht uebernommen, sondern durch die erste Vorlage ersetzt - eine
        // fremde Angabe steht nicht im Formular.
        $wunsch = Security::clean($_GET['design'] ?? '', 96);
        $design = $wunsch !== '' ? Design::find($wunsch) : null;
        if ($design === null || (string) $design['status'] !== 'active') {
            $design = $designs[0];
        }
        $design = Design::complete($design);

        $scope = '.d-' . $design['id'];

        View::page('pages/invite-v2-wizard', [
            'locale'  => $locale,
            'meta'    => Seo::forPage('einladung2', [
                'title'    => I18n::t('invitation2.wizardTitle'),
                'noindex'  => true,
                'scripts'  => ['/assets/invite-v2.js'],
            ]),
            'design'  => $design,
            'designs' => $designs,
            'steps'   => DesignWizard::steps($design),
            'choices' => DesignWizard::choices($design),
            'values'  => [],
            'scope'   => $scope,
            'styles'  => Design::css($design, $scope),
            // Beispieldaten, nicht leer. Design::html() ueberspringt ein
            // gebundenes Textelement, dessen Wert leer ist (Design.php:487) -
            // mit leeren Daten stuenden vier der sechs Elemente gar nicht erst
            // im DOM, und die Vorschau in Aufgabe 9 fuellte etwas, das es nicht
            // gibt. Das Skript leert sie beim ersten Lauf wieder.
            'karte'   => Design::html($design, Design::bindValues(self::BEISPIEL, $locale), $locale, 'card'),
            'csrf'    => Security::csrf(),
            'error'   => '',
            'done'    => null,
        ]);
    }
}
