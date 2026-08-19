# Davetiye v2 — Faz 2 Uygulama Planı

> **Ajan işçiler için:** GEREKLİ ALT SKILL: Bu planı görev görev uygulamak için
> `superpowers:subagent-driven-development` (önerilen) ya da
> `superpowers:executing-plans` kullanın. Adımlar `- [ ]` kutularıyla izleniyor.

**Amaç:** Tasarım belgesini panele taşımak — görsel kartlı katalog, sekiz bölümlü
form editörü, kaydetmeden canlı önizleme ve uyaran (engellemeyen) yayın kontrolü.

**Mimari:** Yeni bir panel kontrolörü (`DesignAdminController`) iki ekran sürüyor:
katalog ve editör. Formun belgeye dönüşmesi kontrolörde değil, saf ve sınanabilir
`Design::fromPost()` içinde oluyor. Önizleme, sunucunun bastığı gerçek kartın
üstünde JS'in yalnızca CSS değişkenlerini ve metin düğümlerini değiştirmesiyle
çalışıyor; keyframe üretimi istemciye hiç geçmiyor.

**Teknoloji:** PHP 8.3, Composer yok, bağımsız test koşucusu (`php bin/test.php`),
PDO/MySQL, sunucu tarafı şablonlar, derlenmiş Tailwind (`public/assets/style.css`),
sade JS (yapı kurulumu yok).

**Spec:** `docs/superpowers/specs/2026-08-19-davetiye-v2-faz2-panel-design.md`

## Global Constraints

- **Composer yok.** Yeni kütüphane eklenmiyor; testler `php/tests/` altında saf
  fonksiyon sınıyor ve `php bin/test.php` ile koşuyor.
- **Sınıflar panelin kendi paletinden:** `text-muted`, `text-ink`, `text-gold`,
  `border-sand-deep`, `font-display`, `bg-cream`, `text-ink-soft`. Tailwind'in
  varsayılan gri/amber tonları (`text-gray-*`, `bg-amber-*`, `tracking-wide`)
  derlenmiş CSS'te **yok** ve yazılırsa hiçbir şey yapmaz.
- **Yeni yardımcı sınıf yazmadan önce derlenmiş CSS'te var mı bak:**
  `grep -c "\.SINIF[^0-9a-zA-Z-]" php/public/assets/style.css`.
- **Her POST'ta** `Security::checkCsrf($_POST['csrf'] ?? null)`; her metin alanı
  `Security::clean()` üzerinden; eylemden sonra 302.
- **`Admin::requireLogin($locale)` ziyareti kendi sayar** — `Admin::recordVisit()`
  elle çağrılmaz, iki kez sayar.
- **Belge istemciden gelmez:** kayıtlı belge okunur, POST onun üstüne uygulanır.
- **`box`, `canvas`, `sections` bu fazda yazılmaz.** Testle bekçili.
- **`public/assets/designs/` panelden yazılmaz** — orayı `bin/export-scene-art.php`
  üretir; yüklenen görseller `public/uploads/` altına gider (`Media` sınıfı).
- **Mevcut dosyalardan yalnızca üçü değişir:** `php/public/index.php`,
  `php/src/Design.php`, `php/src/Controllers/DesignController.php`. Geri kalan her
  şey yeni dosya.

## Dosya yapısı

| Dosya | Sorumluluk |
|---|---|
| `php/src/Design.php` (değişir) | `fromPost()` ve `copy()` — saf birleştirme ve çoğaltma |
| `php/src/Controllers/DesignAdminController.php` (yeni) | Katalog ve editör: oturum, CSRF, yönlendirme, kayıt |
| `php/src/Controllers/DesignController.php` (değişir) | `admin()` buradan çıkar; genel sayfalar kalır |
| `php/templates/admin/designs.php` (yeniden yazılır) | Katalog ekranı |
| `php/templates/admin/design-edit.php` (yeni) | Editör, sekiz bölüm |
| `php/public/assets/design-editor.js` (yeni) | Canlı önizleme bağlantısı |
| `php/tests/design_admin.php` (yeni) | `fromPost()` ve `copy()` testleri |
| `php/public/index.php` (değişir) | İki rota |

---

## Task 1: `Design::fromPost()` — formun belgeye dönüşmesi

**Files:**
- Create: `php/tests/design_admin.php`
- Modify: `php/src/Design.php` (yeni metot, sınıfın sonuna, `save()`'den önce)

**Interfaces:**
- Consumes: `Design::complete()`, `Design::PERMISSIONS`, `Design::STATUSES`,
  `Themes::MOVES`, `Themes::INTROS`, `Themes::IDLES`, `Themes::NAME_ANIMATIONS`,
  `Themes::PARTICLES`, `Themes::REVEALS`, `Security::clean()`.
- Produces: `Design::fromPost(array $doc, array $post): array` — kayıtlı belgeyi
  ve POST dizisini alır, yeni belgeyi döndürür. Yan etkisi yok, veritabanına
  dokunmaz. Task 4 ve Task 6 bunu çağırır.

POST alan adları (şablon bunları basacak):

| Alan | Belgede |
|---|---|
| `name_de`, `name_en` | `name.de`, `name.en` |
| `category`, `sort` | `category`, `sort` |
| `tags` (virgülle) | `tags` |
| `palette_<ad>`, `palette_label_de_<ad>`, `palette_label_tr_<ad>` | `palette.<ad>.value`, `.label.de`, `.label.tr` |
| `palette_customer_<ad>` (checkbox) | `palette.<ad>.customer` |
| `font_family_<ad>`, `font_weight_<ad>`, `font_tracking_<ad>`, `font_line_<ad>`, `font_size_<ad>` | `fonts.<ad>.*` |
| `font_customer_<ad>` (checkbox) | `fonts.<ad>.customer` |
| `text_de_<katman>`, `text_en_<katman>` | `layers[].text.de/.en` |
| `src_<katman>` | `layers[].src` |
| `move_<katman>`, `delay_<katman>`, `duration_<katman>` | `layers[].motion.*` |
| `perm_<izin>_<katman>` (checkbox) | `layers[].permissions.<izin>` |
| `anim_intro`, `anim_idle`, `anim_card`, `anim_name`, `anim_particle`, `anim_reveal`, `anim_speed`, `anim_delay` | `animation.*` |

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_admin.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

/*
 * Faz 2: Was das Formular mit dem Dokument macht.
 *
 * Die Zusammenfuehrung liegt absichtlich in einer reinen Funktion und nicht im
 * Controller: die Grenze dieser Phase - dass hier nichts an box, canvas oder
 * sections schreibt - haelt nur, wenn ein Test sie aussprechen kann.
 */

$basis = Design::complete([
    'id'      => 'pruef',
    'slug'    => 'pruef',
    'name'    => ['de' => 'Prüf', 'en' => 'Test'],
    'status'  => 'draft',
    'canvas'  => ['ratio' => '632:490', 'safe' => 6],
    'palette' => ['accent' => ['value' => '#B08D57', 'customer' => false]],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300]],
    'layers'  => [
        ['id' => 'gruss', 'type' => 'text', 'spot' => 'card',
         'text' => ['de' => 'Wir heiraten', 'en' => 'We marry'],
         'box' => ['x' => 8, 'y' => 12, 'w' => 85],
         'motion' => ['move' => 'fade', 'delay' => 300, 'duration' => 1000],
         'permissions' => ['text' => true]],
    ],
]);

/* --- Bekannte Werte kommen durch --- */

$neu = Design::fromPost($basis, [
    'name_de'      => 'Élysée',
    'category'     => 'luxury',
    'palette_accent' => '#C9A24B',
    'text_de_gruss'  => 'Wir feiern',
    'move_gruss'     => 'rise',
]);

