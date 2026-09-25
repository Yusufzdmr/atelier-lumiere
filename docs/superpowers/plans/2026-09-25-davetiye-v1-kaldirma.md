# Davetiye v1'in kaldırılması Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** v1'in davetiye sihirbazı/gösterimi/yönetimi/ödemesini tamamen
kaldırmak, sadece v2'yi bırakmak — ama `Invitations::slug()` ve
`Themes.php` gibi davetiye dışına sızmış paylaşılan altyapıyı bozmadan.

**Architecture:** Önce v1'in tüketici tarafı (rota, controller, şablon,
JS) tek parça olarak silinir — bu, `Invitations.php`'nin geri kalan yirmi
metodunu güvenle ölü koda çevirir. Sonra `Invitations.php` sadece
`slug()`'a indirilir. Admin'deki "Einladungen" ekranı ve genel bakış
sayacı v2-only olacak şekilde yeniden yazılır. Son olarak admin
etiketleri ve site navigasyonu güncellenir.

**Tech Stack:** PHP 8, MySQL (JSON doküman deseni), Tailwind CSS v4 (bu
plan yeni CSS sınıfı eklemiyor, derleme gerekmiyor).

**Spec:** `docs/superpowers/specs/2026-09-25-davetiye-v1-kaldirma-design.md`

## Global Constraints

- URL şeması değişmiyor — `/v2/einladung`, `/v2/designs` aynen kalıyor
  (spec §Kapsam dışı, 10 gerçek müşterinin linkleri bu adresler).
- v2'ye ödeme/kupon eklenmiyor — bu planın işi değil.
- DB tabloları DROP edilmiyor, sadece kod tarafında kullanılmayan hale
  geliyor.
- `Themes.php` sınıfına (sabitler + `all/find/save/complete`)
  dokunulmuyor — `DesignAdminController::ausThema()` hâlâ buna bağlı.
- Her görev sonunda `php -l` ile sözdizimi kontrolü ve mümkünse
  `php bin/test.php` ile tam suite çalıştırılıyor.

---

### Task 1: v1'in tüketici tarafını tamamen sil

**Files:**
- Delete: `src/Controllers/InviteController.php`
- Delete: `src/Guests.php`
- Delete: `templates/pages/invite-wizard.php`
- Delete: `templates/pages/invite-manage.php`
- Delete: `templates/pages/invitation.php`
- Delete: `templates/pages/designs.php`
- Delete: `public/assets/invite.js`
- Delete: `public/assets/invite-manage.js`
- Modify: `public/index.php`

**Interfaces:**
- Bu görev sadece siliyor. `Invitations::` metotlarının çoğu bu görevden
  sonra çağrılmıyor olacak (Task 2 onları siler) — bu görevin kendisi
  `Invitations.php`'ye dokunmuyor, sadece onun tek tüketicisini kaldırıyor.

- [ ] **Step 1: Dosyaları sil**

```bash
cd php
rm src/Controllers/InviteController.php
rm src/Guests.php
rm templates/pages/invite-wizard.php
rm templates/pages/invite-manage.php
rm templates/pages/invitation.php
rm templates/pages/designs.php
rm public/assets/invite.js
rm public/assets/invite-manage.js
```

- [ ] **Step 2: `public/index.php`'den `use` satırını kaldır**

Şu satırı sil:

```php
use Atelier\Controllers\InviteController;
```

- [ ] **Step 3: `/designs` rotalarını kaldır**

