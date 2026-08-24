<?php
declare(strict_types=1);

/**
 * schema.sql einspielen - auch auf eine Datenbank, die es schon gibt.
 *
 *   sudo -u www-data php bin/schema-anwenden.php
 *
 * Die Datei besteht ausschliesslich aus CREATE TABLE IF NOT EXISTS und ist
 * damit beliebig oft ausfuehrbar. So kommen neue Tabellen auf den Server:
 * Archiv auspacken, dies laufen lassen, fertig. Migrationen gibt es nicht,
 * und ein ALTER TABLE waere die erste Zeile in schema.sql, die beim zweiten
 * Mal scheitert.
 *
 * Ueber die Zugangsdaten der Anwendung, nicht ueber die Kommandozeile: so
 * steht kein Passwort in der Befehlszeile, im Verlauf der Shell oder in der
 * Prozessliste.
 */

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Db;

$datei = __DIR__ . '/../schema.sql';
if (!is_file($datei)) {
    exit("schema.sql nicht gefunden.\n");
}

$pdo = Db::pdo();
$pdo->exec((string) file_get_contents($datei));

$tabellen = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
sort($tabellen);

echo count($tabellen) . " Tabellen:\n  " . implode("\n  ", $tabellen) . "\n";
