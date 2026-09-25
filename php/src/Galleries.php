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
     * Anmeldung prüfen, inklusive Rolle – das Paar mit seinem Passwort,
     * Gäste mit einem zweiten, separaten (nur wenn eines gesetzt ist).
     * Gäste sehen dieselbe Galerie, aber nur zum Ansehen.
     *
     * @return array{gallery:array<string,mixed>,role:string}|null
     */
    public static function authRole(string $code, string $password): ?array
    {
        $gallery = self::find($code);
        if ($gallery === null) {
            return null;
        }

        $password = trim($password);

        if (hash_equals((string) ($gallery['password'] ?? ''), $password)) {
            return ['gallery' => $gallery, 'role' => 'couple'];
        }

        $guest = (string) ($gallery['guestPassword'] ?? '');
        if ($guest !== '' && hash_equals($guest, $password)) {
            return ['gallery' => $gallery, 'role' => 'guest'];
        }

        return null;
    }

    /**
     * Bildliste in der Reihenfolge, auf die sich die Auswahl bezieht.
     *
     * @param array<string,mixed> $gallery
     * @return list<array{thumb:string,full:string,original:?string,upload:bool}>
     */
    public static function photos(array $gallery): array
    {
        $photos = [];

        foreach ((array) ($gallery['uploads'] ?? []) as $src) {
            $photos[] = [
                'thumb'    => (string) $src,
                'full'     => (string) $src,
                // Fürs Herunterladen im Browser: die Galerie zeigt 1600 px,
                // wer sein eigenes Bild speichert, soll das Original bekommen.
                'original' => Media::originalUrl((string) $src),
                'upload'   => true,
            ];
        }
        foreach ((array) ($gallery['seeds'] ?? []) as $seed) {
            $photos[] = [
                'thumb'    => Images::img((string) $seed, 700, 900),
                'full'     => Images::img((string) $seed, 1400, 1800),
                'original' => null,
                'upload'   => false,
            ];
        }

        return $photos;
    }

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
     * Das Titelbild – eine einzelne Position, getrennt von der Herz-Auswahl.
     *
     * @param array<string,mixed> $gallery
     * @return array{nr:int,url:string,original:?string,name:string}|null
     */
    public static function coverPhoto(array $gallery, ?array $selection): ?array
    {
        if ($selection === null || ($selection['cover'] ?? null) === null) {
            return null;
        }

        $index = (int) $selection['cover'];
        $photos = self::photos($gallery);
        if (!isset($photos[$index])) {
            return null;
        }

        $url = (string) $photos[$index]['full'];
        $original = $photos[$index]['upload'] ? Media::originalPath($url) : null;

        return [
            'nr'       => $index + 1,
            'url'      => $url,
            'original' => $original,
            'name'     => basename($original ?? $url),
        ];
    }

    /* --------------------------- Tercihler ------------------------------- */

    /**
     * Bearbeitungsstil und Fragebogen des Paares.
     *
     * @return array<string,mixed>|null
     */
    public static function preferences(string $code): ?array
    {
        return Db::json('SELECT data FROM gallery_preferences WHERE code = ?', [self::normalize($code)]);
    }

    /**
     * Stil und Antworten speichern – neue Einsendung ersetzt die alte, genau
     * wie bei der Albumauswahl: das Paar darf seine Angaben jederzeit ändern.
     *
     * @param array<int|string,string> $answers Fragenindex => Antwort
     */
    public static function savePreferences(string $code, string $couple, ?int $style, array $answers): void
    {
        $preferences = [
            'code'    => self::normalize($code),
            'couple'  => $couple,
            'style'   => $style,
            'answers' => $answers,
            'at'      => date('c'),
        ];

        Db::run(
            'INSERT INTO gallery_preferences (code, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), at = CURRENT_TIMESTAMP',
            [$preferences['code'], Db::encode($preferences)]
        );

        self::notifyPreferences($preferences);
    }

    /** Panel hat die Tercihler gesehen — fällt aus der Liste der offenen Dinge. */
    public static function markPreferencesSeen(string $code): void
    {
        $preferences = self::preferences($code);
        if ($preferences === null) {
            return;
        }

        $preferences['seenAt'] = date('c');

        Db::run(
            'UPDATE gallery_preferences SET data = ? WHERE code = ?',
            [Db::encode($preferences), self::normalize($code)]
        );
    }

    /**
     * Ungesehen oder seit dem letzten Sehen erneut abgeschickt?
     *
     * @param array<string,mixed> $preferences
     */
    public static function isPreferencesUnseen(array $preferences): bool
    {
        return (string) ($preferences['at'] ?? '') > (string) ($preferences['seenAt'] ?? '');
    }

    /* ------------------------------ Auswahl ------------------------------ */

    /** @return array<string,mixed>|null */
    public static function selection(string $code): ?array
    {
        return Db::json('SELECT data FROM selections WHERE code = ?', [self::normalize($code)]);
    }

    /** @param list<int> $picks */
    public static function saveSelection(string $code, string $couple, array $picks, string $note = '', ?int $cover = null): void
    {
        $selection = [
            'code'   => self::normalize($code),
            'couple' => $couple,
            'picks'  => array_values(array_unique(array_map('intval', $picks))),
            'cover'  => $cover,
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

    /**
     * Bir galeri "hazır" sayılır ⇔ seçim var ve en az bir kare seçilmiş.
     *
     * 2. madde (albüm modeli, kargo adresi vb.) eklenene kadar tek ölçüt
     * bu — mevcut ZIP indirme akışının zaten kullandığı ölçütle aynı.
     *
     * @param array<string,mixed>|null $selection
     */
    public static function isReady(?array $selection): bool
    {
        return $selection !== null && (array) ($selection['picks'] ?? []) !== [];
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

        if (($selection['cover'] ?? null) !== null) {
            $body[] = 'Titelbild: Nr. ' . ((int) $selection['cover'] + 1);
        }

        if (($selection['note'] ?? '') !== '') {
            $body[] = '';
            $body[] = 'Nachricht: ' . $selection['note'];
        }

        Mail::toStudio('Albumauswahl: ' . (string) $selection['couple'], $body);
    }

    /** @param array<string,mixed> $preferences */
    private static function notifyPreferences(array $preferences): void
    {
        $style = $preferences['style'] ?? null;
        $styleItem = $style === null ? null : (Content::list('editingStyles')[(int) $style] ?? null);
        $styleName = $styleItem === null ? '' : I18n::pick($styleItem['name'] ?? null, 'de');

        $body = [
            'Galerie: ' . $preferences['code'],
            'Paar:    ' . $preferences['couple'],
            'Stil:    ' . ($styleName !== '' ? $styleName : '(nicht gewählt)'),
        ];

        $questions = Content::list('galleryQuestions');
        foreach ((array) ($preferences['answers'] ?? []) as $index => $answer) {
            $question = $questions[(int) $index] ?? null;
            $label = $question === null ? ('Frage ' . $index) : I18n::pick($question['question'] ?? null, 'de');
            $answerText = is_array($answer) ? implode(', ', array_map('strval', $answer)) : (string) $answer;
            if ($answerText === '') {
                continue;
            }
            $body[] = '';
            $body[] = $label . ':';
            $body[] = $answerText;
        }

        Mail::toStudio('Galerie-Vorlieben: ' . (string) $preferences['couple'], $body);
    }
}
