<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Listen in den Inhalten: Städte, Locations, Reportagen, Beiträge, Leistungen.
 *
 * Diese fünf Reiter machen im Kern dasselbe – einen Eintrag anhängen, einen
 * löschen, die Reihenfolge ändern. Statt das fünfmal zu schreiben, steht es
 * einmal hier; die Controller beschreiben nur noch, WELCHE Liste und welche
 * Felder ein Eintrag hat.
 *
 * Bearbeitet wird ein Eintrag über seinen Index (`cities.3.name`), nicht über
 * seinen Slug: der Slug ist die Adresse einer öffentlichen Seite und soll sich
 * nicht beim Tippen ändern.
 */
final class Lists
{
    /** Höchstzahl je Liste – schützt vor einem durchgehenden Formular. */
    private const MAX = 200;

    /** @return list<array<string,mixed>> */
    public static function all(string $key): array
    {
        return Content::list($key);
    }

    /** @return array<string,mixed>|null */
    public static function item(string $key, int $index): ?array
    {
        return self::all($key)[$index] ?? null;
    }

    /** @param array<string,mixed> $item */
    public static function add(string $key, array $item): void
    {
        Content::mutate(static function (array $content) use ($key, $item): array {
            $list = is_array($content[$key] ?? null) ? array_values($content[$key]) : [];
            if (count($list) >= self::MAX) {
                return $content;
            }
            $list[] = $item;
            $content[$key] = $list;
            return $content;
        });
    }

    /**
     * Eintrag entfernen und zurückgeben – der Aufrufer räumt danach noch
     * Dateien weg, die nur zu diesem Eintrag gehörten.
     *
     * @return array<string,mixed>|null der entfernte Eintrag
     */
    public static function remove(string $key, int $index): ?array
    {
        $removed = self::item($key, $index);
        if ($removed === null) {
            return null;
        }

        Content::mutate(static function (array $content) use ($key, $index): array {
            $list = is_array($content[$key] ?? null) ? array_values($content[$key]) : [];
            unset($list[$index]);
            $content[$key] = array_values($list);
            return $content;
        });

        return $removed;
    }

    /** Einen Platz nach oben (-1) oder unten (+1). */
    public static function move(string $key, int $index, int $delta): void
    {
        Content::mutate(static function (array $content) use ($key, $index, $delta): array {
            $list = is_array($content[$key] ?? null) ? array_values($content[$key]) : [];
            $target = $index + $delta;

            if (!isset($list[$index], $list[$target])) {
                return $content;
            }

            [$list[$index], $list[$target]] = [$list[$target], $list[$index]];
            $content[$key] = $list;
            return $content;
        });
    }

    /** Einzelne Felder eines Eintrags ändern, ohne den Rest anzufassen. @param array<string,mixed> $patch */
    public static function update(string $key, int $index, array $patch): void
    {
        Content::mutate(static function (array $content) use ($key, $index, $patch): array {
            $list = is_array($content[$key] ?? null) ? array_values($content[$key]) : [];
            if (!isset($list[$index]) || !is_array($list[$index])) {
                return $content;
            }
            $list[$index] = array_merge($list[$index], $patch);
            $content[$key] = $list;
            return $content;
        });
    }

    /* -------------------------------- Helfer -------------------------------- */

    /**
     * Freier Slug in einer Liste. Eine Stadt „Ulm“ zweimal anzulegen darf die
     * bestehende Seite nicht überschreiben.
     */
    public static function freeSlug(string $key, string $wanted, string $fallback = 'eintrag'): string
    {
        $base = Invitations::slug($wanted) ?: $fallback;
        $taken = [];
        foreach (self::all($key) as $item) {
            $taken[] = (string) ($item['slug'] ?? '');
        }

        $slug = $base;
        $n = 2;
        while (in_array($slug, $taken, true)) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /** Zweisprachiges Feld aus zwei Eingaben. @return array{de:string,tr:string} */
    public static function l10n(string $de, string $tr): array
    {
        // Nur eine Sprache ausgefüllt: die andere bekommt denselben Text,
        // damit die Seite in beiden Sprachen nicht leer bleibt.
        $de = trim($de);
        $tr = trim($tr);
        return ['de' => $de !== '' ? $de : $tr, 'tr' => $tr !== '' ? $tr : $de];
    }

    /** Index aus dem Formular, auf die Liste begrenzt. */
    public static function index(string $key, mixed $raw): ?int
    {
        $index = (int) Security::clean($raw, 6);
        return self::item($key, $index) === null ? null : $index;
    }

    /* ------------------------------- Bilder --------------------------------- */

    /**
     * Hochgeladene Bilder eines Eintrags anhängen.
     *
     * @param list<string> $urls
     */
    public static function addUploads(string $key, int $index, array $urls, string $field = 'uploads'): void
    {
        $item = self::item($key, $index);
        if ($item === null || $urls === []) {
            return;
        }

        $uploads = array_values(array_filter((array) ($item[$field] ?? []), 'is_string'));
        self::update($key, $index, [$field => array_slice(array_merge($uploads, $urls), 0, 60)]);
    }

    /**
     * Ein Bild zum Titelbild machen: an den Anfang stellen.
     *
     * Wer hundert Fotos hochlaedt, kann das erste nicht vorher wissen. Ohne
     * diesen Griff blieb nur, alles zu loeschen und in anderer Reihenfolge neu
     * hochzuladen.
     */
    public static function makeCover(string $key, int $index, int $photo, string $field = 'uploads'): void
    {
        $item = self::item($key, $index);
        if ($item === null) {
            return;
        }

        $uploads = array_values(array_filter((array) ($item[$field] ?? []), 'is_string'));
        if (!isset($uploads[$photo]) || $photo === 0) {
            return;
        }

        $gewaehlt = $uploads[$photo];
        unset($uploads[$photo]);
        array_unshift($uploads, $gewaehlt);

        self::update($key, $index, [$field => array_values($uploads)]);
    }

    /** Ein hochgeladenes Bild eines Eintrags entfernen – Datei inbegriffen. */
    public static function removeUpload(string $key, int $index, int $photo, string $field = 'uploads'): void
    {
        $item = self::item($key, $index);
        if ($item === null) {
            return;
        }

        $uploads = array_values(array_filter((array) ($item[$field] ?? []), 'is_string'));
        if (!isset($uploads[$photo])) {
            return;
        }

        $removed = (string) $uploads[$photo];
        unset($uploads[$photo]);
        self::update($key, $index, [$field => array_values($uploads)]);
        Media::delete($removed);
    }

    /** Alle Dateien eines Eintrags löschen – beim Entfernen des Eintrags. @param array<string,mixed> $item */
    public static function deleteUploads(array $item, string $field = 'uploads'): void
    {
        foreach ((array) ($item[$field] ?? []) as $url) {
            if (is_string($url)) {
                Media::delete($url);
            }
        }
    }
}
