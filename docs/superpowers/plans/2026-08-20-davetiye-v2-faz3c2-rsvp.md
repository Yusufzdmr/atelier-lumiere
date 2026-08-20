# Davetiye v2 — Faz 3C2: RSVP — Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Yayınlanmış bir v2 davetiyesi kartın altında cevap **toplasın**, ve
çift o cevapları `manageKey` ile açılan salt okunur bir ekranda **okusun**.

**Architecture:** `rsvp`, bölüm kataloğunun beşinci türü olarak girer — aynı
`Section` şekli, aynı izinler, aynı jeton tabanlı renk/yazı. Dördü gösterim,
beşincisi **form**. Form davetiyenin kendi adresine POST eder (`show()` rotası
`get` → `any`), yanıt yalnızca `rsvps` tablosuna yazılır ve `design_snapshot`
ile `invitations_v2.data` hiç dokunulmaz. Aynı isimle gelen ikinci yanıt
birincinin yerine geçer; yazma yolu `InvitationsV2::saveRsvp()`'dir, çünkü
`Invitations::addRsvp()` ekler ve `Invitations.php` dokunulmazlar listesinde.
Okuma ekranı `/{locale}/v2/einladung/{slug}/{manageKey}` altında oturur;
yanlış anahtar 404 döner.

**Tech Stack:** PHP 8.3, MariaDB, kendi yönlendiricisi, kendi şablon motoru
(`View::page`/`View::partial`), bağımlılıksız test çalıştırıcısı
(`php bin/test.php`). Composer yok, Node yok.

**Spec:** `docs/superpowers/specs/2026-08-20-davetiye-v2-faz3c2-rsvp-design.md`

## Global Constraints

- **Yalnızca `php/` altında çalışılır.** `app/`, `lib/`, `scripts/` hiç değişmez.
- **Eski motora dokunulmaz.** Şu dosyalar diff'te **hiç geçmemeli**:
  `php/src/Controllers/InviteController.php`, `php/templates/pages/invitation.php`,
  `php/templates/pages/invite-wizard.php`, `php/src/Invitations.php`,
  `php/src/Themes.php`, `php/src/Pricing.php`, `php/templates/pages/designs.php`.
- **Veritabanı şeması değişmez.** `rsvps` tablosu olduğu gibi kullanılır
  (`id`, `slug`, `data`, `at`). Üzerine yazma PHP tarafında çözülür — tabloya
  yeni bir benzersiz anahtar **eklenmez**, çünkü tablo eski motorla paylaşılıyor.
- **`DesignWizard.php` değişmez.** `rsvp` mevcut `match` ifadesinin `default`
  dalına düşer ve `fields` boş döner.
- **`DesignSections` saf kalır.** Veritabanı yok, oturum yok, `$_POST` yok,
  `date()` çağrısı yok. CSRF jetonu da tarih gibi **parametre olarak** girer —
  aksi hâlde `bin/test.php` (ki `config.php` yüklemez) bu sınıfı çalıştıramaz.
- **Bölümün kendi metinleri gömülü, okuma ekranınınkiler sözlükten.**
  `DesignSections` içinde `I18n::t()` **çağrılmaz**: `I18n::raw()` →
  `Texts::get()` veritabanına gider ve saflığı bozar. Formun etiketleri
  `$locale === 'de' ? ... : ...` kalıbıyla gömülür — `Route planen` ve `Tage`
  için kurulmuş olan kalıbın aynısı. Okuma ekranı normal bir şablondur ve
  sözlüğü kullanır.
- **Sözlük anahtarı eklenir, mevcut anahtar değişmez.** Her yeni anahtar
  `data/dict.php` içindeki **üç** dil kümesine de girer (de: satır ~169,
  en: ~566, tr: ~961 — `'invitation2' => [` blokları).
- **Yorumlar Almanca**, `php/src` üslubuyla: ne yaptığını değil **neden** öyle
  olduğunu anlatır.
- Şablonda ve `html()` içinde basılan her değer `e()` içinden geçer.
- **Testler veritabanısız çalışabilmeli.** Veritabanı isteyen testler
  `needs_db()` ile korunur ve kendi satırlarını temizler.
- **Dev sunucu:** `cd php && php -S 127.0.0.1:8131 -t public public/dev-router.php`
  — `-t public` **şart**, yoksa `/assets/*` 404 döner.

### Bu planın spec'e eklediği tek karar

**Geçmiş tarih kuralı sunucuda da uygulanır** (Görev 4, `saveReply()`).
Spec §3 kuralı yalnızca `visible()` için yazıyor — yani form *basılmıyor*.
Ama bir POST basılmış sayfa gerektirmez: eski bir sekme ya da elle atılmış bir
istek, düğün geçtikten sonra da yanıt yazabilirdi. Kural yalnızca markup'ta
durursa kural değil, görünüm olur. Maliyeti bir `if`; kazancı, "geçmiş düğüne
cevap toplanmaz" cümlesinin gerçekten doğru olması.

---

### Task 1: `rsvp` kataloğun beşinci türü — görünürlük kuralı

**Files:**
- Modify: `php/src/DesignSections.php` — `TYPES`, `hatInhalt()`
- Test: `php/tests/design_sections.php`

**Interfaces:**
- Consumes: yok (mevcut `visible()`/`complete()` yolu)
- Produces: `DesignSections::TYPES` beş elemanlı olur ve `'rsvp'` içerir.
  `DesignSections::visible($doc, $data, $heute)` `rsvp` türünü, `$data['date']`
  boşsa **veya** `>= $heute` ise döndürür.

Bu görev bittiğinde panelden `rsvp` seçilebilir ve bölüm görünür sayılır, ama
gövdesi henüz boştur (`html()`'in `match`'i `default => ''` verir). Ara durum
bilinçli: tarih kuralı, formun markup'ından ayrı gözden geçirilebilsin diye.

- [ ] **Step 1: Önce düşen testi yaz**

`php/tests/design_sections.php` dosyasının **sonuna** ekle:

```php
/*
 * Der fuenfte Typ: rsvp.
 *
 * Vier Abschnitte zeigen, dieser eine fragt. Fuer visible() ist er trotzdem
 * ein Abschnitt wie jeder andere - die Regel steht an derselben Stelle wie
 * die des Countdowns, damit sie nicht ein zweites Mal erfunden wird.
 */

assert_same(5, count(DesignSections::TYPES), 'TYPES: fuenf Arten, die fuenfte ist rsvp');
assert_true(in_array('rsvp', DesignSections::TYPES, true), 'TYPES: rsvp steht im Katalog');

$fragt = sec_doc([['id' => 'rsvp-1', 'type' => 'rsvp']]);

// Ohne Datum wird gefragt: auch eine Einladung ohne festen Termin darf
// wissen wollen, wer kommt. Das ist der Unterschied zum Countdown, der ohne
// Datum gar nichts anzeigen koennte.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, [], '2027-01-01'), 'id'), 'visible: ohne Datum wird das Formular gedruckt');

// Ein kuenftiger Termin sammelt Antworten.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, ['date' => '2027-06-12'], '2027-01-01'), 'id'), 'visible: kuenftiger Termin sammelt Antworten');

// Ein vergangener nicht - Antworten auf eine gefeierte Hochzeit sind Laerm.
assert_same([], DesignSections::visible($fragt, ['date' => '2026-06-12'], '2027-01-01'), 'visible: vergangene Hochzeit sammelt keine Antworten mehr');

// Der Tag selbst zaehlt noch, wie beim Countdown: es wird ja bis zum Morgen
// gefeiert, und wer mittags noch zusagt, sagt zu.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, ['date' => '2027-01-01'], '2027-01-01'), 'id'), 'visible: der Tag selbst zaehlt noch');

// Was der Grafiker abgeschaltet hat, bleibt abgeschaltet - auch das Formular.
assert_same([], DesignSections::visible(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp', 'enabled' => false],
]), [], '2027-01-01'), 'visible: abgeschaltetes Formular bleibt weg');
```

- [ ] **Step 2: Testi çalıştır, düştüğünü gör**

Run: `cd php && php bin/test.php design_sections`
Expected: FAIL — `TYPES: fuenf Arten, die fuenfte ist rsvp` (`erwartet: 5`,
`bekommen: 4`) ve `visible: ohne Datum wird das Formular gedruckt`
(`bekommen: array()`).

