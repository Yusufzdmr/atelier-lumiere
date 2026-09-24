# Albümcü girişi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Albüm baskı firmasına kalıcı, tek ortak hesaplı bir giriş
(`/albumcu`) vermek; hazır olan galerileri listeleyip ZIP indirmesini
sağlamak; bunu yaparken eski süreli/şifresiz link akışını (`/auswahl/{token}`)
tamamen kaldırmak.

**Architecture:** `src/Admin.php`'nin oturum deseni birebir kopyalanıyor
(`src/Albumist.php`) — ayrı `$_SESSION` anahtarı, admin oturumuyla
karışmıyor. Yeni `AlbumistController` dört rotayı yönetiyor (liste, detay,
zip, çıkış). "Hazır" durumu yeni bir DB alanı gerektirmiyor —
`Galleries::isReady()` mevcut `selections` tablosundaki `picks` alanına
bakıyor. Eski `SelectionController` + admin'deki "Link erzeugen" akışı
tamamen siliniyor.

**Tech Stack:** PHP 8 (framework yok, `src/Router.php`), MySQL (JSON
doküman deseni, bkz. `src/Db.php`), Tailwind CSS v4 (`php/assets/app.css`
→ `php/public/assets/style.css`, derleme adımı gerekiyor).

**Spec:** `docs/superpowers/specs/2026-09-25-albumcu-girisi-design.md`

## Global Constraints

- Tek ortak albümcü hesabı — `config.php`'de `albumist_user`/`albumist_key`,
  birden fazla hesap yok (spec §1, Yusuf'un kararı 2026-09-25).
- "Hazır" = seçim var ve en az 1 kare seçilmiş (`picks` boş değil) — 2. madde
  (albüm modeli, kargo adresi) henüz yok, tanım şimdilik bununla sınırlı
  (spec §2).
- Eski link+ZIP akışı (`SelectionController`, `/auswahl/*`,
  `Galleries::shareCreate/shareRevoke/shareFind`, admin'deki "Link
  erzeugen" bloğu) **tamamen kaldırılıyor**, yan yana durmuyor (spec §5).
- Albümcü rotaları admin ile aynı locale kısıtına tabi: sadece `de`/`tr`
  (`I18n::isAdminLocale`, `$admin_` sarmalayıcısı), site'nin `de`/`en`
  şemasına değil.
- Şablonlarda **sadece** kodda zaten var olan Tailwind sınıfları
  kullanılıyor (kopyala-yapıştır, uydurma sınıf yok); yine de her yeni
  şablondan sonra `npx @tailwindcss/cli -i php/assets/app.css -o
  php/public/assets/style.css --minify` çalıştırılıyor — aksi halde stil
  görünmez (bkz. `php/DURUM.md` "Tuzaklar").
- Testler `php bin/test.php` ile çalışıyor, framework yok, DB'siz — sadece
  saf fonksiyonlar test edilebilir (`Galleries::isReady()`); oturum/DB
  gerektiren kod (`Albumist::login`) manuel doğrulanıyor.

---

### Task 1: `Galleries::isReady()` — hazır galeri tanımı

**Files:**
- Modify: `php/src/Galleries.php:345` (mevcut `isSelectionUnseen()` metodunun
  hemen altına ekle, `/* ------------------------------ Schreiben
  ----------------------------- */` bölümünden önce)
- Test: `php/tests/albumist.php` (yeni)

**Interfaces:**
- Produces: `Galleries::isReady(?array $selection): bool` — sonraki
  görevler (`AlbumistController`) bunu `Galleries::selection($code)`'un
  dönüşüyle çağırıyor.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/albumist.php` dosyasını oluştur:

```php
<?php

declare(strict_types=1);

use Atelier\Galleries;

/*
 * Bir galeri "hazır" sayılır ⇔ seçim var ve en az bir kare seçilmiş.
 * 2. madde (albüm modeli, kargo adresi) eklenene kadar tek ölçüt bu —
 * ZIP akışının zaten kullandığı ölçütle aynı.
 */

assert_same(
    false,
    Galleries::isReady(null),
    'Hazır: seçim hiç yoksa hazır değildir'
);

assert_same(
    false,
    Galleries::isReady(['picks' => []]),
    'Hazır: seçim var ama kare seçilmemişse hazır değildir'
);

assert_same(
    true,
    Galleries::isReady(['picks' => [0, 2]]),
    'Hazır: en az bir kare seçilmişse hazırdır'
);
```

- [ ] **Step 2: Testin başarısız olduğunu doğrula**

Çalıştır: `cd php && php bin/test.php albumist`

Beklenen: PHP Fatal error / Uncaught Error — `Call to undefined method
Atelier\Galleries::isReady()`. (Bu test dosyası framework'süz çalışıyor;
tanımsız statik metot çağrısı testi "fail" listesine düşürmez, script'i
tamamen durdurur — bu beklenen davranış, metodu Step 3'te ekleyince geçer.)

- [ ] **Step 3: Minimal implementasyonu yaz**

`php/src/Galleries.php` içinde `isSelectionUnseen()` metodunun hemen
altına (satır 345 civarı, `}` kapanışından sonra) ekle:

```php

    /**
     * Bir galeri "hazır" sayılır ⇔ seçim var ve en az bir kare seçilmiş.
     *
     * 2. madde (albüm modeli, kargo adresi vb.) eklenene kadar tek ölçüt
     * bu — mevcut ZIP indirme akışının zaten kullandığı ölçütle aynı.
     *
     * @param array<string,mixed>|null $selection
     */
    public static function isReady(?array $selection): bool
    {
        return $selection !== null && (array) ($selection['picks'] ?? []) !== [];
    }
```

- [ ] **Step 4: Testin geçtiğini doğrula**

Çalıştır: `cd php && php bin/test.php albumist`

Beklenen: `3 Prüfungen bestanden.` (exit code 0)

- [ ] **Step 5: Commit**

```bash
git add php/src/Galleries.php php/tests/albumist.php
git commit -m "Galleries::isReady() — hazır galeri ölçütü

Bir galeri hazır sayılır ⇔ seçim var ve en az bir kare seçilmiş.
Albümcü girişinin liste/detay/zip akışları bunu kullanacak.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 2: `Albumist.php` — oturum ve giriş mantığı

**Files:**
- Modify: `php/config.example.php` (admin_key bloğunun altına)
- Create: `php/src/Albumist.php`

**Interfaces:**
- Consumes: `Security::session()`, `Security::throttle(string,int,int): bool`,
  `Security::checkCsrf(?string): bool`, `Security::csrf(): string`,
  `Config::str(string,string): string`, `I18n::path(string,?string): string`
- Produces: `Albumist::isLoggedIn(): bool`, `Albumist::login(string $user,
  string $password): bool`, `Albumist::logout(): void`,
  `Albumist::requireLogin(string $locale): void` — Task 3
  (`AlbumistController`) constructor'ı `requireLogin()`'i çağıracak.
  `requireLogin()` içeride `View::page('pages/albumist-login', [...])`
  render ediyor — bu template Task 4'te oluşturulacak (Task 2 bitince bu
  template henüz yok, o yüzden manuel tarayıcı testi Task 4'ten sonra
  yapılacak; bu görev sadece kod yazıyor, çalıştırmıyor).

