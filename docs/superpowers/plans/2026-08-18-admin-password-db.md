# Admin parolası veri tabanından — Uygulama planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin parolasını `config.php` yerine `integrations` tablosunun JSON'una taşı, panelden değiştirilebilir yap, config'i bootstrap için tut.

**Architecture:** `integrations.data` JSON'una `admin.passwordHash` alanı eklenir. `Admin::verify()` DB hash'i varsa onu, yoksa mevcut config yolunu kullanır. Yeni `/admin/zugang` sekmesi + `AccessAdminController` + `access.php` template ile parola değiştirme formu.

**Tech Stack:** PHP 8, MariaDB (JSON in LONGTEXT), Tailwind template. Şema değişikliği yok.

**Not: test suite yok.** Her task `php -l` sözdizim kontrolü + manuel smoke test + commit ile biter.

**Working directory:** Her komut `C:\Users\Yusuf\Documents\GitHub\atelier-lumiere` kökünden. PHP kaynakları `php/` altında.

**Branch:** `admin-password-db` (halihazırda checkout edilmiş — `git status` teyit).

---

## Dosya haritası

| Dosya | Ne olur |
|---|---|
| `php/src/Integrations.php` | `defaults()` `admin` grubunu içerir, `all()` merge döngüsüne `admin` eklenir, `adminPasswordHash()` + `saveAdminPasswordHash()` metotları |
| `php/src/Admin.php` | `TABS`'a `/zugang` sekmesi; `verify()` metodu çıkarılır; `login()` `verify()`'ı çağırır; `passwordWarning()` DB hash varsa boş döner |
| `php/src/Controllers/AccessAdminController.php` | Yeni — GET formu, POST parola değişimini işler |
| `php/templates/admin/access.php` | Yeni — mevcut parola + yeni parola × 2 formu |
| `php/public/index.php` | Yeni route: `/{locale}/admin/zugang` |
| `php/config.example.php` | `admin_key` yorumunu güncelle (bootstrap açıklaması) |

---

## Task 1: Integrations — defaults + helper metotları

**Files:**
- Modify: `php/src/Integrations.php`

- [ ] **Step 1: `defaults()` içine `admin` grubunu ekle**

`defaults()` metodunun return dizisinde, `'meta'` girişinden sonra ekle. Mevcut:

```php
'meta'      => ['pixelId' => ''],
'extras'    => [],
'updatedAt' => '',
```

Yeni:

```php
'meta'      => ['pixelId' => ''],
'admin'     => ['passwordHash' => ''],
'extras'    => [],
'updatedAt' => '',
```

- [ ] **Step 2: `all()` merge döngüsüne `admin`'i dahil et**

Mevcut satır:

```php
foreach (['paypal', 'google', 'meta'] as $group) {
    if (isset($stored[$group]) && is_array($stored[$group])) {
        $merged[$group] = array_merge($defaults[$group], $stored[$group]);
    }
}
```

Değiştir:

```php
foreach (['paypal', 'google', 'meta', 'admin'] as $group) {
    if (isset($stored[$group]) && is_array($stored[$group])) {
        $merged[$group] = array_merge($defaults[$group], $stored[$group]);
    }
}
```

- [ ] **Step 3: `adminPasswordHash()` metodunu ekle**

`Integrations` sınıfının sonuna, `mask()` metodundan önce (ya da uygun bir yere — sınıf sonu kabul edilebilir) ekle:

```php
    /* -------------------------------- Admin -------------------------------- */

    /** Panelden belirlenmiş parola hash'i. Boşsa config bootstrap devreye girer. */
    public static function adminPasswordHash(): string
    {
        $admin = self::all()['admin'] ?? [];
        return trim((string) ($admin['passwordHash'] ?? ''));
    }

    /** Yeni hash'i JSON'a yaz. `$hash` = `password_hash(..., PASSWORD_DEFAULT)` sonucu. */
    public static function saveAdminPasswordHash(string $hash): void
    {
        $settings = self::all();
        $settings['admin']['passwordHash'] = $hash;
        self::save($settings);
    }
```

- [ ] **Step 4: Sözdizim kontrolü**

