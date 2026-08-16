# PHP sürümü — durum ve devam notu

Bu dosya, işi yeni bir sohbette kaldığı yerden sürdürmek içindir.
Projenin genel anlatımı `../README.md` dosyasında; burada yalnızca **PHP sürümünün**
durumu var.

## Neden iki sürüm var

`../app`, `../lib` → **Next.js sürümü** (Vercel'de canlı demo, çalışıyor, silinmedi).
`./` → **PHP sürümü**: müşterinin ALL-INKL paylaşımlı hostinginde çalışması gerektiği
için yazılıyor. ALL-INKL PHP + MariaDB veriyor, Node.js vermiyor.

Veri yapısı iki sürümde **aynı JSON** olduğu için içerik taşıması bir kopyalama işi:
`node ../scripts/export-to-php.mjs` → `data/dict.php`, `data/themes.php`, `data/export.json`
sonra `php bin/import.php` (veya `--replace`).

## Biten

| Alan | Durum |
|---|---|
| İskelet | Router, şablon motoru, PDO katmanı, iki dil (624 metin aktarıldı), oturum/CSRF, `.htaccess` |
| Genel sayfalar | Ana sayfa, hizmetler, fiyatlar, portfolyo + hikâye, bölgeler, 10 şehir, mekân listesi + 7 mekân, rehber + yazılar, hakkımda, iletişim (form + e-posta + kayıt), Impressum/Datenschutz/AGB, sitemap.xml (74 kayıt), robots.txt |
| Müşteri galerisi | Giriş, fotoğraf ızgarası, kalple seçim, lightbox, seçim gönderimi (veritabanı + e-posta) |
| Görsel yükleme | `src/Media.php` — GD ile 1600 px JPEG, tür dosya içeriğinden, silme upload klasörüyle sınırlı. **GD yoksa** dosya küçültülmeden olduğu gibi saklanır (hata sayfası yerine) |
| Video | YouTube/Vimeo, iki tıklamalı (izin öncesi sağlayıcıya istek yok) |
| **Yönetim paneli — 16 sekmenin hepsi** | Giriş (deneme sınırlı), Genel bakış, Metinler & iletişim, **Sayfa metinleri**, Fiyatlar & paketler, **Hizmetler & süreç**, **Şehirler**, **Mekânlar**, **Portfolyo**, **Rehber**, **Müşteriler**, **Davetiyeler**, Temalar, Hakkımda & yorumlar, Yasal metinler, SEO & meta, Entegrasyonlar |
| Liste düzenleyicisi | `src/Lists.php` + `templates/admin/list.php` + `src/Controllers/ListAdminController.php` — şehir/mekân/portfolyo/rehber/hizmet aynı kalıptan: aç, düzenle, kaydet, ekle, sırala (↑↓), sil. Her kayıt kendi formunda (10 şehir tek düğmeye gitmez) |
| Müşteriler | `src/Customers.php` + `CustomerAdminController` — kayıt açınca galeri **otomatik** oluşur (parola ve kupon otomatik üretilir, galeri bitişi düğün + 2 yıl). Fotoğraf yükleme/silme, çiftin seçimi (kalpli kareler + notu), kupon yönetimi (kod/aktif/tek kullanım/son tarih/yeni kod/yeniden aç), arşivle, giriş adını yazdırarak kalıcı sil |
| Davetiyeler | `InviteAdminController` — davetiye listesi, ödendi/kupon/ödenmedi rozeti, RSVP'ler (kabul/ret/kişi sayısı + notlar), **kişiye özel davetiye listesi + çiftin yönetim linki**, müşteri kaydına bağlantı, yarım kalan taslaklar, silme |
| **Sayfa metinleri** | Yeni sekme (`/admin/texte`). Sözlükteki 312 metin — bölüm başlıkları („Was wir für euch tun“), düğme yazıları, form etiketleri — 17 grup halinde, iki dilde düzenlenebilir. `src/Texts.php` bir **üst katman**: sözlük dosyası hiç değişmiyor, yalnızca farklı olan saklanıyor. Bir alanı boşaltmak = ilk metne dönmek |
| Mekân → Google | `src/Places.php` — panelden yer adı yazılır, Google Places karşılıklarını listeler, **her adayın yanında haritası** çıkar, „Bunu al“ deyince ad/adres/koordinat/place-id mekâna yazılır. Metinler ellenmez. Anahtar sunucuda kalır; harita bile kendi adresimizden (`/admin/karte`) geçer. Anahtar yokken sekme çalışır, sadece „önce anahtarı gir“ der |
| Mekân yorumları | Seçili yerin Google yorumları **canlı** gösterilir (4+ yıldız, 80+ karakter, uzun olan üstte), yazar adı ve bağlantısıyla. **Saklanmaz, siteye kopyalanmaz** — Google şartları izin vermiyor, metinler yazarlarına ait ve kopya içerik SEO'da zarar. Amacı: „avludaki ışık, gürültülü salon“ gibi gerçek ayrıntıları görüp kendi cümlelerinizle yazmak |
| Çerez izni + ölçüm | `templates/partials/consent.php` + `public/assets/consent.js`. Ön işaretli kutu yok, „Sadece gerekli“ „Tümünü kabul et“ ile **eşit görünürlükte**, karar `localStorage`'ta (`al-consent-v1`). Alt bilgide ve Datenschutz sayfasındaki `{{consent}}` ile her an geri açılıyor |
| Consent Mode v2 | `dataLayer` sırası ölçüldü: `consent default (hepsi denied)` → `ads_data_redaction` → `js` → `config` → `consent update`. Yani izin **script yüklenmeden önce** denied |
| İzinsizken | Tarayıcıda doğrulandı: Google/Meta/Doubleclick'e **sıfır istek**, `gtag` ve `fbq` tanımsız, HTML'de hiç dış `<script>` yok. Reddedince de aynı |
| Kısmi izin | Yalnız istatistik seçilince GA4 yükleniyor, Meta Pixel yüklenmiyor — test edildi |
| Dönüşümler | İletişim formu (`generate_lead`), davetiye oluşturma (`purchase`, tutarıyla), `tel:` tıklaması (`phone_call`) — tek dinleyici, hiçbir bağlantının haberi olmadan. Ads etiketleri panelden. Sayfa `data-track-event` alanıyla bildiriyor, HTML'de script bloğu yok (CSP sıkı kalsın diye) |
| Krumbach bölge içeriği | 10 şehir sıfırdan yazıldı, iki dilde ~3.300 kelime: Günzburg, Ulm, Neu-Ulm, Memmingen, Augsburg, München, Stuttgart, Friedrichshafen, Bregenz, St. Gallen. Her şehirde giriş + 2 paragraf + 3 çekim noktası + 2 SSS + komşular. `bin/cities.php` içe aktarırken **kontrol ediyor**: çakışan adres, boşa çıkan komşu, ve metinler birbirine %55'ten fazla benziyorsa **içe aktarmayı reddediyor** (doorway page kontrolü) |
| Yayın kontrolü | Panelde yeni sekme (Ayarlar → Yayın kontrolü). Sunucuda 13 şeyi kontrol ediyor: PHP sürümü, eklentiler, GD+WebP, veritabanı, 12 tablo, içerik ve **eksik adres/telefon**, upload klasörü yazılabilirliği ve `.htaccess`'i, `config.php` (dev/site_url/parola), HTTPS, e-posta, entegrasyonlar (PayPal sandbox'ta mı). Her uyarının altında ne yapılacağı yazıyor. Altında da elle yapılacaklar listesi (form maili, WhatsApp önizlemesi, PayPal küçük tutar testi…) |
| Panel düzeni | Sekmeler artık **gruplanmış yan menüde** (İçerik / İşler / Görünüm / Ayarlar) — 16 sekme tek sırada bir duvardı. Geniş ekranda yapışkan yan menü, dar ekranda açılır kapanır seçim (açık sekmenin adı üstte yazılı). Üst çubuk yapışkan, uzun formlarda **kaydet düğmesi altta sabit** duruyor. Genel bakış: kutucuklar tıklanır, „son yedi günde ne geldi“ satırı ve dört hızlı işlem düğmesi |
| Öncesi + geri al | Her alanın altında, **yalnızca değiştiyse**, eski metin ve „geri al“ düğmesi. İçerik alanlarında karşılaştırma `site_content` id=2'den (içe aktarımda yazılan dokunulmamış kopya), sayfa metinlerinde `data/dict.php`'den geliyor. „Geri al“ formun tamamını da kaydediyor, o yüzden diğer yazdıklarınız kaybolmuyor |
| **Tema motoru — modüler** | Renk/font/zarf/mühür/arka plan/süsleme/animasyon ayrı ayrı. `family` alanı varyasyonları (Ivory, Rose, Sage, Dark) bir arada tutuyor; „Varyasyon oluştur“ ailede kalarak kopyalıyor |
| **Tema versiyonlama** | Davetiye oluşturulurken temanın **anlık görüntüsü** kaydediliyor (`themeSnapshot`). Tema sonradan değişince gönderilmiş davetiyeler **değişmiyor** — uçtan uca test edildi. `Invitations::themeOutdated()` eskimişi görür, `refreshTheme()` bilerek günceller. Her kaydetmede `version` artıyor (içerik gerçekten değiştiyse) |
| Süslemeler | Tema başına 12 öğeye kadar: çiçek, çerçeve, monogram. Konum/boyut/dönüş/opaklık **yüzde** cinsinden (telefonda da otursun diye), katman sırası (metnin önü/arkası), yer (kart/sayfa/zarf) ve hareket (belir/yüksel/süzül/salın/büyü) + gecikme + süre. `prefers-reduced-motion` açıksa hareket yok |
| Şeffaf yükleme | `Media::storeGraphic()` — PNG/WebP/GIF alfa kanalını **koruyarak** WebP'ye çeviriyor (600×600 PNG → 3.7 KB WebP, köşe alfa=127 doğrulandı). SVG kabul ediliyor, içindeki `<script>` ve `on…=` temizleniyor; zaten yalnız `<img>` içinde gösteriliyor |
| Yazı tipleri | Kendi sunucumuzdaki iki aile arasında seçim + boyut ve harf aralığı. Fazlası her açılışta Google'a bağlanmak olurdu |
| Tema aktarımı | Her temanın JSON'u panelde; „Yeni tema“ altına yapıştırılınca içe aktarılıyor (görseller hariç — onlar diğer kurulumda) |
| Cihaz önizlemesi | Önizleme kutusu masaüstü / tablet / telefon genişliğine geçiyor |
| Temalar | Renkler, Canva arka planı yükleme, animasyon seçimi + süre, kendi CSS'i (`.theme-<id>` altına sınırlanıyor, `@import`/`expression(` temizleniyor), canlı önizleme, ekle/kopyala/sil |
| Entegrasyonlar | PayPal (ID/Secret/mod + bağlantı testi), GA4/GTM/Ads + 3 dönüşüm etiketi, Meta Pixel, Search Console/Bing, serbest anahtar listesi (`Integrations::value('AD')`) |
| **Kişiye özel davetiye** | `src/Guests.php` + `invite_guests` tablosu. Davetiye **tek kayıt kalır**, üstünde ince bir katman: kişi/aile adı + kendi adresi (`/de/einladung/ayse-mehmet/familie-mueller`). Kartın üstünde „Liebe Familie Müller“ / „Sayın Müller Ailesi“, RSVP adı önceden dolu. Sihirbazda tek alan, sonrasında yönetim sayfası |
| Toplu misafir girişi | Satır satır yapıştırma, `.txt`/`.csv` yükleme. Excel başlığı (`Name`/`İsim`/`Misafir`…), `1.` `2)` `-` `•` numaraları, `"` tırnakları, `;`/tab sütunları ayıklanır; `Müller, Anna` tek isim sayılır (virgül sütun ayırıcı değil). Tekrarlar hem listede hem mevcut kayıtlara karşı elenir |
| Çiftin yönetim sayfası | `/einladung/{slug}/verwalten?schluessel=…` — hesap yok, gizli link var. Genel link + her misafirin linki, kopyala düğmesi, hazır metinli **WhatsApp** düğmesi, isim ekleme/çıkarma, önizleme görseli değiştirme. Sihirbaz bitince link ekranda |
| WhatsApp / OG önizlemesi | Her davetiye için `og:title` = „Ayşe & Mehmet – Hochzeitseinladung“, `og:description` = tarih · mekân, `og:image` = 1200×630 (ilk fotoğraftan kırpılıp hafif gölge + çerçeveyle üretilir, önbelleğe alınır), `og:image:width/height`, `twitter:card`. Müşteri kendi görselini yükleyebilir, istediğinde geri alabilir |
| Davetiye | Sihirbaz (tek form, 5 adım, JS'siz de çalışır), tema seçimi, canlı önizleme, kupon (sunucuda), taslak + devam linki, fotoğraf yükleme, davetiye sayfası (zarf animasyonu, geri sayım, program, menü, harita, müzik), RSVP, PayPal turu |

Hepsi yerelde MariaDB 10.4'e karşı test edildi: kaydetme, ekleme, sıralama, silme,
çift dilli satır alanları (bir dili düzenlerken diğeri korunuyor), fotoğraf
yükleme/silme (dosyalar dahil), müşteri + galeri + kupon döngüsü, davetiye ve
taslak silme, misafir listesi ayrıştırma (7 biçim), kişiye özel link + hitap,
OG etiketleri ve 1200×630 görsel üretimi, gizli yönetim linki (yanlış anahtar 404,
sahte CSRF 403). Panel + genel sayfalar, iki dil: hepsi uyarısız 200.

## Güvenlik

| Konu | Durum |
|---|---|
| Deneme bremsi | **Veritabanında**, gönderen başına (`throttle` tablosu). Eskiden oturumdaydı: çerezi atan bir betik sınırsız deneme yapabiliyordu — ölçtüm, 30 denemenin hepsi geçiyordu. Artık geçmiyor. IP'nin kendisi değil, karması saklanıyor |
| Yönetim parolası | `config.php`'de düz metin **ya da `password_hash`** olabilir (`$2y$…` görürse `password_verify`). Zayıf/varsayılan parolada panelde kırmızı uyarı çıkıyor. 8 deneme / 15 dk |
| Yönetim oturumu | 4 saat hareketsizlikte, her hâlükârda 12 saatte kapanıyor. Girişte oturum kimliği yenileniyor |
| CSRF | Yazan her uç noktada token var (panel, iletişim, galeri seçimi, RSVP, davetiye sihirbazı, misafir listesi) |
| Başlıklar | `src/Http.php` her yanıta koyuyor: CSP, `nosniff`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, canlıda HSTS. `.htaccess`'e güvenmiyor — `mod_headers` her sunucuda açık değil |
| CSP | `script-src 'self'` + JSON-LD için tek kullanımlık nonce. Ölçüm hostları **yalnızca panelde bir kimlik girilmişse** ekleniyor; ölçüm kapalıyken politika dar kalıyor. Kodda hiç `onclick=` yok, tüm betikler dosya — o yüzden sıkı tutulabildi. `frame-src` yalnız YouTube/Vimeo/Google Maps |
| Yükleme | Tür dosya **içeriğinden** belirleniyor, GD ile yeniden kodlanıyor, uzantı bizden. Üstüne `public/uploads/.htaccess`: PHP motoru kapalı, handler'lar kaldırılmış, `sandbox` CSP. (`.gitignore`'da istisna var, dosya sunucuya gidiyor) |
| SQL | Her sorgu hazırlanmış ifade; dize birleştirme yok |
| XSS | Şablonlarda çıktı `e()` ile; ham HTML yalnız sunucunun kendi ürettiği bloklar |
| Açık yönlendirme | Giriş sonrası dönüş adresi `//baska-site` olamıyor |
| Host başlığı | `Config::url()` istekten gelen host'u süzüyor (davetiye linkine ve e-postaya giriyor). Canlıda `site_url` zaten dolu olmalı |
| API anahtarları | PayPal/Maps sunucuda kalıyor; tarayıcıya yalnız `publicTracking()` gidiyor. Harita görseli bile kendi adresimizden geçiyor |
| Bilinen ödün | **Galeri parolaları düz metin saklanıyor.** Bilerek: çifte parolasını söyleyebilmek için fotoğrafçının onu görmesi gerekiyor. Değeri düşük bir sır (kendi fotoğraflarına erişim). Hash'lenmesi istenirse „parola bir kez gösterilir“ akışına geçilmeli |