assert_same('Élysée', $neu['name']['de'], 'fromPost: Name wird uebernommen');
assert_same('luxury', $neu['category'], 'fromPost: Kategorie wird uebernommen');
assert_same('#C9A24B', $neu['palette']['accent']['value'], 'fromPost: Farbe wird uebernommen');
assert_same('Wir feiern', $neu['layers'][0]['text']['de'], 'fromPost: Text wird uebernommen');
assert_same('rise', $neu['layers'][0]['motion']['move'], 'fromPost: Bewegung wird uebernommen');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_admin`
Beklenen: `Call to undefined method Atelier\Design::fromPost()`

- [ ] **Step 3: En küçük uygulamayı yaz**

`php/src/Design.php` içine, `save()`'in hemen üstüne:

```php
    /**
     * Das Formular auf ein bestehendes Dokument legen.
     *
     * Rein: keine Datenbank, keine Session, kein $_POST. Was nicht im Formular
     * steht, bleibt wie es war - ein leeres Feld ist kein Loeschbefehl.
     *
     * Was hier NICHT steht, steht mit Absicht nicht hier: box, canvas und
     * sections. Die Kaesten gehoeren der vierten Phase, die Abschnitte der
     * dritten. tests/design_admin.php haelt diese Grenze.
     *
     * @param array<string,mixed> $doc  das gespeicherte Dokument
     * @param array<string,mixed> $post rohe Formularwerte
     * @return array<string,mixed>
     */
    public static function fromPost(array $doc, array $post): array
    {
        $doc = self::complete($doc);
        $text = static fn (string $key): ?string
            => isset($post[$key]) ? Security::clean((string) $post[$key], 200) : null;

        foreach (['de', 'en'] as $sprache) {
            $wert = $text('name_' . $sprache);
            if ($wert !== null && $wert !== '') {
                $doc['name'][$sprache] = $wert;
            }
        }

        $kategorie = $text('category');
        if ($kategorie !== null) {
            $doc['category'] = $kategorie;
        }
        if (isset($post['sort'])) {
            $doc['sort'] = (int) $post['sort'];
        }
        if (isset($post['tags'])) {
            $roh = explode(',', (string) $post['tags']);
            $doc['tags'] = array_values(array_filter(array_map(
                static fn (string $t): string => Security::clean(trim($t), 40),
                $roh
            ), static fn (string $t): bool => $t !== ''));
        }

        foreach ($doc['palette'] as $marke => $eintrag) {
            $wert = $text('palette_' . $marke);
            if ($wert !== null && $wert !== '') {
                $doc['palette'][$marke]['value'] = $wert;
            }
            foreach (['de' => 'palette_label_de_', 'tr' => 'palette_label_tr_'] as $s => $prefix) {
                $etikett = $text($prefix . $marke);
                if ($etikett !== null && $etikett !== '') {
                    $doc['palette'][$marke]['label'][$s] = $etikett;
                }
            }
            $doc['palette'][$marke]['customer'] = isset($post['palette_customer_' . $marke]);
        }

        foreach ($doc['fonts'] as $marke => $eintrag) {
            $familie = $text('font_family_' . $marke);
            if ($familie !== null && $familie !== '') {
                $doc['fonts'][$marke]['family'] = $familie;
            }
            foreach ([
                'weight'     => 'font_weight_',
                'tracking'   => 'font_tracking_',
                'lineHeight' => 'font_line_',
                'size'       => 'font_size_',
            ] as $feld => $prefix) {
                if (isset($post[$prefix . $marke])) {
                    $doc['fonts'][$marke][$feld] = (int) $post[$prefix . $marke];
                }
            }
            $doc['fonts'][$marke]['customer'] = isset($post['font_customer_' . $marke]);
        }

        foreach ($doc['layers'] as $i => $ebene) {
            $id = (string) $ebene['id'];

            foreach (['de', 'en'] as $sprache) {
                $wert = $text('text_' . $sprache . '_' . $id);
                if ($wert !== null && $wert !== '') {
                    $doc['layers'][$i]['text'][$sprache] = $wert;
                }
            }

            $quelle = $text('src_' . $id);
            if ($quelle !== null && $quelle !== '') {
                $doc['layers'][$i]['src'] = $quelle;
            }

            if (isset($post['move_' . $id])) {
                $doc['layers'][$i]['motion']['move'] = (string) $post['move_' . $id];
            }
            foreach (['delay' => 'delay_', 'duration' => 'duration_'] as $feld => $prefix) {
                if (isset($post[$prefix . $id])) {
                    $doc['layers'][$i]['motion'][$feld] = (int) $post[$prefix . $id];
                }
            }

            foreach (self::PERMISSIONS as $recht) {
                $doc['layers'][$i]['permissions'][$recht] = isset($post['perm_' . $recht . '_' . $id]);
            }
        }

        foreach ([
            'intro'    => 'anim_intro',
            'idle'     => 'anim_idle',
            'card'     => 'anim_card',
            'nameMove' => 'anim_name',
            'particle' => 'anim_particle',
            'reveal'   => 'anim_reveal',
        ] as $feld => $name) {
            if (isset($post[$name])) {
                $doc['animation'][$feld] = (string) $post[$name];
            }
        }
        foreach (['speed' => 'anim_speed', 'delay' => 'anim_delay'] as $feld => $name) {
            if (isset($post[$name])) {
                $doc['animation'][$feld] = (int) $post[$name];
            }
        }

        // complete() zieht die Grenzen: unbekannte Enums fallen auf die
        // Voreinstellung, Zahlen werden geklemmt, Rechte zu Wahrheitswerten.
        return self::complete($doc);
    }
```

`Security` sınıfı `Design.php`'nin `use` bloğunda yoksa ekle: `use Atelier\Security;`
(dosya `namespace Atelier;` içinde olduğu için muhtemelen gerekmez — önce
`grep -n "Security::" php/src/Design.php` ile bak).

- [ ] **Step 4: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_admin`
Beklenen: 5 kontrol geçiyor.

- [ ] **Step 5: Sınırı ve kenar durumları sınayan testleri ekle**

`php/tests/design_admin.php` sonuna:

```php
/* --- Ein leeres Feld loescht nichts --- */

$leer = Design::fromPost($basis, ['name_de' => '', 'text_de_gruss' => '', 'palette_accent' => '']);

assert_same('Prüf', $leer['name']['de'], 'fromPost: leerer Name loescht nicht');
assert_same('Wir heiraten', $leer['layers'][0]['text']['de'], 'fromPost: leerer Text loescht nicht');
assert_same('#B08D57', $leer['palette']['accent']['value'], 'fromPost: leere Farbe loescht nicht');

/* --- Unbekannte Werte fallen auf die Voreinstellung --- */

$quatsch = Design::fromPost($basis, ['move_gruss' => 'salto', 'anim_intro' => 'discokugel']);

assert_same('none', $quatsch['layers'][0]['motion']['move'], 'fromPost: unbekannte Bewegung faellt zurueck');

/* --- rgba bleibt, die bestehenden Themen benutzen es --- */

$rgba = Design::fromPost($basis, ['palette_accent' => 'rgba(176,141,87,0.30)']);

assert_same('rgba(176,141,87,0.30)', $rgba['palette']['accent']['value'], 'fromPost: rgba ueberlebt');

/* --- Rechte gehen hin und zurueck --- */

$rechte = Design::fromPost($basis, ['perm_color_gruss' => 'an']);

assert_same(true, $rechte['layers'][0]['permissions']['color'], 'fromPost: gesetztes Recht kommt an');
assert_same(false, $rechte['layers'][0]['permissions']['text'], 'fromPost: fehlendes Haekchen loescht das Recht');

/* --- Die Grenze der Phase: box, canvas und sections bleiben unberuehrt --- */

$angriff = Design::fromPost($basis, [
    'box_x_gruss'    => '99',
    'box_y_gruss'    => '99',
    'canvas_ratio'   => '1:1',
    'canvas_safe'    => '40',
    'sections'       => 'etwas',
    'layers'         => 'etwas',
    'version'        => '999',
]);

assert_same(8, $angriff['layers'][0]['box']['x'], 'fromPost: box bleibt unberuehrt');
assert_same(12, $angriff['layers'][0]['box']['y'], 'fromPost: box bleibt unberuehrt');
assert_same('632:490', $angriff['canvas']['ratio'], 'fromPost: canvas bleibt unberuehrt');
assert_same(6, $angriff['canvas']['safe'], 'fromPost: canvas bleibt unberuehrt');
assert_same([], $angriff['sections'], 'fromPost: sections bleibt unberuehrt');
assert_same(1, count($angriff['layers']), 'fromPost: die Ebenenliste kommt nicht aus dem Formular');
```

- [ ] **Step 6: Testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: hepsi geçiyor (Faz 1'in 210 kontrolü + bu dosyanın ~17'si).

Bir tanesi bile `box` ya da `canvas` üzerinde patlarsa **devam etme**: `fromPost()`
o alanlara yazıyor demektir ve bu fazın sınırı delinmiş olur.

- [ ] **Step 7: Commit**

```bash
git add php/tests/design_admin.php php/src/Design.php
git commit -m "The form becomes a document in one pure function

Design::fromPost() takes the stored document and the raw form and returns the
new document - no database, no session, no superglobal. That is what lets a
test say the thing this phase has to promise: nothing here writes box, canvas
or sections. An empty field is not a delete command either, and an unknown
value falls back the way the format already falls back."
```

---

## Task 2: `Design::copy()` — çoğaltma ve temadan doğan kayıt