- [ ] **Step 1: `config.example.php`'ye örnek anahtarları ekle**

`php/config.example.php` içinde `'admin_key' => 'bitte-aendern',` satırının
hemen altına ekle:

```php

    /*
     * Zugang für die Albumcu-Anmeldung (/albumcu) – ein gemeinsamer Zugang
     * für den Albumhersteller, kein eigenes Benutzerkonto pro Firma.
     * Gleiche Regel wie beim admin_key: Klartext funktioniert, ein Hash
     * (password_hash) ist sicherer.
     */
    'albumist_user' => 'albumcu',
    'albumist_key'  => 'bitte-aendern',
```

- [ ] **Step 2: `src/Albumist.php`'yi oluştur**

```php
<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Anmeldung des Albumherstellers.
 *
 * Ein gemeinsamer Zugang aus config.php, kein eigenes Benutzerkonto pro
 * Firma – genau wie beim Adminbereich (Admin::login). Eigene Sitzung,
 * eigenes Sitzungsfeld: ein Admin-Login öffnet hier nichts, und umgekehrt.
 */
final class Albumist
{
    /** Nach so langer Untaetigkeit ist Schluss. */
    private const IDLE = 4 * 3600;

    /** Und spaetestens dann in jedem Fall, auch bei Betrieb. */
    private const LIFETIME = 12 * 3600;

    public static function isLoggedIn(): bool
    {
        Security::session();
        if (empty($_SESSION['albumist'])) {
            return false;
        }

        $now = time();
        $seen = (int) ($_SESSION['albumistSeen'] ?? 0);
        $since = (int) ($_SESSION['albumistSince'] ?? 0);

        if (($now - $seen) > self::IDLE || ($now - $since) > self::LIFETIME) {
            self::logout();
            return false;
        }

        $_SESSION['albumistSeen'] = $now;
        return true;
    }

    /** @return bool true bei erfolgreicher Anmeldung */
    public static function login(string $user, string $password): bool
    {
        if (Security::throttle('albumist-login', 8, 900)) {
            return false;
        }

        $expectedUser = Config::str('albumist_user', '');
        $expectedKey = Config::str('albumist_key', '');
        $user = trim($user);
        $password = trim($password);

        if ($expectedUser === '' || $expectedKey === '' || $user === '' || $password === '') {
            return false;
        }

        if (!hash_equals($expectedUser, $user)) {
            return false;
        }

        // Steht in der config.php ein Hash (password_hash), wird er geprueft;
        // sonst der Klartext, zeitkonstant – gleiche Regel wie Admin::login.
        $ok = str_starts_with($expectedKey, '$2y$') || str_starts_with($expectedKey, '$argon2')
            ? password_verify($password, $expectedKey)
            : hash_equals($expectedKey, $password);

        if (!$ok) {
            return false;
        }

        Security::session();
        session_regenerate_id(true);
        $_SESSION['albumist'] = true;
        $_SESSION['albumistSince'] = time();
        $_SESSION['albumistSeen'] = time();

        return true;
    }

    public static function logout(): void
    {
        Security::session();
        unset($_SESSION['albumist'], $_SESSION['albumistSince'], $_SESSION['albumistSeen']);
        session_regenerate_id(true);
    }

    /**
     * Wird von AlbumistController als Erstes aufgerufen. Ohne Anmeldung wird
     * das Anmeldeformular gezeigt und die Ausführung beendet.
     */
    public static function requireLogin(string $locale): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        $error = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['password'])) {
            if (Security::checkCsrf($_POST['csrf'] ?? null) && self::login((string) ($_POST['user'] ?? ''), (string) $_POST['password'])) {
                $back = (string) ($_SERVER['REQUEST_URI'] ?? '');
                if ($back === '' || !str_starts_with($back, '/') || str_starts_with($back, '//')) {
                    $back = I18n::path('/albumcu', $locale);
                }
                header('Location: ' . $back, true, 303);
                exit;
            }
            $error = true;
        }

        View::page('pages/albumist-login', [
            'locale' => $locale,
            'path'   => I18n::path('/albumcu', $locale),
            'meta'   => ['title' => 'Albümcü', 'noindex' => true, 'bare' => true],
            'error'  => $error,
            'csrf'   => Security::csrf(),
        ]);
        exit;
    }
}
```

