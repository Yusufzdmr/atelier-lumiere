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
    /**
     * Die Abschnitte der Seitenleiste.
     *
     * Sechzehn Reiter in einer Reihe sind keine Navigation mehr, sondern eine
     * Wand. Gruppiert wird danach, in welcher Rolle jemand hier sitzt: Texte
     * pflegen, einen Auftrag bearbeiten, das Aussehen ändern, etwas einrichten.
     *
     * @var array<string,array{de:string,tr:string}>
     */
    public const GROUPS = [
        'inhalt'  => ['de' => 'Inhalte', 'tr' => 'İçerik'],
        'auftrag' => ['de' => 'Aufträge', 'tr' => 'İşler'],
        'design'  => ['de' => 'Gestaltung', 'tr' => 'Görünüm'],
        'technik' => ['de' => 'Einstellungen', 'tr' => 'Ayarlar'],
    ];

    /** Die Reiter des Adminbereichs, in dieser Reihenfolge. */
    public const TABS = [
        ['href' => '', 'group' => '', 'de' => 'Übersicht', 'tr' => 'Genel bakış'],

        ['href' => '/inhalte', 'group' => 'inhalt', 'de' => 'Texte & Kontakt', 'tr' => 'Metinler & iletişim'],
        ['href' => '/texte', 'group' => 'inhalt', 'de' => 'Seitentexte', 'tr' => 'Sayfa metinleri'],
        ['href' => '/leistungen', 'group' => 'inhalt', 'de' => 'Leistungen & Ablauf', 'tr' => 'Hizmetler & süreç'],
        ['href' => '/pakete', 'group' => 'inhalt', 'de' => 'Preise & Pakete', 'tr' => 'Fiyatlar & paketler'],
        ['href' => '/staedte', 'group' => 'inhalt', 'de' => 'Städte', 'tr' => 'Şehirler'],
        ['href' => '/locations', 'group' => 'inhalt', 'de' => 'Locations', 'tr' => 'Mekânlar'],
        ['href' => '/portfolio', 'group' => 'inhalt', 'de' => 'Portfolio', 'tr' => 'Portfolyo'],
        ['href' => '/ratgeber', 'group' => 'inhalt', 'de' => 'Ratgeber', 'tr' => 'Rehber'],
        ['href' => '/ueber-mich', 'group' => 'inhalt', 'de' => 'Über mich & Stimmen', 'tr' => 'Hakkımda & yorumlar'],
        ['href' => '/rechtliches', 'group' => 'inhalt', 'de' => 'Rechtstexte', 'tr' => 'Yasal metinler'],

        ['href' => '/kunden', 'group' => 'auftrag', 'de' => 'Kunden', 'tr' => 'Müşteriler'],
        ['href' => '/einladungen', 'group' => 'auftrag', 'de' => 'Einladungen', 'tr' => 'Davetiyeler'],

        ['href' => '/themen', 'group' => 'design', 'de' => 'Themen', 'tr' => 'Temalar'],

        ['href' => '/seo', 'group' => 'technik', 'de' => 'SEO & Meta', 'tr' => 'SEO & meta'],
        ['href' => '/integrationen', 'group' => 'technik', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
    ];

    /**
     * Die Reiter nach Abschnitten, fertig für die Seitenleiste.
     *
     * @return list<array{key:string,label:string,tabs:list<array{href:string,label:string,active:bool}>}>
     */
    public static function sidebar(string $locale, string $current): array
    {
        $sections = [];

        foreach (self::TABS as $tab) {
            $group = (string) $tab['group'];
            $sections[$group]['label'] = $group === ''
                ? ''
                : (self::GROUPS[$group][$locale] ?? self::GROUPS[$group]['de']);
            $sections[$group]['key'] = $group;
            $sections[$group]['tabs'][] = [
                'href'   => I18n::path('/admin' . $tab['href'], $locale),
                'label'  => (string) ($tab[$locale] ?? $tab['de']),
                'active' => $current === $tab['href'],
            ];
        }

        return array_values($sections);
    }

    /** Wie der gerade offene Reiter heißt – für die schmale Ansicht. */
    public static function currentLabel(string $locale, string $current): string
    {
        foreach (self::TABS as $tab) {
            if ($tab['href'] === $current) {
                return (string) ($tab[$locale] ?? $tab['de']);
            }
        }
        return (string) (self::TABS[0][$locale] ?? self::TABS[0]['de']);
    }

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
