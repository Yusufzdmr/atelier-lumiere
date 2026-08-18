<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Kundengalerien und die Albumauswahl des Paares.
 *
 * Die Bilder liegen entweder als hochgeladene Datei (uploads) oder als
 * Platzhalter (seeds) vor – die Reihenfolge ist wichtig, weil die Auswahl
 * über den Index läuft und im Adminbereich dieselbe Nummerierung erscheint.
 */
final class Galleries
{
    /** @return array<string,mixed>|null */
    public static function find(string $code): ?array
    {
        $gallery = Db::json('SELECT data FROM galleries WHERE code = ?', [self::normalize($code)]);
        return $gallery === null ? null : self::complete($gallery);
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return array_map([self::class, 'complete'], Db::jsonList('SELECT data FROM galleries ORDER BY created_at DESC'));
    }

    /** Anmeldung prüfen. @return array<string,mixed>|null */
    public static function auth(string $code, string $password): ?array
    {
        $gallery = self::find($code);
        if ($gallery === null) {
            return null;
        }
        return hash_equals((string) ($gallery['password'] ?? ''), trim($password)) ? $gallery : null;
    }

    /**
     * Bildliste in der Reihenfolge, auf die sich die Auswahl bezieht.
     *
     * @param array<string,mixed> $gallery
     * @return list<array{thumb:string,full:string,upload:bool}>
     */
    public static function photos(array $gallery): array
    {
        $photos = [];

        foreach ((array) ($gallery['uploads'] ?? []) as $src) {
            $photos[] = ['thumb' => (string) $src, 'full' => (string) $src, 'upload' => true];
        }
        foreach ((array) ($gallery['seeds'] ?? []) as $seed) {
            $photos[] = [
                'thumb'  => Images::img((string) $seed, 700, 900),
                'full'   => Images::img((string) $seed, 1400, 1800),
                'upload' => false,
            ];
        }

        return $photos;
    }

    /* --------------------------- Auswahl teilen --------------------------- */

    /**
     * Die ausgewaehlten Bilder als Liste – mit dem Original, wo es eines gibt.
     *
     * Die Auswahl merkt sich Positionen im Raster, keine Dateinamen. Das ist
     * richtig so (Namen aendern sich, Positionen nicht), heisst aber, dass sie
     * hier gegen dieselbe Reihenfolge aufgeloest werden muessen, die auch das
     * Paar gesehen hat: erst die hochgeladenen, dann die Platzhalter.
     *
     * @param array<string,mixed> $gallery
     * @return list<array{nr:int,url:string,original:?string,name:string}>
     */
    public static function selectedPhotos(array $gallery, ?array $selection): array
    {
        if ($selection === null) {
            return [];
        }

        $photos = self::photos($gallery);
        $out = [];

        foreach ((array) ($selection['picks'] ?? []) as $index) {
            $index = (int) $index;
            if (!isset($photos[$index])) {
                continue;
            }

            $url = (string) $photos[$index]['full'];
            $original = $photos[$index]['upload'] ? Media::originalPath($url) : null;

            $out[] = [
                // Wie das Paar sie gezaehlt hat: ab eins, nicht ab null.
                'nr'       => $index + 1,
                'url'      => $url,
                'original' => $original,
                'name'     => basename($original ?? $url),
            ];
        }

        return $out;
    }

    /**
     * Ein Link fuer den Albumhersteller.
     *
     * Der Fotograf soll die Auswahl nicht herunterladen, um sie weiterzugeben.
     * Stattdessen bekommt der Drucker eine eigene Adresse, sieht dort genau die
     * ausgesuchten Bilder und laedt sie als ZIP – ohne Zugang zur Galerie und
     * ohne das Passwort des Paares.
     *
     * Befristet, weil ein Link, der ewig gilt, irgendwann irgendwo steht.
     */
    public static function shareCreate(string $code, int $days = 30): array
    {
        $share = [
            'token'   => bin2hex(random_bytes(16)),
            'expires' => date('Y-m-d', strtotime('+' . max(1, min(365, $days)) . ' days')),
            'created' => date('c'),
        ];

        self::update($code, ['share' => $share]);

        return $share;
    }

    public static function shareRevoke(string $code): void
    {
        self::update($code, ['share' => null]);
    }

    /**
     * Galerie zu einem Freigabe-Token – nur solange er gilt.
     *
     * @return array<string,mixed>|null
     */
    public static function shareFind(string $token): ?array
    {
        $token = preg_replace('/[^a-f0-9]/', '', mb_strtolower(trim($token))) ?? '';
        if (strlen($token) !== 32) {
            return null;
        }

        foreach (Db::jsonList('SELECT data FROM galleries') as $gallery) {
            $share = $gallery['share'] ?? null;
            if (!is_array($share) || !hash_equals((string) ($share['token'] ?? ''), $token)) {
                continue;
            }

            $expires = (string) ($share['expires'] ?? '');
            if ($expires !== '' && $expires < date('Y-m-d')) {
                return null;
            }

            return $gallery;
        }

        return null;
    }

