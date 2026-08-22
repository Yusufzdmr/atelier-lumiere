# Davetiye v2 — Video, motif ve sadeleşme

**Tarih:** 2026-08-23
**Durum:** tasarım onaylandı, plan yazılmadı
**Kapsam:** sadece `php/`. Next.js tarafı (`app/`, `lib/`) bu spec dışında.
**Öncesi:** [Yayın sonrası düzenleme](2026-08-20-davetiye-v2-yayin-sonrasi-duzenleme-design.md)
ve ondan önceki v2 fazları.
**Kaynak:** müşterinin (Ayhan) WhatsApp üzerinden ilettiği yön ve gösterdiği iki
referans site.

---

## 1. Amaç

Müşteri iki şey söyledi ve ikisi aynı şeye çıkıyor:

> „Sistemi çok detaylı yapmaya çalıştık." · „Çok animasyon, çizgi film gibi.
> Esas olması daha elegant ve romantik." · „Ortası boş düşün, sadece çiçekler var."

Ve bir eksik bildirdi:

> „Bundaki sıkıntı biz arkaya video ayarlayamalıyız." · „1. video, 2. resim —
> başlangıç olarak bunlarla çalışabiliriz."

Bu dilim üç işi birlikte yapar: **videoyu birinci sınıf bir malzeme yapar**,
**süslemeyi koddan dosyaya taşır**, ve **hareket sözlüğünü budar**. Üçü ayrı
ayrı yapılırsa yeni video temaları eski animasyon havuzunun içinde doğar ve
„elegant" hissi gelmez — bu yüzden tek dilim.

## 2. Referanslardan ölçülen

İki referans siteyi tarayıcıda açıp DOM'unu okudum. Videoları ağ tarafında
yükleyemedim (`readyState 0`), o yüzden hareketi izlemedim; yapıyı okudum.

**`herzsiegel.de/demo/toskana`**

| Ne | Değer |
|---|---|
| Zarf açılışı | `/video/envelope-tuscany.mp4`, poster: `images/hero/envelope-tuscany-poster.jpg` |
| Kapak | Gerçek kâğıt ve gerçek mum mührün **fotoğrafı** |
| Süsleme | `motifs/tuscany/villa.png`, `cypress.png`, `divider.png`, `wine-bottle.png` — suluboya PNG |
| Hero | `images/hero/tuscany-watercolor.webp` — kenarlardan içeri taşan çiçek çerçevesi, **ortası bomboş krem** |
| Sayfa | 9351 px, 11 `<section>`, tek scroll |
| Hareket | Yok |
| Bölümler | Hero · Detaylar(yer+tarih+harita) · Program · Hikâye · Galeri · Geri sayım · Kıyafet · Hediye · RSVP · Footer · CTA |

**`thedigitalyes.com/demo/royal`** (gerçek adres: `premiumelegante.thedigitalyes.com`)

| Ne | Değer |
|---|---|
| Zarf açılışı | `/assets/intro-video-new-XmwQeafK.mp4`, tam ekran `object-cover`, tıklamayla oynar |
| Kapak | Gerçek kâğıt + altın mum mühür fotoğrafı |

**Çıkan üç sonuç:**

1. **Açılış animasyonu ikisinde de video.** Müşteri „bizim açılma animasyonumuz
   daha güzel" dedi; bu hareket için doğru olabilir, ama malzeme için değil.
   Onlarınki gerçek kâğıt, bizimki çizim.
2. **Süsleme dosya, kod değil.** Bizde `src/Scenes.php` 322 satırla aynı işi SVG
   çizerek yapıyor. „Çizgi film gibi" şikâyetinin kaynağı büyük ölçüde bu.
3. **Sıfır hareket.** Bizde `Themes.php` içinde 38 hareket seçeneği duruyor:
   `INTROS` 6 + `IDLES` 8 + `NAME_ANIMATIONS` 6 + `PARTICLES` 7 + `REVEALS` 5 +
   `MOVES` 6.

## 3. Video: katman tipi olur, yeni kavram değil

`Design::TYPES` (`src/Design.php:21`) zaten `'video'` içeriyor. `Design::html()`
onu bilerek atlıyor:

```php
// video: Faz 3. Bis dahin wird das Element still uebersprungen.
```

O borç burada kapanır. Video, `photo` katmanının kardeşi olur — **kendi yeri,
kendi tablosu, kendi editörü yok.**

### 3.1 Çizim

`Design::html()` içine `video` dalı:

