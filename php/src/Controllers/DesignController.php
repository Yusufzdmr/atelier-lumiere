<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Config;
use Atelier\Design;
use Atelier\DesignSections;
use Atelier\I18n;
use Atelier\Seo;
use Atelier\Security;
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
        // Nur was veroeffentlicht ist. Ein Entwurf ist eine halbe Vorlage, und
        // eine halbe Vorlage im Schaufenster kostet Vertrauen.
        $designs = Design::all('active');

        $kategorien = [];
        foreach ($designs as $eintrag) {
            $k = (string) $eintrag['category'];
            if ($k !== '' && !in_array($k, $kategorien, true)) {
                $kategorien[] = $k;
            }
        }
        sort($kategorien);

        // Der Filter steht in der Adresse, nicht in der Sitzung: ein geteilter
        // Link soll denselben Blick oeffnen.
        $filter = Security::clean($_GET['kategorie'] ?? '', 48);
        if ($filter !== '') {
            $designs = array_values(array_filter(
                $designs,
                static fn (array $d): bool => (string) $d['category'] === $filter
            ));
        }

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
                // Kein Titelbild: der Kopf startet hell, sonst steht seine
                // helle Schrift auf cremefarbenem Grund (siehe layout.php).
                'solidHeader' => true,
                'canonical' => Config::url() . I18n::path('/v2/designs', $locale),
            ]),
            'designs' => $designs,
            'styles'  => $styles,
            'values'  => Design::bindValues(self::BEISPIEL, $locale),
            'kategorien' => $kategorien,
            'filter'     => $filter,
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

        /*
         * Beispieldaten fuer die Abschnitte.
         *
         * BEISPIEL allein reicht nicht: die Abschnitte lesen families und
         * program aus eigenen Schluesseln, und ohne sie blieben genau die
         * zwei Abschnitte leer, die eine Einladung lang machen. Was hier
         * steht, ist dasselbe Paar wie auf der Karte - Sophia und Maximilian
         * heiraten hier seit Faz 1.
         */
        $beispieldaten = self::BEISPIEL + [
            'families' => ['bride' => 'Familie Berger', 'groom' => 'Familie Lindqvist'],
            'program'  => [
                ['time' => '15:30', 'title' => $locale === 'de' ? 'Trauung' : 'Ceremony'],
                ['time' => '17:00', 'title' => $locale === 'de' ? 'Empfang' : 'Reception'],
                ['time' => '19:30', 'title' => $locale === 'de' ? 'Dinner' : 'Dinner'],
                ['time' => '22:00', 'title' => $locale === 'de' ? 'Tanz' : 'Dancing'],
            ],
        ];

        /*
         * Ein Textabschnitt ohne Text ist unsichtbar - visible() laesst ihn
         * weg, und zu Recht: eine leere Ueberschrift ist schlimmer als keine.
         * Im Schaufenster hiesse das aber, dass ausgerechnet der Abschnitt
         * fehlt, in den das Paar seine eigenen Worte schreibt. Also bekommt
         * jeder Textabschnitt der Vorlage hier einen Beispielsatz - nach
         * Kennung, nicht nach Position, damit es auch bei zweien stimmt.
         */
        foreach ($design['sections'] as $abschnitt) {
            if ((string) ($abschnitt['type'] ?? '') !== 'text') {
                continue;
            }
            $beispieldaten['sections'][(string) $abschnitt['id']]['text'] = $locale === 'de'
                ? 'Hier stehen eure eigenen Worte – die Geschichte, die Kleiderordnung, '
                  . 'der Hinweis auf die Parkplätze. Was ihr schreibt, steht hier.'
                : 'Your own words go here – the story, the dress code, a note about '
                  . 'parking. Whatever you write stands here.';
        }
        // Einmal abgefragt, zweimal gebraucht: fuer den Entwicklerbalken
        // unten und dafuer, ob Warnungen ueberhaupt herausgehen.
        $intern = Admin::isLoggedIn();

        View::page('pages/design-preview', [
            'locale'   => $locale,
            'path'     => I18n::path('/v2/designs/' . $design['slug'], $locale),
            'meta'     => Seo::forPage('design-preview', [
                'title'     => $design['name'][$locale] ?? $design['name']['de'],
                'noindex'   => true,
                'canonical' => Config::url() . I18n::path('/v2/designs/' . $design['slug'], $locale),
                /*
                 * Ohne Kopf und Fuss des Hauses - wie die echte Einladung
                 * (InviteV2Controller: 'bare' => true).
                 *
                 * Solange die Buehne fest ueber allem lag, deckte sie den
                 * Kopf zu und niemand sah ihn. Jetzt steht sie im Fluss, und
                 * der Kopf ist "fixed top-0 z-50": er legte sich quer ueber
                 * die Goldecken der Karte. Angesehen, nicht vermutet.
                 *
                 * Die zwei Wege hinaus gehen nicht verloren - der feste
                 * Balken unten traegt sie ohnehin: zurueck zur Auswahl, oder
                 * mit dieser Vorlage anfangen.
                 */
                'bare'      => true,
                // Die Choreografie wird geteilt, nicht nachgebaut: dasselbe
                // Skript wie bei der ersten Fassung. Es kennt keine Farben und
                // keine Formen - es oeffnet ein Kuvert und laesst eine Karte
                // aufsteigen. Das ist Verhalten des Betrachters, nicht Teil
                // des Designs, und gehoert deshalb nicht ins Dokument.
                'scripts'   => ['/assets/invitation.js'],
            ]),
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            // Die Abschnitte bringen eigene Regeln mit - ohne sie stuenden sie
            // ungestylt unter der Karte.
            'styles'   => Design::css($design, $scope) . DesignSections::css($design, $scope),
            /*
             * Das Schaufenster zeigt jetzt die GANZE Einladung, nicht nur ihr
             * Deckblatt.
             *
             * Bis hierher lag die Buehne fest ueber allem ("hier gibt es
             * nichts, was darunter scrollen muesste") - und das stimmte, als
             * eine Vorlage nur aus Ebenen bestand. Seit Faz 3C hat sie
             * Abschnitte, Élysée traegt vier davon, und wer im Schaufenster
             * nach unten wischte, fand nichts. Genau das ist aufgefallen: "asagi
             * dogru kaydiriyorum, hani nerede davetiyenin bilgileri".
             *
             * Kein Formular: das dritte Argument ist leer und sent=false. Eine
             * Vorschau nimmt keine Zusagen entgegen.
             */
            'abschnitte' => DesignSections::html($design, $beispieldaten, $locale, '', ['csrf' => '', 'sent' => false]),
            // Drei Ebenenlisten statt einer: die Vorschau schachtelt sie.
            'initialen' => $values['initials'],
            'seite'    => Design::html($design, $values, $locale, 'page'),
            'kuvert'   => Design::html($design, $values, $locale, 'envelope'),
            'karte'    => Design::html($design, $values, $locale, 'card'),
            // Warnungen sind ein Arbeitsvermerk fuer den, der die Vorlage
            // bearbeitet - nicht etwas, das einen Kunden angeht, der eine
            // Karte im Schaufenster anschaut. Nur angemeldet zeigen, sonst
            // leer.
            'warnings' => $intern ? Design::warnings($design) : [],
            // Der Balken unten ist ein Entwicklerbalken: Fassung, Ebenenzahl,
            // Bewegungsachsen. Wer angemeldet ist, arbeitet an der Vorlage; wer
            // nicht, will sie ansehen und braucht zwei Links statt sieben Zahlen.
            'intern'   => $intern,
        ]);
    }
}
