# Davetiye v2 — Faz 1 Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Davetiye tasarımını koddan veriye taşıyan doküman formatını ve onu ekrana basan renderer'ı kurmak; mevcut Élysée temasını bu formata aktarıp iki sürümü yan yana gösterilebilir hâle getirmek.

**Architecture:** `php/src/Design.php` yeni bir sınıf — `Themes.php`'nin yanında durur, ona dokunmaz. Tasarım artık `designs` tablosunda bir JSON dokümanı; içinde sıralı bir element listesi (`layers`), isimli renk/font jetonları (`palette`, `fonts`) ve element başına müşteri izinleri var. Renderer sunucuda scope'lu CSS + mutlak konumlu işaretleme üretir — mevcut süsleme motorunun kanıtlanmış yüzde-koordinat yaklaşımının aynısı. Her şey **ekleme**: mevcut hiçbir satır silinmez.

**Tech Stack:** PHP 8.3, MariaDB 10.4+, PDO, elle yazılmış autoloader (Composer yok), PHP'nin kendisiyle şablonlama (`View::page`), bağımlılıksız test koşucusu.

**Spec:** `docs/superpowers/specs/2026-08-19-davetiye-v2-design.md`

## Global Constraints

- **Sadece `php/` dizini.** `app/`, `lib/`, `components/`, `scripts/` (Next.js sürümü) bu planın tamamen dışında ve hiç değişmez.
- **Bu faz ekleme, değişim değil.** Bitişte `git diff master --stat -- php/` çıktısında mevcut dosyalardan yalnızca altısı görünmeli ve hepsi sadece satır **kazanmış** olmalı: `public/index.php`, `src/Admin.php`, `schema.sql`, `templates/partials/header.php`, `templates/partials/footer.php`, `data/dict.php`. Bu dosyalarda silinen ya da düzenlenen satır olmamalı. Task 11 bunu doğruluyor.
- **Composer yok.** Yeni bağımlılık eklenmez. ALL-INKL paylaşımlı hostingde composer garanti değil.
- **`php/config.php` repoda yok** (`.gitignore`'da; sunucuda oluşturuluyor). `src/bootstrap.php` o dosya yoksa `exit` ediyor. Bu yüzden `bin/test.php` bootstrap'i **çağırmaz**, kendi autoloader'ını kurar. Saf fonksiyon testleri veritabanı olmadan koşmalı.
- **Namespace `Atelier`**, dosya yolu sınıf adını izler (`Atelier\Design` → `php/src/Design.php`, `Atelier\Controllers\DesignController` → `php/src/Controllers/DesignController.php`).
- **Her sorgu hazırlanmış ifade.** Dize birleştirmeyle SQL kurulmaz.
- **Şablonlarda çıktı yalnız `e()` ile.** Ham HTML basılmaz.
- **CSP `script-src 'self'` korunur.** Renderer satır içi `<script>` ya da `onclick=` üretmez.
- **Ölçüler yüzde.** Yeni formatta piksel yok. `x`,`y` −50…150; `w` 1…200; `h` 0…200 (0 = otomatik); `rotate` −180…180; `opacity` 0…100; `delay`,`duration` 0…20000 ms.
- **Aralık dışı değer kırpılır, reddedilmez.** Bozuk bir sayı yüzünden tasarım açılmaz olmamalı.
- **Yorumlar Almanca** (mevcut `php/src/` kalıbı). Panel/site metinleri sözlükten.

---

## Dosya yapısı

| Dosya | Sorumluluk |
|---|---|
| `php/src/Design.php` | Doküman formatının tamamı: doldurma/kırpma (`complete`), CSS üretimi (`css`), işaretleme üretimi (`html`), dinamik alan çözümü (`bindValues`), uyarılar (`warnings`), tema göçü (`fromTheme`), kalıcılık (`find`/`all`/`save`) |
| `php/src/Controllers/DesignController.php` | İki genel rota: katalog ve önizleme. Hesap burada, şablonda değil |
| `php/templates/pages/designs-v2.php` | Katalog ızgarası |
| `php/templates/pages/design-preview.php` | Tek tasarımın tam sayfası |
| `php/templates/admin/designs.php` | Panelde salt okunur liste |
| `php/bin/test.php` | Bağımlılıksız test koşucusu |
| `php/bin/seed-designs.php` | Élysée'yi v2 dokümanına çevirip `designs` tablosuna yazar |
| `php/tests/design_complete.php` | `complete` ailesi |
| `php/tests/design_css.php` | `css` + scope güvenliği |
| `php/tests/design_html.php` | `html`, `bindValues`, `warnings`, XSS |
| `php/tests/design_from_theme.php` | `fromTheme` |
| `php/tests/design_store.php` | `find`/`all`/`save` (veritabanı varsa) |

`Design.php` tek dosya olarak kalıyor. Mevcut `Themes.php` 1052 satır ve evin kalıbı bu; `Design.php` bitişte 500 satır civarı olacak, bölmek için sebep yok.

---

## Task 1: Test koşucusu ve `Design::complete()`

**Files:**
- Create: `php/bin/test.php`
- Create: `php/tests/design_complete.php`
- Create: `php/src/Design.php`

**Interfaces:**
- Consumes: `Atelier\Themes::SPOTS` (`card`/`page`/`envelope`), `Atelier\Themes::MOVES` (`none`/`fade`/`rise`/`float`/`sway`/`zoom`) — ikisi de sınıf sabiti, veritabanı gerektirmez.
- Produces:
  - `Design::complete(array $doc): array`
  - `Design::completeElement(array $el): array`
  - `Design::completeBox(array $box): array`
  - Sabitler: `Design::CATEGORIES`, `Design::STATUSES`, `Design::TYPES`, `Design::ALIGNS`, `Design::BINDS`, `Design::PERMISSIONS`
  - Test yardımcıları: `assert_same($expected, $actual, string $label)`, `assert_true(bool $ok, string $label)`, `assert_contains(string $haystack, string $needle, string $label)`, `assert_not_contains(string $haystack, string $needle, string $label)`, `needs_db(): bool`

- [ ] **Step 1: Test koşucusunu yaz**

`php/bin/test.php`:

```php
<?php
declare(strict_types=1);

/**
 * Testlaeufer ohne Abhaengigkeit.
 *
 *   php bin/test.php              alle Dateien in tests/
 *   php bin/test.php design_css   nur passende
 *
 * Warum nicht src/bootstrap.php: das laedt config.php, und die liegt nicht im
 * Repository (sie entsteht erst auf dem Server). Die reinen Funktionen brauchen
 * weder Konfiguration noch Datenbank, also laedt dieser Laeufer nur den
 * Autoloader und die Kurzhelfer.
 */

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atelier\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/../src/View.php'; // e()
mb_internal_encoding('UTF-8');

$failures = [];
$passed = 0;

function assert_same(mixed $expected, mixed $actual, string $label): void
{
    global $failures, $passed;
    if ($expected === $actual) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    erwartet: " . var_export($expected, true)
                         . "\n    bekommen: " . var_export($actual, true);
}

function assert_true(bool $ok, string $label): void
{
    global $failures, $passed;
    if ($ok) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    erwartet: true";
}

function assert_contains(string $haystack, string $needle, string $label): void
{
    global $failures, $passed;
    if (str_contains($haystack, $needle)) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    fehlt: " . $needle . "\n    in: " . $haystack;
}

function assert_not_contains(string $haystack, string $needle, string $label): void
{
    global $failures, $passed;
    if (!str_contains($haystack, $needle)) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    darf nicht vorkommen: " . $needle . "\n    in: " . $haystack;
}

/** Datenbanktests laufen nur, wenn eine Konfiguration da ist. */
function needs_db(): bool
{
    return is_file(__DIR__ . '/../config.php');
}

$filter = $argv[1] ?? '';
$files = glob(__DIR__ . '/../tests/*.php') ?: [];
sort($files);

foreach ($files as $file) {
    if ($filter !== '' && !str_contains(basename($file), $filter)) {
        continue;
    }
    echo '— ', basename($file), "\n";
    require $file;
}

echo "\n";
if ($failures === []) {
    echo $passed, " Prüfungen bestanden.\n";
    exit(0);
}

foreach ($failures as $failure) {
    echo "FEHLER: ", $failure, "\n\n";
}
echo count($failures), " von ", $passed + count($failures), " fehlgeschlagen.\n";
exit(1);
```

- [ ] **Step 2: Başarısız testi yaz**

`php/tests/design_complete.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Fehlende Felder werden ergaenzt --- */

$doc = Design::complete([]);

assert_same('draft', $doc['status'], 'complete: status faellt auf draft');
assert_same(1, $doc['version'], 'complete: version beginnt bei 1');
assert_same([], $doc['layers'], 'complete: layers ist eine leere Liste');
assert_same([], $doc['sections'], 'complete: sections ist eine leere Liste');
assert_same('9:16', $doc['canvas']['ratio'], 'complete: canvas.ratio hat einen Standard');
assert_same(['de' => '', 'en' => ''], $doc['name'], 'complete: name ist zweisprachig');

/* --- Unbekannte Aufzaehlungswerte fallen auf den Standard --- */

$doc = Design::complete(['status' => 'quatsch', 'category' => 'quatsch']);

assert_same('draft', $doc['status'], 'complete: unbekannter status faellt zurueck');
assert_same('', $doc['category'], 'complete: unbekannte category faellt zurueck');

/* --- Kennung wird zu Kleinbuchstaben und Bindestrich --- */

assert_same('golden-garden', Design::complete(['id' => 'Golden Garden!'])['id'],
    'complete: id wird bereinigt');

/* --- Kasten: Standardwerte --- */

$box = Design::completeBox([]);

assert_same(['x' => 4, 'y' => 4, 'w' => 20, 'h' => 0, 'rotate' => 0, 'opacity' => 100], $box,
    'completeBox: Standardwerte');

/* --- Kasten: Werte ausserhalb des Bereichs werden beschnitten, nicht verworfen --- */

$box = Design::completeBox(['x' => -900, 'y' => 900, 'w' => 0, 'rotate' => 400, 'opacity' => 250]);

assert_same(-50, $box['x'], 'completeBox: x wird unten beschnitten');
assert_same(150, $box['y'], 'completeBox: y wird oben beschnitten');
assert_same(1, $box['w'], 'completeBox: w hat ein Minimum von 1');
assert_same(180, $box['rotate'], 'completeBox: rotate wird beschnitten');
assert_same(100, $box['opacity'], 'completeBox: opacity wird beschnitten');

/* --- Element: Standardwerte und Rueckfaelle --- */

$el = Design::completeElement(['id' => 'siegel', 'type' => 'quatsch', 'spot' => 'quatsch']);

assert_same('image', $el['type'], 'completeElement: unbekannter type faellt auf image');
assert_same('card', $el['spot'], 'completeElement: unbekannter spot faellt auf card');
assert_same('none', $el['motion']['move'], 'completeElement: Bewegung ist standardmaessig aus');
assert_same(1200, $el['motion']['duration'], 'completeElement: Dauer hat einen Standard');

/* --- Element ohne Kennung bekommt eine --- */

$el = Design::completeElement([]);
assert_true($el['id'] !== '', 'completeElement: leere id wird erzeugt');

/* --- Rechte: alles standardmaessig zu --- */

$el = Design::completeElement(['id' => 'name', 'permissions' => ['color' => true]]);

assert_same(true, $el['permissions']['color'], 'completeElement: gesetztes Recht bleibt');
assert_same(false, $el['permissions']['font'], 'completeElement: ungesetztes Recht ist zu');
assert_same(false, $el['permissions']['hide'], 'completeElement: Design ist zu geboren');

/* --- Unbekannter bind wird NICHT geloescht (warnings() soll ihn noch sehen) --- */

$el = Design::completeElement(['id' => 'x', 'bind' => 'gibt_es_nicht']);
assert_same('gibt_es_nicht', $el['bind'], 'completeElement: unbekannter bind bleibt stehen');

/* --- Aber Sonderzeichen im bind werden entfernt --- */

$el = Design::completeElement(['id' => 'x', 'bind' => 'couple names!']);
assert_same('couple_names', $el['bind'], 'completeElement: bind wird bereinigt');

/* --- layers laufen durch completeElement --- */

$doc = Design::complete(['layers' => [['id' => 'a', 'box' => ['x' => 999]]]]);
assert_same(150, $doc['layers'][0]['box']['x'], 'complete: layers werden mit ergaenzt');

/* --- Palette und Schriften --- */

$doc = Design::complete(['palette' => ['accent' => ['value' => '#B08D57']]]);

assert_same('#B08D57', $doc['palette']['accent']['value'], 'complete: Palettenwert bleibt');
assert_same(false, $doc['palette']['accent']['customer'], 'complete: Palette ist standardmaessig gesperrt');

$doc = Design::complete(['fonts' => ['display' => ['family' => 'Cormorant Garamond']]]);

assert_same('Cormorant Garamond', $doc['fonts']['display']['family'], 'complete: Schriftfamilie bleibt');
assert_same(false, $doc['fonts']['display']['customer'], 'complete: Schrift ist standardmaessig gesperrt');
```

- [ ] **Step 3: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_complete`
Beklenen: `Class "Atelier\Design" not found` hatası.

- [ ] **Step 4: En küçük uygulamayı yaz**

`php/src/Design.php`:

```php
<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Ein Design als Dokument.
 *
 * Der Unterschied zu Themes: dort hat ein Thema feste Faecher (Papier, Kuvert,
 * Siegel) und daneben eine Liste Schmuckelemente. Hier ist *alles* ein Element
 * in einer geordneten Liste, und Farben und Schriften sind benannte Marken, auf
 * die die Elemente zeigen. Deshalb laesst sich ein Design vollstaendig im Panel
 * bauen, ohne dass jemand eine Zeile Code schreibt.
 *
 * Themes.php bleibt unberuehrt und laeuft weiter.
 */
final class Design
{
    public const CATEGORIES = ['luxury', 'floral', 'modern', 'minimal', 'oriental', 'boho'];
    public const STATUSES   = ['draft', 'active', 'inactive'];
    public const TYPES      = ['image', 'text', 'photo', 'shape', 'button', 'video'];
    public const ALIGNS     = ['left', 'center', 'right'];

    /** Welche dynamischen Felder eine Vorlage einsetzen darf. */
    public const BINDS = [
        'couple_names', 'bride_name', 'groom_name', 'initials',
        'wedding_date', 'wedding_time', 'location_name', 'location_address',
        'invitation_text', 'hashtag',
    ];

    /** Was ein Kunde an einem einzelnen Element duerfen kann. */
    public const PERMISSIONS = ['edit', 'color', 'font', 'photo', 'text', 'hide'];

    /**
     * Grenzen des Kastens: [min, max, standard].
     *
     * Dieselben Zahlen wie in Themes::completeDecoration. Sie sind dort nicht
     * geraten, sondern am Handy gemessen worden – deshalb werden sie hier
     * uebernommen und nicht neu erfunden.
     */
    private const BOX = [
        'x'       => [-50, 150, 4],
        'y'       => [-50, 150, 4],
        'w'       => [1, 200, 20],
        'h'       => [0, 200, 0],
        'rotate'  => [-180, 180, 0],
        'opacity' => [0, 100, 100],
    ];

    /**
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function complete(array $doc): array
    {
        $defaults = [
            'id'        => '',
            'slug'      => '',
            'name'      => ['de' => '', 'en' => ''],
            'category'  => '',
            'tags'      => [],
            'cover'     => '',
            'family'    => '',
            'status'    => 'draft',
            'version'   => 1,
            'sort'      => 0,
            'canvas'    => ['ratio' => '9:16', 'safe' => 6],
            'palette'   => [],
            'fonts'     => [],
            'layers'    => [],
            'sections'  => [],
            'animation' => ['intro' => 'none', 'idle' => 'none', 'reveal' => 'up', 'particle' => 'none'],
        ];

        $doc = array_merge($defaults, $doc);

        $doc['id']   = self::key((string) $doc['id']);
        $doc['slug'] = self::key((string) $doc['slug']) ?: $doc['id'];

        $doc['name'] = [
            'de' => (string) (is_array($doc['name']) ? ($doc['name']['de'] ?? '') : ''),
            'en' => (string) (is_array($doc['name']) ? ($doc['name']['en'] ?? '') : ''),
        ];

        $doc['status']   = in_array($doc['status'], self::STATUSES, true) ? (string) $doc['status'] : 'draft';
        $doc['category'] = in_array($doc['category'], self::CATEGORIES, true) ? (string) $doc['category'] : '';
        $doc['version']  = max(1, (int) $doc['version']);
        $doc['sort']     = (int) $doc['sort'];
        $doc['cover']    = (string) $doc['cover'];
        $doc['family']   = self::key((string) $doc['family']);

        $doc['tags'] = array_values(array_filter(
            array_map(static fn (mixed $t): string => self::key((string) $t), (array) $doc['tags']),
            static fn (string $t): bool => $t !== ''
        ));

        $canvas = is_array($doc['canvas']) ? $doc['canvas'] : [];
        $doc['canvas'] = [
            'ratio' => (string) ($canvas['ratio'] ?? '9:16'),
            'safe'  => max(0, min(40, (int) ($canvas['safe'] ?? 6))),
        ];

        $palette = [];
        foreach ((array) $doc['palette'] as $key => $entry) {
            $key = self::key((string) $key);
            if ($key === '' || !is_array($entry)) {
                continue;
            }
            $label = is_array($entry['label'] ?? null) ? $entry['label'] : [];
            $palette[$key] = [
                'value'    => (string) ($entry['value'] ?? ''),
                'label'    => ['de' => (string) ($label['de'] ?? $key), 'tr' => (string) ($label['tr'] ?? $key)],
                'customer' => (bool) ($entry['customer'] ?? false),
            ];
        }
        $doc['palette'] = $palette;

        $fonts = [];
        foreach ((array) $doc['fonts'] as $key => $entry) {
            $key = self::key((string) $key);
            if ($key === '' || !is_array($entry)) {
                continue;
            }
            $fonts[$key] = [
                'family'     => (string) ($entry['family'] ?? ''),
                'size'       => max(1, min(400, (int) ($entry['size'] ?? 100))),
                'weight'     => max(100, min(900, (int) ($entry['weight'] ?? 400))),
                'tracking'   => max(-20, min(100, (int) ($entry['tracking'] ?? 0))),
                'lineHeight' => max(50, min(300, (int) ($entry['lineHeight'] ?? 120))),
                'customer'   => (bool) ($entry['customer'] ?? false),
            ];
        }
        $doc['fonts'] = $fonts;

        $doc['layers'] = array_values(array_map(
            [self::class, 'completeElement'],
            array_filter((array) $doc['layers'], 'is_array')
        ));

        $doc['sections'] = array_values(array_filter((array) $doc['sections'], 'is_array'));

        $animation = is_array($doc['animation']) ? $doc['animation'] : [];
        $doc['animation'] = [
            'intro'    => (string) ($animation['intro'] ?? 'none'),
            'idle'     => (string) ($animation['idle'] ?? 'none'),
            'reveal'   => (string) ($animation['reveal'] ?? 'up'),
            'particle' => (string) ($animation['particle'] ?? 'none'),
        ];

        return $doc;
    }

    /**
     * @param array<string,mixed> $el
     * @return array<string,mixed>
     */
    public static function completeElement(array $el): array
    {
        $defaults = [
            'id'          => '',
            'label'       => '',
            'type'        => 'image',
            'spot'        => 'card',
            'box'         => [],
            'src'         => '',
            'bind'        => '',
            'text'        => ['de' => '', 'en' => ''],
            'style'       => [],
            'motion'      => [],
            'permissions' => [],
        ];

        $el = array_merge($defaults, $el);

        $el['id']    = self::key((string) $el['id']) ?: bin2hex(random_bytes(4));
        $el['label'] = (string) $el['label'];
        $el['type']  = in_array($el['type'], self::TYPES, true) ? (string) $el['type'] : 'image';
        $el['spot']  = array_key_exists((string) $el['spot'], Themes::SPOTS) ? (string) $el['spot'] : 'card';
        $el['src']   = (string) $el['src'];

        // Unbekannte Namen bleiben stehen: warnings() soll sie melden koennen.
        // Nur Sonderzeichen fliegen raus – Leerzeichen werden zum Unterstrich
        // und nicht geloescht (dieselbe Logik wie key(), nur mit "_" statt "-").
        $bind = strtolower(trim((string) $el['bind']));
        $bind = (string) preg_replace('/\s+/', '_', $bind);
        $el['bind'] = (string) preg_replace('/[^a-z_]/', '', $bind);

        $text = is_array($el['text']) ? $el['text'] : [];
        $el['text'] = ['de' => (string) ($text['de'] ?? ''), 'en' => (string) ($text['en'] ?? '')];

        $el['box'] = self::completeBox(is_array($el['box']) ? $el['box'] : []);

        $style = is_array($el['style']) ? $el['style'] : [];
        $el['style'] = [
            'font'       => self::key((string) ($style['font'] ?? '')),
            'color'      => self::key((string) ($style['color'] ?? '')),
            'size'       => max(1, min(500, (int) ($style['size'] ?? 100))),
            'align'      => in_array($style['align'] ?? '', self::ALIGNS, true) ? (string) $style['align'] : 'center',
            'autoShrink' => (bool) ($style['autoShrink'] ?? true),
        ];

        $motion = is_array($el['motion']) ? $el['motion'] : [];
        $move = (string) ($motion['move'] ?? 'none');
        $el['motion'] = [
            'move'     => in_array($move, Themes::MOVES, true) ? $move : 'none',
            'delay'    => max(0, min(20000, (int) ($motion['delay'] ?? 0))),
            'duration' => max(0, min(20000, (int) ($motion['duration'] ?? 1200))),
        ];

        $permissions = is_array($el['permissions']) ? $el['permissions'] : [];
        $out = [];
        foreach (self::PERMISSIONS as $name) {
            // Ein Design wird zu geboren. Rechte werden aufgeschlossen, nicht
            // zugesperrt – so kann ein vergessenes Feld nichts kaputtmachen.
            $out[$name] = (bool) ($permissions[$name] ?? false);
        }
        $el['permissions'] = $out;

        return $el;
    }

    /**
     * @param array<string,mixed> $box
     * @return array<string,int>
     */
    public static function completeBox(array $box): array
    {
        $out = [];
        foreach (self::BOX as $key => [$min, $max, $default]) {
            $value = array_key_exists($key, $box) ? (int) $box[$key] : $default;
            $out[$key] = max($min, min($max, $value));
        }
        return $out;
    }

    /**
     * Kennung: nur Kleinbuchstaben, Ziffern und Bindestrich.
     *
     * Leerzeichen werden zum Bindestrich, nicht geloescht: aus „Golden Garden"
     * soll „golden-garden" werden und nicht „goldengarden". Die Kennung steht
     * in der Adresszeile, und dort ist der Bindestrich die Wortgrenze.
     */
    private static function key(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[\s_]+/', '-', $value);
        $value = (string) preg_replace('/[^a-z0-9-]/', '', $value);
        $value = (string) preg_replace('/-{2,}/', '-', $value);
        return trim($value, '-');
    }
}
```

- [ ] **Step 5: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_complete`
Beklenen: `PASS` — "… Prüfungen bestanden."

- [ ] **Step 6: Commit**

```bash
git add php/bin/test.php php/tests/design_complete.php php/src/Design.php
git commit -m "A design is a document now, and it fills its own gaps

Out-of-range numbers get clipped instead of rejected: a bad figure from
the panel should not be able to make a design refuse to open. An unknown
bind name survives on purpose, so warnings() can still report it."
```

---

## Task 2: `Design::css()`

**Files:**
- Create: `php/tests/design_css.php`
- Modify: `php/src/Design.php` (metot ekle)

**Interfaces:**
- Consumes: `Design::complete()` (Task 1).
- Produces: `Design::css(array $doc, string $scope): string` — scope'lu bir CSS metni. Element kuralları `<scope> .d-el-<id>`, palet jetonları `--d-<anahtar>`, font jetonları `--df-<anahtar>`, keyframe adları `d-move-<move>`.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_css.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

$doc = [
    'id'      => 'elysee',
    'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FBF6EE']],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond']],
    'layers'  => [
        ['id' => 'blume', 'type' => 'image', 'src' => '/uploads/blume.webp',
         'box' => ['x' => 10, 'y' => 20, 'w' => 30, 'rotate' => 5, 'opacity' => 80],
         'motion' => ['move' => 'fade', 'delay' => 200, 'duration' => 900]],
        ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
         'box' => ['x' => 0, 'y' => 40, 'w' => 100]],
    ],
];