```html
<video class="d-el d-el-<id> d-spot-<spot>"
       src="…" poster="…"
       muted loop playsinline preload="metadata"
       aria-hidden="true"></video>
```

- **Kaynak boşsa düğüm kalır, gizlenir** — tıpkı `photo` gibi. Gerekçesi aynı:
  sihirbaz önizlemesi seçileni koyacak bir yer bulamazsa çift arka planı hiç
  görmeden seçer.
- **Ses yok, her zaman.** Bir davetiyenin kendiliğinden ses çıkarması kabul
  edilemez; `muted` olmadan tarayıcı zaten oynatmaz.
- `aria-hidden` — süs, içerik değil.
- **`autoplay` özniteliği yazılmaz.** Oynatmayı `assets/invitation.js` başlatır
  (bkz. 3.5). Öznitelik yazılsaydı video kapalı zarfın arkasında, kimse
  görmeden dönerdi — mobil veride bedava trafik.

### 3.2 Stil

`Design::css()` içindeki `object-fit:cover` koşuluna (`src/Design.php:365`)
`video` eklenir. Koşulun kendisi değişmez: yükseklik verilmemişse `object-fit`
zaten iş yapmaz, ve dolduran bir katmanın yüksekliği olur.

### 3.3 Şema

`Design::completeElement()`'e **tek** yeni alan: `poster`. `safeSrc()`'den
geçer, aynı `/uploads|assets/` kalıbı.

`src` videonun kendisi, `poster` ilk kare. Poster zorunlu değil ama şiddetle
önerilir: video yüklenene kadar kart boş kalmasın.

### 3.4 Yer

`spot` neyi söylerse orası — `Themes::SPOTS` zaten üçünü tanımlıyor:

| `spot` | Sonuç |
|---|---|
| `page` | Kartın arkasında, sahnenin tamamı. Royal'deki his. |
| `card` | Kartın üstünde, yüzde koordinatlı kutu. |
| `envelope` | Zarfın üstünde. |

Yeni bir konum kavramı **gelmez**. Hangi tasarımın videoyu nereye koyacağına
grafiker karar verir.

### 3.5 Oynatmayı kim başlatır

`assets/invitation.js` — sahnenin zaten sahibi olan betik. Hem v1 hem v2 onu
yüklüyor (`DesignController.php:121`, `InviteV2Controller.php:765`) ve zarfın
`data-envelope` / `data-intro-ms` sözleşmesi orada duruyor.

Kural:

- Zarf açılana kadar hiçbir video oynamaz.
- Açıldığında sahnedeki `<video>` düğümleri `play()` edilir.
- `prefers-reduced-motion: reduce` açıksa **hiç oynatılmaz** — poster durur.

Bu, katmanın `motion.move` ayarından bağımsızdır: video bir „belirme efekti"
değil, sürekli hareket eden bir yüzey.

### 3.6 Güvenlik

Yeni iş yok. `Http.php:122` zaten `media-src 'self' https:` yayıyor.
`Media::storeVideo()` (`src/Media.php:275`) zaten var: 100 MB sınırı, tür dosya
**içeriğinden** belirleniyor, mp4/webm/mov, ad rastgele, `public/uploads/`
altındaki `.htaccess` PHP motorunu kapatıyor.

## 4. Video kitaplığı

Panelde **yeni sekme açılmaz.** Mevcut **Tasarımlar** sekmesinin altına bir
liste gelir.

### 4.1 Kayıt

| Alan | Not |
|---|---|
| Ad | Panelde görünen isim |
| `mp4` | Zorunlu |
| `webm` | İsteğe bağlı, varsa `<source>` olarak önce yazılır |
| Poster | Şiddetle önerilir |
| Kategori | `Design::CATEGORIES` ile aynı küme |

### 4.2 Yönerge

Yükleme kutusunun yanında sabit bir yönerge cümlesi durur: çözünürlük, en-boy,
süre, dosya boyutu, döngünün dikişsiz olması. **Bu değerler bu spec'te
yazılmadı** — müşteri belirleyecek. Plan aşamasında değer gelmezse, geçici
olarak `Media::MAX_VIDEO` (100 MB) dışında bir sınır yazılmaz ve cümle
yalnızca „dikişsiz döngü, sessiz" der.

### 4.3 İzin

**Yeni izin eklenmez.** `Design::PERMISSIONS` altı maddede kalır. Mevcut `photo`
hakkı „bu alanın içeriğini çift değiştirebilir" anlamına gelir; video katmanı da
o hakka bağlanır. Hak açıksa sihirbaz kitaplığı gösterir, kapalıysa hiç sormaz.

