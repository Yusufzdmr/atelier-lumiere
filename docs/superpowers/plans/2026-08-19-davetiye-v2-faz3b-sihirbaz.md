# Davetiye v2 — Faz 3B: Sihirbaz — Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Müşteri bir v2 tasarımı seçip sihirbazdan geçsin ve sonunda
`/{locale}/v2/einladung/{slug}` adresinde gerçek bir davetiye dursun.

**Architecture:** Sihirbaz "değişiklik listesi" saklamaz. Müşterinin izinli
seçimleri tasarım dokümanına uygulanır (`DesignWizard::personalize()`) ve sonuç
`invitations_v2.design_snapshot` olarak dondurulur. Davetiyeyi ekrana basmak
böylece Faz 1'in kendisidir: `Design::css()` + `Design::html()`, renderer'a tek
satır eklenmez. Adım listesi de sabit değil — `DesignWizard::steps()` onu
dokümandan türetir.

**Tech Stack:** PHP 8.3, MariaDB, kendi yönlendiricisi (`public/index.php`),
kendi şablon motoru (`View::page`/`View::partial`), bağımlılıksız test
çalıştırıcısı (`php bin/test.php`). Composer yok, Node yok — yayın hedefi
ALL-INKL paylaşımlı hosting.

**Spec:** `docs/superpowers/specs/2026-08-19-davetiye-v2-faz3b-sihirbaz-design.md`

## Global Constraints

- **Yalnızca `php/` altında çalışılır.** `app/`, `lib/`, `scripts/` (Next.js
  tarafı) bu planın dışında ve hiç değişmez.
- **Eski motora dokunulmaz.** Şu dosyalar diff'te **hiç geçmemeli**:
  `php/src/Controllers/InviteController.php`, `php/templates/pages/invite-wizard.php`,
  `php/src/Invitations.php`, `php/src/Themes.php`, `php/src/Pricing.php`,
  `php/templates/pages/designs.php`.
- **`invitations_v2` şeması değişmez:** `(slug, design_id, design_snapshot, data, created_at)`.
  Yeni alan gerekiyorsa `data` JSON'unun içine girer.
- **`Design.php`'de yalnızca iki tür değişiklik, ikisi de adı geçen görevde:**
  (1) `safeColor()`, `safeFont()` ve `safeSrc()` `private` → `public` (Görev 3)
  — **gövdeleri değişmez**, yalnızca görünürlük ve doküman yorumu. Üçü de
  müşteri değerinin belgeye inmeden önce süzülmesi için gerekli: spec §9'un
  doktrini "yazım anında temizle, basım anında değil"; `safeSrc()` bu listeye
  Görev 3'ün adversaryal incelemesinden sonra eklendi, çünkü fotoğraf yolu tek
  başına dışarıda kalmıştı. (2) `html()` metin elementine `data-bind` niteliği
  basar (Görev 9) — canlı önizlemenin `bind` haritasını tarayıcıya ayrıca
  göndermemesi için. Başka satır değişmez.
- **Testler veritabanısız çalışır.** `bin/test.php` `config.php` yüklemez;
  `tests/design_wizard.php` yalnızca saf fonksiyon test eder. Veritabanı gerektiren
  test `needs_db()` ile korunur.
- **Sözlük anahtarı eklenir, mevcut anahtar değişmez.** Her yeni anahtar
  `data/dict.php` içindeki **üç** dil kümesine de (`de`, `en`, `tr`) girer.
- **Yorumlar Almanca**, mevcut `php/src` üslubuyla: ne yaptığını değil **neden**
  öyle olduğunu anlatır.
- **Kaçış zorunlu:** şablonda basılan her değer `e()` içinden geçer.
- **3B hiçbir yerden bağlantılı değildir.** `Design::creatable()` ve onu kullanan
  şablonlar bu planda **değişmez**; vitrindeki düğme eski sihirbaza bakmaya
  devam eder (spec §3.1).

---

### Task 1: `DesignWizard::choices()` — tasarım neyi sunuyor

Sihirbazın sorabileceği her şeyin tek kaynağı. Şablon belgeye kendisi bakmaz.

**Files:**
- Create: `php/src/DesignWizard.php`
- Test: `php/tests/design_wizard.php`

**Interfaces:**
- Consumes: `Design::complete()`, `Design::BINDS`, `Design::PERMISSIONS` (mevcut)
- Produces:
  - `DesignWizard::FIELD_ORDER: array<int,string>` — `['bride','groom','date','time','venue','address','message','hashtag']`
  - `DesignWizard::choices(array $doc): array` — şu şekli döndürür:
    ```php
    [
      'fields'  => ['bride', 'groom', 'date'],        // FIELD_ORDER sırasında
      'palette' => ['accent' => ['value'=>'#B08D57','label'=>[...],'customer'=>true]],
      'fonts'   => ['script' => ['family'=>'Great Vibes', ...,'customer'=>true]],
      'layers'  => ['name-1' => ['color'=>true,'font'=>false,'text'=>false,'photo'=>false,'hide'=>true]],
    ]
    ```

- [ ] **Step 1: Write the failing test**

`php/tests/design_wizard.php` oluştur:

```php
<?php
declare(strict_types=1);

use Atelier\DesignWizard;

/*
 * Was darf der Kunde - und was fragt der Assistent ueberhaupt?
 *
 * Zwei Mechanismen, die leicht verwechselt werden: die Felder kommen aus den
 * bind-Namen, die das Design benutzt. Die Extras kommen aus den Rechten. Wer
 * nur die Rechte liest, bekommt einen leeren Assistenten - im heutigen
 * Bestand steht fast jedes Recht auf false.
 */

/** Ein Dokument mit genau den Ebenen, die der Test braucht. */
function wizard_doc(array $layers, array $palette = [], array $fonts = []): array
{
    return [
        'id' => 'test', 'slug' => 'test',
        'palette' => $palette,
        'fonts'   => $fonts,
        'layers'  => $layers,
    ];
}

$doc = wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'datum', 'type' => 'text', 'bind' => 'wedding_date'],
]);

$w = DesignWizard::choices($doc);

assert_same(['bride', 'groom', 'date'], $w['fields'], 'choices: couple_names fragt bride und groom, wedding_date das Datum');
assert_same([], $w['layers'], 'choices: ohne edit-Recht keine Ebene');
assert_same([], $w['palette'], 'choices: ohne customer-Haken keine Farbmarke');

// Ein bind, das die Vorlage nicht benutzt, wird nicht gefragt.
assert_true(!in_array('hashtag', $w['fields'], true), 'choices: nicht benutztes bind wird nicht gefragt');

// Dieselbe Frage zweimal gestellt bleibt eine Frage.
$doppelt = DesignWizard::choices(wizard_doc([
    ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'b', 'type' => 'text', 'bind' => 'bride_name'],
]));
assert_same(['bride', 'groom'], $doppelt['fields'], 'choices: vier binds, zwei Felder');

// edit ist der Hauptschalter: ohne ihn zaehlen die anderen fuenf nicht.
$zu = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => false, 'color' => true, 'hide' => true]],
]));
assert_same([], $zu['layers'], 'choices: edit aus, also zaehlt color nicht');

$auf = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
]));
assert_same(['color' => true, 'font' => false, 'text' => false, 'photo' => false, 'hide' => false],
    $auf['layers']['namen'], 'choices: edit an, color an');

// text auf einer Ebene mit bind ist sinnlos - der Wert kommt aus den Daten.
$gebunden = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'text' => true]],
]));
assert_same([], $gebunden['layers'], 'choices: text-Recht auf gebundener Ebene wird verworfen');

// photo nur, wo ein Bild steht.
$bild = DesignWizard::choices(wizard_doc([
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
    ['id' => 'zeile', 'type' => 'text', 'bind' => 'hashtag', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_true(isset($bild['layers']['foto']), 'choices: photo-Recht auf einem Bild zaehlt');
assert_true(!isset($bild['layers']['zeile']), 'choices: photo-Recht auf Text zaehlt nicht');

// Marken: nur die mit Haken.
$marken = DesignWizard::choices(wizard_doc(
    [['id' => 'a', 'type' => 'text', 'bind' => 'hashtag']],
    ['accent' => ['value' => '#B08D57', 'customer' => true],
     'ink'    => ['value' => '#1A1A1A', 'customer' => false]],
    ['script' => ['family' => 'Great Vibes', 'customer' => true]]
));
assert_same(['accent'], array_keys($marken['palette']), 'choices: nur angehakte Farbmarke');
assert_same(['script'], array_keys($marken['fonts']), 'choices: nur angehakte Schriftmarke');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: `Class "Atelier\DesignWizard" not found` ile ölümcül hata.

- [ ] **Step 3: Write minimal implementation**

`php/src/DesignWizard.php`:

```php
<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Was der Kunde am Design aendern darf - und was der Assistent ihn fragt.
 *
 * Zwei Fragen, zwei Quellen, und sie werden leicht verwechselt:
 *
 *   Welche Felder frage ich?   -> die bind-Namen, die das Design benutzt.
 *                                 Ohne Recht, ohne Haken: die Werte einer
 *                                 Einladung kommen immer aus den Daten.
 *   Was biete ich darueber
 *   hinaus an?                 -> die Rechte (Design::PERMISSIONS) und die
 *                                 customer-Haken der Marken.
 *
 * Wer nur die Rechte liest, baut einen leeren Assistenten: im heutigen Bestand
 * steht fast jedes Recht auf false.
 *
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php, das keine config.php kennt.
 */
final class DesignWizard
{
    /** In dieser Reihenfolge stehen die Felder im Formular. */
    public const FIELD_ORDER = ['bride', 'groom', 'date', 'time', 'venue', 'address', 'message', 'hashtag'];

    /**
     * Welches Feld hinter welchem bind steckt.
     *
     * Weniger Felder als binds: vier Namen ziehen dieselben zwei Felder. Die
     * Karte steht hier und nicht in der Vorlage - sonst muesste jede Vorlage,
     * die den Assistenten zeichnet, sie noch einmal kennen.
     */
    private const BIND_FIELDS = [
        'couple_names'     => ['bride', 'groom'],
        'initials'         => ['bride', 'groom'],
        'bride_name'       => ['bride'],
        'groom_name'       => ['groom'],
        'wedding_date'     => ['date'],
        'wedding_weekday'  => ['date'],
        'wedding_time'     => ['time'],
        'location_name'    => ['venue'],
        'location_address' => ['address'],
        'invitation_text'  => ['message'],
        'hashtag'          => ['hashtag'],
    ];

