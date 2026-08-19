# Davetiye v2 — Faz 3C: Bölüm sistemi — Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Yayınlanmış bir v2 davetiyesi kartın altında gerçek içerik taşısın —
adres ve harita, geri sayım, aileler, program — ve bunları tasarımcı kursun,
müşteri yalnızca izin verilenleri açıp kapatsın.

**Architecture:** Bölümler tasarım belgesinde (`sections`) yaşar, tıpkı
katmanlar gibi: sıra dizi sırasıdır, renk ve yazı **jeton anahtarıdır**, izinler
`edit` ana şalteriyle çalışır. Müşterinin açma/kapaması `personalize()` ile
belgeye işlenir ve `design_snapshot` olarak donar; bölüm **içeriği** (aile
isimleri, program satırları) `data`'ya gider. Yeni bir "belge + yamalar" mantığı
doğmaz. Paylaşılan sahne partial'ı bir kip kazanır: vitrinde tam ekran sabit,
davetiyede akışta — böylece bölümler kartın altında kayabilir.

**Tech Stack:** PHP 8.3, MariaDB, kendi yönlendiricisi, kendi şablon motoru
(`View::page`/`View::partial`), bağımlılıksız test çalıştırıcısı
(`php bin/test.php`). Composer yok, Node yok.

**Spec:** `docs/superpowers/specs/2026-08-20-davetiye-v2-faz3c-bolumler-design.md`

## Global Constraints

- **Yalnızca `php/` altında çalışılır.** `app/`, `lib/`, `scripts/` hiç değişmez.
- **Eski motora dokunulmaz.** Şu dosyalar diff'te **hiç geçmemeli**:
  `php/src/Controllers/InviteController.php`, `php/templates/pages/invitation.php`,
  `php/templates/pages/invite-wizard.php`, `php/src/Invitations.php`,
  `php/src/Themes.php`, **`php/src/Pricing.php`**, `php/templates/pages/designs.php`.
- **`php/src/Design.php`'de tek değişiklik:** `key()` `private` → `public`
  (Görev 1). Gövdesi değişmez, başka satır değişmez. Gerekçe: `DesignSections`
  bölüm kimliklerini aynı kuralla normalleştirmek zorunda; ikinci bir
  normalleştirici "anahtar nedir" sorusuna iki cevap üretirdi. `safeColor`,
  `safeFont` ve `safeSrc` Faz 3B'de aynı gerekçeyle açılmıştı.
- **Veritabanı şeması değişmez.** Bölüm içeriği `invitations_v2.data` JSON'unun
  içine girer.
- **Testler veritabanısız çalışır.** `bin/test.php` `config.php` yüklemez.
  Zaman bağımlı hiçbir test olmayacak: `visible()` ve `html()` referans tarihi
  parametre olarak alır.