- [ ] **Step 3: `TYPES`'a `rsvp` ekle**

`php/src/DesignSections.php` içinde:

```php
    /** Welche Arten es gibt. Alles andere faellt beim Einlesen weg. */
    public const TYPES = ['location', 'countdown', 'family', 'program', 'rsvp'];
```

Sınıf başlığındaki yorum bloğunda, `Alles hier ist rein` paragrafından
**önce** şu paragrafı ekle:

```php
 * Vier Arten zeigen etwas an, die fuenfte fragt: rsvp ist der einzige
 * Abschnitt, der ein Formular druckt. Er sitzt trotzdem hier und nicht
 * daneben - dieselbe Form, dieselben Rechte, dieselbe Reihenfolge. Was ihn
 * unterscheidet, ist nicht seine Gestalt, sondern was auf der anderen Seite
 * des Absendens passiert, und das steht im Controller.
 *
```

- [ ] **Step 4: `hatInhalt()`'e tarih kuralını ekle**

`hatInhalt()` içindeki `match` ifadesinde, `'program'` dalından **sonra** ve
`default` dalından **önce**:

```php
            // Dieselbe Regel wie beim Countdown, und ausdruecklich dieselbe:
            // eine gefeierte Hochzeit sammelt keine Antworten mehr. Ohne
            // Datum wird trotzdem gedruckt - dort ist der Countdown stumm,
            // weil er nichts zu zaehlen haette, aber die Frage "kommt ihr?"
            // steht auch ohne Termin.
            'rsvp'      => $datum === '' || $datum >= $heute,
```

- [ ] **Step 5: Testi çalıştır, geçtiğini gör**

Run: `cd php && php bin/test.php design_sections`
Expected: PASS — tüm kontroller yeşil.

- [ ] **Step 6: Tüm süiti çalıştır**

Run: `cd php && php bin/test.php`
Expected: PASS — Faz C'nin 429 kontrolü + bu görevin 7 yenisi.

- [ ] **Step 7: Commit**

```bash
git add php/src/DesignSections.php php/tests/design_sections.php
git commit -m "Faz 3C2/1: rsvp katalogun besinci turu olur"
```

---

### Task 2: Formun kendisi — `html()` bir form basar

**Files:**
- Modify: `php/src/DesignSections.php` — `html()` imzası, `formular()`, `baseline()`
- Test: `php/tests/design_sections.php`

**Interfaces:**
- Consumes: Görev 1'in `TYPES`/`visible()` kuralı
- Produces:
  `DesignSections::html(array $doc, array $data, string $locale, string $heute = '', array $form = []): string`
  — beşinci parametre `['csrf' => string, 'sent' => bool]` şeklindedir.
  Görev 4'ün controller'ı bu diziyi doldurur.

**Neden CSRF parametre olarak giriyor:** `Security::csrf()` oturuma dokunur.
`DesignSections` saf kalmak zorunda (Global Constraints), yoksa `bin/test.php`
onu çalıştıramaz. `$heute` aynı gerekçeyle zaten parametre — bu ikincisi o
kalıbı tekrar ediyor, yeni bir kalıp açmıyor.

**Sınıf adı çakışması uyarısı:** `<section>` etiketi zaten `d-sec-<tür>` yani
`d-sec-rsvp` sınıfını taşıyor. Form **`d-sec-form`** adını alır; `d-sec-rsvp`
kullanılsaydı formun `display:grid` kuralı bölümün kendisine de uygulanırdı.

**Spec §9'un XSS testi hakkında:** spec "`html()` misafirin girdiği hiçbir şeyi
ham basmıyor" diyor. Bu fazda misafirin girdiği hiçbir şey `html()`'e geri
**dönmüyor** — form boş çıkar, yanıtlar yalnızca okuma ekranında (Görev 5)
basılır. `html()`'e dışarıdan giren tek yeni değer `$form['csrf']`'tir, ve
kaçış testi ona kurulur. Misafir metninin kaçışı Görev 5'in şablonunda
`e()` ile sağlanır.

- [ ] **Step 1: Önce düşen testi yaz**

`php/tests/design_sections.php` dosyasının **sonuna** ekle:

```php
/*
 * Das Formular.
 *
 * Es ist der einzige Abschnitt, der schreibt, und deshalb der einzige, der
 * ein Zeichen braucht. Das Zeichen kommt als Parameter herein, nicht aus
 * Security::csrf(): diese Klasse fasst keine Sitzung an, sonst liefe sie
 * nicht mehr unter bin/test.php.
 */

$formular = DesignSections::html(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp', 'title' => ['de' => 'Kommt ihr?', 'en' => 'Are you coming?']],
]), ['date' => '2027-06-12'], 'de', '2027-01-01', ['csrf' => 'ZEICHEN123', 'sent' => false]);

assert_contains($formular, 'class="d-sec d-sec-rsvp-1 d-sec-rsvp"', 'html: Kennung und Art stehen in der Klasse');
assert_contains($formular, '<h2 class="d-sec-title">Kommt ihr?</h2>', 'html: der Titel des Grafikers steht darueber');
assert_contains($formular, '<form class="d-sec-form" method="post">', 'html: es ist ein Formular und es sendet per POST');

// Ohne Zeichen kein Schutz: ein Formular, das ohne CSRF-Feld hinausgeht,
// wuerde vom Controller abgewiesen und der Gast saehe nur, dass nichts
// passiert. Dieser Test ist die Wache davor.
assert_contains($formular, '<input type="hidden" name="csrf" value="ZEICHEN123">', 'html: das CSRF-Feld traegt das uebergebene Zeichen');

assert_contains($formular, 'name="name"', 'html: nach dem Namen wird gefragt');
assert_contains($formular, 'required', 'html: der Name ist Pflicht');
assert_contains($formular, 'maxlength="60"', 'html: der Name ist begrenzt');
assert_contains($formular, 'name="coming" value="1"', 'html: zusagen geht');
assert_contains($formular, 'name="coming" value="0"', 'html: absagen auch');
assert_contains($formular, 'name="count"', 'html: nach der Anzahl wird gefragt');
assert_contains($formular, 'min="1" max="20"', 'html: die Anzahl hat Grenzen');
assert_contains($formular, 'name="note"', 'html: es gibt Platz fuer einen Satz');
assert_contains($formular, 'maxlength="300"', 'html: auch der ist begrenzt');

// Kein action-Attribut: die Einladung nimmt ihre eigene Antwort entgegen.
// Ein erfundener Endpunkt waere eine zweite Adresse, die dieselbe Sache tut.
assert_not_contains($formular, 'action=', 'html: gesendet wird an die eigene Adresse');

// Englisch spricht Englisch - die Etiketten stehen in der Klasse, nicht im
// Woerterbuch, weil I18n::t() ueber Texts::get() an die Datenbank ginge.
$formularEn = DesignSections::html(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp'],
]), [], 'en', '2027-01-01', ['csrf' => 'x']);
assert_contains($formularEn, 'Your name', 'html: englische Etiketten auf der englischen Seite');
assert_not_contains($formularEn, 'Euer Name', 'html: und dann nicht auch die deutschen');

/*
 * Das Zeichen wird maskiert wie jeder andere Wert.
 *
 * Heute kommt es aus Security::csrf() und ist Hexadezimal - aber der Wert
 * ist ein Parameter, und ein Parameter ist irgendwann etwas anderes. Was
 * gedruckt wird, geht durch e(), ohne Ausnahme.
 */
$boesesZeichen = DesignSections::html(sec_doc([
    ['id' => 'r', 'type' => 'rsvp'],
]), [], 'de', '2027-01-01', ['csrf' => '"><script>alert(1)</script>']);
assert_not_contains($boesesZeichen, '<script>', 'html: kein rohes Markup aus dem Zeichen');
assert_contains($boesesZeichen, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

/*
 * Nach dem Absenden steht Dank da, kein zweites Formular.
 *
 * Ein wieder leeres Formular direkt unter der eigenen Antwort liest sich wie
 * "nicht angekommen, nochmal" - und genau das taete der Gast dann auch.
 */
$danke = DesignSections::html(sec_doc([
    ['id' => 'r', 'type' => 'rsvp'],
]), [], 'de', '2027-01-01', ['csrf' => 'x', 'sent' => true]);
assert_not_contains($danke, '<form', 'html: nach der Antwort kein zweites Formular');
assert_contains($danke, 'Danke', 'html: sondern ein Dank');

/*
 * Der Grundstil des Formulars.
 *
 * Wie bei den uebrigen Abschnitten: Tailwinds Preflight nimmt input und
 * button jede Kontur, und ein Formular ohne Kontur ist auf einer
 * typografierten Einladung eine Reihe unsichtbarer Zeilen. Jeder Selektor
 * haengt am Bereich.
 */
$cssForm = DesignSections::css(sec_doc([['id' => 'rsvp-1', 'type' => 'rsvp']]), '.d-elysee');
foreach (['.d-sec-form{', '.d-sec-form-row{', '.d-sec-form button{'] as $sel) {
    assert_contains($cssForm, '.d-elysee ' . $sel, 'css: Formular-Selektor "' . $sel . '" ist am Bereich verankert');
}

// Die alten vier Aufrufer geben keinen fuenften Parameter mit und muessen
// weiterlaufen - sonst waere die Signaturaenderung ein Bruch.
$ohneForm = DesignSections::html(sec_doc([
    ['id' => 'fam-1', 'type' => 'family'],
]), ['families' => ['bride' => 'Familie Weber']], 'de', '2027-01-01');
assert_contains($ohneForm, 'Familie Weber', 'html: die vier Anzeige-Abschnitte brauchen den fuenften Parameter nicht');
```

