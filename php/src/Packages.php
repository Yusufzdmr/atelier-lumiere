<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Die Pakete der Fotografie - und das Rechnen damit.
 *
 * Eine eigene Datei und nicht ein Kapitel in Pricing.php: dort stehen die
 * Preise der digitalen EINLADUNG (BASE, SECTIONS, total()), und die haben mit
 * Fotopaketen nichts zu tun. Zwei Preiswelten in einer Klasse waeren zwei
 * Bedeutungen fuer jedes Wort darin.
 *
 * "Bu ek islerde yani pakete gelecek ilave bolumu - tikladikca paket fiyati
 * oynamasi lazim. (...) Odeme sistemi olmayacak ama musteri ne odeyecek, ne
 * alacak gorsun, forma eklensin."
 *
 * Die Zahlen stehen als TEXT im Inhaltsdokument ("1.890 €", "+ 450 €") - der
 * Kunde tippt sie im Panel, und dort ist ein Feld ein Feld. Ein zweites,
 * rein numerisches Feld daneben haette neun Eintraege noch einmal verlangt
 * und jedem kuenftigen Preis eine zweite Wahrheit gegeben. Also wird gelesen,
 * was da steht.
 */
final class Packages
{
    /**
     * Ein Preistext als Betrag in Cent - oder nichts.
     *
     * Nichts und nicht eine Schaetzung: "ab 250 €" ist kein Preis, sondern
     * ein Satz ueber einen Preis, und "auf Anfrage" ist die ausdrueckliche
     * Weigerung, einen zu nennen. Wer daraus 250 macht, zeigt eine Summe, die
     * niemand versprochen hat - und genau diese Sorte Fehler ist hier die
     * teuerste, weil sie nicht auffaellt.
     *
     * Deshalb die harte Regel: steht ein BUCHSTABE darin, ist es keine Zahl.
     * Erlaubt sind nur ein fuehrendes Plus, deutsche Tausenderpunkte, ein
     * Komma mit zwei Stellen und das Eurozeichen.
     */
    public static function amount(string $text): ?int
    {
        $roh = trim($text);
        if ($roh === '') {
            return null;
        }

        // Das Plus davor ist Anzeige ("+ 450 €") und keine Rechenanweisung -
        // ein Zusatz kostet, was dasteht.
        $roh = ltrim($roh, "+ \t");

        // Waehrung hinten weg, in beiden Schreibweisen. Ein geschuetztes
        // Leerzeichen steht zwischen Zahl und Zeichen oefter, als man denkt:
        // es kommt aus Word und aus jedem Panel, in das jemand einfuegt.
        $roh = str_replace(["\u{00a0}", "\u{202f}"], ' ', $roh);
        $roh = trim(preg_replace('/\s*(€|EUR)\s*$/iu', '', $roh) ?? '');

        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $roh) !== 1
            && preg_match('/^\d+(,\d{1,2})?$/', $roh) !== 1) {
            return null;
        }

        [$ganz, $rest] = array_pad(explode(',', $roh, 2), 2, '');
        $euro = (int) str_replace('.', '', $ganz);
        $cent = (int) str_pad($rest, 2, '0');

        return $euro * 100 + $cent;
    }

    /**
     * Ein Betrag, wie ihn dieselbe Seite sonst auch schreibt.
     *
     * Deutsche Schreibweise in allen Sprachen, und das ist kein Versehen: die
     * Preise stehen als Text im Dokument und sind dort deutsch geschrieben
     * ("1.890 €"). Eine Summe in englischer Schreibweise stuende neben ihren
     * eigenen Posten und saehe aus wie ein Fehler.
     *
     * Volle Euro ohne Nachkomma - so stehen die Posten heute da, und eine
     * Summe mit ",00" waere genauer, als die Angaben es sind.
     */
    public static function money(int $cent): string
    {
        $euro = intdiv($cent, 100);
        $rest = $cent % 100;

        $zahl = number_format($euro, 0, ',', '.');

        return $rest === 0
            ? $zahl . ' €'
            : $zahl . ',' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT) . ' €';
    }

    /**
     * Was gewaehlt wurde, was es kostet - und was sich nicht rechnen laesst.
     *
     * Die Auswahl kommt als Nummer und nicht als Name: die Seite schickt
     * sie an das Kontaktformular weiter, und ein Name reiste dabei durch die
     * Adresszeile. Eine Nummer, die es nicht gibt, faellt weg.
     *
     * `offen` sagt, dass etwas Gewaehltes keinen rechenbaren Preis hat. Die
     * Summe stimmt dann fuer den Rest, und die Seite muss es dazusagen -
     * eine Summe, die schweigend zu klein ist, waere die schlechtere Antwort
     * als gar keine.
     *
     * @param list<array<string,mixed>> $packages
     * @param list<array<string,mixed>> $addons
     * @param list<string|int>          $extras
     * @return array{lines:list<array{label:string,price:string,cent:int|null}>,total:int,offen:bool}
     */
    public static function summary(array $packages, array $addons, string $paket, array $extras, string $locale): array
    {
        $lines = [];
        $total = 0;
        $offen = false;

        $nimm = static function (array $eintrag) use ($locale, &$lines, &$total, &$offen): void {
            $preis = (string) ($eintrag['price'] ?? '');
            $cent  = self::amount($preis);

            $lines[] = [
                'label' => I18n::pick($eintrag['name'] ?? null, $locale),
                'price' => $preis,
                'cent'  => $cent,
            ];

            if ($cent === null) {
                $offen = true;
                return;
            }

            $total += $cent;
        };

        if ($paket !== '' && ctype_digit($paket) && isset($packages[(int) $paket])) {
            $nimm($packages[(int) $paket]);
        }

        // Doppelte Nummern fallen weg: dieselbe Zusatzleistung zweimal zu
        // waehlen ist keine Bestellung von zwei Stueck, sondern eine Adresse,
        // an der jemand gedreht hat.
        $gesehen = [];
        foreach ($extras as $nummer) {
            $nummer = (string) $nummer;
            if (!ctype_digit($nummer) || isset($gesehen[$nummer]) || !isset($addons[(int) $nummer])) {
                continue;
            }
            $gesehen[$nummer] = true;
            $nimm($addons[(int) $nummer]);
        }

        return ['lines' => $lines, 'total' => $total, 'offen' => $offen];
    }

    /**
     * Die Auswahl als Satz fuer das Nachrichtenfeld.
     *
     * Ins Nachrichtenfeld und nicht in ein eigenes: dort steht sie im Mailtext,
     * in der Liste der Anfragen und vor den Augen dessen, der sie abschickt -
     * und er kann sie aendern. Ein verstecktes Feld haette eine Spalte in
     * `leads` gebraucht, eine Wanderung durch Mail und Panel, und der Absender
     * saehe nicht, was er mitschickt.
     */
    public static function asText(array $summary, string $locale): string
    {
        if ($summary['lines'] === []) {
            return '';
        }

        $zeilen = [];
        foreach ($summary['lines'] as $zeile) {
            $zeilen[] = '- ' . $zeile['label'] . ': ' . $zeile['price'];
        }

        /*
         * Die Worte stehen hier und nicht im Woerterbuch, obwohl sie dort
         * beinahe genauso stehen (prices.sumOpen).
         *
         * Der Grund ist der Weg: I18n::t geht ueber Texts und damit ueber das
         * Inhaltsdokument, also ueber die Datenbank. Ein Satz, der in einer
         * Mail landet, soll nicht davon abhaengen, ob gerade eine Verbindung
         * steht.
         *
         * "Ohne festen Preis" und nicht "auf Anfrage": der Posten, an dem es
         * hier zuerst auffiel, heisst "Anfahrt über 60 km – 0,40 €/km". Das
         * IST ein Preis, nur keiner, den man addieren kann.
         */
        $wort = match ($locale) {
            'tr'    => ['kopf' => 'Seçtiklerim', 'summe' => 'Toplam',
                        'offen' => 'Sabit fiyatı olmayan kalemler toplama dahil değildir.'],
            'en'    => ['kopf' => 'My selection', 'summe' => 'Total',
                        'offen' => 'Items without a fixed price are not included.'],
            default => ['kopf' => 'Meine Auswahl', 'summe' => 'Summe',
                        'offen' => 'Posten ohne festen Preis sind nicht eingerechnet.'],
        };

        $summe = $wort['summe'] . ': ' . self::money($summary['total'])
            . ($summary['offen'] ? "\n" . $wort['offen'] : '');

        return $wort['kopf'] . ":\n" . implode("\n", $zeilen) . "\n" . $summe . "\n\n";
    }
}
