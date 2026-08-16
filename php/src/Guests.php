<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Persönlich adressierte Fassungen einer Einladung.
 *
 * „Familie Müller“, „Anna & Thomas“, „Familie Yılmaz“ – dieselbe Karte, andere
 * Anrede, eigener Link. Wichtig ist, was hier NICHT passiert: die Einladung
 * wird nicht kopiert. Bei 200 Gästen wären das 200 Karten, von denen keine
 * mehr zu ändern wäre, ohne alle anzufassen. Gespeichert wird nur der Name und
 * die Adresse, unter der er erscheint.
 */
final class Guests
{
    /** Höchstzahl je Einladung – eine Gästeliste, kein Newsletter. */
    public const MAX = 400;

    /**
     * Wörter, die schon eine andere Seite unter der Einladung sind. Ein Gast
     * namens „Zahlung“ würde sonst die Bezahlseite verdecken.
     */
    private const RESERVED = ['zahlung', 'verwalten', 'gast', 'og', 'vorschau'];

    /* --------------------------------- Lesen -------------------------------- */

    /** @return list<array<string,mixed>> */
    public static function all(string $slug): array
    {
        return Db::jsonList(
            'SELECT data FROM invite_guests WHERE slug = ? ORDER BY created_at, token',
            [Invitations::slug($slug)]
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(string $slug, string $token): ?array
    {
        return Db::json(
            'SELECT data FROM invite_guests WHERE slug = ? AND token = ?',
            [Invitations::slug($slug), self::token($token)]
        );
    }

    public static function count(string $slug): int
    {
        return (int) (Db::one('SELECT COUNT(*) AS n FROM invite_guests WHERE slug = ?', [Invitations::slug($slug)])['n'] ?? 0);
    }

    /* ------------------------------- Schreiben ------------------------------ */

    /** Wie jemand angeredet wird. Steht oben auf der Karte. */
    public const KINDS = ['family', 'male', 'female'];

    /**
     * „Liebe yilmaz“ stand auf einer echten Einladung – klein geschrieben und
     * grammatisch daneben. Klein geschrieben, weil abends schnell getippt;
     * daneben, weil eine Familie anders angeredet wird als eine Person und im
     * Deutschen ein Mann anders als eine Frau.
     *
     * Deshalb steht die Anrede nicht mehr in der Vorlage, sondern hier, und
     * das Paar sagt beim Eintragen, um wen es sich handelt.
     */
    public static function salutation(array $guest, string $locale): string
    {
        // Auch beim Ausgeben, nicht nur beim Anlegen: Namen, die vor dieser
        // Änderung klein eingetippt wurden, stehen sonst weiter klein auf der
        // Karte.
        $name = self::properCase(trim((string) ($guest['name'] ?? '')));
        if ($name === '') {
            return '';
        }

        $kind = (string) ($guest['kind'] ?? 'family');
        if (!in_array($kind, self::KINDS, true)) {
            $kind = 'family';
        }

        if ($locale === 'de') {
            return match ($kind) {
                'family' => 'Liebe Familie ' . $name,
                'male'   => 'Lieber ' . $name,
                default  => 'Liebe ' . $name,
            };
        }

        // Englisch kennt die Unterscheidung nicht; nur die Familie hängt an.
        return $kind === 'family' ? 'Dear ' . $name . ' family' : 'Dear ' . $name;
    }

    /**
     * Namen, die ganz klein geschrieben eingegeben wurden, bekommen ihre
     * Grossbuchstaben. Nur dann – wer „van der Berg“ tippt, hat sich etwas
     * dabei gedacht, und daraus soll kein „Van Der Berg“ werden.
     */
    private static function properCase(string $name): string
    {
        if ($name !== mb_strtolower($name)) {
            return $name;
        }

        return implode(' ', array_map(
            static fn (string $word): string => $word === ''
                ? $word
                : mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1),
            explode(' ', $name)
        ));
    }

    /**
     * Einen Namen anlegen. Gibt den Datensatz zurück – oder null, wenn der
     * Name leer war oder es ihn schon gibt.
     *
     * @return array<string,mixed>|null
     */
    public static function add(string $slug, string $name, string $kind = 'family'): ?array
    {
        $slug = Invitations::slug($slug);
        $name = self::properCase(Security::clean($name, 80));
        if (!in_array($kind, self::KINDS, true)) {
            $kind = 'family';
        }

        if ($name === '' || self::count($slug) >= self::MAX) {
            return null;
        }

        // Dieselbe Liste zweimal einfügen ist der Normalfall, nicht der
        // Ausnahmefall – dann soll nichts doppelt entstehen.
        foreach (self::all($slug) as $existing) {
            if (mb_strtolower((string) ($existing['name'] ?? '')) === mb_strtolower($name)) {
                return null;
            }
        }

        $token = self::freeToken($slug, $name);
        $guest = [
            'slug'      => $slug,
            'token'     => $token,
            'name'      => $name,
            'kind'      => $kind,
            'createdAt' => date('c'),
        ];

        Db::run(
            'INSERT INTO invite_guests (slug, token, data) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [$slug, $token, Db::encode($guest)]
        );

        return $guest;
    }

    /**
     * Mehrere Namen auf einmal – der Regelfall, weil eine Gästeliste selten
     * einzeln entsteht.
     *
     * @param list<string> $names
     * @return array{added:int,skipped:int,guests:list<array<string,mixed>>}
     */
    public static function addMany(string $slug, array $names, string $kind = 'family'): array
    {
        $added = 0;
        $skipped = 0;
        $guests = [];

        foreach ($names as $name) {
            $guest = self::add($slug, $name, $kind);
            if ($guest === null) {
                $skipped++;
                continue;
            }
            $added++;
            $guests[] = $guest;
        }

        return ['added' => $added, 'skipped' => $skipped, 'guests' => $guests];
    }

    public static function delete(string $slug, string $token): void
    {
        Db::run(
            'DELETE FROM invite_guests WHERE slug = ? AND token = ?',
            [Invitations::slug($slug), self::token($token)]
        );
    }

    /** Alle Gäste einer Einladung – wird beim Löschen der Einladung gebraucht. */
    public static function deleteAll(string $slug): void
    {
        Db::run('DELETE FROM invite_guests WHERE slug = ?', [Invitations::slug($slug)]);
    }

    /* -------------------------------- Einlesen ------------------------------ */

    /**
     * Namen aus einem Textfeld oder einer hochgeladenen Datei.
     *
     * Eine Gästeliste kommt selten sauber: mal eine Zeile je Name, mal eine
     * CSV aus Excel mit Semikolon, mal aus WhatsApp kopiert mit Nummerierung
     * davor. Alles drei landet hier als Liste von Namen.
     *
     * @return list<string>
     */
    public static function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $names = [];
        $first = true;

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Eine Tabelle bringt ihre Überschrift mit. „Name“ ist kein Gast.
            if ($first) {
                $first = false;
                if (self::isHeading($line)) {
                    continue;
                }
            }

            // Aus einer CSV zählt die erste Spalte; Trenner ist ; oder Tab,
            // NICHT das Komma – „Müller, Anna“ ist ein Name, keine zwei Spalten.
            if (str_contains($line, ';') || str_contains($line, "\t")) {
                $parts = preg_split('/[;\t]/', $line) ?: [];
                $line = trim((string) ($parts[0] ?? ''));
            }

            // Anführungszeichen der CSV, Nummerierung „1.“ / „1)“ und
            // Aufzählungszeichen „-“ / „•“ / „*“ weg
            $line = trim($line, "\"' ");
            $line = preg_replace('/^\s*\d+\s*[.)\-]\s*/u', '', $line) ?? $line;
            $line = preg_replace('/^\s*[-–—•*]\s+/u', '', $line) ?? $line;
            $line = trim($line);

            if ($line === '' || mb_strlen($line) > 80) {
                continue;
            }

            $names[] = $line;
        }

