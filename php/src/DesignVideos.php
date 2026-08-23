<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Die Filmbibliothek der Vorlagen.
 *
 * Warum eine Bibliothek und nicht ein Feld je Vorlage: der Kunde hat gesagt,
 * das Paar findet selbst keinen Hintergrundfilm. Also stellen wir welche hin.
 * Eine Vorlage bringt ihren Standardfilm mit (die src ihrer Videoebene); die
 * Bibliothek ist das, WORAUS das Paar tauschen darf, wenn die Ebene das Recht
 * `photo` traegt.
 *
 * Gespeichert wird im JSON von site_content unter `designVideos` - kein neuer
 * Tabellenname fuer eine Liste, die selten waechst und nie einzeln abgefragt
 * wird.
 *
 * complete() ist rein: keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php.
 */
final class DesignVideos
{
    /** Wo die Liste im Dokument steht. */
    public const KEY = 'designVideos';

    /** Mehr braucht niemand, und eine Auswahl von hundert waere keine mehr. */
    public const MAX = 40;

    /**
     * Die Liste, sauber.
     *
     * Ein Eintrag ohne gueltigen mp4-Pfad faellt weg: er waere im Assistenten
     * ein Name, hinter dem nichts kommt. webm und poster duerfen fehlen.
     *
     * @param array<mixed> $rows
     * @return list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}>
     */
    public static function complete(array $rows): array
    {
        $out = [];
        $gesehen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mp4 = Design::safeSrc((string) ($row['mp4'] ?? ''));
            if ($mp4 === '') {
                continue;
            }

            // Ohne Kennung waere der Eintrag im Formular nicht adressierbar;
            // zweimal dieselbe waere ein Ort fuer zwei Filme.
            $id = Design::key((string) ($row['id'] ?? ''));
            if ($id === '' || isset($gesehen[$id])) {
                $id = bin2hex(random_bytes(4));
            }
            $gesehen[$id] = true;

            $kategorie = Design::key((string) ($row['category'] ?? ''));

            $out[] = [
                'id'       => $id,
                'label'    => Security::clean((string) ($row['label'] ?? ''), 80),
                'mp4'      => $mp4,
                'webm'     => Design::safeSrc((string) ($row['webm'] ?? '')),
                'poster'   => Design::safeSrc((string) ($row['poster'] ?? '')),
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
     * @return list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}>
     */
    public static function all(): array
    {
        $roh = Content::all()[self::KEY] ?? [];

        return self::complete(is_array($roh) ? $roh : []);
    }

    /**
     * Einen Eintrag nachschlagen.
     *
     * @return array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
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