- [ ] **Step 3: Sözdizimini doğrula**

Çalıştır: `cd php && php -l src/Albumist.php && php -l config.example.php`

Beklenen: her ikisi için `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add php/src/Albumist.php php/config.example.php
git commit -m "Albümcü oturum/giriş mantığı (Albumist.php)

Admin.php'deki login/isLoggedIn/logout/requireLogin deseninin aynısı,
ayrı bir \$_SESSION anahtarıyla — admin oturumuyla karışmıyor. Tek
ortak hesap config.php'den (albumist_user/albumist_key).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 3: `AlbumistController` + rotalar

**Files:**
- Create: `php/src/Controllers/AlbumistController.php`
- Modify: `php/public/index.php`

**Interfaces:**
- Consumes: `Albumist::requireLogin(string): void`, `Albumist::logout(): void`,
  `Galleries::all(): array`, `Galleries::find(string): ?array`,
  `Galleries::selection(string): ?array`, `Galleries::isReady(?array): bool`,
  `Galleries::coverPhoto(array,?array): ?array`,
  `Galleries::selectedPhotos(array,?array): array`,
  `PageController::notFound(string): void`
- Produces: `AlbumistController::index()`, `::show(array $params)`,
  `::zip(array $params)`, `::logout()` — rotalardan çağrılıyor, template'lere
  şu değişkenleri veriyor: `index()` → `rows` (Task 4'ün
  `albumist-list.php`'si bunu okuyor), `show()` → `gallery`, `selection`,
  `photos`, `cover`, `code`, `dateLong` (Task 4'ün `albumist-show.php`'si
  bunu okuyor, `selection.php`'nin aynısı).

- [ ] **Step 1: `AlbumistController.php`'yi oluştur**

```php
<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Albumist;
use Atelier\Dates;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Security;
use Atelier\View;

/**
 * Der Bereich des Albumherstellers: hazır Galerien sehen, ZIP laden.
 *
 * Ersetzt den alten Link-Weg (SelectionController) – gleicher ZIP-Aufbau,
 * aber mit einer eigenen, dauerhaften Anmeldung statt eines befristeten,
 * geteilten Tokens.
 */
final class AlbumistController
{
    public function __construct(private readonly string $locale)
    {
        Albumist::requireLogin($this->locale);
    }

    /* --------------------------------- Liste -------------------------------- */

    public function index(): void
    {
        $rows = [];
        foreach (Galleries::all() as $gallery) {
            $code = (string) ($gallery['code'] ?? '');
            $selection = Galleries::selection($code);
            if (!Galleries::isReady($selection)) {
                continue;
            }

            $rows[] = [
                'gallery' => $gallery,
                'cover'   => Galleries::coverPhoto($gallery, $selection),
                'count'   => count((array) ($selection['picks'] ?? [])),
            ];
        }

        View::page('pages/albumist-list', [
            'locale' => $this->locale,
            'path'   => I18n::path('/albumcu', $this->locale),
            'meta'   => ['title' => 'Albümcü', 'noindex' => true, 'bare' => true],
            'rows'   => $rows,
        ]);
    }

    /* --------------------------------- Akte --------------------------------- */

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $code = Security::clean($params['code'] ?? '', 64);
        $gallery = Galleries::find($code);
        $selection = $gallery === null ? null : Galleries::selection($code);

        if ($gallery === null || !Galleries::isReady($selection)) {
            (new PageController())->notFound($this->locale);
            return;
        }

