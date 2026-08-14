<?php
declare(strict_types=1);

/**
 * Spielt data/export.json in die MariaDB ein.
 *
 *   php bin/import.php            vorhandene Datensätze bleiben unangetastet
 *   php bin/import.php --replace  vorhandene werden überschrieben
 *
 * Der Export stammt aus der Next.js-Fassung (scripts/export-to-php.mjs).
 * Die Struktur der JSON-Dokumente ist in beiden Fassungen dieselbe, deshalb
 * ist das hier ein Kopiervorgang und keine Umformung.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Db;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$replace = in_array('--replace', $argv, true);
$file = __DIR__ . '/../data/export.json';

if (!is_file($file)) {
    exit("data/export.json fehlt. Zuerst 'node scripts/export-to-php.mjs' laufen lassen.\n");
}

$dump = json_decode((string) file_get_contents($file), true);
if (!is_array($dump)) {
    exit("export.json ist nicht lesbar.\n");
}

$verb = $replace ? 'REPLACE' : 'INSERT IGNORE';
$counts = [];

/** @param array<string,mixed>|null $data */
$put = function (string $table, string $key, string $id, mixed $data) use ($verb, &$counts): void {
    if (!is_array($data)) {
        return;
    }
    Db::run(
        sprintf('%s INTO %s (%s, data) VALUES (?, ?)', $verb, $table, $key),
        [$id, Db::encode($data)]
    );
    $counts[$table] = ($counts[$table] ?? 0) + 1;
};

// Inhalte: genau ein Datensatz
if (is_array($dump['content'] ?? null)) {
    Db::run(
        sprintf('%s INTO site_content (id, data) VALUES (1, ?)', $verb),
        [Db::encode($dump['content'])]
    );

    // Datensatz 2 ist der unberührte Stand. Er wird nie bearbeitet und dient
    // im Adminbereich als Vergleich: „so stand es ursprünglich da“ – und als
    // Ziel des Zurücksetzen-Knopfes. Deshalb immer überschreiben, auch ohne
    // --replace: er soll zum gerade eingespielten Export passen.
    Atelier\Content::saveOriginal($dump['content']);

    $counts['site_content'] = 1;
}

foreach (($dump['galleries'] ?? []) as $row) {
    $put('galleries', 'code', (string) $row['code'], $row['data'] ?? null);
}
foreach (($dump['customers'] ?? []) as $row) {
    $put('customers', 'code', (string) $row['code'], $row['data'] ?? null);
}
foreach (($dump['invitations'] ?? []) as $row) {
    $put('invitations', 'slug', (string) $row['slug'], $row['data'] ?? null);
}
foreach (($dump['selections'] ?? []) as $row) {
    $put('selections', 'code', (string) $row['code'], $row['data'] ?? null);
}

// Zusagen und Anfragen haben eine laufende Nummer, keinen eigenen Schlüssel:
// bei einem zweiten Lauf würden sie sich verdoppeln, deshalb nur in eine
// leere Tabelle.
$rsvpCount = (int) (Db::one('SELECT COUNT(*) AS n FROM rsvps')['n'] ?? 0);
if ($rsvpCount === 0) {
    foreach (($dump['rsvps'] ?? []) as $row) {
        if (!is_array($row['data'] ?? null)) {
            continue;
        }
        Db::run('INSERT INTO rsvps (slug, data, at) VALUES (?, ?, ?)', [
            (string) $row['slug'],
            Db::encode($row['data']),
            substr((string) ($row['at'] ?? date('c')), 0, 19),
        ]);
        $counts['rsvps'] = ($counts['rsvps'] ?? 0) + 1;
    }
}

$leadCount = (int) (Db::one('SELECT COUNT(*) AS n FROM leads')['n'] ?? 0);
if ($leadCount === 0) {
    foreach (($dump['leads'] ?? []) as $row) {
        if (!is_array($row['data'] ?? null)) {
            continue;
        }
        Db::run('INSERT INTO leads (data, at) VALUES (?, ?)', [
            Db::encode($row['data']),
            substr((string) ($row['at'] ?? date('c')), 0, 19),
        ]);
        $counts['leads'] = ($counts['leads'] ?? 0) + 1;
    }
}

echo "Import fertig", $replace ? ' (überschrieben)' : '', ":\n";
foreach ($counts as $table => $n) {
    printf("  %-14s %d\n", $table, $n);
}
if ($counts === []) {
    echo "  nichts zu tun\n";
}
