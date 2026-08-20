# Davetiye v2 — Yayın sonrası düzenleme: Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Yayınlanmış bir v2 davetiyesinin metinleri ve (yeni yayınlananlarda) tasarım seçimleri, misafir linki değişmeden `manageKey` ile düzenlenebilsin — donmuş şablona dokunmadan.

**Architecture:** `design_snapshot` sütununun anlamı değişir: bugün kişiselleştirilmiş sonucu tutuyor, bundan sonra **kişiselleştirilmemiş şablonu** tutacak. Müşterinin seçimleri `data['wahl']` içinde saklanır ve her basımda `DesignWizard::personalize(snapshot, wahl)` ile şablonun üstüne uygulanır. Şema değişmez, göç yoktur: `wahl` taşımayan eski davetiyelerde `personalize(snapshot, [])` kimliktir (ölçüldü, aşağıda). Düzenleme ekranı yanıt ekranının kardeşidir — aynı anahtar, aynı 404 kuralları, artık ek olarak sel kontrolü.

**Tech Stack:** PHP 8.2, çerçevesiz. `bin/test.php` (bağımlılıksız test koşucusu), MySQL (`Atelier\Db`), önceden derlenmiş `public/assets/style.css`.

**Spec:** `docs/superpowers/specs/2026-08-20-davetiye-v2-yayin-sonrasi-duzenleme-design.md`

---

## §12 taraması: `design_snapshot` nerede geçiyor

Spec §13, plan yazmadan önce bu taramayı istedi. Sonuç (test dosyaları hariç **dört** yer):

| Yer | Ne yapıyor | Bu fazda |
|---|---|---|
| `src/InvitationsV2.php:47` | `create()` — INSERT'e yazıyor | değişmez (çağıran değişir) |
| `src/InvitationsV2.php:65` | `find()` — JSON'dan diziye | değişmez |
| `src/Controllers/InviteV2Controller.php:508` | `show()` — `Design::complete($snapshot)` | **değişir** (Görev 2) |
| `src/DesignWizard.php:197` | sadece yorum | yorum güncellenir (Görev 2) |
| `src/Controllers/InviteV2Controller.php:593` | sadece yorum (RSVP kuralı) | dokunulmaz |

Yazan tek yer `publish()` (`InviteV2Controller.php:~440`), okuyan tek yer `show()`. Panel, vitrin, RSVP ve taslak yolları snapshot'a hiç bakmıyor. **Varsayım "snapshot = basılacak belge" kodun her yerinde değil, tam olarak `show()`'un içinde bir satırda.**

## Ölçülen geriye dönük uyum

Spec §3'ün iddiası, `show()`'un yapacağı tam değiştirme üzerinde ölçüldü (`elysee`, 9202 bayt):

```
Design::complete(snap) === personalize(snap, [])              : true
personalize(d, []) === personalize(personalize(d, []), [])    : true
aynı seçim iki kez uygulanınca                                 : true
kart + css + abschnitte baytı baytına aynı                     : true
```

Bu bir varsayım değil, Görev 2'nin testine dönüşecek bir ölçüm.

## Global Constraints

Bu bölüm **her görevin gereksinimlerine** dâhildir.

- **Kapsam sadece `php/`.** `app/` ve `lib/` (Next.js) bu plana girmez.
- **Şema değişmez.** `ALTER TABLE` yok, yeni sütun yok, göç betiği yok. Yeni her şey `invitations_v2.data` JSON'una girer.
- **Eski motor diff'te geçmez.** `src/Invitations.php`, `src/Controllers/InviteController.php`, `templates/pages/invite-wizard.php`, `templates/pages/invite-manage.php` dokunulmazdır.
- **`rsvps` tablosuna yazılmaz, okunmaz, silinmez.** Düzenleme yolunda tek bir `rsvps` sorgusu bulunmamalı.
- **CSS önceden derlenmiştir.** `public/assets/style.css` hazır gelir, JIT yoktur. Uydurulan bir Tailwind sınıfı sessizce hiçbir şey yapmaz. Yeni bir düzen gerekiyorsa şablonun içine `<style>` bloğu yazılır — `invite-v2-wizard.php:111-141` bunu zaten böyle yapıyor, aynı desen tekrarlanır.
- **Kod yorumları Almanca.** Değişken ve metot adları da (`sammleWahl`, `manageZugang`, `nichtGefunden`). Umlaut yerine `ae/oe/ue/ss` — dosyanın mevcut alışkanlığı.
- **Commit mesajları Almanca, önek yok.** Git geçmişindeki biçim: `Der sechste Abschnitt: ein Textblock`. `feat:` / `fix:` kullanılmaz.
- **Dizi girdisi koruması.** `$_POST` veya `$data`'dan gelen her değerde `is_string()` / `is_array()` kontrolü — `strict_types` altında `name[]=x` bir `TypeError` demektir. Faz 3C2'nin üç Critical'i bu sınıftandı.
- **Yanlış anahtar 404 verir, 403 değil.** Karşılaştırma `hash_equals()`, boş anahtar ondan **önce** reddedilir.
- **Sel kontrolü:** `Security::throttle('v2-manage-' . $slug, 60, 600)`.
- **Test komutu:** `php bin/test.php` (proje kökünden: `cd php && php bin/test.php`). Bu plan yazılırken **525** kontrol yeşil. Her görev sonunda bu sayı artmış ve tümü yeşil olmalı.
- **Veritabanı testleri `needs_db()` ile korunur** ve kendi satırlarını hem başta hem sonda siler.

## Uygulamadan önce: iki değişken

Plan boyunca geçen iki yer tutucu:

- **`<yerel>`** — Laragon'un bu projeyi sunduğu ana bilgisayar (`php/public/` kökü). Genellikle `atelier-lumiere.test` ya da `localhost:8080`. Bir kez belirle, tüm `curl`/tarayıcı adreslerinde onu kullan. `php/config.php` yoksa hiçbir şey çalışmaz — o dosya depoda değil, elle yazılır.
- **Temel SHA** — geriye dönük karşılaştırmalar için bu fazın **ilk** commit'inden önceki nokta. İlk görevden **önce** kaydet:

```bash
cd php && git rev-parse HEAD > /tmp/basis.txt && cat /tmp/basis.txt
```

Plan `$(cat /tmp/basis.txt)` yazan her yerde bu SHA'yı kasteder. `master~7` gibi sayılar kullanılmaz: görev başına commit sayısı uygulama sırasında değişebilir.

## Spec §10'dan iki sapma, açıkça

Spec, "yanlış anahtarla düzenleme 404" ve "`wahl` taşımayan davetiyede tasarım düzenlemesi reddedilir" testlerini veritabanı testi olarak listeledi. `bin/test.php` bir HTTP koşucusu değil — ne durum kodu ne de `$_POST` üretebilir. Bu ikisi planda **ikiye bölündü:**

| Spec'in istediği | Planda nerede |
|---|---|
| yanlış anahtarla 404 | kuralı `InvitationsV2::keyOk()` saf testleri kilitler (Görev 1); 404'ün kendisi Görev 4 Adım 6'da `curl` ile ölçülür |
| `wahl` yoksa tasarım reddedilir | kuralı `InvitationsV2::canEditDesign()` saf testleri kilitler (Görev 1); reddin kendisi Görev 6 Adım 7'de POST ile ölçülür |

Bu yüzden Görev 4 Adım 6 ve Görev 6 Adım 7 **atlanabilir elle kontroller değil** — spec'in iki test satırının tek kanıtı onlar.

---

## Dosya dökümü

| Dosya | Sorumluluk | Görev |
|---|---|---|
| `php/src/InvitationsV2.php` | **Değişir.** Saf kurallar (`keyOk`, `stale`, `canEditDesign`) ve `saveData()` eklenir. | 1 |
| `php/tests/invitations_v2_edit.php` | **Yeni.** Bu fazın tüm testleri — saf blok + `needs_db()` bloğu. | 1, 2, 6 |
| `php/src/Controllers/InviteV2Controller.php` | **Değişir.** Snapshot çevirisi, toplayıcıların ayrılması, kapı, `edit()`, `saveEdit()`. | 2, 3, 4, 5, 6 |
| `php/src/DesignWizard.php` | **Değişir.** Sadece 197. satırdaki yorum — `personalize()`'ın gövdesi değişmez. | 2 |
| `php/public/index.php` | **Değişir.** Bir yol kaydı. | 5 |
| `php/templates/pages/invite-v2-edit.php` | **Yeni.** Düzenleme ekranı: iki sekme, sağda önizleme. | 5 |
| `php/data/dict.php` | **Değişir.** `invitation2` bloğuna yeni anahtarlar, üç dilde. | 5 |