Bu, müşterinin „yüzde 80 bizim hazırladıklarımızdan seçsin" isteğinin
karşılığıdır — ve v2'de zaten böyle: `DesignWizard`'ın sınıf açıklaması
„im heutigen Bestand steht fast jedes Recht auf false" diyor. Altyapı duruyor;
mesele hangi %20'nin açılacağı, ve o karar tasarım tasarım verilir.

### 4.4 Çift kendi videosunu yükleyemez

Bilerek. Müşterinin tespiti: „müşteri arkaya zor bulur." Yükleme kapısı açılırsa
boyut, çözünürlük, süre ve depolama sorunları çiftin eline geçer.

## 5. Açılış: tema seçer

Temaya iki alan gelir: `introVideo` + `introPoster`. Adlandırma mevcut
`backdropVideo` / `backdropPoster` çiftini (`src/Themes.php:421`) izler — o
ikisi kartın **içindeki** arka planı tarif ediyor, bunlar **açılışı**.

`templates/partials/design-stage.php`:

- Tema `introVideo` taşıyorsa: zarf yerine video oynar, bitince kart açılır.
  `introMs` mekanizması zaten sahnede duruyor; süre videonun `duration`'ından
  gelir, `introMs` üst sınır olarak kalır (video yüklenmezse sahne kilitlenmesin).
- Taşımıyorsa: bugünkü CSS zarfı çalışır, hiçbir şey değişmez.

**Mevcut hiçbir tema bozulmaz.** Müşteri bir açılış videosu ürettiğinde tek bir
tema kaydı değişir, kod değişmez.

**Kitaplıktan gelmez.** Açılış videosu temanın kendi alanıdır, 4. bölümdeki
video kitaplığından seçilmez. Kitaplık çiftin katman içeriği için; açılış
grafikerin tema kararı. İki ayrı yer, çünkü iki ayrı sahip.

## 6. Motif: koddan dosyaya

### 6.1 Bugünkü durum — ve neden dikkatli olmak gerekiyor

`Scenes` sınıfını **v2 hiç kullanmıyor.** Kullanan iki yer var, ikisi de v1:

```
templates/pages/invitation.php:37    Scenes::html($theme['scene'] ?? 'botanical', $theme)
templates/pages/invitation.php:241   Scenes::envelopeArt($theme['scene'] ?? '', $theme)
```

Yani `Scenes.php`'yi silmek **yayındaki v1 davetiyelerinin arka planını
söndürür.** Bu davetiyeler misafirlere gönderilmiş linklerdir; `themeSnapshot`
onları korumaz, çünkü snapshot temayı saklıyor ve çizim her istekte yeniden
üretiliyor.

### 6.2 Sıralama — bu bir kapı, öneri değil

1. **Her tema için** `php bin/export-scene-art.php <id>` çalıştırılır. Araç
   zaten var ve tam bunun için yazılmış; kendi başlığında diyor: „die Szene ist
   heute Code … also muss die Zeichnung einmal zu einer Datei werden."
   Bedeli araçta yazılı: renkler donar, paleti artık izlemez.
2. Çıkan dosyalar temanın `decorations` dizisine yazılır. O dizi bu iş için
   hazır: `completeDecoration()` (`src/Themes.php:614`) `src`, `spot`, `x`, `y`,
   `width`, `rotate`, `opacity`, `front`, `move` alanlarını tutuyor, tema başına
   12 öğe sınırı var, ve `Media::storeGraphic()` alfa kanalını koruyarak WebP
   üretiyor.
3. **Her tema tek tek gözle karşılaştırılır** — öncesi/sonrası aynı mı.
4. Ancak ondan sonra `Scenes.php`, `Themes::SCENES` ve `scene-*` CSS silinir,
   `invitation.php`'deki iki çağrı kaldırılır.

Seed dosyasında 16 tema var (`data/themes.php`) ve hiçbirinde `'scene'` alanı
açıkça yazılı değil — hepsi `invitation.php`'deki `'botanical'` varsayılanına
düşüyor. Canlı veritabanındaki değerler farklı olabilir; **adım 1 veritabanından
okunan listeye göre yapılır, seed dosyasına göre değil.**

### 6.3 v2 tarafı

v2'de motif zaten `image` tipi bir katman. Yeni kod gerekmez. Müşteri suluboya
motifleri ürettikçe grafiker onları katman olarak yerleştirir.

## 7. Hareket sözlüğünün budanması