## Diller — 16/17 Ağustos'ta değişti

**Site artık Almanca + İngilizce. Türkçe siteden kalktı.**
**Panel Almanca + Türkçe** — müşteri Türkçe konuşuyor, sitenin ziyaretçisi konuşmuyor.

İki ayrı dil kümesi var (`src/I18n.php`):

```php
LOCALES       = ['de', 'en']   // site
ADMIN_LOCALES = ['de', 'tr']   // panel
```

`public/index.php` bunları iki ayrı kapıyla ayırıyor: `$page_` site rotalarını,
`$admin_` panel rotalarını süzüyor. Sonucu: `/tr/admin` **var**, `/tr/preise` **yok**.
Panelin sağ üstünde DE/TR seçici, bulunduğunuz sekmede kalıyor.

`I18n::raw()` artık eksik anahtarda **Almancaya düşüyor**. Önceden anahtarın
kendisini yazdırıyordu (`nav.prices` menüde görünürdü). Şu anki durumu
kullanılabilir kılan şey bu.

### ⚠️ Yarım kalan: arayüz çevirisi

**313 sözlük girdisi henüz İngilizceye çevrilmedi.** `data/dict.php` içinde
`de` ve `tr` bölümleri var, `en` bölümü **yok**. Bu yüzden İngilizce sayfalar
şu an Almanca metin gösteriyor (ham anahtar değil — düşme mekanizması çalışıyor).