    /**
     * Alles, was der Assistent zu diesem Design anbieten darf.
     *
     * @param array<string,mixed> $doc
     * @return array{fields:list<string>,palette:array<string,mixed>,fonts:array<string,mixed>,layers:array<string,array<string,bool>>}
     */
    public static function choices(array $doc): array
    {
        $doc = Design::complete($doc);

        $felder = [];
        foreach ($doc['layers'] as $el) {
            foreach (self::BIND_FIELDS[(string) $el['bind']] ?? [] as $feld) {
                $felder[$feld] = true;
            }
        }
        // Nach FIELD_ORDER, nicht nach Fundort: sonst haengt die Reihenfolge
        // im Formular daran, wie der Grafiker die Ebenen sortiert hat.
        $fields = array_values(array_filter(self::FIELD_ORDER, static fn (string $f): bool => isset($felder[$f])));

        $palette = array_filter($doc['palette'], static fn (array $e): bool => (bool) $e['customer']);
        $fonts   = array_filter($doc['fonts'], static fn (array $e): bool => (bool) $e['customer']);

        $layers = [];
        foreach ($doc['layers'] as $el) {
            $p = $el['permissions'];
            // edit ist der Hauptschalter. Eine Ebene mit fuenf Haken und ohne
            // edit ist gesperrt - so ist Sperren ein Haken und nicht fuenf.
            if (!$p['edit']) {
                continue;
            }

            $rechte = [
                'color' => $p['color'] && in_array($el['type'], ['text', 'button', 'shape'], true),
                'font'  => $p['font'] && in_array($el['type'], ['text', 'button'], true),
                // Ein bind holt seinen Wert aus den Daten. Ein fester Text
                // daneben waere eine zweite Wahrheit, die nie gewinnt.
                'text'  => $p['text'] && $el['bind'] === '' && in_array($el['type'], ['text', 'button'], true),
                'photo' => $p['photo'] && in_array($el['type'], ['image', 'photo'], true),
                'hide'  => $p['hide'],
            ];

            if (in_array(true, $rechte, true)) {
                $layers[(string) $el['id']] = $rechte;
            }
        }

        return ['fields' => $fields, 'palette' => $palette, 'fonts' => $fonts, 'layers' => $layers];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: bütün `choices:` kontrolleri geçiyor, hiç `fehlgeschlagen` yok.

- [ ] **Step 5: Run the whole suite — nothing else broke**

```bash
cd php && php bin/test.php
```
Beklenen: 3A'nın 275 kontrolü + yeni kontroller, sıfır hata.

- [ ] **Step 6: Commit**

```bash
git add php/src/DesignWizard.php php/tests/design_wizard.php
git commit -m "The wizard asks what the design uses, not what it permits"
```

---

### Task 2: `DesignWizard::steps()` — adım listesi dokümandan türer

**Files:**
- Modify: `php/src/DesignWizard.php`
- Test: `php/tests/design_wizard.php`

**Interfaces:**
- Consumes: `DesignWizard::choices()` (Task 1)
- Produces: `DesignWizard::steps(array $doc): array` — `['angaben','bilder','design','veroeffentlichen']`
  alt kümesi, **bu sırada**. `angaben` ve `veroeffentlichen` her zaman var.

- [ ] **Step 1: Write the failing test**

`php/tests/design_wizard.php` sonuna ekle:

```php
/*
 * Die Schrittliste steht nicht fest, sie faellt aus dem Dokument.
 *
 * Elysee hat heute fast keine Rechte - der Assistent hat dort zwei Schritte,
 * und das ist richtig. Ein leerer Schritt "Design" waere ein Bildschirm ohne
 * Inhalt. Wird im Panel ein Haken gesetzt, wird der Assistent von selbst laenger.
 */

$knapp = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
]));
assert_same(['angaben', 'veroeffentlichen'], $knapp, 'steps: ohne Rechte zwei Schritte');

$mitBild = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_same(['angaben', 'bilder', 'veroeffentlichen'], $mitBild, 'steps: photo-Recht bringt den Bilder-Schritt');

$mitFarbe = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
]));
assert_same(['angaben', 'design', 'veroeffentlichen'], $mitFarbe, 'steps: color-Recht bringt den Design-Schritt');

// Der Haken an einer Marke reicht allein - ohne jedes Ebenenrecht.
$nurMarke = DesignWizard::steps(wizard_doc(
    [['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names']],
    ['accent' => ['value' => '#B08D57', 'customer' => true]]
));
assert_same(['angaben', 'design', 'veroeffentlichen'], $nurMarke, 'steps: angehakte Marke oeffnet den Design-Schritt allein');

$alles = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_same(['angaben', 'bilder', 'design', 'veroeffentlichen'], $alles, 'steps: alle vier, in dieser Reihenfolge');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: `Call to undefined method Atelier\DesignWizard::steps()`.

- [ ] **Step 3: Write minimal implementation**

`DesignWizard` sınıfına, `choices()`'ın altına:

```php
    /**
     * Welche Schritte dieses Design braucht.
     *
     * Nicht fest verdrahtet: ein Design ohne Rechte hat zwei Schritte, eines
     * mit Bildern und Farben vier. Ein leerer Schritt ist ein Bildschirm, auf
     * dem nichts zu tun ist - der wird nicht gezeigt.
     *
     * @param array<string,mixed> $doc
     * @return list<string>
     */
    public static function steps(array $doc): array
    {
        $w = self::choices($doc);

        $schritte = ['angaben'];

        foreach ($w['layers'] as $rechte) {
            if ($rechte['photo']) {
                $schritte[] = 'bilder';
                break;
            }
        }

        $design = $w['palette'] !== [] || $w['fonts'] !== [];
        if (!$design) {
            foreach ($w['layers'] as $rechte) {
                if ($rechte['color'] || $rechte['font'] || $rechte['text'] || $rechte['hide']) {
                    $design = true;
                    break;
                }
            }
        }
        if ($design) {
            $schritte[] = 'design';
        }

        $schritte[] = 'veroeffentlichen';

        return $schritte;
    }
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: bütün `steps:` kontrolleri geçiyor.

- [ ] **Step 5: Commit**

```bash
git add php/src/DesignWizard.php php/tests/design_wizard.php
git commit -m "A design with no permissions gets a two-step wizard, and that is correct"
```

---

### Task 3: `personalize()` — izin süzgeci ve jeton basımı

Planın kalbi ve **güvenlik sınırı**. İzinsiz her seçim burada sessizce düşer.

**Files:**
- Modify: `php/src/DesignWizard.php`
- Modify: `php/src/Design.php` — `safeColor()`/`safeFont()` `private` → `public` (yalnızca bu iki kelime)
- Test: `php/tests/design_wizard.php`

**Interfaces:**
- Consumes: `DesignWizard::choices()` (Task 1), `Design::complete()`,
  `Design::safeColor()`, `Design::safeFont()` (bu görevde `public` oluyorlar)
- Produces: `DesignWizard::personalize(array $doc, array $wahl): array` — kişiselleştirilmiş
  ve `Design::complete()`'ten geçmiş **tam doküman**. `$wahl` şekli:
  ```php
  [
    'palette' => ['accent' => '#8B0000'],                 // jeton anahtarı => renk
    'fonts'   => ['script' => 'Cormorant'],               // jeton anahtarı => aile
    'layers'  => [
      'name-1' => ['color' => '#8B0000', 'font' => 'Jost',
                   'text' => ['de' => '…', 'en' => '…'],
                   'src' => '/uploads/einladungen/v2/x/a.jpg',
                   'hidden' => true],
    ],
  ]
  ```

- [ ] **Step 1: Write the failing test**

`php/tests/design_wizard.php` sonuna ekle:

```php
use Atelier\Design;

/*
 * personalize() ist die Grenze.
 *
 * Was hier durchkommt, steht gleich im design_snapshot und wird ausgeliefert.
 * Ein POST, der ein gesperrtes Recht behauptet, faellt still - nicht mit einer
 * Fehlerseite: das Recht kann im Panel zugegangen sein, waehrend das Formular
 * offen stand. Der Kunde bekommt dann das Design, wie es gedacht ist.
 */

$basis = wizard_doc(
    [
        ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
         'style' => ['color' => 'ink'],
         'permissions' => ['edit' => true, 'color' => true]],
        ['id' => 'siegel', 'type' => 'image', 'src' => '/assets/designs/elysee-1.svg',
         'permissions' => []],
    ],
    ['ink'    => ['value' => '#1A1A1A', 'customer' => false],
     'accent' => ['value' => '#B08D57', 'customer' => true]]
);

// Erlaubte Ebenenfarbe: eine eigene Marke wird gepraegt, weil der Renderer
// nur Markennamen kennt (Design.php:371).
$rot = DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => '#8B0000']]]);
assert_same('kunde-namen', $rot['layers'][0]['style']['color'], 'personalize: die Ebene zeigt auf die eigene Marke');
assert_same('#8B0000', $rot['palette']['kunde-namen']['value'], 'personalize: die Marke traegt die Farbe');
assert_same(false, $rot['palette']['kunde-namen']['customer'], 'personalize: die gepraegte Marke wird nicht wieder angeboten');
assert_contains(Design::css($rot, '.t'), 'color:var(--d-kunde-namen)', 'personalize: der Renderer schreibt die Marke');

// Zweimal dieselbe Ebene: dieselbe Marke, kein Wildwuchs.
$zweimal = DesignWizard::personalize($rot, ['layers' => ['namen' => ['color' => '#004400']]]);
assert_same('#004400', $zweimal['palette']['kunde-namen']['value'], 'personalize: zweite Farbe ueberschreibt dieselbe Marke');
assert_same(1, count(array_filter(array_keys($zweimal['palette']), static fn ($k) => str_starts_with((string) $k, 'kunde-'))), 'personalize: nur eine gepraegte Marke');

// Unsinn wird nicht gespeichert - er wird beim Schreiben geklaert, nicht erst
// beim Drucken.
$mist = DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => 'javascript:alert(1)']]]);
assert_same('transparent', $mist['palette']['kunde-namen']['value'], 'personalize: ungueltige Farbe wird transparent');

// Gesperrte Ebene: faellt still.
$gesperrt = DesignWizard::personalize($basis, ['layers' => ['siegel' => ['color' => '#8B0000']]]);
assert_same([], array_filter(array_keys($gesperrt['palette']), static fn ($k) => str_starts_with((string) $k, 'kunde-')), 'personalize: ohne edit-Recht keine Marke');

// Erfundene Kennung: faellt still.
$erfunden = DesignWizard::personalize($basis, ['layers' => ['gibtesnicht' => ['color' => '#8B0000']]]);
assert_same(count($basis['layers']), count($erfunden['layers']), 'personalize: erfundene Ebene fuegt nichts hinzu');

// Angehakte Marke: darf.
$marke = DesignWizard::personalize($basis, ['palette' => ['accent' => '#8B0000']]);
assert_same('#8B0000', $marke['palette']['accent']['value'], 'personalize: angehakte Marke wird gesetzt');

// Nicht angehakte Marke: faellt still.
$sperre = DesignWizard::personalize($basis, ['palette' => ['ink' => '#8B0000']]);
assert_same('#1A1A1A', $sperre['palette']['ink']['value'], 'personalize: Marke ohne Haken bleibt');

// text auf einer gebundenen Ebene: faellt still.
$text = DesignWizard::personalize($basis, ['layers' => ['namen' => ['text' => ['de' => 'X', 'en' => 'X']]]]);
assert_same('', $text['layers'][0]['text']['de'], 'personalize: fester Text auf gebundener Ebene wird verworfen');

// Ausblenden ohne Recht: faellt still.
$weg = DesignWizard::personalize($basis, ['layers' => ['siegel' => ['hidden' => true]]]);
assert_same(2, count($weg['layers']), 'personalize: ohne hide-Recht bleibt die Ebene stehen');

// Die Form bleibt die eines vollstaendigen Dokuments - der Schnappschuss geht
// unveraendert an den Renderer.
assert_same(Design::complete($rot), $rot, 'personalize: das Ergebnis ist bereits vollstaendig');

// Rein: dasselbe Design traegt zwei Einladungen.
$vorher = $basis;
DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => '#8B0000']]]);
assert_same($vorher, $basis, 'personalize: die Vorlage bleibt unberuehrt');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: `Call to undefined method Atelier\DesignWizard::personalize()`.

- [ ] **Step 3: Open the two sanitisers**

`php/src/Design.php:442` ve `:455` — yalnızca görünürlük değişiyor:

```php
    /**
     * Nur was als Farbe durchgeht.
     *
     * Oeffentlich, weil der Assistent denselben Massstab braucht: eine Farbe
     * wird beim Schreiben geklaert, nicht erst beim Drucken. Zwei Antworten
     * auf "was ist eine gueltige Farbe" waeren eine zu viel.
     */
    public static function safeColor(string $value): string
```

```php
    /** Schriftname aus demselben Grund: nur Buchstaben, Ziffern, Leerzeichen, Komma, Bindestrich. */
    public static function safeFont(string $value): string
```

Gövdeler değişmez.

- [ ] **Step 4: Write the implementation**

`DesignWizard` sınıfına ekle:

```php
    /**
     * Die Wahl des Kunden auf das Design legen.
     *
     * Das Ergebnis ist der design_snapshot: ein vollstaendiges Dokument, das
     * der Renderer aus Phase 1 ohne eine einzige neue Zeile druckt. Es wird
     * bewusst keine Liste "was der Kunde geaendert hat" gefuehrt - die muesste
     * der Renderer, die Vorschau, das Panel und der spaetere Bearbeiten-
     * Bildschirm jeweils einzeln verstehen.
     *
     * Weissliste: gefragt wird immer zuerst choices(). Was dort nicht steht,
     * faellt still - siehe Kommentar im Test.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $wahl
     * @return array<string,mixed>
     */
    public static function personalize(array $doc, array $wahl): array
    {
        $doc  = Design::complete($doc);
        $darf = self::choices($doc);

        foreach ((array) ($wahl['palette'] ?? []) as $key => $wert) {
            if (isset($darf['palette'][$key])) {
                $doc['palette'][$key]['value'] = Design::safeColor((string) $wert);
            }
        }

        foreach ((array) ($wahl['fonts'] ?? []) as $key => $wert) {
            if (isset($darf['fonts'][$key])) {
                $doc['fonts'][$key]['family'] = Design::safeFont((string) $wert);
            }
        }

        $layers = (array) ($wahl['layers'] ?? []);

        foreach ($doc['layers'] as $i => $el) {
            $id = (string) $el['id'];
            $rechte = $darf['layers'][$id] ?? null;
            $gewaehlt = $layers[$id] ?? null;

            if ($rechte === null || !is_array($gewaehlt)) {
                continue;
            }

            // Eine eigene Farbe wird eine eigene Marke. Der Renderer kennt nur
            // Markennamen: color:var(--d-<name>). Ein roher Wert ergaebe
            // var(--d-#8B0000) - ungueltiges CSS und ein farbloses Element.
            if ($rechte['color'] && isset($gewaehlt['color'])) {
                $marke = 'kunde-' . $id;
                $doc['palette'][$marke] = [
                    'value'    => Design::safeColor((string) $gewaehlt['color']),
                    'label'    => ['de' => 'Eigene Farbe', 'tr' => 'Kendi rengi'],
                    // Das Ergebnis der Wahl, nicht eine Wahl, die man wieder
                    // anbietet: sonst stuende sie beim Bearbeiten doppelt da.
                    'customer' => false,
                ];
                $doc['layers'][$i]['style']['color'] = $marke;
            }

            if ($rechte['font'] && isset($gewaehlt['font'])) {
                $marke = 'kunde-' . $id;
                $doc['fonts'][$marke] = [
                    'family'     => Design::safeFont((string) $gewaehlt['font']),
                    'size'       => $doc['fonts'][$el['style']['font']]['size'] ?? 100,
                    'weight'     => $doc['fonts'][$el['style']['font']]['weight'] ?? 400,
                    'tracking'   => $doc['fonts'][$el['style']['font']]['tracking'] ?? 0,
                    'lineHeight' => $doc['fonts'][$el['style']['font']]['lineHeight'] ?? 120,
                    'customer'   => false,
                ];
                $doc['layers'][$i]['style']['font'] = $marke;
            }

            if ($rechte['text'] && isset($gewaehlt['text']) && is_array($gewaehlt['text'])) {
                $doc['layers'][$i]['text'] = [
                    'de' => Security::clean($gewaehlt['text']['de'] ?? '', 600),
                    'en' => Security::clean($gewaehlt['text']['en'] ?? '', 600),
                ];
            }

            if ($rechte['photo'] && isset($gewaehlt['src'])) {
                $doc['layers'][$i]['src'] = (string) $gewaehlt['src'];
            }

            if ($rechte['hide'] && !empty($gewaehlt['hidden'])) {
                unset($doc['layers'][$i]);
            }
        }

        // Nach dem Entfernen einer Ebene sind die Schluessel loechrig; die
        // Reihenfolge ist der z-Index, also wird neu gezaehlt und nicht sortiert.
        $doc['layers'] = array_values($doc['layers']);

        // Noch einmal durch complete(): die gepraegten Marken bekommen ihre
        // Standardfelder, und der Schnappschuss hat garantiert die Form, die
        // Design::css() und Design::html() erwarten.
        return Design::complete($doc);
    }
```

`Security` sınıfını dosyanın başında `use` et: `use Atelier\Security;` gerekmez
(aynı ad alanı), ama `bin/test.php` `Security.php`'yi yüklemiyor olabilir —
Adım 5 bunu doğrular.

- [ ] **Step 5: Run test to verify it passes**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: bütün `personalize:` kontrolleri geçiyor.

`Class "Atelier\Security" not found` hatası gelirse: `bin/test.php`'nin
otomatik yükleyicisi `src/` altındaki her sınıfı bulur (satır 20-28), yani
hata **gelmemeli**. Gelirse `Security::clean()` yerine yerel bir kırpma yazma —
otomatik yükleyicinin neden bulmadığını bul.

- [ ] **Step 6: Run the whole suite**

```bash
cd php && php bin/test.php
```
Beklenen: sıfır hata. `design_css.php` ve `design_html.php` özellikle geçmeli —
`safeColor`/`safeFont` görünürlüğü değişti, davranışı değil.

- [ ] **Step 7: Commit**

```bash
git add php/src/DesignWizard.php php/src/Design.php php/tests/design_wizard.php
git commit -m "A permitted colour becomes a token, and everything else falls silently"
```

---

### Task 4: `InvitationsV2` — davetiyenin saklanması

**Files:**
- Create: `php/src/InvitationsV2.php`
- Test: `php/tests/design_wizard.php` (yalnızca `needs_db()` korumalı bölüm)

**Interfaces:**
- Consumes: `Db::run/one/json/encode`, `Invitations::slug()`, `Design::complete()`
- Produces:
  - `InvitationsV2::slug(string $value): string` — `Invitations::slug()`'a devreder
  - `InvitationsV2::slugAvailable(string $slug): bool` — **her iki** tabloya bakar
  - `InvitationsV2::create(string $slug, string $designId, array $snapshot, array $data): void`
  - `InvitationsV2::find(string $slug): ?array` — `['slug','design_id','design_snapshot','data','created_at']`
    ya da `null`

- [ ] **Step 1: Write the failing test**

`php/tests/design_wizard.php` sonuna ekle:

```php
use Atelier\InvitationsV2;

/*
 * Der Name muss in beiden Tabellen frei sein.
 *
 * Das v2-Praefix in der Adresse ist absichtlich vorlaeufig (Phase 1, §1). An
 * dem Tag, an dem es faellt, muss /einladung/{slug} genau eine Einladung
 * treffen. Heute kostet das nichts; spaeter waere es unmoeglich - eine
 * veroeffentlichte Adresse benennt man nicht um.
 */
if (needs_db()) {
    require_once __DIR__ . '/../src/bootstrap.php';

    $name = 'test-v2-' . bin2hex(random_bytes(4));
    assert_true(InvitationsV2::slugAvailable($name), 'slugAvailable: ein frischer Name ist frei');

    InvitationsV2::create($name, 'elysee', ['id' => 'elysee', 'layers' => []], ['bride' => 'Marie']);
    assert_true(!InvitationsV2::slugAvailable($name), 'slugAvailable: nach dem Anlegen belegt');

    $gefunden = InvitationsV2::find($name);
    assert_same('elysee', $gefunden['design_id'] ?? '', 'find: die Kennung des Designs steht drin');
    assert_same('Marie', $gefunden['data']['bride'] ?? '', 'find: die Daten kommen als Feld zurueck');
    assert_true(isset($gefunden['design_snapshot']['layers']), 'find: der Schnappschuss kommt als Feld zurueck');

    \Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$name]);
} else {
    echo "  (invitations_v2: uebersprungen, keine config.php)\n";
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: `config.php` varsa `Class "Atelier\InvitationsV2" not found`;
yoksa atlama satırı yazılır ve test geçer. **Her iki durumda da devam et** —
uygulama sonraki adımda.

- [ ] **Step 3: Write the implementation**

`php/src/InvitationsV2.php`:

```php
<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Einladungen der zweiten Fassung.
 *
 * Getrennt von Invitations, weil die alte Tabelle das alte Schema traegt und
 * unangetastet bleibt. Das Schema hier steht seit Phase 1 und aendert sich
 * nicht: was noch fehlt, kommt in data hinein - das ist JSON.
 */
final class InvitationsV2
{
    /** Dieselbe Buchstabentabelle wie die alte Fassung - nicht zwei Wahrheiten. */
    public static function slug(string $value): string
    {
        return Invitations::slug($value);
    }

    /**
     * Frei in BEIDEN Tabellen.
     *
     * Das v2 in der Adresse faellt eines Tages weg. Dann muss
     * /einladung/{slug} genau eine Einladung treffen - und eine bereits
     * verschickte Adresse laesst sich nicht umbenennen.
     */
    public static function slugAvailable(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }
        if (Db::one('SELECT slug FROM invitations_v2 WHERE slug = ?', [$slug]) !== null) {
            return false;
        }
        return Db::one('SELECT slug FROM invitations WHERE slug = ?', [$slug]) === null;
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<string,mixed> $data
     */
    public static function create(string $slug, string $designId, array $snapshot, array $data): void
    {
        Db::run(
            'INSERT INTO invitations_v2 (slug, design_id, design_snapshot, data) VALUES (?, ?, ?, ?)',
            [$slug, $designId, Db::encode($snapshot), Db::encode($data)]
        );
    }

    /**
     * @return array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string}|null
     */
    public static function find(string $slug): ?array
    {
        $row = Db::one('SELECT * FROM invitations_v2 WHERE slug = ?', [self::slug($slug)]);
        if ($row === null) {
            return null;
        }

        return [
            'slug'            => (string) $row['slug'],
            'design_id'       => (string) $row['design_id'],
            'design_snapshot' => (array) json_decode((string) $row['design_snapshot'], true),
            'data'            => (array) json_decode((string) $row['data'], true),
            'created_at'      => (string) $row['created_at'],
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd php && php bin/test.php design_wizard
```
Beklenen: `config.php` varsa dört `slugAvailable/find` kontrolü geçer;
yoksa atlama satırı.

- [ ] **Step 5: Verify the table exists**

`config.php` varsa:
```bash
cd php && php -r 'require "src/bootstrap.php"; var_dump(\Atelier\Db::one("SHOW TABLES LIKE \"invitations_v2\""));'
```
Beklenen: boş dizi **değil**. Tablo yoksa `schema.sql`'deki `CREATE TABLE
IF NOT EXISTS invitations_v2` bloğu elle çalıştırılır — Faz 1'de tanımlandı,
şema değişmiyor.

- [ ] **Step 6: Commit**

```bash
git add php/src/InvitationsV2.php php/tests/design_wizard.php
git commit -m "A v2 slug must be free in both tables, because the prefix will fall one day"
```

---

### Task 5: Sahne partial'ı — 3A'nın sayfası bozulmadan

Davetiye sayfası aynı sahneyi gerçek veriyle gösterecek. 148 satır kopyalamak
yerine ortaklanır. **Bu görevin tek ölçütü: `/de/v2/designs/elysee` görünüş
olarak değişmemiş olmak.**

**Files:**
- Create: `php/templates/partials/design-stage.php`
- Modify: `php/templates/pages/design-preview.php`

**Interfaces:**
- Produces: `View::partial('partials/design-stage', [...])` — şu değişkenleri alır:
  `design` (array, tam doküman), `scope` (string), `styles` (string),
  `seite` (string), `kuvert` (string), `karte` (string), `locale` (string).
  Alt çubuk **basmaz** — onu çağıran sayfa kendi basar.

- [ ] **Step 1: Record what the page looks like today**

Sunucuyu çalıştır ve çıktıyı sakla:

```bash
cd php && php -S 127.0.0.1:8080 public/dev-router.php &
sleep 2
curl -s http://127.0.0.1:8080/de/v2/designs/elysee > /tmp/stage-vorher.html
wc -c /tmp/stage-vorher.html
```
Beklenen: boş olmayan HTML. Bu dosya bu görevin ölçütü.

- [ ] **Step 2: Move the stage into a partial**

`design-preview.php` içinde `<style>` satırından (34) alt çubuğun başladığı
`<?php if ($intern) : ?>` satırına (151) kadar olan blok **olduğu gibi**
`templates/partials/design-stage.php` dosyasına taşınır. Tek satır bile
yeniden yazılmaz — kes ve yapıştır.

Yeni dosyanın başına başlık yorumu:

```php
<?php
/**
 * Die Buehne: Seite, Kuvert, Karte - und was sich dabei bewegt.
 *
 * Zwei Seiten zeigen dieselbe Buehne: die Vorschau eines Designs (mit
 * Beispieldaten) und eine echte Einladung (mit den Daten des Paares). Der
 * Unterschied steht nur in der Leiste darunter, und die druckt die
 * aufrufende Seite selbst.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $seite
 * @var string $kuvert
 * @var string $karte
 * @var string $locale
 */
?>
```

- [ ] **Step 3: Call the partial from the preview page**

`design-preview.php`'de taşınan bloğun yerine:

```php
<?= View::partial('partials/design-stage', [
    'design' => $design,
    'scope'  => $scope,
    'styles' => $styles,
    'seite'  => $seite,
    'kuvert' => $kuvert,
    'karte'  => $karte,
    'locale' => $locale,
]) ?>
```

Alt çubuk (`<?php if ($intern) : ?>` … `<?php endif; ?>`) `design-preview.php`'de
**kalır**.

- [ ] **Step 4: Verify the page did not change**

```bash
curl -s http://127.0.0.1:8080/de/v2/designs/elysee > /tmp/stage-nachher.html
diff /tmp/stage-vorher.html /tmp/stage-nachher.html && echo "AYNI"
```
Beklenen: `AYNI`. Fark çıkarsa **taşıma yanlış** — düzelt, ileri gitme.
Boşluk farkı da farktır: `View::partial` çıktısını olduğu gibi döndürür,
yani fark çıkıyorsa bir satır kaymış demektir.

- [ ] **Step 5: Verify the catalogue page too**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8080/de/v2/designs
```
Beklenen: `200`.

- [ ] **Step 6: Run the suite and commit**

```bash
cd php && php bin/test.php
git add php/templates/partials/design-stage.php php/templates/pages/design-preview.php
git commit -m "Two pages want the same stage, so the stage moves out of the showcase"
```

---

### Task 6: Rotalar, sözlük ve sihirbazın GET tarafı

Bu görevin sonunda sihirbaz **açılıyor** ve doğru adımları gösteriyor —
yayınlama henüz yok.

**Files:**
- Create: `php/src/Controllers/InviteV2Controller.php`
- Create: `php/templates/pages/invite-v2-wizard.php`
- Modify: `php/public/index.php` (109. satırın altı)
- Modify: `php/data/dict.php` (üç dil kümesi)

**Interfaces:**
- Consumes: `DesignWizard::steps()`, `DesignWizard::choices()` (Task 1-2),
  `Design::all()`, `Design::find()`, `Design::css()`, `Design::html()`,
  `Design::bindValues()`
- Produces: `InviteV2Controller::wizard(): void`

- [ ] **Step 1: Add the dictionary keys**

`php/data/dict.php` — üç kümeye de (`de` ~169, `en` ~539, `tr` ~907
satırlarındaki `invitation2` bloklarının **içine**) ekle. Mevcut hiçbir
anahtara dokunma.

`de` bloğuna:
```php
            'wizardTitle'   => 'Eure Einladung',
            'wizardLead'    => 'Wir fragen nur, was dieses Design zeigt.',
            'stepAngaben'   => 'Eure Angaben',
            'stepBilder'    => 'Eure Bilder',
            'stepDesign'    => 'Euer Design',
            'stepPublish'   => 'Ansehen & veröffentlichen',
            'fieldBride'    => 'Braut',
            'fieldGroom'    => 'Bräutigam',
            'fieldDate'     => 'Datum',
            'fieldTime'     => 'Uhrzeit',
            'fieldVenue'    => 'Ort',
            'fieldAddress'  => 'Adresse',
            'fieldMessage'  => 'Euer Text',
            'fieldHashtag'  => 'Hashtag',
            'fieldSlug'     => 'Eure Adresse',
            'slugNote'      => 'So lautet der Link, den ihr verschickt.',
            'publish'       => 'Veröffentlichen',
            'doneTitle'     => 'Eure Einladung steht',
            'errorNames'    => 'Bitte tragt beide Namen ein.',
            'errorCsrf'     => 'Das Formular ist abgelaufen. Bitte noch einmal absenden.',
            'errorThrottle' => 'Zu viele Versuche. Bitte in einer Viertelstunde noch einmal.',
```

`en` bloğuna:
```php
            'wizardTitle'   => 'Your invitation',
            'wizardLead'    => 'We only ask for what this design shows.',
            'stepAngaben'   => 'Your details',
            'stepBilder'    => 'Your pictures',
            'stepDesign'    => 'Your design',
            'stepPublish'   => 'Preview & publish',
            'fieldBride'    => 'Bride',
            'fieldGroom'    => 'Groom',
            'fieldDate'     => 'Date',
            'fieldTime'     => 'Time',
            'fieldVenue'    => 'Venue',
            'fieldAddress'  => 'Address',
            'fieldMessage'  => 'Your text',
            'fieldHashtag'  => 'Hashtag',
            'fieldSlug'     => 'Your address',
            'slugNote'      => 'This is the link you will send out.',
            'publish'       => 'Publish',
            'doneTitle'     => 'Your invitation is live',
            'errorNames'    => 'Please enter both names.',
            'errorCsrf'     => 'The form expired. Please submit again.',
            'errorThrottle' => 'Too many attempts. Please try again in fifteen minutes.',
```

`tr` bloğuna:
```php
            'wizardTitle'   => 'Davetiyeniz',
            'wizardLead'    => 'Yalnızca bu tasarımın gösterdiğini soruyoruz.',
            'stepAngaben'   => 'Bilgileriniz',
            'stepBilder'    => 'Görselleriniz',
            'stepDesign'    => 'Tasarımınız',
            'stepPublish'   => 'Önizleme ve yayın',
            'fieldBride'    => 'Gelin',
            'fieldGroom'    => 'Damat',
            'fieldDate'     => 'Tarih',
            'fieldTime'     => 'Saat',
            'fieldVenue'    => 'Mekân',
            'fieldAddress'  => 'Adres',
            'fieldMessage'  => 'Metniniz',
            'fieldHashtag'  => 'Etiket',
            'fieldSlug'     => 'Adresiniz',
            'slugNote'      => 'Göndereceğiniz bağlantı bu olacak.',
            'publish'       => 'Yayınla',
            'doneTitle'     => 'Davetiyeniz hazır',
            'errorNames'    => 'Lütfen iki ismi de yazın.',
            'errorCsrf'     => 'Form zaman aşımına uğradı. Lütfen tekrar gönderin.',
            'errorThrottle' => 'Çok fazla deneme. Lütfen on beş dakika sonra tekrar deneyin.',
```

- [ ] **Step 2: Add the routes**

`php/public/index.php`, 109. satırdaki `v2/designs/{slug}` satırının **altına**:

```php
// Der Assistent der zweiten Fassung. Die feste Adresse steht vor dem Muster
// {slug} - sonst liest der Router "einladung" als Namen einer Einladung.
$router->any('/{locale}/v2/einladung', $page_(static fn (array $p) => (new InviteV2Controller())->wizard()));
$router->get('/{locale}/v2/einladung/{slug}', $page_(static fn (array $p) => (new InviteV2Controller())->show($p)));
```

`show()` Task 8'de yazılacak; rota şimdi ekleniyor ki sıra bir kez kurulsun.
Bu adımdan sonra `/de/v2/einladung/x` **hata verir** — Task 8'e kadar normal.

- [ ] **Step 3: Write the controller (GET only)**

`php/src/Controllers/InviteV2Controller.php`:

```php
<?php

declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Design;
use Atelier\DesignWizard;
use Atelier\I18n;
use Atelier\Security;
use Atelier\Seo;
use Atelier\View;

/**
 * Der Assistent der zweiten Fassung.
 *
 * Er steht neben dem alten (InviteController) und beruehrt ihn nicht: der alte
 * traegt Zahlung, Gutschein und Zusagen, und die kommen erst in Phase D
 * herueber. Bis dahin ist diese Seite von nirgends verlinkt - der Knopf im
 * Schaufenster zeigt weiter auf den alten Assistenten, damit hier keine
 * unbezahlte Einladung entsteht.
 */
final class InviteV2Controller
{
    /**
     * Damit die Vorschau ueberhaupt Knoten hat.
     *
     * Ein gebundenes Textelement ohne Wert wird nicht gezeichnet. Stuende hier
     * nichts, faende das Skript in Aufgabe 9 keine [data-bind]-Knoten und die
     * Live-Vorschau bliebe still - ein Fehler, den man auf einem Bildschirmfoto
     * nicht sieht. Dieselben Felder wie im Schaufenster.
     */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
        'hashtag' => '#sophiaundmaximilian',
    ];

    public function wizard(): void
    {
        $locale = I18n::locale();

        $designs = Design::all('active');
        if ($designs === []) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in
            // welcher Sprache sie aufgerufen wurde. DesignController::preview()
            // gibt sie aus genau diesem Grund mit.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        // Aus dem Schaufenster kommt die Wahl mit. Was wir nicht kennen, wird
        // nicht uebernommen, sondern durch die erste Vorlage ersetzt - eine
        // fremde Angabe steht nicht im Formular.
        $wunsch = Security::clean($_GET['design'] ?? '', 96);
        $design = $wunsch !== '' ? Design::find($wunsch) : null;
        if ($design === null || (string) $design['status'] !== 'active') {
            $design = $designs[0];
        }
        $design = Design::complete($design);

        $scope = '.d-' . $design['id'];

        View::page('pages/invite-v2-wizard', [
            'locale'  => $locale,
            'meta'    => Seo::forPage('einladung2', [
                'title'    => I18n::t('invitation2.wizardTitle'),
                'noindex'  => true,
                'scripts'  => ['/assets/invite-v2.js'],
            ]),
            'design'  => $design,
            'designs' => $designs,
            'steps'   => DesignWizard::steps($design),
            'choices' => DesignWizard::choices($design),
            'values'  => [],
            'scope'   => $scope,
            'styles'  => Design::css($design, $scope),
            // Beispieldaten, nicht leer. Design::html() ueberspringt ein
            // gebundenes Textelement, dessen Wert leer ist (Design.php:487) -
            // mit leeren Daten stuenden vier der sechs Elemente gar nicht erst
            // im DOM, und die Vorschau in Aufgabe 9 fuellte etwas, das es nicht
            // gibt. Das Skript leert sie beim ersten Lauf wieder.
            'karte'   => Design::html($design, Design::bindValues(self::BEISPIEL, $locale), $locale, 'card'),
            'csrf'    => Security::csrf(),
            'error'   => '',
            'done'    => null,
        ]);
    }
}
```

- [ ] **Step 4: Write the template**

`php/templates/pages/invite-v2-wizard.php`:

```php
<?php
/**
 * Der Assistent: ein Formular, so viele Schritte wie das Design braucht.
 *
 * Ohne Skript stehen alle Schritte untereinander und ein Absenden reicht -
 * dieselbe Regel wie im alten Assistenten. Das Skript blendet sie ein und aus.
 * Es entscheidet nichts: welche Felder es gibt, steht schon fest, bevor diese
 * Datei laeuft (DesignWizard::choices()).
 *
 * @var string $locale
 * @var array<string,mixed> $design
 * @var list<array<string,mixed>> $designs
 * @var list<string> $steps
 * @var array<string,mixed> $choices
 * @var array<string,string> $values
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $csrf
 * @var string $error
 * @var array<string,mixed>|null $done
 */

use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$p = static fn (string $path): string => I18n::path($path, $locale);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');

$label = 'text-[0.62rem] uppercase tracking-[0.18em] text-muted';
$field = 'mt-2 w-full border border-sand-deep bg-cream px-4 py-3 text-sm text-ink';

$stepTitles = [
    'angaben'          => $t('stepAngaben'),
    'bilder'           => $t('stepBilder'),
    'design'           => $t('stepDesign'),
    'veroeffentlichen' => $t('stepPublish'),
];

$fieldTitles = [
    'bride'   => $t('fieldBride'),   'groom'   => $t('fieldGroom'),
    'date'    => $t('fieldDate'),    'time'    => $t('fieldTime'),
    'venue'   => $t('fieldVenue'),   'address' => $t('fieldAddress'),
    'message' => $t('fieldMessage'), 'hashtag' => $t('fieldHashtag'),
];

$inputTypes = ['date' => 'date', 'time' => 'time'];
?>
<?= Ui::pageHero('invite2-hero', $t('wizardTitle'), I18n::t('nav.invitation2'), $t('wizardLead')) ?>

<?= Ui::sectionOpen() ?>

<?php if ($done !== null) : ?>
  <div class="mx-auto max-w-2xl text-center">
    <div class="eyebrow">✓</div>
    <h2 class="headline mt-3 text-3xl"><?= e($t('doneTitle')) ?></h2>
    <p class="mt-6 break-all text-sm text-ink">
      <a class="underline" href="<?= e($done['path']) ?>"><?= e($done['url']) ?></a>
    </p>
  </div>

<?php else : ?>

  <?php if ($error !== '') : ?>
    <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
      <?= e($t('error' . ucfirst($error))) ?>
    </p>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="mx-auto max-w-3xl" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="design" value="<?= e((string) $design['slug']) ?>">

    <ol class="mb-10 flex flex-wrap gap-x-6 gap-y-2 border-b border-sand-deep pb-4 text-[0.62rem] uppercase tracking-[0.16em]" data-steps>
      <?php foreach ($steps as $i => $key) : ?>
        <li data-step-label="<?= $i ?>" class="text-muted"><?= $i + 1 ?>. <?= e($stepTitles[$key]) ?></li>
      <?php endforeach; ?>
    </ol>

    <?php foreach ($steps as $i => $key) : ?>
      <fieldset data-step="<?= $i ?>" class="space-y-8">

        <?php if ($key === 'angaben') : ?>
          <div class="grid gap-7 sm:grid-cols-2">
            <?php foreach ($choices['fields'] as $feld) : ?>
              <div<?= $feld === 'message' ? ' class="sm:col-span-2"' : '' ?>>
                <label class="<?= $label ?>" for="f-<?= e($feld) ?>"><?= e($fieldTitles[$feld]) ?></label>
                <?php if ($feld === 'message') : ?>
                  <textarea id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" rows="4" class="<?= $field ?>" data-live="<?= e($feld) ?>"><?= e($old($feld)) ?></textarea>
                <?php else : ?>
                  <input id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" class="<?= $field ?>"
                         type="<?= e($inputTypes[$feld] ?? 'text') ?>"
                         value="<?= e($old($feld)) ?>" data-live="<?= e($feld) ?>"
                         <?= in_array($feld, ['bride', 'groom'], true) ? 'required' : '' ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($key === 'bilder') : ?>
          <?php foreach ($choices['layers'] as $id => $rechte) : ?>
            <?php if (!$rechte['photo']) { continue; } ?>
            <div>
              <label class="<?= $label ?>" for="b-<?= e($id) ?>"><?= e($id) ?></label>
              <input id="b-<?= e($id) ?>" type="file" name="layer_src_<?= e($id) ?>"
                     accept="image/jpeg,image/png,image/webp" class="<?= $field ?>">
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($key === 'design') : ?>
          <?php foreach ($choices['palette'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>" for="p-<?= e($marke) ?>">
                <?= e($eintrag['label'][$locale] ?? $eintrag['label']['de'] ?? $marke) ?>
              </label>
              <input id="p-<?= e($marke) ?>" type="color" name="palette_<?= e($marke) ?>"
                     value="<?= e((string) $eintrag['value']) ?>" class="<?= $field ?> h-12">
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['fonts'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>" for="s-<?= e($marke) ?>"><?= e($marke) ?></label>
              <select id="s-<?= e($marke) ?>" name="fonts_<?= e($marke) ?>" class="<?= $field ?>">
                <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                  <option value="<?= e($familie) ?>" <?= (string) $eintrag['family'] === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['layers'] as $id => $rechte) : ?>
            <?php if (!$rechte['color'] && !$rechte['font'] && !$rechte['text'] && !$rechte['hide']) { continue; } ?>
            <div class="border-t border-sand-deep pt-6">
              <div class="<?= $label ?>"><?= e($id) ?></div>

              <?php if ($rechte['color']) : ?>
                <input type="color" name="layer_color_<?= e($id) ?>" class="<?= $field ?> h-12">
              <?php endif; ?>

              <?php if ($rechte['font']) : ?>
                <select name="layer_font_<?= e($id) ?>" class="<?= $field ?>">
                  <option value=""><?= e($locale === 'de' ? '— wie im Design —' : '— as the design has it —') ?></option>
                  <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                    <option value="<?= e($familie) ?>"><?= e($familie) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>

              <?php if ($rechte['text']) : ?>
                <input type="text" name="layer_text_<?= e($id) ?>" class="<?= $field ?>" maxlength="600">
              <?php endif; ?>

              <?php if ($rechte['hide']) : ?>
                <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                  <input type="checkbox" name="layer_hidden_<?= e($id) ?>"> <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
                </label>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($key === 'veroeffentlichen') : ?>
          <style><?= $styles ?></style>
          <div class="<?= e($scope) ?> mx-auto aspect-[2/3] w-full max-w-sm" data-preview
               style="position:relative;container-type:inline-size;"><?= $karte ?></div>

          <div>
            <label class="<?= $label ?>" for="f-slug"><?= e($t('fieldSlug')) ?></label>
            <input id="f-slug" name="slug" class="<?= $field ?>" value="<?= e($old('slug')) ?>">
            <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('slugNote')) ?></p>
          </div>

          <button type="submit" class="border border-ink px-8 py-4 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= e($t('publish')) ?>
          </button>
        <?php endif; ?>

      </fieldset>
    <?php endforeach; ?>
  </form>

<?php endif; ?>

<?= Ui::sectionClose() ?>
```

İmzalar doğrulandı (`php/src/Ui.php:105`, `:17`, `:31`):
`pageHero(string $seed, string $title, string $eyebrow = '', string $text = '', string $height = 'md')`,
`sectionOpen(string $tone = 'cream', …)`, `sectionClose()`.

Renk markasının etiketi `de` ve **`tr`** taşır, `en` değil (`Design.php:130`) —
şablondaki `[$locale] ?? ['de']` geri düşüşü İngilizce sayfada `de`'yi basar
ve bu kasıtlı: panel DE+TR'de kaldı, site DE+EN'e geçti.

- [ ] **Step 5: Open the page and count the steps**

```bash
curl -s "http://127.0.0.1:8080/de/v2/einladung?design=elysee" | grep -c 'data-step='
```
Beklenen: `2` — Élysée'nin izinleri kapalı, yani `angaben` ve
`veroeffentlichen`. Başka bir sayı çıkarsa Task 2'ye dön.

```bash
curl -s "http://127.0.0.1:8080/de/v2/einladung?design=elysee" | grep -o 'name="\(bride\|groom\|date\|time\|venue\|address\|message\|hashtag\)"'
```
Beklenen: yalnızca Élysée'nin `bind`'larının karşılığı olan alanlar.

- [ ] **Step 6: Prove the step list is really derived**

Panelden (`/de/admin/designs/elysee`, 7. bölüm) bir metin katmanına `edit` ve
`color` işaretle, kaydet. Sonra:

```bash
curl -s "http://127.0.0.1:8080/de/v2/einladung?design=elysee" | grep -c 'data-step='
```
Beklenen: `3`. **Kodda hiçbir şey değişmeden.** Bu, fazın asıl iddiası.
İşaretleri geri al.

- [ ] **Step 7: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/templates/pages/invite-v2-wizard.php php/public/index.php php/data/dict.php
git commit -m "The wizard opens with as many steps as the design earns"
```

---

### Task 7: Yayınlama — POST'tan `invitations_v2` satırına

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php`

**Interfaces:**
- Consumes: `DesignWizard::personalize()` (Task 3), `InvitationsV2::create/slugAvailable/slug` (Task 4),
  `Media::store()`, `Security::checkCsrf/throttle/clean`, `Config::url()`
- Produces: `InviteV2Controller::publish(array $design): array` — başarıda
  `['slug','path','url']`, hatada `['error' => 'csrf'|'throttle'|'names']`

- [ ] **Step 1: Read the POST in the controller**

`wizard()` içinde, `$design` belirlendikten **sonra**, `View::page`'den önce:

```php
        $error = '';
        $done  = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $ergebnis = $this->publish($design);
            if (isset($ergebnis['error'])) {
                $error = (string) $ergebnis['error'];
            } else {
                $done = $ergebnis;
            }
        }
```

ve `View::page`'deki `'error' => ''`, `'done' => null` satırlarını
`'error' => $error`, `'done' => $done` yap.

POST'ta tasarım `$_GET` yerine `$_POST['design']`'den gelmeli. `$wunsch`
satırını değiştir:

```php
        // Nach dem Absenden steht die Wahl im Formular, nicht mehr in der
        // Adresse - sonst waehlte ein Neuladen ein anderes Design.
        $wunsch = Security::clean($_POST['design'] ?? $_GET['design'] ?? '', 96);
```

- [ ] **Step 2: Write publish()**

`InviteV2Controller`'a ekle (`use Atelier\Config; use Atelier\InvitationsV2; use Atelier\Media;`
başa eklenir):

