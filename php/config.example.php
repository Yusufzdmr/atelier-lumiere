<?php
/**
 * Kopie dieser Datei als config.php ablegen und ausfüllen.
 * config.php gehört NICHT ins Repository (siehe .gitignore).
 *
 * Bei ALL-INKL stehen Datenbankname, Benutzer und Passwort im KAS unter
 * „Datenbanken“. Der Host ist dort in der Regel localhost.
 */

return [
    // Adresse ohne Schrägstrich am Ende. Leer = wird aus der Anfrage ermittelt.
    'site_url' => 'https://atelier-lumiere.de',

    // Fehler im Klartext anzeigen – nur auf dem Testsystem einschalten.
    'dev' => false,

    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'd0xxxxx',
    'db_user' => 'd0xxxxx',
    'db_pass' => '',

    // Passwort für /admin
    'admin_key' => 'bitte-aendern',

    // Absender der Benachrichtigungen (Kontaktformular, RSVP)
    'mail_from' => 'website@atelier-lumiere.de',
    'mail_to'   => 'hallo@atelier-lumiere.de',

    // Ordner für hochgeladene Bilder, relativ zu public/
    'upload_dir' => 'uploads',
];