`php/public/assets/invite-v2.js` **değişmez** — düzenleme ekranı onu olduğu gibi tekrar kullanır (Görev 5, adım 4'teki gerekçe).

---

### Görev 1: Kuralları modele koy

Kapının, eşzamanlılığın ve §4'ün kısıtının kuralları saf statik metotlar olur. Sebep: bunlar HTTP olmadan test edilebilir, ve `replies()` ile `edit()` aynı kaynaktan okur — iki kopya zamanla ayrışır.

**Files:**
- Modify: `php/src/InvitationsV2.php` (dosyanın sonuna, `deleteDraft()`'tan sonra yeni bir bölüm)
- Create: `php/tests/invitations_v2_edit.php`

**Interfaces:**
- Produces:
  - `InvitationsV2::keyOk(array $data, string $gegeben): bool`
  - `InvitationsV2::stale(array $data, string $gesehen): bool`
  - `InvitationsV2::canEditDesign(array $data): bool`
  - `InvitationsV2::saveData(string $slug, array $data): void`

- [ ] **Adım 1: Başarısız testi yaz**

`php/tests/invitations_v2_edit.php` dosyasını oluştur:

```php
<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Yayin sonrasi duzenleme.
 *
 * Drei Regeln, die dieser Bildschirm braucht, stehen als reine Funktionen im
 * Modell und nicht im Controller: sie sind die Sicherung einer Seite, die
 * Schreibrechte vergibt, und eine Sicherung, die nur ueber HTTP pruefbar ist,
 * wird nicht geprueft.
 */

/* --- Der Schluessel --- */

$echt = str_repeat('a', 32);

assert_true(InvitationsV2::keyOk(['manageKey' => $echt], $echt), 'keyOk: der richtige Schluessel oeffnet');
assert_true(!InvitationsV2::keyOk(['manageKey' => $echt], str_repeat('b', 32)), 'keyOk: ein falscher Schluessel oeffnet nicht');

// hash_equals('', '') ist WAHR. Eine Einladung ohne manageKey stuende sonst
// jedem offen, der die Adresse mit einem leeren letzten Stueck aufruft.
assert_true(!InvitationsV2::keyOk(['manageKey' => ''], ''), 'keyOk: der leere gespeicherte Schluessel oeffnet niemandem');
assert_true(!InvitationsV2::keyOk([], ''), 'keyOk: ohne manageKey oeffnet nichts');
assert_true(!InvitationsV2::keyOk(['manageKey' => $echt], ''), 'keyOk: ein leerer mitgebrachter Schluessel oeffnet nicht');

// manageKey[]=x aus einer von Hand gestellten Anfrage darf keinen TypeError
// werfen - dieselbe Klasse Fehler wie die drei aus Phase 3C2.
assert_true(!InvitationsV2::keyOk(['manageKey' => ['a']], $echt), 'keyOk: ein Feld statt eines Schluessels wird abgelehnt');

/* --- Der Stand: zwei Tabs --- */

$stand = '2026-08-20T13:00:00+03:00';

assert_true(!InvitationsV2::stale(['updatedAt' => $stand], $stand), 'stale: derselbe Stand ist nicht veraltet');
assert_true(InvitationsV2::stale(['updatedAt' => $stand], '2026-08-20T12:00:00+03:00'), 'stale: ein aelterer Stand ist veraltet');

// Verglichen wird auf Gleichheit, nicht auf "kleiner": zwei Staende mit
// verschiedenem Zonenversatz waeren als Zeichenkette falsch geordnet, und ein
// Stand, den das Formular gar nicht mitbrachte, ist auch keiner.
assert_true(InvitationsV2::stale(['updatedAt' => $stand], ''), 'stale: ein fehlender mitgebrachter Stand ist veraltet');

// Eine Einladung von vor dieser Phase hat keinen Stand. Gegen nichts laesst
// sich nicht vergleichen - sonst waere die erste Bearbeitung jeder alten
// Einladung unmoeglich.
assert_true(!InvitationsV2::stale([], 'egal'), 'stale: ohne gespeicherten Stand wird nicht abgelehnt');
assert_true(!InvitationsV2::stale(['updatedAt' => ['a']], 'egal'), 'stale: ein Feld statt eines Standes wird uebergangen');

/* --- Der Preis aus Spec §4 --- */

assert_true(InvitationsV2::canEditDesign(['wahl' => ['palette' => []]]), 'canEditDesign: mit wahl ist das Design offen');
assert_true(InvitationsV2::canEditDesign(['wahl' => []]), 'canEditDesign: eine leere Wahl ist auch eine Wahl');

// Ohne wahl ist der Sockel bereits personalisiert. Eine neue Auswahl darauf
// waere verlustbehaftet - eine versteckte Ebene kaeme nicht zurueck.
assert_true(!InvitationsV2::canEditDesign([]), 'canEditDesign: ohne wahl bleibt das Design zu');
assert_true(!InvitationsV2::canEditDesign(['wahl' => 'x']), 'canEditDesign: eine Zeichenkette ist keine Wahl');

/* --- Ab hier braucht es die Datenbank --- */

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

// bin/test.php hat den Autoloader schon registriert und View.php schon per
// require geladen (nicht require_once) - src/bootstrap.php wuerde View.php ein
// zweites Mal einbinden und e() doppelt erklaeren. Deshalb hier nur das eine
// Stueck aus bootstrap.php, das wirklich fehlt.
Atelier\Config::load(dirname(__DIR__) . '/config.php');

$slug = 'testedit-' . bin2hex(random_bytes(4));

Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);

$sockel = ['id' => 'test', 'slug' => 'test', 'palette' => [], 'fonts' => [], 'layers' => [
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
]];

InvitationsV2::create($slug, 'test', $sockel, ['slug' => $slug, 'bride' => 'Marie', 'manageKey' => $echt]);

/* --- saveData schreibt data und ruehrt design_snapshot nicht an --- */

$vorher = InvitationsV2::find($slug);
InvitationsV2::saveData($slug, ['slug' => $slug, 'bride' => 'Maria', 'manageKey' => $echt]);
$nachher = InvitationsV2::find($slug);

assert_same('Maria', $nachher['data']['bride'] ?? '', 'saveData: die Daten sind geschrieben');
assert_same($vorher['design_snapshot'], $nachher['design_snapshot'], 'saveData: der Schnappschuss ist unberuehrt');
assert_same($vorher['design_id'], $nachher['design_id'], 'saveData: die Kennung des Designs ist unberuehrt');
assert_same($vorher['created_at'], $nachher['created_at'], 'saveData: der Zeitpunkt der Anlage ist unberuehrt');

/* --- Ein leerer Slug schreibt nichts --- */

InvitationsV2::saveData('', ['bride' => 'Niemand']);
assert_same('Maria', (InvitationsV2::find($slug)['data']['bride'] ?? ''), 'saveData: ohne Slug wird nichts geschrieben');

Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);
```

- [ ] **Adım 2: Testin başarısız olduğunu gör**

```bash
cd php && php bin/test.php invitations_v2_edit
```
Beklenen: `Error: Call to undefined method Atelier\InvitationsV2::keyOk()` ile ölümcül hata.

- [ ] **Adım 3: Kuralları yaz**

`php/src/InvitationsV2.php` içinde, `deleteDraft()` metodunun kapanış `}`'ından sonra ve sınıfın kapanış `}`'ından önce ekle:

```php

    /* ----------------------- Nachtraegliches Bearbeiten ---------------------- */

    /**
     * Oeffnet dieser Schluessel diese Einladung?
     *
     * Seit dieser Phase oeffnet manageKey drei Tueren: die Antworten lesen, die
     * Einladung bearbeiten und - bald - die Gaesteliste. Die Regel steht
     * deshalb einmal hier und nicht in jedem Bildschirm noch einmal.
     *
     * hash_equals statt ===: der Schluessel ist 32 Hexadezimalzeichen und die
     * einzige Sicherung dieser Seiten. Ein Vergleich, der beim ersten
     * ungleichen Zeichen abbricht, verraet ueber die Laufzeit, wie weit ein
     * Rateversuch gekommen ist.
     *
     * Der leere Schluessel wird ausdruecklich VOR hash_equals abgefangen:
     * hash_equals('', '') ist WAHR, und eine Einladung ohne manageKey stuende
     * sonst jedem offen.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function keyOk(array $data, string $gegeben): bool
    {
        // is_string() faengt manageKey aus einem verformten Dokument ab, bevor
        // ein (string)-Cast auf ein Feld greift.
        $erwartet = is_string($data['manageKey'] ?? null) ? $data['manageKey'] : '';

        if ($erwartet === '' || $gegeben === '') {
            return false;
        }

        return hash_equals($erwartet, $gegeben);
    }

    /**
     * Hat jemand anders dazwischen gespeichert?
     *
     * Das Paar bearbeitet in zwei Tabs; der zweite Speichervorgang ueberschreibt
     * sonst den ersten, ohne dass jemand es merkt. Das Formular traegt den
     * Stand, den es beim Oeffnen vorfand, als verstecktes Feld mit - stimmt er
     * beim Absenden nicht mehr, wird nicht geschrieben. Der Designeditor im
     * Panel macht es seit Phase 2 genauso (fehler=veraltet).
     *
     * Verglichen wird auf GLEICHHEIT und nicht auf "aelter als": updatedAt ist
     * eine ISO-Zeichenkette mit Zonenversatz, und zwei Staende aus
     * verschiedenen Zonen waeren als Zeichenkette falsch geordnet. Gleichheit
     * beantwortet dieselbe Frage ohne die Falle.
     *
     * Eine Einladung von vor dieser Phase hat kein updatedAt. Gegen nichts
     * laesst sich nicht vergleichen - sonst waere ihre erste Bearbeitung
     * unmoeglich. Ab dem ersten Speichern steht der Stand dann drin.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function stale(array $data, string $gesehen): bool
    {
        $stand = is_string($data['updatedAt'] ?? null) ? $data['updatedAt'] : '';

        if ($stand === '') {
            return false;
        }

        return $gesehen !== $stand;
    }

    /**
     * Darf an dieser Einladung noch am Design geschraubt werden?
     *
     * Nur wenn die Wahl des Kunden mitgespeichert ist. Ohne sie ist der
     * Schnappschuss bereits personalisiert (so hat publish() bis zu dieser
     * Phase geschrieben), und eine neue Auswahl darauf waere verlustbehaftet:
     * eine ausgeblendete Ebene kaeme nicht zurueck, eine ueberschriebene Farbe
     * nicht wieder hervor. Der Preis steht in Spec §4 und wird dem Kunden auf
     * dem Bildschirm gesagt, nicht verschwiegen.
     *
     * @param array<string,mixed> $data die Daten der Einladung
     */
    public static function canEditDesign(array $data): bool
    {
        return is_array($data['wahl'] ?? null);
    }

    /**
     * Die Daten einer Einladung neu schreiben.
     *
     * Ausdruecklich nur data. design_snapshot steht nicht in diesem UPDATE und
     * soll dort auch nie stehen: die Vorlage einer veroeffentlichten Einladung
     * friert ein (Phase 3B), und diese ganze Phase gibt es, um dieses
     * Versprechen zu halten, waehrend der Kunde trotzdem etwas aendern kann.
     *
     * @param array<string,mixed> $data
     */
    public static function saveData(string $slug, array $data): void
    {
        $slug = self::slug($slug);
        if ($slug === '') {
            return;
        }

        Db::run('UPDATE invitations_v2 SET data = ? WHERE slug = ?', [Db::encode($data), $slug]);
    }
```

- [ ] **Adım 4: Testin geçtiğini gör**

```bash
cd php && php bin/test.php invitations_v2_edit
```
Beklenen: hata yok. Ardından tüm süit:
```bash
cd php && php bin/test.php
```
Beklenen: 525'ten fazla kontrol, sıfır hata.

- [ ] **Adım 5: Commit**

```bash
git add php/src/InvitationsV2.php php/tests/invitations_v2_edit.php
git commit -m "Drei Regeln fuer den Bearbeiten-Bildschirm, als reine Funktionen"
```

---

### Görev 2: Snapshot artık şablonu tutar

Bu, spec'in kalbi. `publish()` kişiselleştirilmemiş şablonu dondurur ve seçimleri `data['wahl']`'a yazar; `show()` her basımda seçimleri şablonun üstüne uygular. Eski davetiyelerde `wahl` yok, `personalize(snapshot, [])` kimlik, çıktı bayt bayt aynı kalır.

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` (`publish()` ~440-460, `show()` ~508)
- Modify: `php/src/DesignWizard.php:194-201` (sadece docblock)
- Modify: `php/tests/invitations_v2_edit.php` (saf bloğa ekleme)

**Interfaces:**
- Consumes: `InvitationsV2::create()` (değişmez imza), `DesignWizard::personalize()` (değişmez gövde)
- Produces: `invitations_v2.data['wahl']` (yeni yayınlarda), `data['updatedAt']` (yeni yayınlarda); `design_snapshot` artık kişiselleştirilmemiş şablon

- [ ] **Adım 1: Başarısız testi yaz**

`php/tests/invitations_v2_edit.php` içinde, `/* --- Der Schluessel --- */` bloğunun **üstüne** (yani `use` satırlarından hemen sonra) ekle:

```php
use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignWizard;

/*
 * Der eingefrorene Sockel.
 *
 * Ab dieser Phase haelt design_snapshot die UNpersonalisierte Vorlage, und
 * die Wahl des Kunden liegt daneben in data['wahl']. Gedruckt wird
 * personalize(snapshot, wahl) - bei jedem Aufruf neu.
 *
 * Der Rueckwaertsvertrag dieser Aenderung ist eine Behauptung ueber
 * personalize(), und sie wird hier gemessen und nicht geglaubt: eine alte
 * Einladung traegt einen bereits personalisierten Sockel und KEIN wahl, also
 * laeuft auf ihr personalize(sockel, []) - und das muss die Identitaet sein,
 * sonst aendert sich das Aussehen jeder heute veroeffentlichten Einladung.
 */

/** Eine Vorlage mit genau den Rechten, die diese Tests brauchen. */
function edit_doc(): array
{
    return [
        'id' => 'test', 'slug' => 'test',
        'palette' => [
            'accent' => ['value' => '#B08D57', 'customer' => true],
            'bg'     => ['value' => '#EFE7DC', 'customer' => false],
        ],
        'fonts'  => [],
        'layers' => [
            ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
            ['id' => 'zier', 'type' => 'shape',
             'permissions' => ['edit' => true, 'color' => true, 'hide' => true]],
        ],
    ];
}

$vorlage = edit_doc();

// So sieht der Sockel aus, den publish() ab jetzt einfriert.
$sockel = DesignSections::complete(Design::complete($vorlage));

// GENAU der Tausch, den show() macht: vorher Design::complete($snapshot),
// nachher DesignWizard::personalize($snapshot, wahl). Fuer eine alte
// Einladung ist wahl leer, und dann muessen beide dasselbe Dokument ergeben.
assert_same(
    Design::complete($sockel),
    DesignWizard::personalize($sockel, []),
    'personalize: mit leerer Wahl ist es genau das, was show() bisher tat'
);

// Zweimal angewendet aendert nichts mehr: ein zweites Speichern darf die
// Einladung nicht verformen.
$einmal = DesignWizard::personalize($sockel, []);
assert_same($einmal, DesignWizard::personalize($einmal, []), 'personalize: leere Wahl ist idempotent');

$wahl = ['palette' => ['accent' => '#123456'], 'fonts' => [], 'layers' => [], 'sections' => []];
assert_same(
    DesignWizard::personalize($sockel, $wahl),
    DesignWizard::personalize(DesignWizard::personalize($sockel, $wahl), $wahl),
    'personalize: dieselbe Wahl zweimal ist idempotent'
);

/* --- Der Sockel bleibt der Sockel: nur die Wahl aendert sich --- */

$rot  = DesignWizard::personalize($sockel, ['palette' => ['accent' => '#AA0000']]);
$blau = DesignWizard::personalize($sockel, ['palette' => ['accent' => '#0000AA']]);

assert_same($rot['layers'], $blau['layers'], 'personalize: eine andere Farbe laesst die Ebenen unberuehrt');
assert_same('#AA0000', $rot['palette']['accent']['value'], 'personalize: die gewaehlte Farbe steht in der Marke');
assert_same('#0000AA', $blau['palette']['accent']['value'], 'personalize: und die andere Wahl in der anderen');
assert_same($sockel['palette']['bg']['value'], $rot['palette']['bg']['value'], 'personalize: eine Marke ohne Haken bleibt, wie der Grafiker sie setzte');

/*
 * Und der eigentliche Punkt der Phase: aendert der Grafiker die Vorlage,
 * aendert sich die veroeffentlichte Einladung NICHT. Der Sockel liegt in der
 * Zeile, nicht im Katalog - das wird hier so nachgestellt, wie es passiert:
 * die Vorlage bekommt eine Ebene dazu, der eingefrorene Sockel nicht.
 */
$spaeter = edit_doc();
$spaeter['layers'][] = ['id' => 'neu', 'type' => 'text', 'bind' => 'hashtag'];

$gedruckt = DesignWizard::personalize($sockel, $wahl);
$ids = array_map(static fn (array $el): string => (string) $el['id'], $gedruckt['layers']);
assert_true(!in_array('neu', $ids, true), 'personalize: was nach dem Einfrieren in die Vorlage kam, steht nicht auf der Karte');

/*
 * Die Kehrseite von §4, gemessen: auf einem BEREITS personalisierten Sockel
 * ist das Ausblenden nicht rueckgaengig zu machen - die Ebene ist weg, und
 * choices() bietet sie nicht mehr an. Genau deshalb bleibt der Design-Tab bei
 * einer Einladung ohne wahl geschlossen.
 */
$versteckt = DesignWizard::personalize($sockel, ['layers' => ['zier' => ['hidden' => true]]]);
assert_true(!isset(DesignWizard::choices($versteckt)['layers']['zier']), 'choices: eine ausgeblendete Ebene wird auf dem personalisierten Sockel nicht mehr angeboten');
assert_true(isset(DesignWizard::choices($sockel)['layers']['zier']), 'choices: auf dem eingefrorenen Sockel steht sie weiter zur Wahl');
```

- [ ] **Adım 2: Testin başarısız olduğunu gör**

```bash
cd php && php bin/test.php invitations_v2_edit
```
Beklenen: `Fatal error: Cannot redeclare edit_doc()` **değil** — bu yeni bir fonksiyon. Beklenen sonuç: testler **geçer**, çünkü `personalize()` zaten böyle davranıyor. Bu bilinçli: bu blok bir davranışı *değiştirmiyor*, Görev 2'nin dayandığı özelliği **kilitliyor**. Kırmızıyı görmek için geçici olarak `personalize` çağrılarından birini `Design::complete` ile değiştirip testin şikâyet ettiğini gör, sonra geri al.

Kırmızı kanıtı (geçici):
```bash
cd php && php -r "
require 'bin/test.php';" 2>/dev/null || true
```
Basit yol: `assert_same(Design::complete(\$sockel), DesignWizard::personalize(\$sockel, ['palette' => ['accent' => '#AA0000']]), ...)` şeklinde geçici olarak boş olmayan bir seçim ver, testin **başarısız** olduğunu gör (`erwartet: ... #B08D57 ... bekommen: ... #AA0000`), sonra `[]`'ye geri döndür.

- [ ] **Adım 3: `publish()` şablonu dondursun**

`php/src/Controllers/InviteV2Controller.php`, `publish()` metodunun sonundaki `$snapshot = ...` satırını bul:

```php
        $snapshot = DesignWizard::personalize($design, $wahl);

        $data['slug']      = $slug;
```

Şununla değiştir:

```php
        /*
         * Der Schnappschuss ist die Vorlage, NICHT das Ergebnis.
         *
         * Bis zu dieser Phase stand hier personalize($design, $wahl): das
         * Ergebnis fror ein, die Eingabe wurde weggeworfen. Damit war
         * nachtraegliches Bearbeiten unmoeglich - eine zweite Wahl haette auf
         * einem Sockel gelegen, in dem die erste schon eingebrannt war.
         *
         * Jetzt friert die Vorlage ein und die Wahl liegt daneben in
         * data['wahl']. Gedruckt wird personalize(snapshot, wahl), bei jedem
         * Aufruf neu (siehe show()). Das Versprechen aus Phase 3B haelt
         * trotzdem: der Sockel ist eine Kopie in der Zeile, und wer die Vorlage
         * im Panel spaeter aendert, aendert diese Karte nicht.
         *
         * Durch beide Normalisierer, damit in der Spalte genau die Form steht,
         * die css(), html() und die Abschnittsvorlage erwarten - dieselbe Form,
         * mit der personalize() ohnehin endet.
         */
        $snapshot = DesignSections::complete(Design::complete($design));

        $data['slug']      = $slug;
```

Aynı metotta, `$data['createdAt'] = date('c');` satırını bul ve **hemen ardına** ekle:

```php
        // Was der Kunde gewaehlt hat, bleibt erhalten - sonst waere der Sockel
        // eine Vorlage, die niemand mehr auf die Karte des Paares abbilden
        // kann. Ihre Anwesenheit ist zugleich das Zeichen, dass der
        // Design-Tab beim Bearbeiten offen steht (Spec §4).
        $data['wahl']      = $wahl;
        // Der Stand fuer die Zwei-Tabs-Kontrolle. Er steht ab der ersten
        // Sekunde da, weil er sonst bei der ersten Bearbeitung fehlte und die
        // Kontrolle genau dann nicht griffe, wenn sie zum ersten Mal gebraucht
        // wird.
        $data['updatedAt'] = $data['createdAt'];
```

- [ ] **Adım 4: `show()` her basımda kişiselleştirsin**

Aynı dosyada, `show()` metodundaki bu satırı bul:

```php
        $doc = Design::complete($einladung['design_snapshot']);
```

Şununla değiştir:

```php
        /*
         * Die Wahl des Kunden auf den eingefrorenen Sockel legen - bei jedem
         * Aufruf neu.
         *
         * Bis zu dieser Phase stand hier Design::complete($snapshot), weil der
         * Schnappschuss das fertige Dokument war. Jetzt haelt er die Vorlage,
         * und die Wahl liegt in data['wahl'].
         *
         * Eine Einladung von VOR dieser Phase traegt kein wahl. Dann laeuft
         * personalize($sockel, []) - und das ist die Identitaet, gemessen und
         * nicht geglaubt (tests/invitations_v2_edit.php). Ihre Ausgabe bleibt
         * Byte fuer Byte dieselbe; deshalb gibt es zu dieser Aenderung keine
         * Wanderung und kein Datenumschreiben.
         *
         * is_array(): wahl aus einem von Hand veraenderten Dokument koennte
         * eine Zeichenkette sein, und personalize() erwartet ein Feld.
         */
        $wahl = is_array($einladung['data']['wahl'] ?? null) ? $einladung['data']['wahl'] : [];
        $doc  = DesignWizard::personalize($einladung['design_snapshot'], $wahl);
```

- [ ] **Adım 5: `DesignWizard`'ın yorumunu düzelt**

`php/src/DesignWizard.php`, `personalize()` docblock'unda şu satırları bul:

```php
     * Das Ergebnis ist der design_snapshot: ein vollstaendiges Dokument, das
     * der Renderer aus Phase 1 ohne eine einzige neue Zeile druckt. Es wird
     * bewusst keine Liste "was der Kunde geaendert hat" gefuehrt - die muesste
     * der Renderer, die Vorschau, das Panel und der spaetere Bearbeiten-
     * Bildschirm jeweils einzeln verstehen.
```

Şununla değiştir:

```php
     * Das Ergebnis ist ein vollstaendiges Dokument, das der Renderer aus
     * Phase 1 ohne eine einzige neue Zeile druckt.
     *
     * Es ist NICHT der design_snapshot. Bis zur Phase "Bearbeiten nach dem
     * Veroeffentlichen" war es das: publish() fror das Ergebnis ein und warf
     * die Eingabe weg. Damit war jede spaetere Aenderung verlustbehaftet.
     * Seitdem haelt die Spalte die unpersonalisierte Vorlage, die Wahl steht
     * in data['wahl'], und diese Funktion laeuft bei jedem Druck neu.
     *
     * Zwei Eigenschaften traegt der Bearbeiten-Bildschirm auf dem Ruecken, und
     * beide sind in tests/invitations_v2_edit.php gemessen: mit leerer Wahl ist
     * sie die Identitaet (deshalb funktionieren alte Einladungen unveraendert
     * weiter), und mit derselben Wahl zweimal angewendet aendert sie nichts
     * (deshalb darf zweimal gespeichert werden).
```

- [ ] **Adım 6: Testleri çalıştır**

```bash
cd php && php bin/test.php
```
Beklenen: sıfır hata, kontrol sayısı Görev 1'e göre artmış.

- [ ] **Adım 7: Elle doğrula — eski davetiye değişmedi**

Yerel Laragon'da bir v2 davetiyesi varsa:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT slug FROM invitations_v2 LIMIT 1'); echo \$r['slug'] ?? 'keine', PHP_EOL;"
```
Çıkan slug ile `http://<yerel>/de/v2/einladung/<slug>` adresini tarayıcıda aç. Kart, bölümler ve renkler bu görevden **önceki** hâliyle aynı görünmeli. Hiç davetiye yoksa sihirbazdan bir tane yayınla, sonra veritabanında `design_snapshot`'ın artık kişiselleştirilmemiş olduğunu ve `data.wahl` ile `data.updatedAt`'in geldiğini doğrula:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT data FROM invitations_v2 ORDER BY created_at DESC LIMIT 1'); \$d = json_decode(\$r['data'], true); echo isset(\$d['wahl']) ? 'wahl da' : 'wahl FEHLT', ' / ', \$d['updatedAt'] ?? 'kein Stand', PHP_EOL;"
```
Beklenen: `wahl da / 2026-...`

- [ ] **Adım 8: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/src/DesignWizard.php php/tests/invitations_v2_edit.php
git commit -m "Der Schnappschuss haelt die Vorlage, die Wahl liegt daneben"
```

---

### Görev 3: Formu okuyan iki toplayıcıyı ayır

Düzenleme ekranı sihirbazla **birebir aynı** alanları, aynı sınırları, aynı `data` anahtarlarını kullanmalı (spec §6). İkinci bir kopya zamanla birincinin peşinden sürüklenir. `publish()`'in içindeki toplama iki özel metoda çıkar; davranış değişmez.

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` (`publish()`)

**Interfaces:**
- Consumes: `DesignWizard::choices()` çıktısı (`$darf`)
- Produces:
  - `private function sammleAngaben(array $darf): array` — `$_POST`'tan `data` içeriği (alanlar, `families`, `program`, `sections[*].text`)
  - `private function sammleWahl(array $darf, string $slug, array $alt): array` — `$_POST`/`$_FILES`'tan `wahl` (`palette`, `fonts`, `layers`, `sections`)

- [ ] **Adım 1: Bu bir yeniden düzenleme — önce yeşili kaydet**

```bash
cd php && php bin/test.php | tail -2
```
Bu sayıyı not al. Görev sonunda **aynı** olmalı: yeni davranış yok, yeni test yok.

- [ ] **Adım 2: `sammleAngaben()`'i yaz**

`php/src/Controllers/InviteV2Controller.php`, `publish()` metodunun **üstüne** ekle:

```php
    /**
     * Was der Kunde eingetippt hat, in den Namen, die data traegt.
     *
     * Herausgeloest aus publish(), weil der Bearbeiten-Bildschirm dieselben
     * Felder mit denselben Grenzen und denselben Schluesseln lesen muss (Spec
     * §6). Zwei Kopien liefen auseinander, und die zweite waere die falsche.
     *
     * Ein leeres Feld setzt seinen Schluessel NICHT - families, program und
     * sections stehen nur da, wenn etwas drinsteht. Beim Bearbeiten heisst das
     * zugleich: ein geleertes Feld loescht seinen Eintrag, weil saveEdit() die
     * Inhaltsschluessel vorher wegnimmt und dieses Ergebnis darueberlegt.
     *
     * @param array{fields:list<string>,sections:array<string,array<string,mixed>>} $darf
     * @return array<string,mixed>
     */
    private function sammleAngaben(array $darf): array
    {
        $data = [];

        foreach ($darf['fields'] as $feld) {
            $data[$feld] = Security::clean($_POST[$feld] ?? '', $feld === 'message' ? 600 : 160);
        }

        foreach ($darf['sections'] as $sid => $abschnitt) {
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

            if (in_array('text', $abschnitt['fields'], true)) {
                // Unter der Kennung, nicht unter einem festen Namen: zwei
                // Textbloecke in einem Dokument wuerden sich sonst einen Platz
                // teilen und der zweite den ersten ueberschreiben.
                $text = Security::clean($_POST['sec_text_' . $sid] ?? '', 1200);
                if ($text !== '') {
                    $data['sections'][$sid]['text'] = $text;
                }
            }
        }

        return $data;
    }
```

- [ ] **Adım 3: `sammleWahl()`'i yaz**

Hemen altına ekle:

```php
    /**
     * Was der Kunde am Aussehen gewaehlt hat.
     *
     * Weissliste zuerst: gefragt wird immer $darf, und was dort nicht steht,
     * faellt still. Diese Schleife ist trotzdem Bequemlichkeit und nicht
     * Sicherheit - personalize() prueft am Ende noch einmal gegen dieselben
     * Rechte.
     *
     * $alt ist die vorhandene Wahl beim Bearbeiten und leer beim
     * Veroeffentlichen. Sie wird fuer genau eine Sache gebraucht: ein Foto,
     * das diesmal nicht neu hochgeladen wurde, behaelt seinen Pfad. Alles
     * andere kommt vollstaendig aus dem Formular - ein nicht gesetzter Haken
     * ist eine Entscheidung und kein fehlender Wert.
     *
     * @param array{palette:array<string,mixed>,fonts:array<string,mixed>,layers:array<string,array<string,bool>>,sections:array<string,array<string,mixed>>} $darf
     * @param array<string,mixed> $alt
     * @return array{palette:array<string,string>,fonts:array<string,string>,layers:array<string,mixed>,sections:array<string,mixed>}
     */
    private function sammleWahl(array $darf, string $slug, array $alt): array
    {
        $altLayers = is_array($alt['layers'] ?? null) ? $alt['layers'] : [];

        $wahl = ['palette' => [], 'fonts' => [], 'layers' => [], 'sections' => []];

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
                } else {
                    // Kein neuer Upload heisst nicht "kein Bild". Beim
                    // Bearbeiten steht der Pfad des vorhandenen Bildes in der
                    // alten Wahl und muss stehen bleiben - sonst loeschte
                    // jedes Speichern, bei dem niemand eine Datei auswaehlt,
                    // das Foto. Beim Veroeffentlichen ist $alt leer, dort
                    // aendert dieser Zweig nichts.
                    $vorher = $altLayers[$id]['src'] ?? null;
                    if (is_string($vorher) && $vorher !== '') {
                        $eintrag['src'] = $vorher;
                    }
                }
            }

            if ($eintrag !== []) {
                $wahl['layers'][$id] = $eintrag;
            }
        }

        // Abschnitte: das Zu- und Abschalten geht ins Dokument, der Inhalt in
        // die Daten (siehe sammleAngaben). Dieselbe Trennung wie bei den Ebenen.
        foreach ($darf['sections'] as $sid => $abschnitt) {
            if ($abschnitt['hide'] && isset($_POST['sec_hidden_' . $sid])) {
                $wahl['sections'][$sid] = ['hidden' => true];
            }
        }

        return $wahl;
    }