- [ ] **Step 2: Testi çalıştır, düştüğünü gör**

Run: `cd php && php bin/test.php design_sections`
Expected: FAIL — `html: es ist ein Formular und es sendet per POST`
(`fehlt: <form class="d-sec-form" method="post">`); ardından form alanlarına
dair kontroller de düşer.

- [ ] **Step 3: `html()` imzasını genişlet ve `match`'e dalı ekle**

`php/src/DesignSections.php` içinde `html()`'in imzasını ve doc bloğunu
değiştir:

```php
    /**
     * Die Abschnitte als Markup.
     *
     * $form traegt, was nur der Controller wissen kann: das CSRF-Zeichen und
     * ob gerade eben geantwortet wurde. Es kommt aus demselben Grund als
     * Parameter herein wie $heute - diese Klasse fasst weder Uhr noch Sitzung
     * an, sonst liefe sie nicht mehr unter bin/test.php. Die vier
     * Anzeige-Abschnitte lesen es nie.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     * @param array<string,mixed> $form
     */
    public static function html(array $doc, array $data, string $locale, string $heute = '', array $form = []): string
```

Aynı metot içindeki `match ($typ)` ifadesine, `'program'` dalından sonra:

```php
                'rsvp'      => self::formular($form, $locale),
```

- [ ] **Step 4: `formular()` metodunu ekle**

`programm()` metodunun **ardından**, sınıfın sonuna:

```php
    /**
     * Der einzige Abschnitt, der schreibt.
     *
     * Kein action-Attribut: die Einladung nimmt ihre eigene Antwort entgegen,
     * genau wie im alten Motor (InviteController::show -> saveRsvp). Ein
     * eigener Endpunkt waere eine zweite Adresse fuer dieselbe Sache, und
     * eine zweite Adresse muesste ihrerseits wissen, zu welcher Einladung
     * sie gehoert.
     *
     * maxlength und min/max sind Hinweise fuer den Browser, keine Sicherung -
     * gekuerzt und beschnitten wird im Controller, wo ein Absender ohne
     * Browser genauso ankommt.
     *
     * Die Etiketten stehen hier und nicht im Woerterbuch: I18n::t() geht
     * ueber Texts::get() an die Datenbank, und diese Klasse ist rein.
     * Dieselbe Entscheidung wie bei "Route planen" und "Tage".
     *
     * @param array<string,mixed> $form
     */
    private static function formular(array $form, string $locale): string
    {
        $de = $locale !== 'en';

        // Nach dem Absenden kein zweites, wieder leeres Formular: das liest
        // sich wie "nicht angekommen" und der Gast antwortet ein zweites Mal.
        if (!empty($form['sent'])) {
            return '<p class="d-sec-form-done">'
                . e($de ? 'Danke - eure Antwort ist angekommen.' : 'Thank you - your reply has arrived.')
                . '</p>';
        }

        return '<form class="d-sec-form" method="post">'
            . '<input type="hidden" name="csrf" value="' . e((string) ($form['csrf'] ?? '')) . '">'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Euer Name' : 'Your name')
            . '</span><input type="text" name="name" maxlength="60" required></label>'
            . '<div class="d-sec-form-row">'
            . '<label><input type="radio" name="coming" value="1" checked> '
            . e($de ? 'Wir kommen' : 'We are coming')
            . '</label>'
            . '<label><input type="radio" name="coming" value="0"> '
            . e($de ? 'Wir kommen leider nicht' : 'We cannot make it')
            . '</label>'
            . '</div>'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Wie viele Personen' : 'How many people')
            . '</span><input type="number" name="count" value="1" min="1" max="20"></label>'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Etwas dazu' : 'Anything else')
            . '</span><input type="text" name="note" maxlength="300"></label>'
            . '<button type="submit">' . e($de ? 'Absenden' : 'Send') . '</button>'
            . '</form>';
    }
```

- [ ] **Step 5: `baseline()`'a formun Grundstil'ini ekle**

