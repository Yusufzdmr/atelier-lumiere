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
| İskelet | Router, şablon motoru, PDO katmanı, üç sözlük — site `de`+`en`, panel `de`+`tr`, her biri 315 anahtar — oturum/CSRF, `.htaccess` |
| Genel sayfalar | Ana sayfa, hizmetler, fiyatlar, portfolyo + hikâye, bölgeler, 10 şehir, mekân listesi + 7 mekân, rehber + yazılar, hakkımda, iletişim (form + e-posta + kayıt), Impressum/Datenschutz/AGB, sitemap.xml (74 kayıt), robots.txt |
| Müşteri galerisi | Giriş, fotoğraf ızgarası, kalple seçim, lightbox, seçim gönderimi (veritabanı + e-posta) |
| Görsel yükleme | `src/Media.php` — GD ile 1600 px JPEG, tür dosya içeriğinden, silme upload klasörüyle sınırlı. **GD yoksa** dosya küçültülmeden olduğu gibi saklanır (hata sayfası yerine) |
| Video | YouTube/Vimeo, iki tıklamalı (izin öncesi sağlayıcıya istek yok) |
| **Yönetim paneli — 16 sekmenin hepsi** | Giriş (deneme sınırlı), Genel bakış, Metinler & iletişim, **Sayfa metinleri**, Fiyatlar & paketler, **Hizmetler & süreç**, **Şehirler**, **Mekânlar**, **Portfolyo**, **Rehber**, **Müşteriler**, **Davetiyeler**, Temalar, Hakkımda & yorumlar, Yasal metinler, SEO & meta, Entegrasyonlar |
| Liste düzenleyicisi | `src/Lists.php` + `templates/admin/list.php` + `src/Controllers/ListAdminController.php` — şehir/mekân/portfolyo/rehber/hizmet aynı kalıptan: aç, düzenle, kaydet, ekle, sırala (↑↓), sil. Her kayıt kendi formunda (10 şehir tek düğmeye gitmez) |
| Müşteriler | `src/Customers.php` + `CustomerAdminController` — kayıt açınca galeri **otomatik** oluşur (parola ve kupon otomatik üretilir, galeri bitişi düğün + 2 yıl). Fotoğraf yükleme/silme, çiftin seçimi (kalpli kareler + notu), kupon yönetimi (kod/aktif/tek kullanım/son tarih/yeni kod/yeniden aç), arşivle, giriş adını yazdırarak kalıcı sil |
| Davetiyeler | `InviteAdminController` — davetiye listesi, ödendi/kupon/ödenmedi rozeti, RSVP'ler (kabul/ret/kişi sayısı + notlar), **kişiye özel davetiye listesi + çiftin yönetim linki**, müşteri kaydına bağlantı, yarım kalan taslaklar, silme |
| **Sayfa metinleri** | Yeni sekme (`/admin/texte`). Sözlükteki 315 metin — bölüm başlıkları („Was wir für euch tun“), düğme yazıları, form etiketleri — 17 grup halinde, sitenin iki dilinde (DE + EN) düzenlenebilir. `src/Texts.php` bir **üst katman**: sözlük dosyası hiç değişmiyor, yalnızca farklı olan saklanıyor. Bir alanı boşaltmak = ilk metne dönmek |
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

### Arayüz İngilizcesi — bitti (17 Ağustos)

`data/dict.php` artık üç bölümlü: `de`, `en`, `tr` — üçü de **315 anahtar**,
küme birebir aynı. `tr` duruyor, paneli o besliyor.

Sözlük işin yarısıymış. Site eskiden Almanca + Türkçe olduğu için şablonlarda
yaklaşık **yüz satır içi ikili** vardı (`$de ? 'Deutsch' : 'Türkçe'`). Site
İngilizceye geçince o „değilse“ dalı İngilizce sayfalara **Türkçe** basmaya
başlamıştı: davetiye sihirbazı „Bilgileriniz“ diyordu, iletişim formu „Sadece
fotoğraf“ seçeneği sunuyordu. Hepsi çevrildi.

İkisi metin hatası değil, **eksik anahtardı**:

- `invite-wizard.php` içindeki `$eventTypes` ve `$sectionLabels` dizileri
  `[$locale]` ile okunuyor. `'tr'` anahtarı vardı, `'en'` yoktu → İngilizce
  sayfada o etiketler **boş** çıkıyordu
- `Dates::MONTHS` ve `WEEKDAYS` yalnızca `de` ve `tr` biliyordu → İngilizce
  sayfalarda ay adının yeri **boştu** („14  2026“)

Bunlar da sözlüğe alındı: `common.skip` (şablonda sabit „Skip“ vardı, Almanca
sayfada bile), `contact.phoneLabel` (sabit „Telefon“). Ayrıca `Guests::salutation`,
`Invitations::kindLabel`, `PageController`, `InviteController` ve iki JS dosyası
(`invite-manage.js` kopyalama bildirimi, `app.js` harita başlığı).

Örnek isimler bilerek duruyor: canlı önizlemede Ayşe & Mehmet, misafir listesi
örneğinde Yılmaz. Onlar arayüz değil, isim.

### İçerik metinleri — bitti (17 Ağustos)

Müşteri „hepsini çevir" dedi, çevrildi. **431 iki dilli alanın hepsinde
İngilizce var, boş alan sıfır** (~10.100 İngilizce kelime):

| Bölüm | Alan |
|---|---|
| Şehirler (10 sayfa) | 110 |
| Mekânlar (7 sayfa) | 100 |
| Rehber yazıları (3) | 21 |
| Portfolyo (5) | 22 |
| Hizmetler / paketler / ek hizmetler | 31 |
| Hakkımda, SSS, süreç, yorumlar, ana sayfa | 40 |
| SEO başlıkları & açıklamaları | 22 |
| Temalar, sayfa metni istisnaları | 18 |
| **Yasal metinler** (Impressum / Datenschutz / AGB) | 66 |

Şehir metinlerinde **her sayfanın kendi ayrıntısı korundu** — çeviri
tektipleştirmedi. Bu şart: `bin/cities.php` metinler %55'ten fazla benzerse
içe aktarmayı reddediyor, ve doorway page riski İngilizce tarafta da aynı.

Yasal metinler için veri yapısı değişti: `title`, `heading`, `body`, `note`
düz metindi, artık `{de, en}`. `LegalText::render` `I18n::pick` kullanıyor
(düz metni de kabul ediyor, yani eski veri kırılmıyor), panel DE + EN çifti
gösteriyor. İngilizce sayfaların üstünde **koddan gelen** bir uyarı var:
„This English version is a convenience translation. Only the German version
is legally binding." Panelden silinemesin ve Almancadan sapmasın diye
`LegalText::BINDING` sabitinde duruyor.

Veritabanındaki eski Türkçe içerik `.tr` altında duruyor ama artık hiçbir
yerde okunmuyor — ölü veri.

**Sunucuya nasıl gidiyor:** çeviri veritabanında yaşıyor, `php bin/export.php`
onu `data/inhalte.sql`'e döküyor ve o dosya git'te. Yani çeviriyi taşımak için
elle bir şey girmek gerekmiyor.

Panelde Almanca ve İngilizce **yan yana iki sütunda** duruyor: alan adı bir kez
yazılıyor, altında solda „Deutsch“ sağda „English“ (`src/Form.php`, `pair()`).
Eskiden alt alta duruyorlar ve İngilizce olanı ince bir kırmızı çizgi ayırıyordu
— akşam hızlıca bir metni değiştiren o çizgiye bakmıyor ve Almanca cümleyi
İngilizce kutuya yazıyordu. Eşleşme **yoldan** tanınıyor (`.de`/`.en` ile biten
ardışık iki alan), o yüzden bütün sekmelerde birden geçerli; kontrolörlerde
hiçbir şey değişmedi.

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

## 17 Ağustos'ta yapılan iki iş

Önceki notta sırada duran ikisi de bitti: arayüz İngilizcesi (yukarıda, „Diller“
başlığı altında) ve aşağıdaki görünürlük anahtarları.

### Görünürlük anahtarları

Şehir ve mekân kayıtlarında iki **bağımsız** anahtar var. Panelde her kaydın
kendi „Görünürlük“ bölümünde:

| Alan | Kapalıyken |
|---|---|
| `listed` | Bölgeler / mekânlar listesinde, ana sayfada ve alt bilgide çıkmaz. Sayfa yaşar, adresi çalışır |
| `indexed` | `sitemap.xml`'den çıkar ve sayfanın kendisi `noindex` alır |

Okuma tarafı `Content::listed('cities')` ve `Content::shows($item, 'indexed')`
üzerinden. **Alan yoksa açık sayılır** — 10 mevcut şehir bu yüzden kaybolmadı.

Buradaki tuzak kutucuktaydı: veri alanı tanımıyor, kod „yok = açık“ okuyor, ama
form **boş kutucuk** çiziyordu. Bir kez kaydet, anahtar kendiliğinden kapanır.
`Form` artık `check` alanlarında `'default' => true` kabul ediyor; alanı hiç
görmemiş kayıt **işaretli** açılıyor. Yeni bir alan eklerken aynı şey geçerli.

Panelde liste satırında adresin yanında „nicht gelistet / listede yok“ ve
„nicht bei Google / Google'da yok“ yazıyor — yoksa gizli şehir görünenden
ayırt edilemiyor.

> **Uyarı — müşteriye de söylendi, değişmedi:** 100 şehir sayfası Google'ın
> *doorway pages* saydığı şeye çok yakın. `bin/cities.php` metinler %55'ten
> fazla benziyorsa içe aktarmayı **reddediyor**. Anahtarlar bu korumayı
> kaldırmaz.

### Okunabilirlik — 17 Ağustos

**Ana sayfa başlığı fotoğrafın üstünde kayboluyordu.** Müşteri bildirdi, ölçtüm:
başlığın arkasındaki perde üstte %55'ten başlayıp başlığın **alt kenarında**
şeffafa iniyordu — yani „ATELIER LUMIÈRE“nin altındaki satır tam perdenin
bittiği yerde duruyordu. Beyaz bir fotoğrafta kontrastı **1.3:1**, yani soluk
değil, yok.

Düzeltme (`assets/app.css`): perde başlığın 3rem altında bitiyor
(`bottom: -3rem`), %72'den %52'ye iniyor, alt satırın kendi saydamlığı
%72 → %90, ve iki satıra `text-shadow` eklendi. Gölge şart: perdeyi tek başına
yeterince koyulaştırmak, iki satırı kurtarmak için **bütün fotoğrafı**
karartmak demekti. En kötü hâlde (bembeyaz fotoğraf) isim 3.7:1 → **6.7:1**,
alt satır 1.3:1 → **3.1:1**.