    /* ------------------------------ Auswahl ------------------------------ */

    /** @return array<string,mixed>|null */
    public static function selection(string $code): ?array
    {
        return Db::json('SELECT data FROM selections WHERE code = ?', [self::normalize($code)]);
    }

    /** @param list<int> $picks */
    public static function saveSelection(string $code, string $couple, array $picks, string $note = ''): void
    {
        $selection = [
            'code'   => self::normalize($code),
            'couple' => $couple,
            'picks'  => array_values(array_unique(array_map('intval', $picks))),
            'note'   => $note,
            'at'     => date('c'),
        ];

        Db::run(
            'INSERT INTO selections (code, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), at = CURRENT_TIMESTAMP',
            [$selection['code'], Db::encode($selection)]
        );

        self::notify($selection);
    }

    /**
     * Seçim panelde görüldü — bekleyen iş listesinden düşer.
     *
     * `seenAt` ilk görme zamanını, `seenPickCount` o andaki kare sayısını
     * tutar. Paar sonradan bir kare daha eklerse (picks sayısı artar) tekrar
     * "yeni" sayılır.
     */
    public static function markSelectionSeen(string $code): void
    {
        $selection = self::selection($code);
        if ($selection === null) {
            return;
        }

        $selection['seenAt'] = date('c');
        $selection['seenPickCount'] = count((array) ($selection['picks'] ?? []));

        Db::run(
            'UPDATE selections SET data = ? WHERE code = ?',
            [Db::encode($selection), self::normalize($code)]
        );
    }

    /**
     * Görülmemiş veya yeni kare eklenmiş seçim mi?
     *
     * @param array<string,mixed> $selection
     */
    public static function isSelectionUnseen(array $selection): bool
    {
        if (empty($selection['seenAt'])) {
            return true;
        }
        $seen = (int) ($selection['seenPickCount'] ?? 0);
        $now  = count((array) ($selection['picks'] ?? []));
        return $now > $seen;
    }

    /* ------------------------------ Schreiben ----------------------------- */

    /** @param array<string,mixed> $gallery */
    public static function save(array $gallery): void
    {
        $gallery['code'] = self::normalize((string) ($gallery['code'] ?? ''));
        Db::run(
            'INSERT INTO galleries (code, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [$gallery['code'], Db::encode(self::complete($gallery))]
        );
    }

    /** @param array<string,mixed> $patch */
    public static function update(string $code, array $patch): ?array
    {
        $gallery = self::find($code);
        if ($gallery === null) {
            return null;
        }
        $next = array_merge($gallery, $patch);
        self::save($next);
        return $next;
    }

    public static function delete(string $code): void
    {
        $code = self::normalize($code);
        $gallery = self::find($code);

        foreach ((array) ($gallery['uploads'] ?? []) as $url) {
            Media::delete((string) $url);
        }

        Db::run('DELETE FROM galleries WHERE code = ?', [$code]);
        Db::run('DELETE FROM selections WHERE code = ?', [$code]);
    }

    /** @param list<string> $urls */
    public static function addPhotos(string $code, array $urls): void
    {
        $gallery = self::find($code);
        if ($gallery === null) {
            return;
        }
        $uploads = array_merge((array) ($gallery['uploads'] ?? []), $urls);
        self::update($code, ['uploads' => array_slice($uploads, 0, 200)]);
    }

    public static function removePhoto(string $code, int $index): void
    {
        $gallery = self::find($code);
        if ($gallery === null) {
            return;
        }

        $uploads = array_values((array) ($gallery['uploads'] ?? []));
        if (!isset($uploads[$index])) {
            return;
        }

        $removed = (string) $uploads[$index];
        unset($uploads[$index]);
        self::update($code, ['uploads' => array_values($uploads)]);
        Media::delete($removed);
    }

    /* ------------------------------- Helfer ------------------------------- */

    public static function normalize(string $code): string
    {
        return strtolower(trim($code));
    }

    /**
     * @param array<string,mixed> $gallery
     * @return array<string,mixed>
     */
    private static function complete(array $gallery): array
    {
        $gallery['uploads'] = array_values(array_filter((array) ($gallery['uploads'] ?? []), 'is_string'));
        $gallery['seeds'] = array_values(array_filter((array) ($gallery['seeds'] ?? []), 'is_string'));
        return $gallery;
    }

    /** @param array<string,mixed> $selection */
    private static function notify(array $selection): void
    {
        $picks = (array) ($selection['picks'] ?? []);
        $numbers = implode(', ', array_map(static fn (int $i): int => $i + 1, array_map('intval', $picks)));

        $body = [
            'Galerie: ' . $selection['code'],
            'Paar:    ' . $selection['couple'],
            'Auswahl: ' . count($picks) . ' Bilder',
            '',
            'Bildnummern: ' . $numbers,
        ];

        if (($selection['note'] ?? '') !== '') {
            $body[] = '';
            $body[] = 'Nachricht: ' . $selection['note'];
        }

        Mail::toStudio('Albumauswahl: ' . (string) $selection['couple'], $body);
    }
}