Run: `php -l php/src/Integrations.php`
Expected: `No syntax errors detected in php/src/Integrations.php`

- [ ] **Step 5: Commit**

```bash
git add php/src/Integrations.php
git commit -m "integrations: add admin.passwordHash field + helpers

Extends the JSON with an admin group holding the password hash. Two
static helpers (adminPasswordHash / saveAdminPasswordHash) keep the
callers small. Task 2 wires them into Admin::verify."
```

---

## Task 2: Admin — verify() + login() refactor + DB lookup

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: Yeni `verify()` metodunu `login()`'un ÜSTÜNE ekle**

Şu bloku (mevcut `login()` metodunun hemen üstüne) yerleştir:

```php
    /**
     * Parolayı doğrula — session açmaz, sadece "doğru mu" cevabını verir.
     *
     * Rangfolge: veri tabanında hash varsa sadece o geçerli. Yoksa
     * config.php'deki `admin_key` (hash veya düz metin, mevcut mantık) —
     * bootstrap için, ilk giriş bu yoldan yapılır.
     */
    public static function verify(string $password): bool
    {
        $password = trim($password);
        if ($password === '') {
            return false;
        }

        $dbHash = Integrations::adminPasswordHash();
        if ($dbHash !== '') {
            return password_verify($password, $dbHash);
        }

        $expected = Config::str('admin_key', 'demo');
        if ($expected === '') {
            return false;
        }

        // Steht in der config.php ein Hash, wird er geprueft; sonst der
        // Klartext, zeitkonstant. Bootstrap-Weg — DB hash gesetzt = Vorrang.
        return str_starts_with($expected, '$2y$') || str_starts_with($expected, '$argon2')
            ? password_verify($password, $expected)
            : hash_equals($expected, $password);
    }
```

- [ ] **Step 2: `login()` metodunu `verify()` kullanacak şekilde sadeleştir**

Mevcut `login()` (satır ~369-400):

```php
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
```

Değiştir:

```php
    public static function login(string $password): bool
    {
        if (Security::throttle('admin-login', 8, 900)) {
            return false;
        }

        if (!self::verify($password)) {
            return false;
        }

        Security::session();
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['adminSince'] = time();
        $_SESSION['adminSeen'] = time();

        return true;
    }
```

- [ ] **Step 3: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`

- [ ] **Step 4: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: verify() splits password check from session open

DB hash from integrations wins if present; otherwise fall back to
config.php admin_key (existing bootstrap path). login() now just
throttles, calls verify(), and opens the session on success.
AccessAdminController will use verify() to gate password changes."
```

---

## Task 3: passwordWarning — DB hash varsa uyarı yok

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: `passwordWarning()`'in başına DB hash kontrolü ekle**

Mevcut metot (satır ~465):

```php
    public static function passwordWarning(string $locale): string
    {
        $key = Config::str('admin_key', '');
        $de = $locale === 'de';

        // Ein Hash ist in Ordnung, egal wie er aussieht.
        if (str_starts_with($key, '$2y$') || str_starts_with($key, '$argon2')) {
            return '';
        }
```

Yeni ilk satırlar:

```php
    public static function passwordWarning(string $locale): string
    {
        // Panelden belirlenmiş DB hash varsa: config yok sayılır, uyarı yok.
        if (Integrations::adminPasswordHash() !== '') {
            return '';
        }

        $key = Config::str('admin_key', '');
        $de = $locale === 'de';

        // Ein Hash ist in Ordnung, egal wie er aussieht.
        if (str_starts_with($key, '$2y$') || str_starts_with($key, '$argon2')) {
            return '';
        }
```

Sonra "default password" uyarı metnine bir satır ekle. Mevcut return:

```php
        if ($key === '' || in_array(mb_strtolower($key), $weak, true)) {
            return $de
                ? 'Der Adminbereich hat noch das Standardpasswort. Vor dem Livegang in der config.php ändern – am besten als Hash.'
                : 'Yönetim paneli hâlâ varsayılan parolayı kullanıyor. Yayına almadan önce config.php içinde değiştirin – tercihen hash olarak.';
        }
```