        View::page('pages/albumist-show', [
            'locale'    => $this->locale,
            'path'      => I18n::path('/albumcu/' . $code, $this->locale),
            'meta'      => ['title' => (string) ($gallery['couple'] ?? ''), 'noindex' => true, 'bare' => true],
            'gallery'   => $gallery,
            'selection' => $selection,
            'photos'    => Galleries::selectedPhotos($gallery, $selection),
            'cover'     => Galleries::coverPhoto($gallery, $selection),
            'code'      => $code,
            'dateLong'  => Dates::long((string) ($gallery['date'] ?? ''), $this->locale),
        ]);
    }

    /** Alles auf einmal – der Grund, warum es diese Seite gibt. @param array<string,string> $params */
    public function zip(array $params): void
    {
        $code = Security::clean($params['code'] ?? '', 64);
        $gallery = Galleries::find($code);
        $selection = $gallery === null ? null : Galleries::selection($code);

        if ($gallery === null || !Galleries::isReady($selection)) {
            (new PageController())->notFound($this->locale);
            return;
        }

        $photos = Galleries::selectedPhotos($gallery, $selection);

        // Nur was wirklich auf der Platte liegt: Platzhalterbilder gehören
        // niemandem und haben im Album nichts zu suchen.
        $files = array_values(array_filter(
            $photos,
            static fn (array $p): bool => $p['original'] !== null
        ));

        if ($files === [] || !class_exists('ZipArchive')) {
            (new PageController())->notFound($this->locale);
            return;
        }

        $zip = new \ZipArchive();
        $temp = tempnam(sys_get_temp_dir(), 'albumcu');
        if ($temp === false || $zip->open($temp, \ZipArchive::OVERWRITE) !== true) {
            (new PageController())->notFound($this->locale);
            return;
        }

        foreach ($files as $file) {
            // Durchnummeriert in der Reihenfolge, die das Paar gesehen hat.
            $name = sprintf('%03d-%s', $file['nr'], basename((string) $file['original']));
            $zip->addFile((string) $file['original'], $name);
        }

        $zip->close();

        $download = $code . '-auswahl.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $download . '"');
        header('Content-Length: ' . (string) filesize($temp));
        header('X-Content-Type-Options: nosniff');
        readfile($temp);
        @unlink($temp);
        exit;
    }

    /* --------------------------------- Login --------------------------------- */

    public function logout(): void
    {
        Albumist::logout();
        header('Location: ' . I18n::path('/albumcu', $this->locale), true, 303);
        exit;
    }
}
```

- [ ] **Step 2: Sözdizimini doğrula**

Çalıştır: `cd php && php -l src/Controllers/AlbumistController.php`

Beklenen: `No syntax errors detected`

- [ ] **Step 3: Rotaları bağla**

`php/public/index.php` içinde `use Atelier\Controllers\SelectionController;`
satırını (satır 22) şununla değiştir:

```php
use Atelier\Controllers\AlbumistController;
```

Sonra `$router->any('/{locale}/admin/integrationen', ...)` satırının (satır
195) hemen altına, `$router->get('/{locale}/impressum', ...)` satırından
önce ekle:

```php

// Albümcü: kalıcı giriş, sadece hazır galerileri gösterir. "abmelden" ve
// "zip" {code} tek-parça deseninden ÖNCE kayıtlı — yoksa "abmelden" bir
// galeri kodu gibi okunur (bkz. /galerie/abmelden'in aynı sırası).
$router->any('/{locale}/albumcu', $admin_(static fn (array $p) => (new AlbumistController($p['locale']))->index()));
$router->get('/{locale}/albumcu/abmelden', $admin_(static fn (array $p) => (new AlbumistController($p['locale']))->logout()));
$router->get('/{locale}/albumcu/{code}/zip', $admin_(static fn (array $p) => (new AlbumistController($p['locale']))->zip($p)));
$router->get('/{locale}/albumcu/{code}', $admin_(static fn (array $p) => (new AlbumistController($p['locale']))->show($p)));
```

(Eski `/auswahl/*` rotaları — satır 97-100 — bu görevde henüz silinmiyor;
Task 5'te, eski akışın geri kalanıyla birlikte kaldırılıyor. Bu görevin
sonunda iki paralel giriş yolu var — geçici, Task 5'e kadar.)

- [ ] **Step 4: Sözdizimini doğrula**

Çalıştır: `cd php && php -l public/index.php`

Beklenen: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add php/src/Controllers/AlbumistController.php php/public/index.php
git commit -m "AlbumistController + /albumcu rotaları

Liste (hazır galeriler), detay, ZIP indirme, çıkış. Eski
SelectionController'daki ZIP toplama mantığının aynısı, token yerine
Albumist oturumu + {code} kullanıyor. Şablonlar henüz yok — Task 4'te.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 4: Şablonlar (login, liste, detay) + Tailwind derleme

**Files:**
- Create: `php/templates/pages/albumist-login.php`
- Create: `php/templates/pages/albumist-list.php`
- Create: `php/templates/pages/albumist-show.php`
- Modify: `php/public/assets/style.css` (derleme çıktısı, elle değil komutla)

**Interfaces:**
- Consumes: Task 2'nin `Albumist::requireLogin()` verdiği `locale`, `error`,
  `csrf`; Task 3'ün `index()` verdiği `rows` (her eleman
  `{gallery: array, cover: array|null, count: int}`); Task 3'ün `show()`
  verdiği `gallery`, `selection`, `photos`, `cover`, `code`, `dateLong`.

- [ ] **Step 1: `albumist-login.php`'yi oluştur**

```php
<?php
/**
 * Albümcü girişi. Admin girişiyle aynı görsel desen, ayrı bir bölüm.
 *
 * @var string $locale
 * @var bool $error
 * @var string $csrf
 */