**Tasarım editöründe kontrast uyarısı.** Müşterinin ilk fikri „her yazının
yanına renk kodu“ydu; kod girmesi gerekmeyen hâli yapıldı.
`Themes::readability()` dört çifti ölçüyor, okunmuyorsa panelde kırmızı kutu
çıkıyor (DE + TR):

| Çift | Sınır | Neden |
|---|---|---|
| `fg` / `paper` | 4.5:1 | Kartın gövde yazısı — WCAG sınırı |
| `soft` / `paper` | 3.0:1 | İkincil yazı (tarih, notlar) |
| `sealText` / `seal` | 3.0:1 | Mühürdeki harfler — küçük ama yazı |
| `accent` / `paper` | **1.8:1** | Çizgi ve „&“ — süs. Burada 4.5 istemek yanlış olurdu: soluk altın bir düğün davetiyesinde **kasıt**, hata değil. Her temada çıkan uyarıyı üçüncüden sonra kimse okumaz |

İki ayrıntı: karşılaştırma `bg` ile değil **`paper`** ile yapılıyor (kart yazısı
kâğıdın üstünde duruyor, sayfa arkasının değil), ve `soft` rengi `rgba(...)`
olduğu için saydamlık önce arka planla harmanlanıyor — yoksa hiç görünmeyen bir
renk ölçülmüş olurdu. Şu an 16 temanın 4'ünde uyarı var, hepsi mühür harflerinde.

## Demo sunucusu (17 Ağustos)

**https://45-147-46-177.sslip.io** — müşterinin bakması için, geçici.

| | |
|---|---|
| Sunucu | VPS 45.147.46.177, Ubuntu 22.04, nginx 1.18, PHP 8.3, MySQL 8 |
| Erişim | `ssh atelier-vps` — anahtarla, parolasız (`~/.ssh/config`) |
| Klasör | `/var/www/atelier` |
| Adres | `sslip.io` IP'yi alan adına çeviriyor → Let's Encrypt sertifikası çıktı, **DNS kaydı gerekmedi** |
| Parolalar | Sunucuda üretildi, sohbetten geçmedi: `/root/.atelier-dbpw`, `/root/.atelier-adminpw`. Panel parolasını değiştirmek için `/root/atelier-parola` |

> **Aynı makinede `gidonla.com` canlı yayında.** Her adım eklemeli yapıldı:
> ayrı nginx bloğu (mevcut dosyaya dokunulmadı), `reload` (restart değil),
> güvenlik duvarına dokunulmadı, certbot yalnız yeni ada verildi. Kurulumdan
> sonra da kontrol edildi: gidonla 200.

Çıplak IP **gönderilmemeli**: Chrome `http`'yi `https`'e yükseltiyor ve 443'te
gidonla'nın sertifikası duruyor → sertifika uyarısı. `sslip.io` adresi bu yüzden.

Demoda `noindex` **açık**, `robots.txt` her şeyi kapatıyor. Müşteri verisi
taşınmadı (`bin/export.php` bilerek atlıyor); yerine uydurma bir örnek müşteri
var: galeri `beispiel-demo`.

Kod güncellemesi: `tar czf … | scp` → sunucuda `tar xzf`.
**msys2'nin `rsync`'i Windows'ta `ssh` açamıyor** (`dup() in/out/err failed`).

## 19 Ağustos — Davetiye v2 Faz 1 demo sunucusunda

