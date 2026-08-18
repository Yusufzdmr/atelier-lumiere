# Admin panel — Faz 1: Bilgi mimarisi

**Tarih:** 2026-08-18
**Kapsam:** Sadece `php/` — Next.js tarafı bu spec dışında.
**Faz:** 1 / 3 (bkz. sondaki "Sonraki fazlar")

## Amaç

Panele girer girmez sıradaki iş görünmeli. Şu an genel bakış "kaç şey var" diyor; hangi işin bekletildiğini söylemiyor. 18 sekmenin çoğu ayda bir açılıyor ama hepsi eşit yer kaplıyor. Kaydet sonrası `?gespeichert=1` URL parametresi ile sayfa yenileniyor — 2010 hissi.

Bu faz üç şeyi yapıyor: genel bakışı **iş listesine** çevirmek, kenar çubuğunu **sık/nadir** olarak katmanlamak, ve `?gespeichert=1` yerine **toast bildirimi** getirmek. Faz 2 (arama + AJAX) ve Faz 3 (bonus özellikler) için zemin de bu faz kuruyor: toast bileşeni Faz 2'de AJAX cevaplarını göstermek için lazım.

## Kapsam dışı

- Global arama (Faz 2)
- Klavye kısayolları (Faz 2)
- AJAX form kaydetme (Faz 2)
- Yan yana önizleme, mobil davetiye editörü, toplu işlemler (Faz 3)

---

## 1. Genel bakış → İş listesi

### Davranış

Sayfa üstünde yeni bir **"Bekleyen iş"** bölümü. Her satır tıklanabilir ve doğrudan işi yapılabilecek yere götürür. Bekleyen iş yoksa bölüm gizlenir (boş kutu yok — huzur ver).

Satır tipleri:

| Tip | Koşul | Bağlantı |
|---|---|---|
| `lead_stale` | `leads` tablosunda `at < now - 48h` olan talep | `#anfragen` bölümüne kaydırır |
| `invitation_unpaid` | `invitations.paid = false && at < now - 7d` | `/admin/einladungen` |
| `selection_new` | `selections.picks` var + operatörün henüz görmediği | `#auswahlen` |
| `wedding_empty` | `customers.date < now + 7d && photos = 0` | `/admin/kunden/{code}` |

Her satır: ikon + kısa açıklama (i18n) + tarih/detay + bağlantı. En fazla ilk 8 satır; fazlası "ve {N} tane daha…" ile bitirilir.

Mevcut 6'lı sayı-grid kutuları **kalır**, "Bekleyen iş"in altında. Sık kullanılan butonlar (Müşteri oluştur vb.) da aynen kalır.

### Backend

Yeni statik metot: `Admin::pendingWork(string $locale): array` — `overview.php`'ye zaten geçen `$leads`, `$selections`, `$invitations`, `$customers` dizilerini alır ve satır listesi döner. Ekstra sorgu yok, mevcut verilerden üretilir.

Dönen yapı:
```php
[
  [
    'kind'    => 'lead_stale',
    'message' => '3 talep 48 saatten uzun cevapsız',
    'href'    => '#anfragen',
    'severity'=> 'warn',   // warn | info
  ],
  ...
]
```

Controller: `AdminController::overview()` şu an ne veriyorsa aynısını verir + `pending`. Template `pending` boşsa bölümü render etmez.

### "Görüldü" işareti — `selection_new` için

Bu sinyalin "görüldü" durumunu tutmak lazım — yoksa liste hep aynı seçimleri gösterir. `selections` tablosu JSON-doküman kalıbında (`data LONGTEXT`), yani şema değişikliği yok: JSON'un içine `seenAt: ?string` alanı eklenir. Kunden detay sayfası açıldığında (seçim orada gösteriliyor) `Galleries::markSelectionSeen(string $code): void` çağrılır — mevcut `Db::run('UPDATE selections SET data = ...')` deseni.

`Admin::pendingWork()` içindeki `selection_new` filtresi: `data.seenAt` yoksa veya `data.picks` sayısı `data.seenPickCount`'tan büyükse gösterilir (paare yeni kare ekleyebilir — o zaman tekrar bildirim).

Diğer üç tip zaman-tabanlı, "görüldü" gerektirmez — talep cevaplandığında zaten silinmez ama 48h penceresi kayar; sen operatörü bir kere uyardıysa, cevabı yazana kadar uyarmaya devam etmesi doğru davranış.

### Kaynak veriler

Zaten `overview()` controller'ında olan sorgulardan gelir — ekstra DB round-trip yok:

- `leads` tablosu → `at` kolonundan yaş hesabı
- `invitations` tablosu → JSON'un `paid` alanı + tablonun `created_at` kolonu
- `selections` tablosu → yukarıdaki `seenAt` mantığı
- `customers` tablosu → JSON'un `date` (düğün tarihi) + `galleries.data.photos` sayısı