use function Atelier\e;

$de = $locale === 'de';
?>
<div class="mx-auto max-w-sm px-5 py-14 sm:py-20">
  <div class="border border-sand-deep p-8">
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Albumhersteller-Login' : 'Albümcü girişi' ?></h2>
    <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
      <?= $de
          ? 'Dieser Bereich ist nicht öffentlich. Bitte anmelden.'
          : 'Bu bölüm herkese açık değildir. Lütfen giriş yapın.' ?>
    </p>

    <form method="post" class="mt-7 space-y-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

      <div>
        <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="user">
          <?= $de ? 'Benutzername' : 'Kullanıcı adı' ?>
        </label>
        <input id="user" name="user" type="text" required autocomplete="username" autofocus
               class="w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold">
      </div>

      <div>
        <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="password">
          <?= $de ? 'Passwort' : 'Parola' ?>
        </label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
               class="w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold">
      </div>

      <?php if ($error) : ?>
        <p class="text-sm text-red-700"><?= $de ? 'Zugangsdaten falsch.' : 'Giriş bilgileri hatalı.' ?></p>
      <?php endif; ?>

      <button class="w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Anmelden' : 'Giriş yap' ?>
      </button>
    </form>
  </div>
</div>
```

- [ ] **Step 2: `albumist-list.php`'yi oluştur**

```php
<?php
/**
 * Albümcü girişi sonrası: hazır olan galerilerin listesi.
 *
 * @var string $locale
 * @var list<array{gallery:array<string,mixed>,cover:array{nr:int,url:string,original:?string,name:string}|null,count:int}> $rows
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
?>
<div class="mx-auto max-w-3xl px-5 py-14 sm:py-20">
  <div class="flex items-center justify-between">
    <h1 class="font-display text-3xl font-light text-ink sm:text-4xl"><?= $de ? 'Hazır Galerien' : 'Hazır galeriler' ?></h1>
    <a href="<?= e(I18n::path('/albumcu/abmelden', $locale)) ?>"
       class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
      <?= $de ? 'Abmelden' : 'Çıkış yap' ?>
    </a>
  </div>

  <?php if ($rows === []) : ?>
    <p class="mt-10 border border-sand-deep p-6 text-sm leading-relaxed text-muted">
      <?= $de ? 'Noch keine Galerie bereit.' : 'Henüz hazır galeri yok.' ?>
    </p>
  <?php else : ?>
    <div class="mt-8 divide-y divide-sand-deep border-y border-sand-deep">
      <?php foreach ($rows as $row) : ?>
        <?php $gallery = $row['gallery']; $code = (string) ($gallery['code'] ?? ''); ?>
        <a href="<?= e(I18n::path('/albumcu/' . $code, $locale)) ?>"
           class="flex items-center gap-4 py-5 transition-colors hover:bg-sand/30">
          <?php if ($row['cover'] !== null) : ?>
            <img src="<?= e($row['cover']['url']) ?>" alt="" class="h-14 w-11 object-cover">
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="text-[0.95rem] text-ink"><?= e((string) ($gallery['couple'] ?? '')) ?></div>
            <div class="mt-1 text-[0.78rem] text-muted">
              <?= e(Dates::short((string) ($gallery['date'] ?? ''))) ?>
              · <?= $row['count'] ?> <?= $de ? 'Bilder' : 'kare' ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
```

- [ ] **Step 3: `albumist-show.php`'yi oluştur**

```php
<?php
/**
 * Tek galerinin detayı — albümcü için. Eski selection.php'nin devamı,
 * token yerine oturum + {code} kullanıyor.
 *
 * @var string $locale
 * @var array<string,mixed> $gallery
 * @var array<string,mixed>|null $selection
 * @var list<array{nr:int,url:string,original:?string,name:string}> $photos
 * @var array{nr:int,url:string,original:?string,name:string}|null $cover
 * @var string $code
 * @var string $dateLong
 */

use function Atelier\e;
use Atelier\I18n;