        // Doppelte Zeilen sind in kopierten Listen die Regel.
        return array_values(array_unique($names));
    }

    /** Namen aus einer hochgeladenen .txt/.csv-Datei. @return list<string> */
    public static function parseUpload(string $field): array
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        // Eine Gästeliste ist Text; alles darüber ist etwas anderes.
        if ($tmp === '' || !is_uploaded_file($tmp) || filesize($tmp) > 512 * 1024) {
            return [];
        }

        $raw = (string) file_get_contents($tmp);

        // Excel schreibt gern Windows-1252 oder setzt eine BOM davor.
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }

        return self::parse($raw);
    }

    /* --------------------------------- Helfer ------------------------------- */

    /**
     * Ist das die Überschriftszeile einer Tabelle?
     *
     * Bewusst nur die erste Zeile und nur genaue Treffer: eine Familie
     * „Namen“ gibt es nicht, eine Familie „Gast“ theoretisch schon.
     */
    private static function isHeading(string $line): bool
    {
        $headings = ['name', 'namen', 'gast', 'gäste', 'gaeste', 'ad', 'isim', 'isimler', 'misafir', 'aile', 'familie'];

        // Bei einer CSV zählt nur die erste Spalte der Kopfzeile.
        $cell = trim(preg_split('/[;\t]/', $line)[0] ?? $line, "\"' ");

        // Das türkische I zuerst: mb_strtolower macht aus „İ“ ein „i“ mit
        // eigenem Punkt darüber, das dann auf kein Wort mehr passt.
        $cell = str_replace(['İ', 'I', 'ı'], 'i', $cell);
        $cell = str_replace("\u{0307}", '', mb_strtolower($cell));

        return in_array($cell, $headings, true);
    }

    public static function token(string $value): string
    {
        return mb_substr(Invitations::slug($value), 0, 60);
    }

    /** Freie Adresse für einen Namen innerhalb dieser Einladung. */
    public static function freeToken(string $slug, string $name): string
    {
        $base = self::token($name);
        if ($base === '' || in_array($base, self::RESERVED, true)) {
            $base = 'gast-' . bin2hex(random_bytes(2));
        }

        $taken = [];
        foreach (self::all($slug) as $guest) {
            $taken[] = (string) ($guest['token'] ?? '');
        }

        $token = $base;
        $n = 2;
        while (in_array($token, $taken, true)) {
            $token = $base . '-' . $n++;
        }

        return $token;
    }

    /** Öffentliche Adresse eines persönlichen Links. */
    public static function url(string $slug, string $token, ?string $locale = null): string
    {
        return Config::url() . I18n::path('/einladung/' . Invitations::slug($slug) . '/' . self::token($token), $locale);
    }
}
