<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Bildquelle – 1:1 die Logik aus lib/images.ts.
 *
 * Wichtig ist die Streuung: derselbe Platzhalter („seed“) muss in beiden
 * Fassungen dasselbe Bild ergeben, sonst sieht die portierte Seite anders aus
 * als die bisherige. Deshalb auch derselbe FNV-1a-Hash.
 *
 * Für die Live-Version wird nur diese Klasse getauscht: dann liefert img()
 * die Pfade der eigenen Aufnahmen.
 */
final class Images
{
    private const BUCKETS = ['couple', 'ceremony', 'party', 'details', 'venue', 'prep', 'portrait'];

    /** @var array<string,list<array<string,mixed>>>|null */
    private static ?array $photos = null;

    /** @return array<string,list<array<string,mixed>>> */
    private static function photos(): array
    {
        if (self::$photos === null) {
            $raw = json_decode((string) file_get_contents(__DIR__ . '/../data/photos.json'), true);
            self::$photos = is_array($raw) ? $raw : [];
        }
        return self::$photos;
    }

    /** FNV-1a, wie in der JavaScript-Fassung (32 Bit, ohne Vorzeichen). */
    private static function hash(string $s): int
    {
        $h = 2166136261;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h ^= ord($s[$i]);
            // Multiplikation modulo 2^32, wie Math.imul
            $h = (int) (($h * 16777619) & 0xFFFFFFFF);
        }
        // Math.abs auf einem 32-Bit-Wert mit Vorzeichen
        if ($h >= 0x80000000) {
            $h -= 0x100000000;
        }
        return abs($h);
    }

    private static function bucketFor(string $seed): string
    {
        if (str_starts_with($seed, 'venue-') || $seed === 'venues-index') {
            return 'venue';
        }
        if ($seed === 'about-portrait') {
            return 'portrait';
        }
        if (str_starts_with($seed, 'lum-service-')) {
            if (str_contains($seed, 'video')) {
                return 'party';
            }
            if (str_contains($seed, 'standesamt')) {
                return 'prep';
            }
            if (str_contains($seed, 'after')) {
                return 'couple';
            }
            return 'ceremony';
        }
        if (in_array($seed, ['lumiere-tool-gallery', 'lumiere-tool-invite', 'invite-hero', 'prices-hero'], true)) {
            return 'details';
        }
        if ($seed === 'gallery-hero') {
            return 'prep';
        }
        if ($seed === 'services-hero' || $seed === 'contact-hero') {
            return 'ceremony';
        }

        if (preg_match('/-(\d+)$/', $seed, $m) === 1 && (str_starts_with($seed, 'gal-') || str_starts_with($seed, 'story-'))) {
            $order = ['couple', 'prep', 'ceremony', 'details', 'party', 'venue'];
            return $order[((int) $m[1]) % count($order)];
        }

        return 'couple';
    }

    /** @return array<string,mixed>|null */
    private static function pick(string $seed): ?array
    {
        $photos = self::photos();
        $bucket = self::bucketFor($seed);
        $list = $photos[$bucket] ?? [];
        if ($list === []) {
            $list = $photos['couple'] ?? [];
        }
        if ($list === []) {
            return null;
        }
        return $list[self::hash($seed) % count($list)] ?? null;
    }

    /** Bildadresse für einen Platzhalter oder eine bereits fertige URL. */
    public static function img(string $seed, int $w = 1200, int $h = 1600): string
    {
        if (preg_match('#^(https?:|data:|/)#', $seed) === 1) {
            return $seed;
        }

        $entry = self::pick($seed);
        if ($entry === null) {
            return 'https://picsum.photos/seed/' . rawurlencode($seed) . "/$w/$h";
        }

        $query = http_build_query([
            'w'    => $w,
            'h'    => $h,
            'fit'  => 'crop',
            'crop' => 'faces,entropy',
            'q'    => '72',
            'fm'   => 'jpg',
            'auto' => 'format',
        ]);

        return $entry['url'] . '?' . $query;
    }

    /** Alternativtext aus den Bilddaten, sonst der übergebene. */
    public static function alt(string $seed, string $fallback): string
    {
        $entry = self::pick($seed);
        $alt = is_string($entry['alt'] ?? null) ? trim($entry['alt']) : '';
        return $alt !== '' ? $alt : $fallback;
    }

    /** Fotografennachweis der Demobilder. @return list<string> */
    public static function credits(): array
    {
        $names = [];
        foreach (self::BUCKETS as $bucket) {
            foreach (self::photos()[$bucket] ?? [] as $entry) {
                $by = is_string($entry['by'] ?? null) ? $entry['by'] : '';
                if ($by !== '') {
                    $names[$by] = true;
                }
            }
        }
        $list = array_keys($names);
        sort($list);
        return $list;
    }

    public const BLUR = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyNiI+PHJlY3Qgd2lkdGg9IjIwIiBoZWlnaHQ9IjI2IiBmaWxsPSIjZWRlNGQ4Ii8+PC9zdmc+';
}