$de = $locale === 'de';
$couple = (string) ($gallery['couple'] ?? '');
$withOriginal = array_values(array_filter($photos, static fn (array $p): bool => $p['original'] !== null));
?>
<div class="mx-auto max-w-5xl px-5 py-14 sm:py-20">

  <a href="<?= e(I18n::path('/albumcu', $locale)) ?>"
     class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
    <?= $de ? '← Zur Liste' : '← Listeye dön' ?>
  </a>

  <div class="mt-6 text-[0.62rem] uppercase tracking-[0.24em] text-muted"><?= $de ? 'Bildauswahl' : 'Fotoğraf seçimi' ?></div>
  <h1 class="font-display mt-2 text-3xl font-light text-ink sm:text-4xl"><?= e($couple) ?></h1>
  <p class="mt-3 text-sm text-muted">
    <?= e($dateLong) ?><?= (string) ($gallery['venue'] ?? '') !== '' ? ' · ' . e((string) $gallery['venue']) : '' ?>
  </p>

  <?php if ($photos === []) : ?>
    <p class="mt-10 border border-sand-deep p-6 text-sm leading-relaxed text-muted">
      <?= $de ? 'Es ist noch keine Auswahl getroffen worden.' : 'Henüz bir seçim yapılmadı.' ?>
    </p>
  <?php else : ?>

    <div class="mt-8 flex flex-wrap items-center gap-6 border-y border-sand-deep py-6">
      <div>
        <div class="font-display text-3xl font-light text-ink"><?= count($photos) ?></div>
        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Bilder' : 'Kare' ?></div>
      </div>

      <?php if ($withOriginal !== []) : ?>
        <a href="<?= e(I18n::path('/albumcu/' . $code . '/zip', $locale)) ?>"
           class="ml-auto bg-ink px-8 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          <?= $de ? 'Alle herunterladen (ZIP)' : 'Tümünü indir (ZIP)' ?>
        </a>
      <?php endif; ?>
    </div>

    <?php if (count($withOriginal) < count($photos)) : ?>
      <p class="mt-5 border border-gold/50 bg-sand/40 px-5 py-3 text-[0.84rem] leading-relaxed text-ink">
        <?= $de
          ? 'Von ' . count($photos) . ' Bildern liegen ' . count($withOriginal) . ' in voller Auflösung vor. Bitte beim Fotografen nach den übrigen fragen.'
          : count($photos) . ' karenin ' . count($withOriginal) . ' tanesi tam çözünürlükte. Kalanlar için fotoğrafçıya sorun.' ?>
      </p>
    <?php endif; ?>

    <?php if ($cover !== null) : ?>
      <div class="mt-6 flex items-center gap-4 border-l-2 border-gold pl-5">
        <img src="<?= e($cover['url']) ?>" alt="" class="h-20 w-16 object-cover">
        <div>
          <div class="text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Titelbild' : 'Kapak fotoğrafı' ?></div>
          <div class="mt-1 text-[0.9rem] text-ink"><?= $de ? 'Nr.' : 'No.' ?> <?= (int) $cover['nr'] ?> — <?= e($cover['name']) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ((string) ($selection['note'] ?? '') !== '') : ?>
      <div class="mt-6 border-l-2 border-gold pl-5">
        <div class="text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Notiz des Paares' : 'Çiftin notu' ?></div>
        <p class="mt-2 text-[0.95rem] leading-relaxed text-ink">&bdquo;<?= e((string) $selection['note']) ?>&ldquo;</p>
      </div>
    <?php endif; ?>

    <div class="mt-10 grid gap-6 sm:grid-cols-3 lg:grid-cols-4">
      <?php foreach ($photos as $photo) : ?>
        <figure>
          <div class="relative aspect-[3/4] overflow-hidden bg-sand">
            <img src="<?= e($photo['url']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
            <span class="absolute left-0 top-0 bg-ink/80 px-2 py-1 text-[0.62rem] tracking-[0.1em] text-cream">
              <?= (int) $photo['nr'] ?>
            </span>
          </div>
          <figcaption class="mt-2 break-all font-mono text-[0.68rem] text-muted"><?= e($photo['name']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
```

- [ ] **Step 4: Sözdizimini doğrula**

Çalıştır: `cd php && php -l templates/pages/albumist-login.php && php -l templates/pages/albumist-list.php && php -l templates/pages/albumist-show.php`

Beklenen: üçü için de `No syntax errors detected`

- [ ] **Step 5: Tailwind'i yeniden derle**

Çalıştır (repo kökünden): `npx @tailwindcss/cli -i php/assets/app.css -o php/public/assets/style.css --minify`

Beklenen: komut hatasız biter, `php/public/assets/style.css`'in değişim
zamanı güncellenir.

- [ ] **Step 6: Commit**

```bash
git add php/templates/pages/albumist-login.php php/templates/pages/albumist-list.php php/templates/pages/albumist-show.php php/public/assets/style.css
git commit -m "Albümcü şablonları: giriş, liste, detay

selection.php'nin görsel deseni korunuyor (aynı Tailwind sınıfları,
yeniden derlendi). Liste yalnızca hazır galerileri gösteriyor.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 5: Eski link akışının kaldırılması

**Files:**
- Delete: `php/src/Controllers/SelectionController.php`
- Delete: `php/templates/pages/selection.php`
- Modify: `php/public/index.php` (eski `/auswahl/*` rotaları)
- Modify: `php/src/Galleries.php` (`shareCreate`/`shareRevoke`/`shareFind`)
- Modify: `php/src/Controllers/CustomerAdminController.php`
  (`freigabe`/`freigabe-aus` dalları)
- Modify: `php/templates/admin/customer.php` ("Für den Albumhersteller"
  bloğu)

**Interfaces:**
- Bu görev sadece siliyor — hiçbir yeni interface üretmiyor. Task 1-4'ün
  ürettiği `Galleries::isReady`, `Albumist`, `AlbumistController`,
  `pages/albumist-*` dosyalarına dokunmuyor.

- [ ] **Step 1: `SelectionController.php`'yi ve `selection.php`'yi sil**