$css = Design::css($doc, '.d-elysee');

/* --- Palette wird zu Eigenschaften --- */

assert_contains($css, '--d-accent:#B08D57', 'css: Palette wird zur Eigenschaft');
assert_contains($css, '--df-display:', 'css: Schrift wird zur Eigenschaft');

/* --- Jedes Element bekommt seine Regel, in Prozent --- */

assert_contains($css, '.d-elysee .d-el-blume{', 'css: Element bekommt eine Regel');
assert_contains($css, 'left:10%', 'css: x wird zu left in Prozent');
assert_contains($css, 'top:20%', 'css: y wird zu top in Prozent');
assert_contains($css, 'width:30%', 'css: w wird zu width in Prozent');
assert_contains($css, 'rotate(5deg)', 'css: rotate wird uebernommen');
assert_contains($css, 'opacity:0.8', 'css: opacity wird auf 0..1 gebracht');

/* --- Reihenfolge in der Liste ist die Stapelreihenfolge --- */

assert_contains($css, 'z-index:1', 'css: erstes Element liegt hinten');
assert_contains($css, 'z-index:2', 'css: zweites Element liegt davor');

/* --- Nur die benutzten Bewegungen werden mitgeschickt --- */

assert_contains($css, '@keyframes d-move-fade', 'css: benutzte Bewegung wird geschrieben');
assert_not_contains($css, '@keyframes d-move-sway', 'css: unbenutzte Bewegung wird nicht geschrieben');
assert_contains($css, '900ms', 'css: Dauer steht in der Regel');
assert_contains($css, '200ms', 'css: Verzoegerung steht in der Regel');

/* --- Wer Bewegung abbestellt hat, bekommt sie nicht --- */

assert_contains($css, 'prefers-reduced-motion', 'css: reduzierte Bewegung wird beachtet');

/* --- Ohne Bewegung auch kein reduced-motion-Block --- */

$still = Design::css(['id' => 'still', 'layers' => [['id' => 'a', 'src' => '/uploads/a.webp']]], '.d-still');
assert_not_contains($still, 'prefers-reduced-motion', 'css: ohne Bewegung kein Block');
assert_not_contains($still, '@keyframes', 'css: ohne Bewegung keine keyframes');

/* --- Ausbruch aus dem Bereich: eine Farbe darf die Regel nicht schliessen --- */

$boese = Design::css([
    'id'      => 'boese',
    'palette' => ['accent' => ['value' => '#fff} body{display:none} .x{color:red']],
], '.d-boese');

assert_not_contains($boese, 'body{display:none}', 'css: Farbe kann nicht aus der Regel ausbrechen');
assert_contains($boese, '--d-accent:transparent', 'css: unsaubere Farbe wird verworfen');

/* --- Dasselbe fuer Schriftnamen --- */

$boese = Design::css([
    'id'    => 'boese2',
    'fonts' => ['display' => ['family' => 'X} body{display:none} .y{a:b']],
], '.d-boese2');

assert_not_contains($boese, 'body{display:none}', 'css: Schriftname kann nicht ausbrechen');

/* --- rgba() bleibt erlaubt: die bestehenden Themen benutzen es --- */

$ok = Design::css([
    'id'      => 'ok',
    'palette' => ['edge' => ['value' => 'rgba(176,141,87,0.30)']],
], '.d-ok');

assert_contains($ok, '--d-edge:rgba(176,141,87,0.30)', 'css: rgba bleibt erhalten');

/* --- Jede Regel traegt den Bereich vorn --- */

$doc2 = Design::css(['id' => 'x', 'layers' => [['id' => 'a', 'src' => '/uploads/a.webp']]], '.d-x');

