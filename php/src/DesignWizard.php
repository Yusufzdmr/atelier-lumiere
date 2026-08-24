<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Was der Kunde am Design aendern darf - und was der Assistent ihn fragt.
 *
 * Zwei Fragen, zwei Quellen, und sie werden leicht verwechselt:
 *
 *   Welche Felder frage ich?   -> die bind-Namen, die das Design benutzt.
 *                                 Ohne Recht, ohne Haken: die Werte einer
 *                                 Einladung kommen immer aus den Daten.
 *   Was biete ich darueber
 *   hinaus an?                 -> die Rechte (Design::PERMISSIONS) und die
 *                                 customer-Haken der Marken.
 *
 * Wer nur die Rechte liest, baut einen leeren Assistenten: im heutigen Bestand
 * steht fast jedes Recht auf false.
 *
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php, das keine config.php kennt.
 */
final class DesignWizard
{
    /** In dieser Reihenfolge stehen die Felder im Formular. */
    public const FIELD_ORDER = ['bride', 'groom', 'date', 'time', 'venue', 'address', 'message', 'hashtag'];

    /**
     * Welches Feld hinter welchem bind steckt.
     *
     * Weniger Felder als binds: vier Namen ziehen dieselben zwei Felder. Die
     * Karte steht hier und nicht in der Vorlage - sonst muesste jede Vorlage,
     * die den Assistenten zeichnet, sie noch einmal kennen.
     */
    private const BIND_FIELDS = [
        'couple_names'     => ['bride', 'groom'],
        'initials'         => ['bride', 'groom'],
        'bride_name'       => ['bride'],
        'groom_name'       => ['groom'],
        'wedding_date'     => ['date'],
        'wedding_weekday'  => ['date'],
        'wedding_time'     => ['time'],
        'location_name'    => ['venue'],
        'location_address' => ['address'],
        'invitation_text'  => ['message'],
        'hashtag'          => ['hashtag'],
    ];