### i18n

Mesajlar `de` + `tr`. `Admin.php` içindeki mevcut sabit dizin desenine uyar:
```php
private const PENDING_LABELS = [
  'lead_stale' => [
    'de' => '%d Anfragen älter als 48 Stunden ohne Antwort',
    'tr' => '%d talep 48 saatten uzun cevapsız',
  ],
  ...
];
```

---

## 2. Kenar çubuğu — sık/nadir katmanlaması

### Davranış

Şu anki 4 grup (Website / Galerie / Einladung / System) çok geniş. Yeni yapı:

```
Genel bakış                          [sabit üst]

SIK KULLANILANLAR
  <en çok 6 sekme, kullanım sayacından>

Daha fazla ▾                         [<details> ile açılır]
  Site
    <geri kalan Website sekmeleri>
  Davetiye
    Tasarımlar
  Sistem
    Entegrasyonlar
    Yayın kontrolü
```

"Sık kullanılanlar" listesi statik değil — son 30 gün içinde en çok açılan sekmelerden üretilir. İlk kurulumda (kullanım verisi yokken) `Admin::TABS` içindeki `pinned: true` alanlı sekmeler gösterilir.

**Varsayılan sabitlemeler** (`pinned: true`):
- Kunden & Galerien
- Einladungen
- Bilder
- Portfolio
- Texte & Kontakt
- Ratgeber

### Kullanım sayacı

Yeni tablo — repodaki JSON-doküman kalıbının basit varyantı:

```sql
CREATE TABLE IF NOT EXISTS admin_usage (
  tab      VARCHAR(64) NOT NULL PRIMARY KEY,
  hits     INT UNSIGNED NOT NULL DEFAULT 0,
  last_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

En fazla ~18 satır olur (sekme sayısı). JSON'a gerek yok — düz sayaç.

Yeni statik metot: `Admin::recordVisit(string $tab): void` — her admin sayfa render'ından önce `AdminController` içinde tek yerde çağrılır. Tek `INSERT ... ON DUPLICATE KEY UPDATE hits = hits + 1` sorgusu, atomik.

Yeni metot: `Admin::pinnedTabs(string $locale, int $count = 6): array` — `SELECT tab FROM admin_usage WHERE last_at > NOW() - INTERVAL 30 DAY ORDER BY hits DESC LIMIT ?`. Dönen `tab` listelerini `TABS`'la birleştirir.

Eşik: sayaç 3+ farklı sekme için veri toplayana kadar `TABS`'daki `pinned: true` varsayılan listesi kullanılır.

Schema deploy: `schema.sql`'e eklenir. Mevcut kurulumlarda ilk `Admin::recordVisit()` çağrısı tablo yoksa hata verir → deploy sırasında bir kere manuel `mysql < schema.sql` (mevcut tablolar `IF NOT EXISTS` sayesinde korunur, sadece yeni tablo oluşur).

### `<details>` ile "daha fazla"

Şu anki mobil görünüm zaten `<details>` kullanıyor — aynı öge desktop kenar çubuğunda da çalışır. Açık/kapalı durumu `localStorage`'da tutulur (`admin.moreOpen`), sayfa değişiminde açık kalır.

### `Admin.php` değişikliği

`TABS` her satırına opsiyonel `pinned: true` alanı. `sidebar()` metodu iki liste döner:
```php
[
  'pinned' => [ ... ],   // düz liste, gruplar yok
  'more'   => [          // gruplar var
    ['key' => 'website',   'label' => 'Site',      'tabs' => [...]],
    ['key' => 'einladung', 'label' => 'Davetiye',  'tabs' => [...]],
    ['key' => 'technik',   'label' => 'Sistem',    'tabs' => [...]],
  ],
]
```

"Genel bakış" özel — hiçbir gruba dahil değil, template'te ayrı render edilir.

---

## 3. Toast bildirimi + `?gespeichert=1` kalkıyor

### Davranış

Sağ alt köşede 3 saniye görünen ince şerit:

```
┌─────────────────────────┐
│ ✓  Kaydedildi.          │
└─────────────────────────┘
```

- Success → altın çerçeve + altın tik
- Error → kırmızı çerçeve + `!`
- 3 saniye sonra kaybolur (opacity + translate transition)
- Yeni toast gelirse eski atlar

Faz 1'de sadece **sayfa yüklenirken tetiklenen** toast var (mevcut `?gespeichert=1` mantığının yerini alır). Faz 2'de AJAX response'ları da aynı bileşeni kullanacak.

### Uygulama

Mevcut layout'taki success banner (`?gespeichert=1` kontrolü) kalkar. Yerine layout altında sabit bir `<div id="toast-host">` durur. Sayfa `?gespeichert=1` ile geldiyse `admin.js` bunu okuyup toast'u tetikler — inline script yok, CSP ile uyumlu.

`admin.js` içine yeni bölüm — halihazırdaki IIFE pattern'ine uyar:

```js
/* -------------------------- Toast -------------------------- */
window.adminToast = function (message, kind) { ... };

