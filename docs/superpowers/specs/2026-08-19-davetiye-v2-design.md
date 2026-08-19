# Davetiye v2 — Faz 1: Doküman formatı ve renderer

**Tarih:** 2026-08-19
**Kapsam:** Sadece `php/` — Next.js tarafı (`app/`, `lib/`) bu spec dışında ve hiç değişmiyor.
**Faz:** 1 / 5 (bkz. sondaki "Sonraki fazlar")

## Amaç

Yeni davetiye tasarımı eklemek bugün kod yazmayı gerektiriyor ya da mevcut tema
motorunun sabit yuvalarına sığmayı. Hedef, tasarımın **veri** olması: grafiker
katmanlı dosyaları gönderir, panelden yüklenir, yerleştirilir, yayınlanır — kod
yazılmaz.

Bu faz o hedefin **tek gerçek riskini** kapatıyor: doküman formatını ve onu
ekrana basan renderer'ı. Format yanlışsa sonraki dört fazın tamamı yeniden
yazılır. Doğruysa gerisi düz iş.

Faz 1 panelde **düzenleme** getirmiyor — yalnızca salt okunur bir liste.
Getirdiği şey: format, renderer, ve formatın mevcut bir tasarımı **birebir**
ifade edebildiğinin kanıtı.

## Neden PHP tarafı

`php/YAYIN.md`: asıl yayın hedefi ALL-INKL paylaşımlı hosting — PHP 8.3 +
MariaDB, **Node.js yok**. `php/YAYIN-VPS.md` yalnızca kısa süreli demo. Yani
Next.js sürümünde yazılan bir davetiye motoru müşterinin sunucusunda hiçbir
zaman çalışamaz.

Ayrıca PHP sürümü davetiye tarafında zaten ileride: `src/Themes.php` (1052
satır) modüler tema motoru, tema başına 12 süslemeye kadar yüzde konumlu katman
(`completeDecoration`), beş intro sahnesi + sekiz idle + altı isim animasyonu +
yedi partikül + beş reveal, ve **tema versiyonlama** — davetiye oluşturulurken
temanın anlık görüntüsü saklanıyor, tema sonradan değişince gönderilmiş
davetiyeler bozulmuyor (`Invitations::theme`, `themeOutdated`, `refreshTheme`).

Bu faz o motoru **değiştirmiyor**; yanına serbest katmanlı bir kardeş koyuyor.

## Kapsam dışı

| Konu | Faz |
|---|---|
| Panelde tasarım kataloğu, form editörü, kopyalama | 2 |
| Müşteri satış sayfası, kategori filtresi, live demo, 5 adımlı sihirbaz | 3 |
| Görsel builder (canvas, tıkla-seç, sürükle, sağ panel) | 4 |
| Medya kütüphanesi sekmesi, font yönetimi, dashboard, QR, müşteri paneli | 5 |
| Animasyon zaman çizelgesi, "Kendin tasarla" creator | 4 sonrası |
| Ödeme, kupon, RSVP | Mevcut sistemden devralınacak (Faz 3) |

Faz 1'de `sections` alanı dokümanda **yer alıyor ama boş** — bölüm sistemi
Faz 3'te doldurulacak, format o zaman değişmesin diye şimdiden ayrılıyor.

---

## 1. Yan yana yaşama

### Rotalar

`public/index.php` içine, mevcut satırlara dokunmadan eklenir:

```
/{locale}/v2/designs           → DesignController::index()    katalog
/{locale}/v2/designs/{slug}    → DesignController::preview()  tek tasarım
```

`v2/` öneki bilerek geçici. Karşılaştırma bitip yeni sistem seçilince önek
kaldırılır ve eski rotalar kapatılır — o iş tek dosyada, birkaç satır.

### Menüde görünürlük

Bu bir **değişim değil, ekleme**. Eski davetiye menüde durduğu yerde kalır;
yanına ikincisi gelir, ikisi aynı anda gezilebilsin diye:

