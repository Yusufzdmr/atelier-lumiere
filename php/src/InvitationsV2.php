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
     * Entwurf oder veroeffentlicht.
     *
     * Bis hierher gab es einen Zustand: es gibt sie, also steht sie im Netz.
     * Das reicht, solange jede Einladung sofort gilt - aber nicht, sobald
     * eine abgesagte Hochzeit vom Netz soll, ein falscher Link
     * zurueckgezogen werden muss, oder eines Tages die Bezahlung davorsteht.
     *
     * Der Zustand steht in einer EIGENEN Tabelle (invite_status) und nicht in
     * einer neuen Spalte: schema.sql besteht ausschliesslich aus CREATE TABLE
     * IF NOT EXISTS und wird von Hand eingespielt, also beliebig oft. Ein
     * ALTER TABLE waere die erste Zeile darin, die beim zweiten Mal
     * scheitert - und der Server hat keine Migrationen.
     */
    public const STATUSES = ['published', 'draft'];

    /**
     * Ein Wort, das man einem Gast gegenueber vertreten kann.
     *
     * Alles Unbekannte gilt als veroeffentlicht: ein Tippfehler oder ein Wort
     * aus einer aelteren Fassung darf keine Einladung abschalten, die im Netz
     * steht. Der Zweifel geht zugunsten des Gastes aus, der den Link schon
     * hat.
     */
    public static function cleanStatus(string $roh): string
    {
        return $roh === 'draft' ? 'draft' : 'published';
    }

    /** Die einzige Frage, die der Renderer stellt. */
    public static function isPublic(string $status): bool
    {
        return self::cleanStatus($status) !== 'draft';
    }

    /**
     * Der Zustand einer Einladung. KEINE Zeile heisst veroeffentlicht.
     *
     * Jede Einladung, die es heute gibt, hat ihren Link laengst verteilt; ein
     * Vorgabewert "Entwurf" haette sie alle auf einen Schlag abgeschaltet.
     */
    public static function status(string $slug): string
    {
        $zeile = Db::one('SELECT status FROM invite_status WHERE slug = ?', [$slug]);

        return self::cleanStatus((string) ($zeile['status'] ?? ''));
    }

    /**
     * Den Zustand setzen.
     *
     * published_at wird beim ERSTEN Veroeffentlichen gesetzt und danach nicht
     * mehr angefasst: es beantwortet "seit wann steht das im Netz", und diese
     * Antwort aendert sich nicht, wenn jemand die Einladung kurz abschaltet
     * und wieder anschaltet.
     */
    public static function setStatus(string $slug, string $status): void
    {
        $status = self::cleanStatus($status);

        Db::run(
            'INSERT INTO invite_status (slug, status, published_at)
                  VALUES (?, ?, IF(? = \'published\', CURRENT_TIMESTAMP, NULL))
             ON DUPLICATE KEY UPDATE
                  status = VALUES(status),
                  published_at = IF(VALUES(status) = \'published\' AND published_at IS NULL,
                                    CURRENT_TIMESTAMP, published_at)',
            [$slug, $status, $status]
        );
    }

    /**
     * Alle Einladungen der zweiten Fassung, neueste zuerst.
     *
     * Mit ihrem Zustand in derselben Abfrage: eine Liste mit dreissig Zeilen
     * waere sonst einunddreissig Abfragen. LEFT JOIN, weil die Zeile im
     * Zustand fehlen darf - sie fehlt bei allem, was vor heute entstanden ist.
     *
     * @return list<array<string,mixed>>
     */
    public static function all(): array
    {
        return Db::all(
            'SELECT i.slug, i.design_id, i.created_at,
                    COALESCE(s.status, \'published\') AS status, s.published_at
               FROM invitations_v2 i
          LEFT JOIN invite_status s ON s.slug = i.slug
           ORDER BY i.created_at DESC'
        );
    }

    /**
     * Wer haengt noch an einer aelteren Fassung seiner Vorlage?
     *
     * Die Entscheidung ist eine Zahl gegen eine Zahl und steht deshalb hier
     * und nicht in der Abfrage: so laesst sie sich ohne Datenbank pruefen.
     *
     * Getrennt nach Zustand, weil die beiden nicht gleich schwer wiegen. Ein
     * Entwurf darf nachgezogen werden; eine veroeffentlichte Einladung liegt
     * bereits bei den Gaesten, und ihr Bild aendert sich mit.
     *
     * Groesser heisst veraltet, ungleich nicht: eine von Hand zurueckgesetzte
     * Vorlage macht aus einer Einladung keine alte.
     *
     * @param list<array{slug:string,status:string,fassung:mixed}> $zeilen
     * @return array{draft:list<string>,published:list<string>}
     */
    public static function outdated(array $zeilen, int $liveVersion): array
    {
        $out = ['draft' => [], 'published' => []];

        foreach ($zeilen as $zeile) {
            // Ohne Fassung ist der Schnappschuss der aelteste, den es gibt.
            $fassung = max(1, (int) ($zeile['fassung'] ?? 1));
            if ($fassung >= $liveVersion) {
                continue;
            }

            $out[self::cleanStatus((string) ($zeile['status'] ?? ''))][] = (string) $zeile['slug'];
        }

        return $out;
    }

    /**
     * Frei in BEIDEN Tabellen.
     *
     * Das v2 in der Adresse faellt eines Tages weg. Dann muss
     * /einladung/{slug} genau eine Einladung treffen - und eine bereits
     * verschickte Adresse laesst sich nicht umbenennen.
     */
    /**
     * Die Einladungen einer Vorlage - Zustand und die Fassung ihres
     * Schnappschusses, sonst nichts.
     *
     * Drei Spalten und nicht das ganze Dokument: ein Schnappschuss ist eine
     * vollstaendige Vorlage, und dreissig davon nur zum Zaehlen zu laden
     * waere Verschwendung. JSON_EXTRACT holt die eine Zahl.
     *
     * @return list<array{slug:string,status:string,fassung:mixed}>
     */
    public static function byDesign(string $designId): array
    {
        return Db::all(
            'SELECT i.slug,
                    COALESCE(s.status, \'published\') AS status,
                    JSON_EXTRACT(i.design_snapshot, \'$.version\') AS fassung
               FROM invitations_v2 i
          LEFT JOIN invite_status s ON s.slug = i.slug
              WHERE i.design_id = ?
           ORDER BY i.created_at DESC',
            [$designId]
        );
    }

    /**
     * Eine Einladung auf den heutigen Stand ihrer Vorlage heben.
     *
     * Das ist die Ausnahme, die saveData() ausdruecklich nicht ist: dort
     * steht der Schnappschuss bewusst nicht im UPDATE, weil eine verschickte
     * Einladung einfriert. Hier wird genau dieses Einfrieren aufgehoben - aber
     * nur, weil ein Mensch im Panel den Knopf gedrueckt hat, und bei einer
     * veroeffentlichten Einladung erst nach einer zweiten Frage.
     *
     * Nur der Schnappschuss. data bleibt stehen, und damit bleibt die Wahl des
     * Paares: personalize() legt sie auf den neuen Sockel.
     *
     * @param array<string,mixed> $live
     */
    public static function refreshDesign(string $slug, array $live): void
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return;
        }

        Db::run('UPDATE invitations_v2 SET design_snapshot = ? WHERE slug = ?', [Db::encode($live), $slug]);
    }

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

    /* ----------------------- Nachtraegliches Bearbeiten ---------------------- */

    /**
     * Oeffnet dieser Schluessel diese Einladung?
     *
     * Seit dieser Phase oeffnet manageKey drei Tueren: die Antworten lesen, die
     * Einladung bearbeiten und - bald - die Gaesteliste. Die Regel steht
     * deshalb einmal hier und nicht in jedem Bildschirm noch einmal.
     *
     * hash_equals statt ===: der Schluessel ist 32 Hexadezimalzeichen und die
     * einzige Sicherung dieser Seiten. Ein Vergleich, der beim ersten
     * ungleichen Zeichen abbricht, verraet ueber die Laufzeit, wie weit ein
     * Rateversuch gekommen ist.
     *
     * Der leere Schluessel wird ausdruecklich VOR hash_equals abgefangen:
     * hash_equals('', '') ist WAHR, und eine Einladung ohne manageKey stuende
     * sonst jedem offen.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function keyOk(array $data, string $gegeben): bool
    {
        // is_string() faengt manageKey aus einem verformten Dokument ab, bevor
        // ein (string)-Cast auf ein Feld greift.
        $erwartet = is_string($data['manageKey'] ?? null) ? $data['manageKey'] : '';

        if ($erwartet === '' || $gegeben === '') {
            return false;
        }

        return hash_equals($erwartet, $gegeben);
    }

    /**
     * Hat jemand anders dazwischen gespeichert?
     *
     * Das Paar bearbeitet in zwei Tabs; der zweite Speichervorgang ueberschreibt
     * sonst den ersten, ohne dass jemand es merkt. Das Formular traegt den
     * Stand, den es beim Oeffnen vorfand, als verstecktes Feld mit - stimmt er
     * beim Absenden nicht mehr, wird nicht geschrieben. Der Designeditor im
     * Panel macht es seit Phase 2 genauso (fehler=veraltet).
     *
     * Verglichen wird auf GLEICHHEIT und nicht auf "aelter als": updatedAt ist
     * eine ISO-Zeichenkette mit Zonenversatz, und zwei Staende aus
     * verschiedenen Zonen waeren als Zeichenkette falsch geordnet. Gleichheit
     * beantwortet dieselbe Frage ohne die Falle.
     *
     * Eine Einladung von vor dieser Phase hat kein updatedAt. Gegen nichts
     * laesst sich nicht vergleichen - sonst waere ihre erste Bearbeitung
     * unmoeglich. Ab dem ersten Speichern steht der Stand dann drin.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function stale(array $data, string $gesehen): bool
    {
        $stand = is_string($data['updatedAt'] ?? null) ? $data['updatedAt'] : '';

        if ($stand === '') {
            return false;
        }

        return $gesehen !== $stand;
    }

    /**
     * Darf an dieser Einladung noch am Design geschraubt werden?
     *
     * Nur wenn die Wahl des Kunden mitgespeichert ist. Ohne sie ist der
     * Schnappschuss bereits personalisiert (so hat publish() bis zu dieser
     * Phase geschrieben), und eine neue Auswahl darauf waere verlustbehaftet:
     * eine ausgeblendete Ebene kaeme nicht zurueck, eine ueberschriebene Farbe
     * nicht wieder hervor. Der Preis steht in Spec §4 und wird dem Kunden auf
     * dem Bildschirm gesagt, nicht verschwiegen.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function canEditDesign(array $data): bool
    {
        return is_array($data['wahl'] ?? null);
    }

    /**
     * Die Daten einer Einladung neu schreiben.
     *
     * Ausdruecklich nur data. design_snapshot steht nicht in diesem UPDATE und
     * soll dort auch nie stehen: die Vorlage einer veroeffentlichten Einladung
     * friert ein (Phase 3B), und diese ganze Phase gibt es, um dieses
     * Versprechen zu halten, waehrend der Kunde trotzdem etwas aendern kann.
     *
     * @param array<string,mixed> $data
     */
    public static function saveData(string $slug, array $data): void
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return;
        }

        Db::run('UPDATE invitations_v2 SET data = ? WHERE slug = ?', [Db::encode($data), $slug]);
    }
}
