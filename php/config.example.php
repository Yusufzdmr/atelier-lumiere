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

    /*
     * Solange die Seite unter einer Testadresse liegt (Subdomain, Vorschau):
     * true. Dann sperrt robots.txt alles und jede Seite trägt noindex.
     * Beim Umzug auf die richtige Domain auf false setzen – sonst findet
     * Google die Seite nie.
     */
    'noindex' => false,

    // Fehler im Klartext anzeigen – nur auf dem Testsystem einschalten.
    'dev' => false,

    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'd0xxxxx',
    'db_user' => 'd0xxxxx',
    'db_pass' => '',

    /*
     * Startpasswort für /admin — nur für den ersten Login.
     *
     * Sobald man im Adminbereich unter „Zugang" ein neues Passwort setzt,
     * landet der Hash in der Datenbank und dieser Eintrag wird ignoriert.
     * Bis dahin: hier ein Hash (bevorzugt) oder Klartext.
     *
     * Hash erzeugen mit:
     *   php -r "echo password_hash('DAS-PASSWORT', PASSWORD_DEFAULT), PHP_EOL;"
     */
    'admin_key' => 'bitte-aendern',

    // Absender der Benachrichtigungen (Kontaktformular, RSVP)
    'mail_from' => 'website@atelier-lumiere.de',
    'mail_to'   => 'hallo@atelier-lumiere.de',

    // Ordner für hochgeladene Bilder, relativ zu public/
    'upload_dir' => 'uploads',

    /*
     * Nur für den Adminbereich: Locations bei Google suchen und auf der Karte
     * prüfen. Kann auch im Adminbereich unter Integrationen stehen.
     * Der Schlüssel sollte auf die Server-IP beschränkt sein, nicht auf einen
     * Referrer – die Anfragen gehen vom Server aus.
     */
    'maps_key' => '',
];