`templates/partials/header.php` içindeki `$extra` dizisi (altın renkli, "kendi
ürünümüz" grubu) tek satır uzar:

```php
$extra = [
    [$p('/einladung'), I18n::t('nav.invitation')],
    [$p('/v2/designs'), I18n::t('nav.invitation2')],   // ← yeni
];
```

Aynı satır `templates/partials/footer.php` için de geçerli.

Yeni sözlük anahtarı `nav.invitation2`, `php/data/dict.php` içinde üç dil
kümesine de eklenir: `de` → "Einladung 2", `en` → "Invitation 2", `tr` →
"Davetiye 2".

Dosyanın başındaki "elle düzenlemeyin, `scripts/export-to-php.mjs` üretir"
notu artık geçerli değil: sözlük Next.js sürümünden ayrışmış (site DE+EN'e
geçerken panel DE+TR'de kaldı; bugün `de` 344, `en` 344, `tr` 342 anahtar).
PHP tarafında elle bakılıyor. Bu faz **anahtar eklemekle** yetiniyor, hiçbir
mevcut anahtara dokunmuyor — o yüzden var olan hiçbir metin değişemez. Notun
kendisi ayrı bir iş, bu spec'in kapsamında değil.

Karşılaştırma bitip karar verilince menüden **biri** silinir. Hangisi olacağı
bu spec'in konusu değil; iki girdinin yan yana durabiliyor olması konusu.

Panelde yeni sekme, `Admin::TABS` dizisine bir kayıt:

```php
['href' => '/designs', 'group' => 'einladung', 'de' => 'Designs (v2)', 'tr' => 'Tasarımlar (v2)'],
```

Faz 1'de bu sekme sadece **salt okunur liste** gösterir (hangi tasarımlar var,
hangi sürümde, önizleme bağlantısı). Düzenleme Faz 2.

### Tablolar

Mevcut temalar `site_content` JSON'unun içinde `content['themes']` altında
yaşıyor (`Themes::save` → `Content::mutate`). v2 dokümanlarını oraya sokmak eski
kaydın şemasına dokunmak olurdu. Bu yüzden iki yeni tablo, `php/schema.sql`
sonuna eklenir:

```sql
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

CREATE TABLE IF NOT EXISTS invitations_v2 (
  slug            VARCHAR(96) NOT NULL PRIMARY KEY,
  design_id       VARCHAR(64) NOT NULL,
  design_snapshot LONGTEXT    NOT NULL CHECK (JSON_VALID(design_snapshot)),
  data            LONGTEXT    NOT NULL CHECK (JSON_VALID(data)),
  created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX invitations_v2_design_idx (design_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`invitations_v2` Faz 1'de **yazılmıyor**, yalnızca şeması kuruluyor.
`design_snapshot` sütunu şimdiden var, çünkü snapshot'ı sonradan eklemek
yayınlanmış davetiyeleri bozan türden bir değişiklik olur — mevcut sistemde
`themeSnapshot` ile öğrenilmiş bir ders.

Mevcut tabloların hiçbirine (`invitations`, `invite_drafts`, `rsvps`,
`site_content`, `customers`, …) dokunulmuyor. `rsvps` v2 için Faz 3'te ele
alınacak: slug çakışması riski var, ayrı tablo mu ortak mı orada karara bağlanır.

---

## 2. Doküman formatı

### Design

| Alan | Tip | Not |
|---|---|---|
| `id` | string | `a-z0-9-`, kalıcı kimlik |
| `slug` | string | URL'de görünen ad |
| `name` | `{de, en}` | katalogda görünen ad |
| `category` | string | `luxury`, `floral`, `modern`, `minimal`, `oriental`, `boho` |
| `tags` | string[] | filtre için serbest etiket |
| `cover` | string | katalog kartı görseli (yüklenen dosya yolu) |
| `family` | string | varyasyonları bir arada tutar (mevcut tema motorundaki gibi) |
| `status` | `draft` \| `active` \| `inactive` | |
| `version` | int | içerik gerçekten değiştiyse artar |
| `canvas` | `{ratio, safe}` | yüzde koordinatların referans çerçevesi |
| `palette` | `{anahtar: PaletteEntry}` | isimli renk jetonları |
| `fonts` | `{anahtar: FontEntry}` | isimli yazı jetonları |
| `layers` | `Element[]` | **sıra = z-index**, küçük indeks arkada |
| `sections` | `Section[]` | Faz 3'e kadar boş dizi |
| `animation` | `{intro, idle, reveal, particle}` | mevcut `Themes` sabitleri aynen |

`canvas.ratio` başlangıçta `"9:16"`. `canvas.safe` kenar boşluğu yüzdesi —
yayın kontrolünde (Faz 2) "isim güvenli alanın dışına taşıyor" uyarısı buradan
üretilecek.

**PaletteEntry:** `{value, label:{de,tr}, customer:bool}`

**FontEntry:** `{family, size, weight, tracking, lineHeight, customer:bool}`

### İki ayrı `customer` / `permissions` bayrağı karışmasın

İki farklı soruyu cevaplıyorlar:

| Nerede | Soru |
|---|---|
| `palette.<anahtar>.customer` | "Müşteri **bu jetonu** değiştirebilir mi?" — değiştirirse o jetonu kullanan **bütün** elementler döner. Sihirbazdaki "Gold / Rose Gold / Silver" seçimi budur. |
| `element.permissions.color` | "Müşteri **bu tek elementin** rengini jetondan koparıp ayrı verebilir mi?" |

Aynısı font için: `fonts.<anahtar>.customer` jetonun kendisi,
`element.permissions.font` tek element. Varsayılan ikisinde de `false` —
tasarım kilitli doğar, izin açılarak verilir (plan maddesi 41).

### Element

| Alan | Tip | Not |
|---|---|---|
| `id` | string | doküman içinde tekil |
| `label` | string | katman panelinde görünen ad |
| `type` | `image` \| `text` \| `photo` \| `shape` \| `button` \| `video` | |
| `spot` | `card` \| `page` \| `envelope` | `Themes::SPOTS` ile aynı küme |
| `box` | `{x, y, w, h, rotate, opacity}` | **yüzde** |
| `src` | string | `image` / `video` için dosya yolu |
| `bind` | string | dinamik alan anahtarı, boşsa sabit metin |
| `text` | `{de, en}` | `bind` boşken kullanılır |
| `style` | `{font, color, size, align, autoShrink}` | `font`/`color` **jeton anahtarı** |
| `motion` | `{move, delay, duration}` | `Themes::MOVES` ile aynı küme |
| `permissions` | `{edit, color, font, photo, text, hide}` | hepsi bool, varsayılan `false` |

### Kutu sınırları

`Themes::completeDecoration` ile **birebir aynı** aralıklar — o değerler
telefonda çalıştığı ölçülerek seçilmiş, yeniden icat edilmiyor:

| Alan | Aralık | Varsayılan |
|---|---|---|
| `x`, `y` | −50 … 150 | 4 |
| `w` | 1 … 200 | 20 |
| `h` | 0 … 200 (0 = otomatik) | 0 |
| `rotate` | −180 … 180 | 0 |
| `opacity` | 0 … 100 | 100 |
| `delay`, `duration` | 0 … 20000 ms | 0 / 1200 |

Aralık dışı değer **kırpılır**, reddedilmez — panelden gelen bir sayı yüzünden
tasarım açılmamazlık etmesin.

### Dinamik alanlar (`bind`)

Renderer, davetiye verisindeki alana çevirir. Faz 1'de test verisiyle beslenir;
gerçek davetiye bağlanması Faz 3.

| `bind` | Kaynak alan |
|---|---|
| `couple_names` | `bride` + `&` + `groom` |
| `bride_name` | `bride` |
| `groom_name` | `groom` |
| `initials` | `bride[0]` + `groom[0]` |
| `wedding_date` | `date` → `Dates` ile yerelleştirilmiş |
| `wedding_time` | `time` |
| `location_name` | `venue` |
| `location_address` | `address` |
| `invitation_text` | `message` |
| `hashtag` | `hashtag` |

Tanınmayan `bind` **boş dizeye** çözülür ve renderer bunu bir uyarı listesine
yazar (Faz 2'deki yayın kontrolü bu listeyi kullanacak).

---

## 3. Formatı taşıyan dört karar

**1. Renk ve font isimli jeton, sabit değer değil.**
Element `palette.accent`'i işaret eder, `#B08D57`'yi değil. "Müşteri altın
rengini değiştirebilsin" tek bayrak olur — her elementi tek tek gezmek değil.
Kopyala-ve-renk-değiştir (plan maddesi 21) bedavaya gelir: `palette`'i
değiştirirsin, 30 element birden döner. Bunu sonradan eklemek her elementi
elden geçirmek demek, o yüzden başta.

**2. z-index = dizi sırası.**
Mevcut motordaki `front` boolean'ı (sadece "metnin önü/arkası") yerine tam
sıralama. Katman panelinde yukarı/aşağı taşıma = dizide eleman kaydırma. Ayrı
bir `z` alanı tutmuyoruz; iki kaynak olur ve er geç çelişirler.

**3. Kutular yüzde.**
Piksel değil. Davetiye ağırlıklı telefonda açılıyor; yüzde ölçekle birlikte
büyür. Mevcut süsleme motoru zaten böyle ve sahada çalıştığı görülmüş.

**4. İzinler element üstünde, ayrı listede değil.**
`permissions` elementin yanında durur. Ayrı bir izin tablosu tutmak, element
silinince öksüz kayıt bırakır ve iki yeri senkron tutmayı gerektirir.

---

## 4. Mevcut temadan göç

`Design::fromTheme(array $theme): array` — mekanik dönüşüm, elle yeniden
kurulum yok. Mevcut süslemelerde zaten `x/y/width/rotate/opacity/spot/move/delay/duration` var:

| Tema alanı | v2 karşılığı |
|---|---|
| `accent`, `paper`, `fg`, `soft`, `seal`, … | `palette` girdileri, `customer:false` |
| `image`, `imageMode`, `imageOpacity` | `spot:page`, `type:image` elementi |
| `envelopeImage` | `spot:envelope`, `type:image` elementi |
| `decorations[]` | her biri bir `type:image` elementi; `front:true` → dizinin sonuna, `false` → başına |
| `animation`, `intro`, `idle`, `particle`, `reveal` | `animation` bloğu, birebir |
| Karttaki sabit metin yerleri (isim, tarih, mekân) | `type:text` + ilgili `bind` |

Son satır işin gerçek kısmı: bugün isim/tarih/mekân kart şablonunda **sabit
yerde** duruyor, doküman formatında ise element oluyorlar. Élysée için o
elementlerin kutuları, mevcut şablonun ürettiği yerleşimden ölçülerek bir kez
yazılır ve `bin/seed-designs.php` içine girer.

---

## 5. Renderer

`php/src/Design.php` — `Themes.php`'ye **dokunmaz**, yanında durur.

```
Design::complete(array $doc): array
    Eksik alanları doldurur, aralıkları kırpar, bilinmeyen enum değerlerini
    varsayılana düşürür. Themes::complete ile aynı disiplin.

Design::css(array $doc, string $scope): string
    Scope'lu CSS üretir:
      - palette → CSS custom property (--d-accent: #B08D57)
      - her element → konum/boyut/opaklık/rotate kuralı
      - yalnızca kullanılan keyframe'ler
      - @media (prefers-reduced-motion: reduce) → animation:none

Design::html(array $doc, array $values, string $locale): string
    Element listesini işaretlemeye çevirir, bind'leri $values'tan yerine koyar.
    Tüm metin çıktısı e() ile kaçırılır. src yalnızca Media'nın bildiği
    yollardan kabul edilir.

Design::fromTheme(array $theme): array
Design::warnings(array $doc): array    ← Faz 2'nin yayın kontrolü buradan beslenir

Design::find(string $slug): ?array     designs tablosundan tek kayıt
Design::all(string $status = ''): array
Design::save(array $doc): void         versiyonu içerik değiştiyse artırır
```

`Design::save()` versiyon artırmayı `Themes::save`'deki kuralla yapar: `version`
ve `updatedAt` karşılaştırma dışı tutulur, içerik gerçekten değiştiyse artar.

Veri erişimi mevcut `Db` üzerinden (`Db::json`, `Db::jsonList`, `Db::run`,
`Db::encode`) — hazırlanmış ifadeler, dize birleştirme yok.

Olduğu gibi kullanılanlar: `Media` (alfa koruyan WebP + SVG temizleme),
`OgImage`, `Dates`, `I18n`, `Http` (CSP dahil), `View`, `Security`.

### Güvenlik

- Üretilen CSS `scope` dışına çıkamaz; `Design::css` her seçicinin başına
  scope'u koyar, doküman içinden ham seçici kabul etmez
- `Design::html` hiçbir alanı ham HTML olarak basmaz
- CSP `script-src 'self'` korunur: renderer satır içi `<script>` ya da
  `onclick=` üretmez. Üretilen CSS `<style nonce>` ile gider (mevcut
  `Http`/`View` düzeni)

---

## 6. Yeni dosyalar

Hepsi yeni dosya. Mevcut dosyalar yalnızca **ek** satır alır:

| Dosya | Eklenen |
|---|---|
| `php/public/index.php` | iki rota |
| `php/src/Admin.php` | bir sekme kaydı |
| `php/schema.sql` | iki tablo |
| `php/templates/partials/header.php` | bir menü satırı |
| `php/templates/partials/footer.php` | bir menü satırı |
| `php/data/dict.php` | bir anahtar × üç dil |

Mevcut hiçbir satır silinmez ya da düzenlenmez. Bu, gözden geçirmede
doğrulanabilir bir iddia: bu fazın diff'inde `-` ile başlayan satır olmamalı.

```
php/src/Design.php
php/src/Controllers/DesignController.php
php/templates/pages/designs-v2.php
php/templates/pages/design-preview.php
php/templates/admin/designs.php          (Faz 1'de salt okunur liste)
php/bin/seed-designs.php                 Élysée → v2 dokümanı
php/bin/test.php                         bağımlılıksız assert koşucusu
php/tests/design_*.php                   test dosyaları
```

---

## 7. Bitti sayılma ölçütü

`/{locale}/v2/designs/elysee` ile `/{locale}/designs/elysee` yan yana açıldığında
**aynı görünecek** — ama v2 tarafı tamamen `designs.doc` verisinden sürülüyor
olacak; Élysée'ye ait tek satır PHP kodu olmayacak.

Kontrol listesi:

- [ ] Telefon genişliğinde (390 px) iki sayfa aynı
- [ ] Tablet ve masaüstünde aynı
- [ ] Zarf → mühür → kart açılış animasyonu aynı zamanlamada
- [ ] Uzun isim ("Maximilian & Charlotte-Sophie") ikisinde de aynı davranıyor
- [ ] `prefers-reduced-motion` açıkken ikisi de duruyor
- [ ] Eski rotalar ve panel sekmelerinin tamamı uyarısız 200 dönüyor

Son madde şartın kendisi: eskisine dokunulmadığının kanıtı.

---

## 8. Test

Composer yok ve ALL-INKL'de olmayabilir. `php/bin/test.php` bağımlılıksız bir
koşucu: `php/tests/` altındaki dosyaları çalıştırır, `assert_same($a, $b, $ad)`
gibi birkaç yardımcı sunar, başarısızlıkta sıfırdan farklı kod döner.

Kapsanacaklar:

| Test | Neyi tutuyor |
|---|---|
| `complete()` eksik alanları dolduruyor | eski kayıt yeni alanla açılabilsin |
| `complete()` aralık dışını kırpıyor, reddetmiyor | bozuk sayı tasarımı açılmaz yapmasın |
| `complete()` bilinmeyen enum'u varsayılana düşürüyor | elle düzenlenmiş JSON çökertmesin |
| `fromTheme()` süsleme → element dönüşümü kayıpsız | göç güvenilir olsun |
| `fromTheme()` `front` bayrağını doğru z sırasına çeviriyor | katman sırası bozulmasın |
| `css()` yalnızca kullanılan keyframe'i yazıyor | sayfa şişmesin |
| `css()` scope dışına sızmıyor | bir tasarım diğerini bozmasın |
| `html()` bind yerine koyma + XSS kaçışı | isim alanına `<script>` yazılabilir |
| `html()` tanınmayan bind boşa çözülüyor + uyarı üretiyor | sessiz bozulma olmasın |
| `permissions` serileşip geri okunuyor | Faz 3 buna güvenecek |
| `save()` içerik değişmediyse versiyonu artırmıyor | snapshot gürültüsü olmasın |

---

## 9. Riskler

**Format eksik çıkarsa.** Élysée'yi ifade edebilmek yetmeyebilir; asıl sınav
grafikerden gelecek katmanlı bir tasarım. Azaltma: Faz 2'ye geçmeden ikinci bir
tasarım daha aktarılır — tercihen mevcutlar arasında en farklı olanı (Noir,
koyu zemin + farklı partikül).

**Sabit metinlerin element'e çevrilmesi.** Kart şablonundaki yerleşim CSS
akışıyla üretiliyor; yüzde kutulara dökerken küçük kaymalar çıkabilir. Azaltma:
bitti ölçütü "yaklaşık aynı" değil "aynı" diyor, ve karşılaştırma üç genişlikte
yapılıyor.

**İki sistemin paralel bakımı.** Karşılaştırma süresince iki davetiye motoru
birden duruyor. Azaltma: `v2/` öneki ve ayrı tablolar sayesinde silme işlemi
ucuz — beğenilmezse yeni tablolar düşürülür, tek satır eski kod değişmemiştir.

---

## 10. Sonraki fazlar

| Faz | İçerik |
|---|---|
| **2** | Panelde tasarım kataloğu (görsel kartlar, kategori filtresi, kopyala, aktif/pasif) + doküman üstünde renkli bölümlere ayrılmış form editörü (Genel / Renkler / Yazılar / Görseller / Animasyon / Müşteri izinleri / Yayın) + yayın kontrol listesi |
| **3** | Müşteri tarafı: satış sayfası, kategori filtresi, tam ekran live demo, 5 adımlı sihirbaz (izin bayraklarını okur), bölüm sistemi (sürükle-sırala + göster/gizle), hazır bölüm şablonları, ödeme/kupon/RSVP devralma |
| **4** | Görsel builder: canvas, katman paneli, tıkla-seç, sağ panel, sürükle-bırak. Faz 2'deki form editörü yeterli geliyorsa kapsamı küçülür |
| **5** | Medya kütüphanesi sekmesi, font yönetimi, dashboard kartları, QR, müşteri paneli |

Animasyon zaman çizelgesi ve "Kendin tasarla" creator'ı Faz 4 sonrasına
bırakıldı — ikincisi için müşterinin kendi notu da "ana ürün olmamalı" diyor.
