<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Anmeldung zum Adminbereich.
 *
 * Ein Passwort aus config.php, keine Benutzerverwaltung – der Betrieb hat
 * genau eine Person. Wichtig sind dafür drei Dinge: das Passwort wird
 * zeitkonstant verglichen, Versuche werden gebremst, und nach der Anmeldung
 * bekommt die Sitzung eine neue Kennung.
 */
final class Admin
{
    /** Die Reiter des Adminbereichs, in dieser Reihenfolge. */
    public const TABS = [
        ['href' => '', 'de' => 'Übersicht', 'tr' => 'Genel bakış'],
        ['href' => '/inhalte', 'de' => 'Texte & Kontakt', 'tr' => 'Metinler & iletişim'],
        ['href' => '/pakete', 'de' => 'Preise & Pakete', 'tr' => 'Fiyatlar & paketler'],
        ['href' => '/leistungen', 'de' => 'Leistungen & Ablauf', 'tr' => 'Hizmetler & süreç'],
        ['href' => '/staedte', 'de' => 'Städte', 'tr' => 'Şehirler'],
        ['href' => '/locations', 'de' => 'Locations', 'tr' => 'Mekânlar'],
        ['href' => '/portfolio', 'de' => 'Portfolio', 'tr' => 'Portfolyo'],
        ['href' => '/ratgeber', 'de' => 'Ratgeber', 'tr' => 'Rehber'],
        ['href' => '/kunden', 'de' => 'Kunden', 'tr' => 'Müşteriler'],
        ['href' => '/einladungen', 'de' => 'Einladungen', 'tr' => 'Davetiyeler'],
        ['href' => '/themen', 'de' => 'Themen', 'tr' => 'Temalar'],
        ['href' => '/ueber-mich', 'de' => 'Über mich & Stimmen', 'tr' => 'Hakkımda & yorumlar'],
        ['href' => '/rechtliches', 'de' => 'Rechtstexte', 'tr' => 'Yasal metinler'],
        ['href' => '/seo', 'de' => 'SEO & Meta', 'tr' => 'SEO & meta'],
        ['href' => '/integrationen', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
    ];

    public static function isLoggedIn(): bool
    {
        Security::session();
        return !empty($_SESSION['admin']);
    }

    /** @return bool true bei erfolgreicher Anmeldung */
    public static function login(string $password): bool
    {
        if (Security::throttle('admin-login', 8, 900)) {
            return false;
        }

        $expected = Config::str('admin_key', 'demo');
        if ($expected === '' || !hash_equals($expected, trim($password))) {
            return false;
        }

        Security::session();
        session_regenerate_id(true);
        $_SESSION['admin'] = true;

        return true;
    }

    public static function logout(): void
    {
        Security::session();
        unset($_SESSION['admin']);
        session_regenerate_id(true);
    }

    /**
     * Wird von jeder Adminseite als Erstes aufgerufen. Ohne Anmeldung wird
     * das Anmeldeformular gezeigt und die Ausführung beendet.
     */
    public static function requireLogin(string $locale): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        $error = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['password'])) {
            if (Security::checkCsrf($_POST['csrf'] ?? null) && self::login((string) $_POST['password'])) {
                // Nach der Anmeldung dieselbe Adresse erneut aufrufen, damit ein
                // Neuladen kein Formular noch einmal abschickt.
                header('Location: ' . ($_SERVER['REQUEST_URI'] ?? I18n::path('/admin', $locale)), true, 303);
                exit;
            }
            $error = true;
        }

        View::page('admin/login', [
            'layout' => 'admin/layout',
            'locale' => $locale,
            'path'   => I18n::path('/admin', $locale),
            'meta'   => ['title' => 'Admin', 'noindex' => true],
            'nav'    => false,
            'error'  => $error,
            'csrf'   => Security::csrf(),
        ]);
        exit;
    }

    /** Schutz vor fremden Formularen; bricht mit 403 ab. */
    public static function checkCsrfOrFail(): void
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            http_response_code(403);
            exit('Sitzung abgelaufen. Bitte die Seite neu laden.');
        }
    }

    /** Nach dem Speichern zurück zur Seite – verhindert doppeltes Absenden. */
    public static function back(string $locale, string $tab, string $flash = 'ok'): never
    {
        header('Location: ' . I18n::path('/admin' . $tab, $locale) . '?gespeichert=' . $flash, true, 303);
        exit;
    }
}
