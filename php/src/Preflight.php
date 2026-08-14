<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Was vor dem Livegang stimmen muss – geprüft dort, wo es zählt.
 *
 * Auf dem eigenen Rechner ist alles grün; die Fragen stellen sich erst auf
 * einem fremden Webspace: Ist GD übersetzt? Greift die .htaccess? Steht in
 * config.php noch das Passwort aus der Vorlage? Diese Seite beantwortet das
 * nach dem Hochladen in einem Blick, statt es über eine Woche einzeln
 * herauszufinden.
 *
 * Sie hängt hinter der Anmeldung. Ein öffentlicher Systemcheck ist eine
 * Liste dessen, was ein Angreifer sonst selbst ausprobieren müsste.
 */
final class Preflight
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /**
     * @return list<array{key:string,state:string,title:string,detail:string,hint:string}>
     */
    public static function run(string $locale): array
    {
        $de = $locale === 'de';
        $checks = [];

        foreach ([
            'php', 'extensions', 'gd', 'database', 'tables', 'content',
            'uploads', 'uploadGuard', 'config', 'https', 'headers', 'mail', 'integrations',
        ] as $name) {
            $checks[] = self::{$name}($de);
        }

        return $checks;
    }

    /** @param list<array{state:string}> $checks @return array{ok:int,warn:int,fail:int} */
    public static function tally(array $checks): array
    {
        $tally = ['ok' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($checks as $check) {
            $tally[$check['state']]++;
        }
        return $tally;
    }

    /* -------------------------------- Server -------------------------------- */

    private static function php(bool $de): array
    {
        $version = PHP_VERSION;
        $ok = version_compare($version, '8.1', '>=');

        return self::result('php', $ok ? self::OK : self::FAIL, 'PHP', $version, $ok
            ? ''
            : ($de
                ? 'Mindestens 8.1 nötig. Im KAS unter „Software“ die PHP-Version des Verzeichnisses umstellen.'
                : 'En az 8.1 gerekiyor. KAS’ta „Software“ altında dizinin PHP sürümünü değiştirin.'));
    }

    private static function extensions(bool $de): array
    {
        $needed = ['pdo_mysql', 'mbstring', 'json', 'curl', 'fileinfo'];
        $missing = array_values(array_filter($needed, static fn (string $e): bool => !extension_loaded($e)));

        return self::result(
            'extensions',
            $missing === [] ? self::OK : self::FAIL,
            $de ? 'Erweiterungen' : 'Eklentiler',
            $missing === [] ? implode(', ', $needed) : ($de ? 'fehlt: ' : 'eksik: ') . implode(', ', $missing),
            $missing === [] ? '' : ($de
                ? 'Ohne diese läuft die Seite nicht. Beim Hoster anfragen oder die PHP-Version wechseln.'
                : 'Bunlar olmadan site çalışmaz. Hosting’e sorun ya da PHP sürümünü değiştirin.')
        );
    }

    private static function gd(bool $de): array
    {
        if (!function_exists('imagejpeg')) {
            return self::result('gd', self::WARN, 'GD', $de ? 'nicht vorhanden' : 'yok', $de
                ? 'Bilder werden dann ohne Verkleinerung gespeichert – aus der Kamera sind das schnell 8 MB je Bild. Beim Hoster nach GD fragen.'
                : 'Görseller küçültülmeden saklanır – kameradan çıkan dosya kolayca 8 MB olur. Hosting’den GD isteyin.');
        }

        $webp = function_exists('imagewebp');

        return self::result('gd', $webp ? self::OK : self::WARN, 'GD', $webp ? 'JPEG, WebP' : 'JPEG', $webp
            ? ''
            : ($de
                ? 'Ohne WebP werden Schmuckelemente als PNG gespeichert – größer, aber funktionsfähig.'
                : 'WebP olmadan süslemeler PNG olarak saklanır – daha büyük ama çalışır.'));
    }

    /* ------------------------------ Datenbank ------------------------------- */

    private static function database(bool $de): array
    {
        try {
            $row = Db::one('SELECT VERSION() AS v');
            return self::result('database', self::OK, $de ? 'Datenbank' : 'Veritabanı', (string) ($row['v'] ?? '?'), '');
        } catch (\Throwable $e) {
            return self::result('database', self::FAIL, $de ? 'Datenbank' : 'Veritabanı', $de ? 'keine Verbindung' : 'bağlantı yok', $de
                ? 'Zugangsdaten in config.php prüfen. Im KAS stehen Name, Benutzer und Host unter „Datenbanken“.'
                : 'config.php içindeki bilgileri kontrol edin. KAS’ta ad, kullanıcı ve host „Datenbanken“ altında.');
        }
    }

    private static function tables(bool $de): array
    {
        $needed = ['site_content', 'integrations', 'customers', 'galleries', 'selections',
                   'invitations', 'invite_guests', 'invite_drafts', 'rsvps', 'leads', 'payments', 'throttle'];

        try {
            $found = array_column(Db::all('SHOW TABLES'), 0);
            if ($found === [] || $found[0] === null) {
                // Je nach Treiber heisst die Spalte anders.
                $found = array_map(static fn (array $r): string => (string) reset($r), Db::all('SHOW TABLES'));
            }
        } catch (\Throwable $e) {
            return self::result('tables', self::FAIL, $de ? 'Tabellen' : 'Tablolar', $de ? 'nicht lesbar' : 'okunamıyor', '');
        }

        $missing = array_values(array_diff($needed, $found));

        return self::result(
            'tables',
            $missing === [] ? self::OK : self::FAIL,
            $de ? 'Tabellen' : 'Tablolar',
            $missing === [] ? count($needed) . ' ' . ($de ? 'vollständig' : 'tam') : ($de ? 'fehlt: ' : 'eksik: ') . implode(', ', $missing),
            $missing === [] ? '' : ($de
                ? 'schema.sql im phpMyAdmin des KAS importieren.'
                : 'schema.sql’i KAS’taki phpMyAdmin’den içe aktarın.')
        );
    }

    private static function content(bool $de): array
    {
        $cities = count(Content::list('cities'));
        $street = trim(Content::field('contact.street'));
        $phone = trim(Content::field('contact.phone'));

        if ($cities === 0) {
            return self::result('content', self::FAIL, $de ? 'Inhalte' : 'İçerik', $de ? 'leer' : 'boş', $de
                ? 'bin/import.php ausführen oder die Inhalte aus dem Export einspielen.'
                : 'bin/import.php çalıştırın ya da içerikleri dışa aktarımdan yükleyin.');
        }

        $gaps = [];
        if ($street === '') {
            $gaps[] = $de ? 'Straße' : 'sokak';
        }
        if ($phone === '') {
            $gaps[] = 'Telefon';
        }

        return self::result(
            'content',
            $gaps === [] ? self::OK : self::FAIL,
            $de ? 'Inhalte' : 'İçerik',
            $cities . ' ' . ($de ? 'Städte' : 'şehir') . ($gaps === [] ? '' : ' · ' . ($de ? 'fehlt: ' : 'eksik: ') . implode(', ', $gaps)),
            $gaps === [] ? '' : ($de
                ? 'Anschrift und Telefon gehören ins Impressum – ohne sie ist die Seite in Deutschland abmahnfähig. Eintragen unter Inhalte → Texte & Kontakt.'
                : 'Adres ve telefon Impressum’a girmeli – onlarsız site Almanya’da ihtar riski taşır. İçerik → Metinler & iletişim altından girin.')
        );
    }

    /* ------------------------------- Dateien -------------------------------- */

    private static function uploads(bool $de): array
    {
        $dir = Media::dir();
        $writable = is_dir($dir) && is_writable($dir);

        return self::result('uploads', $writable ? self::OK : self::FAIL, $de ? 'Upload-Ordner' : 'Yükleme klasörü',
            $writable ? ($de ? 'beschreibbar' : 'yazılabilir') : ($de ? 'nicht beschreibbar' : 'yazılamıyor'),
            $writable ? '' : ($de
                ? 'Rechte auf 755 setzen (im KAS-Dateimanager oder per FTP).'
                : 'İzinleri 755 yapın (KAS dosya yöneticisi ya da FTP ile).'));
    }

    private static function uploadGuard(bool $de): array
    {
        $file = Media::dir() . '/.htaccess';
        $there = is_file($file);

        return self::result('uploadGuard', $there ? self::OK : self::WARN, $de ? 'Schutz der Uploads' : 'Yükleme koruması',
            $there ? '.htaccess ' . ($de ? 'vorhanden' : 'var') : ($de ? 'fehlt' : 'yok'),
            $there ? '' : ($de
                ? 'Die Datei public/uploads/.htaccess mit hochladen – sie verhindert, dass eine hochgeladene Datei je als Programm läuft.'
                : 'public/uploads/.htaccess dosyasını da yükleyin – yüklenen bir dosyanın program olarak çalışmasını engelliyor.'));
    }

    /* ----------------------------- Einstellungen ---------------------------- */

    private static function config(bool $de): array
    {
        $notes = [];
        $state = self::OK;

        if (Config::isDev()) {
            $notes[] = $de ? 'dev steht auf true' : 'dev true';
            $state = self::FAIL;
        }
        if (Config::get('noindex', false)) {
            $notes[] = $de ? 'noindex steht an' : 'noindex açık';
            $state = self::WARN;
        }
        if (Config::str('site_url') === '') {
            $notes[] = $de ? 'site_url leer' : 'site_url boş';
            $state = self::FAIL;
        }

        $warning = Admin::passwordWarning($de ? 'de' : 'tr');
        if ($warning !== '') {
            $notes[] = $de ? 'Adminpasswort' : 'yönetim parolası';
            $state = self::FAIL;
        }

        return self::result('config', $state, 'config.php',
            $notes === [] ? ($de ? 'in Ordnung' : 'uygun') : implode(' · ', $notes),
            $notes === [] ? '' : ($de
                ? 'dev auf false, site_url auf die echte Adresse, admin_key auf ein langes eigenes Passwort (am besten als password_hash). noindex gehört an, solange die Seite unter einer Testadresse liegt – und beim Umzug wieder aus.'
                : 'dev false, site_url gerçek adres, admin_key uzun kendi parolanız (tercihen password_hash). Site test adresindeyken noindex açık kalmalı – gerçek adrese taşıyınca kapatılmalı.'));
    }

    private static function https(bool $de): array
    {
        $secure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        return self::result('https', $secure ? self::OK : ($de && Config::isDev() ? self::WARN : self::FAIL),
            'HTTPS', $secure ? ($de ? 'aktiv' : 'aktif') : ($de ? 'nicht aktiv' : 'aktif değil'),
            $secure ? '' : ($de
                ? 'Im KAS ist Let’s Encrypt ein Klick. Ohne HTTPS greifen weder die Sitzungscookies richtig noch HSTS.'
                : 'KAS’ta Let’s Encrypt tek tık. HTTPS olmadan ne oturum çerezleri ne HSTS düzgün çalışır.'));
    }

    private static function headers(bool $de): array
    {
        // Die Kopfzeilen setzt Http::harden() aus dem Programm heraus; hier
        // geht es darum, ob die .htaccess ueberhaupt gelesen wird.
        $rewrite = ($_SERVER['REDIRECT_URL'] ?? '') !== '' || function_exists('apache_get_modules');

        return self::result('headers', self::OK, $de ? 'Schutzkopfzeilen' : 'Koruma başlıkları',
            $de ? 'werden aus PHP gesetzt' : 'PHP tarafından gönderiliyor',
            $rewrite ? '' : ($de
                ? 'Zusätzlich prüfen, ob die .htaccess greift: eine Unterseite direkt aufrufen. Kommt ein 404 vom Server statt der Seite, fehlt mod_rewrite.'
                : 'Ayrıca .htaccess çalışıyor mu bakın: bir alt sayfayı doğrudan açın. Sayfa yerine sunucudan 404 geliyorsa mod_rewrite yok.'));
    }

    private static function mail(bool $de): array
    {
        $to = Config::str('mail_to');
        $from = Config::str('mail_from');
        $can = function_exists('mail');

        $state = $can && $to !== '' && $from !== '' ? self::OK : self::WARN;

        return self::result('mail', $state, 'E-Mail',
            $can ? ($to !== '' ? $to : ($de ? 'kein Empfänger' : 'alıcı yok')) : ($de ? 'mail() gesperrt' : 'mail() kapalı'),
            $state === self::OK ? ($de
                ? 'Nach dem Livegang einmal das Kontaktformular abschicken und prüfen, ob die Nachricht ankommt – auch im Spam-Ordner.'
                : 'Yayından sonra iletişim formunu bir kez gönderip mesajın geldiğini kontrol edin – spam klasörüne de bakın.')
                : ($de
                ? 'mail_to und mail_from in config.php setzen. Absender sollte eine Adresse der eigenen Domain sein, sonst landet alles im Spam.'
                : 'config.php içinde mail_to ve mail_from ayarlayın. Gönderen kendi alan adınızdan olmalı, yoksa her şey spam’e düşer.'));
    }

    private static function integrations(bool $de): array
    {
        $paypal = Integrations::paypal();
        $tracking = Integrations::publicTracking();

        $on = [];
        if ($paypal['configured']) {
            $on[] = 'PayPal (' . $paypal['mode'] . ')';
        }
        if ($tracking['gaId'] !== '') {
            $on[] = 'GA4';
        }
        if ($tracking['metaPixelId'] !== '') {
            $on[] = 'Meta';
        }
        if (Places::configured()) {
            $on[] = 'Maps';
        }

        $live = $paypal['configured'] && $paypal['mode'] === 'live';

        return self::result('integrations', $paypal['configured'] && !$live ? self::WARN : self::OK,
            $de ? 'Integrationen' : 'Entegrasyonlar',
            $on === [] ? ($de ? 'keine eingerichtet' : 'hiçbiri kurulu değil') : implode(' · ', $on),
            $paypal['configured'] && !$live
                ? ($de ? 'PayPal steht auf sandbox – im Livebetrieb nimmt es kein Geld an.' : 'PayPal sandbox modunda – canlıda para tahsil etmez.')
                : ($on === [] ? ($de ? 'Alles optional. Die Seite läuft auch ohne.' : 'Hepsi isteğe bağlı. Site onlarsız da çalışır.') : ''));
    }

    /* -------------------------------- Helfer -------------------------------- */

    private static function result(string $key, string $state, string $title, string $detail, string $hint): array
    {
        return ['key' => $key, 'state' => $state, 'title' => $title, 'detail' => $detail, 'hint' => $hint];
    }
}