Yapılacak: `data/dict.php` içine `'en' => [...]` bölümü, `de` bölümünün
İngilizce karşılığı. `tr` bölümü **silinmemeli** — paneli o besliyor.

İçerik alanları (şehir, mekân, portfolyo metinleri) ayrı bir iş ve müşteri
kararıyla **sonraya bırakıldı**: panelde artık DE + EN çifti düzenleniyor
(`.tr` yolları `.en` oldu, 43 yol + 89 etiket), İngilizce alanlar boş.
Boş kalınca sayfa Almancayı gösteriyor. Veritabanındaki eski Türkçe içerik
`.tr` altında duruyor ama artık hiçbir yerde okunmuyor — ölü veri.

Panelde İngilizce alanlar **kiremit kırmızısı** çizgi ve etiketle ayrılıyor
(`src/Form.php`), Almanca cümleyi yanlış kutuya yazmayı zorlaştırmak için.

## Panel düzeni — aynı tarihte değişti

Yan menü artık **Site / Galeri / Davetiye / Sistem** olarak gruplu (`src/Admin.php`).
Eskiden İçerik/İşler/Görünüm/Ayarlar'dı — o, yapanın sırasıydı; bu, işletenin.
"Temalar" → **Tasarımlar** ve Davetiye grubuna taşındı (davetiye kartının
tasarımı, sitenin değil). "Müşteriler" → **Müşteriler & galeriler**.