- **Vitrin çıktısı değişmez.** `/de/v2/designs/elysee` ve `/de/v2/designs`
  bu planın hiçbir noktasında bayt değiştirmemeli (Görev 4'ün ölçütü).
- **Sözlük anahtarı eklenir, mevcut anahtar değişmez.** Her yeni anahtar
  `data/dict.php` içindeki **üç** dil kümesine de girer.
- **Yorumlar Almanca**, `php/src` üslubuyla: ne yaptığını değil **neden** öyle
  olduğunu anlatır.
- Şablonda basılan her değer `e()` içinden geçer.
- **Dev sunucu:** `cd php && php -S 127.0.0.1:8131 -t public public/dev-router.php`
  — `-t public` **şart**, yoksa `/assets/*` 404 döner.

---

### Task 1: `DesignSections::complete()` — bölümün normalleştirilmesi

**Files:**
- Create: `php/src/DesignSections.php`
- Modify: `php/src/Design.php` — `key()` `private` → `public`
- Test: `php/tests/design_sections.php`

**Interfaces:**
- Consumes: `Design::key()` (bu görevde `public` oluyor)
- Produces:
  - `DesignSections::TYPES = ['location','countdown','family','program']`
  - `DesignSections::complete(array $doc): array` — `$doc['sections']`'ı
    normalleştirir, tanınmayanı düşürür, diğer alanlara dokunmaz

- [ ] **Step 1: Write the failing test**

`php/tests/design_sections.php`:

```php
<?php
declare(strict_types=1);

use Atelier\DesignSections;

/*
 * Abschnitte sind das, was unter der Karte steht: Ort, Countdown, Familien,
 * Programm. Sie gehoeren dem Dokument, nicht der Einladung - der Grafiker
 * stellt sie auf, der Kunde darf hoechstens zu- und abschalten.
 *
 * Dieselbe Form wie bei den Ebenen: die Reihenfolge ist die Reihenfolge im
 * Feld, Farbe und Schrift sind Markennamen, und edit ist der Hauptschalter.
 */

function sec_doc(array $sections): array
{
    return ['id' => 'test', 'slug' => 'test', 'sections' => $sections];
}

$doc = DesignSections::complete(sec_doc([
    ['id' => 'ort-1', 'type' => 'location'],
    ['id' => 'unbekannt', 'type' => 'wetterbericht'],
    ['id' => 'prog-1', 'type' => 'program', 'enabled' => false,
     'title' => ['de' => 'Ablauf', 'en' => 'Schedule'],
     'style' => ['color' => 'Accent', 'font' => 'display'],
     'permissions' => ['edit' => true, 'hide' => true]],
]));

assert_same(2, count($doc['sections']), 'complete: unbekannter Typ faellt weg');
assert_same('ort-1', $doc['sections'][0]['id'], 'complete: Reihenfolge ist die des Feldes');
assert_same('prog-1', $doc['sections'][1]['id'], 'complete: Reihenfolge ist die des Feldes (zweiter)');

// Vollstaendig, auch wo nichts angegeben war.
$erste = $doc['sections'][0];
assert_same('location', $erste['type'], 'complete: Typ bleibt');
assert_same(true, $erste['enabled'], 'complete: enabled ist standardmaessig an');
assert_same('', $erste['title']['de'], 'complete: fehlender Titel wird leer');
assert_same('', $erste['style']['color'], 'complete: fehlende Farbmarke wird leer');
assert_same(false, $erste['permissions']['edit'], 'complete: Rechte sind standardmaessig zu');
assert_same(false, $erste['permissions']['hide'], 'complete: Rechte sind standardmaessig zu');

// Angegebenes bleibt - und der Markenname wird normalisiert wie ueberall sonst.
$zweite = $doc['sections'][1];
assert_same(false, $zweite['enabled'], 'complete: enabled=false bleibt');
assert_same('Ablauf', $zweite['title']['de'], 'complete: Titel bleibt');
assert_same('accent', $zweite['style']['color'], 'complete: Markenname wird kleingeschrieben');
assert_same(true, $zweite['permissions']['edit'], 'complete: gesetztes Recht bleibt');

// Ohne Kennung kein Abschnitt: er waere im CSS nicht adressierbar.
$ohne = DesignSections::complete(sec_doc([['type' => 'family']]));
assert_same([], $ohne['sections'], 'complete: ohne id faellt der Abschnitt weg');

// Was kein Feld ist, ist kein Abschnitt.
$mist = DesignSections::complete(sec_doc(['etwas', 42, null]));
assert_same([], $mist['sections'], 'complete: Unsinn im Feld faellt weg');

// Der Rest des Dokuments bleibt unberuehrt.
$rest = DesignSections::complete(['id' => 'x', 'layers' => [['id' => 'a']], 'sections' => []]);
assert_same(1, count($rest['layers']), 'complete: layers bleiben unangetastet');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: `Class "Atelier\DesignSections" not found`.

- [ ] **Step 3: Open `Design::key()`**

`php/src/Design.php`, `private static function key(` satırı. Yalnızca
görünürlük ve doküman yorumu değişir, **gövde aynı kalır**:

```php
    /**
     * Adresstauglicher Schluessel: Kleinbuchstaben, Ziffern, Bindestrich.
     *
     * Oeffentlich, weil die Abschnitte dieselbe Regel brauchen. Zwei
     * Normalisierer hiessen zwei Antworten auf "was ist ein Schluessel", und
     * die zweite faellt beim ersten Umlaut auseinander.
     */
    public static function key(string $value): string
```

- [ ] **Step 4: Write the implementation**

`php/src/DesignSections.php`:

```php
<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Was unter der Karte steht.
 *
 * Die Karte ist ein fester Rahmen: jede Ebene sitzt auf Prozentkoordinaten.
 * Ein Programm mit drei Zeilen und eines mit zwoelf passen aber nicht in
 * denselben Kasten - deshalb sind Abschnitte kein vierter Ort, sondern ein
 * fliessendes Dokument unter der Buehne.
 *
 * Sie gehoeren dem Dokument, nicht der Einladung: der Grafiker stellt sie auf
 * und bestimmt Reihenfolge, Farbe und Schrift; der Kunde darf hoechstens
 * zu- und abschalten, was freigegeben ist. Die Reihenfolge im Feld ist die
 * Reihenfolge auf der Seite - genau wie bei den Ebenen der z-Index.
 *
 * Der Katalog ist fest, und das ist Absicht: ein Countdown muss ticken, ein
 * Kartenlink eine Adresse kodieren. Abschnitte aufstellen, faerben, an- und
 * abschalten ist Daten; eine neue Art Abschnitt ist Code.
 *
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST, und kein
 * Blick auf die Uhr: das Bezugsdatum kommt als Parameter herein.
 */
final class DesignSections
{
    /** Welche Arten es gibt. Alles andere faellt beim Einlesen weg. */
    public const TYPES = ['location', 'countdown', 'family', 'program'];

    /**
     * Vollstaendige Abschnitte, in der Reihenfolge des Feldes.
     *
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function complete(array $doc): array
    {
        $out = [];

        foreach ((array) ($doc['sections'] ?? []) as $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }

            $type = (string) ($eintrag['type'] ?? '');
            if (!in_array($type, self::TYPES, true)) {
                // Unbekannt faellt still. Ein Dokument soll sich nicht wegen
                // eines Werts aus dem Panel nicht mehr oeffnen lassen.
                continue;
            }

            // Ohne Kennung waere der Abschnitt im Stilblock nicht adressierbar.
            $id = Design::key((string) ($eintrag['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $title = is_array($eintrag['title'] ?? null) ? $eintrag['title'] : [];
            $style = is_array($eintrag['style'] ?? null) ? $eintrag['style'] : [];
            $recht = is_array($eintrag['permissions'] ?? null) ? $eintrag['permissions'] : [];

            $out[] = [
                'id'      => $id,
                'type'    => $type,
                'title'   => [
                    'de' => (string) ($title['de'] ?? ''),
                    'en' => (string) ($title['en'] ?? ''),
                ],
                'enabled' => (bool) ($eintrag['enabled'] ?? true),
                'style'   => [
                    // Markennamen, keine Werte: der Renderer schreibt
                    // var(--d-<name>). Ein roher Wert ergaebe ungueltiges CSS.
                    'color' => Design::key((string) ($style['color'] ?? '')),
                    'font'  => Design::key((string) ($style['font'] ?? '')),
                ],
                'permissions' => [
                    // edit ist der Hauptschalter, wie bei den Ebenen: ohne ihn
                    // zaehlt hide nicht.
                    'edit' => (bool) ($recht['edit'] ?? false),
                    'hide' => (bool) ($recht['hide'] ?? false),
                ],
            ];
        }

        $doc['sections'] = $out;

        return $doc;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: bütün `complete:` kontrolleri geçiyor.

- [ ] **Step 6: Run the whole suite**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php
```
Beklenen: **339 + yeni kontroller**, sıfır hata. `design_complete.php` özellikle
geçmeli — bölümsüz bir belgede `sections` hâlâ boş liste.

- [ ] **Step 7: Commit**

```bash
git add php/src/DesignSections.php php/src/Design.php php/tests/design_sections.php
git commit -m "A section belongs to the document, and an unknown one falls silently"
```

---

### Task 2: `DesignSections::visible()` — hangileri gerçekten basılır

**Files:**
- Modify: `php/src/DesignSections.php`
- Test: `php/tests/design_sections.php`

**Interfaces:**
- Consumes: `DesignSections::complete()` (Görev 1)
- Produces:
  - `DesignSections::programRows(array $data): array` — `[{time,title}]`, en çok
    `PROGRAM_MAX`, başlıksız satır düşer
  - `DesignSections::visible(array $doc, array $data, string $heute = ''): array`
    — basılacak bölümler; `$heute` boşsa bugünün tarihi (`Y-m-d`)
  - `DesignSections::PROGRAM_MAX = 20`, `PROGRAM_LEN = 80`

- [ ] **Step 1: Write the failing test**

`php/tests/design_sections.php` sonuna:

```php
/*
 * Ein leerer Abschnitt wird nicht gedruckt.
 *
 * Dieselbe Regel wie bei einem gebundenen Textelement ohne Wert: er faellt
 * weg, statt eine leere Ueberschrift zu hinterlassen. Der Kunde muss nichts
 * abschalten, was er ohnehin nicht ausgefuellt hat.
 *
 * Das Bezugsdatum kommt als Parameter - ein Test, der von der Uhr abhaengt,
 * faellt irgendwann von selbst um.
 */

$alle = sec_doc([
    ['id' => 'ort-1',  'type' => 'location'],
    ['id' => 'cd-1',   'type' => 'countdown'],
    ['id' => 'fam-1',  'type' => 'family'],
    ['id' => 'prog-1', 'type' => 'program'],
]);

$leer = DesignSections::visible($alle, [], '2027-01-01');
assert_same([], $leer, 'visible: ohne Daten wird nichts gedruckt');

$voll = DesignSections::visible($alle, [
    'address'  => 'Elmau 2, 82493 Krün',
    'date'     => '2027-06-12',
    'families' => ['bride' => 'Familie Weber', 'groom' => ''],
    'program'  => [['time' => '15:00', 'title' => 'Trauung']],
], '2027-01-01');
assert_same(4, count($voll), 'visible: mit Daten werden alle vier gedruckt');

// Ort ohne Adresse: der Kartenlink haette kein Ziel.
$ohneOrt = DesignSections::visible($alle, ['date' => '2027-06-12'], '2027-01-01');
assert_same(['cd-1'], array_column($ohneOrt, 'id'), 'visible: ohne Adresse kein Ort');

// Ein vergangener Termin bekommt keinen Countdown.
$vorbei = DesignSections::visible($alle, ['date' => '2026-06-12'], '2027-01-01');
assert_same([], $vorbei, 'visible: vergangenes Datum, kein Countdown');

// Der Tag selbst zaehlt noch.
$heute = DesignSections::visible($alle, ['date' => '2027-01-01'], '2027-01-01');
assert_same(['cd-1'], array_column($heute, 'id'), 'visible: der Tag selbst zaehlt noch');

// Eine Familie reicht.
$eine = DesignSections::visible($alle, ['families' => ['groom' => 'Familie Yılmaz']], '2027-01-01');
assert_same(['fam-1'], array_column($eine, 'id'), 'visible: eine Familie reicht');

// Was der Grafiker abgeschaltet hat, bleibt abgeschaltet.
$aus = DesignSections::visible(sec_doc([
    ['id' => 'fam-1', 'type' => 'family', 'enabled' => false],
]), ['families' => ['bride' => 'Familie Weber']], '2027-01-01');
assert_same([], $aus, 'visible: enabled=false bleibt weg');

// Programmzeilen: ohne Titel keine Zeile, Uhrzeit darf fehlen.
$zeilen = DesignSections::programRows(['program' => [
    ['time' => '15:00', 'title' => 'Trauung'],
    ['time' => '16:00', 'title' => ''],
    ['title' => 'Dinner'],
    'unsinn',
]]);
assert_same(2, count($zeilen), 'programRows: ohne Titel keine Zeile');
assert_same('', $zeilen[1]['time'], 'programRows: Uhrzeit darf fehlen');

// Obergrenze: was darueber liegt, faellt weg statt die Seite zu sprengen.
$viele = [];
for ($i = 0; $i < 40; $i++) {
    $viele[] = ['time' => '10:00', 'title' => 'Punkt ' . $i];
}
assert_same(DesignSections::PROGRAM_MAX, count(DesignSections::programRows(['program' => $viele])), 'programRows: Obergrenze greift');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: `Call to undefined method Atelier\DesignSections::visible()`.

- [ ] **Step 3: Write the implementation**

`DesignSections` sınıfına, `complete()` altına:

```php
    /** Wie viele Programmzeilen, und wie lang eine sein darf. */
    public const PROGRAM_MAX = 20;
    public const PROGRAM_LEN = 80;

    /**
     * Die Zeilen des Programms, sauber.
     *
     * Ohne Titel keine Zeile: eine Uhrzeit allein sagt nichts. Die Obergrenze
     * schneidet ab, statt die Eingabe abzulehnen - eine Einladung soll nicht
     * an einer zu langen Liste scheitern.
     *
     * @param array<string,mixed> $data
     * @return list<array{time:string,title:string}>
     */
    public static function programRows(array $data): array
    {
        $out = [];

        foreach ((array) ($data['program'] ?? []) as $zeile) {
            if (!is_array($zeile)) {
                continue;
            }
            $titel = trim((string) ($zeile['title'] ?? ''));
            if ($titel === '') {
                continue;
            }
            $out[] = [
                'time'  => mb_substr(trim((string) ($zeile['time'] ?? '')), 0, self::PROGRAM_LEN),
                'title' => mb_substr($titel, 0, self::PROGRAM_LEN),
            ];
            if (count($out) >= self::PROGRAM_MAX) {
                break;
            }
        }

        return $out;
    }

    /**
     * Welche Abschnitte wirklich gedruckt werden.
     *
     * Getrennt von html(), weil zwei Stellen dieselbe Frage stellen: der
     * Renderer, um zu drucken, und der Assistent, um zu wissen, ob er nach
     * Inhalt fragen muss.
     *
     * $heute kommt herein statt aus date() - sonst haengt ein Test an der Uhr.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     * @return list<array<string,mixed>>
     */
    public static function visible(array $doc, array $data, string $heute = ''): array
    {
        $doc = self::complete($doc);
        $heute = $heute !== '' ? $heute : date('Y-m-d');

        $out = [];
        foreach ($doc['sections'] as $abschnitt) {
            if (!$abschnitt['enabled']) {
                continue;
            }
            if (!self::hatInhalt($abschnitt, $data, $heute)) {
                continue;
            }
            $out[] = $abschnitt;
        }

        return $out;
    }

    /**
     * Hat dieser Abschnitt etwas zu zeigen?
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function hatInhalt(array $abschnitt, array $data, string $heute): bool
    {
        $familien = is_array($data['families'] ?? null) ? $data['families'] : [];
        $datum = trim((string) ($data['date'] ?? ''));

        return match ((string) $abschnitt['type']) {
            // Ohne Adresse haette der Kartenlink kein Ziel.
            'location'  => trim((string) ($data['address'] ?? '')) !== '',
            // Ein vergangener Termin bekommt keinen Countdown; der Tag selbst
            // zaehlt noch, es wird ja bis zum Morgen gefeiert.
            'countdown' => $datum !== '' && $datum >= $heute,
            'family'    => trim((string) ($familien['bride'] ?? '')) !== ''
                        || trim((string) ($familien['groom'] ?? '')) !== '',
            'program'   => self::programRows($data) !== [],
            default     => false,
        };
    }
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: bütün `visible:` ve `programRows:` kontrolleri geçiyor.

- [ ] **Step 5: Commit**

```bash
git add php/src/DesignSections.php php/tests/design_sections.php
git commit -m "An empty section is not printed, and a past date gets no countdown"
```

---

### Task 3: `DesignSections::css()` ve `html()` — basım

**Files:**
- Modify: `php/src/DesignSections.php`
- Test: `php/tests/design_sections.php`

**Interfaces:**
- Consumes: `DesignSections::visible()`, `programRows()` (Görev 2), `Dates::long()`
- Produces:
  - `DesignSections::css(array $doc, string $scope): string`
  - `DesignSections::html(array $doc, array $data, string $locale, string $heute = ''): string`

- [ ] **Step 1: Write the failing test**

`php/tests/design_sections.php` sonuna:

```php
/*
 * Gedruckt wird gegen Markennamen, nicht gegen Werte.
 *
 * Dieselbe Lehre wie in Phase 3B: der Renderer schreibt var(--d-<name>).
 * Stuende dort ein roher Wert, ergaebe das var(--d-#B08D57) - ungueltiges CSS
 * und ein farbloses Element, das niemandem auffaellt.
 */

$stil = sec_doc([
    ['id' => 'prog-1', 'type' => 'program',
     'style' => ['color' => 'accent', 'font' => 'display']],
    ['id' => 'fam-1', 'type' => 'family'],
]);

$css = DesignSections::css($stil, '.d-elysee');
assert_contains($css, '.d-elysee .d-sec-prog-1{', 'css: Abschnitt wird im Bereich adressiert');
assert_contains($css, 'color:var(--d-accent)', 'css: Farbe kommt als Marke');
assert_contains($css, 'font-family:var(--df-display)', 'css: Schrift kommt als Marke');
assert_not_contains($css, '.d-sec-fam-1{', 'css: ohne Stil keine Regel');

$daten = [
    'address'  => 'Elmau 2, 82493 Krün',
    'date'     => '2027-06-12',
    'families' => ['bride' => 'Familie Weber', 'groom' => 'Familie Yılmaz'],
    'program'  => [['time' => '15:00', 'title' => 'Trauung']],
];

$html = DesignSections::html(sec_doc([
    ['id' => 'ort-1',  'type' => 'location',  'title' => ['de' => 'Ort', 'en' => 'Place']],
    ['id' => 'cd-1',   'type' => 'countdown', 'title' => ['de' => '', 'en' => '']],
    ['id' => 'fam-1',  'type' => 'family',    'title' => ['de' => 'Familien', 'en' => 'Families']],
    ['id' => 'prog-1', 'type' => 'program',   'title' => ['de' => 'Ablauf', 'en' => 'Schedule']],
]), $daten, 'de', '2027-01-01');

assert_contains($html, 'class="d-sec d-sec-ort-1 d-sec-location"', 'html: Kennung und Art stehen in der Klasse');
assert_contains($html, '<h2', 'html: Titel wird gedruckt');
assert_contains($html, 'Ort', 'html: der deutsche Titel');
assert_not_contains($html, '<h2 class="d-sec-title"></h2>', 'html: leerer Titel wird nicht gedruckt');
assert_contains($html, 'Elmau 2', 'html: die Adresse steht da');
assert_contains($html, 'google.com/maps', 'html: der Kartenlink wird gebaut');
assert_contains($html, 'data-countdown="2027-06-12"', 'html: der Countdown traegt sein Datum');
assert_contains($html, 'Familie Weber', 'html: die Familie steht da');
assert_contains($html, 'Trauung', 'html: die Programmzeile steht da');
assert_contains($html, '15:00', 'html: die Uhrzeit steht da');

// Englisch nimmt den englischen Titel.
$en = DesignSections::html(sec_doc([
    ['id' => 'ort-1', 'type' => 'location', 'title' => ['de' => 'Ort', 'en' => 'Place']],
]), $daten, 'en', '2027-01-01');
assert_contains($en, 'Place', 'html: englischer Titel auf der englischen Seite');

// Alles, was aus den Daten kommt, wird maskiert.
$boese = DesignSections::html(sec_doc([
    ['id' => 'fam-1', 'type' => 'family'],
]), ['families' => ['bride' => '<script>alert(1)</script>']], 'de', '2027-01-01');
assert_not_contains($boese, '<script>', 'html: kein rohes Markup aus den Daten');
assert_contains($boese, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

// Nichts Sichtbares, nichts Gedrucktes.
assert_same('', DesignSections::html(sec_doc([['id' => 'p', 'type' => 'program']]), [], 'de', '2027-01-01'), 'html: ohne Inhalt kein Markup');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: `Call to undefined method Atelier\DesignSections::css()`.

- [ ] **Step 3: Write the implementation**

`DesignSections` sınıfına ekle (dosyanın başına `use function Atelier\e;`
**gerekmez** — `e()` aynı ad alanında, `View.php` içinde tanımlı):

```php
    /**
     * Stilregeln der Abschnitte.
     *
     * Wie bei den Ebenen: alles unter dem Bereich, damit zwei Designs auf
     * derselben Seite stehen koennen, ohne sich umzufaerben.
     *
     * @param array<string,mixed> $doc
     */
    public static function css(array $doc, string $scope): string
    {
        $doc = self::complete($doc);
        $css = '';

        foreach ($doc['sections'] as $abschnitt) {
            $regeln = '';
            $farbe  = (string) $abschnitt['style']['color'];
            $schrift = (string) $abschnitt['style']['font'];

            if ($farbe !== '') {
                $regeln .= 'color:var(--d-' . $farbe . ');';
            }
            if ($schrift !== '') {
                $regeln .= 'font-family:var(--df-' . $schrift . ');'
                    . 'font-weight:var(--dfw-' . $schrift . ');'
                    . 'letter-spacing:var(--dft-' . $schrift . ');'
                    . 'line-height:var(--dfl-' . $schrift . ');';
            }
            if ($regeln !== '') {
                $css .= $scope . ' .d-sec-' . $abschnitt['id'] . '{' . $regeln . '}';
            }
        }

        return $css;
    }

    /**
     * Die Abschnitte als Markup.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     */
    public static function html(array $doc, array $data, string $locale, string $heute = ''): string
    {
        $out = '';

        foreach (self::visible($doc, $data, $heute) as $abschnitt) {
            $id = (string) $abschnitt['id'];
            $typ = (string) $abschnitt['type'];

            $out .= '<section class="d-sec d-sec-' . e($id) . ' d-sec-' . e($typ) . '">';

            $titel = (string) ($abschnitt['title'][$locale] ?? $abschnitt['title']['de'] ?? '');
            if ($titel !== '') {
                $out .= '<h2 class="d-sec-title">' . e($titel) . '</h2>';
            }

            $out .= match ($typ) {
                'location'  => self::ort($data, $locale),
                'countdown' => self::countdown($data),
                'family'    => self::familien($data),
                'program'   => self::programm($data),
                default     => '',
            };

            $out .= '</section>';
        }

        return $out;
    }

    /** @param array<string,mixed> $data */
    private static function ort(array $data, string $locale): string
    {
        $adresse = trim((string) ($data['address'] ?? ''));
        $ort = trim((string) ($data['venue'] ?? ''));

        $out = '';
        if ($ort !== '') {
            $out .= '<p class="d-sec-venue">' . e($ort) . '</p>';
        }
        $out .= '<p class="d-sec-address">' . e($adresse) . '</p>';

        // Der Link geht zur Routenplanung, nicht auf eine Karte: wer die
        // Adresse liest, will hinfahren.
        $out .= '<a class="d-sec-map" rel="noopener noreferrer" target="_blank" href="'
            . e('https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($adresse))
            . '">' . e($locale === 'de' ? 'Route planen' : 'Plan route') . '</a>';

        return $out;
    }

    /**
     * Der Countdown traegt sein Datum als Attribut.
     *
     * Gerechnet wird im Browser - eine auf dem Server gerenderte Zahl waere
     * in dem Moment falsch, in dem die Seite eine Minute alt ist. Ohne Skript
     * steht trotzdem das Datum da (siehe Aufgabe 8).
     *
     * @param array<string,mixed> $data
     */
    private static function countdown(array $data): string
    {
        $datum = trim((string) ($data['date'] ?? ''));

        return '<p class="d-sec-countdown" data-countdown="' . e($datum) . '">'
            . e(Dates::long($datum, 'de')) . '</p>';
    }

    /** @param array<string,mixed> $data */
    private static function familien(array $data): string
    {
        $familien = is_array($data['families'] ?? null) ? $data['families'] : [];
        $out = '';

        foreach (['bride', 'groom'] as $seite) {
            $name = trim((string) ($familien[$seite] ?? ''));
            if ($name !== '') {
                $out .= '<p class="d-sec-family">' . e($name) . '</p>';
            }
        }

        return $out;
    }

    /** @param array<string,mixed> $data */
    private static function programm(array $data): string
    {
        $out = '<dl class="d-sec-program">';

        foreach (self::programRows($data) as $zeile) {
            $out .= '<dt>' . e($zeile['time']) . '</dt><dd>' . e($zeile['title']) . '</dd>';
        }

        return $out . '</dl>';
    }
```

- [ ] **Step 4: Run test to verify it passes**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_sections
```
Beklenen: bütün `css:` ve `html:` kontrolleri geçiyor.

- [ ] **Step 5: Run the whole suite and commit**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php
git add php/src/DesignSections.php php/tests/design_sections.php
git commit -m "Sections print against token names, and the countdown counts in the browser"
```

---

### Task 4: Sahnenin akış kipi — vitrin bayt bayt aynı kalarak

**Files:**
- Modify: `php/templates/partials/design-stage.php`
- Modify: `php/templates/pages/design-preview.php` (partial çağrısı)
- Modify: `php/templates/pages/invite-v2-show.php` (partial çağrısı)

**Interfaces:**
- Produces: partial artık **on dördüncü** değişkeni alır: `fest` (bool).
  `true` → bugünkü `fixed inset-0 z-50`; `false` → `relative min-h-screen`.

- [ ] **Step 1: Record the baseline**

Sunucuyu doğru belge köküyle çalıştır ve üç sayfanın "önce" hâlini sakla:

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && (php -S 127.0.0.1:8131 -t public public/dev-router.php > /tmp/srv.log 2>&1 &)
sleep 3
curl -s http://127.0.0.1:8131/de/v2/designs/elysee > /tmp/c-vorher-elysee.html
curl -s http://127.0.0.1:8131/de/v2/designs/noir   > /tmp/c-vorher-noir.html
curl -s http://127.0.0.1:8131/de/v2/designs        > /tmp/c-vorher-katalog.html
wc -c /tmp/c-vorher-*.html
```
Beklenen: üçü de boş değil. Aynı isteği iki kez yapıp `diff` ile deterministik
olduklarını doğrula — değilse dur ve bildir.

- [ ] **Step 2: Give the partial its mode**

`php/templates/partials/design-stage.php`, docblock'a bir satır:

```php
 * @var bool $fest
```

ve sahne sarmalayıcısı (`d-stage` satırı) şu hâle gelir:

```php
  <?php /*
    Zwei Rollen, eine Buehne. Im Schaufenster liegt sie ueber allem: dort ist
    die Karte das Einzige, was zaehlt. Auf einer echten Einladung steht sie im
    Fluss, damit die Abschnitte darunter scrollen koennen - fixed inset-0
    liesse darunter nichts zu. Alles INNERHALB ist absolute inset-0, also an
    der Buehne aufgehaengt und nicht am Fenster: beide Rollen funktionieren
    ohne weitere Aenderung.
  */ ?>
  <div class="<?= e($scope) ?> d-stage <?= $fest ? 'fixed inset-0 z-50' : 'relative min-h-screen' ?> overflow-hidden"
       style="background: var(--d-bg, #EFE7DC);">
```

Dizenin `$fest` doğruyken **birebir eskisi** olduğuna dikkat et: `fixed inset-0 z-50`.

- [ ] **Step 3: Pass `fest` from all three callers**

`php/templates/pages/design-preview.php`'deki `View::partial(...)` dizisine:

```php
    // Im Schaufenster liegt die Buehne ueber allem: hier gibt es nichts,
    // was darunter scrollen muesste.
    'fest'      => true,
```

`php/templates/pages/invite-v2-show.php`'deki diziye:

```php
    // Auf der Einladung steht die Buehne im Fluss - darunter kommen die
    // Abschnitte.
    'fest'      => false,
```

Bu iki dosya partial'ı çağıran **tek** yerler — doğrulandı:
`grep -c "View::partial" src/Controllers/DesignController.php` → `0`, yani
denetleyici partial'ı hiç çağırmıyor, veriyi `design-preview.php`'ye veriyor.

- [ ] **Step 4: Prove the showcase did not move**

```bash
curl -s http://127.0.0.1:8131/de/v2/designs/elysee > /tmp/c-nachher-elysee.html
diff /tmp/c-vorher-elysee.html /tmp/c-nachher-elysee.html && echo "elysee AYNI"
curl -s http://127.0.0.1:8131/de/v2/designs/noir   > /tmp/c-nachher-noir.html
diff /tmp/c-vorher-noir.html /tmp/c-nachher-noir.html && echo "noir AYNI"
curl -s http://127.0.0.1:8131/de/v2/designs        > /tmp/c-nachher-katalog.html
diff /tmp/c-vorher-katalog.html /tmp/c-nachher-katalog.html && echo "katalog AYNI"
```
Beklenen: üç kez `AYNI`. Fark çıkarsa **commit etme** — `$fest` dalı yanlış.

- [ ] **Step 5: Run the suite and commit**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php
git add php/templates/partials/design-stage.php php/templates/pages/design-preview.php php/templates/pages/invite-v2-show.php
git commit -m "The stage steps out of the way when something has to scroll beneath it"
```

---

### Task 5: Davetiye sayfası bölümleri gösteriyor

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` — `show()`
- Modify: `php/templates/pages/invite-v2-show.php`

**Interfaces:**
- Consumes: `DesignSections::css()`, `DesignSections::html()` (Görev 3),
  `$fest` (Görev 4)
- Produces: davetiye sayfası kartın altında bölümleri basar

- [ ] **Step 1: Extend `show()`**

`show()` içinde, `View::page` dizisine iki anahtar eklenir ve `styles`
genişletilir:

```php
            // Die Abschnittsregeln haengen an denselben Marken wie die Karte,
            // also gehoeren sie in denselben Stilblock.
            'styles' => Design::css($doc, $scope) . DesignSections::css($doc, $scope),
            ...
            'abschnitte' => DesignSections::html($doc, $einladung['data'], $locale),
```

`use Atelier\DesignSections;` dosyanın başına eklenir.

- [ ] **Step 2: Render them in the template**

`php/templates/pages/invite-v2-show.php`, `View::partial(...)` çağrısının
**altına**:

```php
<?php /*
   Unter der Buehne, nicht darin: die Karte hat einen festen Rahmen, die
   Abschnitte haben eine variable Laenge. Ist nichts auszugeben, steht hier
   auch nichts - kein leerer Kasten.
*/ ?>
<?php if ($abschnitte !== '') : ?>
  <div class="<?= e($scope) ?> d-sections mx-auto max-w-2xl px-6 py-16">
    <?= $abschnitte ?>
  </div>
<?php endif; ?>
```

ve docblock'a `@var string $abschnitte` satırı eklenir.

- [ ] **Step 3: Publish an invitation and look at it**

Sunucu 8131'de. Sihirbazdan (`/de/v2/einladung?design=elysee`) bir davetiye
yayınla — CSRF belirtecini formdan al, isim ve tarih gönder. Sonra adresini aç.

Beklenen: kart yukarıda, altında **hiçbir bölüm yok** — çünkü Élysée'nin
belgesinde henüz bölüm tanımlı değil. Bu doğru davranış; Görev 6 panelden
bölüm eklemeyi getiriyor.

Kanıt olarak raporla: sayfa 200, `d-sections` sınıfı **yok**.

- [ ] **Step 4: Prove sections render, with a constructed document**

Veritabanına yazmadan, salt okunur bir PHP tek satırlığıyla:

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php -r '
require "src/bootstrap.php";
$doc = \Atelier\Design::find("elysee");
$doc["sections"] = [["id"=>"ort-1","type"=>"location","title"=>["de"=>"Ort","en"=>"Place"]]];
echo \Atelier\DesignSections::html($doc, ["venue"=>"Schloss Elmau","address"=>"Elmau 2"], "de", "2027-01-01"), "\n";'
```
Beklenen: `<section class="d-sec d-sec-ort-1 d-sec-location">` ile başlayan,
adresi ve harita bağlantısını içeren markup.

- [ ] **Step 5: Clean up and commit**

Yayınladığın davetiyeyi sil (`DELETE FROM invitations_v2 WHERE slug = ?`) ve
`SELECT COUNT(*)` ile 0 olduğunu göster.

```bash
git add php/src/Controllers/InviteV2Controller.php php/templates/pages/invite-v2-show.php
git commit -m "The invitation carries what the card cannot: address, countdown, families, programme"
```

---

### Task 6: Panelde bölüm editörü

**Files:**
- Modify: `php/src/Design.php` — `fromPost()` bölümleri okur
- Modify: `php/templates/admin/design-edit-sections.php` — dokuzuncu bölüm
- Modify: `php/tests/design_admin.php` — sınır testi güncellenir
- Modify: `php/data/dict.php` (gerekiyorsa; panel metinleri `$tr` ile satır içi)

**Interfaces:**
- Consumes: `DesignSections::TYPES`, `DesignSections::complete()` (Görev 1)
- Produces: `Design::fromPost()` artık `sections`'ı formdan kurar

**Not — bu görev bilerek bir sınırı kaldırıyor.** `Design.php:869`'daki yorum
"die Abschnitte der dritten [Phase]" diyor ve `tests/design_admin.php:90` bunu
tutuyor. O test şimdi değişiyor; **neden değiştiği test dosyasına yazılacak.**

- [ ] **Step 1: Update the boundary test first**

`php/tests/design_admin.php`, `sections bleibt unberuehrt` iddiasının yerine:

```php
/*
 * Die Grenze verschiebt sich: box und canvas bleiben der vierten Phase, die
 * Abschnitte kommen in der dritten herein. Frueher stand hier
 * "sections bleibt unberuehrt" - das war richtig, solange es keinen Editor
 * dafuer gab. Jetzt gibt es einen, und ein Formularwert, der kein Feld ist,
 * darf trotzdem nichts anrichten.
 */
assert_same([], $angriff['sections'], 'fromPost: sections aus einem Nicht-Feld bleibt leer');
```

Ardından yeni bir blok, editörün gerçekten çalıştığını tutan:

```php
$mitAbschnitt = Design::fromPost($basis, [
    'sec_id_0'    => 'Ort 1',
    'sec_type_0'  => 'location',
    'sec_title_de_0' => 'Ort',
    'sec_title_en_0' => 'Place',
    'sec_color_0' => 'accent',
    'sec_font_0'  => 'display',
    'sec_on_0'    => '1',
    'perm_sec_edit_0' => '1',
    'perm_sec_hide_0' => '1',

    'sec_id_1'   => 'gibtesnicht',
    'sec_type_1' => 'wetterbericht',
]);

assert_same(1, count($mitAbschnitt['sections']), 'fromPost: unbekannter Typ kommt nicht herein');
assert_same('ort-1', $mitAbschnitt['sections'][0]['id'], 'fromPost: die Kennung wird normalisiert');
assert_same('location', $mitAbschnitt['sections'][0]['type'], 'fromPost: der Typ steht');
assert_same('Ort', $mitAbschnitt['sections'][0]['title']['de'], 'fromPost: der Titel steht');
assert_same('accent', $mitAbschnitt['sections'][0]['style']['color'], 'fromPost: die Farbmarke steht');
assert_same(true, $mitAbschnitt['sections'][0]['permissions']['hide'], 'fromPost: das Recht steht');

$ohneHaken = Design::fromPost($basis, ['sec_id_0' => 'ort-1', 'sec_type_0' => 'location']);
assert_same(false, $ohneHaken['sections'][0]['enabled'], 'fromPost: ohne Haken ist der Abschnitt aus');
assert_same(false, $ohneHaken['sections'][0]['permissions']['edit'], 'fromPost: ohne Haken kein Recht');
```

- [ ] **Step 2: Run the test to watch it fail**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_admin
```
Beklenen: yeni `fromPost: ...` iddiaları kırılıyor (bölüm oluşmuyor).

- [ ] **Step 3: Teach `fromPost()` the sections**

`php/src/Design.php`, `fromPost()` içinde, `return` satırından önce. Dosyanın
başındaki yorumdaki "sections" sözcüğü de düzeltilir — artık okunuyor:

```php
        /*
         * Die Abschnitte kommen indiziert herein (sec_*_0, sec_*_1 …), damit
         * die Reihenfolge im Formular die Reihenfolge im Dokument wird - genau
         * wie bei den Ebenen der Index den z-Index bestimmt. Ein Eintrag ohne
         * Kennung oder mit unbekanntem Typ faellt in DesignSections::complete()
         * still weg; hier wird nur eingesammelt.
         */
        $abschnitte = [];
        for ($i = 0; $i < 40; $i++) {
            if (!isset($post['sec_type_' . $i])) {
                continue;
            }
            $abschnitte[] = [
                'id'      => Security::clean($post['sec_id_' . $i] ?? '', 64),
                'type'    => Security::clean($post['sec_type_' . $i] ?? '', 24),
                'title'   => [
                    'de' => Security::clean($post['sec_title_de_' . $i] ?? '', 120),
                    'en' => Security::clean($post['sec_title_en_' . $i] ?? '', 120),
                ],
                'enabled' => isset($post['sec_on_' . $i]),
                'style'   => [
                    'color' => Security::clean($post['sec_color_' . $i] ?? '', 64),
                    'font'  => Security::clean($post['sec_font_' . $i] ?? '', 64),
                ],
                'permissions' => [
                    'edit' => isset($post['perm_sec_edit_' . $i]),
                    'hide' => isset($post['perm_sec_hide_' . $i]),
                ],
            ];
        }
        $doc['sections'] = $abschnitte;
        $doc = DesignSections::complete($doc);
```

- [ ] **Step 4: Add the panel section**

`php/templates/admin/design-edit-sections.php`, `8 · Yayın` bloğunun
**öncesine** (yani yeni bölüm dokuzuncu değil, yayınlamadan önce sekizinci
sırada dursun diye — numaraları buna göre kaydır, `8 · Yayın` → `9 · Yayın`):

```php
<?= $auf($tr ? '8 · Bölümler' : '8 · Abschnitte') ?>
  <p class="<?= $label ?>">
    <?= $tr
        ? 'Kartın altında görünenler. Sıra buradaki sıradır. "Düzenlenebilir" ana şalter: kapalıyken müşteri bu bölüme hiç dokunamaz.'
        : 'Was unter der Karte steht. Die Reihenfolge hier ist die Reihenfolge auf der Seite. „Bearbeitbar" ist der Hauptschalter: ist er aus, fasst der Kunde diesen Abschnitt gar nicht an.' ?>
  </p>
  <?php
  $sekmeler = $design['sections'];
  // Immer eine leere Zeile mehr, damit ein Abschnitt ohne Umweg dazukommt.
  $sekmeler[] = ['id' => '', 'type' => '', 'title' => ['de' => '', 'en' => ''],
                 'enabled' => false, 'style' => ['color' => '', 'font' => ''],
                 'permissions' => ['edit' => false, 'hide' => false]];
  ?>
  <?php foreach ($sekmeler as $i => $abschnitt) : ?>
    <div class="grid gap-3 border-b border-sand-deep py-3 sm:grid-cols-6">
      <input class="<?= $feld ?>" name="sec_id_<?= $i ?>" value="<?= e((string) $abschnitt['id']) ?>"
             placeholder="<?= $tr ? 'kimlik' : 'Kennung' ?>">
      <select class="<?= $feld ?>" name="sec_type_<?= $i ?>">
        <option value=""><?= $tr ? '— yok —' : '— keiner —' ?></option>
        <?php foreach (\Atelier\DesignSections::TYPES as $typ) : ?>
          <option value="<?= e($typ) ?>" <?= (string) $abschnitt['type'] === $typ ? 'selected' : '' ?>><?= e($typ) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="<?= $feld ?>" name="sec_title_de_<?= $i ?>" value="<?= e((string) $abschnitt['title']['de']) ?>" placeholder="DE">
      <input class="<?= $feld ?>" name="sec_title_en_<?= $i ?>" value="<?= e((string) $abschnitt['title']['en']) ?>" placeholder="EN">
      <input class="<?= $feld ?>" name="sec_color_<?= $i ?>" value="<?= e((string) $abschnitt['style']['color']) ?>" placeholder="<?= $tr ? 'renk markası' : 'Farbmarke' ?>">
      <input class="<?= $feld ?>" name="sec_font_<?= $i ?>" value="<?= e((string) $abschnitt['style']['font']) ?>" placeholder="<?= $tr ? 'yazı markası' : 'Schriftmarke' ?>">
      <label class="flex items-center gap-2 text-[0.66rem] text-ink">
        <input type="checkbox" name="sec_on_<?= $i ?>" <?= $abschnitt['enabled'] ? 'checked' : '' ?>>
        <?= $tr ? 'Açık' : 'An' ?></label>
      <label class="flex items-center gap-2 text-[0.66rem] text-ink">
        <input type="checkbox" name="perm_sec_edit_<?= $i ?>" <?= $abschnitt['permissions']['edit'] ? 'checked' : '' ?>>
        <?= $tr ? 'Düzenlenebilir' : 'Bearbeitbar' ?></label>
      <label class="flex items-center gap-2 text-[0.66rem] text-muted">
        <input type="checkbox" name="perm_sec_hide_<?= $i ?>" <?= $abschnitt['permissions']['hide'] ? 'checked' : '' ?>>
        <?= $tr ? 'Gizlenebilir' : 'Ausblendbar' ?></label>
    </div>
  <?php endforeach; ?>
<?= $zu ?>
```

Dosyanın başındaki `use Atelier\Design;` satırının yanına
`use Atelier\DesignSections;` eklenir.

- [ ] **Step 5: Run the tests**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php
```
Beklenen: hepsi geçiyor, `design_admin` dahil.

- [ ] **Step 6: Add a section through the panel**

`/de/admin/designs/elysee` → 8. bölüm. `ort-1` / `location` / başlık `Ort` /
`Place`, "Açık" işaretli, "Düzenlenebilir" + "Gizlenebilir" işaretli. Kaydet,
sayfayı yenile — değerler duruyor.

Sonra Görev 5'te yayınladığın gibi bir davetiye yayınla (adresi doldurarak) ve
aç: kartın **altında** adres ve "Route planen" bağlantısı görünüyor.

Yayınladığın satırı sil, sayımı 0'a döndür.

- [ ] **Step 7: Commit**

```bash
git add php/src/Design.php php/templates/admin/design-edit-sections.php php/tests/design_admin.php
git commit -m "The panel can build sections, and the boundary test says why it moved"
```

---

### Task 7: Sihirbaz bölümleri tanıyor

**Files:**
- Modify: `php/src/DesignWizard.php` — `choices()`, `steps()`, `personalize()`
- Test: `php/tests/design_wizard.php`

**Interfaces:**
- Consumes: `DesignSections::complete()`, `visible()` (Görev 1-2)
- Produces:
  - `choices()` bir anahtar daha döndürür:
    `'sections' => ['<id>' => ['type' => string, 'hide' => bool, 'fields' => list<string>]]`
  - `steps()` beşinci anahtarı bilir: `abschnitte`, sıra
    `angaben → bilder → abschnitte → design → veroeffentlichen`
  - `personalize()` `$wahl['sections']['<id>']['hidden'] = true` seçimini uygular

- [ ] **Step 1: Write the failing test**

`php/tests/design_wizard.php` sonuna:

```php
use Atelier\DesignSections;

/*
 * Abschnitte im Assistenten: dieselben zwei Mechanismen wie bei den Ebenen.
 * Was gefragt wird, entscheidet der Typ (family und program brauchen Inhalt,
 * location und countdown leben von dem, was ohnehin gefragt wird). Was
 * angeboten wird, entscheiden die Rechte.
 */

function wiz_sec(array $sections, array $layers = []): array
{
    return ['id' => 'test', 'slug' => 'test', 'layers' => $layers, 'sections' => $sections];
}

$zu = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program'],
]));
assert_same([], $zu['sections'], 'choices: ohne edit-Recht kein Abschnitt');

$auf = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program', 'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'ort-1',  'type' => 'location', 'permissions' => ['edit' => true]],
]));
assert_same(['prog-1', 'ort-1'], array_keys($auf['sections']), 'choices: beide edit-Abschnitte');
assert_same(true, $auf['sections']['prog-1']['hide'], 'choices: hide steht');
assert_same(false, $auf['sections']['ort-1']['hide'], 'choices: ohne hide kein hide');
assert_same(['program'], $auf['sections']['prog-1']['fields'], 'choices: das Programm braucht Inhalt');
assert_same([], $auf['sections']['ort-1']['fields'], 'choices: der Ort lebt von den Angaben');

// Der Schritt kommt nur, wenn es dort etwas zu tun gibt.
assert_same(['angaben', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'ort-1', 'type' => 'location'],
])), 'steps: ohne Rechte kein Abschnitte-Schritt');

assert_same(['angaben', 'abschnitte', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true]],
])), 'steps: ein Abschnitt mit Inhalt bringt den Schritt');

// Die Reihenfolge: Inhalt vor Aussehen.
$voll = DesignWizard::steps(wiz_sec(
    [['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true]]],
    [['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
     ['id' => 'name', 'type' => 'text', 'bind' => 'couple_names',
      'permissions' => ['edit' => true, 'color' => true]]]
));
assert_same(['angaben', 'bilder', 'abschnitte', 'design', 'veroeffentlichen'], $voll, 'steps: alle fuenf, in dieser Reihenfolge');

// personalize: erlaubtes Ausblenden wird ins Dokument geschrieben.
$basis = wiz_sec([
    ['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'ort-1', 'type' => 'location', 'permissions' => ['edit' => true]],
]);

$weg = DesignWizard::personalize($basis, ['sections' => ['fam-1' => ['hidden' => true]]]);
assert_same(false, $weg['sections'][0]['enabled'], 'personalize: erlaubtes Ausblenden wirkt');

// Ohne hide-Recht faellt es still.
$bleibt = DesignWizard::personalize($basis, ['sections' => ['ort-1' => ['hidden' => true]]]);
assert_same(true, $bleibt['sections'][1]['enabled'], 'personalize: ohne hide-Recht bleibt der Abschnitt an');

// Erfundene Kennung faellt still.
$erfunden = DesignWizard::personalize($basis, ['sections' => ['gibtesnicht' => ['hidden' => true]]]);
assert_same(2, count($erfunden['sections']), 'personalize: erfundener Abschnitt fuegt nichts hinzu');
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php design_wizard
```
Beklenen: `Undefined array key "sections"` ya da benzeri.

- [ ] **Step 3: Teach `choices()` the sections**

`DesignWizard::choices()` içinde, `return`'den önce:

```php
        /*
         * Abschnitte: dieselbe Weissliste wie bei den Ebenen. edit ist der
         * Hauptschalter; ohne ihn wird der Abschnitt gar nicht angeboten.
         * fields sagt, wonach der Assistent fragen muss - location und
         * countdown stehen nicht darin, weil sie von den Angaben leben, die
         * ohnehin gefragt werden.
         */
        $sections = [];
        foreach (DesignSections::complete($doc)['sections'] as $abschnitt) {
            if (!$abschnitt['permissions']['edit']) {
                continue;
            }
            $sections[(string) $abschnitt['id']] = [
                'type'   => (string) $abschnitt['type'],
                'hide'   => (bool) $abschnitt['permissions']['hide'],
                'fields' => match ((string) $abschnitt['type']) {
                    'family'  => ['families'],
                    'program' => ['program'],
                    default   => [],
                },
            ];
        }
```

ve `return` dizisine `'sections' => $sections,` eklenir.
Dosyanın başına `use` gerekmez — aynı ad alanı.

- [ ] **Step 4: Teach `steps()` the new step**

`steps()` içinde, `bilder` bloğundan **sonra**, `design` bloğundan **önce**:

```php
        // Inhalt vor Aussehen: erst was draufsteht, dann wie es aussieht.
        if ($w['sections'] !== []) {
            $schritte[] = 'abschnitte';
        }
```

- [ ] **Step 5: Teach `personalize()` the toggle**

`personalize()` içinde, katman döngüsünden **sonra**, `array_values`'tan
**önce**:

```php
        /*
         * Ein abgeschalteter Abschnitt wird nicht geloescht, sondern auf
         * enabled=false gesetzt: das Dokument behaelt, was der Grafiker
         * aufgestellt hat, und beim spaeteren Bearbeiten steht der Abschnitt
         * wieder zur Wahl.
         */
        $sekWahl = (array) ($wahl['sections'] ?? []);
        foreach ($doc['sections'] as $j => $abschnitt) {
            $id = (string) $abschnitt['id'];
            if (!isset($darf['sections'][$id]) || !$darf['sections'][$id]['hide']) {
                continue;
            }
            if (!empty($sekWahl[$id]['hidden'])) {
                $doc['sections'][$j]['enabled'] = false;
            }
        }
```

`personalize()`'ın başındaki `$doc = Design::complete($doc);` satırından hemen
sonra `$doc = DesignSections::complete($doc);` eklenir, ve dönüş satırı
`return DesignSections::complete(Design::complete($doc));` olur — böylece
snapshot her iki normalleştiriciden de geçmiş olur.

- [ ] **Step 6: Run the tests and commit**

```bash
cd /c/Users/yusuf/Documents/GitHub/atelier-lumiere/php && php bin/test.php
git add php/src/DesignWizard.php php/tests/design_wizard.php
git commit -m "The wizard asks about sections the same way it asks about layers"
```

---

### Task 8: Sihirbaz adımı, yayınlama ve geri sayım betiği

**Files:**
- Modify: `php/templates/pages/invite-v2-wizard.php` — `abschnitte` adımı
- Modify: `php/src/Controllers/InviteV2Controller.php` — `publish()` içerik toplar
- Modify: `php/data/dict.php` — yeni anahtarlar, üç dile de
- Modify: `php/public/assets/invite-v2.js` — yok; geri sayım kendi dosyasında
- Create: `php/public/assets/invite-v2-countdown.js`

**Interfaces:**
- Consumes: `choices()['sections']` (Görev 7), `DesignSections::PROGRAM_MAX/LEN` (Görev 2)
- Produces: `data.families`, `data.program` yazılır; `[data-countdown]` tikler

- [ ] **Step 1: Add the dictionary keys**

`php/data/dict.php`, `invitation2` bloklarının **üçüne** de (aynı anahtar
kümesi):

`de`: `'stepAbschnitte' => 'Eure Abschnitte'`, `'familyBride' => 'Familie der Braut'`,
`'familyGroom' => 'Familie des Bräutigams'`, `'programTime' => 'Uhrzeit'`,
`'programTitle' => 'Was passiert'`, `'sectionHide' => 'ausblenden'`

`en`: `'stepAbschnitte' => 'Your sections'`, `'familyBride' => "Bride's family"`,
`'familyGroom' => "Groom's family"`, `'programTime' => 'Time'`,
`'programTitle' => 'What happens'`, `'sectionHide' => 'hide'`

`tr`: `'stepAbschnitte' => 'Bölümleriniz'`, `'familyBride' => 'Gelinin ailesi'`,
`'familyGroom' => 'Damadın ailesi'`, `'programTime' => 'Saat'`,
`'programTitle' => 'Ne oluyor'`, `'sectionHide' => 'gizle'`

- [ ] **Step 2: Render the step**

`invite-v2-wizard.php`, `$stepTitles` dizisine `'abschnitte' => $t('stepAbschnitte'),`
eklenir. Adım gövdesi, `design` bloğunun **öncesine**:

```php
        <?php if ($key === 'abschnitte') : ?>
          <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
            <div class="border-t border-sand-deep pt-6">
              <div class="<?= $label ?>"><?= e($sid) ?></div>

              <?php if ($abschnitt['hide']) : ?>
                <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                  <input type="checkbox" name="sec_hidden_<?= e($sid) ?>"> <?= e($t('sectionHide')) ?>
                </label>
              <?php endif; ?>

              <?php if (in_array('families', $abschnitt['fields'], true)) : ?>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="<?= $label ?>" for="fb"><?= e($t('familyBride')) ?></label>
                    <input id="fb" name="family_bride" class="<?= $field ?>" maxlength="120" value="<?= e($old('family_bride')) ?>">
                  </div>
                  <div>
                    <label class="<?= $label ?>" for="fg"><?= e($t('familyGroom')) ?></label>
                    <input id="fg" name="family_groom" class="<?= $field ?>" maxlength="120" value="<?= e($old('family_groom')) ?>">
                  </div>
                </div>
              <?php endif; ?>

              <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
                <?php /*
                   Feste Zeilenzahl statt Hinzufuegen-Knopf: ohne Skript
                   funktioniert das Formular sonst nicht, und der alte
                   Assistent macht es genauso.
                */ ?>
                <?php for ($z = 0; $z < 8; $z++) : ?>
                  <div class="mt-3 grid gap-3 sm:grid-cols-[8rem_1fr]">
                    <input name="prog_time_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                           placeholder="<?= e($t('programTime')) ?>" value="<?= e($old('prog_time_' . $z)) ?>">
                    <input name="prog_title_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                           placeholder="<?= e($t('programTitle')) ?>" value="<?= e($old('prog_title_' . $z)) ?>">
                  </div>
                <?php endfor; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
```

- [ ] **Step 3: Collect the content in `publish()`**

`publish()` içinde, `$wahl` kurulduktan sonra ve `personalize()` çağrısından
önce:

```php
        // Abschnitte: das Zu- und Abschalten geht ins Dokument, der Inhalt in
        // die Daten. Dieselbe Trennung wie bei den Ebenen.
        $wahl['sections'] = [];
        foreach ($darf['sections'] as $sid => $abschnitt) {
            if ($abschnitt['hide'] && isset($_POST['sec_hidden_' . $sid])) {
                $wahl['sections'][$sid] = ['hidden' => true];
            }

            if (in_array('families', $abschnitt['fields'], true)) {
                $braut = Security::clean($_POST['family_bride'] ?? '', 120);
                $mann  = Security::clean($_POST['family_groom'] ?? '', 120);
                if ($braut !== '' || $mann !== '') {
                    $data['families'] = ['bride' => $braut, 'groom' => $mann];
                }
            }

            if (in_array('program', $abschnitt['fields'], true)) {
                $zeilen = [];
                for ($z = 0; $z < 8; $z++) {
                    $titel = Security::clean($_POST['prog_title_' . $z] ?? '', DesignSections::PROGRAM_LEN);
                    if ($titel === '') {
                        continue;
                    }
                    $zeilen[] = [
                        'time'  => Security::clean($_POST['prog_time_' . $z] ?? '', DesignSections::PROGRAM_LEN),
                        'title' => $titel,
                    ];
                }
                if ($zeilen !== []) {
                    $data['program'] = $zeilen;
                }
            }
        }
```

`use Atelier\DesignSections;` denetleyicinin başına eklenir (Görev 5'te
eklendiyse tekrar etme).

- [ ] **Step 4: Write the countdown script**

`php/public/assets/invite-v2-countdown.js`:

```javascript
/*
 * Der Countdown.
 *
 * Gerechnet wird hier und nicht auf dem Server: eine gerenderte Zahl waere
 * falsch, sobald die Seite eine Minute alt ist. Ohne dieses Skript steht
 * trotzdem das Datum da - der Server hat es schon gedruckt, und das ist die
 * Aussage, auf die es ankommt.
 */
(function () {
  'use strict';

  var ziele = document.querySelectorAll('[data-countdown]');
  if (!ziele.length) return;

  function zeichne() {
    var jetzt = new Date();

    Array.prototype.forEach.call(ziele, function (el) {
      var datum = el.getAttribute('data-countdown');
      if (!datum) return;

      var ziel = new Date(datum + 'T00:00:00');
      var tage = Math.ceil((ziel - jetzt) / 86400000);
      if (isNaN(tage) || tage < 0) return;

      // Nur Tage: Stunden und Minuten laden zum Nachladen ein, und eine
      // Einladung ist kein Wecker.
      el.setAttribute('data-days', String(tage));
    });
  }

  zeichne();
  // Einmal pro Stunde reicht - der Wert aendert sich taeglich.
  window.setInterval(zeichne, 3600000);
})();
```

`show()`'un `Seo::forPage(...)` çağrısındaki `scripts` dizisine
`'/assets/invite-v2-countdown.js'` eklenir (`invitation.js`'in yanına).

- [ ] **Step 5: Verify end to end**

Sunucu 8131'de.
1. Panelden Élysée'ye `fam-1`/`family` ve `prog-1`/`program` bölümleri ekle,
   ikisine de `edit`+`hide` ver, kaydet.
2. `/de/v2/einladung?design=elysee` — adım sayısı **dört** olmalı
   (`angaben`, `abschnitte`, `design`, `veroeffentlichen`). `grep -c 'data-step='`
   ile göster.
3. Aile isimlerini ve iki program satırını doldur, yayınla.
4. Davetiyeyi aç: kartın altında aile isimleri ve program tablosu görünüyor.
5. `sec_hidden_fam-1` işaretleyerek ikinci bir davetiye yayınla — o davetiyede
   aile bölümü **yok**, program var.
6. Elle hazırlanmış bir POST ile `sec_hidden_ort-1` (izinsiz) zorla; saklanan
   snapshot'ta o bölüm hâlâ `enabled=true` olmalı. Sorgu çıktısını göster.
7. `php bin/test.php` — hepsi geçiyor.
8. Yayınladığın satırları sil, `SELECT COUNT(*)` 0 olsun. Panelden eklediğin
   bölümleri geri al.

- [ ] **Step 6: Commit**

```bash
git add php/templates/pages/invite-v2-wizard.php php/src/Controllers/InviteV2Controller.php php/data/dict.php php/public/assets/invite-v2-countdown.js
git commit -m "The customer fills in what the design asks for, and switches off what it allows"
```

---

## Kapanış kontrolü

- [ ] `php bin/test.php` — sıfır hata
- [ ] `/de/v2/designs` ve `/de/v2/designs/elysee` 3C öncesiyle **bayt bayt aynı**
- [ ] Panelde dört türün dördü de eklenebiliyor ve sıralanabiliyor
- [ ] Doldurulmamış bölüm basılmıyor; geçmiş tarihte geri sayım basılmıyor
- [ ] Tasarım sonradan değişince yayınlanmış davetiyenin bölümleri değişmiyor
- [ ] İzinsiz bölüm kapatma sessizce düşüyor
- [ ] Betiksiz tarayıcıda bölümler görünüyor (geri sayım tarihi basıyor)
- [ ] Eski motor diff'te yok:

```bash
git diff --name-only master... | grep -E 'InviteController|invitation\.php|invite-wizard|Invitations\.php|Themes\.php|Pricing\.php|pages/designs\.php|^app/|^lib/|^scripts/'
```
Beklenen: **boş çıktı**.
