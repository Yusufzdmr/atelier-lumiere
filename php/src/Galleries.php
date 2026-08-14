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
        $to = Config::str('mail_to');
        $from = Config::str('mail_from');
        if ($to === '' || $from === '') {
            return;
        }

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

        @mail(
            $to,
            '=?UTF-8?B?' . base64_encode('Albumauswahl: ' . Security::singleLine((string) $selection['couple'])) . '?=',
            implode("\n", $body),
            implode("\r\n", [
                'From: Atelier Lumière <' . Security::singleLine($from) . '>',
                'Content-Type: text/plain; charset=UTF-8',
            ])
        );
    }
}