```php
    /**
     * Die Einladung anlegen.
     *
     * Was der Kunde gewaehlt hat, wird nicht als Liste gespeichert, sondern auf
     * das Design gelegt: das Ergebnis ist der Schnappschuss. Damit ist das
     * Anzeigen spaeter genau Phase 1 - css() und html(), sonst nichts.
     *
     * @param array<string,mixed> $design
     * @return array<string,mixed>
     */
    private function publish(array $design): array
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return ['error' => 'csrf'];
        }
        // Eigener Schluessel: der alte Assistent soll diesen hier nicht
        // aussperren und umgekehrt.
        if (Security::throttle('invite-v2-create', 8, 900)) {
            return ['error' => 'throttle'];
        }

        $darf = DesignWizard::choices($design);

        $data = [];
        foreach ($darf['fields'] as $feld) {
            $data[$feld] = Security::clean($_POST[$feld] ?? '', $feld === 'message' ? 600 : 160);
        }

        // Gefragt und leer gelassen ist erlaubt - html() laesst die Zeile dann
        // einfach weg. Nur ohne jeden Namen weiss niemand, wessen Karte das ist.
        $brauchtNamen = in_array('bride', $darf['fields'], true) || in_array('groom', $darf['fields'], true);
        if ($brauchtNamen && ($data['bride'] ?? '') === '' && ($data['groom'] ?? '') === '') {
            return ['error' => 'names'];
        }

        $slug = InvitationsV2::slug(Security::clean($_POST['slug'] ?? '', 96));
        if ($slug === '') {
            $slug = InvitationsV2::slug(($data['bride'] ?? '') . '-' . ($data['groom'] ?? ''));
        }
        if ($slug === '') {
            $slug = 'einladung-' . bin2hex(random_bytes(3));
        }
        if (!InvitationsV2::slugAvailable($slug)) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        // Die Wahl einsammeln. Was hier hineingeht, wird in personalize()
        // noch einmal gegen die Rechte geprueft - diese Schleife ist Bequem-
        // lichkeit, nicht Sicherheit.
        $wahl = ['palette' => [], 'fonts' => [], 'layers' => []];

        foreach (array_keys($darf['palette']) as $marke) {
            $wert = Security::clean($_POST['palette_' . $marke] ?? '', 32);
            if ($wert !== '') {
                $wahl['palette'][$marke] = $wert;
            }
        }

        foreach (array_keys($darf['fonts']) as $marke) {
            $wert = Security::clean($_POST['fonts_' . $marke] ?? '', 64);
            if ($wert !== '') {
                $wahl['fonts'][$marke] = $wert;
            }
        }

        foreach ($darf['layers'] as $id => $rechte) {
            $eintrag = [];

            if ($rechte['color']) {
                $wert = Security::clean($_POST['layer_color_' . $id] ?? '', 32);
                if ($wert !== '') {
                    $eintrag['color'] = $wert;
                }
            }
            if ($rechte['font']) {
                $wert = Security::clean($_POST['layer_font_' . $id] ?? '', 64);
                if ($wert !== '') {
                    $eintrag['font'] = $wert;
                }
            }
            if ($rechte['text']) {
                $wert = Security::clean($_POST['layer_text_' . $id] ?? '', 600);
                if ($wert !== '') {
                    $eintrag['text'] = ['de' => $wert, 'en' => $wert];
                }
            }
            if ($rechte['hide'] && isset($_POST['layer_hidden_' . $id])) {
                $eintrag['hidden'] = true;
            }
            if ($rechte['photo']) {
                // Media::store() sieht in die Datei, nicht auf ihren Namen.
                $pfad = Media::store($_FILES['layer_src_' . $id] ?? [], 'einladungen/v2/' . $slug);
                if ($pfad !== null) {
                    $eintrag['src'] = $pfad;
                }
            }

            if ($eintrag !== []) {
                $wahl['layers'][$id] = $eintrag;
            }
        }

        $snapshot = DesignWizard::personalize($design, $wahl);

        $data['slug']      = $slug;
        $data['locale']    = I18n::locale();
        $data['paid']      = false;
        // Kein Bildschirm dafuer in dieser Phase - aber der Schluessel muss
        // von Anfang an dastehen. Nachtraeglich eingefuehrt sperrt er jede
        // bis dahin veroeffentlichte Einladung aus.
        $data['manageKey'] = bin2hex(random_bytes(16));
        $data['createdAt'] = date('c');

        InvitationsV2::create($slug, (string) $design['id'], $snapshot, $data);

        $path = I18n::path('/v2/einladung/' . $slug);

        return ['slug' => $slug, 'path' => $path, 'url' => Config::url() . $path];
    }
```