```

- [ ] **Adım 4: `publish()`'i toplayıcılara bağla**

`publish()` içinde, `$darf = DesignWizard::choices($design);` satırından sonraki blokta:

Şu bloğu **sil** (alanların toplandığı döngü):
```php
        $data = [];
        foreach ($darf['fields'] as $feld) {
            $data[$feld] = Security::clean($_POST[$feld] ?? '', $feld === 'message' ? 600 : 160);
        }
```
Yerine:
```php
        $data = $this->sammleAngaben($darf);
```

Sonra `$wahl = ['palette' => [], ...]` ile başlayan uzun bloğu — palette, fonts, layers döngüleri ve `$wahl['sections'] = []` ile başlayan abschnitte döngüsü dâhil, `$snapshot = ...` satırına kadar olan **her şeyi** — sil ve yerine tek satır koy:

```php
        // Die Wahl einsammeln, mit dem Slug fuer den Bilderordner. Leeres
        // drittes Argument: beim Veroeffentlichen gibt es keine alte Wahl,
        // aus der ein Foto uebernommen werden koennte.
        $wahl = $this->sammleWahl($darf, $slug, []);
```

Silinen blokta duran `families`/`program`/`text` toplaması artık `sammleAngaben()` içindedir — silinen kodda onların da olduğuna dikkat et; iki kez toplanmamalı.

`publish()`'in sırası şöyle kalmalı:
1. CSRF
2. throttle
3. `$darf = DesignWizard::choices($design);`
4. `$data = $this->sammleAngaben($darf);`
5. isim kontrolü (`$brauchtNamen` bloğu, değişmez)
6. slug türetme (değişmez)
7. `$wahl = $this->sammleWahl($darf, $slug, []);`
8. `$snapshot = DesignSections::complete(Design::complete($design));`
9. `$data['slug'] = ...` ve devamı

- [ ] **Adım 5: Testlerin hâlâ aynı sayıda geçtiğini gör**

```bash
cd php && php bin/test.php | tail -2
```
Beklenen: Adım 1'de not edilen sayının **aynısı**, sıfır hata.

- [ ] **Adım 6: Elle doğrula — sihirbaz hâlâ yayınlıyor**

`http://<yerel>/de/v2/einladung` adresinden bir davetiye yayınla: isimler, tarih, bir bölüm metni, bir renk seçimi. Yayınlanan linki aç; her şey görünmeli. Veritabanında:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT data FROM invitations_v2 ORDER BY created_at DESC LIMIT 1'); print_r(json_decode(\$r['data'], true));"
```
Beklenen: `wahl` içinde seçilen renk, `bride`/`groom`, varsa `sections`.

- [ ] **Adım 7: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php
git commit -m "Das Formular wird an einer Stelle gelesen, nicht an zweien"
```