`baseline()` metodunun döndürdüğü zincirin **sonuna** (son satır olan
`.d-sec-program dd{margin:0;}`'dan sonra) ekle:

```php
            . $scope . ' .d-sec-form{display:grid;gap:0.85rem;max-width:26rem;}'
            . $scope . ' .d-sec-form-row{display:grid;gap:0.3rem;}'
            . $scope . ' .d-sec-form-row span{font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;opacity:0.7;}'
            . $scope . ' .d-sec-form input[type=text],'
            . $scope . ' .d-sec-form input[type=number]{border:0;border-bottom:1px solid currentColor;background:transparent;padding:0.35rem 0;color:inherit;font:inherit;}'
            . $scope . ' .d-sec-form button{justify-self:start;border:1px solid currentColor;background:transparent;padding:0.55rem 1.5rem;color:inherit;font:inherit;cursor:pointer;}';
```

`baseline()`'ın yorum bloğuna, `.d-sec-days` paragrafının ardından ekle:

```php
     * Das Formular braucht denselben Dienst wie die uebrigen Abschnitte, nur
     * dringender: Preflight nimmt input und button jede Kontur, und ein
     * Eingabefeld ohne Unterkante ist auf einer typografierten Einladung
     * unsichtbar. currentColor statt einer Marke - so nimmt das Formular die
     * Farbe des Abschnitts an, die der Grafiker gesetzt hat, statt eine
     * zweite Quelle dafuer aufzumachen.
```

- [ ] **Step 6: Testi çalıştır, geçtiğini gör**

Run: `cd php && php bin/test.php design_sections`
Expected: PASS

- [ ] **Step 7: Tüm süiti çalıştır**

Run: `cd php && php bin/test.php`
Expected: PASS — mevcut çağrının (`InviteV2Controller.php:364`) beşinci
parametresi yok ve varsayılan `[]` ile çalışır; hiçbir eski test düşmez.

- [ ] **Step 8: Commit**

```bash
git add php/src/DesignSections.php php/tests/design_sections.php
git commit -m "Faz 3C2/2: bolum bir form basar, CSRF alani parametre olarak girer"
```

---

### Task 3: `InvitationsV2::saveRsvp()` — aynı isim üzerine yazar

**Files:**
- Modify: `php/src/InvitationsV2.php`
- Test: `php/tests/invitations_v2_rsvp.php` (**yeni**)

**Interfaces:**
- Consumes: `Db::all()`, `Db::run()`, `Db::jsonList()`, `Db::encode()`,
  `InvitationsV2::slug()`
- Produces:
  - `InvitationsV2::rsvpKey(string $name): string` — saf; `mb_strtolower(trim($name))`
  - `InvitationsV2::saveRsvp(string $slug, array $rsvp): void` — aynı
    normalleştirilmiş isim varsa `UPDATE`, yoksa `INSERT`
  - `InvitationsV2::rsvps(string $slug): array` — yalnızca o slug'ın yanıtları,
    `at DESC`. **Argümansız çağrılamaz** (eski `Invitations::rsvps()`'in aksine).

- [ ] **Step 1: Önce düşen testi yaz**

`php/tests/invitations_v2_rsvp.php` dosyasını oluştur:

```php
<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Eine Antwort je Name.
 *
 * Die Frage des Paares ist "wer kommt", und darauf gehoert eine einzige
 * Antwort. Wer seine Meinung aendert, ersetzt seine erste - stuenden beide
 * untereinander, muesste das Paar aus dem Datum schliessen, welche gilt.
 *
 * Der Preis steht in der Spec (§5) und wird hier nicht wegdiskutiert: zwei
 * echte Gaeste desselben Namens ueberschreiben einander. Der Name ist bis
 * Phase D die einzige Kennung, die wir haben.
 */

/* --- Der Vergleichsname: rein, kein Datenbanktest --- */

assert_same('mehmet', InvitationsV2::rsvpKey('  Mehmet '), 'rsvpKey: Rand und Grossschreibung fallen weg');
assert_same(InvitationsV2::rsvpKey('MEHMET'), InvitationsV2::rsvpKey('mehmet'), 'rsvpKey: zwei Schreibweisen, ein Gast');
assert_same('', InvitationsV2::rsvpKey('   '), 'rsvpKey: nur Leerzeichen ist kein Name');

// mb_strtolower und nicht strtolower: ein tuerkischer oder deutscher Name
// bliebe sonst in der Mitte grossgeschrieben und zwei Schreibweisen desselben
// Gastes waeren wieder zwei Gaeste.
assert_same('ayşe', InvitationsV2::rsvpKey('Ayşe'), 'rsvpKey: mehrbytige Namen werden kleingeschrieben');
assert_same('müller', InvitationsV2::rsvpKey('Müller'), 'rsvpKey: Umlaute werden kleingeschrieben');

// Zwei verschiedene Gaeste bleiben zwei.
assert_true(InvitationsV2::rsvpKey('Mehmet') !== InvitationsV2::rsvpKey('Ahmet'), 'rsvpKey: verschiedene Namen bleiben verschieden');

/* --- Ab hier braucht es die Datenbank --- */

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

// bin/test.php hat den Autoloader schon registriert und View.php schon per
// require geladen (nicht require_once) - src/bootstrap.php wuerde View.php
// ein zweites Mal einbinden und e() doppelt erklaeren. Deshalb hier nur das
// eine Stueck aus bootstrap.php nachholen, das wirklich fehlt: die
// Konfiguration fuer die Datenbankverbindung.
Atelier\Config::load(dirname(__DIR__) . '/config.php');

$slug  = 'testrsvp-a';
$slug2 = 'testrsvp-b';

// Sauber anfangen, falls ein frueherer Lauf abgebrochen ist.
Atelier\Db::run('DELETE FROM rsvps WHERE slug IN (?, ?)', [$slug, $slug2]);

/* --- Die erste Antwort legt eine Zeile an --- */

InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Mehmet', 'coming' => true,
    'count' => 2, 'note' => 'Wir freuen uns', 'at' => '2027-01-01T10:00:00+01:00',
]);

$eine = InvitationsV2::rsvps($slug);
assert_same(1, count($eine), 'saveRsvp: die erste Antwort legt eine Zeile an');
assert_same('Mehmet', $eine[0]['name'], 'saveRsvp: gespeichert wird, was der Gast geschrieben hat');
assert_same(2, $eine[0]['count'], 'saveRsvp: die Anzahl kommt zurueck');

/* --- Die zweite unter demselben Namen ersetzt sie --- */

// Andere Schreibweise, anderer Rand: derselbe Gast. Genau das ist der Punkt
// von rsvpKey - waere hier auf den rohen Namen verglichen worden, stuenden
// jetzt zwei Zeilen da und dieser Test faende sie.
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => '  mehmet ', 'coming' => false,
    'count' => 1, 'note' => 'Leider doch nicht', 'at' => '2027-01-02T10:00:00+01:00',
]);

$zwei = InvitationsV2::rsvps($slug);
assert_same(1, count($zwei), 'saveRsvp: die zweite Antwort ersetzt, sie haengt nicht an');
assert_same(false, $zwei[0]['coming'], 'saveRsvp: die neuere Antwort gilt');
assert_same('Leider doch nicht', $zwei[0]['note'], 'saveRsvp: auch die Notiz ist die neuere');
assert_same('  mehmet ', $zwei[0]['name'], 'saveRsvp: gespeichert bleibt die Schreibweise des Gastes');

/* --- Ein anderer Name ist ein anderer Gast --- */

// Das Ueberschreiben darf nicht zu weit beissen: sonst haette das Paar am
// Ende eine Liste mit genau einem Namen darauf.
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Ayşe', 'coming' => true,
    'count' => 3, 'note' => '', 'at' => '2027-01-03T10:00:00+01:00',
]);

assert_same(2, count(InvitationsV2::rsvps($slug)), 'saveRsvp: ein anderer Name oeffnet eine neue Zeile');

/* --- Zwei Einladungen kommen einander nicht ins Gehege --- */

InvitationsV2::saveRsvp($slug2, [
    'slug' => $slug2, 'name' => 'Mehmet', 'coming' => true,
    'count' => 1, 'note' => '', 'at' => '2027-01-04T10:00:00+01:00',
]);

assert_same(2, count(InvitationsV2::rsvps($slug)), 'saveRsvp: der fremde Slug laesst diese Liste in Ruhe');
assert_same(1, count(InvitationsV2::rsvps($slug2)), 'saveRsvp: derselbe Name unter anderem Slug ist ein anderer Gast');
assert_same('Mehmet', InvitationsV2::rsvps($slug2)[0]['name'], 'rsvps: und er steht in seiner eigenen Liste');

/* --- rsvps() gibt nur diesen Slug zurueck --- */

// Die Liste wird von einer Seite gelesen, die genau einen Schluessel geprueft
// hat. Gaebe rsvps() jemals mehr zurueck als den geprueften Slug, waere der
// gepruefte Schluessel wertlos.
foreach (InvitationsV2::rsvps($slug) as $antwort) {
    assert_same($slug, $antwort['slug'], 'rsvps: jede Zeile gehoert zum abgefragten Slug');
}

// Eine Einladung ohne eine einzige Antwort ist der Normalfall am ersten Tag -
// und der haeufigste Weg, eine Leseansicht zum Absturz zu bringen.
assert_same([], InvitationsV2::rsvps('testrsvp-gibtesnicht'), 'rsvps: ohne Antworten ein leeres Feld, kein Fehler');
assert_same([], InvitationsV2::rsvps(''), 'rsvps: ohne Slug ein leeres Feld - nie die Antworten aller Einladungen');

/* --- Aufraeumen --- */

Atelier\Db::run('DELETE FROM rsvps WHERE slug IN (?, ?)', [$slug, $slug2]);
assert_same(0, count(InvitationsV2::rsvps($slug)), 'aufgeraeumt: die Testzeilen sind wieder weg');
```

- [ ] **Step 2: Testi çalıştır, düştüğünü gör**

Run: `cd php && php bin/test.php invitations_v2_rsvp`
Expected: FAIL — `PHP Fatal error: Call to undefined method
Atelier\InvitationsV2::rsvpKey()`.

- [ ] **Step 3: Üç metodu ekle**

`php/src/InvitationsV2.php` içinde, `find()` metodunun **ardından**, sınıfın
sonuna:

```php
    /* --------------------------------- RSVP --------------------------------- */

    /**
     * Der Name, auf den verglichen wird.
     *
     * Gespeichert wird, was der Gast geschrieben hat; verglichen wird klein
     * und ohne Rand. "  Mehmet " und "mehmet" sind derselbe Gast - alles
     * andere zwaenge das Paar, aus zwei Zeilen zu raten, welche gilt.
     *
     * mb_strtolower und nicht strtolower: ein Name mit Umlaut oder mit ş
     * bliebe sonst in der Mitte grossgeschrieben, und zwei Schreibweisen
     * desselben Gastes waeren wieder zwei Gaeste.
     */
    public static function rsvpKey(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * Eine Antwort, und je Name nur eine.
     *
     * Kein INSERT wie im alten Motor: Invitations::addRsvp() haengt an, und
     * dort stehen zwei Antworten desselben Gastes untereinander. Hier ersetzt
     * die zweite die erste, weil die Frage des Paares - wer kommt - eine
     * einzige Antwort haben muss.
     *
     * Eigene Klasse statt einer Aenderung an Invitations: die alte Fassung
     * steht auf der Liste der Unberuehrbaren. Geteilt wird die Tabelle, nicht
     * der Code. Das ist sicher, weil ein v2-Slug seit Phase 3B in BEIDEN
     * Tabellen frei sein muss (slugAvailable()) - ohne diese Garantie koennte
     * eine v2-Einladung die Antworten einer v1-Einladung sehen. Wer
     * slugAvailable() eines Tages lockert, bricht diese Methode mit.
     *
     * Verglichen wird in PHP und nicht in SQL: der Name steht im
     * JSON-Dokument, und die Tabelle - die dem alten Motor gehoert - hat
     * dafuer keinen Schluessel. Das Schema bleibt unangetastet.
     *
     * Zwei gleichzeitige Antworten desselben Namens koennen beide einfuegen;
     * die Bremse (20 je zehn Minuten und Slug) macht das unwahrscheinlich,
     * und der Schaden waere eine doppelte Zeile, kein Datenverlust.
     *
     * @param array<string,mixed> $rsvp
     */
    public static function saveRsvp(string $slug, array $rsvp): void
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return;
        }

        $key = self::rsvpKey((string) ($rsvp['name'] ?? ''));

        foreach (Db::all('SELECT id, data FROM rsvps WHERE slug = ?', [$slug]) as $row) {
            $alt = json_decode((string) ($row['data'] ?? ''), true);
            if (!is_array($alt)) {
                // Eine Zeile, die kein Dokument ist, wird uebergangen statt
                // ueberschrieben: sie gehoert womoeglich nicht uns.
                continue;
            }
            if (self::rsvpKey((string) ($alt['name'] ?? '')) !== $key) {
                continue;
            }

            // at wird ausdruecklich gesetzt: die Spalte hat ein DEFAULT, aber
            // kein ON UPDATE - sonst stuende in der Liste weiter der
            // Zeitpunkt der ersten Antwort, und die Sortierung waere falsch.
            Db::run(
                'UPDATE rsvps SET data = ?, at = CURRENT_TIMESTAMP WHERE id = ?',
                [Db::encode($rsvp), (int) $row['id']]
            );
            return;
        }

        Db::run('INSERT INTO rsvps (slug, data) VALUES (?, ?)', [$slug, Db::encode($rsvp)]);
    }

    /**
     * Die Antworten zu genau dieser Einladung.
     *
     * Immer mit Slug, und ohne Vorgabewert: Invitations::rsvps() gibt ohne
     * Argument die Antworten ALLER Einladungen zurueck, und diese Methode
     * wird von einer Seite gerufen, die nichts weiter geprueft hat als einen
     * Schluessel. Ein vergessenes Argument waere dort ein Leck.
     *
     * @return list<array<string,mixed>>
     */
    public static function rsvps(string $slug): array
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return [];
        }

        return Db::jsonList('SELECT data FROM rsvps WHERE slug = ? ORDER BY at DESC', [$slug]);
    }
```

- [ ] **Step 4: Testi çalıştır, geçtiğini gör**

Run: `cd php && php bin/test.php invitations_v2_rsvp`
Expected: PASS — `config.php` varsa 20 kontrol; yoksa 6 saf kontrol + atlama
satırı.

- [ ] **Step 5: Tüm süiti çalıştır**

Run: `cd php && php bin/test.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add php/src/InvitationsV2.php php/tests/invitations_v2_rsvp.php
git commit -m "Faz 3C2/3: ayni isimle gelen ikinci yanit birincinin yerine gecer"
```

---

### Task 4: Davetiye kendi cevabını alır — `show()` POST'u karşılar

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` — `show()`, yeni `saveReply()`
- Modify: `php/public/index.php:115` — `show()` rotası `get` → `any`

**Interfaces:**
- Consumes: `InvitationsV2::saveRsvp()` (Görev 3),
  `DesignSections::html(..., $form)` (Görev 2), `Security::checkCsrf()`,
  `Security::throttle()`, `Security::clean()`, `Security::csrf()`
- Produces: `/{locale}/v2/einladung/{slug}` artık POST kabul eder; başarılı
  yanıttan sonra aynı sayfa teşekkür metnini basar.

Bu görev bittiğinde spec §11'in ilk üç ölçütü karşılanır.

- [ ] **Step 1: `show()` POST'u karşılasın**

`php/src/Controllers/InviteV2Controller.php` içinde `show()` metodunda,
404 bloğundan **sonra** ve `$doc = Design::complete(...)` satırından **önce**:

```php
        // Erst antworten, dann zeichnen: die Seite, die nach dem Absenden
        // erscheint, soll den Dank zeigen und nicht noch einmal das leere
        // Formular. Waere die Reihenfolge umgekehrt, saehe der Gast seine
        // eigene Antwort nicht und schickte sie ein zweites Mal.
        $gesendet = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $gesendet = $this->saveReply((string) $einladung['slug'], $einladung['data']);
        }
```

- [ ] **Step 2: `abschnitte` çağrısına form bağlamını ver**

Aynı metotta, `View::page(...)` dizisindeki son satırı değiştir:

```php
            // Rohdaten, nicht gebundene Werte: die Abschnitte binden ihre
            // eigenen Platzhalter (Adresse, Countdown-Datum) selbst.
            //
            // $form ist alles, was DesignSections nicht selbst wissen darf:
            // das CSRF-Zeichen kommt aus der Sitzung, und ob gerade
            // geantwortet wurde, weiss nur diese Anfrage. Das leere vierte
            // Argument laesst das Bezugsdatum bei date('Y-m-d') - eine echte
            // Einladung schaut auf die echte Uhr.
            'abschnitte' => DesignSections::html($doc, $einladung['data'], $locale, '', [
                'csrf' => Security::csrf(),
                'sent' => $gesendet,
            ]),
```

- [ ] **Step 3: `saveReply()` metodunu ekle**

`show()` metodunun **ardından**, sınıfın sonuna:

```php
    /**
     * Die Antwort eines Gastes.
     *
     * Sie geht in die Tabelle rsvps und nirgendwo sonst - weder in
     * design_snapshot noch in invitations_v2.data. Das ist die Regel aus
     * Phase 3B ("das Dokument einer veroeffentlichten Einladung friert ein"),
     * und sie haelt hier ein Versprechen, das mehr wert ist als Bequemlich-
     * keit: nichts, was ein Gast tippt, kann das Aussehen der Einladung
     * veraendern. Deshalb steht hier kein einziger Schreibzugriff auf die
     * Einladung selbst.
     *
     * Falsch heisst still: ein abgelaufenes Zeichen, eine Flut oder ein
     * leerer Name geben false zurueck und die Seite erscheint einfach ohne
     * Dank. Das ist wenig - aber die Alternative waere, einem Gast eine
     * Fehlermeldung ueber CSRF zu zeigen.
     *
     * @param array<string,mixed> $data die Daten der Einladung, nicht des Gastes
     */
    private function saveReply(string $slug, array $data): bool
    {
        // Erste Kontrolle, vor allem anderen.
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return false;
        }

        // Eigener Schluessel, getrennt vom alten Motor: eine Flut auf
        // /einladung/{slug} soll /v2/einladung/{slug} nicht mitsperren. Das
        // Mass ist das des alten Motors - 20 in zehn Minuten je Einladung
        // reicht einer grossen Hochzeit und stoppt ein Skript.
        if (Security::throttle('rsvp-v2-' . $slug, 20, 600)) {
            return false;
        }

        // Ohne Namen keine Antwort: bis zur Gaesteliste in Phase D ist der
        // Name die einzige Kennung, die wir haben. Eine namenlose Zeile
        // koennte weder angezeigt noch ersetzt werden.
        $name = Security::clean($_POST['name'] ?? '', 60);
        if ($name === '') {
            return false;
        }

        // Dieselbe Regel wie in DesignSections::visible(), hier ein zweites
        // Mal - und das ist Absicht. Dort entscheidet sie, ob gedruckt wird;
        // hier, ob angenommen wird. Ein POST braucht keine gedruckte Seite:
        // ein alter Tab oder eine von Hand gestellte Anfrage kaeme sonst noch
        // Jahre nach der Hochzeit durch. Eine Regel, die nur im Markup steht,
        // ist keine Regel.
        $datum = trim((string) ($data['date'] ?? ''));
        if ($datum !== '' && $datum < date('Y-m-d')) {
            return false;
        }

        InvitationsV2::saveRsvp($slug, [
            'slug'   => $slug,
            'name'   => $name,
            // Alles ausser "1" heisst nein - so ist ein fehlendes Feld eine
            // Absage und keine stille Zusage.
            'coming' => (string) ($_POST['coming'] ?? '1') === '1',
            // Beschnitten, nicht abgelehnt: wer sich vertippt und 50 schreibt,
            // soll eine Einladung sehen und keine Fehlerseite.
            'count'  => max(1, min(20, (int) ($_POST['count'] ?? 1))),
            'note'   => Security::clean($_POST['note'] ?? '', 300),
            // Im Dokument, nicht nur in der Spalte: rsvps() liest ueber
            // Db::jsonList() und bekommt die Spalte at gar nicht zu sehen.
            'at'     => date('c'),
        ]);

        return true;
    }