    /**
     * Alles, was der Assistent zu diesem Design anbieten darf.
     *
     * @param array<string,mixed> $doc
     * @return array{fields:list<string>,palette:array<string,mixed>,fonts:array<string,mixed>,layers:array<string,array<string,bool>>,sections:array<string,array<string,mixed>>}
     */
    public static function choices(array $doc): array
    {
        $doc = Design::complete($doc);

        $felder = [];
        foreach ($doc['layers'] as $el) {
            foreach (self::BIND_FIELDS[(string) $el['bind']] ?? [] as $feld) {
                $felder[$feld] = true;
            }
        }
        // Nach FIELD_ORDER, nicht nach Fundort: sonst haengt die Reihenfolge
        // im Formular daran, wie der Grafiker die Ebenen sortiert hat.
        $fields = array_values(array_filter(self::FIELD_ORDER, static fn (string $f): bool => isset($felder[$f])));

        $palette = array_filter($doc['palette'], static fn (array $e): bool => (bool) $e['customer']);
        $fonts   = array_filter($doc['fonts'], static fn (array $e): bool => (bool) $e['customer']);

        $layers = [];
        foreach ($doc['layers'] as $el) {
            $p = $el['permissions'];
            // edit ist der Hauptschalter. Eine Ebene mit fuenf Haken und ohne
            // edit ist gesperrt - so ist Sperren ein Haken und nicht fuenf.
            if (!$p['edit']) {
                continue;
            }

            $rechte = [
                // button fehlt hier absichtlich: Design::css() hat fuer
                // diesen Typ gar keinen Zweig (nur text und shape) - eine
                // Kontrolle anzubieten, die nie etwas veraendert, waere ein
                // Versprechen, das die Vorlage nicht haelt.
                'color' => $p['color'] && in_array($el['type'], ['text', 'shape'], true),
                'font'  => $p['font'] && $el['type'] === 'text',
                // Ein bind holt seinen Wert aus den Daten. Ein fester Text
                // daneben waere eine zweite Wahrheit, die nie gewinnt.
                'text'  => $p['text'] && $el['bind'] === '' && in_array($el['type'], ['text', 'button'], true),
                // video haengt am selben Recht wie photo: es ist dieselbe
                // Frage - "darf das Paar den Inhalt dieser Flaeche tauschen".
                // Ein siebtes Recht waere ein zweiter Name fuer eine Sache.
                'photo' => $p['photo'] && in_array($el['type'], ['image', 'photo', 'video'], true),
                'hide'  => $p['hide'],
            ];

            if (in_array(true, $rechte, true)) {
                $layers[(string) $el['id']] = $rechte;
            }
        }

        /*
         * Abschnitte: dieselbe Weissliste wie bei den Ebenen. edit ist der
         * Hauptschalter; ohne ihn wird der Abschnitt gar nicht angeboten.
         * fields sagt, wonach der Assistent fragen muss - location und
         * countdown stehen nicht darin, weil sie von den Angaben leben, die
         * ohnehin gefragt werden.
         */
        $sections = [];
        foreach (DesignSections::complete($doc)['sections'] as $abschnitt) {
            if (!$abschnitt['permissions']['edit']) {
                continue;
            }
            // Ein abgeschalteter Abschnitt wird nicht angeboten: sonst
            // fuellte der Kunde ein Feld, das visible() beim Drucken ohnehin
            // wegwirft - eingegebener Inhalt, der spurlos verschwindet.
            if (!$abschnitt['enabled']) {
                continue;
            }
            /*
             * Was das Paar in DIESEN Abschnitt schreibt, sagt der Katalog.
             *
             * Frueher stand hier ein match() mit einem Zweig je Art. Eine
             * neue Art mit eigenem Feld hiess dann: hier ein Zweig, in der
             * Vorlage des Assistenten ein if, im Controller noch eins, und
             * beim Vorbelegen ein viertes. Wer eines davon vergass, bekam ein
             * Feld, das sich fuellen aber nicht speichern liess.
             *
             * families und program bleiben Sonderfaelle: sie schreiben NICHT
             * unter die Kennung des Abschnitts, sondern an feste Plaetze im
             * Dokument (data.families, data.program) - von ihnen gibt es je
             * einen, und die Karte bindet sie ueber ihre Namen.
             */
            $eingaben = SectionRegistry::inputs((string) $abschnitt['type']);

            $sections[(string) $abschnitt['id']] = [
                'type'   => (string) $abschnitt['type'],
                // Der Titel des Grafikers, nicht die interne Kennung - die
                // Vorlage soll dem Kunden nicht "prog-1" vorsetzen muessen.
                'title'  => $abschnitt['title'],
                'hide'   => (bool) $abschnitt['permissions']['hide'],
                'fields' => match ((string) $abschnitt['type']) {
                    'family'  => ['families'],
                    'program' => ['program'],
                    default   => array_keys($eingaben),
                },
                // Dasselbe noch einmal, aber mit Art, Obergrenze und Etikett:
                // daraus baut der Assistent seine Eingabefelder.
                'inputs' => $eingaben,
                // Was die Vorlage vorschlaegt. Der Assistent legt es ins Feld,
                // damit das Paar es ueberschreiben kann statt vor einem leeren
                // Kasten zu sitzen - und wer nichts aendert, bekommt genau
                // das, was im Schaufenster stand.
                'defaults' => $abschnitt['defaults'] ?? [],
            ];
        }

        return ['fields' => $fields, 'palette' => $palette, 'fonts' => $fonts, 'layers' => $layers, 'sections' => $sections];
    }

    /**
     * Welche Schritte dieses Design braucht.
     *
     * Nicht fest verdrahtet: ein Design ohne Rechte hat zwei Schritte, eines
     * mit Bildern und Farben vier. Ein leerer Schritt ist ein Bildschirm, auf
     * dem nichts zu tun ist - der wird nicht gezeigt.
     *
     * @param array<string,mixed> $doc
     * @return list<string>
     */
    public static function steps(array $doc): array
    {
        $w = self::choices($doc);

        $schritte = ['angaben'];

        foreach ($w['layers'] as $rechte) {
            if ($rechte['photo']) {
                $schritte[] = 'bilder';
                break;
            }
        }

        // Inhalt vor Aussehen: erst was draufsteht, dann wie es aussieht.
        // Ein Abschnitt mit edit=true, hide=false und ohne fields (z.B.
        // location oder countdown) hat aber keine einzige Kontrolle im
        // Schritt - dann bliebe nur eine Ueberschrift ueber einem leeren
        // Bildschirm stehen, und ein leerer Schritt ist verboten.
        foreach ($w['sections'] as $abschnitt) {
            if ($abschnitt['hide'] || $abschnitt['fields'] !== []) {
                $schritte[] = 'abschnitte';
                break;
            }
        }

        $design = $w['palette'] !== [] || $w['fonts'] !== [];
        if (!$design) {
            foreach ($w['layers'] as $rechte) {
                if ($rechte['color'] || $rechte['font'] || $rechte['text'] || $rechte['hide']) {
                    $design = true;
                    break;
                }
            }
        }
        if ($design) {
            $schritte[] = 'design';
        }

        $schritte[] = 'veroeffentlichen';

        return $schritte;
    }