**Files:**
- Modify: `php/tests/design_admin.php` (testler eklenir)
- Modify: `php/src/Design.php` (`fromPost()`'un hemen altına)

**Interfaces:**
- Consumes: `Design::complete()`, `Design::key()` (özel; `complete()` üzerinden dolaylı).
- Produces: `Design::copy(array $doc, string $slug, array $name): array` — verilen
  belgeden yeni bir taslak üretir. Task 4 hem "kopyala" hem "temadan oluştur"
  akışında bunu çağırır.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_admin.php` sonuna:

```php
/* --- Kopieren: eine neue Vorlage faengt bei eins an --- */

$quelle = Design::complete([
    'id' => 'elysee', 'slug' => 'elysee',
    'name' => ['de' => 'Élysée', 'en' => 'Élysée'],
    'status' => 'active', 'version' => 7, 'category' => 'luxury',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'layers' => [['id' => 'gruss', 'type' => 'text', 'text' => ['de' => 'Hallo']]],
]);

$kopie = Design::copy($quelle, 'Élysée Nacht', ['de' => 'Élysée Nacht', 'en' => 'Élysée Night']);

assert_same('elysee-nacht', $kopie['id'], 'copy: die Kennung kommt aus dem Namen');
assert_same('elysee-nacht', $kopie['slug'], 'copy: der Slug ist die Kennung');
assert_same('Élysée Nacht', $kopie['name']['de'], 'copy: der neue Name steht drin');
assert_same('draft', $kopie['status'], 'copy: eine Kopie ist ein Entwurf');
assert_same(1, $kopie['version'], 'copy: eine neue Vorlage faengt bei Fassung eins an');
assert_same('#B08D57', $kopie['palette']['accent']['value'], 'copy: die Farben kommen mit');
assert_same('gruss', $kopie['layers'][0]['id'], 'copy: die Ebenen kommen mit');

// Die Quelle darf sich nicht veraendern - sie liegt in der Datenbank.
assert_same('elysee', $quelle['id'], 'copy: die Quelle bleibt, wie sie war');
assert_same(7, $quelle['version'], 'copy: die Quelle behaelt ihre Fassung');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_admin`
Beklenen: `Call to undefined method Atelier\Design::copy()`

- [ ] **Step 3: Uygulamayı yaz**

`php/src/Design.php`, `fromPost()`'un altına:

```php
    /**
     * Eine Vorlage als neuer Entwurf.
     *
     * Dient zwei Wegen: dem Kopieren im Katalog und dem Uebernehmen eines
     * alten Themas (dort kommt $doc aus fromTheme()). Beide Male gilt
     * dasselbe: neue Kennung, neuer Name, Entwurf, Fassung eins. Die Fassung
     * der Quelle geht ausdruecklich NICHT mit - „Fassung 7" an einem Eintrag,
     * den es seit einer Minute gibt, waere eine Luege ueber seine Geschichte.
     *
     * @param array<string,mixed>  $doc
     * @param array{de:string,en:string}|array<string,string> $name
     * @return array<string,mixed>
     */
    public static function copy(array $doc, string $slug, array $name): array
    {
        $doc = self::complete($doc);

        $doc['id']      = self::key($slug);
        $doc['slug']    = self::key($slug);
        $doc['name']    = ['de' => (string) ($name['de'] ?? ''), 'en' => (string) ($name['en'] ?? '')];
        $doc['status']  = 'draft';
        $doc['version'] = 1;

        return self::complete($doc);
    }
```

- [ ] **Step 4: Testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: hepsi geçiyor.

- [ ] **Step 5: Commit**

```bash
git add php/tests/design_admin.php php/src/Design.php
git commit -m "A copy is a draft that starts at one

Copying a design and adopting an old theme are the same move with different
sources, so they share one function. The source's version deliberately does not
come along: an entry that has existed for a minute should not claim to be in
its seventh."
```

---

## Task 3: Katalog ekranı — kendi kontrolörü, görsel kartlar

**Files:**
- Create: `php/src/Controllers/DesignAdminController.php`
- Modify: `php/src/Controllers/DesignController.php` (`admin()` çıkar, `use Atelier\Admin;` satırı da)
- Rewrite: `php/templates/admin/designs.php`
- Modify: `php/public/index.php` (rota yeni kontrolöre gider)

**Interfaces:**
- Consumes: `Design::all()`, `Design::warnings()`, `Design::css()`, `Design::html()`,
  `Design::bindValues()`, `Admin::requireLogin()`, `View::page()`.
- Produces: `DesignAdminController::index(string $locale): void`. Task 4, 5 ve 7
  aynı sınıfa metot ekler.

- [ ] **Step 1: Kontrolörü yaz**

`php/src/Controllers/DesignAdminController.php`:

```php
<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Design;
use Atelier\I18n;
use Atelier\View;

/**
 * Der Katalog der zweiten Fassung im Panel und sein Editor.
 *
 * Liegt neben DesignController und nicht darin: der eine bedient Gaeste, der
 * andere den Betrieb. Zwei Leser, zwei Dateien.
 */
final class DesignAdminController
{
    /** Beispieldaten fuer die Kacheln - dieselben wie in der Vorschau. */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
    ];

    public function index(string $locale): void
    {
        Admin::requireLogin($locale);

        $designs = Design::all();

        // Der Filter steht in der Adresse, nicht in der Sitzung: ein Link auf
        // „nur luxury" soll denselben Blick oeffnen wie beim Absender.
        $filter = (string) ($_GET['kategorie'] ?? '');
        $kategorien = [];
        foreach ($designs as $design) {
            $k = (string) $design['category'];
            if ($k !== '' && !in_array($k, $kategorien, true)) {
                $kategorien[] = $k;
            }
        }
        sort($kategorien);

        if ($filter !== '') {
            $designs = array_values(array_filter(
                $designs,
                static fn (array $d): bool => (string) $d['category'] === $filter
            ));
        }

        $styles = '';
        $warnings = [];
        foreach ($designs as $design) {
            $styles .= Design::css($design, '.d-' . $design['id']);
            $warnings[$design['id']] = Design::warnings($design);
        }

        View::page('admin/designs', [
            'layout'     => 'admin/layout',
            'locale'     => $locale,
            'current'    => '/designs',
            'meta'       => ['title' => 'Designs (v2)', 'noindex' => true],
            'designs'    => $designs,
            'warnings'   => $warnings,
            'styles'     => $styles,
            'values'     => Design::bindValues(self::BEISPIEL, $locale),
            'kategorien' => $kategorien,
            'filter'     => $filter,
        ]);
    }
}
```

- [ ] **Step 2: Eski metodu kaldır**

`php/src/Controllers/DesignController.php` içinden `admin()` metodunu **tamamen**
sil ve `use Atelier\Admin;` satırını kaldır (başka kullanan kalmadıysa; kontrol:
`grep -n "Admin::" php/src/Controllers/DesignController.php`).

- [ ] **Step 3: Rotayı yeni kontrolöre bağla**

`php/public/index.php` içinde satırı değiştir:

```php
$router->any('/{locale}/admin/designs', $admin_(static fn (array $p) => (new DesignAdminController())->index($p['locale'])));
```

ve `use` bloğuna ekle:

```php
use Atelier\Controllers\DesignAdminController;
```

- [ ] **Step 4: Katalog şablonunu yaz**

`php/templates/admin/designs.php` (tamamen değişiyor):

```php
<?php
/**
 * Katalog der zweiten Fassung im Panel.
 *
 * Die Kachel ist keine nachgebaute Vorschau, sondern dieselbe Karte, die der
 * Gast sieht - derselbe Weg wie im oeffentlichen Katalog. Was hier gut
 * aussieht, sieht auch dort gut aus, und was hier kaputt ist, faellt hier auf.
 *
 * @var list<array<string,mixed>> $designs
 * @var array<string,list<array{kind:string,element:string,detail:string}>> $warnings
 * @var string $styles
 * @var array<string,string> $values
 * @var list<string> $kategorien
 * @var string $filter
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use Atelier\Security;
use function Atelier\e;

$tr = $locale === 'tr';
$p  = static fn (string $to): string => I18n::path($to, $locale);
?>
<style><?= $styles ?></style>

<div class="space-y-10">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $tr ? 'Tasarımlar (v2)' : 'Designs (v2)' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $tr
        ? 'Her kart, müşterinin göreceği kartın kendisi. Düzenle dediğinde renkleri, yazıları ve metinleri değiştirirsin; yerleşim bu fazda sabit.'
        : 'Jede Kachel ist die Karte, die der Gast sieht. „Bearbeiten" aendert Farben, Schriften und Texte; die Anordnung bleibt in dieser Phase fest.' ?>
    </p>
  </div>

  <?php if ($kategorien !== []) : ?>
    <div class="flex flex-wrap items-center gap-3 text-[0.66rem] uppercase tracking-[0.16em]">
      <a href="<?= e($p('/admin/designs')) ?>"
         class="<?= $filter === '' ? 'text-gold' : 'text-muted hover:text-ink' ?>">
        <?= $tr ? 'Hepsi' : 'Alle' ?>
      </a>
      <?php foreach ($kategorien as $k) : ?>
        <a href="<?= e($p('/admin/designs') . '?kategorie=' . rawurlencode($k)) ?>"
           class="<?= $filter === $k ? 'text-gold' : 'text-muted hover:text-ink' ?>"><?= e($k) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($designs === []) : ?>
    <p class="text-sm text-muted">
      <?= $tr ? 'Bu süzgeçle tasarım yok.' : 'Kein Design mit diesem Filter.' ?>
    </p>
  <?php endif; ?>

  <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design) : ?>
      <?php
      $id  = (string) $design['id'];
      $ms  = $warnings[$id] ?? [];
      $akt = (string) $design['status'] === 'active';
      ?>
      <div class="border <?= $akt ? 'border-gold' : 'border-sand-deep' ?>">
        <div class="d-<?= e($id) ?> relative overflow-hidden"
             style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <?= Design::html($design, $values, $locale) ?>
        </div>

        <div class="space-y-3 p-5">
          <div class="flex items-baseline justify-between gap-3">
            <span class="font-display text-lg text-ink"><?= e($design['name']['de']) ?></span>
            <span class="text-[0.62rem] uppercase tracking-[0.16em] <?= $akt ? 'text-gold' : 'text-muted' ?>">
              <?= e((string) $design['status']) ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-x-4 gap-y-1 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
            <span><?= e((string) $design['category']) ?></span>
            <span><?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
            <span><?= count($design['layers']) ?> <?= $tr ? 'katman' : 'Ebenen' ?></span>
            <span class="<?= $ms === [] ? 'text-muted' : 'text-gold' ?>">
              <?= $ms === [] ? ($tr ? 'uyarı yok' : 'keine Hinweise') : count($ms) . ($tr ? ' uyarı' : ' Hinweise') ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-2 pt-1 text-[0.62rem] uppercase tracking-[0.16em]">
            <a href="<?= e($p('/admin/designs/' . $design['slug'])) ?>"
               class="border border-ink px-3 py-2 text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $tr ? 'Düzenle' : 'Bearbeiten' ?>
            </a>
            <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank"
               class="border border-sand-deep px-3 py-2 text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Önizle' : 'Ansehen' ?>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
```

Kopyalama, aktif/pasif ve "temadan oluştur" düğmeleri **Task 4 ve 7'de** ekleniyor;
bu adımda katalog salt okunur ama görsel.

- [ ] **Step 5: Tarayıcıda kontrol et**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`

Aç ve gör:
- `http://localhost:8080/de/admin/designs` → iki kart, gerçek tasarımlar görünüyor
- `?kategorie=modern` → yalnızca Noir; "Hepsi" bağlantısı geri getiriyor
- Aktif tasarımın çerçevesi altın, taslağınki `border-sand-deep`
- `http://localhost:8080/de/admin/themen` → **eski tema sekmesi hâlâ çalışıyor**

- [ ] **Step 6: Bütün testleri çalıştır ve commit et**

```bash
cd php && php bin/test.php
git add php/src/Controllers/DesignAdminController.php php/src/Controllers/DesignController.php php/templates/admin/designs.php php/public/index.php
git commit -m "The panel's catalogue shows the designs, not a description of them

Each tile renders through the same Design::css() and Design::html() the public
page uses, so what looks right here looks right there - and what is broken
shows up here first. The category filter lives in the URL rather than the
session, so a link opens the same view for the next person.

The panel work also moves out of DesignController: one class serves guests, the
other serves the operator."
```

---

## Task 4: Kopyalama ve temadan oluşturma

**Files:**
- Modify: `php/src/Controllers/DesignAdminController.php` (POST dalı + dört özel metot)
- Modify: `php/templates/admin/designs.php` (iki form + bildirim satırı)

**Interfaces:**
- Consumes: `Design::copy()` (Task 2), `Design::fromTheme()`, `Design::save()`,
  `Design::findById()`, `Themes::all()`, `Themes::find()`, `Security::checkCsrf()`,
  `Security::clean()`.
- Produces: `POST /{locale}/admin/designs` üzerinde `was=kopyala` ve `was=temadan`.
  Sonuç adrese yazılıyor: `?ok=...` ya da `?fehler=...`.

**Bildirim kalıbı:** panelde hazır flash altyapısı yok. Sonuç adres satırında
taşınıyor — süzgeçle aynı mantık: bağlantı paylaşılabilir, oturuma yazmaya gerek
kalmaz, 303 sayesinde yeniden yükleme işlemi tekrarlamaz.

- [ ] **Step 1: POST dalını ekle**

`DesignAdminController::index()` içinde `Admin::requireLogin($locale);` satırının
hemen ardına:

```php
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handle($locale);
            return;
        }
```

`use` bloğuna: `use Atelier\Security;` ve `use Atelier\Themes;`

- [ ] **Step 2: Üç özel metodu ekle**

`DesignAdminController` sınıfına:

```php
    /** POST-Zweig: aendert etwas und leitet auf dieselbe Adresse zurueck. */
    private function handle(string $locale): void
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            $this->zurueck($locale, 'fehler=csrf');
            return;
        }

        $was = (string) ($_POST['was'] ?? '');

        if ($was === 'kopyala') {
            $this->zurueck($locale, $this->kopiere());
            return;
        }
        if ($was === 'temadan') {
            $this->zurueck($locale, $this->ausThema());
            return;
        }

        $this->zurueck($locale, 'fehler=unbekannt');
    }

    /** 303, damit ein Neuladen die Aktion nicht wiederholt. */
    private function zurueck(string $locale, string $query): void
    {
        header('Location: ' . I18n::path('/admin/designs', $locale) . ($query !== '' ? '?' . $query : ''), true, 303);
        exit;
    }

    /** Eine vorhandene Vorlage als neuer Entwurf. */
    private function kopiere(): string
    {
        $quelle = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        $name   = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($quelle === null) {
            return 'fehler=quelle';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        $neu = Design::copy($quelle, $name, ['de' => $name, 'en' => $name]);

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        Design::save($neu);
        return 'ok=kopiert';
    }

    /** Ein altes Thema als neues Dokument. */
    private function ausThema(): string
    {
        $thema = Themes::find(Security::clean($_POST['thema'] ?? '', 64));
        $name  = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($thema === null) {
            return 'fehler=thema';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        $neu = Design::copy(Design::fromTheme($thema), $name, ['de' => $name, 'en' => $name]);

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        // Die gezeichnete Szene liegt als Datei vor, nicht im Dokument. Fehlt
        // sie, entsteht die Vorlage trotzdem - aber die Meldung sagt, was noch
        // fehlt, sonst sucht jemand eine halbe Stunde nach leeren Ecken.
        $kunst = glob(__DIR__ . '/../../public/assets/designs/' . $thema['id'] . '-*.svg') ?: [];

        Design::save($neu);
        return $kunst === [] ? 'ok=uebernommen_ohne_kunst' : 'ok=uebernommen';
    }
```

- [ ] **Step 3: Bildirim satırını şablona ekle**

`php/templates/admin/designs.php`, başlık bloğunun altına:

```php
  <?php
  $ok     = (string) ($_GET['ok'] ?? '');
  $fehler = (string) ($_GET['fehler'] ?? '');
  $meldungen = [
      'kopiert'                => $tr ? 'Kopyalandı.' : 'Kopiert.',
      'uebernommen'            => $tr ? 'Temadan oluşturuldu.' : 'Aus dem Thema übernommen.',
      'uebernommen_ohne_kunst' => $tr
          ? 'Oluşturuldu — ama bu temanın çizilmiş sahnesi yok: php bin/export-scene-art.php ile dışa aktar.'
          : 'Übernommen – aber dieses Thema hat keine exportierte Szene: php bin/export-scene-art.php.',
      'quelle'    => $tr ? 'Kaynak tasarım bulunamadı.' : 'Die Quellvorlage wurde nicht gefunden.',
      'thema'     => $tr ? 'Tema bulunamadı.' : 'Das Thema wurde nicht gefunden.',
      'name'      => $tr ? 'Ad boş olamaz.' : 'Der Name darf nicht leer sein.',
      'belegt'    => $tr ? 'Bu adla bir tasarım zaten var.' : 'Unter diesem Namen gibt es schon eine Vorlage.',
      'csrf'      => $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen, bitte neu laden.',
      'unbekannt' => $tr ? 'Tanınmayan işlem.' : 'Unbekannte Aktion.',
  ];
  ?>
  <?php if ($ok !== '' && isset($meldungen[$ok])) : ?>
    <p class="border-l-2 border-gold px-4 py-3 text-sm text-ink"><?= e($meldungen[$ok]) ?></p>
  <?php endif; ?>
  <?php if ($fehler !== '' && isset($meldungen[$fehler])) : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700"><?= e($meldungen[$fehler]) ?></p>
  <?php endif; ?>
```

- [ ] **Step 4: İki formu ekle**

Katalog başlığının altına, "temadan oluştur":

```php
  <details class="border border-sand-deep">
    <summary class="cursor-pointer p-5 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
      <?= $tr ? 'Temadan yeni tasarım' : 'Neues Design aus einem Thema' ?>
    </summary>
    <form method="post" class="flex flex-wrap items-end gap-4 border-t border-sand-deep p-5">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="temadan">
      <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">
        <?= $tr ? 'Tema' : 'Thema' ?>
        <select name="thema" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
          <?php foreach ($themen as $t) : ?>
            <option value="<?= e((string) $t['id']) ?>"><?= e((string) $t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">
        <?= $tr ? 'Yeni ad' : 'Neuer Name' ?>
        <input name="neuer_name" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
      </label>
      <button class="bg-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
        <?= $tr ? 'Oluştur' : 'Anlegen' ?>
      </button>
    </form>
  </details>
```

Her kartın eylem satırına, kopyalama:

```php
            <form method="post" class="flex flex-wrap items-center gap-2">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="was" value="kopyala">
              <input type="hidden" name="quelle" value="<?= e($id) ?>">
              <input name="neuer_name" placeholder="<?= $tr ? 'kopyanın adı' : 'Name der Kopie' ?>"
                     class="w-40 border border-sand-deep bg-transparent px-2 py-2 text-[0.7rem] text-ink">
              <button class="border border-sand-deep px-3 py-2 text-muted transition-colors hover:text-ink">
                <?= $tr ? 'Kopyala' : 'Kopieren' ?>
              </button>
            </form>
```

İki değişken kontrolörden geliyor, şablon kendi başına iş yapmasın:
`'themen' => Themes::all()` ve `'csrf' => Security::csrf()`.

Bu, panelin bugünkü kalıbının aynısı: `templates/admin/themes.php:59` da
`$csrf`'i alıp gizli alanı kendisi basıyor. Yeni bir yardımcı uydurma.

- [ ] **Step 5: Tarayıcıda kontrol et**

- `http://localhost:8080/de/admin/designs` → "Neues Design aus einem Thema" açılıyor, 14 tema listeleniyor
- Ad vermeden gönder → kırmızı satır: "Der Name darf nicht leer sein."
- `Sage` temasından "Sage Test" oluştur → altın satır; sahne dosyası yoksa "keine exportierte Szene" uyarısı; katalogda yeni kart **taslak** olarak duruyor
- Aynı adla bir daha → "Unter diesem Namen gibt es schon eine Vorlage."
- Élysée kartına "Élysée Kopie" yazıp Kopyala → yeni taslak, **sürüm 1**, renkleri ve katmanları aynı
- F5 → işlem **tekrarlanmıyor** (303)
- Deneme kayıtlarını sonra sil: `php -r` ile değil, bir sonraki fazın silme akışıyla
  uğraşmamak için doğrudan veritabanından:
  `DELETE FROM designs WHERE id IN ('sage-test','elysee-kopie');`

- [ ] **Step 6: Bütün testleri çalıştır ve commit et**

```bash
cd php && php bin/test.php
git add php/src/Controllers/DesignAdminController.php php/templates/admin/designs.php
git commit -m "A new design is born by copying one or adopting a theme

Both paths end in Design::copy(), so a copy and a migrated theme are the same
kind of thing: a draft at version one. The result travels in the address rather
than the session - the reload stays honest and the message can be shared.

When a theme has no exported scene the design is still created, but the message
says what is missing. Empty corners are otherwise a half-hour hunt."
```

---

## Task 5: Editör — sekiz bölüm, kaydetme, çakışma

**Files:**
- Modify: `php/src/Controllers/DesignAdminController.php` (`edit()` + `speichere()`)
- Create: `php/templates/admin/design-edit.php`
- Modify: `php/public/index.php` (ikinci rota)

**Interfaces:**
- Consumes: `Design::find()`, `Design::fromPost()` (Task 1), `Design::save()`,
  `Design::warnings()`, `Design::css()`, `Design::html()`, `Design::bindValues()`,
  `Themes::MOVES/INTROS/IDLES/NAME_ANIMATIONS/PARTICLES/REVEALS/ANIMATIONS/FONTS`.
- Produces: `DesignAdminController::edit(array $params): void` — GET editörü basar,
  POST kaydeder ve `?ok=gespeichert` ile geri döner.

- [ ] **Step 1: Rotayı ekle**

`php/public/index.php`, katalog rotasının hemen altına:

```php
$router->any('/{locale}/admin/designs/{slug}', $admin_(static fn (array $p) => (new DesignAdminController())->edit($p)));
```

- [ ] **Step 2: `edit()` ve `speichere()` metotlarını yaz**

```php
    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        $locale = (string) ($params['locale'] ?? 'de');
        Admin::requireLogin($locale);

        $design = Design::find(Security::clean($params['slug'] ?? '', 96));
        if ($design === null) {
            (new PageController())->notFound($locale);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->speichere($locale, $design);
            return;
        }

        $scope = '.d-' . $design['id'];

        View::page('admin/design-edit', [
            'layout'   => 'admin/layout',
            'locale'   => $locale,
            'current'  => '/designs',
            'meta'     => [
                'title'   => 'Design: ' . ($design['name']['de'] ?? ''),
                'noindex' => true,
                // Nur die Vorschau braucht Skript. Der Rest der Seite ist ein
                // Formular und bleibt ohne.
                'scripts' => ['/assets/design-editor.js'],
            ],
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            'styles'   => Design::css($design, $scope),
            'karte'    => Design::html($design, Design::bindValues(self::BEISPIEL, $locale), $locale, 'card'),
            'seite'    => Design::html($design, Design::bindValues(self::BEISPIEL, $locale), $locale, 'page'),
            'warnings' => Design::warnings($design),
            'csrf'     => Security::csrf(),
        ]);
    }

    /** @param array<string,mixed> $design */
    private function speichere(string $locale, array $design): void
    {
        $ziel = I18n::path('/admin/designs/' . $design['slug'], $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        // Wer das Formular geoeffnet hatte, hat eine Fassungsnummer mitbekommen.
        // Ist sie inzwischen kleiner als die gespeicherte, hat jemand anders
        // dazwischen gespeichert - dann wird hier nichts ueberschrieben.
        $gesehen = (int) ($_POST['version'] ?? 0);
        if ($gesehen > 0 && $gesehen < (int) $design['version']) {
            header('Location: ' . $ziel . '?fehler=veraltet', true, 303);
            exit;
        }

        Design::save(Design::fromPost($design, $_POST));

        header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
        exit;
    }
```

`use` bloğuna `use Atelier\Controllers\PageController;` gerekmiyor (aynı ad alanı),
ama `PageController` sınıfının adı doğru mu diye bak:
`grep -n "class PageController" php/src/Controllers/PageController.php`.

- [ ] **Step 3: Editör şablonunun iskeletini ve önizlemesini yaz**

`php/templates/admin/design-edit.php`:

```php
<?php
/**
 * Editor der zweiten Fassung. Bearbeitet die Oberflaeche: Farben, Schriften,
 * Texte, Bilder, Bewegung, Kundenrechte. Die Kaesten der Ebenen stehen NICHT
 * hier - sie gehoeren der vierten Phase, und tests/design_admin.php haelt die
 * Grenze.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $seite
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var string $csrf
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use Atelier\Themes;
use function Atelier\e;

$tr    = $locale === 'tr';
$p     = static fn (string $to): string => I18n::path($to, $locale);
$label = 'text-[0.66rem] uppercase tracking-[0.16em] text-muted';
$feld  = 'mt-1 block w-full border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink';

$ok     = (string) ($_GET['ok'] ?? '');
$fehler = (string) ($_GET['fehler'] ?? '');
?>
<style><?= $styles ?></style>

<div class="space-y-8">
  <div class="flex flex-wrap items-baseline justify-between gap-4">
    <h2 class="font-display text-xl text-ink"><?= e($design['name']['de']) ?></h2>
    <a href="<?= e($p('/admin/designs')) ?>" class="<?= $label ?> hover:text-ink">
      <?= $tr ? 'Katalog' : 'Zum Katalog' ?>
    </a>
  </div>

  <?php if ($ok === 'gespeichert') : ?>
    <p class="border-l-2 border-gold px-4 py-3 text-sm text-ink"><?= $tr ? 'Kaydedildi.' : 'Gespeichert.' ?></p>
  <?php endif; ?>
  <?php if ($fehler === 'veraltet') : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700">
      <?= $tr
        ? 'Bu tasarım sen açtıktan sonra başka bir yerde değiştirildi. Sayfayı tazele, sonra yeniden dene — yoksa onun işini silersin.'
        : 'Diese Vorlage wurde geaendert, nachdem du sie geoeffnet hast. Bitte neu laden und noch einmal - sonst ueberschreibst du fremde Arbeit.' ?>
    </p>
  <?php endif; ?>
  <?php if ($fehler === 'csrf') : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700"><?= $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen.' ?></p>
  <?php endif; ?>

  <div class="grid gap-8 lg:grid-cols-[1fr_0.8fr]">
    <form method="post" class="space-y-4" data-design-form>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="kaydet">
      <input type="hidden" name="version" value="<?= (int) $design['version'] ?>">

      <!-- BÖLÜMLER buraya (Step 4) -->

      <div class="sticky bottom-0 flex flex-wrap items-center gap-3 border-t border-sand-deep bg-cream py-4">
        <button class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          <?= $tr ? 'Kaydet' : 'Speichern' ?>
        </button>
        <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank"
           class="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-ink">
          <?= $tr ? 'Kaydet ve tam ekran aç' : 'Speichern und ganz ansehen' ?>
        </a>
        <span class="<?= $label ?>"><?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
      </div>
    </form>

    <?php /*
       Die Vorschau ist die Karte selbst, nicht ihr Abbild: derselbe Stilblock,
       dasselbe Markup wie auf der oeffentlichen Seite, nur kleiner. Das Skript
       aendert daran ausschliesslich CSS-Variablen und Textknoten.
    */ ?>
    <div class="lg:sticky lg:top-24">
      <div class="<?= e($scope) ?> relative overflow-hidden border border-sand-deep"
           data-design-preview
           style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                  background: var(--d-bg, #EFE7DC);">
        <div class="absolute inset-0"><?= $seite ?></div>
        <div class="absolute inset-0"><?= $karte ?></div>
      </div>
      <p class="mt-3 <?= $label ?>">
        <?= $tr
          ? 'Renk, yazı ve metin anında değişir. Hareket ve görsel için kaydet.'
          : 'Farbe, Schrift und Text aendern sich sofort. Bewegung und Bild brauchen ein Speichern.' ?>
      </p>
    </div>
  </div>
</div>
```

- [ ] **Step 4: Sekiz bölümü yaz**

Yukarıdaki `<!-- BÖLÜMLER buraya -->` yerine. Her bölüm `details`, kapalı doğuyor —
Élysée'de 14 katman var, hepsi birden açık olursa duvar olur.

```php
      <?php
      // Ein Abschnitt: zu, bis jemand ihn braucht.
      $auf = static function (string $titel): string {
          return '<details class="border border-sand-deep"><summary class="cursor-pointer p-5 text-[0.66rem] uppercase tracking-[0.16em] text-muted">'
               . $titel . '</summary><div class="space-y-5 border-t border-sand-deep p-5">';
      };
      $zu = '</div></details>';

      $textEbenen  = array_filter($design['layers'], static fn (array $l): bool => $l['type'] === 'text' && ($l['bind'] ?? '') === '');
      $bildEbenen  = array_filter($design['layers'], static fn (array $l): bool => in_array($l['type'], ['image', 'photo'], true));
      $bundEbenen  = array_filter($design['layers'], static fn (array $l): bool => ($l['bind'] ?? '') !== '');
      ?>

      <?= $auf($tr ? '1 · Genel' : '1 · Allgemein') ?>
        <div class="grid gap-5 sm:grid-cols-2">
          <label class="<?= $label ?>">DE
            <input name="name_de" value="<?= e($design['name']['de']) ?>" class="<?= $feld ?>"></label>
          <label class="<?= $label ?>">EN
            <input name="name_en" value="<?= e($design['name']['en']) ?>" class="<?= $feld ?>"></label>
          <label class="<?= $label ?>"><?= $tr ? 'Kategori' : 'Kategorie' ?>
            <input name="category" value="<?= e((string) $design['category']) ?>" class="<?= $feld ?>"></label>
          <label class="<?= $label ?>"><?= $tr ? 'Sıra' : 'Reihenfolge' ?>
            <input name="sort" type="number" value="<?= (int) $design['sort'] ?>" class="<?= $feld ?>"></label>
          <label class="<?= $label ?> sm:col-span-2"><?= $tr ? 'Etiketler (virgülle)' : 'Schlagworte (mit Komma)' ?>
            <input name="tags" value="<?= e(implode(', ', $design['tags'])) ?>" class="<?= $feld ?>"></label>
        </div>
      <?= $zu ?>

      <?= $auf($tr ? '2 · Renkler' : '2 · Farben') ?>
        <div class="grid gap-5 sm:grid-cols-2">
          <?php foreach ($design['palette'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>"><?= e($eintrag['label']['de'] ?? $marke) ?> (<?= e($marke) ?>)</label>
              <div class="mt-1 flex items-center gap-2">
                <?php $istHex = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $eintrag['value']) === 1; ?>
                <input type="color" value="<?= e($istHex ? $eintrag['value'] : '#B08D57') ?>"
                       class="h-9 w-10 shrink-0 cursor-pointer border border-sand-deep bg-transparent p-0"
                       data-farbwahl="<?= e($marke) ?>" <?= $istHex ? '' : 'title="rgba"' ?>>
                <input name="palette_<?= e($marke) ?>" value="<?= e((string) $eintrag['value']) ?>"
                       class="<?= $feld ?> font-mono text-[0.8rem]" data-farbfeld="<?= e($marke) ?>">
              </div>
              <label class="mt-2 flex items-center gap-2 text-[0.66rem] text-muted">
                <input type="checkbox" name="palette_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
                <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf aendern' ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      <?= $zu ?>

      <?= $auf($tr ? '3 · Yazı tipleri' : '3 · Schriften') ?>
        <?php foreach ($design['fonts'] as $marke => $eintrag) : ?>
          <div class="grid gap-4 sm:grid-cols-5">
            <label class="<?= $label ?> sm:col-span-2"><?= e($marke) ?>
              <select name="font_family_<?= e($marke) ?>" class="<?= $feld ?>" data-schriftfeld="<?= e($marke) ?>">
                <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                  <option value="<?= e($familie) ?>" <?= $eintrag['family'] === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                <?php endforeach; ?>
              </select></label>
            <label class="<?= $label ?>"><?= $tr ? 'ağırlık' : 'Gewicht' ?>
              <input name="font_weight_<?= e($marke) ?>" type="number" step="100" min="100" max="900"
                     value="<?= (int) $eintrag['weight'] ?>" class="<?= $feld ?>" data-gewichtfeld="<?= e($marke) ?>"></label>
            <label class="<?= $label ?>"><?= $tr ? 'laufweite' : 'Laufweite' ?>
              <input name="font_tracking_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['tracking'] ?>" class="<?= $feld ?>"></label>
            <label class="<?= $label ?>"><?= $tr ? 'satır y.' : 'Zeilenhoehe' ?>
              <input name="font_line_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['lineHeight'] ?>" class="<?= $feld ?>"></label>
          </div>
          <label class="flex items-center gap-2 text-[0.66rem] text-muted">
            <input type="checkbox" name="font_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
            <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf aendern' ?>
          </label>
        <?php endforeach; ?>
      <?= $zu ?>

      <?= $auf($tr ? '4 · Metinler' : '4 · Texte') ?>
        <?php foreach ($textEbenen as $ebene) : ?>
          <div class="grid gap-4 sm:grid-cols-2">
            <label class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?> · DE
              <input name="text_de_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['de']) ?>"
                     class="<?= $feld ?>" data-textfeld="<?= e($ebene['id']) ?>"></label>
            <label class="<?= $label ?>">EN
              <input name="text_en_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['en']) ?>" class="<?= $feld ?>"></label>
          </div>
        <?php endforeach; ?>
        <?php if ($bundEbenen !== []) : ?>
          <p class="<?= $label ?>"><?= $tr ? 'Çiftin verisinden gelenler (düzenlenemez):' : 'Kommt aus den Daten des Paares (nicht editierbar):' ?></p>
          <ul class="space-y-1 text-sm text-muted">
            <?php foreach ($bundEbenen as $ebene) : ?>
              <li><?= e($ebene['label'] ?: $ebene['id']) ?> — <code><?= e((string) $ebene['bind']) ?></code></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?= $zu ?>
```

Devamı, aynı formun içinde:

```php
      <?= $auf($tr ? '5 · Görseller' : '5 · Bilder') ?>
        <?php if ($bildEbenen === []) : ?>
          <p class="text-sm text-muted"><?= $tr ? 'Bu tasarımda görsel katman yok.' : 'Diese Vorlage hat keine Bildebene.' ?></p>
        <?php endif; ?>
        <?php foreach ($bildEbenen as $ebene) : ?>
          <label class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?>
            <input name="src_<?= e($ebene['id']) ?>" value="<?= e((string) $ebene['src']) ?>" class="<?= $feld ?> font-mono text-[0.78rem]"></label>
        <?php endforeach; ?>
        <p class="<?= $label ?>">
          <?= $tr
            ? 'assets/designs/ altındaki dosyalar dışa aktarma betiğinin ürünü; elle değiştirme, sonraki export ezer. Yüklenen görseller uploads/ altına gider.'
            : 'Was unter assets/designs/ liegt, erzeugt das Exportskript; von Hand geaendert, ueberschreibt es der naechste Export. Hochgeladene Bilder liegen unter uploads/.' ?>
        </p>
      <?= $zu ?>

      <?= $auf($tr ? '6 · Animasyon' : '6 · Bewegung') ?>
        <div class="grid gap-4 sm:grid-cols-3">
          <?php
          $achsen = [
              'anim_intro'    => [$tr ? 'Giriş' : 'Auftakt',      Themes::INTROS,          (string) $design['animation']['intro']],
              'anim_idle'     => [$tr ? 'Boşta' : 'Ruhe',         Themes::IDLES,           (string) $design['animation']['idle']],
              'anim_card'     => [$tr ? 'Kart' : 'Karte',         array_keys(Themes::ANIMATIONS), (string) $design['animation']['card']],
              'anim_name'     => [$tr ? 'İsimler' : 'Namen',      Themes::NAME_ANIMATIONS, (string) $design['animation']['nameMove']],
              'anim_particle' => [$tr ? 'Partikül' : 'Teilchen',  Themes::PARTICLES,       (string) $design['animation']['particle']],
              'anim_reveal'   => [$tr ? 'Açılış' : 'Enthuellung', Themes::REVEALS,         (string) $design['animation']['reveal']],
          ];
          ?>
          <?php foreach ($achsen as $name => [$titel, $liste, $wert]) : ?>
            <label class="<?= $label ?>"><?= e($titel) ?>
              <select name="<?= e($name) ?>" class="<?= $feld ?>">
                <?php foreach ($liste as $option) : ?>
                  <option value="<?= e((string) $option) ?>" <?= $wert === (string) $option ? 'selected' : '' ?>><?= e((string) $option) ?></option>
                <?php endforeach; ?>
              </select></label>
          <?php endforeach; ?>
          <label class="<?= $label ?>"><?= $tr ? 'hız (ms)' : 'Tempo (ms)' ?>
            <input name="anim_speed" type="number" value="<?= (int) $design['animation']['speed'] ?>" class="<?= $feld ?>"></label>
        </div>

        <p class="<?= $label ?>"><?= $tr ? 'Katman hareketleri' : 'Bewegung je Ebene' ?></p>
        <?php foreach ($design['layers'] as $ebene) : ?>
          <div class="grid gap-3 sm:grid-cols-4">
            <span class="text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
            <select name="move_<?= e($ebene['id']) ?>" class="<?= $feld ?>">
              <?php foreach (Themes::MOVES as $m) : ?>
                <option value="<?= e($m) ?>" <?= $ebene['motion']['move'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
              <?php endforeach; ?>
            </select>
            <input name="delay_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['delay'] ?>" class="<?= $feld ?>">
            <input name="duration_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['duration'] ?>" class="<?= $feld ?>">
          </div>
        <?php endforeach; ?>
      <?= $zu ?>

      <?= $auf($tr ? '7 · Müşteri izinleri' : '7 · Kundenrechte') ?>
        <p class="<?= $label ?>">
          <?= $tr ? 'Faz 3 sihirbazı bu bayrakları okuyacak: müşteri neye dokunabilir.' : 'Der Assistent der dritten Phase liest diese Haken: was der Kunde anfassen darf.' ?>
        </p>
        <?php foreach ($design['layers'] as $ebene) : ?>
          <div class="flex flex-wrap items-center gap-4 border-b border-sand-deep py-2">
            <span class="w-40 text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
            <?php foreach (Design::PERMISSIONS as $recht) : ?>
              <label class="flex items-center gap-2 text-[0.66rem] text-muted">
                <input type="checkbox" name="perm_<?= e($recht) ?>_<?= e($ebene['id']) ?>" <?= $ebene['permissions'][$recht] ? 'checked' : '' ?>>
                <?= e($recht) ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      <?= $zu ?>

      <?= $auf($tr ? '8 · Yayın' : '8 · Veroeffentlichen') ?>
        <p class="text-sm text-ink">
          <?= $tr ? 'Durum' : 'Zustand' ?>: <strong><?= e((string) $design['status']) ?></strong>
          · <?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?>
        </p>
        <?php if ($warnings === []) : ?>
          <p class="text-sm text-muted"><?= $tr ? 'Uyarı yok.' : 'Keine Hinweise.' ?></p>
        <?php else : ?>
          <ul class="space-y-1 text-sm text-gold">
            <?php foreach ($warnings as $w) : ?>
              <li><?= e($w['kind']) ?> — <?= e($w['element']) ?><?= $w['detail'] !== '' ? ' (' . e($w['detail']) . ')' : '' ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <p class="<?= $label ?>"><?= $tr ? 'Aktife alma katalogdan yapılır (Task 7).' : 'Das Aktivieren geschieht im Katalog (Task 7).' ?></p>
      <?= $zu ?>
```

- [ ] **Step 5: Tarayıcıda kontrol et**

- `http://localhost:8080/de/admin/designs/elysee` → sekiz bölüm kapalı duruyor, sağda kart görünüyor
- Bir rengi değiştir, **Kaydet** → "Gespeichert.", sürüm 1 artıyor
- Aynı sayfada tekrar Kaydet (değişiklik yapmadan) → sürüm **artmıyor**
- İki sekmede aç, birinde kaydet, sonra ötekinde kaydet → ikincisi "Diese Vorlage wurde geaendert…" diyor ve **yazmıyor**
- `http://localhost:8080/de/admin/designs/yoktur` → 404
- Genel sayfa `http://localhost:8080/de/v2/designs/elysee` → değişiklik orada da görünüyor

- [ ] **Step 6: Bütün testleri çalıştır ve commit et**

```bash
cd php && php bin/test.php
git add php/src/Controllers/DesignAdminController.php php/templates/admin/design-edit.php php/public/index.php
git commit -m "The editor edits the surface, and says so in eight sections

Colours, fonts, texts, images, motion and customer rights - each section closed
until someone needs it, because Elysee has fourteen layers and all of them open
at once is a wall. The boxes are not here; that is the fourth phase, and a test
already says so.

A hidden version field travels with the form. If someone else saved in the
meantime, this save is refused rather than winning silently."
```

---

## Task 6: Canlı önizleme — önce CSS'i taşınabilir yap, sonra JS

**Files:**
- Modify: `php/tests/design_css.php` (üç kontrol)
- Modify: `php/src/Design.php` (`css()` içindeki yazı bloğu)
- Create: `php/public/assets/design-editor.js`

**Interfaces:**
- Produces: yazı markası başına üç yeni CSS değişkeni — `--dfw-<marke>` (ağırlık),
  `--dft-<marke>` (laufweite), `--dfl-<marke>` (satır yüksekliği). Eleman kuralları
  bu değişkenleri kullanır. Task 6'nın JS'i ve Faz 3'ün müşteri önizlemesi buna dayanır.

**Neden önce CSS:** bugün `css()` ağırlığı, laufweite'yi ve satır yüksekliğini
**her elemanın kuralına sabit sayı olarak** yazıyor. Sabit sayıyı JS ile
değiştirmek için elemanı yazı markasına bağlayan bir harita gerekir ve o harita
DOM'da yok. Değerler değişkene taşınırsa önizleme tek satırla dönebiliyor — ve
spec'in "yazı alanları anında değişir" sözü gerçek oluyor.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_css.php` sonuna:

```php
/* --- Schriftwerte stehen als Variablen, nicht als feste Zahlen --- */

// Sonst kann die Vorschau im Panel sie nicht ohne Speichern aendern: eine feste
// Zahl in der Elementregel laesst sich nur mit einer Karte Element->Schriftmarke
// erreichen, und die gibt es im DOM nicht.

$sch = Design::css([
    'id'    => 'sch',
    'fonts' => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300, 'tracking' => 4, 'lineHeight' => 115]],
    'layers' => [['id' => 'a', 'type' => 'text', 'style' => ['font' => 'display', 'size' => 100]]],
], '.d-sch');

assert_contains($sch, '--dfw-display:300;', 'css: Gewicht steht als Variable');
assert_contains($sch, '--dft-display:0.04em;', 'css: Laufweite steht als Variable');
assert_contains($sch, '--dfl-display:1.15;', 'css: Zeilenhoehe steht als Variable');
assert_contains($sch, 'font-weight:var(--dfw-display);', 'css: die Elementregel liest die Variable');
assert_contains($sch, 'letter-spacing:var(--dft-display);', 'css: die Elementregel liest die Variable');
assert_contains($sch, 'line-height:var(--dfl-display);', 'css: die Elementregel liest die Variable');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_css`
Beklenen: altı yeni kontrol başarısız (bugün `font-weight:300;` yazılıyor).

- [ ] **Step 3: `css()`'i değiştir**

`php/src/Design.php`, yazı markalarının değişkenlerinin yazıldığı yerde
(`--df-` satırının yanına):

```php
            $vars .= '--df-' . $key . ':' . self::safeFont((string) $entry['family']) . ';';
            // Gewicht, Laufweite und Zeilenhoehe ebenfalls als Variable: nur so
            // kann die Vorschau im Panel sie ohne Speichern aendern.
            $vars .= '--dfw-' . $key . ':' . (int) $entry['weight'] . ';';
            $vars .= '--dft-' . $key . ':' . ($entry['tracking'] / 100) . 'em;';
            $vars .= '--dfl-' . $key . ':' . ($entry['lineHeight'] / 100) . ';';
```

Ve elemanın metin kuralında, bugün sabit sayı yazan üç satır değişkene döner:

```php
                $schrift = '';
                if ($style['font'] !== '' && isset($doc['fonts'][$style['font']])) {
                    $schrift = 'font-weight:var(--dfw-' . $style['font'] . ');'
                        . 'letter-spacing:var(--dft-' . $style['font'] . ');'
                        . 'line-height:var(--dfl-' . $style['font'] . ');';
                }
```

- [ ] **Step 4: Testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: hepsi geçiyor. Faz 1'de yazılmış olan
`css: Gewicht/Laufweite/Zeilenhoehe` kontrolleri sabit sayı bekliyorsa onları da
değişken bekleyecek şekilde güncelle — sözleşme bilerek değişti.

Sonra tarayıcıda `http://localhost:8080/de/v2/designs/elysee` aç ve isimlerin
hâlâ 300 ağırlıkta, 0.04em laufweite ile durduğunu gör: değişkene taşımak
görünümü değiştirmemeli.

- [ ] **Step 5: Önizleme betiğini yaz**

`php/public/assets/design-editor.js`:

```js
/*
 * Vorschau im Design-Editor.
 *
 * Das Skript zeichnet nichts nach: die Karte daneben ist dieselbe, die der Gast
 * sieht. Es aendert ausschliesslich CSS-Variablen und Textknoten. Keyframes
 * bleiben beim Server - sonst gaebe es zwei Wahrheiten, eine im Panel und eine
 * auf der Seite, und sie wuerden auseinanderlaufen.
 */
(function () {
  "use strict";

  var vorschau = document.querySelector("[data-design-preview]");
  var form = document.querySelector("[data-design-form]");
  if (!vorschau || !form) return;

  // Farbe: Textfeld ist die Wahrheit, der Waehler schreibt hinein.
  form.querySelectorAll("[data-farbfeld]").forEach(function (feld) {
    var marke = feld.getAttribute("data-farbfeld");
    var waehler = form.querySelector('[data-farbwahl="' + marke + '"]');

    var male = function () {
      vorschau.style.setProperty("--d-" + marke.toLowerCase(), feld.value.trim());
    };

    feld.addEventListener("input", function () {
      if (/^#[0-9a-fA-F]{6}$/.test(feld.value.trim()) && waehler) waehler.value = feld.value.trim();
      male();
    });

    if (waehler) {
      waehler.addEventListener("input", function () {
        feld.value = waehler.value;
        male();
      });
    }
  });

  // Schriftfamilie, Gewicht: gehen ueber die Variablen aus Step 3.
  form.querySelectorAll("[data-schriftfeld]").forEach(function (feld) {
    feld.addEventListener("change", function () {
      vorschau.style.setProperty("--df-" + feld.getAttribute("data-schriftfeld"), '"' + feld.value + '"');
    });
  });

  form.querySelectorAll("[data-gewichtfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      vorschau.style.setProperty("--dfw-" + feld.getAttribute("data-gewichtfeld"), feld.value);
    });
  });

  // Fester Text: der Knoten in der Vorschau traegt die Klasse d-el-<id>.
  form.querySelectorAll("[data-textfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      var ziel = vorschau.querySelector(".d-el-" + feld.getAttribute("data-textfeld"));
      if (ziel) ziel.textContent = feld.value;
    });
  });
})();
```

**CSP notu:** dosya `public/assets/` altında ve `script-src 'self'` ile
yükleniyor; satır içi script yok, nonce gerekmiyor.

- [ ] **Step 6: Tarayıcıda kontrol et**

`http://localhost:8080/de/admin/designs/elysee`:

- Renkler bölümünde `accent`'i değiştir → önizlemedeki isimler **kaydetmeden** renk değiştiriyor
- Renk seçiciyle metin alanı iki yönlü eşleşiyor
- Yazı tipi `display` → `Jost` seç → önizlemede tarih satırı anında değişiyor
- `display` ağırlığını 300 → 600 yap → isimler anında kalınlaşıyor
- Metinler bölümünde "Wir heiraten"i değiştir → önizlemede anında değişiyor
- **Kaydet**, sonra `http://localhost:8080/de/v2/designs/elysee` → aynısı görünüyor
  (panel ile sayfa ayrışmıyor)
- Kaydetmeden sayfayı tazele → değişiklikler gitmiş olmalı (önizleme kalıcı değil)

- [ ] **Step 7: Commit**

```bash
git add php/src/Design.php php/tests/design_css.php php/public/assets/design-editor.js
git commit -m "Font values move into variables, and the preview can finally answer

css() wrote weight, tracking and line-height as fixed numbers into every element
rule, which meant the panel could not change them without a save: you would need
a map from element to font token, and the DOM has none. As variables the
preview answers immediately, and the page renders exactly as before.

The script only ever sets CSS variables and text nodes. Keyframes stay on the
server - two truths that drift apart is worse than one that needs a save."
```

---

## Task 7: Aktife alma — uyarır, sorar, engellemez

**Files:**
- Modify: `php/src/Controllers/DesignAdminController.php` (`durum()` metodu)
- Modify: `php/templates/admin/designs.php` (aç/kapa formu + onay şeridi)

**Interfaces:**
- Consumes: `Design::findById()`, `Design::warnings()`, `Design::save()`.
- Produces: `POST /{locale}/admin/designs` üzerinde `was=durum`. Uyarı varsa ve
  `bestaetigt` gelmediyse `?frage=aktivieren&id=<id>&n=<sayı>` ile geri döner.

- [ ] **Step 1: Metodu ekle**

`handle()` içindeki dağıtıma bir dal:

```php
        if ($was === 'durum') {
            $this->zurueck($locale, $this->durum());
            return;
        }
```

Ve metot:

```php
    /** Aktiv/inaktiv. Hinweise halten nicht auf, aber sie werden gesagt. */
    private function durum(): string
    {
        $design = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        if ($design === null) {
            return 'fehler=quelle';
        }

        $ziel = (string) $design['status'] === 'active' ? 'inactive' : 'active';

        // Beim Abschalten fragt niemand. Beim Einschalten schon - und die
        // Hinweise werden hier neu gerechnet, nicht aus dem Formular geglaubt.
        if ($ziel === 'active' && !isset($_POST['bestaetigt'])) {
            $meldungen = Design::warnings($design);
            if ($meldungen !== []) {
                return 'frage=aktivieren&id=' . rawurlencode($design['id']) . '&n=' . count($meldungen);
            }
        }

        $design['status'] = $ziel;
        Design::save($design);

        return 'ok=' . ($ziel === 'active' ? 'aktiv' : 'inaktiv');
    }
```

- [ ] **Step 2: Şablona düğmeyi ve onay şeridini ekle**

Kartın eylem satırına:

```php
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="was" value="durum">
              <input type="hidden" name="quelle" value="<?= e($id) ?>">
              <button class="border border-sand-deep px-3 py-2 text-muted transition-colors hover:text-ink">
                <?= $akt ? ($tr ? 'Pasife al' : 'Deaktivieren') : ($tr ? 'Aktife al' : 'Aktivieren') ?>
              </button>
            </form>
```

Ve aynı kartın içinde, uyarıyla dönülmüşse onay şeridi:

```php
          <?php if ((string) ($_GET['frage'] ?? '') === 'aktivieren' && (string) ($_GET['id'] ?? '') === $id) : ?>
            <form method="post" class="space-y-2 border-l-2 border-gold p-3">
              <p class="text-sm text-ink">
                <?= $tr
                  ? 'Bu tasarımda ' . (int) ($_GET['n'] ?? 0) . ' uyarı var. Yine de yayınlansın mı?'
                  : 'Diese Vorlage hat ' . (int) ($_GET['n'] ?? 0) . ' Hinweise. Trotzdem veroeffentlichen?' ?>
              </p>
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="was" value="durum">
              <input type="hidden" name="quelle" value="<?= e($id) ?>">
              <input type="hidden" name="bestaetigt" value="1">
              <button class="bg-ink px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-cream hover:bg-gold">
                <?= $tr ? 'Uyarılarla yayınla' : 'Mit Hinweisen veroeffentlichen' ?>
              </button>
            </form>
          <?php endif; ?>
```

`$meldungen` dizisine iki satır daha:

```php
      'aktiv'   => $tr ? 'Yayında.' : 'Veroeffentlicht.',
      'inaktiv' => $tr ? 'Yayından kaldırıldı.' : 'Aus der Veroeffentlichung genommen.',
```

- [ ] **Step 3: Tarayıcıda kontrol et**

- Uyarısı olmayan bir taslakta "Aktivieren" → tek tıkla aktif, altın çerçeve
- Uyarısı olan bir tasarımda "Aktivieren" → kartta altın şerit: "… hat N Hinweise. Trotzdem veroeffentlichen?"
- "Mit Hinweisen veroeffentlichen" → aktif oluyor (**engellemiyor**)
- Aktif olanda "Deaktivieren" → soru sormadan pasife alıyor
- Sunucu tarafını kandırmayı dene: elle `?frage=` adresine gitmek hiçbir şeyi
  değiştirmiyor, çünkü değişiklik yalnızca POST'ta oluyor

- [ ] **Step 4: Bütün testleri çalıştır ve commit et**

```bash
cd php && php bin/test.php
git add php/src/Controllers/DesignAdminController.php php/templates/admin/designs.php
git commit -m "Publishing asks once, and counts the warnings itself

Switching a design off asks nothing. Switching it on with open warnings asks,
with the number in the question - and the number is recomputed on the server,
not believed from the form. Then it publishes anyway if that is the answer: the
checklist is there to be seen, not to hold the operator hostage."
```

---

## Task 8: Kapanış — sınırın ve eskisinin kanıtı

**Files:**
- Modify: `docs/superpowers/specs/2026-08-19-davetiye-v2-faz2-panel-design.md` (bitti ölçütü)

**Interfaces:** Kod üretmez. Task 1–7'nin tamamını doğrular.

- [ ] **Step 1: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: hepsi geçiyor, `design_admin` **atlanmamış**.

- [ ] **Step 2: Fazın sınırını diffle doğrula**

```bash
git diff master -- php/ | grep -n "\['box'\]\|\['canvas'\]\|\['sections'\]" | grep -v "^-" | head
```

Beklenen: yalnızca **okuyan** satırlar (şablonda `canvas.ratio` ile en-boy oranı
basmak gibi) ve testlerdeki kontroller. Bu üç alana **yazan** tek satır olmamalı.
Varsa dur: fazın sözü delinmiş demektir.

- [ ] **Step 3: Mevcut dosyalardan kaçı değişti**

```bash
git diff master --name-only | while read f; do git cat-file -e master:"$f" 2>/dev/null && echo "$f"; done
```

Beklenen tam liste (üç dosya): `php/public/index.php`, `php/src/Design.php`,
`php/src/Controllers/DesignController.php` — artı bu fazda dokunulan test ve
şablon dosyaları (`php/tests/design_css.php`, `php/templates/admin/designs.php`).
Bunların dışında bir mevcut dosya listedeyse sebebini yaz ya da geri al.

- [ ] **Step 4: Eskisinin bozulmadığını doğrula**

```bash
cd php && for p in /de/ /en/ /de/designs /de/designs/elysee /de/einladung \
  /de/preise /de/galerie /de/admin /de/admin/themen /de/admin/einladungen \
  /de/admin/kunden /de/admin/designs /tr/admin/designs /de/v2/designs \
  /de/v2/designs/elysee ; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:8080$p")
  echo "$code $p"
done
```

Beklenen: hepsi 200 (panel yolları giriş formunu basar, o da 200). 500 yok.

- [ ] **Step 5: Uçtan uca bir tur at**

Tek oturumda, sırayla:

1. Katalogda `Sage` temasından "Sage Probe" oluştur
2. Editörde `accent` rengini değiştir, kaydet
3. Genel sayfada (`/de/v2/designs/sage-probe`) rengin geldiğini gör
4. Katalogda aktife al — uyarı varsa soruyu ve "yine de yayınla"yı gör
5. Genel katalogda (`/de/v2/designs`) kartın çıktığını gör
6. Pasife al, genel katalogdan düştüğünü gör
7. Deneme kaydını sil: `DELETE FROM designs WHERE id = 'sage-probe';`

- [ ] **Step 6: Spec'in bitti ölçütünü işaretle ve commit et**

`docs/superpowers/specs/2026-08-19-davetiye-v2-faz2-panel-design.md` içindeki
"Bitti sayılma ölçütü" listesinde doğrulanan kutuları işaretle; doğrulanmayan
kaldıysa **işaretleme**, altına neden olmadığını yaz.

```bash
git add docs/superpowers/specs/2026-08-19-davetiye-v2-faz2-panel-design.md
git commit -m "Phase 2 is done, and the boundary is proved rather than promised

Every criterion checked in the browser, and the one that matters most checked in
the diff: no line in this phase writes box, canvas or sections. The editor edits
the surface, which is what it said it would do."
```

---

## Sonraki adım

Faz 2 bitince Faz 3 kendi spec'i ve planıyla başlar: müşteri tarafı — satış
sayfası, kategori süzgeci, tam ekran demo, beş adımlı sihirbaz (bu fazda
düzenlenen izin bayraklarını okuyacak), bölüm sistemi ve ödeme/kupon/RSVP
devralma.

Faz 2'den Faz 3'e devredilen iki not:

1. **Silme akışı** — "bu tasarımdan kaç davetiye çıktı" sayacıyla birlikte.
2. **`texture`** — her temada duruyor, PHP tarafında kimse okumuyor, yalnızca
   Next.js sürümü kullanıyor. Formatın bir desen katmanı kazanması gerekiyorsa
   kararı Faz 3'ün ihtiyacı versin.