// Jedes Vorkommen des Elementwaehlers muss ein „.d-x " davor haben. Zaehlen
// statt suchen: eine einzige Regel ohne Bereich faellt so auf.
assert_same(
    substr_count($doc2, '.d-el-a'),
    substr_count($doc2, '.d-x .d-el-a'),
    'css: keine Regel ohne den Bereich davor'
);
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_css`
Beklenen: `Call to undefined method Atelier\Design::css()`.

- [ ] **Step 3: Uygulamayı yaz**

`php/src/Design.php` içine, `completeBox()`'tan sonra:

```php
    /* ------------------------------ Darstellung ------------------------------ */

    /**
     * Stilblock eines Designs.
     *
     * Alles wird vom Bereich ($scope) eingeschlossen: zwei Designs koennen auf
     * derselben Seite stehen (Katalog!), ohne sich gegenseitig umzufaerben.
     *
     * @param array<string,mixed> $doc
     */
    public static function css(array $doc, string $scope): string
    {
        $doc = self::complete($doc);
        $css = '';

        $vars = '';
        foreach ($doc['palette'] as $key => $entry) {
            $vars .= '--d-' . $key . ':' . self::safeColor((string) $entry['value']) . ';';
        }
        foreach ($doc['fonts'] as $key => $entry) {
            $vars .= '--df-' . $key . ':' . self::safeFont((string) $entry['family']) . ';';
        }
        if ($vars !== '') {
            $css .= $scope . '{' . $vars . '}';
        }

        $moves = [];

        foreach (array_values($doc['layers']) as $index => $el) {
            $selector = $scope . ' .d-el-' . $el['id'];
            $box = $el['box'];

            $css .= $selector . '{'
                . 'position:absolute;'
                . 'left:' . $box['x'] . '%;'
                . 'top:' . $box['y'] . '%;'
                . 'width:' . $box['w'] . '%;'
                . ($box['h'] > 0 ? 'height:' . $box['h'] . '%;' : 'height:auto;')
                . 'opacity:' . rtrim(rtrim(number_format($box['opacity'] / 100, 2, '.', ''), '0'), '.') . ';'
                . 'transform:rotate(' . $box['rotate'] . 'deg);'
                . 'transform-origin:center;'
                // Die Stapelreihenfolge ist die Reihenfolge der Liste. Ein
                // eigenes Feld dafuer waere eine zweite Wahrheit.
                . 'z-index:' . ($index + 1) . ';'
                . '}';

            if ($el['type'] === 'text') {
                $style = $el['style'];
                $css .= $selector . '{'
                    . ($style['font'] !== '' ? 'font-family:var(--df-' . $style['font'] . ');' : '')
                    . ($style['color'] !== '' ? 'color:var(--d-' . $style['color'] . ');' : '')
                    . 'font-size:' . $style['size'] . '%;'
                    . 'text-align:' . $style['align'] . ';'
                    . '}';
            }

            if ($el['motion']['move'] !== 'none') {
                $moves[$el['motion']['move']] = true;
                $css .= $selector . '{'
                    . 'animation:d-move-' . $el['motion']['move'] . ' '
                    . $el['motion']['duration'] . 'ms ease-out '
                    . $el['motion']['delay'] . 'ms both;'
                    . '}';
            }
        }

        foreach (array_keys($moves) as $move) {
            $css .= self::moveKeyframes((string) $move);
        }

        if ($moves !== []) {
            $css .= '@media (prefers-reduced-motion: reduce){' . $scope . ' .d-el{animation:none;}}';
        }

        return $css;
    }

    /** Dieselben Bewegungen wie im bestehenden Themenmotor, eigener Namensraum. */
    private static function moveKeyframes(string $move): string
    {
        return match ($move) {
            'fade'  => '@keyframes d-move-fade{from{opacity:0}}',
            'rise'  => '@keyframes d-move-rise{from{opacity:0;transform:translateY(14px)}}',
            'float' => '@keyframes d-move-float{from{opacity:0;transform:translateY(-10px)}}',
            'sway'  => '@keyframes d-move-sway{from{opacity:0;transform:rotate(-4deg)}}',
            'zoom'  => '@keyframes d-move-zoom{from{opacity:0;transform:scale(0.94)}}',
            default => '',
        };
    }

    /**
     * Eine Farbe, die keine Regel schliessen kann.
     *
     * Der Wert kommt aus dem Panel und landet ungefiltert in einem Stilblock.
     * Ohne diese Pruefung reicht ein „}" im Feld, um die Seite umzubauen.
     */
    private static function safeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) === 1) {
            return $value;
        }
        if (preg_match('/^rgba?\(\s*[0-9.,%\s]+\)$/', $value) === 1) {
            return $value;
        }
        return 'transparent';
    }

    /** Schriftname aus demselben Grund: nur Buchstaben, Ziffern, Leerzeichen, Komma, Bindestrich. */
    private static function safeFont(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^[A-Za-z0-9 ,\-]+$/', $value) !== 1) {
            return 'inherit';
        }
        return $value;
    }
```

- [ ] **Step 4: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_css`
Beklenen: PASS.

- [ ] **Step 5: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS — Task 1'in testleri de hâlâ geçiyor.

- [ ] **Step 6: Commit**

```bash
git add php/tests/design_css.php php/src/Design.php
git commit -m "A design paints itself, and cannot paint over its neighbour

Every rule carries the scope in front, so a catalogue page can show
thirty designs without them recolouring each other. Colours and font
names are filtered before they reach a style block: a single closing
brace in a panel field would otherwise rewrite the page."
```

---

## Task 3: `Design::html()`, `bindValues()` ve `warnings()`

**Files:**
- Create: `php/tests/design_html.php`
- Modify: `php/src/Design.php` (metot ekle)

**Interfaces:**
- Consumes: `Design::complete()` (Task 1), `Atelier\Dates::long(string $iso, ?string $locale)` (mevcut, saf — yalnız sabit dizi kullanıyor, veritabanı gerektirmiyor), `Atelier\e()` (mevcut, `src/View.php`).
- Produces:
  - `Design::html(array $doc, array $values, string $locale): string`
  - `Design::bindValues(array $data, string $locale): array`
  - `Design::warnings(array $doc): array` — her satır `['kind' => string, 'element' => string, 'detail' => string]`. `kind` şunlardan biri: `unknown_bind`, `missing_src`, `unknown_color`, `unknown_font`.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_html.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Dynamische Felder werden eingesetzt --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'ort', 'type' => 'text', 'bind' => 'location_name'],
]];

$werte = ['couple_names' => 'Ayşe & Mehmet', 'location_name' => 'Schloss Hohenstein'];
$html = Design::html($doc, $werte, 'de');

assert_contains($html, 'Ayşe &amp; Mehmet', 'html: bind wird eingesetzt');
assert_contains($html, 'Schloss Hohenstein', 'html: zweiter bind wird eingesetzt');
assert_contains($html, 'd-el-namen', 'html: Element traegt seine Klasse');

/* --- Ohne bind gilt der feste Text, in der Sprache der Seite --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'gruss', 'type' => 'text', 'text' => ['de' => 'Wir heiraten', 'en' => 'We are getting married']],
]];

assert_contains(Design::html($doc, [], 'de'), 'Wir heiraten', 'html: fester Text auf Deutsch');
assert_contains(Design::html($doc, [], 'en'), 'We are getting married', 'html: fester Text auf Englisch');

/* --- Ein Name ist ein Feld, kein Markup --- */

$doc = ['id' => 'x', 'layers' => [['id' => 'namen', 'type' => 'text', 'bind' => 'bride_name']]];
$html = Design::html($doc, ['bride_name' => '<script>alert(1)</script>'], 'de');

assert_not_contains($html, '<script>', 'html: Eingaben werden maskiert');
assert_contains($html, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

/* --- Unbekannter bind wird leer, nicht zum Namen des Feldes --- */

$doc = ['id' => 'x', 'layers' => [['id' => 'a', 'type' => 'text', 'bind' => 'gibt_es_nicht']]];
$html = Design::html($doc, [], 'de');

assert_not_contains($html, 'gibt_es_nicht', 'html: unbekannter bind wird nicht ausgegeben');

/* --- Bilder: nur Pfade, die wir selbst vergeben --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'gut', 'type' => 'image', 'src' => '/uploads/blume.webp'],
    ['id' => 'fremd', 'type' => 'image', 'src' => 'https://beispiel.de/bild.jpg'],
    ['id' => 'hoch', 'type' => 'image', 'src' => '/uploads/../../config.php'],
]];
$html = Design::html($doc, [], 'de');

assert_contains($html, '/uploads/blume.webp', 'html: eigener Pfad wird gezeigt');
assert_not_contains($html, 'beispiel.de', 'html: fremder Host wird verworfen');
assert_not_contains($html, '..', 'html: Verzeichniswechsel wird verworfen');

/* --- Kein Skript, keine Ereignisse: die CSP bleibt eng --- */

assert_not_contains($html, '<script', 'html: erzeugt keine Skriptbloecke');
assert_not_contains($html, 'onclick', 'html: erzeugt keine Ereignisse');

/* --- bindValues: aus den Feldern einer Einladung --- */

$werte = Design::bindValues([
    'bride' => 'Ayşe', 'groom' => 'Mehmet', 'date' => '2027-09-12',
    'time' => '18:00', 'venue' => 'Schloss Hohenstein', 'address' => 'Hauptstr. 1',
    'message' => 'Wir freuen uns', 'hashtag' => '#AyseMehmet',
], 'de');

assert_same('Ayşe & Mehmet', $werte['couple_names'], 'bindValues: Namen werden verbunden');
assert_same('Ayşe', $werte['bride_name'], 'bindValues: Braut');
assert_same('Mehmet', $werte['groom_name'], 'bindValues: Braeutigam');
assert_same('AM', $werte['initials'], 'bindValues: Initialen');
assert_same('18:00', $werte['wedding_time'], 'bindValues: Uhrzeit');
assert_same('Schloss Hohenstein', $werte['location_name'], 'bindValues: Ort');
assert_same('Hauptstr. 1', $werte['location_address'], 'bindValues: Adresse');
assert_same('Wir freuen uns', $werte['invitation_text'], 'bindValues: Text');
assert_true($werte['wedding_date'] !== '', 'bindValues: Datum wird ausgeschrieben');
assert_true(str_contains($werte['wedding_date'], '2027'), 'bindValues: Datum traegt das Jahr');

/* --- Fehlt ein Name, entsteht kein einsames Kaufmanns-Und --- */

$werte = Design::bindValues(['bride' => 'Ayşe', 'groom' => ''], 'de');
assert_same('Ayşe', $werte['couple_names'], 'bindValues: ohne zweiten Namen kein &');

/* --- Jeder bind aus der Liste kommt vor --- */

$werte = Design::bindValues([], 'de');
foreach (Design::BINDS as $bind) {
    assert_true(array_key_exists($bind, $werte), 'bindValues: ' . $bind . ' wird geliefert');
}

/* --- warnings: was ein Design noch braucht --- */

$meldungen = Design::warnings(['id' => 'x', 'layers' => [
    ['id' => 'a', 'type' => 'text', 'bind' => 'gibt_es_nicht'],
    ['id' => 'b', 'type' => 'image', 'src' => ''],
    ['id' => 'c', 'type' => 'text', 'style' => ['color' => 'fehlt']],
    ['id' => 'd', 'type' => 'text', 'style' => ['font' => 'fehlt']],
]]);

$arten = array_column($meldungen, 'kind');

assert_true(in_array('unknown_bind', $arten, true), 'warnings: unbekannter bind wird gemeldet');
assert_true(in_array('missing_src', $arten, true), 'warnings: fehlendes Bild wird gemeldet');
assert_true(in_array('unknown_color', $arten, true), 'warnings: fehlende Farbmarke wird gemeldet');
assert_true(in_array('unknown_font', $arten, true), 'warnings: fehlende Schriftmarke wird gemeldet');

/* --- Ein sauberes Design meldet nichts --- */

$sauber = Design::warnings([
    'id'      => 'x',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond']],
    'layers'  => [
        ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names',
         'style' => ['color' => 'accent', 'font' => 'display']],
        ['id' => 'b', 'type' => 'image', 'src' => '/uploads/blume.webp'],
    ],
]);