Değiştir:

```php
        if ($key === '' || in_array(mb_strtolower($key), $weak, true)) {
            return $de
                ? 'Der Adminbereich hat noch das Standardpasswort. Am besten unter „Zugang" ein neues setzen.'
                : 'Yönetim paneli hâlâ varsayılan parolayı kullanıyor. En iyisi „Erişim" sekmesinden yeni bir parola belirle.';
        }
```

Kısa parola uyarısı aynen kalır (< 12 karakter).

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`

- [ ] **Step 3: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: passwordWarning hides once DB hash is set

If integrations has an adminPasswordHash, the warning goes away — the
operator has already dealt with it. Weak-config warning now points to
the new Zugang / Erişim tab rather than config.php."
```

---

## Task 4: Admin::TABS — /zugang sekmesi

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: `TABS`'a Zugang / Erişim girişini ekle**

`TABS` sabitinde `technik` grubunun ilk sırasında dursun. Mevcut son iki entry:

```php
        ['href' => '/integrationen', 'group' => 'technik', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
        ['href' => '/systemcheck', 'group' => 'technik', 'de' => 'Vor dem Livegang', 'tr' => 'Yayın kontrolü'],
```

Aralarına (ya da entegrasyonların üstüne) ekle:

```php
        ['href' => '/zugang', 'group' => 'technik', 'de' => 'Zugang', 'tr' => 'Erişim'],
        ['href' => '/integrationen', 'group' => 'technik', 'de' => 'Integrationen', 'tr' => 'Entegrasyonlar'],
        ['href' => '/systemcheck', 'group' => 'technik', 'de' => 'Vor dem Livegang', 'tr' => 'Yayın kontrolü'],
```

`pinned:true` **yok** — ayda bir açılan bir sekme.

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`

- [ ] **Step 3: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: register the Zugang / Erişim tab

Small technik-group tab that will host the password-change form.
Controller + template land in the next two tasks."
```

---

## Task 5: AccessAdminController — yeni controller

**Files:**
- Create: `php/src/Controllers/AccessAdminController.php`

- [ ] **Step 1: Yeni controller dosyasını oluştur**

Tüm içerik:

```php
<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\I18n;
use Atelier\Integrations;
use Atelier\Security;
use Atelier\View;

/**
 * Admin parolasını panelden değiştirme.
 *
 * Parolanın kendisi Integrations::adminPasswordHash() ile veri tabanında
 * tutulur. Bu sayfa: mevcut parolayı doğrula, yeni parola × 2, hash'le ve
 * kaydet, session'ı yenile.
 */
final class AccessAdminController
{
    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    public function index(): void
    {
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();
            $error = $this->change();
            if ($error === '') {
                Admin::back($this->locale, '/zugang');
            }
        }

        View::page('admin/access', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin/zugang', $this->locale),
            'current' => '/zugang',
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'error'   => $error,
            'hasHash' => Integrations::adminPasswordHash() !== '',
        ]);
    }

    /** @return string boş = başarılı, aksi halde hata anahtarı */
    private function change(): string
    {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!Admin::verify($current)) {
            return 'current';
        }
        if (mb_strlen($new) < 8) {
            return 'short';
        }
        if ($new !== $confirm) {
            return 'mismatch';
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') {
            return 'hash';
        }

        Integrations::saveAdminPasswordHash($hash);

        // Sessiona yeni bir kimlik ver — eski cookie'yle oturum sürdürülemesin.
        session_regenerate_id(true);

        return '';
    }
}
```

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Controllers/AccessAdminController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add php/src/Controllers/AccessAdminController.php
git commit -m "access admin: controller for changing the admin password

