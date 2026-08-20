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

    /* -------------------------------- Entwuerfe ------------------------------- */

    /**
     * Obergrenze je Feld im Entwurf.
     *
     * Grosszuegig und absichtlich nur eine Zahl: ein Entwurf ist ein
     * Zwischenstand, kein Archiv. Die richtigen Feldgrenzen setzt publish()
     * noch einmal, wenn aus dem Entwurf eine Einladung wird. Hier geht es
     * allein darum, dass eine einzelne Anfrage die Tabelle nicht sprengt.
     */
    public const DRAFT_LEN = 600;

    /**
     * Was aus dem Formular in den Entwurf darf.
     *
     * Drei Namen fallen weg: csrf gehoert der Sitzung und haette in einer
     * Tabelle nichts zu suchen; was ist der gedrueckte Knopf und token die
     * Kennung des Entwurfs selbst - beide beschreiben die Anfrage, nicht die
     * Einladung.
     *
     * Alles andere wird gereinigt uebernommen, ohne Weissliste: welche Felder
     * es gibt, entscheidet das Design (DesignWizard::choices), und eine zweite
     * Liste hier liefe der ersten irgendwann hinterher.
     *
     * @param array<string,mixed> $post
     * @return array<string,string>
     */
    public static function draftValues(array $post): array
    {
        $out = [];

        foreach ($post as $name => $wert) {
            if (in_array((string) $name, ['csrf', 'was', 'token'], true)) {
                continue;
            }
            // Ein Feld statt eines Wertes (name[]=x) kommt aus keinem Formular,
            // wohl aber aus einer von Hand gestellten Anfrage.
            if (!is_string($wert)) {
                continue;
            }
            $out[(string) $name] = Security::clean($wert, self::DRAFT_LEN);
        }

        return $out;
    }

    /**
     * Den Zwischenstand festhalten.
     *
     * Geteilt wird die Tabelle invite_drafts, nicht der Code: Invitations
     * steht auf der Liste der Unberuehrbaren - dieselbe Entscheidung wie bei
     * den Antworten (siehe saveRsvp).
     *
     * Das Dokument traegt dieselbe Form wie das der ersten Fassung (token,
     * label, data, updatedAt), weil der Adminbereich beide Tabellenzeilen in
     * einer Liste zeigt. fassung sagt ihm, welcher Assistent den Entwurf
     * wieder oeffnen kann - ohne das schickte der Link einen v2-Entwurf in den
     * alten Assistenten.
     *
     * @param array<string,string> $values
     */
    public static function saveDraft(string $token, array $values): void
    {
        if ($token === '') {
            return;
        }

        // Der Name des Paares als Aufschrift: im Adminbereich steht sonst eine
        // Reihe gleich aussehender Zeilen.
        $label = trim(($values['bride'] ?? '') . ' & ' . ($values['groom'] ?? ''), ' &');

        $doc = [
            'token'     => $token,
            'label'     => $label !== '' ? $label : 'Entwurf',
            'fassung'   => 2,
            'data'      => $values,
            'updatedAt' => date('c'),
        ];

        Db::run(
            'INSERT INTO invite_drafts (token, data) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = CURRENT_TIMESTAMP',
            [$token, Db::encode($doc)]
        );

        // Liegengelassene Entwuerfe raeumen, sonst waechst die Tabelle endlos.
        // Dieselbe Frist wie in der ersten Fassung; sie steht auch hier, damit
        // das Aufraeumen nicht daran haengt, dass jemand den alten Assistenten
        // benutzt.
        Db::run('DELETE FROM invite_drafts WHERE updated_at < (NOW() - INTERVAL 120 DAY)');
    }

    /**
     * Der gespeicherte Zwischenstand, oder null.
     *
     * null und ein leeres Feld sind verschiedene Antworten: der Assistent muss
     * "diesen Entwurf gibt es nicht" von "ein Entwurf ohne Eingaben"
     * unterscheiden koennen, sonst zeigt ein falscher Link ein leeres Formular
     * statt einer ehrlichen Meldung.
     *
     * @return array<string,string>|null
     */
    public static function draft(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $doc = Db::json('SELECT data FROM invite_drafts WHERE token = ?', [$token]);
        if ($doc === null) {
            return null;
        }

        $werte = $doc['data'] ?? null;

        return is_array($werte) ? $werte : [];
    }

    /**
     * Nach dem Veroeffentlichen ist der Entwurf Ballast.
     *
     * Er zeigt einen Stand, den die fertige Einladung laengst ueberholt hat -
     * und sein Link fuehrte den Kunden zurueck in ein Formular, das er schon
     * abgeschickt hat.
     */
    public static function deleteDraft(string $token): void
    {
        if ($token === '') {
            return;
        }

        Db::run('DELETE FROM invite_drafts WHERE token = ?', [$token]);
    }
}