```bash
cd php
rm src/Controllers/SelectionController.php
rm templates/pages/selection.php
```

- [ ] **Step 2: `public/index.php`'den eski rotaları kaldır**

Şu iki satırı (ve üstündeki açıklama yorumunu) sil:

```php
// Auswahl fuer den Albumhersteller: geheimer, befristeter Link ohne Login.
// "zip" steht vor dem allgemeinen Muster, sonst wird es als Token gelesen.
$router->get('/{locale}/auswahl/{token}/zip', $page_(static fn (array $p) => (new SelectionController())->zip($p)));
$router->get('/{locale}/auswahl/{token}', $page_(static fn (array $p) => (new SelectionController())->show($p)));
```

(Bu satırlar Task 3'te eklenen `/albumcu` rotalarının **üstünde**, dosyanın
başlarında duruyor — `$page_`, `$admin_` tanımlarının hemen altında.)

- [ ] **Step 3: Sözdizimini doğrula**

Çalıştır: `cd php && php -l public/index.php`

Beklenen: `No syntax errors detected` (artık `SelectionController` hiçbir
yerde `use` edilmiyor ve çağrılmıyor olmalı — `grep -n
"SelectionController" public/index.php` boş dönmeli)

- [ ] **Step 4: `Galleries.php`'den paylaşım metotlarını sil**

`php/src/Galleries.php` içinde, `/* --------------------------- Auswahl
teilen --------------------------- */` başlığından (satır 90 civarı)
`shareFind()`'ın kapanışına kadar olan üç metodu (`shareCreate`,
`shareRevoke`, `shareFind` — satır 162-217 civarı) tamamen sil. Başlık
yorumunu da kaldır (altında başka metot yok, `selectedPhotos()` ve
`coverPhoto()` bu başlıktan önce, "Auswahl teilen" başlığı sadece bu üçü
için vardı).

Kontrol: `grep -n "shareCreate\|shareRevoke\|shareFind" src/Galleries.php`
boş dönmeli.

- [ ] **Step 5: `CustomerAdminController.php`'den `freigabe` dallarını sil**

`php/src/Controllers/CustomerAdminController.php` içindeki `apply()`
metodunda şu iki satırı (ve üstündeki yorumu) kaldır:

```php
            // Link fuer den Albumhersteller – erzeugen und wieder abschalten.
            'freigabe'      => Galleries::shareCreate($code),
            'freigabe-aus'  => Galleries::shareRevoke($code),
```

- [ ] **Step 6: `templates/admin/customer.php`'den paylaşım bloğunu sil**

Fotoğraf grid'inin (`<?php foreach ($chosen as $photo) : ?>` döngüsünün
kapanışı) hemen altındaki şu bloğu tamamen kaldır — döngünün kapanış
`</div>`'i ile `<?php if ($chosen !== []) : ?>`'in kendi `<?php endif; ?>`'i
arasındaki her şey:

```php
            <?php /* ------------------- Link für den Albumhersteller ------------------- */ ?>
            <?php $share = (array) (($gallery ?? [])["share"] ?? []); ?>
            <div class="mt-6 border-t border-sand-deep pt-5">
              <div class="text-[0.62rem] uppercase tracking-[0.18em] text-gold">
                <?= $de ? 'Für den Albumhersteller' : 'Albümcü için' ?>
              </div>

              <?php if (($share['token'] ?? '') === '') : ?>
                <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
                  <?= $de
                    ? 'Erzeugt einen geheimen Link. Wer ihn hat, sieht genau diese Bilder und lädt sie als ZIP – ohne Zugang zur Galerie.'
                    : 'Gizli bir bağlantı üretir. Bağlantıya sahip olan tam bu fotoğrafları görür ve ZIP olarak indirir — galeriye erişmeden.' ?>
                </p>
                <form method="post" class="mt-3">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="was" value="freigabe">
                  <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Link erzeugen (30 Tage)' : 'Bağlantı üret (30 gün)' ?>
                  </button>
                </form>
              <?php else : ?>
                <?php $shareUrl = \Atelier\Config::url() . \Atelier\I18n::sitePath('/auswahl/' . (string) $share['token'], $locale); ?>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                  <code class="min-w-0 flex-1 break-all border border-sand-deep bg-cream px-4 py-3 text-[0.76rem] text-ink"><?= e($shareUrl) ?></code>
                  <button type="button" data-copy="<?= e($shareUrl) ?>"
                          class="border border-ink px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Kopieren' : 'Kopyala' ?>
                  </button>
                  <a href="<?= e(\Atelier\I18n::sitePath('/auswahl/' . (string) $share['token'] . '/zip', $locale)) ?>"
                     class="border border-gold px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-gold transition-colors hover:bg-gold hover:text-white">
                    ZIP
                  </a>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4">
                  <span class="text-[0.75rem] text-muted">
                    <?= $de ? 'Gültig bis' : 'Geçerlilik' ?>: <?= e(Dates::short((string) ($share['expires'] ?? ''))) ?>
                    · <?= count($full) ?>/<?= count($chosen) ?> <?= $de ? 'in voller Auflösung' : 'tam çözünürlükte' ?>
                  </span>
                  <form method="post" class="ml-auto">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="was" value="freigabe-aus">
                    <button class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
                      <?= $de ? 'Link abschalten' : 'Bağlantıyı kapat' ?>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
```