---

### Görev 4: Anahtarın kapısı — tek yer, sel kontrolüyle

`manageKey` artık üç kapı açıyor (spec §5). 404 kuralı, `hash_equals`, boş anahtar reddi ve **yeni gelen sel kontrolü** tek bir özel metotta toplanır; `replies()` oraya taşınır. `edit()` bir sonraki görevde aynı kapıdan girer.

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` (`replies()`, yeni `nichtGefunden()` ve `manageZugang()`; `wizard()` ve `show()`'daki 404 blokları da tek metoda çekilir)

**Interfaces:**
- Consumes: `InvitationsV2::keyOk()` (Görev 1)
- Produces:
  - `private function nichtGefunden(): void`
  - `private function manageZugang(array $params): ?array` — yetki verirse davetiye kaydını döner ve `Cache-Control: private, no-store` yollar; vermezse 404 sayfasını basıp `null` döner

- [ ] **Adım 1: `nichtGefunden()`'i yaz**

`php/src/Controllers/InviteV2Controller.php`, sınıfın sonuna (`replies()`'ten sonra, sınıf kapanışından önce) ekle:

```php
    /**
     * Die 404-Seite dieses Controllers.
     *
     * Sie stand bis hierher viermal wortgleich in dieser Datei. Der Grund fuer
     * jede der drei Zeilen ist derselbe geblieben: pages/not-found liest
     * $locale unbedingt (not-found.php:10) und layout.php braucht $path -
     * fehlen sie, meldet PHP undefinierte Variablen und die Seite kommt auf
     * Englisch heraus, egal in welcher Sprache sie aufgerufen wurde.
     */
    private function nichtGefunden(): void
    {
        http_response_code(404);
        View::page('pages/not-found', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
        ]);
    }
```

- [ ] **Adım 2: `manageZugang()`'ı yaz**

Hemen altına ekle:

```php
    /**
     * Die Tuer, die manageKey oeffnet.
     *
     * Seit dieser Phase oeffnet derselbe Schluessel mehr als eine Seite: die
     * Antworten lesen UND die Einladung bearbeiten. Deshalb steht die Pruefung
     * einmal hier statt in jedem Bildschirm noch einmal - zwei Kopien
     * derselben Sicherung altern verschieden schnell.
     *
     * 404 und nicht 403: ein 403 bestaetigt, dass es diese Einladung gibt, und
     * wer den Schluessel nicht hat, soll auch das nicht erfahren.
     *
     * Die Bremse ist neu und sie ist der Preis dafuer, dass der Schluessel
     * jetzt Schreibrechte vergibt. Auf dem reinen Leseschirm war "128 Bit sind
     * nicht zu erraten" ein vertretbares Argument; sobald damit ein fremdes
     * Dokument geaendert werden kann, ist es keines mehr (Spec §5). Sie steht
     * VOR dem Vergleich, sonst braemste sie nur die Berechtigten - ein
     * falscher Schluessel faellt danach ohnehin ins 404.
     *
     * Eine ausgeloeste Bremse antwortet ebenfalls mit 404 und nicht mit einer
     * eigenen Meldung: jede unterscheidbare Antwort waere ein Orakel, an dem
     * sich ablesen liesse, dass es diese Einladung gibt. Der Preis ist, dass
     * ein Paar, das sechzigmal in zehn Minuten neu laedt, eine Fehlseite
     * sieht - bei diesem Mass ein unwahrscheinlicher Fall.
     *
     * @param array<string,string> $params
     * @return array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string}|null
     */
    private function manageZugang(array $params): ?array
    {
        // Normalisiert, damit "Foo" und "foo" denselben Eimer benutzen - sonst
        // waere die Bremse mit einer anderen Schreibweise zu umgehen.
        $slug = InvitationsV2::slug((string) ($params['slug'] ?? ''));

        if ($slug === '' || Security::throttle('v2-manage-' . $slug, 60, 600)) {
            $this->nichtGefunden();
            return null;
        }

        $einladung = InvitationsV2::find($slug);

        if ($einladung === null || !InvitationsV2::keyOk($einladung['data'], (string) ($params['key'] ?? ''))) {
            $this->nichtGefunden();
            return null;
        }

        // Diese Seiten sind eine geheime Adresse mit den Daten eines Paares
        // darauf - sie duerfen in keinem geteilten Cache landen. show()
        // bekommt no-store geschenkt, weil Security::csrf() dort eine Sitzung
        // startet; hier muss der Hinweis von Hand hinaus.
        header('Cache-Control: private, no-store');

        return $einladung;
    }
```

- [ ] **Adım 3: `replies()`'i kapıya taşı**

`replies()` metodunda, `$locale = I18n::locale();` satırından `header('Cache-Control: private, no-store');` satırına kadar olan **her şeyi** (davetiye arama, `$erwartet`/`$gegeben`, uzun 404 yorum bloğu ve `if` gövdesi, `header()` çağrısı) sil ve yerine koy:

```php
        $locale = I18n::locale();

        // Der Schluessel steht seit Phase 3B in den Daten jeder Einladung. Die
        // Pruefung - 404 statt 403, hash_equals, der leere Schluessel zuerst,
        // die Bremse - steht in manageZugang(), weil sie der Bearbeiten-
        // Bildschirm Wort fuer Wort auch braucht.
        $einladung = $this->manageZugang($params);
        if ($einladung === null) {
            return;
        }
```

`replies()`'in geri kalanı (`$antworten`, `$kommen`, `$namen`, `View::page`) **değişmez**.

- [ ] **Adım 4: Kalan üç 404 bloğunu da tek metoda çek**

`wizard()` içindeki `if ($designs === []) { ... }` ve `show()` içindeki `if ($einladung === null) { ... }` bloklarında, `http_response_code(404); View::page('pages/not-found', [...]);` üçlüsünü `$this->nichtGefunden();` ile değiştir. Yorumlar `nichtGefunden()`'e taşındı, tekrar edilmez. `return;` satırları yerinde kalır.

- [ ] **Adım 5: Testleri çalıştır**

```bash
cd php && php bin/test.php | tail -2
```
Beklenen: Görev 3'teki sayının aynısı, sıfır hata (bu görev davranış eklemiyor, yer değiştiriyor).

- [ ] **Adım 6: Elle doğrula — kapı çalışıyor**

Görev 3'te yayınladığın davetiyenin `manageKey`'ini al:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT slug, data FROM invitations_v2 ORDER BY created_at DESC LIMIT 1'); \$d = json_decode(\$r['data'], true); echo \$r['slug'], ' ', \$d['manageKey'], PHP_EOL;"
```
Üç kontrol:
```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://<yerel>/de/v2/einladung/<slug>/<manageKey>"   # 200
curl -s -o /dev/null -w "%{http_code}\n" "http://<yerel>/de/v2/einladung/<slug>/00000000000000000000000000000000"  # 404
curl -s -D- -o /dev/null "http://<yerel>/de/v2/einladung/<slug>/<manageKey>" | grep -i cache-control  # private, no-store
```

