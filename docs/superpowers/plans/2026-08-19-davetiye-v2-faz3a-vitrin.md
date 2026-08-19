# Davetiye v2 — Faz 3A Uygulama Planı: Satış vitrini

> **Ajan işçiler için:** GEREKLİ ALT SKILL: `superpowers:subagent-driven-development`
> (önerilen) ya da `superpowers:executing-plans` ile görev görev uygulayın.

**Amaç:** `/{locale}/v2/designs`'i karşılaştırma sayfasından müşteri vitrinine
çevirmek: yalnızca yayındaki tasarımlar, kategori süzgeci, tam ekran demo ve
çalışan bir "bu tasarımla oluştur" düğmesi.

**Mimari:** Mevcut `DesignController::index()` genişliyor; hangi tasarımın
sihirbaza gidebildiği kararı saf `Design::creatable()` içinde duruyor. Demo
sayfasındaki geliştirici çubuğu `Admin::isLoggedIn()` ile ayrılıyor. Yeni
kontrolör, yeni tablo, yeni bağımlılık yok.

**Teknoloji:** PHP 8.3, Composer yok, `php bin/test.php`, sunucu tarafı şablonlar,
derlenmiş Tailwind (`public/assets/style.css`).

**Spec:** `docs/superpowers/specs/2026-08-19-davetiye-v2-faz3a-vitrin-design.md`

## Global Constraints

- **Sınıflar panelin/sitenin kendi paletinden.** Yeni bir yardımcı sınıf yazmadan
  önce derlenmiş CSS'te var mı bak — Faz 1'de bu hata dört kez, Faz 2'de bir kez
  çıktı. Tarayıcıda: her stylesheet'in selector'lerini toplayıp
  `CSS.escape(sinif)` ile ara.
- **Eski rotalara dokunulmuyor:** `/{locale}/designs`, `/{locale}/designs/{thema}`,
  `/{locale}/einladung` ve panel. Onlara yazan tek satır olmayacak.
- **Metinler sözlükte**, şablonda sabit dize değil (`I18n::t()`).
- **`Design::all('active')`** — taslak tasarım müşteriye görünmez.
- Her yeni saf fonksiyonun testi `php/tests/` altında ve `php bin/test.php` ile koşar.

## Dosya yapısı

| Dosya | Sorumluluk |
|---|---|
| `php/src/Design.php` (değişir) | `creatable()` — hangi tasarım sihirbaza gidebilir |
| `php/tests/design_showcase.php` (yeni) | `creatable()` ve `all('active')` testleri |
| `php/data/dict.php` (değişir) | `invitation2.title`, `invitation2.lead` — üç dil |
| `php/src/Controllers/DesignController.php` (değişir) | `index()`: süzgeç, aktif süzme, `creatable` haritası |
| `php/templates/pages/designs-v2.php` (yeniden yazılır) | Vitrin |
| `php/templates/pages/design-preview.php` (değişir) | Alt çubuk: müşteri mi, panel mi |

---

## Task 1: `Design::creatable()`

