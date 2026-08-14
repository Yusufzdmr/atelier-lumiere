<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Redaktionelle Inhalte – dieselbe Struktur wie in der Next.js-Fassung.
 *
 * Gelesen wird einmal pro Aufruf, danach aus dem Speicher. Geschrieben wird
 * nur im Adminbereich; wer dort etwas ändert, ändert das ganze Dokument.
 */
final class Content
{
    /** @var array<string,mixed>|null */
    private static ?array $cache = null;

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $data = Db::json('SELECT data FROM site_content WHERE id = 1');
        if ($data === null) {
            // Leere Datenbank: lieber eine sprechende Meldung als hundert
            // Warnungen über fehlende Schlüssel.
            error_log('[content] site_content ist leer – bin/import.php ausgeführt?');
            $data = [];
        }

        self::$cache = $data;
        return $data;
    }

    /** Ein Zweig: get('hero'), get('contact') … @return array<string,mixed> */
    public static function get(string $key): array
    {
        $value = self::all()[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    /** Liste: list('cities'), list('venues') … @return list<array<string,mixed>> */
    public static function list(string $key): array
    {
        $value = self::all()[$key] ?? [];
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }

    /** Einzelnes Feld über einen Pfad: field('contact.email') */
    public static function field(string $path, string $default = ''): string
    {
        $node = self::all();
        foreach (explode('.', $path) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return $default;
            }
            $node = $node[$part];
        }
        return is_string($node) ? $node : $default;
    }

    /** Zweisprachiges Feld: l10n('hero.title') */
    public static function l10n(string $path, ?string $locale = null): string
    {
        $node = self::all();
        foreach (explode('.', $path) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return '';
            }
            $node = $node[$part];
        }
        return is_array($node) ? I18n::pick($node, $locale) : (is_string($node) ? $node : '');
    }

    /* ------------------------------ Suchen ------------------------------ */

    /** @return array<string,mixed>|null */
    public static function city(string $slug): ?array
    {
        foreach (self::list('cities') as $city) {
            if (($city['slug'] ?? '') === $slug) {
                return $city;
            }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public static function venue(string $slug): ?array
    {
        foreach (self::list('venues') as $venue) {
            if (($venue['slug'] ?? '') === $slug) {
                return $venue;
            }
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public static function story(string $slug): ?array
    {
        foreach (self::list('stories') as $story) {
            if (($story['slug'] ?? '') === $slug) {
                return $story;
            }
        }
        return null;
    }

    /** Beiträge, neueste zuerst. @return list<array<string,mixed>> */
    public static function posts(): array
    {
        $posts = self::list('posts');
        usort($posts, static fn (array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return $posts;
    }

    /** @return array<string,mixed>|null */
    public static function post(string $slug): ?array
    {
        foreach (self::posts() as $post) {
            if (($post['slug'] ?? '') === $slug) {
                return $post;
            }
        }
        return null;
    }

    /** Beiträge zu einer Stadt – für die interne Verlinkung. @return list<array<string,mixed>> */
    public static function postsForCity(string $citySlug): array
    {
        return array_values(array_filter(self::posts(), static fn (array $p): bool => ($p['citySlug'] ?? '') === $citySlug));
    }

    /** @return list<array<string,mixed>> */
    public static function postsForVenue(string $venueSlug): array
    {
        return array_values(array_filter(self::posts(), static fn (array $p): bool => ($p['venueSlug'] ?? '') === $venueSlug));
    }

    /** Locations einer Stadt. @return list<array<string,mixed>> */
    public static function venuesForCity(string $citySlug): array
    {
        return array_values(array_filter(self::list('venues'), static fn (array $v): bool => ($v['citySlug'] ?? '') === $citySlug));
    }

    /* ----------------------------- Schreiben ----------------------------- */

    /** @param array<string,mixed> $data */
    public static function save(array $data): void
    {
        Db::run(
            'INSERT INTO site_content (id, data) VALUES (1, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [Db::encode($data)]
        );
        self::$cache = $data;
    }

    /**
     * Lesen, ändern, zurückschreiben – jede Änderung im Adminbereich läuft
     * hierüber, damit niemand versehentlich das halbe Dokument überschreibt.
     *
     * @param callable(array<string,mixed>):array<string,mixed> $fn
     */
    public static function mutate(callable $fn): void
    {
        self::save($fn(self::all()));
    }
}