- [ ] **Step 3: Publish one from the browser**

`http://127.0.0.1:8080/de/v2/einladung?design=elysee` — isimleri doldur,
yayınla. Beklenen: "Eure Einladung steht" ve altında bir bağlantı.

- [ ] **Step 4: Check the row**

```bash
cd php && php -r 'require "src/bootstrap.php";
$r = \Atelier\Db::one("SELECT slug, design_id, JSON_VALID(design_snapshot) v, JSON_LENGTH(design_snapshot, \"$.layers\") n FROM invitations_v2 ORDER BY created_at DESC LIMIT 1");
var_dump($r);'
```
Beklenen: `v` = 1, `n` > 0, `design_id` = `elysee`.

- [ ] **Step 5: Prove the permission filter holds**

İzinsiz bir alanı zorlayan elle POST:

```bash
CSRF=$(curl -s -c /tmp/c.txt "http://127.0.0.1:8080/de/v2/einladung?design=elysee" | grep -o 'name="csrf" value="[^"]*"' | cut -d'"' -f4)
curl -s -b /tmp/c.txt -X POST http://127.0.0.1:8080/de/v2/einladung \
  -d "csrf=$CSRF" -d "design=elysee" -d "bride=Test" -d "groom=Zwang" \
  -d "layer_color_siegel=%238B0000" -o /dev/null -w "%{http_code}\n"
```
(`siegel` yerine Élysée'de `edit` izni **olmayan** gerçek bir katman kimliği
kullan — `php -r` ile `Design::find('elysee')['layers']` listesinden seç.)