`davetiye-v2-phase-1` dalı `master`'a alındı ve VPS demosuna yüklendi
(https://45-147-46-177.sslip.io). Yükleme yine `tar` + `scp`; 150 dosya.
Yükleme öncesi yedek: `/root/atelier-yedek-20260819-1013.tar.gz`.
`config.php` ve `public/uploads/` pakete alınmadı, o yüzden ikisine de
dokunulmadı. Komşu `gidonla.com` kontrol edildi: 200, nginx aktif.

**Veritabanı 12 → 15 tablo.** `schema.sql` baştan sona `CREATE TABLE IF NOT
EXISTS`, mevcut veriye dokunmadan üçünü kurdu: `designs`, `invitations_v2` ve
bir de `admin_usage` — sonuncusu panel kullanım sayacının tablosu, 17 Ağustos
yüklemesinden sonra eklendiği için sunucuda hiç yoktu. Şema tek satır SQL
parolası açığa çıkmadan `config.php` üzerinden PDO ile koşuldu.

Sonra `php bin/seed-designs.php` — Élysée belgesi 14 katmanla kuruldu.
Doğrulandı: `/de/v2/designs`, `/de/v2/designs/elysee`, `/tr/admin/designs`
200; bilinmeyen slug 404; eski yolların hepsi (ana sayfa, eski katalog, eski
davetiye sihirbazı, panel) 200.

### Canlıdaki renkler siyahtı — geri alındı

Yükleme sonrası isimler altın değil siyah çıktı. Sebep kodda değil **içerikte**:
canlı `site_content` içinde `elysee` temasının `accent`, `fg` ve `soft` alanları
`#000000` idi. Eski sayfa da aynı şekilde koyu basıyordu, yani v2 veriyi
sadakatle yansıtıyordu.

Kod aklandı: kaydetme yolu (`AdminController` → `Security::clean(...) ?: eski
değer`) kendiliğinden asla siyah yazmıyor, `admin.js` renk seçiciyi yalnızca
kullanıcı girdisinde metin alanına kopyalıyor, ve 18 Ağustos'ta sunucuda
çalışan şablon bugünküyle aynı (iki ayrı alan: seçici + metin). Bozulan yalnızca
o gün panelde elle dokunulan dört kayıt — `elysee`, `elysee-variante`, `test-2`,
`1a-test` — ve hep aynı üç alan.

`elysee` Yusuf'un onayıyla belgelenmiş değerlerine döndürüldü
(`accent #B08D57`, `fg #221C16`, `soft rgba(34,28,22,0.58)`; kaynak
`data/themes.php` ve `data/inhalte.sql`). Üç deneme temasına **dokunulmadı**.

> **Bunu bir daha yaşarsan:** v2 tasarım belgesi paleti temadan **kopyalayarak**
> saklıyor. Temanın rengini değiştirdikten sonra `php bin/seed-designs.php`
> yeniden koşmazsan v2 eski rengi göstermeye devam eder. Bu, Faz 2'de panelden
> renk düzenlenince otomatik yapılmalı.

ALL-INKL tarafı değişmedi: kod orada duruyor ama `config.php` hâlâ yok, yani
site ayağa kalkmış değil. `atelier-lumiere.de` de checkdomain'de park hâlinde
(sertifikası `*.checkdomain.de`), `atelier.newbornshooting-babydream.de` ise
kasserver'a düşüyor ama kendi sertifikası yok.

## 19 Ağustos — Davetiye v2 Faz 2: tasarım artık panelden düzenleniyor

Faz 1 belgeyi kurmuştu ama değiştirmenin tek yolu `php bin/seed-designs.php`'ti.
Faz 2 bunu panele taşıdı. Spec `docs/superpowers/specs/2026-08-19-davetiye-v2-faz2-panel-design.md`,
plan `docs/superpowers/plans/2026-08-19-davetiye-v2-faz2.md`, sekiz görev, hepsi bitti.

**Ne var:**

- **Katalog** (`/{locale}/admin/designs`) — kartlar tasarımın kendisini basıyor
  (`Design::css()` + `html()`, genel katalogla aynı yol). Kategori süzgeci
  adreste taşınıyor. Kart başına: düzenle, önizle, kopyala, aktif/pasif.
- **Editör** (`/{locale}/admin/designs/{slug}`) — sekiz bölüm (`design-edit.php`
  iskelet + önizleme, `design-edit-sections.php` alanlar), hepsi kapalı doğuyor.
- **Canlı önizleme** — `public/assets/design-editor.js`. Yalnızca CSS
  değişkenlerine ve metin düğümlerine dokunuyor; keyframe üretimi sunucuda
  kalıyor. Bunun mümkün olması için `Design::css()` artık yazı ağırlığını,
  laufweite'yi ve satır yüksekliğini `--dfw-*`, `--dft-*`, `--dfl-*`
  değişkenlerine yazıyor (eskiden her eleman kuralına sabit sayıydı).
- **Yayın** — uyarı sayısını sunucuda yeniden hesaplayıp soruyor, engellemiyor.
- **Yeni tasarım** — kopyalama, ve "temadan oluştur". İkincisi *yerleşimi bir
  tasarımdan, rengi/yazıyı/hareketi temadan* alıyor (`Design::dress()`), çünkü
  hiçbir temada kartın metin katmanları yok — onlar Faz 1'de elle ölçüldü.

**Fazın sınırı testle bekçili:** `tests/design_admin.php`, POST'a `box_x`,
`canvas_ratio`, `sections` ve komple bir katman listesi koyup belgenin
kımıldamadığını gösteriyor. Geometri Faz 4'e ait.

**Bu turda düzelen gerçek hatalar** (hepsi testli):

| Ne | Neydi |
|---|---|
| `Design::fromTheme()` | Temanın yazı markalarını hiç taşımıyordu; canvas `9:16` kalıyordu. Artık aileleri + ölçülmüş ağırlık/laufweite/satır yüksekliği ve `632:490` geliyor |
| `Design::key()` ve `Themes::slug()` | `strtolower` bayt bazlı olduğu için "Élysée"→`lysee`, "Şafak Işık"→`afak-isik`. İkisi de `mb_strtolower` + ortak harita |
| Format: aynalama | Eski motor `scale:-1 1` kullanıyor; kutuda yalnızca `rotate` vardı. `flipx`/`flipy` eklendi |
| Format: köşeye yapışma | Sağ/alt parçalar `100 - genişlik` diye hesaplanıyordu ve oran değişince kayıyordu (1920x1080'de 31 px). `anchor` eklendi |

**Test:** `php bin/test.php` → 269 kontrol.

**Yükleme:** demo sunucusuna (`45-147-46-177.sslip.io`) alındı, şema değişikliği
yok, yeniden tohumlama gerekmedi.

**Bekleyenler:**

1. **Panelde göz kontrolü** — geliştiricide panel şifresi olmadığı için editörün
   canlı önizlemesi ve iki sekmeli çakışma reddi giriş altındaki katmanda
   sınandı; formun kendi bağlantısını gözle görmek kaldı. Beş dakikalık tur:

   | Adım | Beklenen |
   |---|---|
   | `/de/admin/designs` aç | İki kart: Élysée ve Noir, kartlarda gerçek davetiye görünüyor, aktif olanın çerçevesi altın |
   | Üstteki `modern` süzgecine bas | Yalnızca Noir kalıyor; "Alle" geri getiriyor |
   | Élysée kartında **Bearbeiten** | Sekiz bölüm kapalı, sağda kartın küçük hâli |
   | **2 · Farben** aç, `accent` alanına `#AA0000` yaz | Sağdaki karttaki isimler **kaydetmeden** kırmızıya döner |
   | Renk seçiciyi tıkla, başka renk seç | Metin alanı da onunla değişir (iki yönlü) |
   | **3 · Schriften** → `display` ağırlığını 300'den 700'e al | Tarih satırı anında kalınlaşır |
   | **4 · Texte** → "Wir heiraten"i değiştir | Kartta anında değişir |
   | **Speichern** | "Gespeichert." ve altta sürüm bir artmış olur |
   | Hiçbir şey değiştirmeden tekrar **Speichern** | Sürüm **artmaz** (içerik aynı) |
   | Aynı tasarımı ikinci bir sekmede aç, birinci sekmede kaydet, sonra ikinci sekmede kaydet | İkincisi yazmaz: "Diese Vorlage wurde geändert…" |
   | Kataloğa dön, Noir'da **Deaktivieren** | Soru sormadan pasife alır, çerçeve altından çıkar |
   | Tekrar **Aktivieren** | Uyarısı yoksa tek tıkla açılır; uyarısı olan bir taslakta önce "N Hinweise. Trotzdem veröffentlichen?" diye sorar |
   | `/de/v2/designs/elysee` aç | Panelde kaydettiğin ne varsa burada da öyle görünür |

   Bir şey beklenenden farklı çıkarsa ekran görüntüsü yeter; hangi adımda
   olduğunu yaz.
2. **Kalp şeklinde harita** — `docs/backlog/2026-08-19-kalp-seklinde-harita.md`,
   Ayhan'a üç soru.
3. **Faz 3** — müşteri tarafı. Kendi spec'iyle başlamalı. Faz 2'den devredilen
   iki not: tasarım silme akışı ("bu tasarımdan kaç davetiye çıktı" sayacıyla)
   ve `texture` (her temada var, PHP tarafında kimse okumuyor, yalnızca Next.js
   sürümü kullanıyor).

## 17 Ağustos — kullanım kolaylığı ve SEO

Hepsi „sahibi teknik değil, telefondan yönetecek" ölçütüyle yapıldı.

- **Panelde DE/EN yan yana iki sütun** (`Form::pair()`). Eskiden alt alta
  duruyorlardı ve İngilizceyi ince bir kırmızı çizgi ayırıyordu — akşam hızlıca
  bir metni değiştiren ona bakmıyor. Eşleşme **yoldan** tanınıyor, o yüzden
  bütün sekmelerde birden geçerli
- **Fotoğraf silme düğmesi telefonda görünmüyordu.** `opacity-0` + `group-hover`
  ile yazılmıştı; telefonda hover yok, yani düğme sahibinin kullandığı cihazda
  hiç yoktu. İki yerdeydi (`list.php`, `customer.php`), ikisi de düzeldi.
  **Yeni bir gizli kontrol yazarken bunu hatırla**
- **Bölge seçimi zorunluydu.** Mekân seçiminde „—" vardı, şehirde yoktu; her
  kayıt listedeki ilk şehre bağlanıyor ve koparılamıyordu (`cityOptions()`)
- **Kilit düğmesi** sağ üstte → panele giriş. Site DE+EN, panel DE+TR: `/en/admin`
  yok, o yüzden hedef hesaplanıyor (`I18n::isAdminLocale`)
- **Tasarım akışı ters çevrildi.** Artık: tasarımlara bak → „Bu tasarımla
  oluştur" → sihirbaz o tasarım seçili açılıyor (`?design=`, doğrulanarak;
  kayıtlı taslak öncelikli). Önizlemenin altında da aynı düğme, yalnız
  önizlemede (slug `vorschau`)
- **Formların yanına veri koruma cümlesi** (`contact.dataNote`,
  `invite.rsvpDataNote`, `invite.guestsDataNote`). Geçerli mevzuat **DSGVO**,
  KVKK değil — işletme Almanya'da

### SEO — yapılan ve kalan

**Kaldırıldı: uydurma değerlendirme.** `Seo.php` Google'a „4,9 puan, 87
değerlendirme" diyordu; ikisi de koda sabit yazılmış demo verisiydi. Sahte
değerlendirme işaretlemesi rich-result spam sayılıyor ve ceza **alan adına**
iniyor. Ayrıca tip `Photograph`'tı (sözlükte: tek bir fotoğraf) →
`PhotographyBusiness`. Boş alanlar artık gönderilmiyor.

Zaten doğru olan, dokunulmadı: canonical, hreflang (de/en/x-default), Open
Graph + Twitter card, rehberde `BlogPosting`+`FAQPage`+`BreadcrumbList`,
80 adresli sitemap, boş `alt` metni yok. **133 adres tarandı: kırık bağlantı
yok, PHP uyarısı yok.** 17 panel sekmesi de temiz.

Sıralamayı belirleyecek olanlar kod değil:

1. **Adres + telefon** — müşteri panelden girecek (17 Ağustos kararı). Yerel
   aramada en belirleyici veri; Google profiliyle harfi harfine aynı olmalı
2. **Google Business Profile + Search Console** kurulmadı
3. `og:image` hâlâ Unsplash'ten temsili görsel
4. Mekânlar ve portfolyo hâlâ Stuttgart demo verisi
5. Ana sayfadaki **„4,9 Google puanı" kutusu** hâlâ sayfada yazıyor —
   işaretlemeden çıkarıldı ama metin duruyor, müşteri kararı
6. Gerçek alan adına geçince `noindex` → `false`

## 17 Ağustos, öğleden sonra — müşterinin bildirdiği dört şey

Dördü de müşteri kullanırken çıktı. Hepsi yayında (demo sunucusu) ve commit'li.

**1. Yüklenen fotoğraf kapak olmuyordu.** „Resimler değişmiyor" diye bildirdi;
aslında değişiyordu ama baktığı yerde değil. Çekim sayfasının **galeri şeridi**
`$uploads ?: $seeds` ile doğru çalışıyordu, ama **kapak** (sayfanın üstündeki
büyük görsel), **portfolyo listesindeki kartlar** ve **„diğer çekimler"**
doğrudan `seeds[0]` okuyordu. Üç fotoğraf yükle, aşağıda gör, yukarıda hâlâ
stok fotoğraf. Üçü de artık galerinin kuralını kullanıyor
(`templates/pages/story.php`, `portfolio.php`).

**2. Kapak seçilebiliyor.** Kapak = `uploads[0]` olduğu için „kapak yap" =
**başa al** (`Lists::makeCover`). Yeni alan yok, göç yok, ve `[0]` okuyan her
yerde birden geçerli. Panelde her fotoğrafın üstünde düğme; kapak olanda düğme
yerine altın „Kapak" etiketi. Tarifi müşteriden: *100 fotoğraf yükleyip ilki
kötüyse hepsini silmek zorunda kalmayayım.*

**3. Yükleme yüzdesi** (`public/assets/upload.js`, panel düzeninde yükleniyor).
Sorun şuydu: bastı, bir şey görmedi, ekranı kapattı, yükleme iptal oldu. Artık
dosya seçili **her** panel formunda çubuk + yüzde çıkıyor, sayfayı kapatmaya
kalkarsa uyarı veriyor. %100'de „kaydediliyor" yazısına dönüyor — sunucu o an
görselleri küçültüyor, donmuş çubuk yalan olurdu. XHR/FormData yoksa sessizce
normal form gönderimine düşüyor.

> İki tuzak: eylem gizli `was` alanında gidiyor **ama** bazı sekmelerde
> düğmenin kendi `name="was"`'ı var („geri al" ile „kaydet" yan yana). Bu yüzden
> basılan düğme `event.submitter` ile okunuyor, ilk düğme tahmin edilmiyor.

**4. Görsel yuvaları yanlış sayfaya götürüyordu.** Görseller sekmesinde sekiz
yuva var, **altı ayrı sayfaya** ait — ama altta tek bir bağlantı vardı ve hep
ana sayfaya gidiyordu. „Designs, Kopfbild"i düzenleyip kontrol etmek isteyen
ana sayfada olmayan bir fotoğrafı arıyordu; kaydetme hatası sanılıyordu. Artık
her yuvanın kendi „Sayfayı gör" bağlantısı var (`Images::SLOT_PAGES` — bilerek
`SLOTS`'un içine değil yanına: `SLOTS` birkaç yerde döngüye giriyor).

### Sonraki oturum için not

- **Panel oturumu 4 saatte düşüyor** (kasıtlı). `curl` ile panel kontrol
  ederken çıktılar aniden 0 veriyorsa önce oturuma bak, koda değil — bir kez
  yanlış teşhis koydum
- Demo sunucusuna kod atmak: `tar czf … | scp` → `tar xzf`. `rsync` Windows'ta
  çalışmıyor. Ardından `chown -R www-data:www-data`
- **Kapak seçimi doğrulandı** (17 Ağustos akşamı, aşağıdaki bölüm).
  **Yükleme çubuğu hâlâ gözle görülmedi** — panele parola yazarak girmiyorum;
  o tıklama sahibinden gelecek

## 17 Ağustos, akşam — doğrulama, ve çubuğun görünmeme sebebi

Bu turun işi yeni bir özellik değildi: bir önceki turun „doğrulanmadı" dediği
iki şeye bakmak. İkisinden biri sağlam çıktı, öteki yayında **çalışmıyordu** —
ve sebebi koda değil derlemeye bakıyordu.

**Kapak seçimi — uçtan uca doğru.** `stories[4]` (3 yüklü fotoğraf) üzerinde:
panelde foto 0'da düğme yerine „Kapak" etiketi, ötekilerde düğme; `photo-cover`
POST'u 303 veriyor; dizi `[a,b,c]` → `[c,a,b]` oluyor, silme yok; çekim
sayfasının kapağı **ve** portfolyo kartındaki büyük görsel yeni fotoğrafa
dönüyor, kartın yanındaki küçük kare `uploads[1]`'i gösteriyor. İki
`makeCover` ile eski sıraya geri getirildi, veri ilk hâlinde.

**Yükleme çubuğunun yüksekliği yoktu.** `style.css` commit'liydi ama çubuk
eklendikten sonra Tailwind **yeniden derlenmemişti**: derlenmiş dosyada
`h-1.5` ve `duration-150` yoktu. Yani çubuk yayında bir piksellik hiçlik
olarak duracaktı — tam da önlemek için yazıldığı şey.

Yeniden derlemek bugünü kurtarırdı, sebebi kurtarmazdı: çubuğun HTML'i
`public/assets/upload.js` **içinde** kuruluyor, `app.css` ise `@source` ile
yalnız `app.js`'i gösteriyordu. Artık `public/assets` klasörünün tamamı
taranıyor. Sınıflar bugüne kadar Tailwind'in **otomatik** taramasıyla
bulunuyordu; o tarama komutun çalıştığı klasöre göre kök seçiyor, yani üstüne
bina edilecek bir söz değil.

> Kural: **tarayıcıda çizen bir JS yazdıysan, sınıfları derlemeye giriyor mu
> diye bak.** Şablona sınıf eklemek zaten biliniyordu (tuzaklar bölümü);
> eksik olan, JS'in de bir şablon olduğuydu.

**Demo sunucusuna yayın (HEAD).** `tar` + `scp` + `tar xzf`, ardından
`chown -R www-data:www-data`. `config.php` tarball'da yok, `uploads/` silinmiyor
— ikisi de yerinde kaldı. Öncesi ve sonrası kontrol edildi: **gidonla.com 200**
(aynı makinede canlı), demoda 8 adres 200, `style.css` artık 73 896 bayt ve
`h-1.5` içinde.

**Çubuk yine de gözle görülmedi.** Panele girmek parola yazmak demek, onu
yapmıyorum; sahibi bir kez „Anmelden"e bassın, kalanı bir dakikalık iş.
Sunucudan doğru CSS geldiği ölçülebildi, çubuğun kendisi ölçülemedi.

**ALL-INKL bu turda kapsam dışı** — proje kararı: sistem tamamlanmadan oraya
geçilmiyor. Bölüm 2'deki üç adım (veritabanı, `config.php`, alan adı) olduğu
gibi duruyor, sadece sırası gelmedi.

### Ayhan'ın bildirdiği — mobilde boşluk, ve iki kaldıraç

„Kaydırınca çok boş alan oluyor." **Hangi sayfa olduğu hâlâ belli değil** —
ekran görüntüsü sohbete düşmedi. Ama boşluğu üreten iki yer sayıyla bulundu,
ikisi de tek satır:

1. **Her bölüm** `Ui::sectionOpen`'dan `px-5 py-20 sm:px-8 sm:py-28` alıyor.
   Telefonda komşu iki bloğun içeriği arasında **160 px hiçlik** kalıyor.
   Telefon değeri `py-14` oldu; `sm:` **dokunulmadı**, yani tasarımın çizildiği
   masaüstü ritmi olduğu gibi duruyor
2. **Alt bilgi** `mt-24` taşıyordu, bir önceki bölümün kendi dolgusunun üstüne.
   Krem bölümle biten sayfalarda bu görünmez, sadece hava; **ama ana sayfa ink
   bölümle bitiyor** → iki koyu bloğun arasında **96 px krem şerit** duruyordu
   ve bozuk gibi görünüyordu. Havayı bölüm dolgusu veriyor, marj yalnız dikiş
   yeri üretiyordu

Ana sayfa telefonda ~576 px, masaüstünde 96 px boşluk kaybetti. **Gözle
doğrulanmadı**: bu ortam mobil viewport taklit edemiyor (aşağıdaki tuzak),
sayılar şablonlardan çıktı. Gerçek telefonda bakılmalı.

**Genel tarama (17 Ağustos akşamı).** „Hangi sayfa" sorusu cevapsız kaldığı
için bütün sayfalar telefon genişliğinde ölçüldü. Alet `bin/probe.html`:
headless Chrome içinde **390 px genişliğinde bir iframe**, içerik kutularını
birleştirip metin/görsel bulunmayan bantları çıkarıyor. Girişin arkasındaki
sekmeler için HTML oturumlu `curl` ile alınıp `<base href="/">` ile geçici
statik dosya olarak ölçüldü (sonra silindi — içinde CSRF izi var).

| Nerede | Ölçüm |
|---|---|
| Site, 12 sayfa tipi (ana, hizmetler, fiyatlar, portfolyo, çekim, bölgeler, rehber, yazı, hakkımda, iletişim, galeri, Impressum) | En geniş boş bant **~150 px** — bir bölüm sınırı. Patolojik boşluk yok |
| Panel, 7 sekme | Neredeyse hiç boşluk yok (`docH` 1 320 – 3 437 px, `emptyPx` 0 – 339) |
| `/de/designs` | Metrik **%41 boş** dedi → **göz kararına bakıldı, tasarım doğru**: o boşluk davetiye kartının paspartusu ve kartın kendi içi. Metrik yanlış sayıyor, dokunulmadı |
| `/de/einladung` | 179 px'lik bant telefon maketinin içi, örnek davetiyenin kendi boşluğu. Dokunulmadı |

Yani sistemik sebep yukarıdaki iki kaldıraçtı ve düzeltildi; başka bir yerde
ekran boyunda boşluk üreten şey **bulunamadı**. Ayhan'ın „burası" dediği yer
hâlâ bilinmiyor; ekran görüntüsü gelirse tek tek o noktaya bakılır.

> **Tuzak — tarayıcı otomasyonundaki sekme arka planda olabilir.** Bir kez
> „sayfanın bütün içeriği görünmüyor" teşhisi koydum: 40 `reveal` bloğunun
> hepsi `data-visible="false"` kalıyordu ve ekran görüntüsü bomboş krem
> geliyordu. Sebebi sitede değildi: `document.visibilityState === "hidden"`,
> yani Chrome o sekmeyi hiç boyamıyor, dolayısıyla `IntersectionObserver` hiç
> tetiklenmiyor. Kendi kurduğum test gözlemcisi de tek callback almadı — teşhisi
> çürüten şey o oldu. **Yerleşim (getBoundingClientRect) ölçülebiliyor, boyama
> ve IO ölçülemiyor.** Ayrıca `resize_window` başarı diyor ama `innerWidth`
> 1920'de kalıyor: **mobil genişlik taklit edilemiyor.** Site kendini iframe'e
> de aldırmıyor (CSP `frame-src`), o yol da kapalı.

## 17 Ağustos, akşam — TR panelde her bağlantı 404 veriyordu

Ayhan „designs sayfası 404" dedi. Sayfa sağlamdı, **adres** yanlıştı: site
`de`+`en`, panel `de`+`tr` konuşuyor ve iki küme yalnız Almanca'da kesişiyor.
`I18n::path('/designs','tr')` → `/tr/designs`, ve o rotayı hiçbir şey
karşılamıyor.

Tek bağlantı değildi. **TR panelden siteye giden 35 bağlantı ölçüldü, 35'i de
ölüydü** (düzeltme geçici olarak kaldırılıp sayıldı): şehirler, mekânlar,
portfolyo, rehber yazıları, sekiz görsel yuvası, müşterinin galerisi, davetiye
taslakları. Yani Türkçe çalışan biri için panelin **her kapısı** 404'e açılıyordu
— „kaydettim ama değişmedi" hissinin bir kaynağı da bu: kontrol etmenin yolu
kırık kapıydı.

İkisi tıklama kaybından fazlasıydı: `Guests::url` ve `Invitations::manageUrl`
**insanlara giden** adresler üretiyor (misafirin kişisel bağlantısı, çiftin
misafir listesi). Site dili olmayan bir dil oraya düşse üçüncü kişilere ölü
bağlantı gönderilecekti.

Çözüm 35 yerde değil, bir yerde: **`I18n::sitePath()`** — site dili olmayan
her şeyi Almanca'ya çevirip `path()`'e devrediyor. `path()` olduğu gibi kaldı,
çünkü `/tr/admin` gerçek bir adres ve panelin dil değiştiricisi ona muhtaç.

> Zaten çalışan tek bağlantı, düzendeki „Zur Website", elle `I18n::DEFAULT`
> yazılmıştı. **O geçici çözüm, sorunun sistemik olduğunun işaretiydi** — bir
> yerde elle atlanan şey, ötekilerde atlanmamış demektir. Bir daha böyle bir
> „elle düzeltilmiş tek yer" görülürse, aynı hatanın kaç kardeşi var diye
> sayılsın.

Kontrol: 35/35 cevap veriyor, **Almanca panel değişmedi**, `sitePath('en')`
hâlâ `/en` veriyor (İngilizce sayfaya bağlantı hâlâ mümkün), ve sunucuda da
doğrulandı: `tr → /de/designs`.

## 17 Ağustos, akşam — doğum günü davetiyesi „Evleniyoruz" diyordu

Ayhan ekran görüntüsü attı: sihirbazda **Occasion = Birthday** seçili, sağdaki
önizleme hâlâ „WE ARE GETTING MARRIED". „Tıklayınca değişmiyor" dediği buydu.

Sihirbaz **yedi vesile** sunuyor, kart **birini** biliyordu: o satır hem
sihirbazda hem **yayınlanan davetiyede** (`invitation.php`) sabit
`I18n::t('invite.weMarry')`'di. Yani bugüne kadar kurulmuş her kına, nişan,
sünnet, doğum günü ve firma davetiyesi bir düğün ilanı taşıyordu — önizlemenin
başka bir şey göstermesi de mümkün değildi.

**Çözüm:** `Invitations::occasionLine()` yedi vesileyi birer sözlük anahtarına
bağlıyor. Beş yeni anahtar `invite` grubunda, yani **„Sayfa metinleri"
sekmesinden Ayhan kendisi yeniden yazabilir** — bir kutlamanın nasıl ilan
edildiği onun sözü, benim değil.

Önizleme betiğe tablo koymadan takip ediyor: **her `<option>` kendi satırını
`data-line`'da taşıyor**, `paintEvents` onu kopyalıyor. Satır içi betik yok,
yani CSP'ye eklenecek bir şey de yok, ve metin zaten çeviren yoldan geçiyor.

Kontrol: yedi vesile üç dilde; yayınlanan sayfa `wedding` · `multi` ·
`birthday` için DE ve EN'de (biri geçici olarak birthday yapılıp geri alındı);
„birden çok feier"in hâlâ tek başına ikinci tarihi açtığı.

**Bilerek dokunulmadı** — ürün kararı bekliyor:
- `invite.bride` / `invite.groom` („Gelin" / „Damat") ve iki aile etiketi hâlâ
  düğün kelimeleri. **Doğum günü muhtemelen tek isim istiyor, iki değil** —
  bu bir alan değişikliği, sözlük değişikliği değil
- `invite.title` („Dijital düğün davetiyesi"), `secProgramD` („Nikâh, yemek…"),
  `freeNote` („düğün çiftlerimize ücretsiz") aynı sınıftan
- `shareText` sözlükte duruyor ama **hiçbir yerde kullanılmıyor** — Next.js
  sürümünden kalmış. WhatsApp önizlemesi `kindLabel` üzerinden zaten vesileyi
  doğru veriyor, orada hata yoktu

## 17 Ağustos, akşam — hitap: „Sevgili Yılmaz" değil, „Yılmaz ailesi"

Ayhan'ın sözü: *„Sevgili Yılmaz olmuyor, Sevgili Yılmaz ailesi olur. Ama tek
kişilerde de Sevgili Yusuf, ama Sevgili Yusuf ailesi olmaz. Buraya bir ayar
vermemiz lazım, insanların doğru yapması için."*

Ayrım kodda **vardı** (`Guests::KINDS`: family · male · female) ama **soru
yanlış yerde soruluyordu**: „Anrede" seçimi bütün partiye uygulanıyor ve
öntanımlı `family`. Misafir listesi tek hamlede yapıştırıldığı için listedeki
her tek kişi bir aile oluyordu. Çift bunu göremiyordu da: listede sadece çıplak
ad duruyordu, hitap ilk kez kartta görünüyordu.

Üç değişiklik, bu sırayla:

1. **Her satır kendi başına okunuyor** (`Guests::guessKind`):
   - önde ya da arkada aile sözü (`Familie`, `Fam.`, `Ailesi`, `family`) →
     **aile**, ve o söz **atılıyor** (yoksa „Liebe Familie Familie Yılmaz")
   - bağlaç (`&`, `+`, `und`, `and`, `ve`, virgül) → **birkaç kişi**
   - tek kelime → **soyadı, yani aile** · iki kelime → **bir kişi**
   - „Tanı" artık öntanımlı; üç sabit seçenek hâlâ bütün partiyi zorlamak için duruyor
2. **Dördüncü tür: `people`.** „Liebe Familie Anna & Thomas" yanlıştı ve
   seçilecek başka bir şey yoktu. Artık „Liebe Anna & Thomas" / „Dear Anna &
   Thomas"
3. **Listede hitap görünüyor**, kartta okunacağı hâliyle, yanında dört seçenekli
   bir kutu. Tek kelimeyi aile saymak bir **karar**, bilgi değil — o yüzden
   karar görünür ve tek tıkla düzeltilebilir. „İnsanların doğru yapması için"
   olan kısım bu

Kontrol: gerçek formdan beş isimlik karışık liste → `people`'dan `Herr`'e
değiştirme → kartın kendisi („Lieber Yusuf Demir", „Liebe Familie Yılmaz").
Test misafirleri silindi. Sunucuda da doğrulandı.

Yan iş: **Türkçe noktalı İ normalleştirmesi tek yere indi** (`Guests::flat`),
`isHeading` artık onu çağırıyor — aynı dört satır iki yerde duruyordu. Başlık
satırları (`İsim`, `Misafir`, `Aile`, `Gäste`) hâlâ atlanıyor.

### Bunun altında duran soru — karar müşterinin

Ayhan örnekleri **Türkçe** verdi ama davetiye kartı yalnız `de` ve `en`
konuşuyor (`I18n::LOCALES`). Sitenin Türkçeyi bırakma gerekçesi yazılı ve
sağlam: *arama Almanca yapılır*. **Ama davetiye siteye gelen ziyaretçiye değil,
düğünün misafirine gidiyor** — ve bu işletme Türk-Alman düğünleri çekiyor.
Yani „Sevgili Yılmaz ailesi" bugün hiçbir kartta çıkmıyor. Davetiyenin üçüncü
bir dili olmalı mı, sorusu açık; kod tarafı hazır (sözlükte `tr` zaten var,
`salutation` yalnız `de`/`en` ayrımı yapıyor).

## 18 Ağustos — hizmet bölümleri örnek alıyor, davetiye görselleşiyor

Müşteri iki şey söyledi. Birincisi: „videoya basıyorsun, ne yaptığımızı yazıyor
ama **örnek video yok**; resim çekiyoruz yazıyor, **örnek resim yok**." İkincisi:
„sayfalarda çok boşluk var." Üçüncü olarak da davetiyenin „adam akıllı,
animasyonlu" olmasını istedi ve örnek olarak in-vitely.com'u gösterdi.

### Hizmet bölümleri (`/leistungen`)

Her hizmetin altında artık **dört örnek görsel** ve isteğe bağlı bir **örnek
film** var.

| Nerede | Ne değişti |
|---|---|
| `templates/pages/services.php` | Metnin altına „Beispiele" şeridi (4 kare) ve varsa „Beispielfilm" (iki tıklamalı `Video::embedBox`) |
| `Controllers/ListAdminController.php` | Hizmet sekmesine `photos` bloğu (yükle/sil/kapak yap — liste düzenleyicisinin hazır mekanizması) ve `videoUrl` alanı |
| `src/Images.php` | `svc-<anker>-<n>` anahtarları hizmete göre eşleniyor: video→parti, standesamt→hazırlık, after→çift, diğerleri→tören |

Kendi fotoğrafı yüklenmediği sürece hizmete **uygun** temsili görseller çıkıyor;
bir kare bile yüklenince temsili olanlar tamamen kayboluyor (portfolyodaki
davranışın aynısı).

Ayrıca bölümlerin üstüne **yapışkan bir kapitel şeridi** kondu (01 Fotoğraf,
02 Video, 03 Standesamt, 04 After-Wedding). Müşteri „insan ne kadar az tıklarsa
o kadar iyi" dedi — o yüzden bölümler ayrı sayfalara **bölünmedi**; tek sayfa
kaldı, ama artık ne olduğu görünüyor ve çapaya atlanabiliyor.

### Boşluk

`Ui::sectionOpen` masaüstünde `sm:py-28` (112 px) idi → `sm:py-20 lg:py-24`.
İki bölüm arasında 224 px boşluk oluyordu. Telefonda `py-14` zaten daha önce
düşürülmüştü, ona dokunulmadı. `Ui::pageHero` 52vh/380px → 46vh/330px, alt
boşluğu 56 → 40 px. Hizmet blokları arası 96 → 64/80 px.

### Davetiye

| Ne | Nerede |
|---|---|
| **Kaligrafi fontu** | Great Vibes, self-hosted (`public/fonts/greatvibes-*.woff2`, `assets/fonts.css`). OFL lisanslı, latin + latin-ext (Türkçe ç ğ ş İ dahil). `Themes::FONTS`'a girdi, tema başına „Namen (Kalligrafie)" olarak seçiliyor |
| **Blattgold** | `--t-foil-a/b/c`, temanın **accent renginden hesaplanıyor** (`Themes::foil()`). Üç ayrı renk alanı açmadık; her tema kendiliğinden alıyor. Hex olmayan değerlerde (rgba) düz renge düşüyor |
| **İsimler yazılıyor** | `.write` maskesi soldan açılıyor, bitince `.foil` altını yürütüyor. **Dikkat:** ikisi ayrı `animation` kuralı olursa sonraki öncekini eziyor — bu yüzden `.foil.write` diye birleşik bir kural var |
| **Zarf** | Artık dikdörtgen değil: `.t-flap` (kapak), `.t-sheet` (kart), `.t-seal` (mühür). Sıra: mühür kırılır → kapak açılır → kart yukarı çıkar. `invitation.js` `data-open="true"` koyuyor ve gizlemeyi 700 ms'den 2600 ms'ye aldı, yoksa animasyon oynuyordu ama kimse görmüyordu |
| **Sahneler** | `src/Scenes.php` — çizilen SVG: botanical, leafy, bouquet, deco, lace, pampas. Temanın renklerini alıyor, dosya yüklemiyor. Panelde „Hintergrundkunst" seçimi. Alanı olmayan eski temalar id'sine göre uygun sahneyi alıyor (`Themes::defaultScene`) |
| **Scroll ile geliş** | Kartın 11 bölümü `.iv` sınıfıyla; `invitation.js` IntersectionObserver ile açıyor. **Zarf açılmadan başlatılmıyor** — yoksa gözlemci bölümleri hâlâ zarfın arkasındayken „görüldü" sayıyor ve kart açıldığında her şey hareketsiz duruyor |
| **Yapraklar** | 12 parça, temanın `petal` rengiyle süzülüyor |
| Sihirbaz önizlemesi | İsimler orada da kaligrafi (`invite-wizard.php`) |

Tuzak: SVG'de `rotate(a)` **başlangıç noktası etrafında** döndürür. Yaprakları
elipsle çizip `rotate(-28)` deyince zeytin dalı değil, ilmek zinciri çıktı.
Şimdi yapraklar iki yaydan oluşan sivri oval ve sapın o noktadaki yönüne göre
duruyorlar (`rotate(açı x y)` ile).

Great Vibes'ın büyük harflerinde (J, M) **ayrık süs kıvrımı** var — kırpma
sanılıp maske genişletildi, sonra düz bir div'de yazdırılıp fontun kendi
tasarımı olduğu görüldü. Maske geniş kaldı, zararı yok.

`npx @tailwindcss/cli -i php/assets/app.css -o php/public/assets/style.css --minify`
**çalıştırıldı** — yeni sınıflar olmadan hiçbiri görünmez. 73.9 KB → 81.9 KB.

### Next.js tarafı

Aynı işler `../components/InviteCard.tsx`, `../components/invite/Scenes.tsx`,
`../components/invite/Rise.tsx` ve `../app/globals.css` içinde de yapıldı;
Next sürümünde ayrıca builder'ın önizlemesi gerçek telefon çerçevesinde
**açılabilir** hale geldi. Ama canlı olan bu taraf — orası referans olarak
duruyor, oradan buraya bir aktarım **yok**.

## 18 Ağustos, ikinci tur — leere Flächen ve bir sürü animasyon

Müşteri iki şey bildirdi: `/leistungen#hochzeitsvideo` gibi bir çapayla girince
**yazının yanı boş** kalıyor; ve davetiyede **tek animasyon** seçilebiliyor,
oysa bir sürü isteniyor — hepsi de panelden ayarlanabilsin.

### Boş kalan yan: reveal bir daha açılmıyordu

`.reveal` / `.reveal-mask` öğeleri **başlangıçta görünmez**; `.reveal-mask`
`clip-path: inset(0 0 100% 0)` ile tamamen kesik. Görünür yapan tek şey
`app.js`'teki IntersectionObserver. Çapayla girildiğinde bazı öğeler hiç
işaretlenmiyordu: tarayıcıda ölçüldü, görünüm alanının içinde **iki öğe**
`data-visible="false"` kalmış, 750 px yer kaplayıp hiçbir şey göstermiyordu.
Kaydırmak da düzeltmiyordu.

Kök sebebi kovalamak yerine deseni sağlamlaştırdım — çünkü asıl kusur şu:
**içerik varsayılan olarak görünmezse, bir aksama onu kalıcı olarak yok eder.**
Gözlemci artık yalnızca hızlı yol; altında kendini iptal edemeyen bir tarama
var (`sweep`): görünüm alanına giren her şey açılır. `scroll`, `resize`,
`hashchange` ve `load` olaylarına bağlı, rAF ile kısıtlı. Aynı ağ
`invitation.js` içindeki `.iv` bölümlerine de kondu.

Ölçüm: çapayla giriş sonrası `stuckInViewport` **2 → 0**.

### Bir sürü animasyon

Tek liste yerine **dört bağımsız eksen**, hepsi tema başına panelden:

| Eksen | Alan | Seçenek | Nerede çalışıyor |
|---|---|---|---|
| Kartın gelişi | `animation` | 13 | `invitation.js` içindeki `frames` haritası |
| İsimler | `nameAnimation` | 6 | `.write` / `.t-name-*` sınıfları |
| Uçuşan parçacıklar | `particle` | 7 | `.t-petal-*` sınıfları |
| Bölümler | `reveal` | 5 | Kartın üstündeki `.rv-*` sınıfı |

13 × 6 × 7 × 5 = **2730 birleşim**. Bilinmeyen bir değer varsayılana çekilir
(`rise` / `write` / `petal` / `up`) — kartın hareketsiz değil, **görünmez**
kalması ihtimali kapalı.

Alanı olmayan eski temalar id'sine göre uygun bir set alıyor
(`Themes::defaultMoves`): Noir → `flip / letters / spark / side`, Pearl →
`curtain / fade / round / mask`, Azur → `slideRight / fade / snow / side`…
Böylece panelde çeşitlilik hazır duruyor, hepsi aynı `seal` değil.

### İki tuzak

**`background-clip: text` çocuğun `transform`'unu geçmiyor.** „Harf harf"
seçeneğinde her harf ayrı `<span>` ve kendi `transform`'u var; üstteki
`.foil` altın verlaufunu kendi metnine boyuyor ama dönüşümlü çocuklara
ulaşmıyor — harfler `color: transparent` olduğu için **isimler tamamen
kayboldu**. Tarayıcıda görüldü. O yüzden `letters` varyantı `.foil` yerine
`.t-name-solid` alıyor: düz altın renk, yürüyen parıltı yok.

**Yerelde test için `Themes::save()` çağırmak tehlikeli:** `all()` önce
`complete()` ile bütün varsayılanları doldurur, `save()` de onları diske
yazar. Bir temayı denemek için çağırdığımda **bütün temalara** o günkü
varsayılanlar (`write`/`petal`/`up`) yazıldı ve `defaultMoves` bir daha
devreye girmedi. Alanları `Content::save()` ile silip doğruladım. Sunucuda
bu alanlar hiç yazılmadığı için orada sorun yok.

CSS yeniden derlendi: 81.8 → **83.8 KB**.

## 18 Ağustos, üçüncü tur — açılış sahneleri

Müşteri in-vitely.com'u gösterip „aynı öyle animasyonlar" istedi. Önizleme
iframe'inin stil tablosunu okudum: **99 keyframe**. Fark sayıda değil kurguda —
onlarınki tek efekt değil, **hikâyesi olan sahneler**: `dynamicFlashDarkroom →
Burst → Grain → LatentBloom → DevelopWash → LightLeak → VignetteExit` gibi 16
adımlı bir dizi, kına için `kiDrawStroke` + `kinaHeartbeat`, doğum günü için
`ld-intro-beams-in` + `ld-confetti-fall`. Ayrıca her temanın **kendi bekleme
hareketi** var (`lacyTapRing`, `blushTapSheen`, `luxuryTapPulse`…).

Not: teknikte referans alındı, **kod kopyalanmadı**.

### Ne yapıldı

`src/Intro.php` + `.t-intro` / `.ti-*` bloğu: beş sahne, tema başına panelden
seçilir (`Açılış sahnesi`).

| Sahne | Süre | Katmanlar |
|---|---|---|
| `darkroom` | 4200 ms | karartma → flaş → gren → banyo dalgası → ışık sızıntısı → vinyet açılır |
| `focus` | 2600 ms | bulanıklık → vizör halkası → vinyet |
| `henna` | 3800 ms | sıcak parıltı + kendini çizen kına deseni (6 yol, sırayla) + kalp atışı |
| `party` | 3200 ms | karartma → ışık huzmeleri → 22 konfeti |
| `sealLight` | 2400 ms | sıcak parıltı → altın şerit geçer → vinyet |

Sıra: **sahne → zarf → kart → bölümler.** `invitation.js` sahnenin süresini
`data-intro-ms` üzerinden okuyup her şeyi o kadar öteliyor. Ölçüldü (noir,
darkroom): 0–4.2 s sahne, 4.2 s'de zarf açılır, ~6.8 s'de zarf gizlenir.
Yani karta kadar ~7 sn — sinematik ama uzun; kısaltmak istenirse
`Themes::introDuration()` tek yer.

`prefers-reduced-motion` açıksa sahne **hiç çizilmiyor** (`display:none`) ve JS
beklemeyi de sıfırlıyor — yoksa hareket istemeyen kişi boş ekrana bakardı.

Alanı olmayan temalar id'sine göre sahne alıyor: Noir/Blush/Terra → darkroom,
Sage/Pearl/Azur → focus, Élysée/Bordeaux/Marbre → sealLight, Safran/Rubis →
party, Moderne → yok.

### Test ortamı tuzağı (bunu bir daha yaşamayın)

**Arka plandaki sekmede CSS animasyonları ilerlemiyor.** `document.hidden`
true iken `animation-play-state` „running" görünür ama `currentTime` 0'da
kalır; ekran görüntüsü de bomboş çıkar. Yarım saat „sahne çalışmıyor" diye
aradım, halbuki çalışıyordu. Ayrıca `requestAnimationFrame` gizli sekmede
**hiç ateşlenmiyor** — içinde `await rAF` olan bir betik CDP zaman aşımına
düşürüyor.

Çözüm: sahneyi elle sarmak.
```js
el.getAnimations().forEach(a => { a.pause(); a.currentTime = 1600; });
```
Bu görünürlükten bağımsız çalışıyor ve istediğiniz anı yakalıyor.

### Düzeltilen iki görsel kusur

- Işık huzmeleri `repeating-conic-gradient` ile sert bir güneş diski gibi
  çıkıyordu. `blur(7px)` + radial `mask-image` + daha ince açı ile yumuşadı.
- Konfetinin x'i `i * 4.6` idi; parçalar soldan sağa **sırayla** düşüyordu,
  liste gibi. Adım `i * 37.3` yapıldı, artık dağılıyor.

CSS: 83.8 → **89.3 KB**.

## 18 Ağustos, dördüncü tur — kapalı zarfın bekleme hareketi

in-vitely'de her temanın **kendi bekleme hareketi** var (`lacyTapRing`,
`blushTapSheen`, `luxuryTapPulse`, `pearlGoldSheen`, `kinaHeartbeat`). Bizde
hepsi aynı `envBreathe` idi. Misafirin ilk gördüğü ve „buraya dokunulur"
diyen tek şey orası olduğu için altıncı eksen olarak ayrıldı.

`Themes::IDLES` — 8 seçenek, tema başına panelden (`Kapalı zarf beklerken`):

| Seçenek | Ne yapıyor |
|---|---|
| `breathe` | Sakince inip kalkar (eski davranış, varsayılan) |
| `ring` | Mühürden dışa doğru halka açılır |
| `sheen` | Zarfın üzerinden ışık şeridi geçer |
| `pulse` | Mühür nabız gibi atar |
| `heartbeat` | Çift vuruş, sonra duraklama |
| `glow` | Mühürün ardında yumuşak parıltı |
| `tilt` | Hafif 3B eğilme |
| `none` | Hareketsiz |

Dağıtım: Élysée → sheen, Pearl/Marbre → ring, Blush/Lavande → glow,
Noir/Bordeaux → pulse, Safran/Rubis → heartbeat, Azur → tilt, Moderne → yok.

### Yol açtığı gerçek düzeltme

Mühür Tailwind ile ortalanıyordu (`-translate-x-1/2 -translate-y-1/2`).
`transform` kullanan **her** animasyon bu ortalamayı siliyor ve mühür köşeye
sıçrıyor. Bu yalnız yeni hareketleri değil, **zaten var olan `sealBreak`'i de**
etkiliyordu — açılışta mühür kırılırken yerinden zıplıyordu; solup gittiği
için fark edilmemişti.

Çözüm iki katman: dışta konum (`absolute … -translate-*`), içte hareket
(`.t-seal`). Şablonda böyle duruyor, bir daha tek elemana indirmeyin.

Bir de: `currentColor` mühürde **yazı** rengidir. Halka ve parıltı onunla
çizilince Pearl gibi açık temalarda beyaz üstüne beyaz oluyordu. Artık
`var(--t-seal)` kullanıyorlar — o değişken `styleBlock()` zaten üretiyor.

CSS: 89.3 → **91 KB**.

## 18 Ağustos, beşinci tur — „neyin ne olduğu" görünsün

Panelde altı açılır liste vardı ama hiçbiri ne yaptığını göstermiyordu.
„Dunkelkammer" ya da „Halka" seçen kişi ancak kaydedip siteye bakarak
anlıyordu — ve yanlışsa gerçek temayı bozmuş oluyordu.

**Tasarım önizlemesi artık parametre alıyor.** `designPreview()` altı ekseni
(+ sahne) `$_GET`'ten okuyor, yalnızca listede olan değerleri kabul ediyor:

```
/de/designs/elysee?intro=party&idle=heartbeat&animation=flip&particle=confetti&reveal=side&nameAnimation=letters
```

Panelde her temanın altında **„Bu hareketlerle önizle"** düğmesi var; formdaki
seçimleri okuyup bu adresi yeni sekmede açıyor. **Kaydetmek gerekmiyor** —
denemenin gerçek temaya bir maliyeti yok.

**Önizleme çubuğu artık ne olduğunu yazıyor:** Sahne / Beklerken / Kart /
İsimler / Parçacık / Bölümler, her biri okunur adıyla. Yanında „Tekrar oynat"
— aynı adrese bir bağlantı, sayfayı baştan yükleyip diziyi sıfırlıyor
(betik gerekmiyor, CSP zaten HTML'de handler'a izin vermiyor).

Çubuk `z-50` idi ve **kapalı zarf onu örtüyordu**; ancak zarf açıldıktan sonra
görünüyordu. Oysa asıl gerektiği an, daha hiçbir şey açılmamışken. `z-[70]`
oldu — kuvertin (50) ve sahnenin (60) üstünde.

## 18 Ağustos — karanlık oda kısaltıldı

4200 → **2800 ms**. Karta kadar ölçülen süre ~7 sn idi, ~5,6 sn oldu
(2,8 sahne + 1,7 zarf + 1,1 kart). Adımların sırası korunsun diye bütün
katmanlar **aynı oranla** (0,667) sıkıştırıldı: flaş .45→.3, gren .9→.6,
banyo 1,5→1,0, ışık sızıntısı 2,3→1,5, vinyet 2,7→1,8.

Bir yeri değiştirirken diğerini unutmayın: süre **iki yerde** yazılı —
`Themes::introDuration()` (JS bu kadar bekliyor) ve `app.css` içindeki
`.ti-darkroom` blokları. İkisi ayrışırsa ya kart sahne bitmeden gelir ya da
bitmiş sahnenin üstünde boşuna beklenir.

## 18 Ağustos — kâğıt ve mühür (tema görselleri)

Invitely'nin zarfları kabartmalı kâğıt fotoğrafı. Fotoğraf üretemem, ama asıl
fark oradan değil oradan **önce** geliyordu: bizim mühür düz bir daire, zarf
düz bir dikdörtgendi.

**Yükleme yolu zaten tamdı** — Temalar sekmesinde kart görseli ve kuvert
görseli, mod/opaklık, yükle ve sil. Gerçek fotoğraflar geldiğinde doğrudan
giriyor, kod değişikliği gerekmiyor. Aşağıdakiler o gelene kadar (ve altında)
duran taban.

| Ne | Nasıl |
|---|---|
| **Mühür** | `border-radius` ile düzensiz kenar (dökülen mumun daire olmaması), sol üstte ışık / sağ altta gölge radial gradient'i, içeride bastırılmış halka, harflerde kabartma `text-shadow` |
| **Zarf** | Üstte ışık kenarı, altta gölge, kâğıt için `inset` gölgeler |
| **Kabartma motif** | `Scenes::envelopeArt()` — ortada dikey dikiş + sahneye göre motif (dal, dantel kemeri, art deco yelpaze, pampas, çiçek). Yalnız kontur; derinliği aşağı doğru **açık renk** `drop-shadow` veriyor, ışığın basılı çizgiye düşmesi gibi |

### Bir tuzak, iki yerde

`style.background` **kısa yazımdır** ve `background-image`'ı `none`'a çeker.
Mühürün mum reliefi `background-image` içinde durduğu için, hem şablondaki
`style="background: …"` hem de `admin.js`'teki `seal.style.background = …`
onu siliyordu. İkisi de `background-color`'a çevrildi. Yeni bir yere renk
yazarken aynı tuzağa dikkat.

CSS: 91,4 → **93,4 KB**.

## 18 Ağustos — hizmet örnekleri elle seçildi

Örnek kareleri bir hash seçiyordu: `svc-<anker>-<n>` bir bildik topa düşüyor,
oradan rastgele bir kare geliyordu. Havuzda 256 kare var ama içinde **çardak,
park bankı, bina cephesi** de var — „Beispiele" başlığının altında hizmetle
alakasız şeyler duruyordu.

`Images::CURATED` — hizmet başına **dört kare elle seçildi**, alt metinlerine
bakılarak: bir an, bir duygu, bir detay, bir sahne. Hiçbiri iki hizmette
tekrar etmiyor.

| Hizmet | Ne kondu |
|---|---|
| Hochzeitsfotografie | Koridorda öpücük · siyah-beyaz yüzük anı · avizeli tören · misafirler arasında çift |
| Hochzeitsfilm & Video | Resepsiyonda dans (×3) · kalabalığın içinde çift |
| Standesamt | Kâğıt üstünde iki alyans · yüzüğü takarken · buket · ahşap duvarda çift |
| After-Wedding | Tarlada yürüyen çift · gün batımında öpücük · kır yürüyüşü · buketiyle gelin |

Listede olmayan bir anker (yeni açılan hizmet) eskisi gibi bildik topa
düşüyor — yeni bir hizmet görselsiz kalmasın diye.

**Bunlar hâlâ başkasının çekimleri.** Bir fotoğrafçının sitesinde „Örnekler"
başlığı altında durunca onun işi gibi okunuyor; müşteriye bu söylendi. Panelden
tek bir kare yüklendiği anda bu dördü tamamen kayboluyor (portfolyodaki
davranışın aynısı).

## 18 Ağustos — animasyonu artık ÇİFT seçiyor (sihirbazda)

Yanlış yere koymuşum: altı ekseni **panele** eklemiştim, yani işletmenin
ayarına. Müşterinin kastı ise davetiyeyi yaptıran çiftin kendisinin seçmesiydi
(„müşteri tek tek seçsin baksın"). Sihirbazda yalnızca renk teması vardı.

**Sihirbaza „Bewegung / Movement" bölümü eklendi** (1. adım, tasarım
kutularının altında): altı açılır liste + **„So ansehen / Watch it"** düğmesi.
Düğme seçilen tasarımı ve seçilen altı hareketi alıp tasarım önizlemesini yeni
sekmede açıyor:

```
/de/designs/noir?intro=…&idle=…&animation=…&nameAnimation=…&particle=…&reveal=…
```

Yani çift **kaydetmeden, sipariş vermeden** deneyip bakabiliyor. Bu adres zaten
bir önceki turda parametre alır hale gelmişti; sihirbaz onu kullanıyor.

Her listenin ilk seçeneği **„— tasarımdaki gibi —" (boş)**. Kimse bu bölümle
uğraşmak zorunda değil; dokunulmazsa temanın kendi hareketi geçerli.

### Nerede saklanıyor

`themeSnapshot` içinde. Davetiye oluşturulurken temanın anlık kopyası
alınıyordu; çiftin seçtikleri o kopyanın üstüne yazılıyor. Böylece:
- Davetiye sayfası zaten `$theme`'den okuduğu için **ek kod gerekmedi**
- İşletme temayı sonradan değiştirse bile gönderilmiş davetiye değişmiyor
  (versiyonlama mantığı korunuyor)

### Yan iş: İngilizce etiketler

Etiket yardımcıları yalnız `de`/`tr` biliyordu (panel dilleri). Site `de`/`en`
olduğu için sihirbazda İngilizce tarafta ham anahtarlar („darkroom") çıkardı.
Altı yardımcıya da `en` seti eklendi.

## 18 Ağustos — hareket seçimi kendi adımı oldu

Bir önceki turda sihirbaza eklemiştim ama **tasarım kutularının altına**
koymuştum. Ölçtüm: blok sayfanın **2286. pikselinde**, 16 tasarımın arkasında.
Teknik olarak oradaydı, akışta yoktu — müşteri „yok" derken haklıydı.

Sihirbaz 5 adımdan **6 adıma** çıktı; „Bewegung / Movement" ikinci adım:

1. Anlass & Design → 2. **Bewegung** → 3. Eure Angaben → 4. Feier →
5. Abschnitte → 6. Fotos & Link

Doğrulama (alanların hangi adımda olduğu):
`0: 16 tema · 1: 6 hareket + önizleme · 2-5: boş`

Dikkat: `data-step` numaraları **geriye doğru** kaydırıldı (4→5, 3→4, 2→3,
1→2), yoksa 1→2 hemen ardından gelen 2'yi eziyor. Araya yeni bir adım
eklerken aynı sırayı izleyin.

## Sıradaki oturum buradan başlasın

### Bu akşam nerede bırakıldı (17 Ağustos akşamı)

Her şey commit'li ve push'lu (`0dd145e`), **demo sunucusu HEAD ile birebir**,
aynı makinedeki `gidonla.com` 200. Yerelde MariaDB ve PHP sunucusu açık
bırakıldı — kapatmak yeter, veri diskte.

Bu turda yapılanlar, her biri kendi bölümünde ayrıntılı:

| Ne | Nerede yazılı |
|---|---|
| Yükleme çubuğunun CSS'i eksik gidiyordu (Tailwind `upload.js`'i taramıyordu) | „çubuğun görünmeme sebebi" |
| Kapak seçimi uçtan uca doğrulandı | aynı bölüm |
| Mobilde boşluk: bölüm dolgusu 160→112 px, alt bilgideki krem şerit kaldırıldı | „mobilde boşluk, ve iki kaldıraç" |
| Bütün sayfalar telefon genişliğinde tarandı (`bin/probe.html`) | „Genel tarama" |
| TR panelde siteye giden **35 bağlantının 35'i** 404 veriyordu → `I18n::sitePath()` | „TR panelde her bağlantı 404" |
| Doğum günü davetiyesi „Evleniyoruz" diyordu → `Invitations::occasionLine()` | „doğum günü davetiyesi…" |
| Hitap satır satır tanınıyor, listede görünüyor, tek tıkla düzeliyor | „hitap: Sevgili Yılmaz değil" |

**Doğrulanmayan tek şey, ilk iş o olsun:** yükleme çubuğu **tarayıcıda gözle
görülmedi**. CSS'i artık sunucudan doğru geliyor ve kodu lint'ten geçti, ama
panele girmek parola yazmak demek. Sahibi „Anmelden"e bir kez basınca dosya
seçip çubuğu izlemek bir dakikalık iş.

**Bu turda çıkan, kararı müşteride olan iki yeni şey:**
- Düğün dışı vesilelerde `Gelin` / `Damat` ve iki aile etiketi hâlâ düğün
  kelimesi. **Doğum günü muhtemelen tek isim istiyor, iki değil** — formu
  değiştirmek demek
- **Davetiye kartı Türkçe konuşmuyor.** Ayhan örneklerini Türkçe verdi;
  gerekçesiyle birlikte „hitap" bölümünün sonunda yazılı

Eski, hâlâ bekleyenler değişmedi: alan adı · marka değişikliği (site hâlâ
„Atelier Lumière", Impressum'da uydurma „Julian Roth") · adres ve telefon ·
60 km mi 80 km mi · ALL-INKL (kapsam dışı: sistem tamamlanmadan geçilmiyor).

### Kod tarafında elle alınabilecekler

Büyük bir iş kalmadı; kalanlar ya küçük, ya müşteriden gelecek bir şeyi
bekliyor. Sıralaması aşağıda „Kalan — bu sırayla“ başlığında. Kod tarafında
elle alınabilecekler:

- Galeri ızgarası en-boy oranını fotoğrafın gerçek ölçüsünden değil **sıradan**
  seçiyor (`templates/pages/gallery.php`, `$i % 5` / `$i % 3`). Müşteri “şimdilik
  sorun değil” dedi, ama kesilen kare şikâyeti gelirse sebebi bu.
- Müşteriyle **mesajlaşma paneli** — istendi, kapsamı konuşulmadı.
- **Stuttgart → Krumbach geçişi yarım kalmıştı, tamamlandı (17 Ağustos).**
  Taradım: 40 alanda eski bölge geçiyordu. Üçe ayırdım:
  - **Düzeltildi (17 alan, DE + EN):** ana sayfa/fiyatlar/iletişim/bölgeler/
    hizmetler SEO başlık ve açıklamaları, `about.lead` („Fotograf aus
    Stuttgart“ → Krumbach), rehber yazısındaki „Baden-Württemberg“ → Bayerisch-
    Schwaben. Bunlar işletme hakkında **yanlış bilgi** veriyordu
  - **Düzeltildi:** Impressum'da „Handwerkskammer Region Stuttgart“ →
    **Handwerkskammer für Schwaben, Augsburg**; Datenschutz'ta denetim makamı
    Baden-Württemberg → **BayLDA, Ansbach**. Krumbach Bavyera'da, Baden-
    Württemberg'de değil. Avukat teyidi **istenmedi** (17 Ağustos, müşteri
    kararı: „klasik şeyler onlar“) — makam adları adresten çıkıyor, ikisi de
    kamuya açık bilgi
  - **Bilerek dokunulmadı:** portfolyo, mekânlar, yorumlar ve Stuttgart şehir
    sayfası. Bunların içeriği **gerçekten** Stuttgart — değiştirmek metni
    yalancı yapardı. Gerçek Krumbach çekimleri gelince beraber değişecekler
- **Tutarsızlık, karar müşterinin: 60 km mi 80 km mi?** Müşterinin kendi
  yazdığı ana sayfa metni „Krumbach ve **80 km** çevresi yol ücretsiz“ diyor;
  SEO açıklaması ve ek hizmet listesi **60 km** diyor. Para konusu olduğu için
  tahmin etmedim, ikisi de olduğu gibi duruyor. Doğrusu hangisiyse üç dilde
  birden düzeltilmeli
- `Seo.php`'deki `areaServed` **bütün** şehirleri sayıyor (`Content::list`).
  100 şehirde 100 satırlık JSON-LD olur. Zararsız ama saçma; `listed`'e
  bağlanabilir. Bilerek dokunulmadı.
- Şehir sayfasındaki mekân listesi ve komşu şehirler **süzülmüyor** — onlar
  müşterinin tek tek yazdığı bağlantılar, liste değil. Google'dan gelen ziyaretçi
  gizli bir şehre komşusundan ulaşabilir; bu kasıtlı.

## Kalan — bu sırayla

### 1. Modüler temada kalanlar (küçük)

Motor ve panel hazır. Yapılmayanlar:
- Süsleme konumunu **sürükleyerek** ayarlama (şu an sayıyla; sayı kesin, sürükleme hızlı)
- AVIF (`imageavif` PHP 8.1+ ve libavif ister — ALL-INKL'de kontrol edilmeli; WebP zaten çalışıyor)
- Panelde „bu temanın eski sürümüne bağlı N davetiye var“ sayacı ve toplu güncelleme
  (tekil `refreshTheme()` hazır)

### 2. ALL-INKL'e yayın — kod yüklendi, üç adım kaldı (17 Ağustos)

**SSH erişimi çalışıyor ve anahtarla, parolasız:** `~/.ssh/config` içinde
`atelier` kaydı var (`w0219c08.kasserver.com`, kullanıcı `ssh-w0219c08`,
anahtar `id_ed25519_atelier`). `ssh atelier` yeterli.

Sunucunun durumu (17 Ağustos'ta bakıldı):

| | |
|---|---|
| PHP | 8.3.29 |
| Eklentiler | `pdo_mysql` `mbstring` `json` `curl` `fileinfo` `zip` **`gd`** — hepsi var |
| Klasör | `/www/htdocs/w0219c08/atelier/` — **bugünkü kod yüklü** (7.9 MB) |
| Yanında | `newbornshooting-babydream.de` — **canlı başka bir site**, dokunulmayacak |
| `config.php` | **yok** — bilerek; sırlar buradan geçmiyor |
| `public/uploads/` | boş (yalnız `.htaccess`) — silinecek fotoğraf yok |

14 Ağustos'ta bir yükleme yapılmış ama `config.php` hiç oluşturulmadığı için
site ayağa kalkmamış. 17 Ağustos'ta kod bugünkü hâliyle yenilendi: sözlük
üç dilli (`de,en,tr`), `inhalte.sql` çevirilerle 306 KB, robots düzeltmesi
içinde. Yükleme `tar` + `ssh` ile yapıldı — **msys2'nin `rsync`'i Windows'ta
`ssh` açamıyor** (`dup() in/out/err failed`), zaman kaybetmeyin.

Kalan üç adım, üçü de **sizin yapmanız gereken** (KAS arayüzü + sırlar):

1. **Veritabanı** — KAS → Database → yeni veritabanı (utf8mb4). Sonra sunucuda:
   `cd /www/htdocs/w0219c08/atelier && mysql -u KULLANICI -p VERITABANI < schema.sql`
   ve aynısı `data/inhalte.sql` ile
2. **`config.php`** — `cp config.example.php config.php`, sonra `db_*`,
   `admin_key` (hash olarak) ve `mail_to`. Ayrıntısı `YAYIN.md` 5. adımda
3. **Alan adı** → `/www/htdocs/w0219c08/atelier/public` klasörüne bakmalı.
   `atelier-lumiere.de` **henüz çözülmüyor** (kayıtlı değil ya da yönlenmemiş).
   Ayhan'ın hemen bakması için pratik yol: hesapta zaten duran
   `newbornshooting-babydream.de`'nin bir alt alan adını (`atelier.` gibi) KAS'tan
   bu klasöre bağlamak — dakikalık iş, mevcut siteye dokunmaz

> Demo adresinde `config.php` içinde **`noindex` => `true`** olmalı. Sebebi
> „Yayına alma — VPS“ belgesinde uzun uzun yazılı: aynı içerik iki adreste
> durursa Google hangisinin asıl olduğunu kendi seçer. Gerçek alan adına
> geçilince `false`.

**VPS (45.147.46.177) gerekmedi.** Oraya da bakıldı: nginx 1.18 / Ubuntu ve
üstünde canlı `gidonla.com` var. ALL-INKL zaten erişilebilir ve asıl hedef
olduğu için demo oraya kuruluyor. VPS anlatımı yine de `YAYIN-VPS.md`'de
duruyor — bir gün lazım olursa, canlı siteyi düşürmeden.

### 2b. ALL-INKL — eski adım listesi
1. KAS → veritabanı oluştur, `schema.sql` içe aktar (phpMyAdmin)
2. `config.example.php` → `config.php`, veritabanı + `admin_key` + `mail_to` doldur
3. Dosyaları FTP/SSH ile yükle; alan adının kök klasörü **`public/`** olmalı
   (KAS'ta ayarlanamazsa bir üst dizine yönlendiren ikinci `.htaccess` gerekir)
4. İçerik: **`data/inhalte.sql`'i phpMyAdmin'den içe aktar** (schema.sql'den sonra).
   Dosya git'te ve İngilizce çeviriyi de içeriyor; `php bin/export.php` ile
   yerelden yeniden üretilir. **`node ../scripts/export-to-php.mjs` çalıştırma**
   — `themes.php`'yi bozuyor, aşağıdaki tuzaklara bak.
   `inhalte.sql` `site_content` id=2'ye **dokunulmamış kopyayı** da yazıyor;
   paneldeki „öncesi / geri al“ onu kullanıyor. O kopya çeviriden **önceki**
   hâl, yani İngilizce alanlarda „öncesi“ satırı çıkmıyor (boş orijinal =
   uyarı yok, `Form::originalNote`). İstenirse bir kez elle güncellenebilir:
   `php -r 'require "src/bootstrap.php"; Atelier\Content::saveOriginal(Atelier\Content::all());'`
   — ama o zaman Almanca alanlardaki „Stuttgart → Krumbach" geçmişi kaybolur
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
- **`node ../scripts/export-to-php.mjs` artık ÇALIŞTIRILMAMALI.** İki dosyayı
  birden bozar: `data/themes.php`'den **219 satır siler** (modüler tema motoru
  yalnızca PHP tarafında var, `lib/themes.ts` onu tanımıyor) ve `data/dict.php`'den
  `blog.more` anahtarını uçurur (elle eklenmişti, `post.php` onu kullanıyor).
  Sözlük ve temalar bu tarafta **elle yaşıyor**; `lib/dict.ts` artık kaynak değil.
  Dosyanın başındaki „nicht von Hand bearbeiten“ satırı eskimiş
- **Yeni bir `check` alanına `'default' => true` koymayı unutma.** Kod „alan yok
  = açık“ okuyorsa ama form boş kutucuk çiziyorsa, ilk kaydetmede anahtar
  kendiliğinden kapanır. `Form::field` bu yüzden `default`'u tanıyor
  (`ListAdminController`'daki `listed`/`indexed`)
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

## Veri koruma — formların yanındaki cümle (17 Ağustos)

Müşteri „KVKK belirt“ dedi. **Burada geçerli olan KVKK değil DSGVO/GDPR** —
işletme Almanya'da, sunucu AB'de. Kastettiği şey doğruydu ama: Datenschutz
sayfasında hukuki metin var, **formun yanında** insanın okuyacağı cümle yoktu.
Veri giren üç yere birer satır eklendi (sözlükte, DE + EN + TR, panelden
düzenlenebilir):

| Nerede | Anahtar | Ne diyor |
|---|---|---|
| İletişim formu | `contact.dataNote` | „Bilgilerinizi yalnızca talebinizi yanıtlamak için kullanıyoruz. Reklam yok, üçüncü kişilere aktarım yok.“ |
| Davetiye RSVP | `invite.rsvpDataNote` | „Cevabınızı yalnızca çift görür. Değerlendirmiyoruz ve size yazmıyoruz.“ |
| Misafir listesi | `invite.guestsDataNote` | „İsimler yalnızca kendi davetiye bağlantılarınızda görünür… davetiyeyle birlikte siliniyor.“ |

Üçüncüsü en önemlisi: orada **siteyi hiç görmemiş insanların** adları giriliyor.

GDPR'ın Art. 13 şartı (veri girilen yerden gizlilik metnine erişim) **zaten
karşılanıyordu** — üç sayfanın üçünde de `/datenschutz` linki var, kontrol
edildi. O yüzden fazladan link konmadı. „DSGVO“ kısaltması cümlelerin içine
yazılmadı: kısaltma metni okunmaz yapıyor, mevzuat adı zaten linkteki belgede.

## Sitenin işlediği kişisel veriler

Bu liste bir uyarı değil, bir envanter — Datenschutz metni bunların hepsini
zaten tarif ediyor. Bir karar verilirken „biz veri almıyoruz“ diye
hatırlanmasın diye buraya yazıldı.

| Nerede | Ne saklanıyor |
|---|---|
| `leads` | İletişim formu: ad, e-posta, telefon, düğün tarihi, mekân, kişi sayısı, hizmet, **serbest mesaj** |
| `customers` · `galleries` | Çift adı, galeri kodu ve **parolası (düz metin, bilerek)**, düğün tarihi |
| `selections` | Çiftin albüm için seçtiği kareler + notu |
| `invitations` · `rsvps` | İsimler, tarih, mekân, adres, fotoğraflar; misafir cevapları (ad, katılım, kişi sayısı, not) |
| `invite_guests` | **Üçüncü kişilerin adları** — düğün misafirleri. Siteyi hiç görmemiş insanlar, listeyi çift giriyor. Datenschutz §7 bunu doğru kurguluyor: sorumlu çift, biz işleyeniz |
| `throttle` | IP'nin **karması** (IP'nin kendisi değil) |
| Ölçüm | GA4 / Meta Pixel — yalnızca izin verilirse, izinsizken tek istek gitmiyor |

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