Sabit alanlı sekmelerde artık Kaydet'in yanında **"Sayfayı gör"** var
(`templates/admin/content.php` + `ContentAdminController::handle()`'ın
beşinci parametresi). Liste sekmelerinde zaten vardı.

Yeni sekme: **Site → Görseller** (`/admin/bilder`). Sabit sayfaların sekiz
görsel yuvası (`Images::SLOTS`) panelden değiştirilebiliyor. `Images::img()`
önce panele bakıyor, yoksa temsili görsele düşüyor. Yükleme `Media::store()`
ile, yani orijinali de saklanıyor.

## Kalan — bu sırayla

### 1. Modüler temada kalanlar (küçük)

Motor ve panel hazır. Yapılmayanlar:
- Süsleme konumunu **sürükleyerek** ayarlama (şu an sayıyla; sayı kesin, sürükleme hızlı)
- AVIF (`imageavif` PHP 8.1+ ve libavif ister — ALL-INKL'de kontrol edilmeli; WebP zaten çalışıyor)
- Panelde „bu temanın eski sürümüne bağlı N davetiye var“ sayacı ve toplu güncelleme
  (tekil `refreshTheme()` hazır)

### 2. ALL-INKL'e yayın
1. KAS → veritabanı oluştur, `schema.sql` içe aktar (phpMyAdmin)
2. `config.example.php` → `config.php`, veritabanı + `admin_key` + `mail_to` doldur
3. Dosyaları FTP/SSH ile yükle; alan adının kök klasörü **`public/`** olmalı
   (KAS'ta ayarlanamazsa bir üst dizine yönlendiren ikinci `.htaccess` gerekir)
4. `node ../scripts/export-to-php.mjs` → `php bin/import.php` ile içerik + galeriler.
   İçe aktarım artık `site_content` id=2'ye **dokunulmamış bir kopya** da yazıyor;
   paneldeki „öncesi / geri al“ bunu kullanıyor. Zaten kurulu bir sistemde bir
   kez elle: `php -r 'require "src/bootstrap.php"; Atelier\Content::saveOriginal(json_decode(file_get_contents("data/export.json"), true)["content"]);'`
5. Let's Encrypt (KAS'ta tek tık), `uploads/` yazılabilir olmalı (755)
6. İsteğe bağlı: Google Cloud Console'da **Places API (New)** + **Maps Static API**
   açıp anahtarı Entegrasyonlar sekmesine gir (mekân arama için; birkaç düzine
   mekân ücretsiz kotada kalır). Anahtarı **HTTP referrer ile değil, sunucu IP'si
   ile** kısıtla — çağrılar sunucudan gidiyor
7. **GD açık mı kontrol et** (`phpinfo()`), yoksa görseller küçültülmeden yüklenir
8. Test listesi: iki dil, iletişim formu e-postası, galeri girişi, davetiye oluşturma,
   PayPal sandbox turu, sitemap, robots, 404

### 3. Krumbach — kalanlar

- **7 mekân metni** — mekân listesi hâlâ müşteriden bekleniyor. Şehirler hazır,
  mekânlar `venues` listesine eklenince şehir sayfalarındaki `venues` alanına
  bağlanacak
- **Mekânlar, portfolyo ve rehber hâlâ Stuttgart demo verisi.** Bunlar gerçek
  fotoğraflara ve referanslara bağlı; uydurma çekim hikâyesi yazmak görünür
  demo veriden kötü olurdu. Silinen şehirlere bakan 6 iç bağlantı `stuttgart`e
  yönlendirildi, kırık link yok
- **Adres ve telefon hâlâ eksik**: posta kodu ve şehir Krumbach (86381) yapıldı,
  **sokak ve telefon boş** — uydurulmadı, müşteriden gelecek. Impressum yasal
  olarak bunları gerektiriyor

## Yerelde çalıştırma

```bash
# 1) MariaDB (XAMPP)
"C:/xampp/mysql/bin/mysqld.exe" --defaults-file="C:/xampp/mysql/bin/my.ini" --standalone

# 2) Veritabanı + şema (ilk kurulumda)
"C:/xampp/mysql/bin/mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS atelier_dev CHARACTER SET utf8mb4"
"C:/xampp/mysql/bin/mysql.exe" -u root atelier_dev < php/schema.sql

# 3) Veri (Next sürümündeki Neon'dan)
node scripts/export-to-php.mjs
cd php && php bin/import.php

# 4) Sunucu ve stil
php -S 127.0.0.1:8080 -t public public/dev-router.php
npx @tailwindcss/cli -i php/assets/app.css -o php/public/assets/style.css --minify
```

`php/config.php` yerelde: `db_name=atelier_dev`, `db_user=root`, `db_pass=`, `dev=true`,
`admin_key=demo`. Bu dosya git'e girmez.

Panel: `http://127.0.0.1:8080/de/admin` · parola `demo`
Galeri demo: `elif-marco` / `solitude24`

## Tuzaklar (tekrar düşmemek için)

- **`View::capture` içindeki değişken adları `__` ile başlar** — sebebi var:
  `extract(..., EXTR_SKIP)` var olan değişkenin üstüne yazmaz. Parametre `$data`
  olduğu için `data` anahtarı şablona hiç ulaşmıyordu; şablon onun yerine tüm
  aktarım listesini görüyordu. Sonuç: **panelde bütün alanlar boş açılıyordu**
  ve „Kaydet“ o sekmeyi silecekti. Buraya yeni bir parametre eklerken aynı
  tuzağa dikkat
- **Şablona sınıf eklediysen Tailwind'i yeniden derle**, yoksa stil yok.
  `assets/app.css` artık `../src` klasörünü de tarıyor: panel formları sınıfları
  PHP içinde üretiyor (`ListAdminController`'daki `md:grid-cols-4` gibi)
- `„…"` yazarken kapanış tırnağı düz `"` olursa PHP string'i erken kapanır → kapanış `"`;
  JSX/HTML metninde `&bdquo; &ldquo;`
- Toplu dosya düzenlemesini **bash heredoc ile yapma**: `\1` gibi kaçışlar bozuluyor.
  Betiği dosyaya yaz, `PYTHONIOENCODING=utf-8 python betik.py` ile çalıştır
- Form içindeki yükleme/yardımcı düğmeler `type="button"` olmalı
- **İç içe form olmaz**: fotoğraf yükleme ve silme formları, düzenleme formunun
  *dışında* durmalı (`templates/admin/list.php` böyle kurulu)
- PHP'nin yerleşik sunucusu statik dosyaları `public/dev-router.php` olmadan vermez
- Aynı porta ikinci sunucu açılırsa eskisi cevap vermeye devam eder — `netstat -ano | grep 8080`
- Ölçümü denerken **tarayıcıda `localStorage.removeItem("al-consent-v1")`**,
  yoksa banner bir daha çıkmaz. İzinsizken hiçbir dış istek olmaması
  `performance.getEntriesByType("resource")` ile kontrol edilebilir
- Tema değiştirince **yayınlanmış davetiyeler değişmez** — bu kasıtlı. Test
  ederken „neden güncellenmiyor“ diye aramayın; `Invitations::refreshTheme()`
  bilerek günceller
- Süsleme yüklerken `Media::store()` **değil** `storeGraphic()` kullanılmalı;
  ilki her şeyi JPEG yapar ve saydamlığı yok eder
- **Bremsi denemek istersen** `DELETE FROM throttle` — yoksa kendi testlerin
  seni kilitler. Sınır aşıldığında doğru parola da kabul edilmez, bu kasıtlı
- **XAMPP'ta GD kapalı gelebilir**: `C:\xampp\php\php.ini` içinde `;extension=gd`
  satırındaki noktalı virgülü kaldır, sunucuyu yeniden başlat. Kapalıyken görseller
  küçültülmeden saklanır (artık hata vermez ama 6000 px dosyalar birikir)
- **Windows'ta PHP `/tmp` yolunu bilmez** (Git Bash bilir). Test dosyalarını
  `C:/Users/.../Temp/...` gibi tam yolla yaz, yoksa `file_put_contents` sessizce patlar
- MySQL komut satırı `€` ve `ı`'yı `?` gösterir — bu ekran sorunu, veri doğru.
  Kontrol için `php -r` ile oku
- **Türkçe büyük İ**: `mb_strtolower("İsim")` → `i` + ayrı nokta (U+0307), hiçbir
  kelimeyle eşleşmez. Karşılaştırmadan önce `str_replace(['İ','I','ı'], 'i', …)`
  ve U+0307 temizliği gerekir (`Guests::isHeading` böyle yapıyor)
- Yeni rota eklerken **sıra önemli**: `/einladung/{slug}/{gast}` deseni
  `/zahlung` ve `/verwalten`'i yutar, o yüzden onlardan **sonra** kayıtlı.
  Ayrıca bu kelimeler misafir adresi olarak yasak (`Guests::RESERVED`)
- Test ederken `curl -F "alan=çok
  satırlı değer"` kabuk tarafından bozulabilir — çok satırlı gönderiler için
  değeri bir dosyaya yazıp `-F "alan=<dosya"` ya da `--data-urlencode` kullan
- Neon (Next sürümü) boşta uyur; ilk sorgu yavaş olabilir

## Nerede ne var (panel)

```
src/Admin.php                    sekme listesi (TABS), giriş, CSRF, geri yönlendirme
src/Form.php                     alan tanımından form üretir ve geri okur
src/Lists.php                    içerik listelerinde ekle/sil/sırala/yükle
src/Content.php                  içerik dokümanı (tek JSON kaydı)
src/Customers.php                müşteri + kupon; galeriyle senkron
src/Guests.php                   kişiye özel davetiyeler, liste ayrıştırma
src/Texts.php                    sözlük üstü metin katmanı (sayfa metinleri)
src/Places.php                   Google Places: yer arama, detay, statik harita
src/Themes.php                   modüler tema: renk/font/süsleme/hareket + versiyon
src/Media.php                    store() foto=JPEG · storeGraphic() süsleme=WebP+alfa/SVG
src/Http.php                     güvenlik başlıkları + CSP (nonce burada üretilir)
templates/partials/consent.php   çerez kutusu (sunucuda gizli, JS gösteriyor)
public/assets/consent.js         izin mantığı, Consent Mode v2, dönüşümler
src/Security.php                 oturum, CSRF, veritabanı tabanlı deneme bremsi
src/OgImage.php                  WhatsApp önizleme görseli (1200×630, önbellekli)
src/Controllers/
  AdminController.php            genel bakış, temalar, entegrasyonlar
  ContentAdminController.php     sabit alanlı sekmeler (metinler, paketler, SEO…)
  ListAdminController.php        liste sekmeleri (hizmet, şehir, mekân, portfolyo, rehber)
  CustomerAdminController.php    müşteri listesi ve müşteri kartı
  TextAdminController.php        sayfa metinleri sekmesi
  InviteAdminController.php      davetiyeler, RSVP'ler, misafirler, taslaklar
  InviteController.php           sihirbaz, davetiye sayfası, ödeme,
                                 `manage()` = çiftin misafir listesi
templates/admin/                 layout, login, overview, content, list, customers,
                                 customer, customer-missing, invitations, themes,
                                 integrations
templates/admin/place-panel.php   mekân sekmesindeki Google yer arama paneli
templates/pages/invite-manage.php  çiftin misafir listesi (müşteriye görünen yüz)
public/assets/invite-manage.js     link kopyalama
```

## Müşteriden bekleyenler

Panelden kendisi girebilecekleri (bizden bir şey gerekmiyor):
- **Adres ve telefon** → Metinler & iletişim. Şu an **boş** ve yayın kontrolü
  bunu kırmızı gösteriyor; Impressum yasal olarak istiyor
- **Gerçek fotoğraflar** → Portfolyo, Rehber, Müşteriler, Temalar sekmeleri
- **PayPal Client ID + Secret** → Entegrasyonlar (bağlantı testi düğmesi var).
  Hesap: `akyel.business@gmail.com`, Business olmalı
- **Google Maps anahtarı** → Entegrasyonlar (mekân arama için, isteğe bağlı)

Bizden metin gerektirenler:
- **Krumbach bölgesi mekân listesi** — 7 mekân metni bunu bekliyor. Şehirler hazır
- Portfolyo/rehber hâlâ Stuttgart demo verisi; gerçek çekimler gelince yazılır

Yayın için:
- **ALL-INKL KAS erişimi.** Parolayı sohbete yazmayın — aşağıdaki nota bakın
