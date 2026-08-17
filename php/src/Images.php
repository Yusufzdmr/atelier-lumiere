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
        // Beispielstrecke unter einer Leistung: svc-<anker>-<nummer>. Die
        // Bildwelt richtet sich nach der Leistung, die Nummer streut sie.
        if (preg_match('/^svc-(.+)-(\d+)$/', $seed, $m) === 1) {
            $name = $m[1];
            $n = (int) $m[2];
            if (str_contains($name, 'video') || str_contains($name, 'film')) {
                return ['party', 'couple', 'ceremony', 'prep'][$n % 4];
            }
            if (str_contains($name, 'standesamt')) {
                return ['prep', 'ceremony', 'portrait', 'details'][$n % 4];
            }
            if (str_contains($name, 'after')) {
                return ['couple', 'portrait', 'venue', 'couple'][$n % 4];
            }
            return ['ceremony', 'couple', 'details', 'party'][$n % 4];
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
    /**
     * Die festen Bildplätze der Seite – Startbild, Porträt, Kopfbilder.
     *
     * Sie standen als Kürzel in den Vorlagen und waren damit das Einzige auf
     * der Website, das der Betrieb nicht selbst tauschen konnte: Texte ja,
     * Städte ja, das eigene Porträt nein.
     *
     * @return array<string,array{de:string,tr:string}>
     */
    public const SLOTS = [
        'lumiere-hero-main' => ['de' => 'Startseite, großes Bild', 'tr' => 'Ana sayfa, büyük görsel'],
        'lumiere-intro'     => ['de' => 'Startseite, Bild im Text', 'tr' => 'Ana sayfa, metindeki görsel'],
        'about-hero'        => ['de' => 'Über mich, Kopfbild', 'tr' => 'Hakkımda, üst görsel'],
        'about-portrait'    => ['de' => 'Über mich, Porträt', 'tr' => 'Hakkımda, portre'],
        'services-hero'     => ['de' => 'Leistungen, Kopfbild', 'tr' => 'Hizmetler, üst görsel'],
        'prices-hero'       => ['de' => 'Preise, Kopfbild', 'tr' => 'Fiyatlar, üst görsel'],
        'contact-hero'      => ['de' => 'Kontakt, Kopfbild', 'tr' => 'İletişim, üst görsel'],
        'designs-hero'      => ['de' => 'Designs, Kopfbild', 'tr' => 'Tasarımlar, üst görsel'],
    ];

    /**
     * Auf welcher Seite ein Platz vorkommt - fuer den Blick nach dem Speichern.
     *
     * Steht bewusst neben SLOTS und nicht darin: SLOTS wird an mehreren
     * Stellen durchlaufen, und ein zweites Feld haette dort jede Schleife
     * angefasst.
     */
    public const SLOT_PAGES = [
        'lumiere-hero-main' => '',
        'lumiere-intro'     => '',
        'about-hero'        => '/ueber-mich',
        'about-portrait'    => '/ueber-mich',
        'services-hero'     => '/leistungen',
        'prices-hero'       => '/preise',
        'contact-hero'      => '/kontakt',
        'designs-hero'      => '/designs',
    ];

    /** Gesetzte Bilder aus dem Adminbereich. Einmal je Anfrage gelesen. */
    private static ?array $own = null;

    public static function img(string $seed, int $w = 1200, int $h = 1600): string
    {
        if (preg_match('#^(https?:|data:|/)#', $seed) === 1) {
            return $seed;
        }

        // Was im Adminbereich hochgeladen wurde, gewinnt über den Platzhalter.
        if (self::$own === null) {
            $stored = Content::get('images');
            self::$own = is_array($stored) ? $stored : [];
        }
        $eigen = (string) (self::$own[$seed] ?? '');
        if ($eigen !== '') {
            return $eigen;
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