Şu bloğu (v1'in vitrin rotaları + üstündeki yorum):

```php
// Das Schaufenster steht bewusst NICHT unter /einladung/: dort greift das
// Muster {slug}, und ein Paar, das seine Einladung "designs" nennt, haette
// entweder die eigene Karte oder diese Seite unerreichbar gemacht.
$router->get('/{locale}/designs', $page_(static fn (array $p) => (new InviteController())->designs()));
$router->get('/{locale}/designs/{thema}', $page_(static fn (array $p) => (new InviteController())->designPreview($p)));

// Zweite Fassung der Einladung – laeuft neben der ersten, bis verglichen ist.
$router->get('/{locale}/v2/designs', $page_(static fn (array $p) => (new DesignController())->index()));
```

şununla değiştir (sadece v2'nin rotası kalıyor, artık "neben der ersten"
değil, o yüzden o yorum da kalkıyor):

```php
$router->get('/{locale}/v2/designs', $page_(static fn (array $p) => (new DesignController())->index()));
```

- [ ] **Step 4: `/einladung/*` ve `/api/kupon` rotalarını kaldır**

Şu bloğu (v1'in sihirbaz/ödeme/yönetim/gösterim rotaları + kupon
kontrolü):

```php
$router->any('/{locale}/einladung', $page_(static fn (array $p) => (new InviteController())->wizard()));
$router->get('/{locale}/einladung/{slug}/zahlung', $page_(static fn (array $p) => (new InviteController())->payment($p)));
$router->any('/{locale}/einladung/{slug}/verwalten', $page_(static fn (array $p) => (new InviteController())->manage($p)));
$router->any('/{locale}/einladung/{slug}', $page_(static fn (array $p) => (new InviteController())->show($p)));
// Persoenlich adressierte Fassung. Steht bewusst NACH "zahlung" und
// "verwalten": das Muster wuerde sie sonst schlucken.
$router->any('/{locale}/einladung/{slug}/{gast}', $page_(static fn (array $p) => (new InviteController())->show($p)));
$router->post('/api/kupon', static fn (array $p) => (new InviteController())->checkCoupon());

$router->any('/{locale}/galerie', $page_(static fn (array $p) => (new GalleryController())->index()));
```

şununla değiştir (sadece galerie rotası kalıyor):

```php
$router->any('/{locale}/galerie', $page_(static fn (array $p) => (new GalleryController())->index()));
```

- [ ] **Step 5: Sözdizimini doğrula**

Çalıştır: `cd php && php -l public/index.php`

Beklenen: `No syntax errors detected`

- [ ] **Step 6: Kalıntı referans kalmadığını doğrula**

Çalıştır (repo kökünden):
`grep -rn "InviteController\|new Guests\|Guests::" php/src php/templates php/public`

Beklenen: boş çıktı (`src/Guests.php` ve `InviteController.php` zaten
silindiği için kendi içlerindeki eşleşmeler de gitmiş olmalı).

Not: bu adımda `Invitations.php` içindeki `Guests::`/`InviteController`
referansları henüz durabilir — o dosya Task 2'de değişiyor. Eğer bu grep
`src/Invitations.php` içinde bir eşleşme bulursa (`Guests::` kullanımı),
bu beklenen bir ara durumdur, endişelenme — Task 2 onu temizliyor.

- [ ] **Step 7: Commit**

```bash
git add -A php/src php/templates php/public
git commit -m "Davetiye v1: sihirbaz, gösterim, yönetim, ödeme kaldırıldı

InviteController.php, Guests.php, v1 şablonları ve JS'i silindi.
/einladung/*, /designs*, /api/kupon rotaları kaldırıldı. v2
(/v2/einladung, /v2/designs) dokunulmadı.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 2: `Invitations.php`'i sadece `slug()`'a indir

**Files:**
- Modify: `src/Invitations.php`
- Test: `tests/invitations-slug.php` (yeni)

**Interfaces:**
- Consumes: yok (Task 1 tek tüketiciyi kaldırdı)
- Produces: `Invitations::slug(string $value): string` — `Customers.php`,
  `Guests.php` (Task 1'de silindi, artık tüketici değil), `Lists.php`,
  `OgImage.php`, `ListAdminController.php` bunu kullanmaya devam ediyor,
  imza değişmiyor.

- [ ] **Step 1: Başarısız testi yaz**

`tests/invitations-slug.php` dosyasını oluştur:

```php
<?php

declare(strict_types=1);

use Atelier\Invitations;

/*
 * Invitations::slug() davetiyeyle ilgisiz dört yerde de kullanılıyor
 * (müşteri kodu, misafir linki, admin liste sıralaması, OG görsel) —
 * bu yüzden Invitations.php küçülürken davranışı değişmemeli.
 */

assert_same('ayse-mehmet', Invitations::slug('Ayşe & Mehmet'), 'slug: Türkçe karakter ve boşluk');
assert_same('', Invitations::slug('   '), 'slug: sadece boşluk boş döner');
assert_same('test-123', Invitations::slug('  Test 123!!  '), 'slug: baştaki/sondaki boşluk ve özel karakter kırpılır');
assert_same('grossmutter', Invitations::slug('Großmutter'), 'slug: ß → ss');
```

- [ ] **Step 2: Testin geçtiğini doğrula (kırpma öncesi, mevcut davranışla)**

Çalıştır: `cd php && php bin/test.php invitations-slug`

Beklenen: `4 Prüfungen bestanden.` — bu test şu an `Invitations.php`
henüz kırpılmadan da geçmeli (mevcut `slug()` davranışını doğruluyoruz,
değiştirmiyoruz). Geçmezse, kırpmaya başlamadan önce durup asıl
`slug()` davranışını (satır 73-80) tekrar oku.

- [ ] **Step 3: `Invitations.php`'i kırp**

`src/Invitations.php`'in tamamını şununla değiştir:

```php
<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Adresstauglicher Name aus einem beliebigen Text.
 *
 * War einmal Teil der ersten Einladungsfassung (siehe Git-Historie,
 * 2026-09-25 vor diesem Commit) – inzwischen an Stellen im Kundenbereich
 * verwendet, die mit Einladungen nichts zu tun haben: Kundencode,
 * Gastlinks, Listensortierung, OG-Bild. Deshalb bleibt die Klasse, auch
 * ohne die Einladung, die ihr den Namen gab.
 */
final class Invitations
{
    /** Adresstauglicher Name: Kleinbuchstaben, Ziffern, Bindestrich. */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'â' => 'a'];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
```

- [ ] **Step 4: Testin hâlâ geçtiğini doğrula**

Çalıştır: `cd php && php bin/test.php invitations-slug`

Beklenen: `4 Prüfungen bestanden.`

- [ ] **Step 5: Sözdizimini doğrula ve kalıntı referans ara**

Çalıştır: `cd php && php -l src/Invitations.php`

Beklenen: `No syntax errors detected`

Çalıştır (repo kökünden):
`grep -rn "Invitations::find\|Invitations::all\|Invitations::create\|Invitations::update\|Invitations::delete\|Invitations::checkCoupon\|Invitations::redeemCoupon\|Invitations::theme\|Invitations::manageUrl\|Invitations::rsvps\|Invitations::drafts\|Invitations::kindLabel\|Invitations::occasionLine\|Invitations::EVENT_TYPES" php/src php/templates`

Beklenen: boş çıktı.

- [ ] **Step 6: Tüm test suite'i çalıştır**

Çalıştır: `cd php && php bin/test.php`

Beklenen: tüm testler geçiyor, önceki toplam + 4 yeni assertion.

- [ ] **Step 7: Commit**

```bash
git add php/src/Invitations.php php/tests/invitations-slug.php
git commit -m "Invitations.php: sadece slug() kalıyor

Geri kalan yirmi metot (davetiye CRUD, RSVP, taslak, kupon) v1 ile
birlikte gitti. slug() dört ilgisiz yerde (müşteri kodu, misafir
linki, admin liste sıralaması, OG görsel) hâlâ kullanılıyor.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 3: Admin'in "Einladungen" ekranı v2-only oluyor

**Files:**
- Modify: `src/InvitationsV2.php` (yeni `drafts()` metodu)
- Modify: `src/Controllers/InviteAdminController.php`
- Modify: `templates/admin/invitations.php`

**Interfaces:**
- Produces: `InvitationsV2::drafts(): array` — sadece bu görevin kendi
  içinde, `InviteAdminController::index()` tarafından kullanılıyor.
- Consumes: `InvitationsV2::all()` (zaten var), `InvitationsV2::setStatus()`
  (zaten var, dokunulmuyor).

- [ ] **Step 1: `InvitationsV2::drafts()`'ı ekle**

`src/InvitationsV2.php` içinde `deleteDraft()` metodunun hemen altına ekle:

```php

    /**
     * Yarım kalan taslakların listesi — panelin "Liegengebliebene
     * Entwürfe" bölümü için. `invite_drafts` tablosu v1 ile paylaşılıyordu
     * (bkz. saveDraft()'un yorumu); v1 gittiği için burada sadece
     * fassung=2 olanlar filtreleniyor — eski bir v1 taslağı varsa (artık
     * hiçbir rotanın açamayacağı), listede görünmesin.
     *
     * @return list<array<string,mixed>>
     */
    public static function drafts(): array
    {
        $out = [];
        foreach (Db::jsonList('SELECT data FROM invite_drafts ORDER BY updated_at DESC LIMIT 200') as $draft) {
            if ((int) ($draft['fassung'] ?? 1) === 2) {
                $out[] = $draft;
            }
        }
        return $out;
    }
```

- [ ] **Step 2: Sözdizimini doğrula**

Çalıştır: `cd php && php -l src/InvitationsV2.php`

Beklenen: `No syntax errors detected`

- [ ] **Step 3: `InviteAdminController.php`'i v2-only yeniden yaz**

`src/Controllers/InviteAdminController.php`'in tamamını şununla değiştir:

```php
<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\I18n;
use Atelier\InvitationsV2;
use Atelier\Security;
use Atelier\View;

/**
 * Der Einladungsreiter: was mit dem neuen Assistenten erstellt wurde.
 *
 * Bis 2026-09-25 stand hier zusaetzlich die erste Fassung (Invitations.php)
 * mit Zusagen, persoenlichen Gastlinks und Gutscheinen. Sie ist raus — der
 * neue Assistent hat noch keine Zusagen und keine Gutscheine (Phase D),
 * deshalb ist diese Seite bis dahin kürzer als sie war.
 */
final class InviteAdminController
{
    private const TAB = '/einladungen';

    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            match (Security::clean($_POST['was'] ?? '', 20)) {
                /*
                 * Eine Einladung an- oder abschalten.
                 *
                 * Kein Loeschen daneben: eine verschickte Adresse loescht man
                 * nicht, man schaltet sie ab. Wer sie loescht, gibt sie zur
                 * Wiederverwendung frei - und der naechste Gast, der den alten
                 * Link oeffnet, landet auf einer fremden Hochzeit.
                 */
                'v2-zustand'       => InvitationsV2::setStatus(
                    Security::clean($_POST['slug'] ?? '', 96),
                    Security::clean($_POST['zustand'] ?? '', 16)
                ),
                'entwurf-loeschen' => InvitationsV2::deleteDraft(Security::clean($_POST['token'] ?? '', 64)),
                default            => null,
            };

            Admin::back($this->locale, self::TAB);
        }

        View::page('admin/invitations', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . self::TAB),
            'current' => self::TAB,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'v2'      => InvitationsV2::all(),
            'drafts'  => InvitationsV2::drafts(),
        ]);
    }
}
```

- [ ] **Step 4: `templates/admin/invitations.php`'i v2-only yeniden yaz**

Dosyanın tamamını şununla değiştir:

```php
<?php
/**
 * Erstellte Einladungen und die liegengebliebenen Entwürfe.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $v2
 * @var list<array<string,mixed>> $drafts
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
$hidden = '<input type="hidden" name="csrf" value="' . e($csrf) . '">';
?>
<div class="space-y-10">

  <?php /*
     Abschalten und nicht loeschen. Eine verschickte Adresse loescht man
     nicht - wer sie loescht, gibt sie zur Wiederverwendung frei, und der
     naechste Gast, der den alten Link oeffnet, landet auf einer fremden
     Hochzeit. Ein Entwurf antwortet dem Gast wie eine Adresse, die es nicht
     gibt: wer den Link hat, soll nicht denken, er muesse es spaeter noch
     einmal versuchen.
  */ ?>
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Einladungen' : 'Davetiyeler' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Aus dem Assistenten. Abgeschaltet heißt: der Link antwortet wie eine Adresse, die es nicht gibt.'
        : 'Sihirbazdan çıkanlar. Kapalı demek: bağlantı, olmayan bir adres gibi cevap verir.' ?>
    </p>

    <?php if ($v2 === []) : ?>
      <p class="mt-5 border border-sand-deep p-5 text-sm text-muted">
        <?= $de ? 'Noch keine Einladung erstellt.' : 'Henüz davetiye oluşturulmadı.' ?>
      </p>
    <?php else : ?>
      <div class="mt-5 divide-y divide-sand-deep border-y border-sand-deep">
        <?php foreach ($v2 as $ein) : ?>
          <?php
            $slug = (string) ($ein['slug'] ?? '');
            $an   = (string) ($ein['status'] ?? 'published') !== 'draft';
          ?>
          <div class="flex flex-wrap items-center justify-between gap-4 py-3">
            <div class="min-w-0">
              <a class="text-sm text-ink underline decoration-sand-deep underline-offset-4"
                 href="<?= e(I18n::sitePath('/v2/einladung/' . $slug, $locale)) ?>" target="_blank">/<?= e($slug) ?></a>
              <div class="mt-1 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
                <?= e((string) ($ein['design_id'] ?? '')) ?>
                · <?= e(Dates::short((string) ($ein['created_at'] ?? ''))) ?>
                <?php if (!empty($ein['published_at'])) : ?>
                  · <?= $de ? 'seit' : 'şu tarihten beri' ?> <?= e(Dates::short((string) $ein['published_at'])) ?>
                <?php endif; ?>
              </div>
            </div>
            <form method="post" class="flex items-center gap-3">
              <?= $hidden ?>
              <input type="hidden" name="was" value="v2-zustand">
              <input type="hidden" name="slug" value="<?= e($slug) ?>">
              <input type="hidden" name="zustand" value="<?= $an ? 'draft' : 'published' ?>">
              <span class="text-[0.66rem] uppercase tracking-[0.16em] <?= $an ? 'text-ink' : 'text-muted' ?>">
                <?= $an ? ($de ? 'im Netz' : 'yayında') : ($de ? 'abgeschaltet' : 'kapalı') ?>
              </span>
              <button class="border border-sand-deep px-4 py-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-ink">
                <?= $an ? ($de ? 'abschalten' : 'kapat') : ($de ? 'anschalten' : 'aç') ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- --------------------------------- Entwürfe -------------------------------- -->
  <?php if ($drafts !== []) : ?>
    <section>
      <h3 class="font-display text-lg text-ink"><?= $de ? 'Liegengebliebene Entwürfe' : 'Yarım kalan taslaklar' ?></h3>
      <p class="mt-2 max-w-3xl text-[0.8rem] leading-relaxed text-muted">
        <?= $de
          ? 'Jemand hat angefangen und nicht abgeschickt. Nach 120 Tagen räumt sich das von selbst.'
          : 'Biri başlamış ama göndermemiş. 120 gün sonra kendiliğinden temizlenir.' ?>
      </p>

      <div class="mt-5 space-y-2">
        <?php foreach ($drafts as $draft) : ?>
          <div class="flex flex-wrap items-center justify-between gap-4 border border-sand-deep p-4">
            <div class="min-w-0">
              <div class="text-[0.88rem] text-ink"><?= e((string) ($draft['label'] ?? '—')) ?></div>
              <div class="mt-1 break-all text-[0.72rem] text-muted">
                <?= e(Dates::short((string) ($draft['updatedAt'] ?? ''))) ?> ·
                <a href="<?= e(I18n::sitePath('/v2/einladung', $locale)) ?>?taslak=<?= e((string) ($draft['token'] ?? '')) ?>"
                   class="text-gold underline-offset-4 hover:underline">
                  <?= $de ? 'Entwurf öffnen' : 'Taslağı aç' ?>
                </a>
              </div>
            </div>
            <form method="post">
              <?= $hidden ?>
              <input type="hidden" name="was" value="entwurf-loeschen">
              <input type="hidden" name="token" value="<?= e((string) ($draft['token'] ?? '')) ?>">
              <button data-confirm="<?= $de ? 'Entwurf löschen?' : 'Taslak silinsin mi?' ?>"
                      class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                <?= $de ? 'Löschen' : 'Sil' ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
```

- [ ] **Step 5: Sözdizimini doğrula**

Çalıştır:
`cd php && php -l src/Controllers/InviteAdminController.php && php -l templates/admin/invitations.php`

Beklenen: ikisi için de `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add php/src/InvitationsV2.php php/src/Controllers/InviteAdminController.php php/templates/admin/invitations.php
git commit -m "Admin 'Einladungen' ekranı v2-only

InvitationsV2::drafts() eklendi (fassung=2 filtreli — v1'in eski
taslakları artık açılamayacağı için listede görünmüyor).
InviteAdminController + şablon v1'in rsvp/misafir/kupon zenginleştirmesi
olmadan, sadece v2 listesi ve taslaklarıyla yeniden yazıldı.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 4: Müşteri kartındaki kupon-davetiye eşleştirmesi

**Files:**
- Modify: `src/Controllers/CustomerAdminController.php`
- Modify: `templates/admin/customer.php`

**Interfaces:**
- Consumes: yok (bu görev sadece kaldırıyor/basitleştiriyor)
- Produces: `templates/admin/customer.php`'nin `$usedFor` değişkeni
  tamamen kalkıyor — "Wieder freigeben" düğmesi artık doğrudan
  `$coupon['usedFor']` (zaten şablonda var olan `$coupon` değişkeninden)
  okuyor.

- [ ] **Step 1: `CustomerAdminController.php`'den `usedFor` bloğunu kaldır**

`show()` metodundaki şu bloğu:

```php
        // Nur die Einladungen, die mit dem Gutschein dieses Kunden entstanden.
        $usedFor = [];
        foreach ($customer['coupon']['usedFor'] as $use) {
            $slug = (string) ($use['slug'] ?? '');
            $invitation = Invitations::find($slug);
            $usedFor[] = [
                'slug'    => $slug,
                'at'      => (string) ($use['at'] ?? ''),
                'couple'  => $invitation === null
                    ? ''
                    : trim((string) ($invitation['bride'] ?? '') . ' & ' . (string) ($invitation['groom'] ?? ''), ' &'),
                'rsvps'   => count(Invitations::rsvps($slug)),
                'exists'  => $invitation !== null,
            ];
        }

        $this->render('admin/customer', [
            'customer'    => $customer,
            'gallery'     => $gallery,
            'selection'   => $selection,
            'preferences' => $preferences,
            'styles'      => Content::list('editingStyles'),
            'questions'   => Content::list('galleryQuestions'),
            'photos'      => $gallery === null ? [] : Galleries::photos($gallery),
            'usedFor'     => $usedFor,
        ], '/kunden/' . $code);
```

şununla değiştir:

```php
        $this->render('admin/customer', [
            'customer'    => $customer,
            'gallery'     => $gallery,
            'selection'   => $selection,
            'preferences' => $preferences,
            'styles'      => Content::list('editingStyles'),
            'questions'   => Content::list('galleryQuestions'),
            'photos'      => $gallery === null ? [] : Galleries::photos($gallery),
        ], '/kunden/' . $code);
```

- [ ] **Step 2: Artık kullanılmayan `use Atelier\Invitations;` satırını kaldır**

Dosyanın başındaki `use` bloğunda şu satırı sil:

```php
use Atelier\Invitations;
```

Kontrol: `grep -n "Invitations::" src/Controllers/CustomerAdminController.php`
boş dönmeli.

- [ ] **Step 3: `templates/admin/customer.php`'deki gösterim bloğunu kaldır, düğmeyi ham veriye bağla**

Şu bloğu:

```php
        <?php if ($usedFor !== []) : ?>
          <div class="mt-5 border-t border-sand-deep pt-4">
            <div class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Eingelöst' : 'Kullanıldı' ?></div>
            <ul class="mt-2 space-y-1.5">
              <?php foreach ($usedFor as $use) : ?>
                <li class="text-[0.78rem]">
                  <a href="<?= e(I18n::sitePath('/einladung/' . $use['slug'], $locale)) ?>" target="_blank" rel="noopener"
                     class="text-gold underline-offset-4 hover:underline">/<?= e($use['slug']) ?></a>
                  <span class="ml-2 text-muted">
                    <?= e(Dates::short($use['at'])) ?><?php
                      if ($use['couple'] !== '') { echo ' · ' . e($use['couple']); }
                      if ($use['rsvps'] > 0) { echo ' · ' . $use['rsvps'] . ' RSVP'; }
                      if (!$use['exists']) { echo ' · ' . ($de ? 'gelöscht' : 'silinmiş'); }
                    ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="mt-6 flex flex-wrap gap-3">
          <button name="was" value="gutschein"
                  class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= $de ? 'Speichern' : 'Kaydet' ?>
          </button>
          <button name="was" value="gutschein-neu"
                  data-confirm="<?= $de ? 'Neuen Code erzeugen? Der alte gilt dann nicht mehr.' : 'Yeni kod üretilsin mi? Eski kod geçersiz olur.' ?>"
                  class="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-gold">
            <?= $de ? 'Neuer Code' : 'Yeni kod' ?>
          </button>
          <?php if ($usedFor !== []) : ?>
            <button name="was" value="gutschein-frei"
                    data-confirm="<?= $de ? 'Gutschein wieder freigeben?' : 'Kupon yeniden açılsın mı?' ?>"
                    class="px-3 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:text-gold hover:underline">
              <?= $de ? 'Wieder freigeben' : 'Yeniden aç' ?>
            </button>
          <?php endif; ?>
        </div>
```

şununla değiştir (gösterim listesi gider — üretici olmadığı için hep boş
kalacaktı; "Wieder freigeben" düğmesi kalıyor ama artık zenginleştirilmiş
`$usedFor` yerine doğrudan `$coupon['usedFor']`'a bakıyor, o veri
`Customers::saveCoupon()`/`redeemCoupon()` tarafından hâlâ dolduruluyor
olabilir):

```php
        <div class="mt-6 flex flex-wrap gap-3">
          <button name="was" value="gutschein"
                  class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= $de ? 'Speichern' : 'Kaydet' ?>
          </button>
          <button name="was" value="gutschein-neu"
                  data-confirm="<?= $de ? 'Neuen Code erzeugen? Der alte gilt dann nicht mehr.' : 'Yeni kod üretilsin mi? Eski kod geçersiz olur.' ?>"
                  class="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-gold">
            <?= $de ? 'Neuer Code' : 'Yeni kod' ?>
          </button>
          <?php if ($coupon['usedFor'] !== []) : ?>
            <button name="was" value="gutschein-frei"
                    data-confirm="<?= $de ? 'Gutschein wieder freigeben?' : 'Kupon yeniden açılsın mı?' ?>"
                    class="px-3 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:text-gold hover:underline">
              <?= $de ? 'Wieder freigeben' : 'Yeniden aç' ?>
            </button>
          <?php endif; ?>
        </div>
```

- [ ] **Step 4: Docblock'taki `$usedFor` satırını kaldır**

Dosyanın başındaki `@var list<array{slug:string,at:string,couple:string,rsvps:int,exists:bool}> $usedFor`
satırını sil.

- [ ] **Step 5: Sözdizimini doğrula**

Çalıştır:
`cd php && php -l src/Controllers/CustomerAdminController.php && php -l templates/admin/customer.php`

Beklenen: ikisi için de `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add php/src/Controllers/CustomerAdminController.php php/templates/admin/customer.php
git commit -m "Müşteri kartı: kupon-davetiye eşleştirmesi kaldırıldı

Invitations::find()/rsvps() gittiği için üretici kalmamıştı, zaten
hep boş olacaktı. 'Wieder freigeben' düğmesi kalıyor, artık ham
coupon.usedFor'a bakıyor.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 5: Genel bakıştaki davetiye sayacı düzeltmesi

**Files:**
- Modify: `src/Controllers/AdminController.php`
- Modify: `templates/admin/overview.php`

**Interfaces:**
- Consumes: `InvitationsV2::all(): array` (zaten var)
- Produces: `templates/admin/overview.php`'ye artık `$invitations`
  (liste) değil `$invitationCount` (int) geçiliyor.

- [ ] **Step 1: `AdminController.php`'ye `InvitationsV2` importu ekle**

`use Atelier\Integrations;` satırının hemen altına ekle:

```php
use Atelier\InvitationsV2;
```

- [ ] **Step 2: `overview()`'daki `$invitations` kaynağını değiştir**

Şu satırı:

```php
        $invitations = Db::jsonList('SELECT data FROM invitations ORDER BY created_at DESC');
```

şununla değiştir:

```php
        // v1'in invitations tablosu 2026-09-25'te kaldırıldı — sayaç artık
        // v2'yi sayıyor. pendingWork()'e boş liste geçiyoruz: v2'nin henüz
        // ödeme kavramı yok (Phase D), "ödenmemiş davetiye" uyarısı bu
        // yüzden anlamsız — boş liste onu sessizce hiç üretmiyor.
        $invitationCount = count(InvitationsV2::all());
```

- [ ] **Step 3: `pendingWork()` çağrısındaki parametreyi güncelle**

Şu çağrıyı:

```php
        $pending = Admin::pendingWork(
            $this->locale,
            $leads,
            $selections,
            $invitations,
            $customers,
            $galleries
        );
```

şununla değiştir:

```php
        $pending = Admin::pendingWork(
            $this->locale,
            $leads,
            $selections,
            [],
            $customers,
            $galleries
        );
```

- [ ] **Step 4: `render()` çağrısındaki değişkeni güncelle**

Şu satırı:

```php
            'invitations' => $invitations,
```

şununla değiştir:

```php
            'invitationCount' => $invitationCount,
```

- [ ] **Step 5: `templates/admin/overview.php`'yi güncelle**

Docblock'taki şu satırı:

```php
 * @var list<array<string,mixed>> $invitations
```

şununla değiştir:

```php
 * @var int $invitationCount
```

Ve stat kutusundaki şu satırı:

```php
    [$de ? 'Einladungen' : 'Davetiyeler', count($invitations), $p('/admin/einladungen')],
```

şununla değiştir:

```php
    [$de ? 'Einladungen' : 'Davetiyeler', $invitationCount, $p('/admin/einladungen')],
```

- [ ] **Step 6: Sözdizimini doğrula**

Çalıştır:
`cd php && php -l src/Controllers/AdminController.php && php -l templates/admin/overview.php`

Beklenen: ikisi için de `No syntax errors detected`

- [ ] **Step 7: Commit**

```bash
git add php/src/Controllers/AdminController.php php/templates/admin/overview.php
git commit -m "Genel bakış: 'Einladungen' sayacı artık v2'yi sayıyor

v1'in tablosu kalıcı olarak boşaldığı için sayaç yanlış 0
gösterecekti — 10 gerçek v2 davetiyesi varken. pendingWork()'e giden
ödenmemiş-davetiye kontrolü boş listeyle besleniyor (v2'de henüz
ödeme yok, Phase D).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 6: Admin sekme etiketleri ve site navigasyonu

**Files:**
- Modify: `src/Admin.php`
- Modify: `templates/partials/header.php`
- Modify: `templates/partials/footer.php`

**Interfaces:** yok — sadece etiket/link metni değişiyor, hiçbir
fonksiyon imzası değişmiyor.

- [ ] **Step 1: `/themen` sekmesinin etiketini değiştir**

`src/Admin.php`'de şu satırı:

```php
        ['href' => '/themen', 'group' => 'einladung', 'de' => 'Designs', 'tr' => 'Tasarımlar'],
```

şununla değiştir:

```php
        ['href' => '/themen', 'group' => 'einladung', 'de' => 'Farbvorlagen', 'tr' => 'Renk Şablonları'],
```

- [ ] **Step 2: `/designs` sekmesinin etiketinden "(v2)" kalksın**

Şu satırı:

```php
        ['href' => '/designs', 'group' => 'einladung', 'de' => 'Designs (v2)', 'tr' => 'Tasarımlar (v2)'],
```

şununla değiştir:

```php
        ['href' => '/designs', 'group' => 'einladung', 'de' => 'Designs', 'tr' => 'Tasarımlar'],
```

- [ ] **Step 3: `header.php`'deki çift davetiye linkini teke indir**

`templates/partials/header.php`'de şu bloğu:

```php
// Abgesetzt und in Gold: das ist das eigene Produkt, nicht eine Seite mehr.
$extra = [
    [$p('/einladung'), I18n::t('nav.invitation')],
    // Zweite Fassung, zum Vergleich daneben. Eine der beiden faellt weg,
    // sobald entschieden ist.
    [$p('/v2/designs'), I18n::t('nav.invitation2')],
];
```

şununla değiştir:

```php
// Abgesetzt und in Gold: das ist das eigene Produkt, nicht eine Seite mehr.
$extra = [
    [$p('/v2/designs'), I18n::t('nav.invitation')],
];
```

- [ ] **Step 4: `footer.php`'deki üç davetiye linkini teke indir**

`templates/partials/footer.php`'de şu üç satırı:

```php
    [$p('/einladung'), I18n::t('nav.invitation')],
    [$p('/v2/designs'), I18n::t('nav.invitation2')],
    [$p('/designs'), 'Designs'],
```

şu tek satırla değiştir:

```php
    [$p('/v2/designs'), I18n::t('nav.invitation')],
```

- [ ] **Step 5: Sözdizimini doğrula**

Çalıştır:
`cd php && php -l src/Admin.php && php -l templates/partials/header.php && php -l templates/partials/footer.php`

Beklenen: üçü için de `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add php/src/Admin.php php/templates/partials/header.php php/templates/partials/footer.php
git commit -m "Admin etiketleri ve site menüsü: tek davetiye sistemi

'/themen' artık 'Farbvorlagen'/'Renk Şablonları' (ne olduğunu
söylüyor), '/designs'den '(v2)' kalktı. Header/footer'daki çift/üçlü
davetiye linki tek linke indi (/v2/designs).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 7: Uçtan uca doğrulama

**Files:** yok (sadece çalıştırma/gözlem)

**Interfaces:** yok — Task 1-6'nın bir araya geldiğinde çalıştığını
doğruluyor.

- [ ] **Step 1: Tam test suite'i çalıştır**

Çalıştır: `cd php && php bin/test.php`

Beklenen: tüm testler geçiyor, hiç başarısız yok.

- [ ] **Step 2: Dev sunucusunu başlat**

Çalıştır (8080 doluysa başka bir port dene — `netstat -ano | grep ":8091 "`
gibi kontrol et):
`cd php && php -S 127.0.0.1:8091 -t public public/dev-router.php`

- [ ] **Step 3: Eski v1 adreslerinin 404 döndüğünü doğrula**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8091/de/einladung
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8091/de/designs
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8091/de/einladung/herhangibirsey
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://127.0.0.1:8091/api/kupon
```

Beklenen: dördü de `404`.

- [ ] **Step 4: v2'nin çalıştığını doğrula**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8091/de/v2/designs
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8091/de/v2/einladung
```

Beklenen: ikisi de `200`.

- [ ] **Step 5: Site menüsünü tarayıcıda kontrol et**

`http://127.0.0.1:8091/de` adresine git, üst menüde tek bir "Einladung"
linki olduğunu ve `/v2/designs`'a gittiğini doğrula. Sayfayı en alta
kaydır, footer'da da tek link olduğunu doğrula.

- [ ] **Step 6: Admin panelini kontrol et**

`http://127.0.0.1:8091/de/admin` → parola `lokal-nur-zum-testen` (yerel
`config.php`'deki değer) ile gir.
- Sol menüde "Einladung" grubu altında "Farbvorlagen" ve "Designs" (artık
  "(v2)" yok) sekmelerini gör.
- "Einladungen" sekmesine gir, sayfanın hatasız açıldığını ve v2
  davetiyelerini listelediğini doğrula.
- Genel bakışa dön, "Einladungen" sayaç kutusunun `InvitationsV2::all()`
  sayısıyla eşleştiğini doğrula (yerelde muhtemelen 0 veya demo veri
  kadar).
- Bir müşteri kartına gir (`Kunden & Galerien`), "Gutschein" bölümünün
  hatasız render olduğunu doğrula.
- `/de/admin/designs` (Designs) ekranında "eski bir temadan başla"
  (`ausThema`) özelliğinin hâlâ orada olduğunu ve tema listesinin dolu
  geldiğini doğrula — bu, `Themes.php`'ye dokunulmadığının kanıtı.

- [ ] **Step 7: Dev sunucusunu kapat**

```bash
netstat -ano | grep ":8091 " # PID'i bul
# PowerShell'den: Stop-Process -Id <PID> -Force
```

- [ ] **Step 8 (opsiyonel, Yusuf'un onayıyla): Canlıdaki 2 test kaydını sil**

Spec §6'da anlatıldığı gibi — kod değişikliğine bağımlı değil, istenirse
şu SQL ile silinir (üretim veritabanında, salt bu iki test kaydı
hedeflenerek):

```sql
DELETE FROM invitations WHERE slug IN ('test-claude-1787084561', 'test-claude-full-1787084687');
```

Bu adım ayrı bir onay gerektirir (Yusuf'un doğrudan "sil" demesi), planın
geri kalanına bağımlı değildir ve atlanabilir.
