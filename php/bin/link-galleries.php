<?php
declare(strict_types=1);

/**
 * Legt Kundenakten für Galerien an, die keine haben.
 *
 *   php bin/link-galleries.php --dry   nur zeigen, was passieren würde
 *   php bin/link-galleries.php         anlegen
 *
 * Warum das gebraucht wird: die Kundenliste im Adminbereich wird über die
 * Kundenakten aufgebaut – eine Galerie ohne Akte taucht dort nicht auf, und
 * mit ihr auch nicht die Bildauswahl des Paares. Beim Import aus der
 * Next.js-Fassung kamen Galerien mit, Kundenakten gab es dort noch nicht.
 *
 * `Customers::create()` hilft hier nicht: es lehnt einen Anmeldenamen ab, den
 * es schon als Galerie gibt – zu Recht, sonst würde die fremde Galerie samt
 * Bildern überschrieben. Deshalb wird die Akte hier direkt geschrieben und
 * die Galerie bleibt unangetastet.
 *
 * Zugangsdaten werden aus der Galerie übernommen, nicht neu erfunden: das
 * Paar hat sein Passwort schon.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Customers;
use Atelier\Galleries;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

$angelegt = 0;
$hatSchon = 0;

foreach (Galleries::all() as $gallery) {
    $code = (string) ($gallery['code'] ?? '');
    if ($code === '') {
        continue;
    }

    if (Customers::find($code) !== null) {
        $hatSchon++;
        continue;
    }

    $couple = (string) ($gallery['couple'] ?? '');
    $date = (string) ($gallery['date'] ?? '');

    printf(
        "%s  %s  (%s)%s\n",
        $dry ? 'wuerde anlegen:' : 'angelegt:',
        str_pad($code, 16),
        $couple !== '' ? $couple : 'ohne Namen',
        $dry ? '' : ''
    );

    if ($dry) {
        $angelegt++;
        continue;
    }

    Customers::save([
        'code'      => $code,
        // Aus der Galerie, nicht neu erzeugt – sonst kommt das Paar morgen
        // nicht mehr in seine Bilder.
        'password'  => (string) ($gallery['password'] ?? ''),
        'couple'    => $couple,
        'date'      => $date,
        'venue'     => (string) ($gallery['venue'] ?? ''),
        'status'    => 'active',
        'createdAt' => date('c'),
        'notes'     => 'Akte nachtraeglich zur bestehenden Galerie angelegt.',
        'coupon'    => [
            'code'    => Customers::randomCoupon(),
            'active'  => true,
            'once'    => true,
            'expires' => '',
            'usedFor' => [],
        ],
    ]);

    $angelegt++;
}

printf(
    "\n%s: %d · bereits vorhanden: %d\n",
    $dry ? 'Waeren angelegt worden' : 'Angelegt',
    $angelegt,
    $hatSchon
);