```

- [ ] **Step 4: Rotayı `any` yap**

`php/public/index.php` satır 115'i değiştir:

```php
// any und nicht get: die Einladung nimmt ihre eigene Antwort entgegen
// (DesignSections druckt ein Formular ohne action). Ein eigener Endpunkt
// muesste erst wieder herausfinden, zu welcher Einladung er gehoert.
$router->any('/{locale}/v2/einladung/{slug}', $page_(static fn (array $p) => (new InviteV2Controller())->show($p)));
```

- [ ] **Step 5: Söz dizimini denetle ve süiti çalıştır**

Run:
```bash
cd php && php -l src/Controllers/InviteV2Controller.php && php -l public/index.php && php bin/test.php
```
Expected: `No syntax errors detected` (iki kez) + PASS

- [ ] **Step 6: Elle dene — bir davetiye yayınla ve cevap ver**

Sunucuyu başlat:
```bash
cd php && php -S 127.0.0.1:8131 -t public public/dev-router.php
```

1. `http://127.0.0.1:8131/de/admin/designs/elysee` → 8. bölümde bir satıra
   `kimlik: rsvp-1`, `tür: rsvp`, `Açık` ✓, `Düzenlenebilir` ✓,
   `Gizlenebilir` ✓ ver ve kaydet.
   Beklenen: açılır listede `rsvp` seçeneği görünüyor (TYPES'tan geliyor,
   `templates/admin/design-edit-sections.php` değişmedi).
