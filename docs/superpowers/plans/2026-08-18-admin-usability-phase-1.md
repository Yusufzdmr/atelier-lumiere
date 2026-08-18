# Admin panel Faz 1 — Uygulama planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Genel bakışı iş listesine çevir, kenar çubuğunu sık/nadir olarak katmanla, `?gespeichert=1` bannerını toast bileşeniyle değiştir.

**Architecture:** Üç bağımsız blok, her biri kendi başına deploy edilebilir. Toast önce gelir (Faz 2'de de kullanılacak). Sonra bekleyen iş listesi (şema değişmez — mevcut overview verilerinden üretilir + `selections` JSON'una `seenAt` alanı). En son kenar çubuğu (yeni `admin_usage` tablosu + `Admin::TABS`'a `pinned` alanı).

**Tech Stack:** PHP 8.x (strict types), MariaDB, vanilla JS (IIFE, no framework), Tailwind (CDN-derived), server-side render (`View::page`).

**Not: test suite yok.** Repo'da PHPUnit veya benzeri kurulum yok — her task manuel doğrulama + `php -l` sözdizim kontrolü + commit ile biter. Her adımın "smoke test" satırı vardır.

**Working directory:** Her komut `C:\Users\Yusuf\Documents\GitHub\atelier-lumiere` kökünden çalışır. PHP dosyaları `php/` altında.

**Deploy edilmez:** Bu plan sadece commit yapar. Deploy ayrı bir adım (VPS SSH — bkz. memory `reference_vps.md`), operatörün tercihiyle sonra yapılır.

---

## Dosya yapısı

Değişecek dosyalar:

| Dosya | Ne olur |
|---|---|
| `php/templates/admin/layout.php` | Success banner çıkar, `<div id="toast-host">` + `data-toast-*` eklenir, sidebar render'ı yenilenir |
| `php/public/assets/admin.js` | Toast bileşeni + `?gespeichert` okuyucu + `<details>` durum kalıcılığı eklenir |
| `php/src/Admin.php` | `PENDING_LABELS`, `pendingWork()`, `recordVisit()`, `pinnedTabs()` metotları; `TABS`'a `pinned:true`; `sidebar()` dönüş yapısı değişir |
| `php/src/Controllers/AdminController.php` | `overview()` `pending` geçirir |
| `php/src/Controllers/CustomerAdminController.php` | `show()` içinde `Galleries::markSelectionSeen()` çağrılır |
| `php/src/Galleries.php` | Yeni metot `markSelectionSeen()` + `selection_new` filtresi için yardımcı |
| `php/templates/admin/overview.php` | Bekleyen iş bölümü eklenir |
| `php/schema.sql` | Yeni tablo `admin_usage` |

---

## Task 1: Toast bileşeni (layout güncellemesi)

**Files:**
- Modify: `php/templates/admin/layout.php`

- [ ] **Step 1: Success banner bloğunu kaldır ve toast-host ekle**

`layout.php` içinde şu blok var (satır ~152-159):

```php
<?php if (isset($_GET['gespeichert'])) : ?>
  <div class="mb-8 flex items-center gap-3 border border-gold/50 bg-sand/40 px-5 py-3 text-[0.88rem] text-ink">
    <span class="text-gold">✓</span>
    <?= $_GET['gespeichert'] === 'geloescht'
      ? ($de ? 'Gelöscht.' : 'Silindi.')
      : ($de ? 'Gespeichert.' : 'Kaydedildi.') ?>
  </div>
<?php endif; ?>
```

Tamamen kaldır.

- [ ] **Step 2: `<body>` etiketine data-toast-* attribute'ları ekle**

Mevcut:
```php
<body class="min-h-screen bg-cream antialiased">
```

Yeni:
```php
<body class="min-h-screen bg-cream antialiased"
      data-toast-ok="<?= $de ? 'Gespeichert.' : 'Kaydedildi.' ?>"
      data-toast-deleted="<?= $de ? 'Gelöscht.' : 'Silindi.' ?>">
```

- [ ] **Step 3: Toast host div'ini `</body>`'den hemen önce, script'lerden sonra ekle**

Mevcut son satırlar:
```php
  <script src="/assets/admin.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/admin.js')) ?>" defer></script>
  <script src="/assets/upload.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/upload.js')) ?>" defer></script>
</body>
```

Yeni (script'lerin ÜSTÜNE toast host'u ekle):
```php
  <div id="toast-host" class="pointer-events-none fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>

  <script src="/assets/admin.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/admin.js')) ?>" defer></script>
  <script src="/assets/upload.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/upload.js')) ?>" defer></script>
</body>
```

- [ ] **Step 4: Sözdizim kontrolü**

Run: `php -l php/templates/admin/layout.php`
Expected: `No syntax errors detected in php/templates/admin/layout.php`

- [ ] **Step 5: Commit**

```bash
git add php/templates/admin/layout.php
git commit -m "admin: prepare layout for toast component

Remove the ?gespeichert URL banner and add a fixed toast-host container
plus data-toast-* attributes on <body>. JS in Task 2 reads these to
render the toast without inline script."
```

---

## Task 2: Toast bileşeni (JavaScript)

**Files:**
- Modify: `php/public/assets/admin.js`

- [ ] **Step 1: Toast bileşenini `admin.js` sonuna ekle**

Dosya bir IIFE ile başlıyor: `(function () { "use strict"; ...`. En son IIFE'nin kapanışından ÖNCE (yani son `})();` satırından önce) şunu ekle:

```js
  /* -------------------------- Toast -------------------------- */
  // Kısa süreli bildirim: kaydet-yenile-oku döngüsü yerine sağ alt köşede
  // üç saniye görünür. Faz 2'de AJAX cevaplarını da bu bileşen gösterecek.
  var toastHost = document.getElementById("toast-host");

  window.adminToast = function (message, kind) {
    if (!toastHost || !message) return;

    var toast = document.createElement("div");
    var isError = kind === "error";
    var isInfo = kind === "info";
    var border = isError ? "border-red-700 text-red-800 bg-red-50"
               : isInfo  ? "border-sand-deep text-muted bg-cream"
                         : "border-gold text-ink bg-sand/40";

    toast.className =
      "pointer-events-auto flex items-center gap-3 border px-5 py-3 " +
      "text-[0.88rem] shadow-sm transition-all duration-200 " +
      "translate-y-2 opacity-0 " + border;

    var mark = document.createElement("span");
    mark.textContent = isError ? "!" : "✓";
    mark.className = isError ? "text-red-700" : isInfo ? "text-muted" : "text-gold";
    toast.appendChild(mark);

    var text = document.createElement("span");
    text.textContent = message;
    toast.appendChild(text);

    toastHost.appendChild(toast);

    // Bir sonraki paint'te transition başlasın diye requestAnimationFrame.
    requestAnimationFrame(function () {
      toast.classList.remove("translate-y-2", "opacity-0");
    });

    setTimeout(function () {
      toast.classList.add("translate-y-2", "opacity-0");
      setTimeout(function () { toast.remove(); }, 220);
    }, 3000);
  };

  /* Sayfa yüklenirken ?gespeichert=... varsa toast tetikle ve URL'yi temizle. */
  (function () {
    var params = new URLSearchParams(location.search);
    if (!params.has("gespeichert")) return;

    var val = params.get("gespeichert");
    var body = document.body;
    var kind = "ok";
    var msg  = body.getAttribute("data-toast-ok") || "";

    if (val === "geloescht") {
      kind = "info";
      msg  = body.getAttribute("data-toast-deleted") || msg;
    }

    window.adminToast(msg, kind);

    // Geri tuşu tekrar toast atmasın.
    params.delete("gespeichert");
    var query = params.toString();
    history.replaceState({}, "", location.pathname + (query ? "?" + query : ""));
  })();
```

- [ ] **Step 2: Sözdizim kontrolü (tarayıcıda syntax hatası olmasın)**

Run: `node --check php/public/assets/admin.js`
Expected: Çıktı yok (başarılı) veya "OK". Hata verirse düzelt.

- [ ] **Step 3: Manuel smoke test**

Yerel geliştirme ortamı varsa:
1. Panele gir, herhangi bir metni kaydet
2. Sağ altta 3 saniye "Kaydedildi." toast'u görünmeli
3. F5 → toast tekrar görünmemeli, URL'de `?gespeichert` olmamalı
4. Console'da `adminToast('Test error', 'error')` → kırmızı toast görünmeli

Yerel ortam yoksa: bu commit'i ver, deploy sonrası doğrula.

- [ ] **Step 4: Commit**

```bash
git add php/public/assets/admin.js
git commit -m "admin: replace ?gespeichert banner with toast component

Adds window.adminToast(message, kind) and auto-triggers on page load
from the ?gespeichert query. URL is cleaned after firing so back-button
doesn't repeat the toast. Backend still redirects to ?gespeichert=1;
Task 2 changes only the UX layer."
```

---

## Task 3: Selection "görüldü" işareti — Galleries helper

**Files:**
- Modify: `php/src/Galleries.php`

- [ ] **Step 1: `markSelectionSeen()` metodunu `Galleries` sınıfına ekle**

`Galleries::saveSelection()` metodunun (satır ~171-187) altına şunu ekle:

```php
    /**
     * Seçim panelde görüldü — bekleyen iş listesinden düşer.
     *
     * `seenAt` ilk görme zamanını, `seenPickCount` o andaki kare sayısını
     * tutar. Paar sonradan bir kare daha eklerse (picks sayısı artar) tekrar
     * "yeni" sayılır.
     */
    public static function markSelectionSeen(string $code): void
    {
        $selection = self::selection($code);
        if ($selection === null) {
            return;
        }

        $selection['seenAt'] = date('c');
        $selection['seenPickCount'] = count((array) ($selection['picks'] ?? []));

        Db::run(
            'UPDATE selections SET data = ? WHERE code = ?',
            [Db::encode($selection), self::normalize($code)]
        );
    }

    /**
     * Görülmemiş veya yeni kare eklenmiş seçim mi?
     *
     * @param array<string,mixed> $selection
     */
    public static function isSelectionUnseen(array $selection): bool
    {
        if (empty($selection['seenAt'])) {
            return true;
        }
        $seen = (int) ($selection['seenPickCount'] ?? 0);
        $now  = count((array) ($selection['picks'] ?? []));
        return $now > $seen;
    }
```

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Galleries.php`
Expected: `No syntax errors detected in php/src/Galleries.php`

- [ ] **Step 3: Commit**

```bash
git add php/src/Galleries.php
git commit -m "galleries: track when an album selection was seen

Adds markSelectionSeen(code) and isSelectionUnseen(selection). Stored
inside the selection JSON (no schema change). Task 4 wires the call,
Task 5 uses the predicate in Admin::pendingWork()."
```

---

## Task 4: Selection "görüldü" — Müşteri sayfasında işaretle

**Files:**
- Modify: `php/src/Controllers/CustomerAdminController.php`

- [ ] **Step 1: `show()` metodunda seçim yüklendikten sonra işaretle**

`CustomerAdminController::show()` içinde şu satırı bul (satır ~125):

```php
        $selection = Galleries::selection($code);
```

Hemen altına ekle:

```php
        // Panele bakıldı: bekleyen iş listesinden düşer. Paar sonradan
        // kare eklerse (picks sayısı artar) tekrar "yeni" sayılır.
        if ($selection !== null && Galleries::isSelectionUnseen($selection)) {
            Galleries::markSelectionSeen($code);
            // Bellekteki kopyayı da güncelle ki template aynı isteğinde
            // eski "yeni" işareti göstermesin.
            $selection['seenAt'] = date('c');
            $selection['seenPickCount'] = count((array) ($selection['picks'] ?? []));
        }
```

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Controllers/CustomerAdminController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add php/src/Controllers/CustomerAdminController.php
git commit -m "customer admin: mark selection as seen when opening the case

Opening the customer detail page counts as seeing the selection. Task 5
will use this to filter the pending-work list on the overview page."
```

---

## Task 5: Bekleyen iş listesi — Admin::pendingWork()

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: `PENDING_LABELS` sabitini `TABS`'ın altına ekle**

`Admin.php` içinde `TABS` sabitinden hemen sonra (satır ~62'nin sonrasına), `sidebar()` metodundan ÖNCE, şunu ekle:

```php
    /**
     * Bekleyen iş satırlarının şablonları.
     *
     * `%d` sayı ile, `%s` metin ile doldurulur. Tek satırlık mesajlar; ekstra
     * detay `href` üzerinden — kart tıklandığında tam bağlama gider.
     *
     * @var array<string,array{de:string,tr:string}>
     */
    private const PENDING_LABELS = [
        'lead_stale' => [
            'de' => '%d Anfrage(n) älter als 48 Stunden ohne Antwort',
            'tr' => '%d talep 48 saatten uzun cevapsız',
        ],
        'invitation_unpaid' => [
            'de' => '%d Einladung(en) seit über 7 Tagen unbezahlt',
            'tr' => '%d davetiye 7 günden uzun ödenmemiş',
        ],
        'selection_new' => [
            'de' => '%d neue Albumauswahl(en) noch nicht angesehen',
            'tr' => '%d yeni albüm seçimi henüz görülmedi',
        ],
        'wedding_empty' => [
            'de' => '%s in %d Tagen — Galerie noch leer',
            'tr' => '%s %d gün sonra — galeri hâlâ boş',
        ],
    ];
```

- [ ] **Step 2: `pendingWork()` metodunu `PENDING_LABELS`'ın altına ekle**

```php
    /**
     * Bekleyen iş satırları — overview şablonu için.
     *
     * Ekstra DB round-trip yok: overview() zaten leads/selections/invitations/
     * customers dizilerini yükledi, aynı verilerden filtreliyoruz.
     *
     * @param list<array<string,mixed>> $leads
     * @param list<array<string,mixed>> $selections
     * @param list<array<string,mixed>> $invitations
     * @param list<array<string,mixed>> $customers
     * @param list<array<string,mixed>> $galleries
     * @return list<array{kind:string,message:string,href:string,severity:string}>
     */
    public static function pendingWork(
        string $locale,
        array $leads,
        array $selections,
        array $invitations,
        array $customers,
        array $galleries
    ): array {
        $out = [];
        $de = $locale === 'de';
        $label = static function (string $kind) use ($locale): string {
            $row = self::PENDING_LABELS[$kind] ?? [];
            return (string) ($row[$locale] ?? $row['de'] ?? '');
        };

        // Cevapsız talepler (48 saatten eski)
        $limit48h = date('c', strtotime('-48 hours'));
        $stale = 0;
        foreach ($leads as $lead) {
            if ((string) ($lead['at'] ?? '') !== '' && (string) $lead['at'] < $limit48h) {
                $stale++;
            }
        }
        if ($stale > 0) {
            $out[] = [
                'kind'     => 'lead_stale',
                'message'  => sprintf($label('lead_stale'), $stale),
                'href'     => '#anfragen',
                'severity' => 'warn',
            ];
        }

        // Ödenmemiş davetiyeler (7 günden eski)
        // createdAt ISO 8601 (date('c')) formatında saklanıyor — aynı formatta karşılaştır.
        $limit7d = date('c', strtotime('-7 days'));
        $unpaid = 0;
        foreach ($invitations as $inv) {
            $created = (string) ($inv['createdAt'] ?? '');
            if (empty($inv['paid']) && $created !== '' && $created < $limit7d) {
                $unpaid++;
            }
        }
        if ($unpaid > 0) {
            $out[] = [
                'kind'     => 'invitation_unpaid',
                'message'  => sprintf($label('invitation_unpaid'), $unpaid),
                'href'     => I18n::path('/admin/einladungen', $locale),
                'severity' => 'warn',
            ];
        }

        // Yeni gelen albüm seçimleri
        $unseen = 0;
        foreach ($selections as $sel) {
            if (Galleries::isSelectionUnseen($sel)) {
                $unseen++;
            }
        }
        if ($unseen > 0) {
            $out[] = [
                'kind'     => 'selection_new',
                'message'  => sprintf($label('selection_new'), $unseen),
                'href'     => '#auswahlen',
                'severity' => 'info',
            ];
        }

        // Yaklaşan düğün + boş galeri (önümüzdeki 7 gün)
        $photosByCode = [];
        foreach ($galleries as $g) {
            $code = (string) ($g['code'] ?? '');
            $photosByCode[$code] = count((array) ($g['uploads'] ?? [])) + count((array) ($g['seeds'] ?? []));
        }
        $today = date('Y-m-d');
        $in7d  = date('Y-m-d', strtotime('+7 days'));
        foreach ($customers as $c) {
            $date = (string) ($c['date'] ?? '');
            $code = (string) ($c['code'] ?? '');
            if ($date < $today || $date > $in7d || $code === '') {
                continue;
            }
            if (($photosByCode[$code] ?? 0) > 0) {
                continue;
            }
            $days = max(0, (int) ((strtotime($date) - strtotime($today)) / 86400));
            $couple = (string) ($c['couple'] ?? $code);
            $out[] = [
                'kind'     => 'wedding_empty',
                'message'  => sprintf($label('wedding_empty'), $couple, $days),
                'href'     => I18n::path('/admin/kunden/' . $code, $locale),
                'severity' => 'warn',
            ];
        }

        // Sekiz satırdan fazlasını gösterme.
        return array_slice($out, 0, 8);
    }
```

- [ ] **Step 3: `Galleries` sınıfını import et**

Dosyanın en üstünde (satır 3-5 civarı):
```php
namespace Atelier;
```
Halihazırda aynı namespace'te — import gerekmez, doğrudan `Galleries::` çağırabilir.

- [ ] **Step 4: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: compute pending work items for the overview

Adds PENDING_LABELS and pendingWork() — filters existing overview data
(leads/selections/invitations/customers/galleries) into a short list of
actionable rows. No extra DB queries; the template renders the section
in Task 6."
```

---

## Task 6: Overview şablonunda bekleyen iş bölümü

**Files:**
- Modify: `php/src/Controllers/AdminController.php`
- Modify: `php/templates/admin/overview.php`

- [ ] **Step 1: `AdminController::overview()`'a `pending` geç**

Mevcut `overview()` metodunu (satır 38-54) düzenle. Şu satırdan sonra:
```php
        $customers = Db::jsonList('SELECT data FROM customers ORDER BY created_at DESC');
```

Ekle:
```php
        $pending = Admin::pendingWork(
            $this->locale,
            Leads::all(30),
            $selections,
            $invitations,
            $customers,
            $galleries
        );
```

Sonra `render()` çağrısına `pending` ekle:
```php
        $this->render('admin/overview', '', [
            'leads'       => Leads::all(30),
            'selections'  => $selections,
            'galleries'   => $galleries,
            'invitations' => $invitations,
            'rsvps'       => $rsvps,
            'customers'   => $customers,
            'pending'     => $pending,
        ]);
```

**Not:** `Leads::all(30)` iki kere çağrılıyor. Bunu tek değişkene al:

```php
        $leads = Leads::all(30);
        $pending = Admin::pendingWork(
            $this->locale,
            $leads,
            $selections,
            $invitations,
            $customers,
            $galleries
        );

        $this->render('admin/overview', '', [
            'leads'       => $leads,
            'selections'  => $selections,
            'galleries'   => $galleries,
            'invitations' => $invitations,
            'rsvps'       => $rsvps,
            'customers'   => $customers,
            'pending'     => $pending,
        ]);
```

- [ ] **Step 2: Sözdizim kontrolü**

Run: `php -l php/src/Controllers/AdminController.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: `overview.php` başına bekleyen iş bölümü ekle ve `@var` bloğuna ekle**

`overview.php` en üstündeki docblock'a satır ekle (mevcut `@var` satırları arasına):

```php
 * @var list<array{kind:string,message:string,href:string,severity:string}> $pending
```

Sonra render bloğunun içinde, ilk `<div class="space-y-12">` açılışının hemen altına — mevcut "Übersicht" başlığı bloğundan ÖNCE — şunu ekle:

```php
  <?php /* -------------------- Bekleyen iş -------------------- */ ?>
  <?php if ($pending !== []) : ?>
    <section aria-label="<?= $de ? 'Ausstehend' : 'Bekleyen' ?>">
      <h2 class="font-display text-xl text-ink">
        <?= $de ? 'Was gerade wartet' : 'Bekleyen işler' ?>
      </h2>
      <div class="mt-4 space-y-2">
        <?php foreach ($pending as $item) : ?>
          <?php
            $tone = $item['severity'] === 'warn'
              ? 'border-l-red-700 hover:border-l-red-800'
              : 'border-l-gold hover:border-l-gold';
          ?>
          <a href="<?= e($item['href']) ?>"
             class="flex items-center justify-between gap-4 border-l-2 border border-sand-deep bg-cream px-5 py-3 text-[0.9rem] text-ink transition-colors hover:bg-sand/30 <?= $tone ?>">
            <span><?= e($item['message']) ?></span>
            <span class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">
              <?= $de ? 'öffnen' : 'aç' ?> →
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
```

- [ ] **Step 4: Sözdizim kontrolü**

Run: `php -l php/templates/admin/overview.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Manuel smoke test**

Yerel/staging ortamda:
1. Test verisi olmadan panele git → yeni "Bekleyen işler" bölümü gizli olmalı, sayfa eskisi gibi görünmeli
2. DB'ye eski bir talep ekle:
   ```sql
   INSERT INTO leads (data, at) VALUES ('{"name":"Test","email":"a@b.c","message":"x","at":"2026-08-15T10:00:00+00:00"}', '2026-08-15 10:00:00');
   ```
3. Sayfayı yenile → "1 talep 48 saatten uzun cevapsız" satırı üstte görünmeli, tıkla → `#anfragen` bölümüne kaydırmalı
4. Bir müşteri detayı aç → seçim varsa "seenAt" set olmalı, overview'a dön → "yeni albüm seçimi" satırı azalmalı

- [ ] **Step 6: Commit**

```bash
git add php/src/Controllers/AdminController.php php/templates/admin/overview.php
git commit -m "admin: render pending work section on overview

Overview now leads with what needs attention today (stale leads, unpaid
invitations, unseen selections, upcoming weddings with empty galleries).
Existing tile grid and quick actions remain below."
```

---

## Task 7: Yeni DB tablosu — admin_usage

**Files:**
- Modify: `php/schema.sql`

- [ ] **Step 1: `schema.sql`'in sonuna yeni tabloyu ekle**

Dosyanın en sonuna (son `) ENGINE=InnoDB ...` satırından sonra) ekle:

```sql

-- Panelin hangi sekmelerinde çalışıldığını sayar.
-- „Sık kullanılanlar" listesini (son 30 gün) buradan üretiriz.
CREATE TABLE IF NOT EXISTS admin_usage (
  tab      VARCHAR(64) NOT NULL PRIMARY KEY,
  hits     INT UNSIGNED NOT NULL DEFAULT 0,
  last_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Yerel DB'de tabloyu oluştur (varsa)**

Yerel geliştirme DB'si varsa:
```bash
mysql -h <host> -u <user> -p <db> < php/schema.sql
```

`IF NOT EXISTS` ile diğer tablolar korunur. Yerel DB yoksa: deploy anında çalıştırılır, bu adım atlanır.

- [ ] **Step 3: Commit**

```bash
git add php/schema.sql
git commit -m "schema: add admin_usage table for tab visit counter

Tracks which admin tabs are opened. Task 8 will pin the most-used tabs
in the sidebar. Deploy step: run mysql < schema.sql once (idempotent
due to IF NOT EXISTS)."
```

---

## Task 8: TABS'a pinned alanı + recordVisit()

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: `TABS` sabitinde varsayılan pinned sekmelere `pinned:true` ekle**

`Admin.php` içinde `TABS` sabitini bul (satır ~37-62). Şu altı satırda `'pinned' => true` alanı ekle:

```php
        ['href' => '/inhalte', 'group' => 'website', 'de' => 'Texte & Kontakt', 'tr' => 'Metinler & iletişim', 'pinned' => true],
        ['href' => '/bilder', 'group' => 'website', 'de' => 'Bilder', 'tr' => 'Görseller', 'pinned' => true],
        ['href' => '/portfolio', 'group' => 'website', 'de' => 'Portfolio', 'tr' => 'Portfolyo', 'pinned' => true],
        ['href' => '/ratgeber', 'group' => 'website', 'de' => 'Ratgeber', 'tr' => 'Rehber', 'pinned' => true],

        // Kunden & Galerien
        ['href' => '/kunden', 'group' => 'galerie', 'de' => 'Kunden & Galerien', 'tr' => 'Müşteriler & galeriler', 'pinned' => true],

        ['href' => '/einladungen', 'group' => 'einladung', 'de' => 'Einladungen', 'tr' => 'Davetiyeler', 'pinned' => true],
```

Diğer satırlar aynen kalır (pinned alanı yoksa `false` kabul edilir).

- [ ] **Step 2: `recordVisit()` metodunu `Admin` sınıfına ekle**

`PENDING_LABELS`'ın altına, `pendingWork()`'ün üstüne veya altına ekle — namespace içinde:

```php
    /**
     * Bir sekmenin ziyaret sayacını artırır. Panele her GET isteğinde çağrılır.
     *
     * @param string $tab örn. "" (overview), "/kunden", "/portfolio"
     */
    public static function recordVisit(string $tab): void
    {
        // Sadece TABS'ta olan sekmeleri say. Alt sayfalar (/kunden/{code}) üst
        // sekmeye ("/kunden") yuvarlanır — sayaç kısmen daha temiz olur.
        $canonical = null;
        foreach (self::TABS as $t) {
            $href = (string) $t['href'];
            if ($href === $tab || ($href !== '' && str_starts_with($tab, $href . '/'))) {
                $canonical = $href;
                break;
            }
        }
        if ($canonical === null) {
            return;
        }

        // ON DUPLICATE KEY: atomik, yarış koşulu yok.
        try {
            Db::run(
                'INSERT INTO admin_usage (tab, hits) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE hits = hits + 1, last_at = CURRENT_TIMESTAMP',
                [$canonical]
            );
        } catch (\Throwable $_) {
            // Tablo yoksa (henüz schema yüklenmedi) veya DB düştüyse:
            // panel çalışmaya devam etsin — sayaç kritik değil.
        }
    }
```

- [ ] **Step 3: `Admin::requireLogin()` içinden GET isteklerinde çağır**

Mevcut `requireLogin()` metodu (satır ~171-203) `isLoggedIn()` `true` dönerse hemen `return` ediyor. `return`'den ÖNCE ziyaret sayacını artır:

Mevcut:
```php
    public static function requireLogin(string $locale): void
    {
        if (self::isLoggedIn()) {
            return;
        }
```

Yeni:
```php
    public static function requireLogin(string $locale): void
    {
        if (self::isLoggedIn()) {
            // GET isteklerinde ziyaret sayacını artır — POST'lar redirect
            // sonrası GET olarak gelir, çift sayım olmaz.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
                // "/tr/admin/kunden" → "/kunden", "/de/admin" → ""
                $tab = preg_replace('#^/[a-z]{2}/admin#', '', $path) ?? '';
                self::recordVisit($tab);
            }
            return;
        }
```

- [ ] **Step 4: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: count tab visits + mark default pinned tabs

Adds Admin::recordVisit() called from requireLogin() on GET only.
Subpaths like /kunden/{code} roll up to /kunden. Six frequently-used
tabs get pinned:true as the fallback until the counter has data."
```

---

## Task 9: pinnedTabs() + sidebar() yeniden şekilleniyor

**Files:**
- Modify: `php/src/Admin.php`

- [ ] **Step 1: `pinnedTabs()` metodunu ekle**

`Admin.php` içinde `recordVisit()`'in altına ekle:

```php
    /**
     * Kenar çubuğu için „sık kullanılanlar" — en çok en fazla 6 sekme.
     *
     * Kullanım verisi yeterliyse (son 30 günde ≥3 farklı sekme) sayaçtan;
     * yeterli değilse TABS içindeki pinned:true alanından.
     *
     * @return list<array{href:string,label:string,active:bool}>
     */
    public static function pinnedTabs(string $locale, string $current, int $count = 6): array
    {
        $hrefs = [];

        try {
            $rows = Db::all(
                'SELECT tab FROM admin_usage
                 WHERE last_at > (NOW() - INTERVAL 30 DAY)
                 ORDER BY hits DESC LIMIT ' . max(1, min(12, $count))
            );
            if (count($rows) >= 3) {
                foreach ($rows as $row) {
                    $hrefs[] = (string) $row['tab'];
                }
            }
        } catch (\Throwable $_) {
            // Tablo yoksa varsayılana düşer.
        }

        if ($hrefs === []) {
            foreach (self::TABS as $t) {
                if (!empty($t['pinned'])) {
                    $hrefs[] = (string) $t['href'];
                }
            }
        }

        $out = [];
        foreach ($hrefs as $href) {
            foreach (self::TABS as $t) {
                if ((string) $t['href'] === $href) {
                    $out[] = [
                        'href'   => I18n::path('/admin' . $href, $locale),
                        'label'  => (string) ($t[$locale] ?? $t['de']),
                        'active' => $current === $href,
                    ];
                    break;
                }
            }
            if (count($out) >= $count) {
                break;
            }
        }

        return $out;
    }
```

**Not:** `Db::rows()` metodu var mı diye kontrol et. Yoksa `Db::jsonList` deseninden yola çıkarak muhtemelen `Db::rows` veya benzeri var. Yoksa bu satırı `Db::run` + PDO fetch ile değiştirmen gerekebilir. Kontrol:

Run: `grep -n "public static function" php/src/Db.php`

Eğer `rows()` metodu yoksa, mevcut bir metot bul (örn. `fetchAll`, `all`) ve onun ismini kullan; ya da `Db::json` deseninden PDO'ya erişim var mı bak.

- [ ] **Step 2: `sidebar()` metodunu yeni dönüş yapısına güncelle**

Mevcut `sidebar()` metodunu (satır 69-87) tamamen değiştir:

```php
    /**
     * Kenar çubuğu içeriği: sık kullanılanlar (düz liste) ve „daha fazla"
     * altında gruplu geri kalan sekmeler.
     *
     * @return array{
     *   pinned: list<array{href:string,label:string,active:bool}>,
     *   more:   list<array{key:string,label:string,tabs:list<array{href:string,label:string,active:bool}>}>
     * }
     */
    public static function sidebar(string $locale, string $current): array
    {
        $pinned = self::pinnedTabs($locale, $current);
        $pinnedHrefs = array_map(static fn (array $t): string => $t['href'], $pinned);

        // "more" bölümü: pinned'de OLMAYAN ve grubu olan sekmeler.
        // Overview grup boş — o pinned'in üstünde ayrı render edilir.
        $more = [];
        foreach (self::TABS as $tab) {
            $group = (string) $tab['group'];
            if ($group === '') {
                continue;
            }
            $rendered = [
                'href'   => I18n::path('/admin' . $tab['href'], $locale),
                'label'  => (string) ($tab[$locale] ?? $tab['de']),
                'active' => $current === $tab['href'],
            ];
            if (in_array($rendered['href'], $pinnedHrefs, true)) {
                continue;
            }
            $more[$group]['label'] = self::GROUPS[$group][$locale] ?? self::GROUPS[$group]['de'];
            $more[$group]['key']   = $group;
            $more[$group]['tabs'][] = $rendered;
        }

        return [
            'pinned' => $pinned,
            'more'   => array_values($more),
        ];
    }
```

- [ ] **Step 3: Sözdizim kontrolü**

Run: `php -l php/src/Admin.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add php/src/Admin.php
git commit -m "admin: sidebar returns pinned + more, from usage counter

sidebar() now returns { pinned: [...], more: [group => tabs] }. Pinned
list comes from admin_usage (top 6 in last 30 days) with fallback to
TABS[].pinned. Template rewrite in Task 10."
```

---

## Task 10: Layout — yeni kenar çubuğu render'ı + <details> kalıcılığı

**Files:**
- Modify: `php/templates/admin/layout.php`
- Modify: `php/public/assets/admin.js`

- [ ] **Step 1: `layout.php` içindeki sidebar bloklarını yenile**

Mevcut sidebar oluşturma satırlarını bul (satır ~23):
```php
$sections = $nav ? Admin::sidebar($locale, $current) : [];
```

Değiştirme yok — aynı çağrı, dönüş yapısı değişti. Ama render kısımları değişecek.

**Overview linkini ayrı hazırla** — `$link` closure'ın altına ekle:

```php
$overviewTab = static function () use ($locale, $current, $link) {
    return $link([
        'href'   => I18n::path('/admin', $locale),
        'label'  => $locale === 'de' ? 'Übersicht' : 'Genel bakış',
        'active' => $current === '',
    ]);
};
```

- [ ] **Step 2: Mobil (details) navigasyonunu güncelle**

Şu bloku (satır ~112-123) bul:
```php
<nav class="mt-4 pb-3 sm:columns-2 sm:gap-x-8">
  <?php foreach ($sections as $section) : ?>
    <div class="mb-6 break-inside-avoid">
      ...
    </div>
  <?php endforeach; ?>
</nav>
```

Değiştir:
```php
<nav class="mt-4 pb-3 sm:columns-2 sm:gap-x-8">
  <?php /* Overview + sık kullanılanlar önce */ ?>
  <div class="mb-6 break-inside-avoid">
    <div class="mb-2 text-[0.58rem] uppercase tracking-[0.2em] text-muted">
      <?= $de ? 'Häufig' : 'Sık kullanılan' ?>
    </div>
    <?= $overviewTab() ?>
    <?php foreach ($sections['pinned'] as $tab) : ?>
      <?= $link($tab) ?>
    <?php endforeach; ?>
  </div>

  <?php foreach ($sections['more'] as $section) : ?>
    <div class="mb-6 break-inside-avoid">
      <?php if ($section['label'] !== '') : ?>
        <div class="mb-2 text-[0.58rem] uppercase tracking-[0.2em] text-muted"><?= e($section['label']) ?></div>
      <?php endif; ?>
      <?php foreach ($section['tabs'] as $tab) : ?>
        <?= $link($tab) ?>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</nav>
```

- [ ] **Step 3: Desktop kenar çubuğunu güncelle — sık + <details>**

Şu bloku (satır ~130-141) bul:
```php
<nav class="hidden lg:sticky lg:top-[3.9rem] lg:block lg:max-h-[calc(100vh-5rem)] lg:overflow-y-auto lg:py-7">
  <?php foreach ($sections as $section) : ?>
    ...
  <?php endforeach; ?>
</nav>
```

Değiştir:
```php
<nav class="hidden lg:sticky lg:top-[3.9rem] lg:block lg:max-h-[calc(100vh-5rem)] lg:overflow-y-auto lg:py-7">
  <?php /* Overview (sabit üst) */ ?>
  <div class="mb-4">
    <?= $overviewTab() ?>
  </div>

  <?php /* Sık kullanılanlar */ ?>
  <div class="mb-5">
    <div class="mb-1.5 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted">
      <?= $de ? 'Häufig' : 'Sık kullanılan' ?>
    </div>
    <?php foreach ($sections['pinned'] as $tab) : ?>
      <?= $link($tab) ?>
    <?php endforeach; ?>
  </div>

  <?php /* Daha fazla — açılır, localStorage'da durum kalır */ ?>
  <details id="admin-more" class="group">
    <summary class="mb-3 flex cursor-pointer items-center gap-2 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted hover:text-ink">
      <span><?= $de ? 'Mehr' : 'Daha fazla' ?></span>
      <span class="text-gold transition-transform group-open:rotate-90">›</span>
    </summary>
    <?php foreach ($sections['more'] as $section) : ?>
      <div class="mb-5">
        <?php if ($section['label'] !== '') : ?>
          <div class="mb-1.5 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted"><?= e($section['label']) ?></div>
        <?php endif; ?>
        <?php foreach ($section['tabs'] as $tab) : ?>
          <?= $link($tab) ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </details>
</nav>
```

- [ ] **Step 4: `admin.js` — details açık/kapalı durumunu localStorage'da tut**

`admin.js` içinde IIFE'nin kapanışından ÖNCE (toast bileşeninin altına) ekle:

```js
  /* --------------------- Sidebar „daha fazla" hafıza --------------------- */
  var more = document.getElementById("admin-more");
  if (more) {
    if (localStorage.getItem("admin.moreOpen") === "1") {
      more.open = true;
    }
    more.addEventListener("toggle", function () {
      if (more.open) {
        localStorage.setItem("admin.moreOpen", "1");
      } else {
        localStorage.removeItem("admin.moreOpen");
      }
    });
  }
```

- [ ] **Step 5: Sözdizim kontrolü**

Run: `php -l php/templates/admin/layout.php`
Expected: `No syntax errors detected`

Run: `node --check php/public/assets/admin.js`
Expected: Hata yok

- [ ] **Step 6: Manuel smoke test**

Yerel/staging:
1. Panele gir → sol kenarda "Sık kullanılan" bölümü var, altında "Daha fazla ›" kapalı
2. "Daha fazla" → tıkla, sekmeler açılır. Başka sekmeye git → hâlâ açık (localStorage sayesinde)
3. `/admin/portfolio` sekmesine 3-4 kere gir → orada zaten pinned, sıralaması sayaçla değişebilir
4. `admin_usage` tablosunda satırların oluştuğunu doğrula:
   ```sql
   SELECT * FROM admin_usage ORDER BY hits DESC;
   ```
5. Mobil görünümde (dev tools mobil emulasyonu) `<details>` açılır menüsü hâlâ çalışıyor mu?
6. `active` sekme (üstünde bulunduğun) altın kenarlıklı hâlâ görünüyor mu?

- [ ] **Step 7: Commit**

```bash
git add php/templates/admin/layout.php php/public/assets/admin.js
git commit -m "admin: render pinned + collapsible more in sidebar

Desktop sidebar shows Overview + Häufig section on top and puts the
rest inside a <details> block that remembers its state via
localStorage. Mobile keeps the columned <details> layout but with the
same pinned/more split."
```

---

## Manuel doğrulama tam listesi (deploy sonrası)

Deploy edilen ortamda tek seferde:

- [ ] Panele gir, sol üstte "Genel bakış" görünüyor
- [ ] Altında "Sık kullanılan" 6 sekme
- [ ] "Daha fazla ›" tıklanınca açılır, tekrar tıklanınca kapanır
- [ ] Yeni sekmeye git → "Daha fazla" hâlâ açık
- [ ] Bekleyen iş varsa üstte kırmızı/altın kenarlı satırlar
- [ ] Boşsa bölüm hiç görünmemeli
- [ ] Bir metin kaydet → sağ alt köşe "Kaydedildi." 3 sn görünür
- [ ] F5 → toast yok, URL temiz
- [ ] Bir müşteri detayına gir → seçim "yeni" bildirimi overview'da azalır
- [ ] `SELECT * FROM admin_usage;` → farklı sekme ziyaretleri toplanmış
- [ ] Sonraki 30+ ziyaretten sonra "Sık kullanılan" listesi kullanım paternine göre yeniden sıralanır
- [ ] TR + DE locale ikisinde de tüm başlıklar doğru dil

---

## Deploy adımı (bu planın kapsamı DIŞINDA — operatör yapar)

1. `git push` (VPS'e commit'leri aktar)
2. VPS'te: `mysql -h ... -u ... -p <db> < php/schema.sql` (yeni `admin_usage` tablosu oluşur, mevcut tablolar `IF NOT EXISTS` ile korunur)
3. Ana repo deploy yolunu izle (bkz. memory `reference_vps.md`)

Geri alma: önceki commit'e dön. `admin_usage` tablosu boşta kalır — zararsız.
