<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Die Bildbibliothek der Vorlagen.
 *
 * Dasselbe Muster wie DesignVideos, fuer Blaetter statt Filme: "sistemde olan
 * arkaplanlari secilebilir yap ... surekli yuklicem mi admin panelinde." Ein
 * Hintergrund gehoert oft zu mehreren Abschnitten derselben Vorlage (oder zu
 * mehreren Vorlagen) - ohne Bibliothek hiess das, dieselbe Datei je Abschnitt
 * neu hochzuladen, oder ihren Pfad von einer Stelle abzutippen.
 *
 * Gespeichert wird im JSON von site_content unter `designImages` - kein neuer
 * Tabellenname fuer eine Liste, die selten waechst und nie einzeln abgefragt
 * wird.
 *
 * complete() ist rein: keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php.
 */
final class DesignImages
{
    /** Wo die Liste im Dokument steht. */
    public const KEY = 'designImages';

    /** Mehr braucht niemand, und eine Auswahl von hundert waere keine mehr. */
    public const MAX = 40;

    /**
     * Die Liste, sauber.
     *
     * Ein Eintrag ohne gueltigen Bildpfad faellt weg: er waere in der Auswahl
     * ein Name, hinter dem nichts kommt.
     *
     * @param array<mixed> $rows
     * @return list<array{id:string,label:string,src:string,category:string}>
     */
    public static function complete(array $rows): array
    {
        $out = [];
        $gesehen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $src = Design::safeSrc((string) ($row['src'] ?? ''));
            if ($src === '') {
                continue;
            }

            // Ohne Kennung waere der Eintrag nicht adressierbar; zweimal
            // dieselbe waere ein Ort fuer zwei Bilder.
            $id = Design::key((string) ($row['id'] ?? ''));
            if ($id === '' || isset($gesehen[$id])) {
                $id = bin2hex(random_bytes(4));
            }
            $gesehen[$id] = true;

            $kategorie = Design::key((string) ($row['category'] ?? ''));

            $out[] = [
                'id'       => $id,
                'label'    => Security::clean((string) ($row['label'] ?? ''), 80),
                'src'      => $src,
                'category' => in_array($kategorie, Design::CATEGORIES, true) ? $kategorie : '',
            ];

            if (count($out) >= self::MAX) {
                break;
            }
        }

        return $out;
    }

    /**
     * Die gespeicherte Liste.
     *
     * @return list<array{id:string,label:string,src:string,category:string}>
     */
    public static function all(): array
    {
        $roh = Content::all()[self::KEY] ?? [];

        return self::complete(is_array($roh) ? $roh : []);
    }

    /** @param array<mixed> $rows */
    public static function save(array $rows): void
    {
        $sauber = self::complete($rows);

        Content::mutate(static function (array $daten) use ($sauber): array {
            $daten[self::KEY] = $sauber;

            return $daten;
        });
    }
}