// Sayfa yüklenirken query'den tetikle
var params = new URLSearchParams(location.search);
if (params.has("gespeichert")) {
  var kind = params.get("gespeichert") === "geloescht" ? "info" : "ok";
  var msg  = /* i18n mesaj — data-attribute'tan okur */;
  window.adminToast(msg, kind);
  // URL'yi temizle — geri tuşu tekrar toast atmasın
  history.replaceState({}, "", location.pathname);
}
```

i18n mesajları `<body data-toast-ok="Kaydedildi." data-toast-deleted="Silindi.">` gibi layout'ta veriliyor — server-side render, JS'e string gömmüyoruz.

### Değişen dosyalar

- `templates/admin/layout.php` — success banner bloğu silinir, `<div id="toast-host">` + `data-toast-*` eklenir
- `public/assets/admin.js` — toast bileşeni + query kontrolü
- `src/Admin.php::back()` — `?gespeichert=1` parametresi **korunur** (backend değişmez, kullanıcı arayüzü değişir — POST-redirect-GET geleneği için hâlâ gerekli). Toast onu okuyup URL'den temizler.

### CSP notu

`public/.htaccess` veya `Http::harden()`'da CSP varsa `<script>` inline kullanılamaz — mevcut nonce sistemi (`Http::nonce()`) sadece JSON-LD için. Yeni script'i `admin.js`'e koyduğumuz için sorun yok; `data-*` attribute'undan okuma inline JS gerektirmez.

---

## Test planı

Otomatik test yok (bu repo PHP tarafında test suite tutmuyor — kontrol: `php/bin/` içi script'ler, `phpunit.xml` yok). Manuel kontrol listesi:

1. Bekleyen iş yokken genel bakış eski gibi görünüyor mu?
2. `leads` tablosuna 3 gün eski bir talep eklenip (`INSERT INTO leads (data, at) VALUES ('{}', NOW() - INTERVAL 3 DAY)`) panel açıldığında `lead_stale` satırı çıkıyor mu?
3. Kenar çubuğunda "Daha fazla" tıklanınca açılıp kapanıyor mu? Sayfa değişiminde durum korunuyor mu?
4. `admin_usage` boşken "Sık kullanılanlar" varsayılan pinned listeyi mi gösteriyor?
5. `/admin/portfolio` üç kere ziyaret edildikten sonra sık kullanılanlar listesinde üste çıkıyor mu?
6. Bir metin kaydedildiğinde toast görünüyor + 3 sn sonra kayboluyor mu?
7. Kaydettikten sonra F5 → toast tekrar görünmüyor mu? (URL temizlendi mi?)
8. JavaScript kapalıyken: eski `?gespeichert=1` davranışı olmadan sayfa kırılıyor mu? — Yanıt: kırılmıyor, sadece bildirim görünmüyor. Kabul edilebilir mi? **Evet** — admin JS zorunlu, upload zaten JS gerektiriyor.
9. DE ve TR locale'lerinde tüm mesajlar doğru dilde mi?

---

## Deploy

Standart yol — memory kaydına göre VPS 45.147.46.177, `php/` senkronizasyonu. Fazladan bir adım: `schema.sql`'e yeni `admin_usage` tablosu eklendi, deploy sonrası bir kere `mysql < schema.sql` çalıştırmak gerekir (mevcut tablolar `IF NOT EXISTS` ile korunuyor, sadece yeni tablo oluşur).

`selections` tablosunun şeması değişmiyor — sadece JSON içine `seenAt`/`seenPickCount` alanları eklenir, mevcut kayıtlar da `seenAt` yoksa "yeni" sayılır (yani zaten görülmüş eski seçimler için ilk giriş sırasında `pending` listesinde belirebilir — kabul edilebilir, sadece bir seferlik gürültü).

Geri alma: önceki commit'e dönmek yeter. `admin_usage` tablosu kalır ama zararsız (kimse yazmıyor/okumuyor).

---

## Sonraki fazlar (bu spec'in kapsamı dışında)

- **Faz 2 — Hız:** Global arama `Ctrl+K`, klavye kısayolları, ilk 3 form için AJAX kaydet (texts, content, customer stammdaten). Toast bileşeni bu fazda AJAX cevaplarını göstermek için tekrar kullanılır.
- **Faz 3 — Bonus:** Yan yana önizleme (iframe `?embed=1`), mobil davetiye editörü, toplu arşivle.

Her faz kendi spec/plan/PR döngüsü ile gider.