POSTs verify the current password (via Admin::verify), enforce min 8
chars and matching confirmation, hash with PASSWORD_DEFAULT, save via
Integrations, and regenerate the session id. GET just renders the form.
Route + template land next."
```

---

## Task 6: /admin/zugang route + template

**Files:**
- Modify: `php/public/index.php`
- Create: `php/templates/admin/access.php`

- [ ] **Step 1: Router'a yeni route ekle**

`index.php` içinde admin route'larını bul. `/{locale}/admin/integrationen` satırının hemen üstüne ekle:

```php
$router->any('/{locale}/admin/zugang', $admin_(static fn (array $p) => (new AccessAdminController($p['locale']))->index()));
```

`use` satırı ekle — dosyanın üst kısmında diğer `use Atelier\Controllers\...` satırlarının yanına:

```php
use Atelier\Controllers\AccessAdminController;
```

- [ ] **Step 2: Template'i oluştur — `php/templates/admin/access.php`**

```php
<?php
/**
 * Admin parolasını değiştir.
 *
 * @var string $locale
 * @var string $csrf
 * @var string $error  '' = hata yok; 'current' | 'short' | 'mismatch' | 'hash'
 * @var bool $hasHash  DB'de parola hash'i var mı — bilgi mesajı için
 */

use function Atelier\e;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';

$errors = [
    'current'  => ['de' => 'Aktuelles Passwort stimmt nicht.', 'tr' => 'Mevcut parola doğru değil.'],
    'short'    => ['de' => 'Neues Passwort ist zu kurz (mindestens 8 Zeichen).', 'tr' => 'Yeni parola çok kısa (en az 8 karakter).'],
    'mismatch' => ['de' => 'Die beiden neuen Passwörter stimmen nicht überein.', 'tr' => 'İki yeni parola aynı değil.'],
    'hash'     => ['de' => 'Passwort konnte nicht gespeichert werden.', 'tr' => 'Parola kaydedilemedi.'],
];
$errorMessage = $error !== '' ? ($errors[$error][$locale] ?? '') : '';
?>
<div class="max-w-md space-y-8">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Zugang' : 'Erişim' ?></h2>
    <p class="mt-2 text-sm leading-relaxed text-muted">
      <?php if ($hasHash) : ?>
        <?= $de
          ? 'Das Adminpasswort steht in der Datenbank. Zum Ändern hier ein neues setzen.'
          : 'Admin parolası veri tabanında tutuluyor. Değiştirmek için burada yeni bir tane belirle.' ?>
      <?php else : ?>
        <?= $de
          ? 'Noch nutzt der Adminbereich das Passwort aus config.php. Setzt hier eines, greift ab sofort die Datenbank.'
          : 'Panel şu an config.php\'deki parolayı kullanıyor. Burada bir tane belirlediğinde, bundan sonra veri tabanı geçerli olur.' ?>
      <?php endif; ?>
    </p>
  </div>

  <?php if ($errorMessage !== '') : ?>
    <p class="border border-red-700/40 bg-red-50 px-4 py-3 text-sm text-red-700">
      <?= e($errorMessage) ?>
    </p>
  <?php endif; ?>

  <form method="post" class="space-y-6" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div>
      <label class="<?= $label ?>" for="current"><?= $de ? 'Aktuelles Passwort' : 'Mevcut parola' ?></label>
      <input id="current" type="password" name="current" required autocomplete="current-password"
             class="<?= $input ?> mt-2">
    </div>

    <div>
      <label class="<?= $label ?>" for="new"><?= $de ? 'Neues Passwort' : 'Yeni parola' ?></label>
      <input id="new" type="password" name="new" required minlength="8" autocomplete="new-password"
             class="<?= $input ?> mt-2">
      <p class="mt-2 text-[0.72rem] text-muted">
        <?= $de ? 'Mindestens 8, zwölf oder mehr Zeichen sind besser.' : 'En az 8 karakter — on iki ve üzeri daha iyi.' ?>
      </p>
    </div>

    <div>
      <label class="<?= $label ?>" for="confirm"><?= $de ? 'Neues Passwort (Wiederholung)' : 'Yeni parola (tekrar)' ?></label>
      <input id="confirm" type="password" name="confirm" required minlength="8" autocomplete="new-password"
             class="<?= $input ?> mt-2">
    </div>

    <button type="submit"
            class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
      <?= $de ? 'Passwort ändern' : 'Parolayı değiştir' ?>
    </button>
  </form>