| Liste | Bugün | Sonra | Gerekçe |
|---|---|---|---|
| `PARTICLES` | 7 | **kalkar** | Konfeti, kar, kıvılcım — „çizgi film" şikâyetinin merkezi |
| `IDLES` | 8 | `breathe`, `none` | Kapalı zarfın nefesi süs değil, „bana dokun" işareti |
| `NAME_ANIMATIONS` | 6 | `fade`, `none` | İsmin harf harf yazılması zarif değil, gösterişli |
| `REVEALS` | 5 | `up`, `none` | Bir yön yeter |
| `MOVES` | 6 | `fade`, `none` | Süzülen/salınan süs = hareketli duvar kâğıdı |
| `INTROS` | 6 | **dokunulmaz** | v1'e ait; `Intro::` yalnızca `pages/invitation.php`'de çağrılıyor, v2 onu hiç kullanmıyor |

Budanan beş liste: 32 → 8. `INTROS` v1'in kendi işi olarak yerinde kalır.

**Yayınlanmış davetiyeler etkilenmez:** `themeSnapshot` değeri saklıyor ve
render eden CSS keyframe'i duruyor. Panelde artık listede olmayan bir değer
okununca `Themes::` doğrulaması onu `none`'a düşürür — bugün de böyle çalışıyor
(`src/Themes.php:467`).

**Silinmeyen:** ilgili CSS keyframe'leri. Snapshot'lar onlara işaret ediyor.
Panelden seçilemez olurlar, dosyada kalırlar.

## 8. İki referans tasarım

Katalogda iki yeni v2 tasarımı: **① Video** ve **② Resim**.

Aynı düzen, tek fark arka katmanın tipi (`video` / `photo`). Düzen Toskana'nın
okuması:

- Kenarlardan içeri taşan motif çerçevesi
- **Ortası boş**
- Ortada: küçük harf aralıklı üst başlık (`WIR HEIRATEN`) · isimler · tarih
- Hareket: yalnızca `fade`

Bunlar müşteri gerçek motifleri ve videoları üretene kadar mevcut
malzemeyle kurulur; amaçları sistemi göstermek, son görüntüyü vermek değil.

## 9. Test

`php bin/test.php` — veritabanı istemez, saf sınıfları koşar.

| Dosya | Eklenen |
|---|---|
| `tests/design_html.php` | video dalı: kaynaklı, kaynaksız (gizli düğüm), geçersiz `src` (düşer), `poster` yazılıyor mu, `muted`/`playsinline` var mı |
| `tests/design_css.php` | `object-fit:cover` video için yazılıyor, yükseklik yokken yazılmıyor |
| `tests/design_complete.php` | `poster` alanı tamamlanıyor ve `safeSrc`'den geçiyor |
| yeni: `tests/themes_motion.php` | budanmış listeler; listede olmayan değer `none`'a düşüyor |

Elle doğrulanacak (test edilemez):

- Yayındaki her v1 teması, motif dışa aktarımından önce ve sonra aynı görünüyor
- `prefers-reduced-motion` açıkken video oynamıyor, poster duruyor
- Telefon: video `page` spot'unda tam sahneyi kaplıyor, kart okunur kalıyor

## 10. Kapsam dışı

| Ne | Neden |
|---|---|
| Yeni bölüm tipleri (galeri, hikâye/zaman çizelgesi, kıyafet, hediye) | Referanslarda var, bizde yok (`DesignSections::TYPES` altı tip). Kendi dilimini alır. |
| Otomatik sayfalama / „kâğıt" sayfalar | Müşterinin son isteği. Bölüm tipleri geldikten sonra anlamlı. |
| Motif ve video dosyalarının kendisi | Müşteri üretecek. Bu spec altyapıyı ve geçiş yolunu verir. |
| Yönerge değerleri (çözünürlük, süre, boyut) | Müşteri belirleyecek — bkz. 4.2 |
| Next.js sürümü | `app/`, `lib/` bu spec dışında |

## 11. Açık kalan

1. **Yönerge değerleri** (4.2). Plan aşamasında gelmezse geçici cümleyle
   ilerlenir.
2. **Hangi tasarımda hangi izin açık olacak** (4.3). Tasarım başına verilecek
   karar; bu spec yalnızca mekanizmayı sabitler.
3. **v1'in ömrü.** Bu spec v1'i yaşatıyor ve `Scenes` geçişini onun için
   yapıyor. v1 kapatılacaksa 6. bölümün yarısı gereksizleşir — ama o karar
   bu dilimin dışında.