assert_same([], $sauber, 'warnings: sauberes Design meldet nichts');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_html`
Beklenen: `Call to undefined method Atelier\Design::html()`.

- [ ] **Step 3: Uygulamayı yaz**

`php/src/Design.php` içine, `safeFont()`'tan sonra:

```php
    /**
     * Die Elemente eines Designs als Markup.
     *
     * @param array<string,mixed> $doc
     * @param array<string,string> $values  Ergebnis von bindValues()
     */
    public static function html(array $doc, array $values, string $locale): string
    {
        $doc = self::complete($doc);
        $out = '';

        foreach ($doc['layers'] as $el) {
            $class = 'd-el d-el-' . $el['id'] . ' d-spot-' . $el['spot'];

            if ($el['type'] === 'text' || $el['type'] === 'button') {
                $text = self::resolveText($el, $values, $locale);
                if ($text === '') {
                    continue;
                }
                $out .= '<div class="' . e($class) . '">' . e($text) . '</div>';
                continue;
            }

            if ($el['type'] === 'image' || $el['type'] === 'photo') {
                $src = self::safeSrc($el['src']);
                if ($src === '') {
                    continue;
                }
                // Schmuck ist Schmuck: fuer die Vorlesesoftware nicht vorhanden.
                $out .= '<img class="' . e($class) . '" src="' . e($src) . '" alt="" aria-hidden="true">';
                continue;
            }

            if ($el['type'] === 'shape') {
                $out .= '<div class="' . e($class) . '" aria-hidden="true"></div>';
            }

            // video: Faz 3. Bis dahin wird das Element still uebersprungen.
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $el
     * @param array<string,string> $values
     */
    private static function resolveText(array $el, array $values, string $locale): string
    {
        if ($el['bind'] !== '') {
            // Unbekannter Name wird leer – niemals der Name selbst, sonst
            // steht „couple_names" auf der Einladung.
            return (string) ($values[$el['bind']] ?? '');
        }
        return (string) ($el['text'][$locale] ?? $el['text']['de'] ?? '');
    }

    /**
     * Nur Pfade, die wir selbst vergeben haben.
     *
     * Ein Design kann aus dem Panel kommen oder als JSON eingespielt werden.
     * Ohne diese Pruefung liesse sich ueber die Bildquelle ein fremder Host
     * einbinden – und das faellt genau dann auf, wenn es zu spaet ist.
     */
    private static function safeSrc(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        // Prozentkodierung aufloesen und vergleichen. Unsere eigenen Pfade
        // tragen keine - kommt eine an, stammt der Pfad nicht von uns. Ohne
        // diesen Schritt schluepft %2e%2e an der Punktpruefung vorbei, und
        // erst der Server macht daraus wieder einen Verzeichniswechsel.
        if (rawurldecode($src) !== $src) {
            return '';
        }

        // Erlaubt ist nur, was wir selbst vergeben. Damit fallen Nullbyte,
        // Tabulator, Steuerzeichen und der Doppelpunkt von allein weg.
        if (preg_match('#^/(uploads|assets)/[A-Za-z0-9/._-]+$#', $src) !== 1) {
            return '';
        }

        if (str_contains($src, '..')) {
            return '';
        }

        return $src;
    }

    /**
     * Die dynamischen Felder aus den Daten einer Einladung.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public static function bindValues(array $data, string $locale): array
    {
        $bride = trim((string) ($data['bride'] ?? ''));
        $groom = trim((string) ($data['groom'] ?? ''));
        $date  = trim((string) ($data['date'] ?? ''));

        $couple = $bride;
        if ($bride !== '' && $groom !== '') {
            $couple = $bride . ' & ' . $groom;
        } elseif ($bride === '') {
            $couple = $groom;
        }

        return [
            'couple_names'     => $couple,
            'bride_name'       => $bride,
            'groom_name'       => $groom,
            'initials'         => mb_substr($bride, 0, 1) . mb_substr($groom, 0, 1),
            'wedding_date'     => $date !== '' ? Dates::long($date, $locale) : '',
            'wedding_time'     => trim((string) ($data['time'] ?? '')),
            'location_name'    => trim((string) ($data['venue'] ?? '')),
            'location_address' => trim((string) ($data['address'] ?? '')),
            'invitation_text'  => trim((string) ($data['message'] ?? '')),
            'hashtag'          => trim((string) ($data['hashtag'] ?? '')),
        ];
    }

    /**
     * Was an einem Design noch fehlt.
     *
     * Faz 2 haengt daran die Veroeffentlichungspruefung; hier entsteht nur die
     * Liste. Sie meldet, statt zu blockieren – ein halbfertiges Design soll
     * sich ansehen lassen.
     *
     * @param array<string,mixed> $doc
     * @return list<array{kind:string,element:string,detail:string}>
     */
    public static function warnings(array $doc): array
    {
        $doc = self::complete($doc);
        $out = [];

        foreach ($doc['layers'] as $el) {
            if ($el['bind'] !== '' && !in_array($el['bind'], self::BINDS, true)) {
                $out[] = ['kind' => 'unknown_bind', 'element' => $el['id'], 'detail' => $el['bind']];
            }
            if (($el['type'] === 'image' || $el['type'] === 'photo') && self::safeSrc($el['src']) === '') {
                $out[] = ['kind' => 'missing_src', 'element' => $el['id'], 'detail' => $el['src']];
            }
            if ($el['style']['color'] !== '' && !isset($doc['palette'][$el['style']['color']])) {
                $out[] = ['kind' => 'unknown_color', 'element' => $el['id'], 'detail' => $el['style']['color']];
            }
            if ($el['style']['font'] !== '' && !isset($doc['fonts'][$el['style']['font']])) {
                $out[] = ['kind' => 'unknown_font', 'element' => $el['id'], 'detail' => $el['style']['font']];
            }
        }

        return $out;
    }
```

- [ ] **Step 4: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_html`
Beklenen: PASS.

- [ ] **Step 5: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS.

- [ ] **Step 6: Commit**

```bash
git add php/tests/design_html.php php/src/Design.php
git commit -m "The names on the card come from the invitation, not the template

An unknown binding resolves to nothing rather than to its own name, so a
typo in the panel never puts couple_names on a wedding invitation. Image
sources are held to paths we hand out ourselves."
```

---

## Task 4: `Design::fromTheme()`

**Files:**
- Create: `php/tests/design_from_theme.php`
- Modify: `php/src/Design.php` (metot ekle)

**Interfaces:**
- Consumes: `Atelier\Themes::complete(array $theme): array`, `Atelier\Themes::completeDecoration(array $deco): array` — ikisi de mevcut ve saf (veritabanı gerektirmiyor).
- Produces: `Design::fromTheme(array $theme): array` — tamamlanmış bir doküman.

Mevcut süslemedeki `front` boolean'ı z sırasına çevriliyor: `front:false` olanlar dizinin başına (arkada), `front:true` olanlar sonuna (önde). Sabit kart metinleri (isim, tarih, mekân) `fromTheme` tarafından **üretilmiyor** — onlar Task 6'daki seed betiğinin işi.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_from_theme.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

$thema = [
    'id'            => 'elysee',
    'name'          => 'Élysée',
    'bg'            => '#EFE7DC',
    'paper'         => '#FBF6EE',
    'accent'        => '#B08D57',
    'seal'          => '#B08D57',
    'image'         => '/uploads/hintergrund.webp',
    'imageOpacity'  => '60',
    'envelopeImage' => '/uploads/kuvert.webp',
    'decorations'   => [
        ['id' => 'blumeli', 'label' => 'Blume links', 'src' => '/uploads/blume-l.webp',
         'spot' => 'card', 'x' => '2', 'y' => '70', 'width' => '28', 'rotate' => '-6',
         'opacity' => '90', 'front' => false, 'move' => 'rise', 'delay' => '300', 'duration' => '900'],
        ['id' => 'siegel', 'label' => 'Siegel', 'src' => '/uploads/siegel.webp',
         'spot' => 'envelope', 'x' => '40', 'y' => '45', 'width' => '20', 'rotate' => '0',
         'opacity' => '100', 'front' => true, 'move' => 'zoom', 'delay' => '0', 'duration' => '600'],
    ],
];

$doc = Design::fromTheme($thema);

/* --- Kopfdaten --- */

assert_same('elysee', $doc['id'], 'fromTheme: id wird uebernommen');
assert_same('Élysée', $doc['name']['de'], 'fromTheme: Name wird uebernommen');

/* --- Farben werden zu Marken, gesperrt --- */

assert_same('#B08D57', $doc['palette']['accent']['value'], 'fromTheme: accent wird zur Marke');
assert_same('#FBF6EE', $doc['palette']['paper']['value'], 'fromTheme: paper wird zur Marke');
assert_same(false, $doc['palette']['accent']['customer'], 'fromTheme: Marken sind zunaechst gesperrt');

/* --- Hintergrund und Kuvert werden Elemente --- */

$nachId = [];
foreach ($doc['layers'] as $i => $el) {
    $nachId[$el['id']] = ['index' => $i] + $el;
}

assert_true(isset($nachId['bgimage']), 'fromTheme: Hintergrundbild wird ein Element');
assert_same('page', $nachId['bgimage']['spot'], 'fromTheme: Hintergrund liegt auf der Seite');
assert_same(60, $nachId['bgimage']['box']['opacity'], 'fromTheme: Deckkraft des Hintergrunds');

assert_true(isset($nachId['envimage']), 'fromTheme: Kuvertbild wird ein Element');
assert_same('envelope', $nachId['envimage']['spot'], 'fromTheme: Kuvertbild liegt auf dem Kuvert');

/* --- Schmuck wird verlustfrei uebernommen --- */

$blume = $nachId['blumeli'];

assert_same('image', $blume['type'], 'fromTheme: Schmuck wird ein Bildelement');
assert_same('card', $blume['spot'], 'fromTheme: spot bleibt');
assert_same('/uploads/blume-l.webp', $blume['src'], 'fromTheme: Quelle bleibt');
assert_same(2, $blume['box']['x'], 'fromTheme: x bleibt');
assert_same(70, $blume['box']['y'], 'fromTheme: y bleibt');
assert_same(28, $blume['box']['w'], 'fromTheme: width wird zu w');
assert_same(-6, $blume['box']['rotate'], 'fromTheme: rotate bleibt');
assert_same(90, $blume['box']['opacity'], 'fromTheme: opacity bleibt');
assert_same('rise', $blume['motion']['move'], 'fromTheme: Bewegung bleibt');
assert_same(300, $blume['motion']['delay'], 'fromTheme: Verzoegerung bleibt');
assert_same(900, $blume['motion']['duration'], 'fromTheme: Dauer bleibt');
assert_same('Blume links', $blume['label'], 'fromTheme: Beschriftung bleibt');

/* --- front:true landet HINTER front:false in der Liste, also weiter oben --- */

assert_true($nachId['siegel']['index'] > $nachId['blumeli']['index'],
    'fromTheme: front:true liegt spaeter in der Liste');
assert_true($nachId['bgimage']['index'] < $nachId['blumeli']['index'],
    'fromTheme: der Hintergrund liegt ganz hinten');

/* --- Schmuck ohne Quelle wird uebersprungen --- */

$doc = Design::fromTheme(['id' => 'x', 'decorations' => [['id' => 'leer', 'src' => '']]]);
assert_same([], $doc['layers'], 'fromTheme: Schmuck ohne Bild wird uebersprungen');

/* --- Der Animationsblock wird eins zu eins uebernommen --- */

$doc = Design::fromTheme([
    'id' => 'x', 'intro' => 'darkroom', 'idle' => 'breathe',
    'reveal' => 'mask', 'particle' => 'petal',
]);

assert_same('darkroom', $doc['animation']['intro'], 'fromTheme: intro bleibt');
assert_same('breathe', $doc['animation']['idle'], 'fromTheme: idle bleibt');
assert_same('mask', $doc['animation']['reveal'], 'fromTheme: reveal bleibt');
assert_same('petal', $doc['animation']['particle'], 'fromTheme: particle bleibt');

/* --- Das Ergebnis ist ein fertiges Dokument --- */

$doc = Design::fromTheme(['id' => 'x']);
assert_same('draft', $doc['status'], 'fromTheme: Ergebnis ist vollstaendig');
assert_same([], $doc['sections'], 'fromTheme: sections bleibt leer (Faz 3)');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_from_theme`
Beklenen: `Call to undefined method Atelier\Design::fromTheme()`.

- [ ] **Step 3: Uygulamayı yaz**

`php/src/Design.php` içine, `warnings()`'ten sonra:

```php
    /* -------------------------------- Umzug -------------------------------- */

    /**
     * Ein Thema aus dem bestehenden Motor als Dokument.
     *
     * Der Schmuck traegt dort schon alles, was ein Element hier braucht –
     * Prozentmasse, Ort, Bewegung. Deshalb ist das eine Umrechnung und keine
     * Neuanlage von Hand.
     *
     * Der feste Text der Karte (Namen, Datum, Ort) entsteht hier *nicht*: er
     * steckt heute im Kartenschablone und wird beim Aussaeen gesetzt, wo man
     * die Kaesten am fertigen Bild abmessen kann.
     *
     * @param array<string,mixed> $theme
     * @return array<string,mixed>
     */
    public static function fromTheme(array $theme): array
    {
        $theme = Themes::complete($theme);

        $palette = [];
        foreach ([
            'bg', 'paper', 'paperEdge', 'fg', 'soft', 'accent', 'accentSoft',
            'envelope', 'envelopeFlap', 'envelopeEdge', 'seal', 'sealText', 'petal',
        ] as $key) {
            $value = (string) ($theme[$key] ?? '');
            if ($value === '') {
                continue;
            }
            $palette[$key] = [
                'value'    => $value,
                'label'    => ['de' => $key, 'tr' => $key],
                'customer' => false,
            ];
        }

        $back = [];
        $front = [];

        if ((string) ($theme['image'] ?? '') !== '') {
            $back[] = [
                'id'     => 'bgimage',
                'label'  => 'Hintergrund',
                'type'   => 'image',
                'spot'   => 'page',
                'src'    => (string) $theme['image'],
                'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100,
                             'rotate' => 0, 'opacity' => (int) ($theme['imageOpacity'] ?? 100)],
                'motion' => ['move' => 'none', 'delay' => 0, 'duration' => 0],
            ];
        }

        if ((string) ($theme['envelopeImage'] ?? '') !== '') {
            $back[] = [
                'id'     => 'envimage',
                'label'  => 'Kuvert',
                'type'   => 'image',
                'spot'   => 'envelope',
                'src'    => (string) $theme['envelopeImage'],
                'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'rotate' => 0, 'opacity' => 100],
                'motion' => ['move' => 'none', 'delay' => 0, 'duration' => 0],
            ];
        }

        foreach ((array) ($theme['decorations'] ?? []) as $deco) {
            if (!is_array($deco)) {
                continue;
            }
            $deco = Themes::completeDecoration($deco);
            if ((string) $deco['src'] === '') {
                continue;
            }

            $element = [
                'id'     => (string) $deco['id'],
                'label'  => (string) $deco['label'],
                'type'   => 'image',
                'spot'   => (string) $deco['spot'],
                'src'    => (string) $deco['src'],
                'box'    => [
                    'x'       => (int) $deco['x'],
                    'y'       => (int) $deco['y'],
                    'w'       => (int) $deco['width'],
                    'h'       => 0,
                    'rotate'  => (int) $deco['rotate'],
                    'opacity' => (int) $deco['opacity'],
                ],
                'motion' => [
                    'move'     => (string) $deco['move'],
                    'delay'    => (int) $deco['delay'],
                    'duration' => (int) $deco['duration'],
                ],
            ];

            // Aus einem Ja/Nein wird eine Position in der Liste.
            if ($deco['front']) {
                $front[] = $element;
            } else {
                $back[] = $element;
            }
        }

        return self::complete([
            'id'        => (string) ($theme['id'] ?? ''),
            'slug'      => (string) ($theme['id'] ?? ''),
            'name'      => ['de' => (string) ($theme['name'] ?? ''), 'en' => (string) ($theme['name'] ?? '')],
            'family'    => (string) ($theme['family'] ?? ''),
            'version'   => max(1, (int) ($theme['version'] ?? 1)),
            'palette'   => $palette,
            'layers'    => array_merge($back, $front),
            'animation' => [
                'intro'    => (string) ($theme['intro'] ?? 'none'),
                'idle'     => (string) ($theme['idle'] ?? 'none'),
                'reveal'   => (string) ($theme['reveal'] ?? 'up'),
                'particle' => (string) ($theme['particle'] ?? 'none'),
            ],
        ]);
    }
```

- [ ] **Step 4: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_from_theme`
Beklenen: PASS.

- [ ] **Step 5: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS.

- [ ] **Step 6: Commit**

```bash
git add php/tests/design_from_theme.php php/src/Design.php
git commit -m "An old theme walks into the new format on its own feet

The ornaments already carried percent boxes, a place and a motion, so
this is arithmetic rather than re-entry by hand. The front flag becomes a
position in the list, which is the only place stacking order lives now."
```

---

## Task 5: Şema ve kalıcılık

**Files:**
- Modify: `php/schema.sql` (sona iki tablo)
- Create: `php/tests/design_store.php`
- Modify: `php/src/Design.php` (metot ekle)

**Interfaces:**
- Consumes: `Atelier\Db::run(string $sql, array $params): \PDOStatement`, `Atelier\Db::json(string $sql, array $params): ?array`, `Atelier\Db::all(string $sql, array $params): array`, `Atelier\Db::encode(mixed $value): string` — hepsi mevcut.
- Produces:
  - `Design::find(string $slug): ?array`
  - `Design::findById(string $id): ?array`
  - `Design::all(string $status = ''): array`
  - `Design::save(array $doc): void`

- [ ] **Step 1: Şemayı ekle**

`php/schema.sql` **sonuna** ekle (mevcut satırlara dokunma):

```sql

-- Davetiye v2: tasarım bir doküman. Eski temalar site_content içinde kalıyor,
-- bu tablo onların yanında duruyor.
CREATE TABLE IF NOT EXISTS designs (
  id         VARCHAR(64)  NOT NULL PRIMARY KEY,
  slug       VARCHAR(96)  NOT NULL,
  family     VARCHAR(64)  NOT NULL DEFAULT '',
  category   VARCHAR(48)  NOT NULL DEFAULT '',
  status     VARCHAR(16)  NOT NULL DEFAULT 'draft',
  version    INT UNSIGNED NOT NULL DEFAULT 1,
  sort       INT          NOT NULL DEFAULT 0,
  cover      VARCHAR(255) NOT NULL DEFAULT '',
  data       LONGTEXT     NOT NULL CHECK (JSON_VALID(data)),
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY designs_slug_idx (slug),
  INDEX designs_list_idx (status, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Davetiyenin kendisi Faz 3'te yazılacak. Tablo şimdiden kuruluyor:
-- design_snapshot'ı sonradan eklemek yayınlanmış davetiyeleri bozar.
CREATE TABLE IF NOT EXISTS invitations_v2 (
  slug            VARCHAR(96) NOT NULL PRIMARY KEY,
  design_id       VARCHAR(64) NOT NULL,
  design_snapshot LONGTEXT    NOT NULL CHECK (JSON_VALID(design_snapshot)),
  data            LONGTEXT    NOT NULL CHECK (JSON_VALID(data)),
  created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX invitations_v2_design_idx (design_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Başarısız testi yaz**

`php/tests/design_store.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

require_once __DIR__ . '/../src/bootstrap.php';

$id = 'testdesign';

// Sauber anfangen, falls ein früherer Lauf abgebrochen ist.
Atelier\Db::run('DELETE FROM designs WHERE id = ?', [$id]);

/* --- Speichern und wiederfinden --- */

Design::save([
    'id'      => $id,
    'slug'    => $id,
    'name'    => ['de' => 'Testdesign', 'en' => 'Test design'],
    'status'  => 'active',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'layers'  => [['id' => 'a', 'type' => 'image', 'src' => '/uploads/a.webp']],
]);

$doc = Design::find($id);

assert_true($doc !== null, 'store: gespeichertes Design wird gefunden');
assert_same('Testdesign', $doc['name']['de'], 'store: Name kommt zurueck');
assert_same('#B08D57', $doc['palette']['accent']['value'], 'store: Palette kommt zurueck');
assert_same(1, count($doc['layers']), 'store: Elemente kommen zurueck');
assert_same(1, $doc['version'], 'store: erste Fassung ist 1');

/* --- Rechte ueberleben den Weg durch die Datenbank --- */

Design::save([
    'id' => $id, 'slug' => $id, 'status' => 'active',
    'layers' => [['id' => 'a', 'type' => 'text', 'permissions' => ['color' => true, 'text' => true]]],
]);

$doc = Design::find($id);

assert_same(true, $doc['layers'][0]['permissions']['color'], 'store: Recht color bleibt erhalten');
assert_same(true, $doc['layers'][0]['permissions']['text'], 'store: Recht text bleibt erhalten');
assert_same(false, $doc['layers'][0]['permissions']['font'], 'store: ungesetztes Recht bleibt zu');

/* --- Ohne Aenderung keine neue Fassung --- */

$vorher = Design::find($id)['version'];
Design::save(Design::find($id));
assert_same($vorher, Design::find($id)['version'], 'store: gleicher Inhalt zaehlt nicht hoch');

/* --- Mit Aenderung schon --- */

$doc = Design::find($id);
$doc['name']['de'] = 'Anderer Name';
Design::save($doc);
assert_same($vorher + 1, Design::find($id)['version'], 'store: echte Aenderung zaehlt hoch');

/* --- all() filtert nach Zustand --- */

$aktive = Design::all('active');
$ids = array_column($aktive, 'id');
assert_true(in_array($id, $ids, true), 'store: all(active) enthaelt das Design');

$doc = Design::find($id);
$doc['status'] = 'inactive';
Design::save($doc);

$ids = array_column(Design::all('active'), 'id');
assert_true(!in_array($id, $ids, true), 'store: all(active) laesst inaktive weg');
assert_true(in_array($id, array_column(Design::all(), 'id'), true), 'store: all() zeigt alle');

/* --- Unbekanntes Design ist null, kein Fehler --- */

assert_same(null, Design::find('gibtesnicht'), 'store: unbekanntes Design ist null');

Atelier\Db::run('DELETE FROM designs WHERE id = ?', [$id]);
```

- [ ] **Step 3: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_store`
Beklenen: `config.php` yoksa "übersprungen" yazar ve geçer. Varsa `Call to undefined method Atelier\Design::save()`.

> Eğer yerelde veritabanı yoksa bu görevi tamamlamak için önce `config.example.php`'yi `config.php`'ye kopyalayıp yerel MariaDB bilgilerini gir ve `mysql < schema.sql` çalıştır. Testin atlanması bir başarı değildir — bu görev veritabanına karşı koşulmadan bitmiş sayılmaz.

- [ ] **Step 4: Uygulamayı yaz**

`php/src/Design.php` içine, `fromTheme()`'den sonra:

```php
    /* ------------------------------ Speicher ------------------------------ */

    /*
     * Warum die Spalte `data` heisst und nicht `doc`:
     *
     * Db::json() und Db::jsonList() lesen die Spalte mit dem festen Namen
     * `data` – sie nehmen ihn nicht aus der Abfrage. Alle elf JSON-Spalten des
     * bestehenden Schemas heissen deshalb so. Eine Spalte `doc` haette hier
     * still `null` geliefert, und zwar erst bei der ersten Abfrage, die
     * jemand spaeter ohne Alias schreibt.
     */

    /** @return array<string,mixed>|null */
    public static function find(string $slug): ?array
    {
        $doc = Db::json('SELECT data FROM designs WHERE slug = ? LIMIT 1', [$slug]);
        return $doc === null ? null : self::complete($doc);
    }

    /** @return array<string,mixed>|null */
    public static function findById(string $id): ?array
    {
        $doc = Db::json('SELECT data FROM designs WHERE id = ? LIMIT 1', [$id]);
        return $doc === null ? null : self::complete($doc);
    }

    /**
     * @param string $status Leer = alle.
     * @return list<array<string,mixed>>
     */
    public static function all(string $status = ''): array
    {
        $rows = $status === ''
            ? Db::jsonList('SELECT data FROM designs ORDER BY sort, slug')
            : Db::jsonList('SELECT data FROM designs WHERE status = ? ORDER BY sort, slug', [$status]);

        return array_values(array_map([self::class, 'complete'], $rows));
    }

    /**
     * Speichern und die Fassung derer hochzaehlen, die sich geaendert haben.
     *
     * Dieselbe Regel wie bei den Themen: eine verschickte Einladung haelt ihre
     * eigene Kopie fest, und die Nummer sagt, wie weit sie vom heutigen Stand
     * entfernt ist. Wuerde jedes Speichern hochzaehlen, waere die Nummer nichts
     * wert.
     *
     * @param array<string,mixed> $doc
     */
    public static function save(array $doc): void
    {
        $doc = self::complete($doc);
        $old = self::findById($doc['id']);

        if ($old !== null) {
            $a = $old;
            $b = $doc;
            unset($a['version'], $b['version']);
            $doc['version'] = $a === $b ? (int) $old['version'] : (int) $old['version'] + 1;
        }

        Db::run(
            'INSERT INTO designs (id, slug, family, category, status, version, sort, cover, data)
             VALUES (:id, :slug, :family, :category, :status, :version, :sort, :cover, :data)
             ON DUPLICATE KEY UPDATE
               slug = VALUES(slug), family = VALUES(family), category = VALUES(category),
               status = VALUES(status), version = VALUES(version), sort = VALUES(sort),
               cover = VALUES(cover), data = VALUES(data)',
            [
                'id'       => $doc['id'],
                'slug'     => $doc['slug'],
                'family'   => $doc['family'],
                'category' => $doc['category'],
                'status'   => $doc['status'],
                'version'  => $doc['version'],
                'sort'     => $doc['sort'],
                'cover'    => $doc['cover'],
                'data'     => Db::encode($doc),
            ]
        );
    }
```

- [ ] **Step 5: Testi çalıştır, geçtiğini gör**

Çalıştır: `cd php && php bin/test.php design_store`
Beklenen: PASS, "übersprungen" **değil**.

- [ ] **Step 6: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS.

- [ ] **Step 7: Commit**

```bash
git add php/schema.sql php/tests/design_store.php php/src/Design.php
git commit -m "Designs get a table of their own, and a version that means something

Saving the same content twice does not count up: the number exists so a
sent invitation can say how far it has drifted from today's design, and a
number that rises on every save says nothing.

invitations_v2 is created empty. Phase 3 fills it, but the snapshot
column has to exist first — adding it later is the change that breaks
invitations already in the world."
```

---

## Değişiklik notu — 2026-08-19, Task 5'ten sonra

Task 1–5 bitti. Task 6'ya geçmeden önce şu ortaya çıktı: **Élysée'de taşınacak tasarım verisi yok.**
Doğrulandı — `decorations: 0`, `image: ''`, `envelopeImage: ''`, `scene: 'botanical'`. 16 temanın
sadece birinde (`azur`) tek bir süsleme var. Élysée'nin görünüşünün tamamı kod: `Scenes.php`
konumlandırılmış SVG parçaları üretiyor, zarf/mühür/kart iskeleti davetiye şablonunda.

Yani `Design::fromTheme('elysee')` palet + **sıfır katman** veriyordu; eski Task 6 bunun üstüne üç
metin elementi ekliyordu. O hâliyle karşılaştırma "renkli zeminde 3 yazı" ile "zarf + mühür + kart +
botanik desen + taç yaprakları" arasında olurdu, ve spec'in §7 ölçütü ulaşılamazdı.

Karar (proje sahibi): **sanat da veriye taşınacak.** Eski Task 6–10 yerine Task 6–12 geliyor:

| Yeni | İçerik | Eski karşılığı |
|---|---|---|
| 6 | Format büyür: `shape` elementine `blur` + `radius` | — (yeni) |
| 7 | Sahne sanatını SVG varlıklarına dök | — (yeni) |
| 8 | Élysée'yi tohumla — palet, font, yıkama lekeleri, sahne katmanları, metinler | eski 6 |
| 9 | Rotalar, kontrolör, katalog + önizleme şablonu (üç yuva + açılış koreografisi) | eski 7 |
| 10 | Menü girişleri ve sözlük anahtarı | eski 8 |
| 11 | Panelde salt okunur liste | eski 9 |
| 12 | Yan yana karşılaştırma + "sadece ekleme" doğrulaması | eski 10 |

Bu genişlemenin sebebi kapsam kayması değil: kanıt testi formatı **yanlışladı**. İki eksik çıktı —
bulanık yuvarlak lekeler için `blur`/`radius` yok, ve sanat `vw` ile ölçekleniyorken format yüzde
kullanıyor. İkisi de Task 6 ve Task 12'de karşılanıyor.

---

## Task 6: `shape` elementi — bulanıklık ve köşe yuvarlaklığı

**Files:**
- Create: `php/tests/design_shape.php`
- Modify: `php/src/Design.php` (append only — `completeElement()` içindeki `style` bloğu ve `css()` içindeki shape dalı)

**Interfaces:**
- Consumes: `Design::complete()`, `Design::css()` (Task 1, 2).
- Produces: `style.blur` (0…100, px) ve `style.radius` (0…50, %) alanları; `css()` bunları yalnız `type: shape` elementleri için yazar.

Élysée'nin arka planındaki iki renk lekesi (`.scene-wash-a`, `.scene-wash-b`) `filter: blur(46px)` ve
`border-radius: 50%` ile çiziliyor. Format bugün ikisini de ifade edemiyor. Task 8 bunlara muhtaç.

> **Dikkat:** bu görev `Design.php`'de mevcut iki bloğa dokunuyor (`completeElement`'in `style`
> haritası, `css()`'in element döngüsü). "Sadece ekleme" kuralı **dosya bazında** geçerli —
> `php/src/Design.php` zaten bu fazda yaratılmış bir dosya, mevcut sistemden değil. Task 12'nin
> denetlediği altı dosyaya dokunulmuyor.

- [ ] **Step 1: Başarısız testi yaz**

`php/tests/design_shape.php`:

```php
<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Standardwerte: kein Weichzeichner, keine Rundung --- */

$el = Design::completeElement(['id' => 'a', 'type' => 'shape']);

assert_same(0, $el['style']['blur'], 'shape: ohne Angabe kein Weichzeichner');
assert_same(0, $el['style']['radius'], 'shape: ohne Angabe keine Rundung');

/* --- Werte werden uebernommen und beschnitten --- */

$el = Design::completeElement(['id' => 'a', 'type' => 'shape', 'style' => ['blur' => 46, 'radius' => 50]]);

assert_same(46, $el['style']['blur'], 'shape: blur bleibt');
assert_same(50, $el['style']['radius'], 'shape: radius bleibt');

$el = Design::completeElement(['id' => 'a', 'style' => ['blur' => 900, 'radius' => 900]]);

assert_same(100, $el['style']['blur'], 'shape: blur wird beschnitten');
assert_same(50, $el['style']['radius'], 'shape: radius wird beschnitten');

$el = Design::completeElement(['id' => 'a', 'style' => ['blur' => -20, 'radius' => -20]]);

assert_same(0, $el['style']['blur'], 'shape: blur wird unten beschnitten');
assert_same(0, $el['style']['radius'], 'shape: radius wird unten beschnitten');

/* --- css() schreibt sie, aber nur fuer shape --- */

$css = Design::css([
    'id'      => 'x',
    'palette' => ['petal' => ['value' => '#E2CFAF']],
    'layers'  => [
        ['id' => 'wash', 'type' => 'shape',
         'box' => ['x' => -16, 'y' => -10, 'w' => 58, 'h' => 58],
         'style' => ['color' => 'petal', 'blur' => 46, 'radius' => 50]],
    ],
], '.d-x');

assert_contains($css, 'filter:blur(46px)', 'css: shape bekommt den Weichzeichner');
assert_contains($css, 'border-radius:50%', 'css: shape bekommt die Rundung');
assert_contains($css, 'background:var(--d-petal)', 'css: shape nimmt seine Farbe als Flaeche');

/* --- Ein Bild bekommt nichts davon --- */

$css = Design::css([
    'id'     => 'y',
    'layers' => [
        ['id' => 'bild', 'type' => 'image', 'src' => '/uploads/a.webp',
         'style' => ['blur' => 46, 'radius' => 50]],
    ],
], '.d-y');

assert_not_contains($css, 'filter:blur', 'css: ein Bild wird nicht weichgezeichnet');
assert_not_contains($css, 'border-radius', 'css: ein Bild wird nicht gerundet');

/* --- blur 0 schreibt keine leere Regel --- */

$css = Design::css([
    'id'     => 'z',
    'layers' => [['id' => 's', 'type' => 'shape', 'style' => ['blur' => 0, 'radius' => 0]]],
], '.d-z');

assert_not_contains($css, 'filter:blur(0px)', 'css: kein Weichzeichner ohne Wert');
assert_not_contains($css, 'border-radius:0', 'css: keine Rundung ohne Wert');

/* --- Ein shape ohne Farbe bekommt keine Flaeche --- */

$css = Design::css([
    'id'     => 'w',
    'layers' => [['id' => 's', 'type' => 'shape', 'style' => ['blur' => 10]]],
], '.d-w');

assert_not_contains($css, 'background:var(--d-)', 'css: shape ohne Farbmarke bekommt keine Flaeche');
```

- [ ] **Step 2: Testi çalıştır, başarısız olduğunu gör**

Çalıştır: `cd php && php bin/test.php design_shape`
Beklenen: FAIL — `shape: ohne Angabe kein Weichzeichner` (dizi anahtarı yok).

- [ ] **Step 3: `completeElement()`'in style bloğuna iki alan ekle**

`php/src/Design.php` içinde, `completeElement()` metodundaki `$el['style'] = [...]` haritasına iki satır ekle. Mevcut satırları değiştirme, sadece ekle:

```php
        $el['style'] = [
            'font'       => self::key((string) ($style['font'] ?? '')),
            'color'      => self::key((string) ($style['color'] ?? '')),
            'size'       => max(1, min(500, (int) ($style['size'] ?? 100))),
            'align'      => in_array($style['align'] ?? '', self::ALIGNS, true) ? (string) $style['align'] : 'center',
            'autoShrink' => (bool) ($style['autoShrink'] ?? true),
            // Nur fuer shape: die weichen Farbflecken hinter der Karte. Ohne
            // die beiden laesst sich ein bestehendes Design nicht abbilden –
            // das ist beim Umzug von Élysée aufgefallen.
            'blur'       => max(0, min(100, (int) ($style['blur'] ?? 0))),
            'radius'     => max(0, min(50, (int) ($style['radius'] ?? 0))),
        ];
```

- [ ] **Step 4: `css()`'e shape dalını ekle**

`php/src/Design.php` içinde `css()` metodunda, mevcut `if ($el['type'] === 'text') { … }` bloğunun **hemen altına** ekle:

```php
            if ($el['type'] === 'shape') {
                $style = $el['style'];
                $rules = '';
                if ($style['color'] !== '') {
                    $rules .= 'background:var(--d-' . $style['color'] . ');';
                }
                if ($style['blur'] > 0) {
                    $rules .= 'filter:blur(' . $style['blur'] . 'px);';
                }
                if ($style['radius'] > 0) {
                    $rules .= 'border-radius:' . $style['radius'] . '%;';
                }
                if ($rules !== '') {
                    $css .= $selector . '{' . $rules . '}';
                }
            }
```

- [ ] **Step 5: Testleri çalıştır**

Çalıştır: `cd php && php bin/test.php design_shape`
Beklenen: PASS.

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS — Task 1–3'ün testleri de hâlâ geçiyor.

- [ ] **Step 6: Commit**

```bash
git add php/tests/design_shape.php php/src/Design.php
git commit -m "The format grows a blur, because a real design asked for one

Élysée's background is two blurred circles of colour. The document format
could not say that, which is the proof test doing its job: a shape now
carries blur and corner radius, and only a shape gets them."
```

---

## Task 7: Sahne sanatını varlık dosyalarına dök

**Files:**
- Create: `php/bin/export-scene-art.php`
- Create (betiğin çıktısı, git'e girer): `php/public/assets/designs/elysee-1.svg`, `-2.svg`, `-3.svg`

**Interfaces:**
- Consumes: mevcut `Atelier\Scenes::html(string $id, array $theme): string` ve `Atelier\Themes::find()`.
- Produces: her sahne parçası için tek başına duran bir SVG dosyası + konsola bir **ölçü tablosu** (sınıf adı → CSS'ten okunan geometri). Task 8 o tabloyu kullanıyor.

`Scenes::pieces()` **private**; onu public yapmıyoruz (bkz. Ruling 5). Bunun yerine public
`Scenes::html()` çağrılıp dönen işaretleme `DOMDocument` ile ayrıştırılıyor. Böylece
`php/src/Scenes.php` hiç değişmiyor.

- [ ] **Step 1: Dışa aktarıcıyı yaz**

`php/bin/export-scene-art.php`:

```php
<?php
declare(strict_types=1);

/**
 * Die gezeichnete Szene eines Themas als eigenstaendige SVG-Dateien.
 *
 *   php bin/export-scene-art.php elysee --dry
 *   php bin/export-scene-art.php elysee
 *
 * Warum ueberhaupt: die Szene ist heute Code. Scenes::html() setzt sie bei
 * jedem Aufruf neu zusammen, mit den Farben des Themas. Ein Design der zweiten
 * Fassung besteht aber aus Daten – also muss die Zeichnung einmal zu einer
 * Datei werden, auf die eine Ebene zeigen kann.
 *
 * Der Preis steht hier, damit ihn niemand spaeter suchen muss: die Farben der
 * Zeichnung frieren dabei ein. Sie folgen der Palette nicht mehr. Fuer den
 * Umzug ist das richtig – die echten Vorlagen kommen ohnehin als fertige
 * Dateien vom Grafiker.
 *
 * Scenes::pieces() ist private und bleibt es. Wir nehmen die oeffentliche
 * Scenes::html() und zerlegen ihre Ausgabe.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Scenes;
use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$id = '';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--dry') {
        $id = $arg;
    }
}
$dry = in_array('--dry', $argv, true);

if ($id === '') {
    exit("Aufruf: php bin/export-scene-art.php <themen-id> [--dry]\n");
}

$theme = Themes::find($id);
if ($theme === null) {
    exit("Thema „{$id}\" nicht gefunden.\n");
}

$scene = (string) ($theme['scene'] ?? '');
$html = Scenes::html($scene, $theme);

if (trim($html) === '') {
    exit("Thema „{$id}\" hat keine gezeichnete Szene (scene = „{$scene}\").\n");
}

$dom = new DOMDocument();
// Die Ausgabe ist ein Fragment und enthaelt Umlaute; ohne die Angabe liest
// DOMDocument sie als Latin-1 und macht aus „é" zwei Zeichen.
$ok = @$dom->loadHTML(
    '<?xml encoding="UTF-8"?><div id="wurzel">' . $html . '</div>',
    LIBXML_NOERROR | LIBXML_NOWARNING
);
if (!$ok) {
    exit("Die Szene liess sich nicht lesen.\n");
}

$dir = __DIR__ . '/../public/assets/designs';
if (!$dry && !is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    exit("Ordner {$dir} liess sich nicht anlegen.\n");
}

/** Was die Stilvorlage zu den Klassen sagt – Task 8 misst damit die Kaesten. */
$geometrie = [
    'scene-tl'     => 'width:38vw max:240px  top:0    left:0',
    'scene-tr'     => 'width:38vw max:240px  top:0    right:0',
    'scene-bl'     => 'width:32vw max:200px  bottom:0 left:0',
    'scene-br'     => 'width:32vw max:200px  bottom:0 right:0',
    'scene-left'   => 'width:42vw max:280px  top:6%   left:0',
    'scene-ml'     => 'width:20vw max:120px  top:18%  left:0',
    'scene-mr'     => 'width:22vw max:130px  top:30%  right:4%',
    'scene-top'    => 'width:100%            top:0    left:0',
    'scene-bottom' => 'width:100%            bottom:0 left:0',
    'scene-wide'   => 'width:46vw max:290px',
    'scene-flip'   => 'rotate:180deg',
    'scene-mirror' => 'scale:-1 1',
    'scene-updown' => 'scale:1 -1',
];

$svgs = $dom->getElementsByTagName('svg');
$n = 0;
$zeilen = [];

// getElementsByTagName liefert eine lebende Liste – erst einsammeln, dann
// schreiben, sonst verschiebt sich der Index unter den Fuessen.
$knoten = [];
foreach ($svgs as $svg) {
    $knoten[] = $svg;
}

foreach ($knoten as $svg) {
    $n++;
    $klassen = trim($svg->getAttribute('class'));
    $box = trim($svg->getAttribute('viewBox'));
    $stil = trim($svg->getAttribute('style'));

    // Die Datei traegt nur die Zeichnung: viewBox bleibt, Klassen und Stil der
    // Seite gehoeren nicht hinein – die Ebene bringt ihre eigene Geometrie mit.
    $inhalt = '';
    foreach ($svg->childNodes as $kind) {
        $inhalt .= $dom->saveXML($kind);
    }

    $datei = sprintf('%s-%d.svg', $id, $n);
    $pfad = $dir . '/' . $datei;

    $svgDatei = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'
        . ($box !== '' ? $box : '0 0 100 100') . '">' . $inhalt . '</svg>';

    if (!$dry) {
        file_put_contents($pfad, $svgDatei);
    }

    $hinweise = [];
    foreach (explode(' ', $klassen) as $klasse) {
        if (isset($geometrie[$klasse])) {
            $hinweise[] = $klasse . ' → ' . $geometrie[$klasse];
        }
    }

    $zeilen[] = [
        'datei'  => '/assets/designs/' . $datei,
        'klasse' => $klassen,
        'box'    => $box,
        'stil'   => $stil,
        'geo'    => $hinweise === [] ? '(keine Regel gefunden)' : implode(' + ', $hinweise),
        'bytes'  => strlen($svgDatei),
    ];
}

echo "\n", $dry ? 'Probelauf' : 'Geschrieben', ": ", count($zeilen), " Teile\n\n";

foreach ($zeilen as $z) {
    echo $z['datei'], "  (", $z['bytes'], " Bytes)\n";
    echo "  Klasse : ", $z['klasse'], "\n";
    echo "  viewBox: ", $z['box'], "\n";
    echo "  Stil   : ", $z['stil'], "\n";
    echo "  Lage   : ", $z['geo'], "\n\n";
}

echo "Die Farbflecken hinter der Szene stehen NICHT in diesen Dateien –\n";
echo "sie sind shape-Ebenen mit blur und radius (siehe Task 8).\n";
```

- [ ] **Step 2: Kuru çalıştır**

Çalıştır: `cd php && php bin/export-scene-art.php elysee --dry`
Beklenen: 3 parça listelenir, sınıfları `scene-tl`, `scene-tr`, `scene-bl scene-flip`, hepsinin viewBox'ı `0 0 100 100`, ve her birinin `animation-delay` içeren bir stili.

Üç parça çıkmıyorsa devam etme — `Scenes::html()` beklenenden farklı bir şey döndürmüş demektir, raporla.

- [ ] **Step 3: Yaz**

Çalıştır: `cd php && php bin/export-scene-art.php elysee`
Beklenen: `php/public/assets/designs/elysee-1.svg`, `-2.svg`, `-3.svg` oluşur.

- [ ] **Step 4: Dosyaların gerçekten geçerli SVG olduğunu doğrula**

Çalıştır:
```bash
cd php && for f in public/assets/designs/elysee-*.svg; do
  php -r '
    $f = $argv[1];
    $x = @simplexml_load_file($f);
    printf("%-40s %s  %s\n", $f, $x === false ? "UNGUELTIG" : "gueltig", $x === false ? "" : (string) $x["viewBox"]);
  ' "$f"
done
```
Beklenen: üçü de `gueltig`, viewBox `0 0 100 100`.

Ayrıca tarayıcıda tek tek aç ve gerçekten yaprak deseni göründüğünü gör:
`http://localhost:8080/assets/designs/elysee-1.svg`

- [ ] **Step 5: `.gitignore` bu klasörü engelliyor mu, kontrol et**

Çalıştır: `git check-ignore -v php/public/assets/designs/elysee-1.svg || echo "izleniyor - iyi"`

`uploads` klasörü ignore'da ama `assets` olmamalı. Engelleniyorsa raporla — varlıkların repoda durması gerekiyor, yükleme klasöründe değil.

- [ ] **Step 6: Commit**

```bash
git add php/bin/export-scene-art.php php/public/assets/designs/
git commit -m "The drawn scene becomes three files it can be pointed at

Scenes::html() rebuilds the artwork from code on every request. A design
made of data needs something a layer can reference, so the pieces are
written out once as standalone SVG.

The cost is stated in the script rather than discovered later: the colours
freeze at export and stop following the palette. For the move that is the
right trade — real templates arrive from the designer as files anyway.

Scenes::pieces() stays private; the exporter takes the public html() and
takes it apart, so php/src/Scenes.php is not touched at all."
```

---

## Task 8: Élysée'yi tohumla

**Files:**
- Create: `php/bin/seed-designs.php`

**Interfaces:**
- Consumes: `Design::fromTheme()` (Task 4), `Design::save()` / `findById()` (Task 5), `style.blur`/`style.radius` (Task 6), `/assets/designs/elysee-*.svg` (Task 7), `Themes::find()`.
- Produces: `designs` tablosunda `elysee` kaydı, `status = 'active'`.

Bu görev üç şeyi bir araya getiriyor: `fromTheme()`'in verdiği palet, Task 7'nin varlıkları, ve
elle ölçülen kutular. Ölçüm gerçek iş — tahmin edilen değerler başlangıç noktası.

- [ ] **Step 1: Eski kartı aç ve ölç**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`
Tarayıcıda aç: `http://localhost:8080/de/designs/elysee`

Geliştirici araçlarında kart alanının kutusunu al, sonra şunların her biri için kart alanına göre yüzde hesapla:

```
x = (element.left - kart.left) / kart.width  * 100
y = (element.top  - kart.top)  / kart.height * 100
w =  element.width             / kart.width  * 100
h =  element.height            / kart.height * 100
```

Ölçülecekler:
1. `.scene-wash-a` ve `.scene-wash-b` (bulanık renk lekeleri)
2. `.scene-tl`, `.scene-tr`, `.scene-bl` (yaprak parçaları)
3. İsim satırı, tarih satırı, mekân satırı

Dokuz satır not al. Aşağıdaki betikteki sayılar **başlangıç tahminidir**, ölçümle değiştirilecek.

> Not: sahne parçaları eski sayfada `vw` ile ölçekleniyor (`38vw`, üst sınır `240px`), yeni format
> ise kartın yüzdesi. İkisi genişlikle birlikte farklı davranır. **Masaüstü genişliğinde** ölç
> (üst sınırın devrede olduğu yer) ve Task 12'de dar ekrandaki sapmayı kaydet.

- [ ] **Step 2: Tohumlama betiğini yaz**

`php/bin/seed-designs.php`:

```php
<?php
declare(strict_types=1);

/**
 * Élysée als Dokument der zweiten Fassung anlegen.
 *
 *   php bin/seed-designs.php --dry   nur zeigen
 *   php bin/seed-designs.php         schreiben
 *
 * Das Thema selbst bleibt unberuehrt und laeuft weiter. Hier entsteht daneben
 * ein Eintrag in `designs`, damit sich beide nebeneinander ansehen lassen.
 *
 * Die Kaesten stehen als Zahlen hier und nicht in Design::fromTheme(): im
 * alten Motor liegen weder die Szene noch die Namen als Daten vor. Die Szene
 * kommt aus Scenes.php, die Namen aus dem Fluss des Kartensatzes. Beide lassen
 * sich nur am fertigen Bild abmessen – und eine gemessene Zahl gehoert dorthin,
 * wo jemand sie nachmessen kann.
 *
 * Voraussetzung: php bin/export-scene-art.php elysee ist gelaufen.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Design;
use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

$theme = Themes::find('elysee');
if ($theme === null) {
    exit("Thema „elysee\" nicht gefunden.\n");
}

foreach (['elysee-1', 'elysee-2', 'elysee-3'] as $stueck) {
    $pfad = __DIR__ . '/../public/assets/designs/' . $stueck . '.svg';
    if (!is_file($pfad)) {
        exit("Es fehlt {$stueck}.svg – erst „php bin/export-scene-art.php elysee\" laufen lassen.\n");
    }
}

$doc = Design::fromTheme($theme);

$doc['status']   = 'active';
$doc['category'] = 'luxury';
$doc['tags']     = ['creme', 'gold'];
$doc['sort']     = 1;
$doc['name']     = ['de' => 'Élysée', 'en' => 'Élysée'];

// Die Schriften des Themas als Marken.
$doc['fonts'] = [
    'display' => ['family' => 'Cormorant Garamond', 'size' => 100, 'weight' => 300,
                  'tracking' => 4, 'lineHeight' => 115, 'customer' => false],
    'body'    => ['family' => 'Jost', 'size' => 100, 'weight' => 400,
                  'tracking' => 0, 'lineHeight' => 150, 'customer' => false],
];

// Das Gold darf der Kunde spaeter waehlen.
if (isset($doc['palette']['accent'])) {
    $doc['palette']['accent']['customer'] = true;
    $doc['palette']['accent']['label'] = ['de' => 'Gold', 'tr' => 'Altın'];
}

/*
 * Alle Zahlen gemessen an /de/designs/elysee auf Desktopbreite.
 * Wer die Karte umbaut, misst nach.
 *
 * Reihenfolge ist Stapelreihenfolge: Farbflecken ganz hinten, dann die
 * Zeichnung, dann der Text.
 */
$ebenen = [
    // 1. Die weichen Farbflecken (frueher .scene-wash-a / -b)
    ['id' => 'washa', 'label' => 'Farbfleck oben links', 'type' => 'shape', 'spot' => 'page',
     'box' => ['x' => -16, 'y' => -10, 'w' => 58, 'h' => 58, 'rotate' => 0, 'opacity' => 30],
     'style' => ['color' => 'accentSoft', 'blur' => 46, 'radius' => 50],
     'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1600]],

    ['id' => 'washb', 'label' => 'Farbfleck unten rechts', 'type' => 'shape', 'spot' => 'page',
     'box' => ['x' => 62, 'y' => 56, 'w' => 52, 'h' => 52, 'rotate' => 0, 'opacity' => 34],
     'style' => ['color' => 'petal', 'blur' => 46, 'radius' => 50],
     'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1600]],

    // 2. Die gezeichnete Szene (frueher Scenes::html)
    ['id' => 'szenetl', 'label' => 'Blattwerk oben links', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-1.svg',
     'box' => ['x' => 0, 'y' => 0, 'w' => 62, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 200, 'duration' => 1600]],

    ['id' => 'szenetr', 'label' => 'Blattwerk oben rechts', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-2.svg',
     'box' => ['x' => 38, 'y' => 0, 'w' => 62, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 350, 'duration' => 1600]],

    ['id' => 'szenebl', 'label' => 'Blattwerk unten links', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-3.svg',
     'box' => ['x' => 0, 'y' => 48, 'w' => 52, 'h' => 0, 'rotate' => 180, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 500, 'duration' => 1600]],

    // 3. Der Text der Karte
    ['id' => 'namen', 'label' => 'Namen', 'type' => 'text', 'spot' => 'card',
     'bind' => 'couple_names',
     'box' => ['x' => 8, 'y' => 34, 'w' => 84, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'accent', 'size' => 260,
                 'align' => 'center', 'autoShrink' => true],
     'motion' => ['move' => 'fade', 'delay' => 400, 'duration' => 1200],
     'permissions' => ['color' => true]],

    ['id' => 'datum', 'label' => 'Datum', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_date',
     'box' => ['x' => 8, 'y' => 52, 'w' => 84, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'fg', 'size' => 100,
                 'align' => 'center', 'autoShrink' => true],
     'motion' => ['move' => 'fade', 'delay' => 700, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'ort', 'label' => 'Ort', 'type' => 'text', 'spot' => 'card',
     'bind' => 'location_name',
     'box' => ['x' => 8, 'y' => 62, 'w' => 84, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 90,
                 'align' => 'center', 'autoShrink' => true],
     'motion' => ['move' => 'fade', 'delay' => 900, 'duration' => 1000],
     'permissions' => []],
];

foreach ($ebenen as $ebene) {
    $doc['layers'][] = $ebene;
}

$doc = Design::complete($doc);

$meldungen = Design::warnings($doc);
foreach ($meldungen as $meldung) {
    echo "Hinweis: ", $meldung['kind'], " an „", $meldung['element'], "\"";
    echo $meldung['detail'] !== '' ? ' (' . $meldung['detail'] . ')' : '';
    echo "\n";
}

echo count($doc['layers']), " Ebenen, ", count($doc['palette']), " Farbmarken, ",
     count($doc['fonts']), " Schriftmarken.\n";

foreach ($doc['layers'] as $i => $ebene) {
    printf("  %2d. %-9s %-8s %-22s x%4d y%4d w%4d\n",
        $i + 1, $ebene['type'], $ebene['spot'], $ebene['label'] ?: $ebene['id'],
        $ebene['box']['x'], $ebene['box']['y'], $ebene['box']['w']);
}

if ($dry) {
    echo "\nProbelauf – nichts geschrieben.\n";
    exit(0);
}

$vorher = Design::findById($doc['id']);
Design::save($doc);
$nachher = Design::findById($doc['id']);

echo "\n", $vorher === null ? "Angelegt" : "Aktualisiert",
     ": ", $doc['id'], " (Fassung ", $nachher['version'], ")\n";
```

- [ ] **Step 3: Kuru çalıştır**

Çalıştır: `cd php && php bin/seed-designs.php --dry`
Beklenen: 8 katman listelenir (2 shape, 3 image, 3 text), uyarı çıkmaz.

Uyarı çıkarsa oku: `missing_src` varlık yolu yanlış demek, `unknown_color` palet anahtarı yok demek. Devam etmeden düzelt.

- [ ] **Step 4: Yaz ve iki kez çalıştırıp sürümü kontrol et**

Çalıştır: `cd php && php bin/seed-designs.php`
Beklenen: `Angelegt: elysee (Fassung 1)`

Çalıştır tekrar: `cd php && php bin/seed-designs.php`
Beklenen: `Aktualisiert: elysee (Fassung 1)` — sürüm **1** kalmalı. Artıyorsa `save()`'in karşılaştırması bozuk.

- [ ] **Step 5: Commit**

```bash
git add php/bin/seed-designs.php
git commit -m "Élysée moves in with its artwork, measured off the rendered card

Eight layers: two blurred colour fields, three pieces of drawn foliage,
three lines of text bound to the invitation's fields. Nothing about
Élysée is code on the new side any more.

The numbers are measured rather than derived, because neither the scene
nor the names have coordinates in the old engine — the scene comes from
Scenes.php and the names fall out of the flow of the card. They are
written here, where the next person can measure again."
```

---

## Task 9: Rotalar, kontrolör ve şablonlar

**Files:**
- Create: `php/src/Controllers/DesignController.php`
- Create: `php/templates/pages/designs-v2.php`
- Create: `php/templates/pages/design-preview.php`
- Modify: `php/public/index.php` (iki rota + bir `use`)

**Interfaces:**
- Consumes: `Design::all('active')`, `Design::find()`, `Design::css()`, `Design::html()`, `Design::bindValues()`, `Design::warnings()`; mevcut `I18n::locale()`, `I18n::path()`, `Seo::forPage()`, `Config::url()`, `View::page()`.
- Produces: `Atelier\Controllers\DesignController::index(): void`, `::preview(array $params): void`.

Önizleme şablonu **üç yuvayı** iç içe kuruyor — `page` (sayfa zemini), `envelope` (zarf), `card`
(kart) — ve açılış koreografisini `animation.intro` alanından sürüyor. Element `spot`'una göre
doğru kabın içine giriyor.

- [ ] **Step 1: `Design::html()`'i yuvaya göre süzülebilir yap**

`php/src/Design.php` içinde `html()` metodunun imzasına isteğe bağlı bir dördüncü parametre ekle ve döngünün başına bir satır koy. Mevcut çağrılar (dört parametresiz) aynı şekilde çalışmaya devam etmeli.

```php
    public static function html(array $doc, array $values, string $locale, string $spot = ''): string
    {
        $doc = self::complete($doc);
        $out = '';

        foreach ($doc['layers'] as $el) {
            // Leer = alle Ebenen. Sonst nur die eines Ortes: die Vorschau baut
            // Seite, Kuvert und Karte getrennt und schachtelt sie ineinander.
            if ($spot !== '' && $el['spot'] !== $spot) {
                continue;
            }

            $class = 'd-el d-el-' . $el['id'] . ' d-spot-' . $el['spot'];
            // … der Rest bleibt unveraendert
```

Ve `php/tests/design_html.php` sonuna ekle:

```php
/* --- Nach Ort filtern --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'a', 'type' => 'text', 'spot' => 'page', 'text' => ['de' => 'SEITE']],
    ['id' => 'b', 'type' => 'text', 'spot' => 'card', 'text' => ['de' => 'KARTE']],
    ['id' => 'c', 'type' => 'text', 'spot' => 'envelope', 'text' => ['de' => 'KUVERT']],
]];

$alle = Design::html($doc, [], 'de');
assert_contains($alle, 'SEITE', 'html: ohne Filter kommt alles');
assert_contains($alle, 'KARTE', 'html: ohne Filter kommt alles (2)');

$nurKarte = Design::html($doc, [], 'de', 'card');
assert_contains($nurKarte, 'KARTE', 'html: Filter laesst den Ort durch');
assert_not_contains($nurKarte, 'SEITE', 'html: Filter haelt andere Orte zurueck');
assert_not_contains($nurKarte, 'KUVERT', 'html: Filter haelt andere Orte zurueck (2)');

assert_same('', Design::html($doc, [], 'de', 'gibtesnicht'), 'html: unbekannter Ort ist leer');
```

Çalıştır: `cd php && php bin/test.php design_html` — geçmeli.

- [ ] **Step 2: Kontrolörü yaz**

`php/src/Controllers/DesignController.php`:

```php
<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Design;
use Atelier\I18n;
use Atelier\Seo;
use Atelier\View;

/**
 * Der Katalog der zweiten Fassung und eine einzelne Vorlage darin.
 *
 * Liegt bewusst neben InviteController statt darin: die beiden Fassungen
 * sollen sich nicht beruehren, solange verglichen wird.
 */
final class DesignController
{
    /** Testdaten fuer die Vorschau: lang genug, um Umbrueche zu zeigen. */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
    ];

    public function index(): void
    {
        $locale = I18n::locale();
        $designs = Design::all('active');

        $styles = '';
        foreach ($designs as $design) {
            $styles .= Design::css($design, '.d-' . $design['id']);
        }

        View::page('pages/designs-v2', [
            'locale'  => $locale,
            'path'    => I18n::path('/v2/designs', $locale),
            'meta'    => Seo::forPage('designs-v2', [
                'title'     => $locale === 'de' ? 'Designs (zweite Fassung)' : 'Designs (second version)',
                'noindex'   => true,
                'canonical' => Config::url() . I18n::path('/v2/designs', $locale),
            ]),
            'designs' => $designs,
            'styles'  => $styles,
            'values'  => Design::bindValues(self::BEISPIEL, $locale),
        ]);
    }

    /** @param array<string,string> $params */
    public function preview(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $design = Design::find($slug);

        if ($design === null || $design['status'] === 'inactive') {
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => I18n::locale(),
                'meta'   => ['title' => '404', 'noindex' => true],
            ]);
            return;
        }

        $locale = I18n::locale();
        $scope = '.d-' . $design['id'];
        $values = Design::bindValues(self::BEISPIEL, $locale);

        View::page('pages/design-preview', [
            'locale'   => $locale,
            'path'     => I18n::path('/v2/designs/' . $design['slug'], $locale),
            'meta'     => Seo::forPage('design-preview', [
                'title'     => $design['name'][$locale] ?? $design['name']['de'],
                'noindex'   => true,
                'canonical' => Config::url() . I18n::path('/v2/designs/' . $design['slug'], $locale),
            ]),
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            'styles'   => Design::css($design, $scope),
            // Drei Ebenenlisten statt einer: die Vorschau schachtelt sie.
            'seite'    => Design::html($design, $values, $locale, 'page'),
            'kuvert'   => Design::html($design, $values, $locale, 'envelope'),
            'karte'    => Design::html($design, $values, $locale, 'card'),
            'warnings' => Design::warnings($design),
        ]);
    }
}
```

- [ ] **Step 3: Önizleme şablonunu yaz**

`php/templates/pages/design-preview.php`:

```php
<?php
/**
 * Eine Vorlage der zweiten Fassung, in voller Groesse.
 *
 * Drei Ebenen ineinander, wie beim Original: die Seite traegt Hintergrund und
 * Zeichnung, darauf liegt das Kuvert, darin die Karte. Welche Ebene wohin
 * gehoert, sagt ihr `spot` – der Controller hat sie schon getrennt.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $seite
 * @var string $kuvert
 * @var string $karte
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var string $locale
 */

use function Atelier\e;

$ratio = str_replace(':', ' / ', (string) $design['canvas']['ratio']);
$intro = (string) $design['animation']['intro'];
?>
<style><?= $styles ?></style>

<section class="mx-auto max-w-3xl px-6 py-16">
  <h1 class="font-display text-3xl font-light text-ink">
    <?= e($design['name'][$locale] ?? $design['name']['de']) ?>
  </h1>
  <p class="mt-2 text-sm text-ink/60">
    <?= e($design['category']) ?> · Fassung <?= (int) $design['version'] ?> ·
    <?= count($design['layers']) ?> Ebenen · Auftakt: <?= e($intro) ?>
  </p>

  <?php if ($warnings !== []): ?>
    <ul class="mt-6 border border-amber-500/40 bg-amber-50 p-4 text-sm text-ink/80">
      <?php foreach ($warnings as $warning): ?>
        <li><?= e($warning['kind']) ?> — <?= e($warning['element']) ?><?php
          if ($warning['detail'] !== '') {
            echo ' (', e($warning['detail']), ')';
          }
        ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="mt-10 flex justify-center">
    <div class="<?= e($scope) ?> d-stage d-intro-<?= e($intro) ?> relative w-full max-w-sm overflow-hidden"
         style="aspect-ratio: <?= $ratio ?>; background: var(--d-bg, #EFE7DC);">

      <div class="d-page absolute inset-0"><?= $seite ?></div>

      <?php if (trim($kuvert) !== ''): ?>
        <div class="d-envelope absolute inset-0"><?= $kuvert ?></div>
      <?php endif; ?>

      <div class="d-card absolute inset-0"><?= $karte ?></div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: Katalog şablonunu yaz**

`php/templates/pages/designs-v2.php`:

```php
<?php
/**
 * Katalog der zweiten Fassung.
 *
 * @var list<array<string,mixed>> $designs
 * @var string $styles
 * @var array<string,string> $values
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;
?>
<style><?= $styles ?></style>

<section class="mx-auto max-w-6xl px-6 py-16">
  <h1 class="font-display text-3xl font-light text-ink">
    <?= $locale === 'de' ? 'Designs (zweite Fassung)' : 'Designs (second version)' ?>
  </h1>
  <p class="mt-2 max-w-xl text-sm text-ink/60">
    <?= $locale === 'de'
      ? 'Dieselben Vorlagen, aber vollständig aus Daten gebaut. Steht zum Vergleich neben der ersten Fassung.'
      : 'The same templates, built entirely from data. Here for comparison beside the first version.' ?>
  </p>

  <?php if ($designs === []): ?>
    <p class="mt-10 text-sm text-ink/60">
      <?= $locale === 'de' ? 'Noch kein Design angelegt.' : 'No design yet.' ?>
      <code>php bin/seed-designs.php</code>
    </p>
  <?php endif; ?>

  <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design): ?>
      <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], $locale)) ?>" class="group block">
        <div class="d-<?= e($design['id']) ?> relative overflow-hidden"
             style="aspect-ratio: <?= str_replace(':', ' / ', (string) $design['canvas']['ratio']) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <?= Design::html($design, $values, $locale) ?>
        </div>
        <p class="mt-3 font-display text-lg font-light text-ink group-hover:underline">
          <?= e($design['name'][$locale] ?? $design['name']['de']) ?>
        </p>
        <p class="text-xs uppercase tracking-[0.16em] text-ink/50">
          <?= e($design['category']) ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
```

- [ ] **Step 5: Rotaları ekle**

`php/public/index.php`, mevcut `/{locale}/designs/{thema}` satırının **hemen altına** (mevcut satırlara dokunma):

```php
// Zweite Fassung der Einladung – laeuft neben der ersten, bis verglichen ist.
$router->get('/{locale}/v2/designs', $page_(static fn (array $p) => (new DesignController())->index()));
$router->get('/{locale}/v2/designs/{slug}', $page_(static fn (array $p) => (new DesignController())->preview($p)));
```

Dosyanın başındaki `use` bloğuna ekle:

```php
use Atelier\Controllers\DesignController;
```

- [ ] **Step 6: Tarayıcıda kontrol et**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`

- [ ] `http://localhost:8080/de/v2/designs` → Élysée kartı ızgarada, yapraklar ve renk lekeleri görünüyor
- [ ] `http://localhost:8080/de/v2/designs/elysee` → tam sayfa, 8 katman, uyarı kutusu yok
- [ ] `http://localhost:8080/de/v2/designs/gibtesnicht` → 404
- [ ] `http://localhost:8080/de/designs/elysee` → **eski sayfa hâlâ çalışıyor**

- [ ] **Step 7: Bütün testleri çalıştır ve commit et**

Çalıştır: `cd php && php bin/test.php` — hepsi geçmeli.

```bash
git add php/src/Controllers/DesignController.php php/src/Design.php php/tests/design_html.php \
        php/templates/pages/designs-v2.php php/templates/pages/design-preview.php php/public/index.php
git commit -m "The second catalogue opens at its own address, in three layers

Two routes under a v2 prefix, deliberately ugly and deliberately easy to
delete. Both pages are noindex: they exist to be compared against.

html() can now be asked for one spot at a time, so the preview nests page,
envelope and card the way the original does instead of flattening them
into a single box."
```

---

## Task 10: Menü girişleri ve sözlük anahtarı

**Files:**
- Modify: `php/data/dict.php` (üç dile birer anahtar)
- Modify: `php/templates/partials/header.php` (bir satır)
- Modify: `php/templates/partials/footer.php` (bir satır)

**Interfaces:**
- Consumes: mevcut `Atelier\I18n::t(string $key): string`.
- Produces: menüde ikinci davetiye girişi. Yeni sözlük anahtarı `nav.invitation2`.

- [ ] **Step 1: Sözlük anahtarını ekle**

`php/data/dict.php` içinde **üç** `'nav' => [` bloğunun her birinde, `'invitation' => …` satırının hemen altına ekle:

`de` bölümünde:
```php
            'invitation2' => 'Einladung 2',
```

`en` bölümünde:
```php
            'invitation2' => 'Invitation 2',
```

`tr` bölümünde:
```php
            'invitation2' => 'Davetiye 2',
```

Mevcut hiçbir satırı değiştirme. Sadece üç satır eklenmiş olmalı.

- [ ] **Step 2: Başlık menüsüne ekle**

`php/templates/partials/header.php` içindeki `$extra` dizisine ikinci satırı ekle:

```php
// Abgesetzt und in Gold: das ist das eigene Produkt, nicht eine Seite mehr.
$extra = [
    [$p('/einladung'), I18n::t('nav.invitation')],
    // Zweite Fassung, zum Vergleich daneben. Eine der beiden faellt weg,
    // sobald entschieden ist.
    [$p('/v2/designs'), I18n::t('nav.invitation2')],
];
```

- [ ] **Step 3: Alt menüye ekle**

`php/templates/partials/footer.php` içinde `[$p('/einladung'), I18n::t('nav.invitation')],` satırının hemen altına:

```php
    [$p('/v2/designs'), I18n::t('nav.invitation2')],
```

- [ ] **Step 4: Sözlüğün üç dilde de dolu olduğunu doğrula**

Çalıştır:
```bash
cd php && php -r '$d = require "data/dict.php";
foreach (["de","en","tr"] as $l) {
    printf("%s: %s\n", $l, $d[$l]["nav"]["invitation2"] ?? "FEHLT");
}'
```
Beklenen:
```
de: Einladung 2
en: Invitation 2
tr: Davetiye 2
```
Herhangi biri `FEHLT` diyorsa devam etme.

- [ ] **Step 5: Tarayıcıda kontrol et**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`

Aç ve gör:
- `http://localhost:8080/de/` → menüde hem "Einladung" hem "Einladung 2" var, ikisi de altın grubunda
- `http://localhost:8080/en/` → "Invitation" ve "Invitation 2"
- Dar pencerede (mobil menü) ikisi de listede
- "Einladung 2"ye tıklayınca katalog açılıyor
- "Einladung"a tıklayınca **eski sihirbaz** açılıyor

- [ ] **Step 6: Commit**

```bash
git add php/data/dict.php php/templates/partials/header.php php/templates/partials/footer.php
git commit -m "Both invitations stand in the menu at once

The old entry keeps its place; the new one joins it in the same gold
group. Walking the two side by side is the whole point of this phase, and
a route nobody can reach is not something you compare against."
```

---

## Task 11: Panelde salt okunur liste

**Files:**
- Create: `php/templates/admin/designs.php`
- Modify: `php/src/Admin.php` (bir sekme kaydı)
- Modify: `php/src/Controllers/DesignController.php` (bir metot)
- Modify: `php/public/index.php` (bir rota)

**Interfaces:**
- Consumes: `Design::all()`, `Design::warnings()`; mevcut `Atelier\Admin::requireLogin(string $locale)`, `Admin::recordVisit(string $tab)`, `Admin::sidebar()`; panel şablon düzeni (`'layout' => 'admin/layout'`).
- Produces: `DesignController::admin(string $locale): void`.

- [ ] **Step 1: Sekmeyi kaydet**

`php/src/Admin.php` içindeki `TABS` dizisinde, `'group' => 'einladung'` olan son kaydın altına ekle:

```php
        ['href' => '/designs', 'group' => 'einladung', 'de' => 'Designs (v2)', 'tr' => 'Tasarımlar (v2)'],
```

- [ ] **Step 2: Kontrolör metodunu ekle**

`php/src/Controllers/DesignController.php` içine, `preview()`'dan sonra. `use` bloğuna `use Atelier\Admin;` ekle.

```php
    /**
     * Panel: was liegt in `designs`.
     *
     * Faz 1 zeigt nur. Bearbeiten kommt in Faz 2 – die Liste ist trotzdem
     * schon da, weil man sonst nicht sieht, ob das Aussaeen gewirkt hat.
     */
    public function admin(string $locale): void
    {
        Admin::requireLogin($locale);
        Admin::recordVisit('/designs');

        $designs = Design::all();
        $warnings = [];
        foreach ($designs as $design) {
            $warnings[$design['id']] = Design::warnings($design);
        }

        View::page('admin/designs', [
            'layout'   => 'admin/layout',
            'locale'   => $locale,
            'current'  => '/designs',
            'meta'     => ['title' => 'Designs (v2)', 'noindex' => true],
            'designs'  => $designs,
            'warnings' => $warnings,
        ]);
    }
```

- [ ] **Step 3: Panel şablonunu yaz**

`php/templates/admin/designs.php`:

```php
<?php
/**
 * Liste der Designs der zweiten Fassung. Nur lesen (Faz 1).
 *
 * @var list<array<string,mixed>> $designs
 * @var array<string,list<array{kind:string,element:string,detail:string}>> $warnings
 * @var string $locale
 */

use Atelier\I18n;
use function Atelier\e;

$tr = $locale === 'tr';
?>
<h1 class="text-xl font-medium"><?= $tr ? 'Tasarımlar (v2)' : 'Designs (v2)' ?></h1>

<p class="mt-2 max-w-2xl text-sm text-gray-600">
  <?= $tr
    ? 'Davetiye sisteminin ikinci sürümü. Bu listede düzenleme yok — Faz 1 yalnızca formatı ve gösterimi kuruyor. Yeni kayıt için: php bin/seed-designs.php'
    : 'Die zweite Fassung des Einladungssystems. Hier wird nur gelesen – Faz 1 baut Format und Darstellung. Neue Einträge: php bin/seed-designs.php' ?>
</p>

<?php if ($designs === []): ?>
  <p class="mt-8 text-sm text-gray-500">
    <?= $tr ? 'Henüz tasarım yok.' : 'Noch kein Design.' ?>
  </p>
<?php else: ?>
  <table class="mt-8 w-full text-sm">
    <thead class="border-b border-gray-300 text-left text-xs uppercase tracking-wide text-gray-500">
      <tr>
        <th class="py-2"><?= $tr ? 'Ad' : 'Name' ?></th>
        <th class="py-2"><?= $tr ? 'Kategori' : 'Kategorie' ?></th>
        <th class="py-2"><?= $tr ? 'Durum' : 'Zustand' ?></th>
        <th class="py-2"><?= $tr ? 'Sürüm' : 'Fassung' ?></th>
        <th class="py-2"><?= $tr ? 'Element' : 'Elemente' ?></th>
        <th class="py-2"><?= $tr ? 'Uyarı' : 'Hinweise' ?></th>
        <th class="py-2"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($designs as $design): ?>
        <?php $meldungen = $warnings[$design['id']] ?? []; ?>
        <tr class="border-b border-gray-200">
          <td class="py-3"><?= e($design['name']['de']) ?></td>
          <td class="py-3 text-gray-600"><?= e($design['category']) ?></td>
          <td class="py-3 text-gray-600"><?= e($design['status']) ?></td>
          <td class="py-3 text-gray-600"><?= (int) $design['version'] ?></td>
          <td class="py-3 text-gray-600"><?= count($design['layers']) ?></td>
          <td class="py-3 <?= $meldungen === [] ? 'text-gray-400' : 'text-amber-700' ?>">
            <?= $meldungen === [] ? '—' : count($meldungen) ?>
          </td>
          <td class="py-3 text-right">
            <a class="underline" target="_blank"
               href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>">
              <?= $tr ? 'Önizle' : 'Ansehen' ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
```

- [ ] **Step 4: Rotayı ekle**

`php/public/index.php` içinde, `/{locale}/admin/themen` satırının hemen altına:

```php
$router->any('/{locale}/admin/designs', $admin_(static fn (array $p) => (new DesignController())->admin($p['locale'])));
```

- [ ] **Step 5: Tarayıcıda kontrol et**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`

Aç ve gör:
- `http://localhost:8080/de/admin` → giriş yap
- Yan menüde "Davetiye" grubunda "Designs (v2)" var
- `http://localhost:8080/de/admin/designs` → Élysée satırı, sürüm 1, element sayısı, "Ansehen" bağlantısı çalışıyor
- `http://localhost:8080/tr/admin/designs` → başlıklar Türkçe
- `http://localhost:8080/de/admin/themen` → **eski tema sekmesi hâlâ çalışıyor**

- [ ] **Step 6: Commit**

```bash
git add php/templates/admin/designs.php php/src/Admin.php php/src/Controllers/DesignController.php php/public/index.php
git commit -m "The panel can see what the seeding did

Read-only on purpose: editing is Phase 2. But a table nobody can look at
is a table nobody can trust, and the warning count is the first place the
publish checklist will grow from."
```

---

## Task 12: Yan yana karşılaştırma ve "sadece ekleme" doğrulaması

**Files:**
- Modify: `docs/superpowers/specs/2026-08-19-davetiye-v2-design.md` (bitti ölçütü)

**Interfaces:**
- Consumes: Task 1–11'in tamamı. Kod üretmez.

- [ ] **Step 1: Bütün testleri çalıştır**

Çalıştır: `cd php && php bin/test.php`
Beklenen: PASS, `design_store` **atlanmamış** olmalı.

- [ ] **Step 2: Üç genişlikte yan yana karşılaştır**

Çalıştır: `cd php && php -S localhost:8080 -t public public/dev-router.php`

İki sekme: `http://localhost:8080/de/designs/elysee` (eski) ve `http://localhost:8080/de/v2/designs/elysee` (yeni).

- [ ] 1440 px (masaüstü) — kart oranı, yaprak parçalarının yeri, renk lekeleri, isim/tarih/mekân aynı
- [ ] 820 px (tablet) — aynı
- [ ] 390 px (telefon) — **sapma bekleniyor**, ölç ve yaz

> **Bilinen ve kabul edilen sapma.** Eski sahne `vw` ile ölçekleniyor (`38vw`, üst sınır `240px`);
> yeni format kartın yüzdesini kullanıyor. İkisi dar ekranda ayrışır. Ölçüt bu yüzden şudur:
> **masaüstü ve tablette aynı; telefonda sapma ölçülür ve rakamla kaydedilir.** Sapmayı gizlemek
> değil, büyüklüğünü bilmek istiyoruz — Faz 2'de formata `maxWidth` eklenip eklenmeyeceğine o
> rakam karar verecek.

Telefon genişliğindeki sapmayı şu şekilde yaz: her sahne parçası için eski ve yeni genişlik (px), ve fark yüzdesi.

- [ ] **Step 3: Uzun isimle kontrol et**

`DesignController::BEISPIEL` içinde geçici olarak `'bride' => 'Charlotte-Sophie'` yap.

- [ ] Uzun isim v2 tarafında kartın dışına taşmıyor
- [ ] Eski taraftaki davranışla aynı

Sonra geri al.

- [ ] **Step 4: Hareket kısıtlaması**

Geliştirici araçlarında `prefers-reduced-motion: reduce` aç.

- [ ] v2 sayfasında katmanlar animasyonsuz, yerinde
- [ ] Eski sayfa da öyle

- [ ] **Step 5: Neyin üretilmediğini açıkça yaz**

Karşılaştırmada eski sayfada olup yeni sayfada **olmayan** her şeyi listele. Beklenenler: mühür,
zarf açılma hareketi, taç yaprağı partikülleri, `sheen` idle hareketi. Her biri için tek satır:
ne, neden yok, hangi faza ait.

Bu liste Faz 2'nin girdisidir. Boş bırakmak "hepsi üretildi" demektir — öyle değilse yazılmalı.

- [ ] **Step 6: Eskisinin bozulmadığını doğrula**

```bash
cd php && for p in \
  /de/ /en/ /de/designs /de/designs/elysee /de/einladung \
  /de/preise /de/portfolio /de/kontakt /de/galerie \
  /de/admin/themen /de/admin/einladungen /de/admin/kunden /tr/admin/designs ; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "http://localhost:8080$p")
  echo "$code $p"
done
```

- [ ] Genel yollar 200; panel yolları 200 ya da 302; hiçbiri 500 değil

- [ ] **Step 7: Diff'in gerçekten ekleme olduğunu doğrula**

```bash
git diff master --stat -- php/
```

- [ ] Mevcut (bu daldan önce var olan) dosyalardan yalnızca altısı listede: `public/index.php`, `src/Admin.php`, `schema.sql`, `templates/partials/header.php`, `templates/partials/footer.php`, `data/dict.php`

```bash
git diff master -- php/public/index.php php/src/Admin.php php/schema.sql \
  php/templates/partials/header.php php/templates/partials/footer.php php/data/dict.php \
  | grep '^-' | grep -v '^---'
```

- [ ] Çıktı **boş**.

```bash
git diff master --stat -- app lib components scripts
```

- [ ] Çıktı **boş**.

- [ ] **Step 8: Spec'i güncelle ve commit et**

`docs/superpowers/specs/2026-08-19-davetiye-v2-design.md` içindeki "Bitti sayılma ölçütü" bölümünü gerçekleşen hâliyle güncelle: doğrulanan kutuları işaretle, telefon sapmasını rakamıyla yaz, Step 5'teki "üretilmeyenler" listesini ekle.

```bash
git add docs/superpowers/specs/2026-08-19-davetiye-v2-design.md
git commit -m "Both Élysées compared, and the gap written down rather than glossed

Desktop and tablet match. The phone diverges by construction — the old
scene scales in vw with a pixel cap, the new one in percent of the card —
so the criterion measures that gap instead of pretending it is not there.
Whatever was not reproduced is listed by name and handed to Phase 2."
```

---

## Sonraki adım

Faz 1 bitti. Faz 2 (panelde tasarım kataloğu ve form editörü) kendi spec'i ve planıyla başlar.

İki şey Faz 2'nin ilk masasında duruyor:

1. **İkinci bir tasarım aktarılmalı** — format yalnızca Élysée ile sınandı. Noir en farklı olanı
   (koyu zemin, `deco` sahnesi, `spark` partikülü).
2. **Task 12 Step 5'in listesi** — mühür, zarf hareketi, partiküller. Hepsi tasarım verisi olmalı,
   şablon süsü değil.

Ve formatın bilinen eksiği: sahne parçaları için `maxWidth`. Task 12'nin telefon ölçümü buna
karar verecek.