</div>
```

- [ ] **Step 3: Sözdizim kontrolleri**

Run:
```bash
php -l php/public/index.php
php -l php/templates/admin/access.php
```
İkisi de `No syntax errors detected`.

- [ ] **Step 4: Manuel smoke test (yerel/staging)**

1. Panele config parolasıyla gir → sidebar Sistem grubunda "Erişim/Zugang" var
2. `/admin/zugang` aç → form görünüyor, "config.php kullanıyor" bilgi mesajı
3. Yanlış mevcut parola → kırmızı "Mevcut parola doğru değil" hatası
4. Doğru mevcut + kısa yeni (7 karakter) → "en az 8" hatası
5. Doğru mevcut + iki farklı yeni → "aynı değil" hatası
6. Doğru mevcut + 12+ karakter × 2 → başarı, sağ alt "Kaydedildi." toast (?gespeichert=1 mantığı zaten çalışıyor)
7. Sayfa yenile → bilgi mesajı "veri tabanında tutuluyor" olarak değişmiş olmalı
8. Çıkış → yeni parolayla giriş çalışıyor
9. Eski (config) parolayla giriş → çalışmıyor
10. `SELECT data FROM integrations WHERE id = 1;` → JSON içinde `admin.passwordHash` bir `$2y$...` string
11. Overview'da parola uyarı bandı kaybolmuş olmalı (Admin::passwordWarning boş döner)

- [ ] **Step 5: Commit**

```bash
git add php/public/index.php php/templates/admin/access.php
git commit -m "admin: /zugang route + password-change form

New template shows a small three-field form (current, new, new-repeat)
with inline errors and an autocomplete-friendly structure. Router hooks
it up. Once the operator submits a new password, Integrations::save
stores the hash and Admin::verify uses it exclusively."
```

---

## Task 7: config.example.php — bootstrap açıklaması

**Files:**
- Modify: `php/config.example.php`

- [ ] **Step 1: `admin_key` yorumunu güncelle**

Mevcut yorum bloğu (satır ~31-42):

```php
    /*
     * Passwort für /admin.
     *
     * Besser als Klartext ist ein Hash – dann steht das Passwort nirgends,
     * auch nicht in einer Sicherung der Datei. Erzeugen mit:
     *
     *   php -r "echo password_hash('DAS-PASSWORT', PASSWORD_DEFAULT), PHP_EOL;"
     *
     * und das Ergebnis ($2y$…) hier eintragen. Klartext funktioniert weiter,
     * damit ein bestehender Zugang nicht bricht.
     */
    'admin_key' => 'bitte-aendern',
```

Değiştir:

```php
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
```

- [ ] **Step 2: Commit**

```bash
git add php/config.example.php
git commit -m "config.example: clarify admin_key is bootstrap-only

Points new operators to the Zugang / Erişim tab as the durable place
for the admin password. Config value stays as a first-run fallback."
```

---

## Manuel doğrulama tam listesi (deploy sonrası)

- [ ] Fresh gibi bir yerel ortamda: `integrations` tablosunda kayıt yok, config parolasıyla giriş çalışıyor
- [ ] Sistem grubunda "Zugang/Erişim" sekmesi var
- [ ] Form: mevcut parola yanlış → hata; doğru + kısa yeni → hata; doğru + eşleşmeyen → hata; doğru + geçerli çift → başarı
- [ ] Başarıdan sonra çıkış + yeni parolayla giriş çalışıyor
- [ ] Eski (config) parola artık kabul edilmiyor
- [ ] `SELECT data FROM integrations WHERE id = 1;` → `admin.passwordHash` bir `$2y$...` hash
- [ ] Overview'da parola uyarı bandı kaybolmuş (DB hash var artık)
- [ ] TR + DE ikisinde de tüm form etiketleri + hata mesajları doğru dilde
- [ ] `Integrations::save` sonrası mevcut Integrations sayfası hâlâ çalışıyor (regresyon yok)

---

## Deploy

Şema değişikliği yok. Sadece `git push` + normal PHP deploy.

Geri alma: önceki commit'e dön. DB'de `admin.passwordHash` alanı JSON içinde kalır ama eski `Admin::login` onu okumaz — config'e düşer. Kullanıcı config'i biliyorsa girebilir.