Sonra son satırın snapshot'ında `kunde-` diye bir jeton **olmamalı**:

```bash
cd php && php -r 'require "src/bootstrap.php";
$r = \Atelier\Db::one("SELECT design_snapshot FROM invitations_v2 ORDER BY created_at DESC LIMIT 1");
$d = json_decode($r["design_snapshot"], true);
var_dump(array_filter(array_keys($d["palette"]), fn($k) => str_starts_with($k, "kunde-")));'
```
Beklenen: boş dizi. Doluysa süzgeç sızdırıyor — Task 3'e dön.

- [ ] **Step 6: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php
git commit -m "Publishing freezes the personalised document, not a list of edits"
```

---

### Task 8: `show()` — davetiyenin kendisi

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php`
- Create: `php/templates/pages/invite-v2-show.php`

**Interfaces:**
- Consumes: `InvitationsV2::find()` (Task 4), `View::partial('partials/design-stage')` (Task 5),
  `Design::css/html/bindValues`
- Produces: `InviteV2Controller::show(array $params): void`

- [ ] **Step 1: Write show()**

```php
    /**
     * Die fertige Einladung.
     *
     * Gezeigt wird der Schnappschuss, nicht das Design: wer die Vorlage im
     * Panel spaeter aendert, aendert diese Karte nicht. Genau dafuer gibt es
     * die Spalte.
     *
     * @param array<string,string> $params
     */
    public function show(array $params): void
    {
        $locale = I18n::locale();
        $einladung = InvitationsV2::find($params['slug'] ?? '');

        if ($einladung === null) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in
            // welcher Sprache sie aufgerufen wurde. DesignController::preview()
            // gibt sie aus genau diesem Grund mit.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        $doc = Design::complete($einladung['design_snapshot']);
        $values = Design::bindValues($einladung['data'], $locale);
        $scope = '.d-' . $doc['id'];

        $namen = trim(((string) ($einladung['data']['bride'] ?? '')) . ' & ' . ((string) ($einladung['data']['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-show', [
            'locale' => $locale,
            'path'   => I18n::path('/v2/einladung/' . $einladung['slug'], $locale),
            'meta'   => Seo::forPage('einladung2', [
                'title' => $namen !== '' ? $namen : I18n::t('invitation2.wizardTitle'),
                // Eine Einladung gehoert nicht in den Index. Der Link ist
                // fuer die Gaeste, nicht fuer die Suche.
                'noindex' => true,
                // Dieselbe Choreografie wie in der Design-Vorschau: Kuvert
                // oeffnen, Karte aufsteigen lassen. Ohne dieses Skript bleibt
                // das Kuvert zu.
                'scripts' => ['/assets/invitation.js'],
            ]),
            'design' => $doc,
            // OHNE Punkt. Design::css() bekommt den Selektor (".d-elysee"),
            // die Vorlage bekommt den Klassennamen ("d-elysee") - sie schreibt
            // ihn in ein class-Attribut. Mit Punkt entstuende die Klasse
            // ".d-elysee", die der Selektor .d-elysee niemals trifft, und die
            // Einladung kaeme voellig ungestylt heraus. DesignController::
            // preview() macht es aus demselben Grund so (DesignController.php:125).
            'scope'  => ltrim($scope, '.'),
            'styles' => Design::css($doc, $scope),
            // Die fuenf Bewegungswerte rechnet sonst design-preview.php aus.
            // Die Buehne liest sie, leitet sie aber nicht selbst ab - eine
            // Rechnung, eine Quelle der Wahrheit (Aufgabe 5).
            'ratio'   => str_replace(':', ' / ', (string) $doc['canvas']['ratio']),
            'karteAn' => (string) $doc['animation']['card'],
            'tempo'   => (int) $doc['animation']['speed'],
            'introMs' => 0,
            'idle'    => (string) $doc['animation']['idle'],
            // Die Initialen stehen auf dem Siegel. Sie kommen aus den Daten
            // des Paares, nicht aus dem Dokument.
            'initialen' => $values['initials'],
            // Leer, und zwar immer. Die Buehne zeigt Warnungen ungeprueft an
            // (design-stage.php:50) - auf einer echten Einladung hat ein Gast
            // nichts mit den Maengeln einer Vorlage zu tun. Waere der Wert gar
            // nicht gesetzt, stuende dort eine leere Box: null !== [] ist wahr.
            'warnings' => [],
            'seite'  => Design::html($doc, $values, $locale, 'page'),
            'kuvert' => Design::html($doc, $values, $locale, 'envelope'),
            'karte'  => Design::html($doc, $values, $locale, 'card'),
        ]);
    }
```

