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

        ['href' => '/inhalte', 'group' => 'website', 'de' => 'Texte & Kontakt', 'tr' => 'Metinler & iletişim', 'pinned' => true],
        ['href' => '/bilder', 'group' => 'website', 'de' => 'Bilder', 'tr' => 'Görseller', 'pinned' => true],
        ['href' => '/texte', 'group' => 'website', 'de' => 'Seitentexte', 'tr' => 'Sayfa metinleri'],
        ['href' => '/leistungen', 'group' => 'website', 'de' => 'Leistungen & Ablauf', 'tr' => 'Hizmetler & süreç'],
        ['href' => '/pakete', 'group' => 'website', 'de' => 'Preise & Pakete', 'tr' => 'Fiyatlar & paketler'],
        ['href' => '/staedte', 'group' => 'website', 'de' => 'Städte', 'tr' => 'Şehirler'],
        ['href' => '/locations', 'group' => 'website', 'de' => 'Locations', 'tr' => 'Mekânlar'],
        ['href' => '/portfolio', 'group' => 'website', 'de' => 'Portfolio', 'tr' => 'Portfolyo', 'pinned' => true],
        ['href' => '/ratgeber', 'group' => 'website', 'de' => 'Ratgeber', 'tr' => 'Rehber', 'pinned' => true],
        ['href' => '/ueber-mich', 'group' => 'website', 'de' => 'Über mich & Stimmen', 'tr' => 'Hakkımda & yorumlar'],
        ['href' => '/rechtliches', 'group' => 'website', 'de' => 'Rechtstexte', 'tr' => 'Yasal metinler'],
        ['href' => '/seo', 'group' => 'website', 'de' => 'SEO & Meta', 'tr' => 'SEO & meta'],

        // Die Kundenakte ist die Galerie: wer eine anlegt, legt eine Galerie an.
        ['href' => '/kunden', 'group' => 'galerie', 'de' => 'Kunden & Galerien', 'tr' => 'Müşteriler & galeriler', 'pinned' => true],

        ['href' => '/einladungen', 'group' => 'einladung', 'de' => 'Einladungen', 'tr' => 'Davetiyeler', 'pinned' => true],
        // Themen sind die Designs der Einladungskarte, nicht der Website.
        ['href' => '/themen', 'group' => 'einladung', 'de' => 'Designs', 'tr' => 'Tasarımlar'],
        // Die zweite Fassung liegt daneben, nicht darin: verglichen wird noch.
        ['href' => '/designs', 'group' => 'einladung', 'de' => 'Designs (v2)', 'tr' => 'Tasarımlar (v2)'],

        ['href' => '/integrationen', 'group' => 'technik', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
        ['href' => '/systemcheck', 'group' => 'technik', 'de' => 'Vor dem Livegang', 'tr' => 'Yayın kontrolü'],
    ];

    /**
     * Bekleyen iş satırlarının şablonları.
     *
     * `%d` sayı ile, `%s` metin ile doldurulur. Tek satırlık mesajlar; ekstra
     * detay `href` üzerinden — kart tıklandığında tam bağlama gider.
     *
     * @var array<string,array{de:string,tr:string}>
     */
    private const PENDING_LABELS = [
        'lead_stale' => [
            'de' => '%d Anfrage(n) älter als 48 Stunden ohne Antwort',
            'tr' => '%d talep 48 saatten uzun cevapsız',
        ],
        'invitation_unpaid' => [
            'de' => '%d Einladung(en) seit über 7 Tagen unbezahlt',
            'tr' => '%d davetiye 7 günden uzun ödenmemiş',
        ],
        'selection_new' => [
            'de' => '%d neue Albumauswahl(en) noch nicht angesehen',
            'tr' => '%d yeni albüm seçimi henüz görülmedi',
        ],
        'wedding_empty' => [
            'de' => '%s in %d Tagen — Galerie noch leer',
            'tr' => '%s %d gün sonra — galeri hâlâ boş',
        ],
    ];

    /**
     * Bekleyen iş satırları — overview şablonu için.
     *
     * Ekstra DB round-trip yok: overview() zaten leads/selections/invitations/
     * customers dizilerini yükledi, aynı verilerden filtreliyoruz.
     *
     * @param list<array<string,mixed>> $leads
     * @param list<array<string,mixed>> $selections
     * @param list<array<string,mixed>> $invitations
     * @param list<array<string,mixed>> $customers
     * @param list<array<string,mixed>> $galleries
     * @return list<array{kind:string,message:string,href:string,severity:string}>
     */
    public static function pendingWork(
        string $locale,
        array $leads,
        array $selections,
        array $invitations,
        array $customers,
        array $galleries
    ): array {
        $out = [];
        $label = static function (string $kind) use ($locale): string {
            $row = self::PENDING_LABELS[$kind] ?? [];
            return (string) ($row[$locale] ?? $row['de'] ?? '');
        };

        // Cevapsız talepler (48 saatten eski)
        $limit48h = date('c', strtotime('-48 hours'));
        $stale = 0;
        foreach ($leads as $lead) {
            if ((string) ($lead['at'] ?? '') !== '' && (string) $lead['at'] < $limit48h) {
                $stale++;
            }
        }
        if ($stale > 0) {
            $out[] = [
                'kind'     => 'lead_stale',
                'message'  => sprintf($label('lead_stale'), $stale),
                'href'     => '#anfragen',
                'severity' => 'warn',
            ];
        }

        // Ödenmemiş davetiyeler (7 günden eski)
        // createdAt ISO 8601 (date('c')) formatında saklanıyor — aynı formatta karşılaştır.
        $limit7d = date('c', strtotime('-7 days'));
        $unpaid = 0;
        foreach ($invitations as $inv) {
            $created = (string) ($inv['createdAt'] ?? '');
            if (empty($inv['paid']) && $created !== '' && $created < $limit7d) {
                $unpaid++;
            }
        }
        if ($unpaid > 0) {
            $out[] = [
                'kind'     => 'invitation_unpaid',
                'message'  => sprintf($label('invitation_unpaid'), $unpaid),
                'href'     => I18n::path('/admin/einladungen', $locale),
                'severity' => 'warn',
            ];
        }

        // Yeni gelen albüm seçimleri
        $unseen = 0;
        foreach ($selections as $sel) {
            if (Galleries::isSelectionUnseen($sel)) {
                $unseen++;
            }
        }
        if ($unseen > 0) {
            $out[] = [
                'kind'     => 'selection_new',
                'message'  => sprintf($label('selection_new'), $unseen),
                'href'     => '#auswahlen',
                'severity' => 'info',
            ];
        }

        // Yaklaşan düğün + boş galeri (önümüzdeki 7 gün)
        $photosByCode = [];
        foreach ($galleries as $g) {
            $code = (string) ($g['code'] ?? '');
            $photosByCode[$code] = count((array) ($g['uploads'] ?? [])) + count((array) ($g['seeds'] ?? []));
        }
        $today = date('Y-m-d');
        $in7d  = date('Y-m-d', strtotime('+7 days'));
        foreach ($customers as $c) {
            $date = (string) ($c['date'] ?? '');
            $code = (string) ($c['code'] ?? '');
            if ($date < $today || $date > $in7d || $code === '') {
                continue;
            }
            if (($photosByCode[$code] ?? 0) > 0) {
                continue;
            }
            $days = max(0, (int) ((strtotime($date) - strtotime($today)) / 86400));
            $couple = (string) ($c['couple'] ?? $code);
            $out[] = [
                'kind'     => 'wedding_empty',
                'message'  => sprintf($label('wedding_empty'), $couple, $days),
                'href'     => I18n::path('/admin/kunden/' . $code, $locale),
                'severity' => 'warn',
            ];
        }

        // Sekiz satırdan fazlasını gösterme.
        return array_slice($out, 0, 8);
    }

    /**
     * Bir sekmenin ziyaret sayacını artırır. Panele her GET isteğinde çağrılır.
     *
     * @param string $tab örn. "" (overview), "/kunden", "/portfolio"
     */
    public static function recordVisit(string $tab): void
    {
        // Sadece TABS'ta olan sekmeleri say. Alt sayfalar (/kunden/{code}) üst
        // sekmeye ("/kunden") yuvarlanır — sayaç kısmen daha temiz olur.
        $canonical = null;
        foreach (self::TABS as $t) {
            $href = (string) $t['href'];
            if ($href === $tab || ($href !== '' && str_starts_with($tab, $href . '/'))) {
                $canonical = $href;
                break;
            }
        }
        if ($canonical === null) {
            return;
        }

        // ON DUPLICATE KEY: atomik, yarış koşulu yok.
        try {
            Db::run(
                'INSERT INTO admin_usage (tab, hits) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE hits = hits + 1, last_at = CURRENT_TIMESTAMP',
                [$canonical]
            );
        } catch (\Throwable $_) {
            // Tablo yoksa (henüz schema yüklenmedi) veya DB düştüyse:
            // panel çalışmaya devam etsin — sayaç kritik değil.
        }
    }

    /**
     * Kenar çubuğu için „sık kullanılanlar" — en çok en fazla 6 sekme.
     *
     * Kullanım verisi yeterliyse (son 30 günde ≥3 farklı sekme) sayaçtan;
     * yeterli değilse TABS içindeki pinned:true alanından.
     *
     * @return list<array{href:string,label:string,active:bool}>
     */
    public static function pinnedTabs(string $locale, string $current, int $count = 6): array
    {
        $hrefs = [];

        try {
            $rows = Db::all(
                'SELECT tab FROM admin_usage
                 WHERE last_at > (NOW() - INTERVAL 30 DAY)
                 ORDER BY hits DESC LIMIT ' . max(1, min(12, $count))
            );
            if (count($rows) >= 3) {
                foreach ($rows as $row) {
                    $hrefs[] = (string) $row['tab'];
                }
            }
        } catch (\Throwable $_) {
            // Tablo yoksa varsayılana düşer.
        }

        if ($hrefs === []) {
            foreach (self::TABS as $t) {
                if (!empty($t['pinned'])) {
                    $hrefs[] = (string) $t['href'];
                }
            }
        }

        $out = [];
        foreach ($hrefs as $href) {
            foreach (self::TABS as $t) {
                if ((string) $t['href'] === $href) {
                    $out[] = [
                        'href'   => I18n::path('/admin' . $href, $locale),
                        'label'  => (string) ($t[$locale] ?? $t['de']),
                        'active' => $current === $href,
                    ];
                    break;
                }
            }
            if (count($out) >= $count) {
                break;
            }
        }

        return $out;
    }

    /**
     * Kenar çubuğu içeriği: sık kullanılanlar (düz liste) ve „daha fazla"
     * altında gruplu geri kalan sekmeler.
     *
     * @return array{
     *   pinned: list<array{href:string,label:string,active:bool}>,
     *   more:   list<array{key:string,label:string,tabs:list<array{href:string,label:string,active:bool}>}>
     * }
     */
    public static function sidebar(string $locale, string $current): array
    {
        $pinned = self::pinnedTabs($locale, $current);
        $pinnedHrefs = array_map(static fn (array $t): string => $t['href'], $pinned);

        // "more" bölümü: pinned'de OLMAYAN ve grubu olan sekmeler.
        // Overview grup boş — o pinned'in üstünde ayrı render edilir.
        $more = [];
        foreach (self::TABS as $tab) {
            $group = (string) $tab['group'];
            if ($group === '') {
                continue;
            }
            $rendered = [
                'href'   => I18n::path('/admin' . $tab['href'], $locale),
                'label'  => (string) ($tab[$locale] ?? $tab['de']),
                'active' => $current === $tab['href'],
            ];
            if (in_array($rendered['href'], $pinnedHrefs, true)) {
                continue;
            }
            $more[$group]['label'] = self::GROUPS[$group][$locale] ?? self::GROUPS[$group]['de'];
            $more[$group]['key']   = $group;
            $more[$group]['tabs'][] = $rendered;
        }

        return [
            'pinned' => $pinned,
            'more'   => array_values($more),
        ];
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
            // GET isteklerinde ziyaret sayacını artır — POST'lar redirect
            // sonrası GET olarak gelir, çift sayım olmaz.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
                // "/tr/admin/kunden" → "/kunden", "/de/admin" → ""
                $tab = preg_replace('#^/[a-z]{2}/admin#', '', $path) ?? '';
                self::recordVisit($tab);
            }
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