- [ ] **Adım 7: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php
git commit -m "Der Schluessel oeffnet mehr als eine Tuer, also steht die Pruefung an einer Stelle"
```

---

### Görev 5: Düzenleme ekranı — okuma yolu

Ekran, yol kaydı, şablon ve sözlük anahtarları. Bu görev **yazmaz**: form basılır, dolu gelir, önizleme sağda durur, ama gönderme bir sonraki görevde bağlanır.

**Files:**
- Modify: `php/public/index.php` (`/{locale}/v2/einladung/{slug}/{key}` satırının hemen üstüne bir yol)
- Modify: `php/src/Controllers/InviteV2Controller.php` (yeni `edit()`, yeni `formularWerte()`)
- Create: `php/templates/pages/invite-v2-edit.php`
- Modify: `php/data/dict.php` (üç dilde `invitation2` bloğu)

**Interfaces:**
- Consumes: `manageZugang()` (Görev 4), `InvitationsV2::canEditDesign()` (Görev 1), `DesignWizard::choices()`, `DesignWizard::personalize()`
- Produces:
  - `public function edit(array $params): void`
  - `private function formularWerte(array $data): array` — `sammleAngaben()`'in ters yönü

**Bu görevin en kolay yanlış yapılacak yeri:** form neyin üstüne kurulur. **Cevap: donmuş snapshot'ın, kişiselleştirilmiş belgenin değil.** `personalize()` gizlenmiş bir katmanı **siler** (`DesignWizard.php:298`) ve gizlenmiş bir bölümü `enabled=false` yapar; `choices()` ise ne silinmiş katmanı ne de kapalı bölümü sunar (`DesignWizard.php:117`). Form kişiselleştirilmiş belgeden kurulursa, bir kez gizlenen katman **bir daha geri getirilemez** — kutucuğu bile ekranda olmaz. Bu yüzden:

- `$darf = DesignWizard::choices($einladung['design_snapshot'])` → formun sunduğu her şey
- `$doc = DesignWizard::personalize($snapshot, $wahl)` → **sadece** sağdaki önizleme

- [ ] **Adım 1: Sözlük anahtarlarını ekle**

`php/data/dict.php`, **üç blokta da** (`'de'` → satır ~169, `'en'` → ~583, `'tr'` → ~995) `'invitation2' => [` dizisinin içine, `'repliesTotal'` satırından sonra ekle.

`de` bloğuna:
```php
            'editTitle'      => 'Eure Einladung ändern',
            'editLead'       => 'Der Link, den ihr verschickt habt, bleibt derselbe.',
            'editTabTexts'   => 'Eure Angaben',
            'editTabDesign'  => 'Euer Design',
            'editSave'       => 'Änderungen speichern',
            'editSaved'      => 'Gespeichert. Die Einladung zeigt jetzt eure Änderung.',
            'editGuestLink'  => 'Der Link eurer Gäste',
            'editLocked'     => 'Diese Einladung wurde mit einer früheren Fassung veröffentlicht. Ihre Texte lassen sich ändern, ihr Design nicht mehr – dafür fehlt uns, was ihr damals ausgewählt habt. Wenn ihr am Aussehen etwas ändern möchtet, schreibt uns.',
            'editPhoto'      => 'Neues Bild',
            'editPhotoNote'  => 'Ohne Auswahl bleibt das vorhandene Bild.',
            'errorVeraltet'  => 'Diese Einladung wurde inzwischen an anderer Stelle geändert. Bitte ladet die Seite neu und versucht es noch einmal – sonst überschreibt ihr die andere Änderung.',
```

`en` bloğuna:
```php
            'editTitle'      => 'Change your invitation',
            'editLead'       => 'The link you sent out stays the same.',
            'editTabTexts'   => 'Your details',
            'editTabDesign'  => 'Your design',
            'editSave'       => 'Save changes',
            'editSaved'      => 'Saved. The invitation now shows your change.',
            'editGuestLink'  => 'Your guests’ link',
            'editLocked'     => 'This invitation was published with an earlier version. Its texts can be changed, its design cannot – we no longer have what you picked back then. If you would like to change how it looks, write to us.',
            'editPhoto'      => 'New picture',
            'editPhotoNote'  => 'Without a selection the existing picture stays.',
            'errorVeraltet'  => 'This invitation has been changed elsewhere in the meantime. Please reload the page and try again – otherwise you overwrite the other change.',
```

`tr` bloğuna:
```php
            'editTitle'      => 'Davetiyenizi değiştirin',
            'editLead'       => 'Gönderdiğiniz bağlantı aynı kalır.',
            'editTabTexts'   => 'Bilgileriniz',
            'editTabDesign'  => 'Tasarımınız',
            'editSave'       => 'Değişiklikleri kaydet',
            'editSaved'      => 'Kaydedildi. Davetiye artık değişikliğinizi gösteriyor.',
            'editGuestLink'  => 'Misafirlerinizin bağlantısı',
            'editLocked'     => 'Bu davetiye eski bir sürümle yayınlandı. Metinleri değiştirilebilir, tasarımı değiştirilemez — o gün neyi seçtiğiniz elimizde kalmadı. Görünümde bir şey değiştirmek isterseniz bize yazın.',
            'editPhoto'      => 'Yeni görsel',
            'editPhotoNote'  => 'Seçim yapılmazsa mevcut görsel korunur.',
            'errorVeraltet'  => 'Bu davetiye bu arada başka bir yerde değiştirildi. Lütfen sayfayı yenileyip yeniden deneyin — yoksa diğer değişikliğin üzerine yazarsınız.',
```

Not: `errorCsrf`, `errorThrottle`, `errorNames`, `fieldBride`…`fieldHashtag`, `familyBride`, `familyGroom`, `programTime`, `programTitle`, `sectionHide`, `sectionText`, `sectionTextNote` zaten üç dilde var — düzenleme ekranı onları olduğu gibi kullanır.

- [ ] **Adım 2: `formularWerte()`'yi yaz**

`php/src/Controllers/InviteV2Controller.php`, `sammleAngaben()`'in hemen **üstüne** ekle:

```php
    /**
     * Aus den gespeicherten Daten die Namen machen, die das Formular traegt.
     *
     * Die Gegenrichtung von sammleAngaben(): dort werden family_bride und
     * prog_title_0 zu families und program, hier wieder zurueck. Ohne diesen
     * Weg stuende der Bearbeiten-Bildschirm mit leeren Feldern da, und ein
     * Speichern loeschte alles, was der Kunde beim Veroeffentlichen eingegeben
     * hatte - der schlimmstmoegliche Ausgang fuer einen Bildschirm, der
     * Tippfehler reparieren soll.
     *
     * Ueberall is_string()/is_array(): das Dokument kommt aus JSON und muss
     * nicht die Form haben, die es haben sollte.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function formularWerte(array $data): array
    {
        $werte = [];

        foreach (DesignWizard::FIELD_ORDER as $feld) {
            $werte[$feld] = is_string($data[$feld] ?? null) ? $data[$feld] : '';
        }

        $familie = is_array($data['families'] ?? null) ? $data['families'] : [];
        $werte['family_bride'] = is_string($familie['bride'] ?? null) ? $familie['bride'] : '';
        $werte['family_groom'] = is_string($familie['groom'] ?? null) ? $familie['groom'] : '';

        // array_values, weil das Formular acht feste Zeilen hat und die
        // gespeicherte Liste loechrige Schluessel tragen koennte.
        $programm = is_array($data['program'] ?? null) ? array_values($data['program']) : [];
        for ($z = 0; $z < 8; $z++) {
            $zeile = is_array($programm[$z] ?? null) ? $programm[$z] : [];
            $werte['prog_time_' . $z]  = is_string($zeile['time'] ?? null) ? $zeile['time'] : '';
            $werte['prog_title_' . $z] = is_string($zeile['title'] ?? null) ? $zeile['title'] : '';
        }

        foreach ((array) ($data['sections'] ?? []) as $sid => $eintrag) {
            if (is_array($eintrag) && is_string($eintrag['text'] ?? null)) {
                $werte['sec_text_' . (string) $sid] = $eintrag['text'];
            }
        }

        return $werte;
    }
```

- [ ] **Adım 3: `edit()`'i yaz (okuma yolu)**

`php/src/Controllers/InviteV2Controller.php`, `replies()` metodundan **sonra** ekle:

```php
    /**
     * Eine veroeffentlichte Einladung nachtraeglich aendern.
     *
     * Der Bildschirm, den Spec §1 verlangt: heute muss ein Paar wegen eines
     * Buchstabens eine neue Einladung bauen und den Link erneut verschicken.
     *
     * Zwei Tabs, dieselben Felder wie im Assistenten - und ein Sockel, der
     * sich nicht bewegt. slug, manageKey, die Vorlage, createdAt, paid und die
     * Antworten der Gaeste stehen nicht auf diesem Formular (Spec §6).
     *
     * @param array<string,string> $params
     */
    public function edit(array $params): void
    {
        $locale = I18n::locale();

        $einladung = $this->manageZugang($params);
        if ($einladung === null) {
            return;
        }

        $key  = (string) ($params['key'] ?? '');
        $slug = (string) $einladung['slug'];
        $data = $einladung['data'];

        /*
         * Das Formular wird auf dem EINGEFRORENEN Sockel gebaut, nicht auf dem
         * personalisierten Dokument.
         *
         * personalize() LOESCHT eine ausgeblendete Ebene (DesignWizard.php:298)
         * und schaltet einen ausgeblendeten Abschnitt auf enabled=false; und
         * choices() bietet weder eine geloeschte Ebene noch einen
         * abgeschalteten Abschnitt an (DesignWizard.php:117). Baute man das
         * Formular auf dem Ergebnis, waere jedes Ausblenden endgueltig - das
         * Haekchen zum Wiedereinblenden stuende gar nicht erst auf der Seite.
         */
        $sockel = $einladung['design_snapshot'];
        $darf   = DesignWizard::choices($sockel);

        $wahl = InvitationsV2::canEditDesign($data) ? (array) $data['wahl'] : [];

        // Nur zum Zeichnen der Vorschau - nie als Grundlage des Formulars.
        $doc = DesignWizard::personalize($sockel, $wahl);

        $error = '';
        $ok    = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $ergebnis = $this->saveEdit($einladung, $darf);
            if (isset($ergebnis['error'])) {
                $error = (string) $ergebnis['error'];
            } else {
                $ok = true;
                // Neu lesen: das Formular soll zeigen, was jetzt in der Zeile
                // steht, und nicht, was vor dem Speichern darin stand - sonst
                // traegt das versteckte updatedAt einen ueberholten Stand und
                // das naechste Speichern faellt gegen sich selbst durch.
                $frisch = InvitationsV2::find($slug);
                if ($frisch !== null) {
                    $einladung = $frisch;
                    $data      = $frisch['data'];
                    $wahl      = InvitationsV2::canEditDesign($data) ? (array) $data['wahl'] : [];
                    $doc       = DesignWizard::personalize($sockel, $wahl);
                }
            }
        }

        $scope  = '.d-' . $doc['id'];
        $values = Design::bindValues($data, $locale);
        $namen  = trim(((string) ($data['bride'] ?? '')) . ' & ' . ((string) ($data['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-edit', [
            'locale' => $locale,
            // Ohne $path meldet layout.php eine undefinierte Variable im
            // Sprachumschalter. Der Schluessel gehoert NICHT hinein: der
            // Umschalter schriebe ihn sonst in eine sichtbare Adresse.
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', [
                'title'       => $namen !== '' ? $namen : I18n::t('invitation2.editTitle'),
                'solidHeader' => true,
                // Diese Seite IST der Schluessel. Sie gehoert unter keinen
                // Umstaenden in einen Index.
                'noindex'     => true,
                // Dasselbe Skript wie der Assistent, unveraendert: es blendet
                // [data-step] ein und aus und spiegelt [data-live] in die
                // Karte. Es entscheidet nichts.
                'scripts'     => ['/assets/invite-v2.js'],
            ]),
            // Der eingefrorene Sockel, vollstaendig: die Vorlage liest daraus
            // die Ausgangsfarbe einer Ebene.
            'design'     => Design::complete($sockel),
            'choices'    => $darf,
            'values'     => $this->formularWerte($data),
            'wahl'       => $wahl,
            'darfDesign' => InvitationsV2::canEditDesign($data),
            'gastPfad'   => I18n::path('/v2/einladung/' . $slug),
            'stand'      => is_string($data['updatedAt'] ?? null) ? $data['updatedAt'] : '',
            'scope'      => ltrim($scope, '.'),
            'styles'     => Design::css($doc, $scope),
            'sectionCss' => DesignSections::css($doc, $scope),
            'karte'      => Design::html($doc, $values, $locale, 'card'),
            'abschnitte' => DesignSections::html($doc, $data, $locale, '', ['csrf' => '', 'sent' => false]),
            'csrf'       => Security::csrf(),
            'error'      => $error,
            'ok'         => $ok,
        ]);
    }
```

Not: `saveEdit()` bir sonraki görevde yazılacak. Bu görevi tamamlamak için **şimdilik** şu geçici gövdeyi ekle ve Görev 6'da doldur:

```php
    /**
     * @param array<string,mixed> $einladung
     * @param array<string,mixed> $darf
     * @return array<string,string>
     */
    private function saveEdit(array $einladung, array $darf): array
    {
        // Aufgabe 6 fuellt diesen Weg. Bis dahin schreibt der Bildschirm nichts.
        return ['error' => 'csrf'];
    }
```

- [ ] **Adım 4: Şablonu yaz**

`php/templates/pages/invite-v2-edit.php` dosyasını oluştur:

```php
<?php
/**
 * Nachtraeglich aendern: dieselben Felder wie im Assistenten, zwei Tabs.
 *
 * Ohne Skript stehen beide Tabs untereinander und ein Absenden reicht -
 * dieselbe Regel wie im Assistenten. invite-v2.js blendet sie ein und aus; es
 * entscheidet nichts, welche Felder es gibt, steht schon fest, bevor diese
 * Datei laeuft (DesignWizard::choices() auf dem eingefrorenen Sockel).
 *
 * Absichtlich OHNE [data-sections]: der Assistent laesst sich die Abschnitte
 * bei jeder Aenderung vom Server neu zeichnen, und jede dieser Anfragen liefe
 * hier durch manageZugang() und damit gegen die Bremse (60 je zehn Minuten).
 * Ein Paar, das zwanzig Felder durchgeht, saesse mitten im Bearbeiten vor
 * einer 404-Seite. ladeAbschnitte() steigt ohne diesen Knoten sofort aus
 * (invite-v2.js:87), also bleibt die Vorschau hier ein einmaliges,
 * serverseitig gezeichnetes Bild - und die Karte laeuft ueber [data-live]
 * trotzdem live mit.
 *
 * @var string $locale
 * @var array<string,mixed> $design      der eingefrorene Sockel, vollstaendig
 * @var array<string,mixed> $choices
 * @var array<string,string> $values     Formularnamen, nicht Datennamen
 * @var array<string,mixed> $wahl        was der Kunde beim Veroeffentlichen waehlte
 * @var bool   $darfDesign               Spec §4: ohne wahl kein Design-Tab
 * @var string $gastPfad
 * @var string $stand                    data['updatedAt'], reist versteckt mit
 * @var string $scope
 * @var string $styles
 * @var string $sectionCss
 * @var string $karte
 * @var string $abschnitte
 * @var string $csrf
 * @var string $error
 * @var bool   $ok
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');

// Was der Kunde beim Veroeffentlichen an dieser Ebene gewaehlt hat, oder null.
$gewaehlt = static function (string $id) use ($wahl): array {
    $layers = is_array($wahl['layers'] ?? null) ? $wahl['layers'] : [];
    return is_array($layers[$id] ?? null) ? $layers[$id] : [];
};

// Ein Farbfeld sendet immer einen Wert mit, auch wenn niemand es beruehrt hat -
// ohne value faellt der Browser auf #000000 zurueck und das Speichern saehe
// fuer jede erlaubte Ebene Schwarz. Zuerst die Wahl des Kunden, dann die
// Ausgangsfarbe des Sockels. style.color ist ein Markenname, nicht der Wert
// selbst - der Wert steht in der Palette.
$farbeVon = static function (string $id) use ($design, $gewaehlt): string {
    $eigen = $gewaehlt($id)['color'] ?? null;
    if (is_string($eigen) && $eigen !== '') {
        return $eigen;
    }
    foreach ($design['layers'] as $el) {
        if ((string) $el['id'] === $id) {
            $marke = (string) ($el['style']['color'] ?? '');
            return (string) ($design['palette'][$marke]['value'] ?? '#000000');
        }
    }
    return '#000000';
};

$label = 'text-[0.62rem] uppercase tracking-[0.18em] text-muted';
$field = 'mt-2 w-full border border-sand-deep bg-cream px-4 py-3 text-sm text-ink';

$inputTypes = ['date' => 'date', 'time' => 'time'];

$fieldTitles = [
    'bride'   => $t('fieldBride'),   'groom'   => $t('fieldGroom'),
    'date'    => $t('fieldDate'),    'time'    => $t('fieldTime'),
    'venue'   => $t('fieldVenue'),   'address' => $t('fieldAddress'),
    'message' => $t('fieldMessage'), 'hashtag' => $t('fieldHashtag'),
];

// Ein Tab, wenn das Design zu ist (Spec §4) - sonst zwei.
$tabs = [$t('editTabTexts')];
if ($darfDesign) {
    $tabs[] = $t('editTabDesign');
}
?>
<?= Ui::pageHero('invite2-edit-hero', $t('editTitle'), I18n::t('nav.invitation2'), $t('editLead')) ?>

<?= Ui::sectionOpen() ?>

<?php /*
   Eigene Regeln statt Tailwind-Klassen: die PHP-Fassung laedt ein FERTIG
   gebautes style.css, kein JIT. Eine Klasse, die dort nicht drinsteht, tut
   schlicht nichts. Dieselben drei Regeln wie im Assistenten
   (invite-v2-wizard.php) - dort steht der ausfuehrliche Grund.
*/ ?>
<style>
  .wz-grid { margin-inline: auto; max-width: 72rem; }
  @media (min-width: 1024px) {
    .wz-grid { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 3rem; align-items: start; }
    .wz-side { position: sticky; top: 7rem; }
  }
  @media (max-width: 1023px) { .wz-side { margin-top: 3rem; } }
  .wz-card { aspect-ratio: 2 / 3; background: var(--d-bg, #EFE7DC); }
  .wz-quiet { margin: 0; border: 0; padding: 0; min-inline-size: 0; }
</style>

<?php if ($ok) : ?>
  <p class="mx-auto mb-8 max-w-2xl border-l-2 border-gold px-5 py-4 text-sm text-ink"><?= e($t('editSaved')) ?></p>
<?php endif; ?>

<?php if ($error !== '') : ?>
  <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
    <?= e($t('error' . ucfirst($error))) ?>
  </p>
<?php endif; ?>

<div class="wz-grid">
  <form method="post" enctype="multipart/form-data" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php /*
       Der Stand beim Oeffnen reist mit. Stimmt er beim Absenden nicht mehr
       mit dem gespeicherten ueberein, hat jemand in einem anderen Tab
       gespeichert und es wird nichts geschrieben (Spec §7).
    */ ?>
    <input type="hidden" name="stand" value="<?= e($stand) ?>">

    <ol class="mb-10 flex flex-wrap gap-x-6 gap-y-2 border-b border-sand-deep pb-4 text-[0.62rem] uppercase tracking-[0.16em]" data-steps>
      <?php foreach ($tabs as $i => $titel) : ?>
        <li data-step-label="<?= $i ?>" class="text-muted"><?= $i + 1 ?>. <?= e($titel) ?></li>
      <?php endforeach; ?>
    </ol>

    <fieldset data-step="0" class="space-y-8">
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

      <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
        <?php
          // Der Titel des Grafikers, falls vorhanden - sonst die Kennung als
          // letzter Ausweg (dieselbe Regel wie in DesignSections::html()).
          $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
          if ($secTitel === '') {
              $secTitel = (string) ($abschnitt['title']['de'] ?? '');
          }
          if ($secTitel === '') {
              $secTitel = (string) $sid;
          }
          $hatFeld = $abschnitt['fields'] !== [];
        ?>
        <?php if (!$hatFeld) { continue; } ?>
        <div class="border-t border-sand-deep pt-6">
          <div class="<?= $label ?>"><?= e($secTitel) ?></div>

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

          <?php if (in_array('text', $abschnitt['fields'], true)) : ?>
            <div class="mt-3">
              <label class="<?= $label ?>" for="st-<?= e((string) $sid) ?>"><?= e($t('sectionText')) ?></label>
              <textarea id="st-<?= e((string) $sid) ?>" name="sec_text_<?= e((string) $sid) ?>" rows="4" maxlength="1200"
                        class="<?= $field ?>"><?= e($old('sec_text_' . $sid)) ?></textarea>
              <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('sectionTextNote')) ?></p>
            </div>
          <?php endif; ?>

          <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
            <?php /*
               Feste Zeilenzahl statt Hinzufuegen-Knopf: ohne Skript
               funktioniert das Formular sonst nicht - dieselbe Entscheidung
               wie im Assistenten.
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
    </fieldset>

    <?php if ($darfDesign) : ?>
      <fieldset data-step="1" class="space-y-8">
        <?php foreach ($choices['palette'] as $marke => $eintrag) : ?>
          <?php
            $vorher = $wahl['palette'][$marke] ?? null;
            $wert = is_string($vorher) && $vorher !== '' ? $vorher : (string) $eintrag['value'];
          ?>
          <div>
            <label class="<?= $label ?>" for="p-<?= e((string) $marke) ?>">
              <?= e($eintrag['label'][$locale] ?? $eintrag['label']['de'] ?? $marke) ?>
            </label>
            <input id="p-<?= e((string) $marke) ?>" type="color" name="palette_<?= e((string) $marke) ?>"
                   value="<?= e($wert) ?>" class="<?= $field ?> h-12">
          </div>
        <?php endforeach; ?>

        <?php foreach ($choices['fonts'] as $marke => $eintrag) : ?>
          <?php
            $vorher = $wahl['fonts'][$marke] ?? null;
            $wert = is_string($vorher) && $vorher !== '' ? $vorher : (string) $eintrag['family'];
          ?>
          <div>
            <label class="<?= $label ?>" for="s-<?= e((string) $marke) ?>"><?= e((string) $marke) ?></label>
            <select id="s-<?= e((string) $marke) ?>" name="fonts_<?= e((string) $marke) ?>" class="<?= $field ?>">
              <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                <option value="<?= e($familie) ?>" <?= $wert === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>

        <?php foreach ($choices['layers'] as $id => $rechte) : ?>
          <?php $eigen = $gewaehlt((string) $id); ?>
          <div class="border-t border-sand-deep pt-6">
            <div class="<?= $label ?>"><?= e((string) $id) ?></div>

            <?php if ($rechte['color']) : ?>
              <input type="color" name="layer_color_<?= e((string) $id) ?>" value="<?= e($farbeVon((string) $id)) ?>" class="<?= $field ?> h-12">
            <?php endif; ?>

            <?php if ($rechte['font']) : ?>
              <?php $fontVorher = is_string($eigen['font'] ?? null) ? $eigen['font'] : ''; ?>
              <select name="layer_font_<?= e((string) $id) ?>" class="<?= $field ?>">
                <option value=""><?= e($locale === 'de' ? '— wie im Design —' : '— as the design has it —') ?></option>
                <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                  <option value="<?= e($familie) ?>" <?= $fontVorher === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>

            <?php if ($rechte['text']) : ?>
              <?php
                $textVorher = is_array($eigen['text'] ?? null) ? $eigen['text'] : [];
                $textWert = is_string($textVorher['de'] ?? null) ? $textVorher['de'] : '';
              ?>
              <input type="text" name="layer_text_<?= e((string) $id) ?>" class="<?= $field ?>" maxlength="600" value="<?= e($textWert) ?>">
            <?php endif; ?>

            <?php if ($rechte['photo']) : ?>
              <div class="mt-3">
                <label class="<?= $label ?>" for="b-<?= e((string) $id) ?>"><?= e($t('editPhoto')) ?></label>
                <input id="b-<?= e((string) $id) ?>" type="file" name="layer_src_<?= e((string) $id) ?>"
                       accept="image/jpeg,image/png,image/webp" class="<?= $field ?>">
                <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('editPhotoNote')) ?></p>
              </div>
            <?php endif; ?>

            <?php if ($rechte['hide']) : ?>
              <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="layer_hidden_<?= e((string) $id) ?>" <?= !empty($eigen['hidden']) ? 'checked' : '' ?>>
                <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
              </label>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
          <?php if (!$abschnitt['hide']) { continue; } ?>
          <?php
            $sekWahl = is_array($wahl['sections'] ?? null) ? $wahl['sections'] : [];
            $aus = !empty($sekWahl[$sid]['hidden']);
            $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
            if ($secTitel === '') { $secTitel = (string) ($abschnitt['title']['de'] ?? ''); }
            if ($secTitel === '') { $secTitel = (string) $sid; }
          ?>
          <div class="border-t border-sand-deep pt-6">
            <div class="<?= $label ?>"><?= e($secTitel) ?></div>
            <label class="mt-3 flex items-center gap-2 text-sm text-muted">
              <input type="checkbox" name="sec_hidden_<?= e((string) $sid) ?>" <?= $aus ? 'checked' : '' ?>>
              <?= e($t('sectionHide')) ?>
            </label>
          </div>
        <?php endforeach; ?>
      </fieldset>
    <?php else : ?>
      <?php /*
         Kein stiller Verzicht, sondern ein Satz auf dem Bildschirm (Spec §4):
         diese Einladung hat keine gespeicherte Wahl, ihr Sockel ist bereits
         personalisiert, und eine neue Auswahl darauf waere verlustbehaftet.
      */ ?>
      <p class="mt-10 border-t border-sand-deep pt-6 text-[0.85rem] leading-relaxed text-muted">
        <?= e($t('editLocked')) ?>
      </p>
    <?php endif; ?>

    <div class="mt-10 border-t border-sand-deep pt-6">
      <button type="submit" class="border border-ink px-8 py-4 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= e($t('editSave')) ?>
      </button>

      <p class="mt-6 text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e($t('editGuestLink')) ?></p>
      <p class="mt-2 break-all text-sm">
        <a class="text-gold underline" href="<?= e($gastPfad) ?>"><?= e($gastPfad) ?></a>
      </p>
    </div>
  </form>

  <aside class="wz-side">
    <style><?= $styles ?><?= $sectionCss ?></style>
    <div class="<?= e($scope) ?> wz-card mx-auto w-full max-w-xs" data-preview
         style="position:relative;container-type:inline-size;"><?= $karte ?></div>

    <?php /*
       disabled fieldset, kein blosses CSS: der rsvp-Abschnitt druckt ein
       echtes Formular mit Absenden-Knopf. In der Vorschau darf es nicht
       abschicken - es steht ausserhalb dieses Formulars und wuerde eine
       eigene Anfrage an dieselbe Adresse stellen. Ein deaktiviertes fieldset
       schaltet jedes Bedienelement darin ab, in jedem Browser.

       Ohne data-sections: siehe der Kommentar am Kopf dieser Datei.
    */ ?>
    <fieldset disabled class="wz-quiet">
      <div class="<?= e($scope) ?> mx-auto mt-6 w-full max-w-xs text-[0.8rem]"><?= $abschnitte ?></div>
    </fieldset>
  </aside>
</div>

<?= Ui::sectionClose() ?>
```

- [ ] **Adım 5: Yolu kaydet**

`php/public/index.php`, `$router->get('/{locale}/v2/einladung/{slug}/{key}', ...)` satırının **hemen üstüne** ekle:

```php
// Der Bearbeiten-Bildschirm, unter demselben Schluessel wie die Antworten.
// Vor dem kuerzeren Muster, weil sie zusammengehoeren - noetig waere die
// Reihenfolge nicht: beide Muster sind verankert und {key} matcht keinen
// Schraegstrich, also koennen sie einander nicht fangen.
//
// any und nicht get: dieser Bildschirm nimmt seine eigene Aenderung entgegen.
$router->any('/{locale}/v2/einladung/{slug}/{key}/bearbeiten', $page_(static fn (array $p) => (new InviteV2Controller())->edit($p)));
```

- [ ] **Adım 6: Testleri çalıştır**

```bash
cd php && php bin/test.php | tail -2
```
Beklenen: Görev 4'teki sayının aynısı, sıfır hata.

- [ ] **Adım 7: Elle doğrula — ekran açılıyor ve dolu geliyor**

```
http://<yerel>/de/v2/einladung/<slug>/<manageKey>/bearbeiten
```
Kontrol listesi:
- Alanlar **dolu** geliyor (isimler, tarih, varsa bölüm metni, program satırları).
- Sağda kart ve bölümler görünüyor; kart boş bir kutu değil.
- İki sekme var; "Weiter"/"Zurück" düğmeleri çalışıyor. Tasarım sekmesinde renk kutusu yayınlarken seçilen renkte.
- İsim alanına yazarken sağdaki kart **canlı** değişiyor.
- Yanlış anahtarla aynı adres **404**.
- Kaydet'e basınca (Görev 6 henüz yok) `errorCsrf` mesajı çıkıyor — beklenen, geçici.

Ayrıca eski (wahl'sız) bir davetiyeyi taklit et:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT slug, data FROM invitations_v2 ORDER BY created_at DESC LIMIT 1'); \$d = json_decode(\$r['data'], true); unset(\$d['wahl']); Atelier\Db::run('UPDATE invitations_v2 SET data = ? WHERE slug = ?', [Atelier\Db::encode(\$d), \$r['slug']]); echo 'wahl entfernt', PHP_EOL;"
```
Sayfayı yenile: tek sekme, tasarım sekmesi yok, `editLocked` cümlesi ekranda. Sonra `wahl`'ı geri koymak için o davetiyeyi sil ve yeniden yayınla (ya da bu davetiyeyi Görev 7'nin "eski davetiye" senaryosu için sakla).

- [ ] **Adım 8: Commit**

```bash
git add php/public/index.php php/src/Controllers/InviteV2Controller.php php/templates/pages/invite-v2-edit.php php/data/dict.php
git commit -m "Der Bearbeiten-Bildschirm: zwei Tabs, dieselben Felder, ein Sockel der sich nicht bewegt"
```

---

### Görev 6: Yazma yolu

`saveEdit()`. CSRF önce, sonra sel, sonra eşzamanlılık, sonra §4 kapısı, sonra yazma. `rsvps`'e tek bir sorgu bile gitmez.

**Files:**
- Modify: `php/src/Controllers/InviteV2Controller.php` (`saveEdit()` geçici gövdesi doldurulur)
- Modify: `php/tests/invitations_v2_edit.php` (veritabanı bloğuna ekleme)

**Interfaces:**
- Consumes: `sammleAngaben()`, `sammleWahl()` (Görev 3), `InvitationsV2::stale()`, `canEditDesign()`, `saveData()` (Görev 1)
- Produces: `private function saveEdit(array $einladung, array $darf): array` — `['error' => 'csrf'|'throttle'|'veraltet'|'names']` veya `[]`

- [ ] **Adım 1: Başarısız testi yaz**

`php/tests/invitations_v2_edit.php`, veritabanı bloğunun sonundaki `Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);` satırının **üstüne** ekle:

```php
/* --- Bearbeiten ruehrt die Antworten der Gaeste nicht an --- */

/*
 * Spec §8: "Antworten sind unberuehrbar." Die Regel ist einfach genug, um sie
 * zu glauben, und genau deshalb wird sie gemessen: der Bearbeiten-Weg schreibt
 * mit InvitationsV2::saveData(), und wenn dort je ein zweiter Schreibzugriff
 * dazukaeme, faellt dieser Test.
 */
Atelier\Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Mehmet', 'coming' => true,
    'count' => 2, 'note' => 'Wir kommen', 'at' => '2027-01-01T10:00:00+01:00',
]);

$vorAntworten = InvitationsV2::rsvps($slug);

InvitationsV2::saveData($slug, [
    'slug' => $slug, 'bride' => 'Marije', 'manageKey' => $echt, 'updatedAt' => '2026-08-20T14:00:00+03:00',
]);

assert_same($vorAntworten, InvitationsV2::rsvps($slug), 'saveData: die Antworten der Gaeste bleiben, wie sie waren');
assert_same('Marije', (InvitationsV2::find($slug)['data']['bride'] ?? ''), 'saveData: und die Daten sind trotzdem geschrieben');

/* --- Der Stand wandert mit und sperrt den zweiten Tab --- */

$jetzt = InvitationsV2::find($slug)['data'];
assert_true(!InvitationsV2::stale($jetzt, '2026-08-20T14:00:00+03:00'), 'stale: der gerade geschriebene Stand passt');
assert_true(InvitationsV2::stale($jetzt, '2026-08-20T13:00:00+03:00'), 'stale: ein Formular von vorher wird abgelehnt');

Atelier\Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);
```

- [ ] **Adım 2: Testin başarısız olduğunu gör**

```bash
cd php && php bin/test.php invitations_v2_edit
```
`needs_db()` doğruysa: testler **geçer** (yeni davranış yok, mevcut sözleşmeyi kilitliyor). Kırmızı kanıtı için geçici olarak `saveData()`'nın gövdesine `Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);` ekle, testin `die Antworten der Gaeste bleiben` satırında düştüğünü gör, sonra geri al.

`config.php` yoksa test bloğu atlanır — o durumda Adım 6'daki elle doğrulama bu sözleşmenin tek kanıtıdır ve atlanamaz.

- [ ] **Adım 3: `saveEdit()`'i yaz**

`php/src/Controllers/InviteV2Controller.php` içindeki geçici `saveEdit()` gövdesini şununla değiştir:

```php
    /**
     * Die Aenderung schreiben.
     *
     * Die Reihenfolge ist die Sicherung, nicht eine Geschmacksfrage:
     *
     *   1. CSRF - vor allem anderen, wie auf jedem Schreibweg dieser Datei.
     *   2. Bremse - ein eigener Eimer fuers Schreiben, damit ein Skript nicht
     *      ueber diesen Weg das Kontingent des Leseschirms aufbraucht.
     *   3. Stand - hat jemand in einem anderen Tab gespeichert (Spec §7)?
     *   4. Namen - dieselbe Mindestbedingung wie beim Veroeffentlichen: eine
     *      Karte ohne jeden Namen gehoert niemandem.
     *   5. Schreiben.
     *
     * Was NICHT geschrieben wird: design_snapshot (die Vorlage friert ein,
     * Phase 3B), slug (die Adresse ist verschickt), manageKey (die eigene Tuer
     * des Paares), createdAt und paid (Buchhaltung, nicht Kundenfeld) - und
     * die Antworten der Gaeste. In dieser Methode steht kein einziger Zugriff
     * auf rsvps, und das ist Absicht (Spec §8).
     *
     * @param array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string} $einladung
     * @param array<string,mixed> $darf choices() auf dem EINGEFRORENEN Sockel
     * @return array<string,string> leer, wenn geschrieben wurde
     */
    private function saveEdit(array $einladung, array $darf): array
    {
        // is_string() faengt csrf[]=x ab: ohne die Pruefung reicht ein Feld, um
        // Security::checkCsrf() unter strict_types einen TypeError werfen zu
        // lassen - derselbe Fehler wie schon auf dem Antwortweg.
        $csrfEingabe = $_POST['csrf'] ?? null;
        if (!Security::checkCsrf(is_string($csrfEingabe) ? $csrfEingabe : null)) {
            return ['error' => 'csrf'];
        }

        $slug = (string) $einladung['slug'];
        $alt  = $einladung['data'];

        // Eigener Eimer neben v2-manage-{slug}: das Lesen der Antworten und das
        // Schreiben an der Einladung sollen einander nicht aussperren.
        if (Security::throttle('v2-edit-' . $slug, 20, 900)) {
            return ['error' => 'throttle'];
        }

        // Zwei Tabs. Der zweite Speichervorgang ueberschriebe sonst den ersten,
        // ohne dass jemand es merkt (Spec §7).
        $gesehen = Security::clean($_POST['stand'] ?? '', 40);
        if (InvitationsV2::stale($alt, $gesehen)) {
            return ['error' => 'veraltet'];
        }

        $neueAngaben = $this->sammleAngaben($darf);

        // Gefragt und leer gelassen ist erlaubt - html() laesst die Zeile dann
        // einfach weg. Nur ohne jeden Namen weiss niemand, wessen Karte das
        // ist. Dieselbe Regel wie in publish().
        $brauchtNamen = in_array('bride', $darf['fields'], true) || in_array('groom', $darf['fields'], true);
        if ($brauchtNamen && ($neueAngaben['bride'] ?? '') === '' && ($neueAngaben['groom'] ?? '') === '') {
            return ['error' => 'names'];
        }

        /*
         * Die Inhaltsschluessel werden ZUERST weggenommen und dann neu gelegt.
         *
         * Ohne das Wegnehmen waere ein geleertes Feld kein Loeschbefehl:
         * sammleAngaben() setzt families, program und sections nur, wenn etwas
         * drinsteht, und ein blosses Ueberlegen liesse den alten Wert stehen.
         * Wer eine Programmzeile loescht, will sie geloescht haben.
         *
         * Alles, was NICHT in dieser Liste steht, bleibt unangetastet - slug,
         * locale, paid, manageKey und createdAt reisen so durch, ohne dass
         * dieser Weg sie kennen muss.
         */
        $neu = $alt;
        unset($neu['families'], $neu['program'], $neu['sections']);
        foreach ($darf['fields'] as $feld) {
            unset($neu[$feld]);
        }

        $neu = array_merge($neu, $neueAngaben);

        /*
         * Das Design nur, wenn es ueberhaupt offen steht.
         *
         * Serverseitig und nicht nur im Markup: der Design-Tab fehlt bei einer
         * alten Einladung auf dem Bildschirm, aber eine von Hand gestellte
         * Anfrage traegt palette_* trotzdem. Wuerde sie hier angenommen, legte
         * sie eine Wahl auf einen Sockel, in dem eine erste Wahl schon
         * eingebrannt ist - genau der verlustbehaftete Fall aus Spec §4.
         *
         * $alt['wahl'] als drittes Argument: ein Foto, das diesmal nicht neu
         * hochgeladen wurde, behaelt seinen Pfad (sammleWahl).
         */
        if (InvitationsV2::canEditDesign($alt)) {
            $neu['wahl'] = $this->sammleWahl($darf, $slug, (array) $alt['wahl']);
        }

        // Der neue Stand fuer die naechste Zwei-Tabs-Kontrolle. Zuletzt, damit
        // er den Zustand nach dieser Aenderung beschreibt und nicht den davor.
        $neu['updatedAt'] = date('c');

        // Ausdruecklich noch einmal: was in dieser Zeile unberuehrbar ist (Spec
        // §6). Heute kann keiner der Namen aus sammleAngaben() mit ihnen
        // kollidieren - FIELD_ORDER enthaelt keinen davon. "Heute kann das
        // nicht passieren" ist aber der Satz, nach dem in Phase 3C drei Fehler
        // gefunden wurden, und diese fuenf Zeilen kosten nichts.
        $neu['slug']      = $alt['slug']      ?? $slug;
        $neu['manageKey'] = $alt['manageKey'] ?? '';
        $neu['createdAt'] = $alt['createdAt'] ?? $neu['updatedAt'];
        $neu['paid']      = $alt['paid']      ?? false;
        $neu['locale']    = $alt['locale']    ?? I18n::locale();

        InvitationsV2::saveData($slug, $neu);

        return [];
    }
```

- [ ] **Adım 4: Testleri çalıştır**

```bash
cd php && php bin/test.php
```
Beklenen: sıfır hata, kontrol sayısı Görev 5'e göre artmış (`needs_db()` doğruysa).

- [ ] **Adım 5: Elle doğrula — kaydediyor**

Düzenleme ekranını aç, gelinin adında bir harfi değiştir, **Kaydet**. Beklenen:
- `editSaved` mesajı çıkıyor.
- Alan yeni değerle dolu.
- Misafir linki (`/de/v2/einladung/<slug>`) yeni ismi gösteriyor, **adres değişmedi**.
- Aynı ekranda ikinci kez Kaydet → yine başarılı (`stand` yenilendiği için `veraltet` çıkmamalı).

- [ ] **Adım 6: Elle doğrula — iki sekme reddediliyor**

Düzenleme ekranını **iki** sekmede aç. Birincide kaydet. İkincide bir şey değiştirip kaydet. Beklenen: ikinci sekmede `errorVeraltet` mesajı ve **hiçbir değişiklik yazılmamış** olması. Doğrula:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT data FROM invitations_v2 WHERE slug = ?', ['<slug>']); \$d = json_decode(\$r['data'], true); echo \$d['bride'], ' / ', \$d['updatedAt'], PHP_EOL;"
```
Beklenen: birinci sekmenin yazdığı değer.

- [ ] **Adım 7: Elle doğrula — eski davetiyede tasarım POST'u reddediliyor**

Görev 5, Adım 7'deki `wahl`'sız davetiyeyi kullan (yoksa yeniden üret). Tarayıcı konsolundan tasarım alanı ekleyerek gönder:
```bash
curl -s -X POST "http://<yerel>/de/v2/einladung/<slug>/<manageKey>/bearbeiten" \
  -d "csrf=<gecerli>" -d "stand=<stand>" -d "bride=Test" -d "groom=Test" -d "palette_accent=%23FF0000" \
  -o /dev/null -w "%{http_code}\n"
```
Sonra veritabanında:
```bash
cd php && php -r "require 'src/bootstrap.php'; \$r = Atelier\Db::one('SELECT data FROM invitations_v2 WHERE slug = ?', ['<slug>']); echo isset(json_decode(\$r['data'], true)['wahl']) ? 'FEHLER: wahl angelegt' : 'gut: keine wahl', PHP_EOL;"
```
Beklenen: `gut: keine wahl`. CSRF token'ı elde etmek zorsa aynı doğrulamayı tarayıcının geliştirici konsolundan `fetch` ile yap — önemli olan `palette_accent`'in **sessizce yok sayılması**.

- [ ] **Adım 8: Commit**

```bash
git add php/src/Controllers/InviteV2Controller.php php/tests/invitations_v2_edit.php
git commit -m "Die Aenderung wird geschrieben, der Sockel und die Antworten bleiben"
```

---

### Görev 7: Spec §11'in ölçütlerini tek tek geç

Bu görev kod yazmaz; spec'in "bitti sayılma" listesini kanıtlar ve eksik çıkanı düzeltir.

**Files:**
- (gerekirse) düzeltmeler önceki görevlerin dosyalarında

- [ ] **Adım 1: Süit ve diff temizliği**

```bash
cd php && php bin/test.php | tail -2
git status --short
BASIS=$(cat /tmp/basis.txt)
git diff --stat $BASIS..HEAD -- php/src/Invitations.php php/src/Controllers/InviteController.php php/templates/pages/invite-wizard.php php/templates/pages/invite-manage.php
```
Beklenen: sıfır hata; çalışma ağacı temiz (`AGENTS.md`'nin `next dev` tarafından yeniden yazılan bloğu hariç — o varsa iş ile birlikte commit'lenir); **eski motor diff'inde sıfır satır** (`git diff --stat` hiçbir şey basmaz).

Şema değişmediğini ve Next.js tarafına dokunulmadığını da doğrula:
```bash
git diff $BASIS..HEAD | grep -iE "ALTER TABLE|CREATE TABLE|ADD COLUMN" || echo "kein Schemaeingriff"
git diff --name-only $BASIS..HEAD | grep -E "^(app|lib)/" || echo "Next.js unberuehrt"
```

- [ ] **Adım 2: §11 listesi, tek tek**

Yeni bir davetiye yayınla (A) ve Görev 5'ten kalan `wahl`'sız davetiyeyi (B) kullan.

| # | Ölçüt | Nasıl |
|---|---|---|
| 1 | İsim düzeltiliyor, misafir linki aynı | A'da adı değiştir, kaydet; `/de/v2/einladung/<slug>` aynı adres, yeni isim |
| 2 | Renk değişiyor, düzen değişmiyor | A'nın tasarım sekmesinde rengi değiştir, kaydet; kartın yerleşimi aynı |
| 3 | Panelde şablon değişiyor, davetiye değişmiyor | `/de/admin/designs/<slug>`'da bir katmanın konumunu kaydır, kaydet; A'nın misafir linkini yenile — **değişmemeli** |
| 4 | Eski davetiye: metin var, tasarım yok, sebep yazılı | B'nin bearbeiten ekranı: tek sekme, `editLocked` cümlesi görünür, metin kaydedilebiliyor |
| 5 | Eski davetiyenin çıktısı bu fazdan öncesiyle aynı | Adım 3 (aşağıda) |
| 6 | Yanlış anahtar 404 | `curl -o /dev/null -w "%{http_code}"` ile yanlış anahtar → `404` |
| 7 | İkinci sekme reddediliyor, sebebi yazıyor | Görev 6 Adım 6 tekrar |
| 8 | `rsvps`'e dokunulmuyor | `grep -n "rsvps" php/src/Controllers/InviteV2Controller.php` → yalnızca `saveReply()` ve `replies()` içindeki mevcut satırlar; `saveEdit()`/`edit()` gövdelerinde hiç yok |
| 9 | `php bin/test.php` geçiyor | Adım 1 |
| 10 | Eski motor diff'te geçmiyor | Adım 1 |

- [ ] **Adım 3: Bayt bayt aynılık kanıtı**

Bu fazdan **önceki** commit'te bir eski davetiyenin çıktısını al, sonra bugünküyle karşılaştır:

```bash
cd php
git status --short   # temiz olmali, yoksa asagidaki checkout is kaybettirir
BASIS=$(cat /tmp/basis.txt)

curl -s "http://<yerel>/de/v2/einladung/<B-slug>" > /tmp/nachher.html
git checkout $BASIS -- src templates public data
curl -s "http://<yerel>/de/v2/einladung/<B-slug>" > /tmp/vorher.html
git checkout HEAD -- src templates public data
git status --short   # wieder leer

diff /tmp/vorher.html /tmp/nachher.html && echo "Byte fuer Byte identisch"
```

`git checkout $BASIS -- ...` çalışma ağacını geçici olarak eski koda döndürür. **Bu adıma temiz bir ağaçla girilir** — commit edilmemiş bir değişiklik varsa bu komut onu siler. İkinci `git checkout HEAD -- ...` geri alır; sonraki `git status --short` boş çıkmalı.
Beklenen: `Byte fuer Byte identisch`. Fark çıkarsa **durdur** ve farkı incele — spec §3'ün geriye dönük uyum iddiası kırılmış demektir; `show()`'daki değişiklik gözden geçirilmeli. (Küçük yanlış pozitifler: CSRF token, `nonce`, zaman damgası. Varsa `sed` ile maskeleyip yeniden karşılaştır.)

- [ ] **Adım 4: Üretimde kaç davetiye var (spec §12)**

Spec, `wahl` taşımayan davetiyelerin sayılmasını istedi — azsa elle yeniden yayınlamak bir seçenek:
```bash
# Uretim VPS'inde
php -r "require 'src/bootstrap.php'; \$n = 0; \$alt = 0; foreach (Atelier\Db::all('SELECT data FROM invitations_v2') as \$r) { \$n++; if (!isset(json_decode(\$r['data'], true)['wahl'])) { \$alt++; } } echo \$n, ' Einladungen, davon ', \$alt, ' ohne wahl', PHP_EOL;"
```
Sayıyı rapor et. Karar (elle yeniden yayınlama) kullanıcınındır, bu planın kapsamı değildir.

- [ ] **Adım 5: Bu fazın açtığı borcu yaz**

Spec §5, `manageKey` yenilemesinin artık gerçek bir ihtiyaç olduğunu ama bu fazın kapsamında olmadığını söylüyor. `docs/superpowers/specs/2026-08-20-davetiye-v2-yayin-sonrasi-duzenleme-design.md` dosyasının sonuna ekle:

```markdown
## 14. Bu faz uygulandı — açtığı borç

Uygulama planı: `docs/superpowers/plans/2026-08-20-davetiye-v2-yayin-sonrasi-duzenleme.md`

Sonraki dilime devreden, §9'da yazılıydı ve bu faz onu **büyüttü**:

- **`manageKey` yenileme/iptal.** Anahtar artık yazma yetkisi veriyor. Yanlış
  kişiye gitmiş bir link artık sadece yanıtları okutmuyor, davetiyeyi de
  değiştirtiyor. Yenileme kendi kararı, ama artık ertelenebilir değil.
- **Davetiyeyi silme.** Düzenleme ekranı varken "sil" düğmesinin yokluğu
  görünür bir eksik hâline geldi.
```

- [ ] **Adım 6: Commit**

```bash
git add docs/superpowers/specs/2026-08-20-davetiye-v2-yayin-sonrasi-duzenleme-design.md
git commit -m "Die Phase steht: was sie geoeffnet hat, steht als Schuld in der Spec"
```

---

## Uygulayan için: en olası üç hata

1. **Formu kişiselleştirilmiş belgeden kurmak.** Görev 5'in başındaki uyarı. `personalize()` gizlenmiş katmanı **siler**; ondan sonra `choices()` onu bir daha sunmaz ve gizleme kalıcı olur. Form her zaman `design_snapshot`'tan kurulur, önizleme `personalize()`'dan çizilir.
2. **Boş alanın silmemesi.** `sammleAngaben()` boş değeri hiç yazmaz. `saveEdit()` içerik anahtarlarını **önce silmezse**, bir program satırını temizlemek imkânsız olur. Bu, kullanıcının fark etmesi aylar alacak türden bir hatadır.
3. **Uydurulan Tailwind sınıfı.** `public/assets/style.css` hazır derlenmiştir. `lg:grid-cols-[...]` gibi bir sınıf hata vermez, sadece hiçbir şey yapmaz ve düzen sessizce çöker. Yeni düzen kuralı şablonun `<style>` bloğuna yazılır — Görev 5'in şablonu bunu zaten böyle yapıyor.