- [ ] **Step 2: Write the template**

`php/templates/pages/invite-v2-show.php`:

```php
<?php
/**
 * Eine echte Einladung.
 *
 * Dieselbe Buehne wie die Design-Vorschau, mit den Daten des Paares statt der
 * Beispieldaten - und ohne Leiste darunter: wer diese Seite oeffnet, ist
 * eingeladen und nicht auf Vorlagensuche.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $seite
 * @var string $kuvert
 * @var string $karte
 * @var string $locale
 */

use Atelier\View;
?>
<?= View::partial('partials/design-stage', [
    'design' => $design,
    'scope'  => $scope,
    'styles' => $styles,
    'seite'  => $seite,
    'kuvert' => $kuvert,
    'karte'  => $karte,
    'locale' => $locale,
]) ?>
```

- [ ] **Step 3: Open the invitation you published in Task 7**

Task 7'nin verdiği bağlantıyı aç. Beklenen: zarf görünüyor, tıklayınca
açılıyor, kartta **girdiğin isimler ve tarih** duruyor — örnek veri değil.

- [ ] **Step 4: Prove the snapshot freezes**

Panelden (`/de/admin/designs/elysee`) bir renk markasını belirgin biçimde
değiştir (örneğin `accent`'i `#FF0000` yap), kaydet.

- `/de/v2/designs/elysee` — **yeni** rengi gösterir.
- Task 7'de yayınlanan davetiye — **eski** rengi gösterir.

İkincisi de değiştiyse snapshot okunmuyor demektir; `show()` `Design::find()`
çağırıyor olabilir. Rengi geri al.

- [ ] **Step 5: Check the missing-invitation case and the meta tag**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8080/de/v2/einladung/gibtesnicht
curl -s "http://127.0.0.1:8080/de/v2/einladung/<slug>" | grep -c 'name="robots" content="noindex'
```
Beklenen: `404`, sonra `1`.

- [ ] **Step 6: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/templates/pages/invite-v2-show.php
git commit -m "The invitation shows its snapshot, so a later edit to the design leaves it alone"
```

---

### Task 9: `invite-v2.js` — adım geçişi ve canlı önizleme

Betik **hiçbir kural taşımaz**: hangi adımların olduğu ve hangi alanların
sorulduğu sunucuda belli. Betik yalnızca görünürlük ve önizleme.

**Files:**
- Create: `php/public/assets/invite-v2.js`

**Interfaces:**
- Consumes: `[data-wizard]`, `[data-step]`, `[data-step-label]`, `[data-live]`,
  `[data-preview]` — hepsi Task 6'nın şablonundan
- Produces: yok (tarayıcı tarafı)

- [ ] **Step 1: Write the script**

```javascript
/*
 * Der Assistent der zweiten Fassung: Schritte ein- und ausblenden, Vorschau
 * mitlaufen lassen.
 *
 * Ohne dieses Skript funktioniert das Formular vollstaendig - alle Schritte
 * stehen dann untereinander und ein Absenden reicht. Deshalb wird hier auch
 * nichts geprueft: welche Felder es gibt, hat der Server entschieden, bevor
 * die Seite ankam.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-wizard]');
  if (!form) return;

  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
  var labels = Array.prototype.slice.call(form.querySelectorAll('[data-step-label]'));
  if (steps.length < 2) return;   // Ein Schritt braucht keine Navigation.

  var at = 0;

  var nav = document.createElement('div');
  nav.className = 'mt-10 flex items-center justify-between gap-4';

  var back = document.createElement('button');
  back.type = 'button';
  back.className = 'border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-muted';
  back.textContent = document.documentElement.lang === 'de' ? 'Zurück' : 'Back';

  var next = document.createElement('button');
  next.type = 'button';
  next.className = 'border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-ink';
  next.textContent = document.documentElement.lang === 'de' ? 'Weiter' : 'Next';

  nav.appendChild(back);
  nav.appendChild(next);
  form.appendChild(nav);

  function show(i) {
    at = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach(function (el, n) { el.hidden = n !== at; });
    labels.forEach(function (el, n) {
      el.className = n === at
        ? 'text-ink'
        : 'text-muted';
    });
    back.hidden = at === 0;
    // Auf dem letzten Schritt steht der Absenden-Knopf im Formular selbst.
    next.hidden = at === steps.length - 1;
    window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
  }

  back.addEventListener('click', function () { show(at - 1); });
  next.addEventListener('click', function () {
    // Pflichtfelder des aktuellen Schrittes zuerst.
    var pflicht = steps[at].querySelectorAll('[required]');
    for (var i = 0; i < pflicht.length; i++) {
      if (!pflicht[i].reportValidity()) return;
    }
    show(at + 1);
  });

  show(0);

  /*
   * Vorschau: die gebundenen Felder heissen im Dokument anders als im
   * Formular. Diese Zuordnung ist die einzige Stelle, an der der Browser
   * etwas ueber bind-Namen wissen muss.
   */
  var preview = form.querySelector('[data-preview]');
  if (!preview) return;

  function paint() {
    var werte = {};
    form.querySelectorAll('[data-live]').forEach(function (el) {
      werte[el.getAttribute('data-live')] = el.value.trim();
    });

    // couple_names und initials setzen sich aus zwei Feldern zusammen; der
    // Rest ist eins zu eins.
    var text = {
      couple_names: [werte.bride, werte.groom].filter(Boolean).join(' & '),
      initials: (werte.bride || ' ').charAt(0) + (werte.groom || ' ').charAt(0),
      bride_name: werte.bride || '',
      groom_name: werte.groom || '',
      wedding_date: werte.date || '',
      wedding_time: werte.time || '',
      location_name: werte.venue || '',
      location_address: werte.address || '',
      invitation_text: werte.message || '',
      hashtag: werte.hashtag || ''
    };

    // Welches Element welches bind traegt, steht im Markup (data-bind, siehe
    // Schritt 2) - nicht hier. Ein Design ohne wedding_time hat die Zeile
    // nicht, und dann findet querySelector nichts. Das ist richtig so.
    Object.keys(text).forEach(function (bind) {
      var ziel = preview.querySelector('[data-bind="' + bind + '"]');
      if (ziel) ziel.textContent = text[bind];
    });
  }

  form.querySelectorAll('[data-live]').forEach(function (el) {
    el.addEventListener('input', paint);
  });
  paint();
})();
```

- [ ] **Step 2: The preview needs `data-bind` in the markup**

Yukarıdaki `paint()` `[data-bind="…"]` arıyor ama `Design::html()` bunu
basmıyor (`Design.php:490`). İki seçenek var; **birincisi seçilir**:

`Design::html()`'de metin elementine bir veri niteliği eklenir — tek satır:

```php
                $out .= '<div class="' . e($class) . '"'
                    . ($el['bind'] !== '' ? ' data-bind="' . e($el['bind']) . '"' : '')
                    . '>' . e($text) . '</div>';
```

Bu Global Constraints'teki "`Design.php`'de tek değişiklik" kuralını genişletir.
Gerekçe planda yazılı olsun: canlı önizlemenin başka yolu, `bind` haritasını
tarayıcıya ayrıca göndermek olurdu — yani belgenin ikinci bir kopyası.
Nitelik `html()`'in çıktısını yalnızca genişletir; mevcut testler
(`design_html.php`) bunu görmeli ve **geçmeye devam etmeli**.

```bash
cd php && php bin/test.php design_html
```
Beklenen: geçiyor. Kırılıyorsa test `assert_same` ile tam çıktı karşılaştırıyor
demektir — o testi yeni niteliği içerecek şekilde güncelle ve **neden**
eklendiğini test dosyasına yaz.

- [ ] **Step 3: Try it in the browser**

`http://127.0.0.1:8080/de/v2/einladung?design=elysee` — iki adım var, "Weiter"
çalışıyor, "Zurück" ilk adımda gizli. Yayınlama adımındaki karta isim
yazdıkça isim değişiyor.

- [ ] **Step 4: Turn the script off**

Tarayıcıda JavaScript'i kapat ve sayfayı yenile. Beklenen: iki adım da alt
alta görünüyor, tek "Veröffentlichen" düğmesi var ve davetiye oluşuyor.

- [ ] **Step 5: Commit**

```bash
git add php/public/assets/invite-v2.js php/src/Design.php php/tests/design_html.php
git commit -m "The script only shows and hides; without it the form still works"
```

---

### Task 10: Panelin izin etiketleri

`edit`, `color`, `hide` diye ham İngilizce basılıyor. 3B bu kelimelere anlam
verdi (spec §5); panel de artık onları söyleyebilir.

**Files:**
- Modify: `php/templates/admin/design-edit-sections.php:155-172`

- [ ] **Step 1: Replace the raw names with words**

`foreach (Design::PERMISSIONS as $recht)` döngüsünün üstüne bir harita, içine
etiket:

```php
<?= $auf($tr ? '7 · Müşteri izinleri' : '7 · Kundenrechte') ?>
  <p class="<?= $label ?>">
    <?= $tr
        ? 'Sihirbaz bu bayrakları okur. "Düzenlenebilir" ana şalterdir: kapalıyken diğer beşi sayılmaz.'
        : 'Der Assistent liest diese Haken. „Bearbeitbar" ist der Hauptschalter: ist er aus, zählen die anderen fünf nicht.' ?>
  </p>
  <?php
  $rechteNamen = [
      'edit'  => ['de' => 'Bearbeitbar', 'tr' => 'Düzenlenebilir'],
      'color' => ['de' => 'Farbe',       'tr' => 'Renk'],
      'font'  => ['de' => 'Schrift',     'tr' => 'Yazı tipi'],
      'photo' => ['de' => 'Bild',        'tr' => 'Görsel'],
      'text'  => ['de' => 'Text',        'tr' => 'Metin'],
      'hide'  => ['de' => 'Ausblendbar', 'tr' => 'Gizlenebilir'],
  ];
  ?>
  <?php foreach ($design['layers'] as $ebene) : ?>
    <div class="flex flex-wrap items-center gap-4 border-b border-sand-deep py-2">
      <span class="w-56 text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
      <?php foreach (Design::PERMISSIONS as $recht) : ?>
        <label class="flex items-center gap-2 text-[0.66rem] <?= $recht === 'edit' ? 'text-ink' : 'text-muted' ?>">
          <input type="checkbox" name="perm_<?= e($recht) ?>_<?= e($ebene['id']) ?>" <?= $ebene['permissions'][$recht] ? 'checked' : '' ?>>
          <?= e($rechteNamen[$recht][$tr ? 'tr' : 'de']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?= $zu ?>
```

`$tr` ve `$label` değişkenlerinin bu dosyada zaten tanımlı olduğunu doğrula
(dosyanın başı); değilse `design-edit-sections.php`'deki mevcut kullanımlarını
kopyala.

- [ ] **Step 2: Check the panel**

`/de/admin/designs/elysee` → 7. bölüm. Beklenen: kutular kelimelerle,
"Bearbeitbar" koyu ve önde, üstünde ana şalteri açıklayan cümle. Kutuların
`name` nitelikleri **değişmemiş** (`perm_edit_<id>`), yani kaydetme çalışıyor.

- [ ] **Step 3: Save and confirm nothing broke**

Bir kutuyu işaretle, kaydet, sayfayı yenile — işaret duruyor.

```bash
cd php && php bin/test.php design_admin
```
Beklenen: geçiyor.

- [ ] **Step 4: Commit**

```bash
git add php/templates/admin/design-edit-sections.php
git commit -m "The permission boxes say what they do, now that they mean something"
```

---

## Kapanış kontrolü

Bütün görevler bittikten sonra, spec §12'deki listeyi baştan sona geç:

- [ ] `php bin/test.php` — sıfır hata
- [ ] `/de/v2/einladung` iki adım (Élysée), izin açılınca üç
- [ ] Yayınlanan davetiye `/de/v2/einladung/{slug}` adresinde görünüyor
- [ ] Tasarım sonradan değişince yayınlanmış davetiye değişmiyor
- [ ] `/de/v2/designs` ve `/de/v2/designs/elysee` 3A'daki gibi
- [ ] Betiksiz tarayıcıda davetiye oluşuyor
- [ ] İzinsiz POST sessizce düşüyor
- [ ] Eski motor diff'te yok:

```bash
git diff --name-only master... | grep -E 'InviteController|invite-wizard|Invitations\.php|Themes\.php|Pricing\.php|designs\.php|^app/|^lib/'
```
Beklenen: **boş çıktı**.