    /**
     * Die Wahl des Kunden auf das Design legen.
     *
     * Das Ergebnis ist ein vollstaendiges Dokument, das der Renderer aus
     * Phase 1 ohne eine einzige neue Zeile druckt.
     *
     * Es ist NICHT der design_snapshot. Bis zur Phase "Bearbeiten nach dem
     * Veroeffentlichen" war es das: publish() fror das Ergebnis ein und warf
     * die Eingabe weg. Damit war jede spaetere Aenderung verlustbehaftet.
     * Seitdem haelt die Spalte die unpersonalisierte Vorlage, die Wahl steht
     * in data['wahl'], und diese Funktion laeuft bei jedem Druck neu.
     *
     * Zwei Eigenschaften traegt der Bearbeiten-Bildschirm auf dem Ruecken, und
     * beide sind in tests/invitations_v2_edit.php gemessen: mit leerer Wahl ist
     * sie die Identitaet (deshalb funktionieren alte Einladungen unveraendert
     * weiter), und mit derselben Wahl zweimal angewendet aendert sie nichts
     * (deshalb darf zweimal gespeichert werden).
     *
     * Weissliste: gefragt wird immer zuerst choices(). Was dort nicht steht,
     * faellt still - siehe Kommentar im Test.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $wahl
     * @return array<string,mixed>
     */
    public static function personalize(array $doc, array $wahl): array
    {
        $doc  = Design::complete($doc);
        $doc  = DesignSections::complete($doc);
        $darf = self::choices($doc);

        foreach ((array) ($wahl['palette'] ?? []) as $key => $wert) {
            if (isset($darf['palette'][$key])) {
                $doc['palette'][$key]['value'] = Design::safeColor((string) $wert);
            }
        }

        foreach ((array) ($wahl['fonts'] ?? []) as $key => $wert) {
            if (isset($darf['fonts'][$key])) {
                $doc['fonts'][$key]['family'] = Design::safeFont((string) $wert);
            }
        }

        $layers = (array) ($wahl['layers'] ?? []);

        foreach ($doc['layers'] as $i => $el) {
            $id = (string) $el['id'];
            $rechte = $darf['layers'][$id] ?? null;
            $gewaehlt = $layers[$id] ?? null;

            if ($rechte === null || !is_array($gewaehlt)) {
                continue;
            }

            // Eine eigene Farbe wird eine eigene Marke. Der Renderer kennt nur
            // Markennamen: color:var(--d-<name>). Ein roher Wert ergaebe
            // var(--d-#8B0000) - ungueltiges CSS und ein farbloses Element.
            if ($rechte['color'] && isset($gewaehlt['color'])) {
                // "kunde-" ist kein reserviertes Praefix - ein Admin kann im
                // Panel schon eine Marke mit genau diesem Namen angelegt
                // haben, bevor je ein Kunde durch den Assistenten ging. Ohne
                // diese Pruefung ueberschriebe die Wahl des Kunden die Marke
                // des Grafikers still, und die Kollision friere sich in
                // jeden kuenftigen Schnappschuss ein.
                $marke = self::freieMarke($doc, 'palette', $id, (string) ($el['style']['color'] ?? ''));
                $doc['palette'][$marke] = [
                    'value'    => Design::safeColor((string) $gewaehlt['color']),
                    'label'    => ['de' => 'Eigene Farbe', 'tr' => 'Kendi rengi'],
                    // Das Ergebnis der Wahl, nicht eine Wahl, die man wieder
                    // anbietet: sonst stuende sie beim Bearbeiten doppelt da.
                    'customer' => false,
                ];
                $doc['layers'][$i]['style']['color'] = $marke;
            }

            if ($rechte['font'] && isset($gewaehlt['font'])) {
                // Dieselbe Kollisionsgefahr wie bei der Farbe, siehe oben.
                $marke = self::freieMarke($doc, 'fonts', $id, (string) ($el['style']['font'] ?? ''));
                $doc['fonts'][$marke] = [
                    'family'     => Design::safeFont((string) $gewaehlt['font']),
                    'size'       => $doc['fonts'][$el['style']['font']]['size'] ?? 100,
                    'weight'     => $doc['fonts'][$el['style']['font']]['weight'] ?? 400,
                    'tracking'   => $doc['fonts'][$el['style']['font']]['tracking'] ?? 0,
                    'lineHeight' => $doc['fonts'][$el['style']['font']]['lineHeight'] ?? 120,
                    'customer'   => false,
                ];
                $doc['layers'][$i]['style']['font'] = $marke;
            }

            if ($rechte['text'] && isset($gewaehlt['text']) && is_array($gewaehlt['text'])) {
                // Ein leeres Feld ist kein Loeschbefehl - dieselbe Regel wie
                // Design::fromPost(). Sonst loescht eine Wahl, die nur "de"
                // mitschickt, stillschweigend das vorhandene "en".
                foreach (['de', 'en'] as $sprache) {
                    $wert = Security::clean((string) ($gewaehlt['text'][$sprache] ?? ''), 600);
                    if ($wert !== '') {
                        $doc['layers'][$i]['text'][$sprache] = $wert;
                    }
                }
            }

            if ($rechte['photo'] && isset($gewaehlt['src'])) {
                // Beim Schreiben geklaert, nicht erst beim Drucken (siehe
                // Design::safeSrc()). Ein leerer Rueckgabewert heisst
                // "kein gueltiger Pfad" - dann bleibt der alte Pfad stehen,
                // statt eine leere Bildquelle einzufrieren.
                $pfad = Design::safeSrc((string) $gewaehlt['src']);
                if ($pfad !== '') {
                    $doc['layers'][$i]['src'] = $pfad;
                }

                // Der Poster reist mit der Quelle, sonst stuende hinter dem
                // gewaehlten Film das Standbild des vorigen. Er darf leer
                // werden: ein Film aus der Bibliothek ohne Standbild hat
                // keines, und das alte waere dann schlicht falsch.
                if (isset($gewaehlt['poster'])) {
                    $doc['layers'][$i]['poster'] = Design::safeSrc((string) $gewaehlt['poster']);
                }
            }

            if ($rechte['hide'] && !empty($gewaehlt['hidden'])) {
                unset($doc['layers'][$i]);
            }
        }

        // Nach dem Entfernen einer Ebene sind die Schluessel loechrig; die
        // Reihenfolge ist der z-Index, also wird neu gezaehlt und nicht sortiert.
        $doc['layers'] = array_values($doc['layers']);

        /*
         * Ein abgeschalteter Abschnitt wird nicht geloescht, sondern auf
         * enabled=false gesetzt: das Dokument behaelt, was der Grafiker
         * aufgestellt hat, und beim spaeteren Bearbeiten steht der Abschnitt
         * wieder zur Wahl.
         */
        $sekWahl = (array) ($wahl['sections'] ?? []);
        foreach ($doc['sections'] as $j => $abschnitt) {
            $id = (string) $abschnitt['id'];
            if (!isset($darf['sections'][$id]) || !$darf['sections'][$id]['hide']) {
                continue;
            }
            if (!empty($sekWahl[$id]['hidden'])) {
                $doc['sections'][$j]['enabled'] = false;
            }
        }

        // Noch einmal durch beide Normalisierer: die gepraegten Marken
        // bekommen ihre Standardfelder, und der Schnappschuss hat garantiert
        // die Form, die Design::css(), Design::html() und die Vorlage der
        // Abschnitte erwarten.
        return DesignSections::complete(Design::complete($doc));
    }

    /**
     * Einen freien Markennamen fuer die gepraegte "kunde-<id>"-Konvention finden.
     *
     * Zeigt die Ebene schon auf genau diesen Namen, ist es die eigene Marke aus
     * einer frueheren Personalisierung derselben Ebene - die wird wiederverwendet,
     * damit ein zweites Absenden keine zweite Marke praegt (siehe Test). Steht
     * der Name aber schon im eingehenden Dokument und gehoert nicht dieser
     * Ebene, ist es die Marke des Grafikers - dann wird ein Ausweichname
     * angehaengt, statt sie still zu ueberschreiben.
     *
     * @param array<string,mixed> $doc
     */
    private static function freieMarke(array $doc, string $bereich, string $id, string $aktuell): string
    {
        $basis = 'kunde-' . $id;

        if ($aktuell === $basis && isset($doc[$bereich][$basis])) {
            return $basis;
        }
        if (!isset($doc[$bereich][$basis])) {
            return $basis;
        }

        $n = 2;
        while (isset($doc[$bereich][$basis . '-' . $n])) {
            $n++;
        }
        return $basis . '-' . $n;
    }
}