**Files:**
- Create: `php/tests/design_showcase.php`
- Modify: `php/src/Design.php` (`copy()`'nin altına)

**Interfaces:**
- Produces: `Design::creatable(array $designs, array $themeIds): array` — slug =>
  bool haritası. Task 3 ve Task 4 bunu kullanır.

- [ ] **Step 1: Başarısız testi yaz**

```php
<?php
declare(strict_types=1);

use Atelier\Design;

/*
 * Welche Vorlage kann heute in den Assistenten?
 *
 * Der alte Assistent prueft ?design= gegen die Themen-Kennungen und ignoriert
 * still, was er nicht kennt (InviteController.php:74). Ein Knopf, der still
 * nichts tut, ist schlechter als kein Knopf - also entscheidet das hier, mit
 * einem Test, und nicht die Vorlage.
 */

$designs = [
    ['slug' => 'elysee'],
    ['slug' => 'noir'],
    ['slug' => 'elysee-nacht'],
];

$karte = Design::creatable($designs, ['elysee', 'noir', 'sage']);

assert_same(true, $karte['elysee'], 'creatable: mit passendem Thema ja');
assert_same(true, $karte['noir'], 'creatable: mit passendem Thema ja');
assert_same(false, $karte['elysee-nacht'], 'creatable: eine Kopie hat kein Thema');
assert_same(3, count($karte), 'creatable: jede Vorlage kommt vor');

$ohne = Design::creatable($designs, []);
assert_same(false, $ohne['elysee'], 'creatable: ohne Themen kann keine');
assert_same([], Design::creatable([], ['elysee']), 'creatable: ohne Vorlagen leer');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_showcase`
Beklenen: `Call to undefined method Atelier\Design::creatable()`

- [ ] **Step 3: Uygulamayı yaz**

`php/src/Design.php`, `copy()`'nin altına:

```php
    /**
     * Welche Vorlage kann heute in den alten Assistenten?
     *
     * Er prueft ?design= gegen die Themen-Kennungen und ignoriert still, was er
     * nicht kennt. Eine im Panel kopierte Vorlage hat kein Thema desselben
     * Namens - fuer sie gibt es deshalb keinen Knopf, sondern einen Satz. Mit
     * dem eigenen Assistenten (Faz 3B) faellt diese Frage ganz weg, und sie
     * faellt an einer Stelle weg, weil sie nur hier steht.
     *
     * @param list<array<string,mixed>> $designs
     * @param list<string>              $themeIds
     * @return array<string,bool>
     */
    public static function creatable(array $designs, array $themeIds): array
    {
        $karte = [];
        foreach ($designs as $design) {
            $slug = (string) ($design['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $karte[$slug] = in_array($slug, $themeIds, true);
        }
        return $karte;
    }
```

- [ ] **Step 4: Testleri çalıştır ve commit et**

```bash
cd php && php bin/test.php
git add php/tests/design_showcase.php php/src/Design.php
git commit -m "One function decides which design can reach the wizard

The old wizard checks ?design= against theme ids and silently ignores what it
does not know, so a design copied in the panel would give a button that does
nothing. The rule lives here with a test instead of in a template, which is also
why the wizard phase will remove it in one place."
```

---

## Task 2: Sözlük anahtarları

**Files:**
- Modify: `php/data/dict.php` (üç dile ikişer anahtar)

**Interfaces:**
- Produces: `invitation2.title`, `invitation2.lead`. Task 3 bunları `I18n::t()` ile okur.

**Neden sözlük:** panelin "Sayfa metinleri" sekmesi sözlükteki her anahtarı
düzenletiyor (`src/Texts.php` bir üst katman; sözlük dosyası değişmiyor).
Şablona sabit dize yazmak, Ayhan'ın o cümleyi bir daha değiştirememesi demek.

- [ ] **Step 1: Anahtarları ekle**

`php/data/dict.php`, her dilin sayfa metinleri bölümüne (`'nav'` bloğunun
dışına). Önce mevcut yapıya bak: `grep -n "'invitation'" php/data/dict.php`.

`de`:
```php
        'invitation2' => [
            'title' => 'Einladungen, die sich öffnen lassen',
            'lead'  => 'Jede Vorlage bringt ihre eigenen Farben, ihr Kuvert und ihr Siegel mit. Schaut sie in Ruhe an – ändern lässt sich später alles.',
        ],
```

`en`:
```php
        'invitation2' => [
            'title' => 'Invitations that open',
            'lead'  => 'Every template brings its own colours, its envelope and its seal. Take your time looking – everything can be changed later.',
        ],
```

`tr`:
```php
        'invitation2' => [
            'title' => 'Açılan davetiyeler',
            'lead'  => 'Her tasarım kendi renklerini, zarfını ve mührünü getirir. Acele etmeden bakın — sonradan her şey değiştirilebilir.',
        ],
```

- [ ] **Step 2: Üç dilde de dolu mu**

```bash
cd php && php -r '$d = require "data/dict.php";
foreach (["de","en","tr"] as $l) {
    printf("%s: %s\n", $l, $d[$l]["invitation2"]["title"] ?? "FEHLT");
}'
```

Biri `FEHLT` diyorsa devam etme.

- [ ] **Step 3: Commit**

```bash
git add php/data/dict.php
git commit -m "The showcase gets its words from the dictionary

Hard-coding the headline would mean Ayhan can never change it again; every key
in the dictionary is editable from the panel's page-texts tab."
```

---

## Task 3: Vitrin — kontrolör

**Files:**
- Modify: `php/src/Controllers/DesignController.php` (`index()`)

**Interfaces:**
- Consumes: `Design::all('active')`, `Design::creatable()` (Task 1), `Themes::all()`.
- Produces: şablona `kategorien`, `filter`, `machbar`.

- [ ] **Step 1: `index()`'i genişlet**

`$designs = Design::all('active');` yap (bugün `Design::all()`), altına:

```php
        // Nur was veroeffentlicht ist. Ein Entwurf ist eine halbe Vorlage, und
        // eine halbe Vorlage im Schaufenster kostet Vertrauen.
        $kategorien = [];
        foreach ($designs as $design) {
            $k = (string) $design['category'];
            if ($k !== '' && !in_array($k, $kategorien, true)) {
                $kategorien[] = $k;
            }
        }
        sort($kategorien);

        $filter = Security::clean($_GET['kategorie'] ?? '', 48);
        if ($filter !== '') {
            $designs = array_values(array_filter(
                $designs,
                static fn (array $d): bool => (string) $d['category'] === $filter
            ));
        }

        $themenIds = array_map(
            static fn (array $t): string => (string) ($t['id'] ?? ''),
            Themes::all()
        );
```

`View::page(...)` dizisine:

```php
            'kategorien' => $kategorien,
            'filter'     => $filter,
            'machbar'    => Design::creatable($designs, $themenIds),
```

`use` bloğunda `Atelier\Security` ve `Atelier\Themes` yoksa ekle.

- [ ] **Step 2: Sayfa hâlâ açılıyor mu**

`curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/de/v2/designs` → 200

- [ ] **Step 3: Commit**

```bash
git add php/src/Controllers/DesignController.php
git commit -m "The showcase asks for published designs only

A draft is half a template, and half a template in the shop window costs trust.
The filter travels in the address so a link opens the same view for the next
person, and the wizard question is answered once, in Design::creatable()."
```

---

## Task 4: Vitrin — şablon

**Files:**
- Rewrite: `php/templates/pages/designs-v2.php`

**Interfaces:** Consumes Task 3'ün verdiği değişkenleri.

Üst boşluk `pt-32` kalıyor: bu sayfanın hero'su yok ve `#site-header` sabit,
93,6 px (Faz 1'de ölçüldü, `py-16` başlığı altında bırakıyordu).

- [ ] **Step 1: Şablonu yaz**

```php
<?php
/**
 * Schaufenster der zweiten Fassung.
 *
 * Die Kachel ist die Karte, die der Gast spaeter sieht - derselbe Stilblock,
 * dasselbe Markup. Was hier gut aussieht, sieht auch in der Einladung gut aus.
 *
 * @var list<array<string,mixed>> $designs
 * @var string $styles
 * @var array<string,string> $values
 * @var list<string> $kategorien
 * @var string $filter
 * @var array<string,bool> $machbar
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;

$p  = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
?>
<style><?= $styles ?></style>

<section class="mx-auto max-w-7xl px-6 pb-24 pt-32">
  <h1 class="font-display text-3xl font-light text-ink"><?= e(I18n::t('invitation2.title')) ?></h1>
  <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted"><?= e(I18n::t('invitation2.lead')) ?></p>

  <?php if ($kategorien !== []) : ?>
    <div class="mt-8 flex flex-wrap items-center gap-4 text-[0.66rem] uppercase tracking-[0.16em]">
      <a href="<?= e($p('/v2/designs')) ?>" class="<?= $filter === '' ? 'text-gold' : 'text-muted hover:text-ink' ?>">
        <?= $de ? 'Alle' : 'All' ?>
      </a>
      <?php foreach ($kategorien as $k) : ?>
        <a href="<?= e($p('/v2/designs') . '?kategorie=' . rawurlencode($k)) ?>"
           class="<?= $filter === $k ? 'text-gold' : 'text-muted hover:text-ink' ?>"><?= e($k) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($designs === []) : ?>
    <p class="mt-10 text-sm text-muted">
      <?= $de
        ? 'Hier steht gerade keine Vorlage. Schreibt uns – wir zeigen euch, was möglich ist: '
        : 'No template here right now. Write to us and we will show you what is possible: ' ?>
      <a class="text-gold" href="<?= e($p('/kontakt')) ?>"><?= e(I18n::t('nav.contact')) ?></a>
    </p>
  <?php endif; ?>

  <div class="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design) : ?>
      <?php $slug = (string) $design['slug']; ?>
      <div>
        <a href="<?= e($p('/v2/designs/' . $slug)) ?>" class="group block">
          <div class="d-<?= e($design['id']) ?> relative overflow-hidden"
               style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                      background: var(--d-bg, #EFE7DC);">
            <?= Design::html($design, $values, $locale) ?>
          </div>
          <p class="mt-4 font-display text-lg font-light text-ink group-hover:text-gold">
            <?= e($design['name'][$locale] ?? $design['name']['de']) ?>
          </p>
          <p class="text-xs uppercase tracking-[0.16em] text-muted"><?= e((string) $design['category']) ?></p>
        </a>

        <?php if ($machbar[$slug] ?? false) : ?>
          <a href="<?= e($p('/einladung') . '?design=' . rawurlencode($slug)) ?>"
             class="mt-3 inline-block border border-ink px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= $de ? 'Mit diesem Design erstellen' : 'Create with this design' ?>
          </a>
        <?php else : ?>
          <p class="mt-3 text-[0.64rem] uppercase tracking-[0.16em] text-muted">
            <?= $de ? 'Bald im Assistenten' : 'Coming to the wizard' ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
```

- [ ] **Step 2: Sınıf denetimi**

Tarayıcıda `/de/v2/designs` açıkken: her stylesheet'in selector'lerini topla,
sayfadaki her sınıfı `CSS.escape()` ile ara, bulunamayanı listele. Beklenen ölü
liste yalnızca `d-*` kanca sınıfları. Bir yardımcı sınıf ölü çıkarsa **mevcut
bir muadiliyle değiştir**, CSS'i yeniden derlemeye kalkma.

- [ ] **Step 3: Tarayıcıda kontrol et**

- `/de/v2/designs` → başlık ve paragraf sözlükten geliyor, iki kart, süzgeç
- `?kategorie=modern` → yalnızca Noir; "Alle" geri getiriyor
- Élysée kartında "Mit diesem Design erstellen"; temasız bir tasarımda
  "Bald im Assistenten"
- Düğmeye bas → `/de/einladung?design=elysee` ve sihirbazda **o tasarım seçili**
- `/en/v2/designs` → başlık İngilizce
- `/de/designs` ve `/de/einladung` bozulmamış

- [ ] **Step 4: Commit**

```bash
git add php/templates/pages/designs-v2.php
git commit -m "The comparison page becomes a shop window

The tile is the card the guest will see, not a picture of it. Where the wizard
can take a design, the button says so; where it cannot, the card says that
instead of pretending."
```

---

## Task 5: Demo sayfasının alt çubuğu

**Files:**
- Modify: `php/templates/pages/design-preview.php` (alt çubuk)
- Modify: `php/src/Controllers/DesignController.php` (`preview()`: iki değişken)

**Interfaces:**
- Consumes: `Admin::isLoggedIn()`, `Design::creatable()`.
- Produces: müşteriye iki bağlantı, panele girmişe bugünkü geliştirici çubuğu.

Bugünkü çubuk şunu yazıyor: "Élysée · luxury · Fassung 5 · 15 Ebenen · Auftakt
darkroom · Karte seal (1200 ms) · Kuvert anklicken". Bu bir geliştirici çubuğu ve
Faz 1'den beri işe yarıyor — müşteriye gösterilecek bir şey değil.

- [ ] **Step 1: Kontrolörde iki değişken**

`preview()`'un `View::page(...)` dizisine:

```php
            // Der Balken unten ist ein Entwicklerbalken. Wer angemeldet ist,
            // arbeitet an der Vorlage; wer nicht, will sie ansehen.
            'intern'  => Admin::isLoggedIn(),
            'machbar' => (Design::creatable([$design], array_map(
                static fn (array $t): string => (string) ($t['id'] ?? ''),
                Themes::all()
            ))[$design['slug']] ?? false),
```

`use Atelier\Admin;` gerekiyorsa ekle.

- [ ] **Step 2: Şablonda çubuğu ikiye ayır**

`design-preview.php` içindeki mevcut alt çubuğu şununla sar:

```php
      <?php if ($intern) : ?>
        <!-- bugünkü geliştirici çubuğu buraya, olduğu gibi -->
      <?php else : ?>
        <div class="fixed inset-x-0 bottom-0 z-[70] flex flex-wrap items-center justify-center gap-6 bg-ink/80 px-6 py-4 text-[0.62rem] uppercase tracking-[0.16em] text-cream">
          <a href="<?= e(I18n::path('/v2/designs', $locale)) ?>" class="hover:text-gold">
            <?= $locale === 'de' ? 'Alle Designs' : 'All designs' ?>
          </a>
          <?php if ($machbar) : ?>
            <a href="<?= e(I18n::path('/einladung', $locale) . '?design=' . rawurlencode((string) $design['slug'])) ?>"
               class="border border-cream px-5 py-2.5 transition-colors hover:bg-cream hover:text-ink">
              <?= $locale === 'de' ? 'Mit diesem Design erstellen' : 'Create with this design' ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
```

**Önce sınıf denetimi:** `z-[70]`, `bg-ink/80`, `inset-x-0` derlenmiş CSS'te var
mı? Yoksa mevcut muadillerini kullan (`z-[60]` ve `bg-ink/80` Faz 1'de
görülmüştü; yine de bak).

- [ ] **Step 3: Tarayıcıda kontrol et**

- Panelden çıkmış (ya da gizli sekmede) `/de/v2/designs/elysee` → alt çubukta
  yalnızca "Alle Designs" ve oluştur düğmesi; **hiçbir teknik bilgi yok**
- Panele girmiş halde aynı sayfa → bugünkü geliştirici çubuğu
- Zarf açılışı ikisinde de aynı çalışıyor

- [ ] **Step 4: Commit**

```bash
git add php/templates/pages/design-preview.php php/src/Controllers/DesignController.php
git commit -m "The demo stops talking to the developer when a customer is looking

The bar at the bottom names the version, the layer count and the motion axes.
That is useful while building a template and meaningless to someone deciding
whether they like it. Logged in, it stays; logged out, it becomes two links."
```

---

## Task 6: Kapanış

**Files:**
- Modify: `docs/superpowers/specs/2026-08-19-davetiye-v2-faz3a-vitrin-design.md`

- [ ] **Step 1: Bütün testleri çalıştır**

`cd php && php bin/test.php` → hepsi geçiyor, `design_showcase` atlanmamış.

- [ ] **Step 2: Eskisinin bozulmadığını doğrula**

```bash
cd php && for p in /de/ /en/ /de/designs /de/designs/elysee /de/einladung \
  /de/v2/designs /de/v2/designs/elysee /de/admin /de/admin/designs ; do
  echo "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8080$p) $p"
done
```

Hepsi 200; hiçbiri 500 değil.

- [ ] **Step 3: Eski rotalara yazan satır var mı**

```bash
git diff <faz3a-baslangic> --stat -- php/src/Controllers/InviteController.php php/templates/pages/invite-wizard.php php/templates/pages/designs.php
```

Çıktı **boş** olmalı: A eski motora dokunmuyor.

- [ ] **Step 4: Spec'in bitti ölçütünü işaretle ve commit et**

Doğrulananları işaretle; doğrulanmayan kaldıysa işaretleme, nedenini altına yaz.

---

## Sonraki adım

**B · Sihirbaz** kendi spec'iyle başlar. A'dan devredilen tek soru: `creatable()`
kuralı B gelince tamamen kalkacak — v2 sihirbazı tasarımı belgeden okuyacağı için
"temada karşılığı var mı" sorusu anlamını yitirir.