Sonrasında geriye `<?php if ($chosen !== []) : ?>` ve hemen ardından
`<?php endif; ?>` kalmalı — arada sadece fotoğraf grid'i olacak.

Ayrıca bu bloğun üstünde, `$chosen` tanımının hemen altındaki artık
kullanılmayan şu satırı da sil (sadece silinen blokta `count($full)` için
vardı):

```php
          $full = array_values(array_filter($chosen, static fn (array $p): bool => $p['original'] !== null));
```

- [ ] **Step 7: Sözdizimini doğrula**

Çalıştır: `cd php && php -l src/Galleries.php && php -l src/Controllers/CustomerAdminController.php && php -l templates/admin/customer.php`

Beklenen: üçü için de `No syntax errors detected`

- [ ] **Step 8: Kalıntı referans kalmadığını doğrula**

Çalıştır (repo kökünden): `grep -rn "shareCreate\|shareRevoke\|shareFind\|SelectionController\|/auswahl/" php/src php/templates php/public`

Beklenen: boş çıktı (hiçbir eşleşme yok)

- [ ] **Step 9: Testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`

Beklenen: tüm testler geçiyor (önceki test sayısına eşit veya fazla,
başarısız yok) — bu görev sadece kod sildiği için mevcut hiçbir testi
bozmamalı.

- [ ] **Step 10: Commit**

```bash
git add -A php/src php/templates php/public
git commit -m "Eski link+ZIP akışını kaldır (SelectionController, /auswahl/*)

Yerini /albumcu'daki kalıcı albümcü girişi aldı (Task 1-4). Admin
panelindeki 'Link erzeugen' düğmesi ve Galleries::shareCreate/
shareRevoke/shareFind ile birlikte gidiyor — artık kullanılmıyorlar.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 6: Uçtan uca manuel doğrulama

**Files:** yok (sadece çalıştırma/gözlem)

**Interfaces:** yok — bu görev Task 1-5'in bir araya geldiğinde
çalıştığını doğruluyor.

- [ ] **Step 1: Yerel `config.php`'ye albümcü hesabını ekle**

`php/config.php` dosyasına (repoda değil, yerel kopyada) şu iki satırı
ekle — yoksa `albumist_key` boş kalır ve `Albumist::login()` her zaman
`false` döner:

```php
    'albumist_user' => 'albumcu',
    'albumist_key'  => 'test1234',
```

- [ ] **Step 2: Dev sunucusunu başlat**

Çalıştır (arka planda, zaten çalışmıyorsa — `netstat -ano | grep 8080` ile
kontrol et): `cd php && php -S 127.0.0.1:8080 -t public public/dev-router.php`

- [ ] **Step 3: En az bir galeriyi "hazır" yap**

Tarayıcıda `http://127.0.0.1:8080/de/admin` → parola `demo` → Kunden &
Galerien → demo müşterisi (`elif-marco`) → en az bir fotoğraf yükle veya
zaten yüklüyse devam et. Sonra `http://127.0.0.1:8080/de/galerie/elif-marco`
adresine `elif-marco` / `solitude24` ile gir, en az bir kareyi kalple seç
ve kaydet — bu, `selections` tablosuna `picks` dolu bir kayıt düşürür.

- [ ] **Step 4: Albümcü girişini dene**

`http://127.0.0.1:8080/de/albumcu` adresine git:
- Yanlış parolayla giriş dene → "Giriş bilgileri hatalı" mesajı görünmeli.
- Doğru bilgilerle (`albumcu` / `test1234`) giriş yap → hazır galeriler
  listesi görünmeli, az önce seçim yapılan galeri (çift adı, tarih, kare
  sayısı) listede olmalı.
- Listeden galeriye tıkla → detay sayfası, seçilen kareler, "ZIP indir"
  düğmesi görünmeli.
- ZIP düğmesine tıkla → dosya iniyor mu kontrol et (placeholder/seed
  fotoğraflarla test ediliyorsa ZIP boş dönebilir — bu beklenen, gerçek
  yüklenmiş fotoğraf varsa dosya inmeli).
- "Çıkış yap"a tıkla → `/albumcu`'ya dönüp tekrar giriş formu görünmeli.

- [ ] **Step 5: Eski link akışının gerçekten kalktığını doğrula**

`http://127.0.0.1:8080/de/auswahl/herhangibirtoken` adresine git → 404
sayfası görünmeli (Not Found).

Admin panelinde `http://127.0.0.1:8080/de/admin/kunden/elif-marco`'ya git
→ "Für den Albumhersteller" / "Link erzeugen" bölümü artık görünmemeli.

- [ ] **Step 6: `config.php`'nin commit'e girmediğini doğrula**

Çalıştır: `cd .. && git status`

Beklenen: `php/config.php` listede **görünmemeli** (`.gitignore`'da zaten
hariç tutuluyor) — Step 1'de eklenen satırlar yerel kalmalı, commit
edilmemeli.
