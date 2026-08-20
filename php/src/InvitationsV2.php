<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Einladungen der zweiten Fassung.
 *
 * Getrennt von Invitations, weil die alte Tabelle das alte Schema traegt und
 * unangetastet bleibt. Das Schema hier steht seit Phase 1 und aendert sich
 * nicht: was noch fehlt, kommt in data hinein - das ist JSON.
 */
final class InvitationsV2
{
    /** Dieselbe Buchstabentabelle wie die alte Fassung - nicht zwei Wahrheiten. */
    public static function slug(string $value): string
    {
        return Invitations::slug($value);
    }

    /**
     * Frei in BEIDEN Tabellen.
     *
     * Das v2 in der Adresse faellt eines Tages weg. Dann muss
     * /einladung/{slug} genau eine Einladung treffen - und eine bereits
     * verschickte Adresse laesst sich nicht umbenennen.
     */
    public static function slugAvailable(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }
        if (Db::one('SELECT slug FROM invitations_v2 WHERE slug = ?', [$slug]) !== null) {
            return false;
        }
        return Db::one('SELECT slug FROM invitations WHERE slug = ?', [$slug]) === null;
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<string,mixed> $data
     */
    public static function create(string $slug, string $designId, array $snapshot, array $data): void
    {
        Db::run(
            'INSERT INTO invitations_v2 (slug, design_id, design_snapshot, data) VALUES (?, ?, ?, ?)',
            [$slug, $designId, Db::encode($snapshot), Db::encode($data)]
        );
    }

    /**
     * @return array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string}|null
     */
    public static function find(string $slug): ?array
    {
        $row = Db::one('SELECT * FROM invitations_v2 WHERE slug = ?', [self::slug($slug)]);
        if ($row === null) {
            return null;
        }

        return [
            'slug'            => (string) $row['slug'],
            'design_id'       => (string) $row['design_id'],
            'design_snapshot' => (array) json_decode((string) $row['design_snapshot'], true),
            'data'            => (array) json_decode((string) $row['data'], true),
            'created_at'      => (string) $row['created_at'],
        ];
    }

    /* --------------------------------- RSVP --------------------------------- */

    /**
     * Der Name, auf den verglichen wird.
     *
     * Gespeichert wird, was der Gast geschrieben hat; verglichen wird klein
     * und ohne Rand. "  Mehmet " und "mehmet" sind derselbe Gast - alles
     * andere zwaenge das Paar, aus zwei Zeilen zu raten, welche gilt.
     *
     * mb_strtolower und nicht strtolower: ein Name mit Umlaut oder mit ş
     * bliebe sonst in der Mitte grossgeschrieben, und zwei Schreibweisen
     * desselben Gastes waeren wieder zwei Gaeste.
     */
    public static function rsvpKey(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * Eine Antwort, und je Name nur eine.
     *
     * Kein INSERT wie im alten Motor: Invitations::addRsvp() haengt an, und
     * dort stehen zwei Antworten desselben Gastes untereinander. Hier ersetzt
     * die zweite die erste, weil die Frage des Paares - wer kommt - eine
     * einzige Antwort haben muss.
     *
     * Eigene Klasse statt einer Aenderung an Invitations: die alte Fassung
     * steht auf der Liste der Unberuehrbaren. Geteilt wird die Tabelle, nicht
     * der Code. Das ist sicher, weil ein v2-Slug seit Phase 3B in BEIDEN
     * Tabellen frei sein muss (slugAvailable()) - ohne diese Garantie koennte
     * eine v2-Einladung die Antworten einer v1-Einladung sehen. Wer
     * slugAvailable() eines Tages lockert, bricht diese Methode mit.
     *
     * Verglichen wird in PHP und nicht in SQL: der Name steht im
     * JSON-Dokument, und die Tabelle - die dem alten Motor gehoert - hat
     * dafuer keinen Schluessel. Das Schema bleibt unangetastet.
     *
     * Zwei gleichzeitige Antworten desselben Namens koennen beide einfuegen;
     * die Bremse (20 je zehn Minuten und Slug) macht das unwahrscheinlich,
     * und der Schaden waere eine doppelte Zeile, kein Datenverlust.
     *
     * @param array<string,mixed> $rsvp
     */
    public static function saveRsvp(string $slug, array $rsvp): void
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return;
        }

        $key = self::rsvpKey((string) ($rsvp['name'] ?? ''));

        foreach (Db::all('SELECT id, data FROM rsvps WHERE slug = ?', [$slug]) as $row) {
            $alt = json_decode((string) ($row['data'] ?? ''), true);
            if (!is_array($alt)) {
                // Eine Zeile, die kein Dokument ist, wird uebergangen statt
                // ueberschrieben: sie gehoert womoeglich nicht uns.
                continue;
            }
            if (self::rsvpKey((string) ($alt['name'] ?? '')) !== $key) {
                continue;
            }

            // at wird ausdruecklich gesetzt: die Spalte hat ein DEFAULT, aber
            // kein ON UPDATE - sonst stuende in der Liste weiter der
            // Zeitpunkt der ersten Antwort, und die Sortierung waere falsch.
            Db::run(
                'UPDATE rsvps SET data = ?, at = CURRENT_TIMESTAMP WHERE id = ?',
                [Db::encode($rsvp), (int) $row['id']]
            );
            return;
        }

        Db::run('INSERT INTO rsvps (slug, data) VALUES (?, ?)', [$slug, Db::encode($rsvp)]);
    }

    /**
     * Die Antworten zu genau dieser Einladung.
     *
     * Immer mit Slug, und ohne Vorgabewert: Invitations::rsvps() gibt ohne
     * Argument die Antworten ALLER Einladungen zurueck, und diese Methode
     * wird von einer Seite gerufen, die nichts weiter geprueft hat als einen
     * Schluessel. Ein vergessenes Argument waere dort ein Leck.
     *
     * @return list<array<string,mixed>>
     */
    public static function rsvps(string $slug): array
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return [];
        }

        return Db::jsonList('SELECT data FROM rsvps WHERE slug = ? ORDER BY at DESC', [$slug]);
    }
}