2. `http://127.0.0.1:8131/de/v2/einladung` → sihirbazı aç.
   Beklenen: "Eure Abschnitte" adımında `rsvp` bölümü için **yalnızca**
   gizleme kutusu var — doldurulacak hiçbir alan yok. Bu, `DesignWizard`
   dosyasına dokunmadan `match`'in `default` dalından geliyor (spec §3).
   Gelecek bir tarihle yayınla.
3. Çıkan bağlantıyı aç.
   Beklenen: kartın altında form var, "Euer Name" alanı görünüyor.
4. Sayfa kaynağında `name="csrf"` alanının **boş olmayan** bir değer taşıdığını
   doğrula.
5. Formu doldur, gönder.
   Beklenen: sayfa yeniden yükleniyor ve formun yerinde
   "Danke - eure Antwort ist angekommen." yazıyor.
6. Sayfayı elle yenile (GET).
   Beklenen: form yine boş hâliyle geliyor — teşekkür yalnızca POST'un
   yanıtında görünür. Bu doğru davranış: aynı kişi cevabını düzeltmek
   isteyebilir, ve düzeltirse §5 gereği üzerine yazılır.

- [ ] **Step 7: Elle dene — bölümü olmayan bir tasarım (§13'ün ikinci tuzağı)**

`rsvp` bölümü **olmayan** bir tasarımdan bir davetiye yayınla ve aç.
Beklenen: sayfa hatasız açılıyor, hiçbir form yok, PHP uyarısı yok.
Ardından o adrese elle bir POST at:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X POST http://127.0.0.1:8131/de/v2/einladung/SLUG -d 'name=Test'
```
Expected: `200` — sayfa normal açılıyor, yanıt CSRF'te sessizce düşüyor,
fatal yok.

- [ ] **Step 8: Elle dene — geçmiş tarih**

Geçmiş tarihli bir davetiye yayınla ve aç.
Beklenen: form basılmıyor. Ardından aynı adrese elle POST at (Step 7'deki
komutla) ve veritabanında satır oluşmadığını doğrula:

```bash
cd php && php -r 'require "src/bootstrap.php"; var_dump(Atelier\InvitationsV2::rsvps("SLUG"));'
```
Expected: `array(0) {}`

- [ ] **Step 9: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/public/index.php
git commit -m "Faz 3C2/4: davetiye kendi cevabini alir"
```

---

### Task 5: Okuma ekranı — `manageKey` nihayet bir kapı açar

**Files:**
- Create: `php/templates/pages/invite-v2-replies.php`
- Modify: `php/src/Controllers/InviteV2Controller.php` — yeni `replies()`
- Modify: `php/public/index.php` — yeni rota
- Modify: `php/data/dict.php` — üç dile de yeni anahtarlar

**Interfaces:**
- Consumes: `InvitationsV2::find()`, `InvitationsV2::rsvps()` (Görev 3),
  `Dates::short()`, `I18n::t()`, `Seo::forPage()`, `View::page()`
- Produces: `GET /{locale}/v2/einladung/{slug}/{key}` — doğru anahtarla liste
  ve iki sayı; yanlış anahtarla **404**.

- [ ] **Step 1: Sözlük anahtarlarını üç dile de ekle**

`php/data/dict.php`, **de** bloğu (`'invitation2' => [`, ~satır 169) içinde
`'sectionHide' => 'ausblenden',` satırından **sonra**:

```php
            'repliesTitle'   => 'Eure Antworten',
            'repliesLead'    => 'Diese Seite gehört euch allein. Der Link ist der Schlüssel – gebt ihn nicht weiter.',
            'repliesEmpty'   => 'Noch hat niemand geantwortet. Sobald jemand das Formular ausfüllt, steht die Antwort hier.',
            'repliesYes'     => 'kommt',
            'repliesNo'      => 'kommt nicht',
            'repliesCount'   => 'Personen',
            'repliesUpdated' => 'zuletzt geändert',
            'repliesGuests'  => 'Gäste insgesamt',
            'repliesTotal'   => 'Antworten',
```

**en** bloğunda (~satır 566) aynı yere:

```php
            'repliesTitle'   => 'Your replies',
            'repliesLead'    => 'This page is yours alone. The link is the key – please keep it to yourselves.',
            'repliesEmpty'   => 'Nobody has replied yet. As soon as someone fills in the form, their reply appears here.',
            'repliesYes'     => 'is coming',
            'repliesNo'      => 'cannot make it',
            'repliesCount'   => 'people',
            'repliesUpdated' => 'last changed',
            'repliesGuests'  => 'guests in total',
            'repliesTotal'   => 'replies',
```

**tr** bloğunda (~satır 961) aynı yere:

```php
            'repliesTitle'   => 'Cevaplarınız',
            'repliesLead'    => 'Bu sayfa yalnızca size ait. Bağlantı anahtardır – kimseyle paylaşmayın.',
            'repliesEmpty'   => 'Henüz kimse cevap vermedi. Biri formu doldurduğunda cevabı burada görünecek.',
            'repliesYes'     => 'geliyor',
            'repliesNo'      => 'gelemiyor',
            'repliesCount'   => 'kişi',
            'repliesUpdated' => 'son güncelleme',
            'repliesGuests'  => 'toplam misafir',
            'repliesTotal'   => 'cevap',
```

- [ ] **Step 2: Şablonu yaz**

`php/templates/pages/invite-v2-replies.php` dosyasını oluştur:

```php
<?php
/**
 * Was die Gaeste geantwortet haben.
 *
 * Nur lesen. Loeschen, Aendern und Ausleiten sind Phase D - das hier ist eine
 * Liste, kein Panel. Wer sie oeffnet, hat einmal im Leben eine Einladung
 * verschickt und will eine einzige Sache wissen: wer kommt.
 *
 * Der haeufigste Zustand am ersten Tag ist der leere: die Liste muss ohne
 * eine einzige Antwort etwas Vernuenftiges zeigen, sonst waere das die Seite,
 * die genau dann bricht, wenn sie zum ersten Mal geoeffnet wird.
 *
 * @var string $locale
 * @var string $namen
 * @var list<array<string,mixed>> $antworten
 * @var int $kommen
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;
?>
<section class="mx-auto max-w-3xl px-6 py-20">
  <h1 class="font-display text-3xl text-ink"><?= e(I18n::t('invitation2.repliesTitle')) ?></h1>
  <?php if ($namen !== '') : ?>
    <p class="mt-2 text-[0.95rem] text-muted"><?= e($namen) ?></p>
  <?php endif; ?>
  <p class="mt-4 max-w-xl text-sm text-muted"><?= e(I18n::t('invitation2.repliesLead')) ?></p>

  <?php /*
     Zwei Zahlen und nicht eine: "wie viele haben geantwortet" und "wie viele
     kommen" sind verschiedene Fragen. Eine Absage zaehlt als Antwort und
     nicht als Gast, und eine Zusage bringt mehrere Personen mit - eine
     einzige Zahl muesste sich fuer eine der beiden Fragen entscheiden.
  */ ?>
  <div class="mt-8 flex gap-12 border-y border-sand-deep py-5">
    <div>
      <div class="text-2xl text-ink"><?= (int) $kommen ?></div>
      <div class="text-[0.62rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invitation2.repliesGuests')) ?></div>
    </div>
    <div>
      <div class="text-2xl text-ink"><?= count($antworten) ?></div>
      <div class="text-[0.62rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invitation2.repliesTotal')) ?></div>
    </div>
  </div>

  <?php if ($antworten === []) : ?>
    <p class="mt-8 text-sm text-muted"><?= e(I18n::t('invitation2.repliesEmpty')) ?></p>
  <?php else : ?>
    <ul class="mt-8 divide-y divide-sand-deep">
      <?php foreach ($antworten as $antwort) : ?>
        <?php $kommt = !empty($antwort['coming']); ?>
        <li class="py-4">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-[0.95rem] text-ink"><?= e((string) ($antwort['name'] ?? '')) ?></span>
            <span class="text-[0.66rem] uppercase tracking-[0.18em] <?= $kommt ? 'text-gold' : 'text-muted' ?>">
              <?= e(I18n::t($kommt ? 'invitation2.repliesYes' : 'invitation2.repliesNo')) ?>
            </span>
            <?php if ($kommt) : ?>
              <span class="text-[0.8rem] text-muted">
                <?= max(1, (int) ($antwort['count'] ?? 1)) ?> <?= e(I18n::t('invitation2.repliesCount')) ?>
              </span>
            <?php endif; ?>
          </div>
          <?php $notiz = trim((string) ($antwort['note'] ?? '')); ?>
          <?php if ($notiz !== '') : ?>
            <p class="mt-1 text-[0.9rem] text-ink"><?= e($notiz) ?></p>
          <?php endif; ?>
          <?php /*
             Das Datum steht an jeder Zeile, weil eine zweite Antwort die
             erste ersetzt: ohne es koennte das Paar nicht sehen, dass jemand
             seine Meinung geaendert hat. Nur der Tag - eine Uhrzeit auf die
             Minute waere eine Genauigkeit, die niemand braucht.
          */ ?>
          <?php $wann = (string) ($antwort['at'] ?? ''); ?>
          <?php if ($wann !== '') : ?>
            <p class="mt-1 text-[0.66rem] text-muted">
              <?= e(I18n::t('invitation2.repliesUpdated')) ?>: <?= e(Dates::short(substr($wann, 0, 10))) ?>
            </p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
```

- [ ] **Step 3: `replies()` metodunu ekle**

`php/src/Controllers/InviteV2Controller.php` içinde, `saveReply()`'ın
**ardından**, sınıfın sonuna:

```php
    /**
     * Was die Gaeste geantwortet haben.
     *
     * Der Schluessel steht seit Phase 3B in den Daten jeder Einladung, und
     * bis heute hat ihn niemand gebraucht. Er wurde damals mit genau diesem
     * Argument geschrieben: nachtraeglich eingefuehrt haette er jede bis
     * dahin veroeffentlichte Einladung ausgesperrt. Dies ist die Phase, fuer
     * die das Argument gemacht war.
     *
     * Nur lesen: Loeschen, Aendern und Ausleiten sind Phase D.
     *
     * @param array<string,string> $params
     */
    public function replies(array $params): void
    {
        $locale = I18n::locale();
        $einladung = InvitationsV2::find($params['slug'] ?? '');

        $erwartet = $einladung !== null ? (string) ($einladung['data']['manageKey'] ?? '') : '';
        $gegeben  = (string) ($params['key'] ?? '');

        /*
         * 404 und nicht 403.
         *
         * Ein 403 bestaetigt, dass es diese Einladung gibt - wer den
         * Schluessel nicht hat, soll auch das nicht erfahren. "Diese Seite
         * gibt es nicht" ist die richtige Antwort an jemanden, der nicht
         * gemeint ist.
         *
         * hash_equals statt ===: der Schluessel ist 32 Hexadezimalzeichen und
         * die einzige Sicherung dieser Seite. Ein Vergleich, der beim ersten
         * ungleichen Zeichen abbricht, verraet ueber die Laufzeit, wie weit
         * ein Rateversuch gekommen ist.
         *
         * Der leere Schluessel wird ausdruecklich vorher abgefangen:
         * hash_equals('', '') ist WAHR. Eine Einladung ohne manageKey stuende
         * sonst jedem offen. Heute schreibt publish() ihn immer - aber "heute
         * kann das nicht passieren" ist der Satz, nach dem in Phase C drei
         * Fehler gefunden wurden.
         */
        if ($einladung === null || $erwartet === '' || !hash_equals($erwartet, $gegeben)) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in
            // welcher Sprache sie aufgerufen wurde.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        $antworten = InvitationsV2::rsvps((string) $einladung['slug']);

        // Zwei Zahlen, weil es zwei Fragen sind: eine Absage ist eine
        // Antwort und kein Gast, und eine Zusage bringt mehrere Personen mit.
        $kommen = 0;
        foreach ($antworten as $antwort) {
            if (!empty($antwort['coming'])) {
                $kommen += max(1, (int) ($antwort['count'] ?? 1));
            }
        }

        $namen = trim(((string) ($einladung['data']['bride'] ?? '')) . ' & ' . ((string) ($einladung['data']['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-replies', [
            'locale' => $locale,
            // Ohne $path meldet layout.php eine undefinierte Variable im
            // Sprachumschalter. Der Schluessel gehoert NICHT hinein: der
            // Umschalter schriebe ihn sonst in eine sichtbare Adresse.
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', [
                'title'   => I18n::t('invitation2.repliesTitle'),
                // Diese Seite IST der Schluessel. Sie gehoert unter keinen
                // Umstaenden in einen Index.
                'noindex' => true,
            ]),
            'namen'     => $namen,
            'antworten' => $antworten,
            'kommen'    => $kommen,
        ]);
    }
```

- [ ] **Step 4: Rotayı ekle**

`php/public/index.php` içinde, Görev 4'te `any` yapılan `show()` rotasından
**önce**:

```php
// Vor der Einladung selbst, wie /einladung/{slug}/verwalten im alten Motor.
// Beide Muster sind verankert und {slug} matcht keinen Schraegstrich, also
// koennen sie einander nicht fangen - die Reihenfolge steht hier fuer den
// Leser, nicht fuer den Router.
//
// get und nicht any: die Leseansicht schreibt nichts.
$router->get('/{locale}/v2/einladung/{slug}/{key}', $page_(static fn (array $p) => (new InviteV2Controller())->replies($p)));
```

- [ ] **Step 5: Söz dizimini denetle ve süiti çalıştır**

Run:
```bash
cd php && php -l src/Controllers/InviteV2Controller.php && php -l public/index.php && php -l data/dict.php && php -l templates/pages/invite-v2-replies.php && php bin/test.php
```
Expected: dört kez `No syntax errors detected` + PASS

- [ ] **Step 6: Elle dene — boş liste (§13'ün birinci tuzağı)**

Yeni bir davetiye yayınla, **hiç cevap verme**. `manageKey`'i al:

```bash
cd php && php -r 'require "src/bootstrap.php"; $i = Atelier\InvitationsV2::find("SLUG"); echo $i["data"]["manageKey"], "\n";'
```

`http://127.0.0.1:8131/de/v2/einladung/SLUG/ANAHTAR` adresini aç.
Beklenen: sayfa açılıyor, iki sayı da `0`, "Noch hat niemand geantwortet…"
metni görünüyor, hiçbir PHP uyarısı yok.

- [ ] **Step 7: Elle dene — dolu liste, yanlış anahtar, üzerine yazma**

1. Davetiyeye "Mehmet", geliyor, 2 kişi olarak cevap ver → okuma ekranını
   yenile. Beklenen: 1 Antwort, 2 Gäste.
2. Aynı davetiyeye "  mehmet " adıyla, gelmiyor olarak cevap ver → yenile.
   Beklenen: hâlâ **1** Antwort, `0` Gäste, satır "kommt nicht" diyor.
3. Yanlış anahtar:
   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8131/de/v2/einladung/SLUG/00000000000000000000000000000000
   ```
   Expected: `404`
4. Var olmayan davetiye:
   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8131/de/v2/einladung/gibtesnicht/abc
   ```
   Expected: `404`
5. `noindex` gerçekten basılıyor mu:
   ```bash
   curl -s http://127.0.0.1:8131/de/v2/einladung/SLUG/ANAHTAR | grep -i noindex
   ```
   Expected: `noindex` içeren bir `<meta>` satırı

- [ ] **Step 8: Elle dene — yanıt belgeye sızmadı**

```bash
cd php && php -r 'require "src/bootstrap.php";
$i = Atelier\InvitationsV2::find("SLUG");
var_dump(
  str_contains(json_encode($i["design_snapshot"], JSON_UNESCAPED_UNICODE), "Mehmet"),
  str_contains(json_encode($i["data"], JSON_UNESCAPED_UNICODE), "Mehmet")
);'
```
Expected: `bool(false)` iki kez — yanıt ne `design_snapshot`'a ne `data`'ya
girmiş.

- [ ] **Step 9: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/public/index.php \
        php/templates/pages/invite-v2-replies.php php/data/dict.php
git commit -m "Faz 3C2/5: manageKey nihayet bir kapi acar"
```

---

### Task 6: Bitiş denetimi — vitrin ve eski motor

**Files:**
- Modify: yok (yalnızca doğrulama; bulgu çıkarsa ilgili görevin dosyası)

**Interfaces:**
- Consumes: Görev 1–5'in tamamı
- Produces: yok — spec §11'in tüm ölçütlerinin kanıtı

- [ ] **Step 1: Vitrin çıktısı bayt bayt aynı mı**

Faz C'nin ölçütü: bu plan vitrini hiç değiştirmemeli. Vitrin yolu
`DesignSections`'a hiç uğramıyor — `DesignController` ne `css()` ne `html()`
çağırıyor (`grep -n "DesignSections" src/Controllers/DesignController.php`
boş döner). Dolayısıyla beklenen sonuç "hiçbir değişiklik".

```bash
cd php && curl -s http://127.0.0.1:8131/de/v2/designs/elysee | wc -c
```
Expected: `25427`

Kesin kanıt — aynı sunucuda öncesi/sonrası karşılaştırması:
```bash
cd php && curl -s http://127.0.0.1:8131/de/v2/designs/elysee | md5sum
git stash
cd php && curl -s http://127.0.0.1:8131/de/v2/designs/elysee | md5sum
git stash pop
```
Expected: iki md5 aynı.

Sayı 25427 tutmuyor **ama** iki md5 aynıysa: sapma bu plandan değil, önceki
bir commit'ten gelir. Not et ve bu görevi bloke etme.

- [ ] **Step 2: Eski motor diff'te geçmiyor**

```bash
git diff --name-only b417538..HEAD
```
Expected: çıktıda şunların **hiçbiri** yok:
`php/src/Controllers/InviteController.php`, `php/templates/pages/invitation.php`,
`php/templates/pages/invite-wizard.php`, `php/src/Invitations.php`,
`php/src/Themes.php`, `php/src/Pricing.php`, `php/templates/pages/designs.php`,
`php/src/DesignWizard.php`, `php/schema.sql`,
`php/templates/admin/design-edit-sections.php`.

Beklenen liste tam olarak:
```
docs/superpowers/plans/2026-08-20-davetiye-v2-faz3c2-rsvp.md
php/data/dict.php
php/public/index.php
php/src/Controllers/InviteV2Controller.php
php/src/DesignSections.php
php/src/InvitationsV2.php
php/templates/pages/invite-v2-replies.php
php/tests/design_sections.php
php/tests/invitations_v2_rsvp.php
```

- [ ] **Step 3: Süit yeşil**

Run: `cd php && php bin/test.php`
Expected: PASS. Faz C 429 kontrolle bitiyordu; bu faz Görev 1'den 7,
Görev 2'den 20, Görev 3'ten 20 (veritabanı varsa) ekler.

- [ ] **Step 4: Sel kontrolü gerçekten ayrı anahtar kullanıyor**

```bash
cd php && php -r 'require "src/bootstrap.php";
foreach (Atelier\Db::all("SELECT bucket, hits FROM throttle WHERE bucket LIKE ?", ["rsvp-v2-%"]) as $r) {
  echo $r["bucket"], " ", $r["hits"], "\n"; }'
```
Expected: en az bir `rsvp-v2-<slug>|<hash>` satırı — yani eski motorun
`rsvp-<slug>` kovasından ayrı.

- [ ] **Step 5: Spec §11 ölçütlerini tek tek işaretle**

Spec dosyasındaki §11 listesini aç ve her satırı aşağıdaki eşlemeyle
karşılaştır. Karşılığı olmayan bir satır kalırsa **commit etme** — eksik
görevi bildirip dur.

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/plans/2026-08-20-davetiye-v2-faz3c2-rsvp.md
git commit -m "Faz 3C2: plan ve bitis denetimi"
```

---

## Bitti sayılma ölçütü (spec §11 ile birebir)

- [ ] Panelden bir tasarıma `rsvp` bölümü eklenebiliyor, `edit`+`hide`
      verilebiliyor. → Görev 1 + Görev 4/Step 6.1
- [ ] Yayınlanan davetiyede kartın altında form görünüyor; CSRF alanı var.
      → Görev 2 + Görev 4/Step 6.4
- [ ] Misafir cevap veriyor, sayfa tekrar yüklendiğinde teşekkür görünüyor.
      → Görev 4/Step 6.5
- [ ] Aynı isimle ikinci cevap **üzerine yazıyor** — satır sayısı artmıyor.
      → Görev 3 testleri + Görev 5/Step 7.2
- [ ] Geçmiş tarihli davetiyede form basılmıyor. → Görev 1 testleri +
      Görev 4/Step 8 (sunucuda da reddediliyor)
- [ ] `/{locale}/v2/einladung/{slug}/{manageKey}` yanıtları listeliyor ve
      toplamı gösteriyor. → Görev 5/Step 7.1
- [ ] Yanlış anahtar **404** dönüyor, 403 değil. → Görev 5/Step 7.3–7.4
- [ ] Yanıt `design_snapshot`'a **hiç** dokunmuyor. → Görev 5/Step 8
- [ ] `/de/v2/designs/elysee` vitrin çıktısı **bayt bayt** değişmemiş (25427).
      → Görev 6/Step 1
- [ ] Eski motora dokunulmadı. → Görev 6/Step 2
- [ ] `php bin/test.php` geçiyor. → Görev 6/Step 3

### Spec §13'ün uyardığı iki tuzak

Faz C'de üç Critical'in üçü de "yalnızca henüz var olmayan veri ortaya
çıkınca çalışan kod"du. Bu fazdaki iki karşılığı ayrı ölçüt oldu:

- [ ] **Hiç yanıt yokken okuma ekranı** açılıyor, iki sayı `0`, boş metin
      görünüyor, uyarı yok. → Görev 5/Step 6 + Görev 3'ün `rsvps()` testleri
- [ ] **Bölümü olmayan bir tasarımda** davetiye açılıyor, form yok, oraya
      atılan POST fatal vermiyor. → Görev 4/Step 7
