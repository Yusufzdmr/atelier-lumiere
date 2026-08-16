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
     * Wand. Gruppiert wird nach den drei Dingen, die der Betrieb betreibt –
     * nicht nach der Art der Bearbeitung. „Inhalte, Aufträge, Gestaltung“ war
     * die Ordnung dessen, der es gebaut hat: die Designs der Einladung standen
     * unter Gestaltung, die Kundengalerien unter Aufträge, und wer die
     * Einladung testen wollte, suchte an drei Stellen. Website, Galerie und
     * Einladung sind die Teile, die man einzeln in Betrieb nimmt.
     *
     * @var array<string,array{de:string,tr:string}>
     */
    public const GROUPS = [
        'website'   => ['de' => 'Website', 'tr' => 'Site'],
        'galerie'   => ['de' => 'Galerie', 'tr' => 'Galeri'],
        'einladung' => ['de' => 'Einladung', 'tr' => 'Davetiye'],
        'technik'   => ['de' => 'System', 'tr' => 'Sistem'],
    ];

    /** Die Reiter des Adminbereichs, in dieser Reihenfolge. */
    public const TABS = [
        ['href' => '', 'group' => '', 'de' => 'Übersicht', 'tr' => 'Genel bakış'],

        ['href' => '/inhalte', 'group' => 'website', 'de' => 'Texte & Kontakt', 'tr' => 'Metinler & iletişim'],
        ['href' => '/bilder', 'group' => 'website', 'de' => 'Bilder', 'tr' => 'Görseller'],
        ['href' => '/texte', 'group' => 'website', 'de' => 'Seitentexte', 'tr' => 'Sayfa metinleri'],
        ['href' => '/leistungen', 'group' => 'website', 'de' => 'Leistungen & Ablauf', 'tr' => 'Hizmetler & süreç'],
        ['href' => '/pakete', 'group' => 'website', 'de' => 'Preise & Pakete', 'tr' => 'Fiyatlar & paketler'],
        ['href' => '/staedte', 'group' => 'website', 'de' => 'Städte', 'tr' => 'Şehirler'],
        ['href' => '/locations', 'group' => 'website', 'de' => 'Locations', 'tr' => 'Mekânlar'],
        ['href' => '/portfolio', 'group' => 'website', 'de' => 'Portfolio', 'tr' => 'Portfolyo'],
        ['href' => '/ratgeber', 'group' => 'website', 'de' => 'Ratgeber', 'tr' => 'Rehber'],
        ['href' => '/ueber-mich', 'group' => 'website', 'de' => 'Über mich & Stimmen', 'tr' => 'Hakkımda & yorumlar'],
        ['href' => '/rechtliches', 'group' => 'website', 'de' => 'Rechtstexte', 'tr' => 'Yasal metinler'],
        ['href' => '/seo', 'group' => 'website', 'de' => 'SEO & Meta', 'tr' => 'SEO & meta'],

        // Die Kundenakte ist die Galerie: wer eine anlegt, legt eine Galerie an.
        ['href' => '/kunden', 'group' => 'galerie', 'de' => 'Kunden & Galerien', 'tr' => 'Müşteriler & galeriler'],

        ['href' => '/einladungen', 'group' => 'einladung', 'de' => 'Einladungen', 'tr' => 'Davetiyeler'],
        // Themen sind die Designs der Einladungskarte, nicht der Website.
        ['href' => '/themen', 'group' => 'einladung', 'de' => 'Designs', 'tr' => 'Tasarımlar'],

        ['href' => '/integrationen', 'group' => 'technik', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
        ['href' => '/systemcheck', 'group' => 'technik', 'de' => 'Vor dem Livegang', 'tr' => 'Yayın kontrolü'],
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

    /** Nach so langer Untaetigkeit ist Schluss – ein offener Laptop reicht sonst. */
    private const IDLE = 4 * 3600;

    /** Und spaetestens dann in jedem Fall, auch bei Betrieb. */
    private const LIFETIME = 12 * 3600;

    public static function isLoggedIn(): bool
    {
        Security::session();
        if (empty($_SESSION['admin'])) {
            return false;
        }

        $now = time();
        $seen = (int) ($_SESSION['adminSeen'] ?? 0);
        $since = (int) ($_SESSION['adminSince'] ?? 0);

        if (($now - $seen) > self::IDLE || ($now - $since) > self::LIFETIME) {
            self::logout();
            return false;
        }

        $_SESSION['adminSeen'] = $now;
        return true;
    }

    /** @return bool true bei erfolgreicher Anmeldung */
    public static function login(string $password): bool
    {
        if (Security::throttle('admin-login', 8, 900)) {
            return false;
        }

        $expected = Config::str('admin_key', 'demo');
        $password = trim($password);

        if ($expected === '' || $password === '') {
            return false;
        }

        // Steht in der config.php ein Hash (password_hash), wird er geprueft;
        // sonst der Klartext, zeitkonstant. So laesst sich ein bestehender
        // Zugang umstellen, ohne dass jemand ausgesperrt wird.
        $ok = str_starts_with($expected, '$2y$') || str_starts_with($expected, '$argon2')
            ? password_verify($password, $expected)
            : hash_equals($expected, $password);

        if (!$ok) {
            return false;
        }

        Security::session();
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['adminSince'] = time();
        $_SESSION['adminSeen'] = time();

        return true;
    }

    public static function logout(): void
    {
        Security::session();
        unset($_SESSION['admin'], $_SESSION['adminSince'], $_SESSION['adminSeen']);
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
                // Neuladen kein Formular noch einmal abschickt. Geprueft wird
                // sie trotzdem: „//fremde-seite“ waere sonst ein gueltiges Ziel.
                $back = (string) ($_SERVER['REQUEST_URI'] ?? '');
                if ($back === '' || !str_starts_with($back, '/') || str_starts_with($back, '//')) {
                    $back = I18n::path('/admin', $locale);
                }
                header('Location: ' . $back, true, 303);
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

    /**
     * Steht der Zugang noch offen wie am ersten Tag?
     *
     * Das Passwort aus der Vorlage oder eines, das man in einer Mittagspause
     * durchprobiert, ist der wahrscheinlichste Weg in diesen Bereich. Deshalb
     * ein Hinweis im Adminbereich selbst – dort sieht ihn genau die Person,
     * die ihn abstellen kann.
     *
     * @return string leer, wenn nichts zu melden ist
     */
    public static function passwordWarning(string $locale): string
    {
        $key = Config::str('admin_key', '');
        $de = $locale === 'de';

        // Ein Hash ist in Ordnung, egal wie er aussieht.
        if (str_starts_with($key, '$2y$') || str_starts_with($key, '$argon2')) {
            return '';
        }

        $weak = ['demo', 'test', 'admin', 'passwort', 'password', 'bitte-aendern', '1234', 'geheim'];

        if ($key === '' || in_array(mb_strtolower($key), $weak, true)) {
            return $de
                ? 'Der Adminbereich hat noch das Standardpasswort. Vor dem Livegang in der config.php ändern – am besten als Hash.'
                : 'Yönetim paneli hâlâ varsayılan parolayı kullanıyor. Yayına almadan önce config.php içinde değiştirin – tercihen hash olarak.';
        }

        if (mb_strlen($key) < 12) {
            return $de
                ? 'Das Adminpasswort ist kurz. Zwölf Zeichen oder mehr machen einen echten Unterschied.'
                : 'Yönetim parolası kısa. On iki karakter ve üzeri gerçek bir fark yaratır.';
        }

        return '';
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
