# Atelier Lumière — Hochzeitsfotografie Demo (DE / TR)

Almanya'daki bir düğün fotoğrafçısı için **çalışan demo**: iki dilli site, Local SEO altyapısı,
şifreli müşteri galerisi, animasyonlu dijital davetiye sistemi ve her şeyin düzenlenebildiği yönetim paneli.

> Bu proje bir freelance ilanına teklif vermek için hazırlandı. İlanın 17 sorusuna hazır cevaplar `TEKLIF.md` dosyasında.

**Canlı:** https://atelier-lumiere-demo.vercel.app/de · Türkçe: `/tr`

---

## Demo erişimleri

| Alan | Adres | Giriş |
|---|---|---|
| Almanca site | `/de` | — |
| Türkçe site | `/tr` | — |
| **Yönetim paneli** | `/de/admin` | parola `demo` (`ADMIN_KEY` env ile değişir) |
| Müşteri galerisi | `/de/galerie` | kod `elif-marco` · parola `solitude24` |
| " | | kod `sarah-daniel` · parola `kelter25` |
| Davetiye sihirbazı | `/de/einladung` | ücretsiz için kupon `lumiere2026` |
| Örnek davetiye | `/de/einladung/ayse-mehmet` | çoklu etkinlik (kına + düğün) |
| " | `/de/einladung/lena-jonas` | tek etkinlik |

## Hızlı başlangıç

```bash
npm install
npm run dev      # http://localhost:3000  ->  /de
npm run build
npx vercel deploy --prod --yes
```

Deploy sonrası kısa alias:
`npx vercel alias set <yeni-deployment-url> atelier-lumiere-demo.vercel.app`

> **Not:** Vercel projesinde Deployment Protection **kapalı** olmalı, aksi halde müşteri linki açamaz
> (proje: `atelier-lumiere`, team `yusufzdmrs-projects`).

---

## Yönetim paneli (`/de/admin`)

Müşteri koda dokunmadan **ekleyebilir, düzenleyebilir, silebilir**. Tüm metin alanları Almanca + Türkçe ayrı.

| Sekme | Kapsam |
|---|---|
| **Genel bakış** | Talepler, albüm seçimleri, RSVP yanıtları |
| **Metinler & iletişim** | Ana sayfa başlık/açıklama, sayılar, telefon, e-posta, adres, çalışma saatleri, **harita konumu** |
| **Fiyatlar & paketler** | Paket adı, fiyat, madde madde içerik, öne çıkan paket, ek hizmetler |
| **Hizmetler & süreç** | `/leistungen` hizmet blokları (başlık, kısa/uzun metin, "neler dahil" maddeleri, anchor, görsel anahtarı) · ana sayfadaki süreç adımları · ekleme/silme |
| **Şehirler** | Şehir SEO sayfaları: giriş, uzun metin, çekim noktaları (`Ad \| Açıklama`), SSS (`Soru \| Cevap`) · yeni şehir ekleme/silme |
| **Mekânlar** | Düğün salonu sayfaları: ad, tür, şehir, adres, kapasite, giriş, uzun metin, ışık notları, çekim noktaları, kurallar · ekleme/silme |
| **Ratgeber** | Blog yazıları: başlık, özet, uzun metin (`## ` = ara başlık), SSS (`Soru \| Cevap`), bağlı şehir/mekân, görsel yükleme · ekleme/silme |
| **Portfolyo** | Referans çekimler: metinler, bağlı şehir/mekân, **fotoğraf yükleme**, **düğün filmi (YouTube/Vimeo linki)**, ekleme/silme |
| **Müşteriler** | Müşteri dosyası: giriş adı + parola, düğün/mekân/paket/tutar, iç not, **kişisel davetiye kuponu**, fotoğraf yükleme, çiftin seçtiği kareler, arşivle · kalıcı sil |
| **Müşteri galerileri** | Yeni galeri (kod + parola), toplu fotoğraf yükleme, **düğün filmi (YouTube/Vimeo linki)**, çiftin albüm seçimini görme, silme |
| **SEO & meta** | Her sayfanın Google başlığı ve açıklaması (DE+TR), karakter sayacı ve canlı Google önizlemesi, paylaşım görseli, `noindex`, çoklu sayfalar için başlık şablonları |
| **Entegrasyonlar** | PayPal Client ID/Secret + mod ve bağlantı testi, Google Analytics/Tag Manager/Ads + dönüşüm etiketleri, Meta Pixel, Search Console & Bing doğrulaması, sonradan eklenecek API'ler için serbest anahtar listesi |
| **Davetiyeler** | Oluşturulan davetiyeler, RSVP listesi, fiyat, silme |
| **Hakkımda & yorumlar** | "Hakkımda" sayfasının tamamı (giriş, uzun metin, çalışma şekli `Başlık \| Metin`, ekipman listesi) · müşteri yorumları · genel SSS · ekleme/silme |
| **Yasal metinler** | Impressum · Datenschutz · AGB: sayfa başlığı, bölüm başlık/metinleri, sayfa sonu notu · bölüm ekleme/silme · şablona geri dönme |

Değişiklikler `revalidatePath` ile anında yayına girer. Görseller tarayıcıda küçültülür (maks. 1600 px, JPEG q0.8).

---

## Müşteri akışı (fotoğraf → seçim → albüm)

Bir müşteri = bir iş. Panelden **Müşteriler → Yeni müşteri** dendiğinde tek kayıtta üç şey birden oluşur:
galeri girişi (kullanıcı adı + parola), o müşteriye ait boş galeri ve **kişisel davetiye kuponu**.

1. Fotoğrafçı çekimi yükler (çoklu seçim, tarayıcıda 1600 px'e küçültülür, Vercel Blob fra1)
2. Müşteriye kullanıcı adı + parola verilir → müşteri `/galerie` üzerinden girer
3. Müşteri beğendiği kareleri işaretler, isterse not yazar, gönderir
4. Seçim panelde o müşterinin sayfasında görünür: kaç kare, hangi numaralar, notu ve tarihi
5. Müşteri kalıcı olarak silinmez; **Arşivle** girişi ve kuponu kapatır, fotoğraflar durur.
   Kalıcı silme ayrı bir kutuda ve giriş adının elle yazılmasını ister — galeri, seçim ve
   yüklenen tüm görseller de gider.

### Ücretsiz davetiye kuponu

Fotoğraf/film hizmeti alan her müşteriye otomatik, okunabilir bir kod üretilir (`LUM-ELIF-4K27` gibi;
karıştırılan 0/O, 1/I harfleri kullanılmaz). Müşteri bu kodu davetiye sihirbazının son adımına girer →
fiyat 0 € olur, PayPal adımı hiç çıkmaz.

- Doğrulama **sunucuda** (`/api/kupon` ve `/api/einladung`) — tarayıcıdan gelen "ücretsiz" bilgisine güvenilmez
- Varsayılan **tek kullanım**; kod kullanıldığında hangi davetiyede kullanıldığı müşteri kaydına işlenir
- Panelden: yeni kod üretme, aktif/pasif, son geçerlilik tarihi, kullanılmışsa yeniden açma
- Arşivlenen müşterinin kodu çalışmaz
- Kod denemesine karşı IP başına dakikada 12 sorgu sınırı
- Bunlardan bağımsız bir **kampanya kodu** alanı var (fuar, tanıtım); istenirse kapatılır

## Dijital davetiye

7 adımlı sihirbaz: **Etkinlik tipi → Tema → Bilgiler → Mekân → Bölümler → Fotoğraflar → Link**

- **Etkinlik tipi**: Düğün · Çoklu etkinlik (kına + düğün, iki ayrı tarih/saat/mekân) · Kına · Nişan · Sünnet · Doğum günü
- **6 tema**: Élysée, Élysée Sage, Blush, Noir, Pearl, Terra (`lib/themes.ts`)
- **Açılış animasyonu**: mühür kalkar → kapak açılır → kart süzülür → içerik sırayla belirir (`app/globals.css`, `.env-*` blokları)
- **Aç/kapa bölümler**: RSVP · Konum & harita · Sayaç · Program · Yemek menüsü · Aile bilgileri · Müzik · Video intro
- Mini game, anı duvarı ve hediye hesabı (IBAN) **bilinçli olarak yok** (müşteri istemedi)
- Müzik zarfa dokunulunca başlar (tarayıcı autoplay engeline takılmaz), sağ altta aç/kapa butonu

### Bölüm bazlı fiyatlandırma (`lib/pricing.ts`)

| Kalem | Normal | Açılışa özel |
|---|---|---|
| Dijital davetiye (temel) | 99 € | **79 €** |
| İkinci tören | 39 € | **20 €** |
| Yemek menüsü | 19 € | **ücretsiz** |
| Müzik | 29 € | **19 €** |
| Video intro | 49 € | **29 €** |
| RSVP · Konum · Sayaç · Program · Aile bilgileri | — | **ücretsiz** |

Toplam ve tasarruf canlı hesaplanır. Kupon kodu girildiğinde 0 €.
Fiyat **sunucuda** `computeTotal()` ile yeniden hesaplanır — istemciden gelen tutar kullanılmaz.

### Taslak kaydetme

Sihirbaz uzun; kimse tek oturuşta bitirmek zorunda değil.

- Her adım tarayıcıya otomatik kaydedilir (fotoğraflar hariç — localStorage'ı doldururlar)
- **Taslağı kaydet** düğmesi sunucuda saklar ve kişisel bir devam linki verir:
  `/de/einladung?taslak=<kod>` — başka cihazda da kaldığı yerden devam eder
- Davetiye oluşturulunca taslak kendiliğinden silinir; kimsenin dokunmadığı taslaklar 120 gün sonra düşer
- Panelde **Davetiyeler → Başlanmış taslaklar**: kim başlamış, ne zaman, devam linki, silme

### PayPal

- `lib/paypal.ts` — Orders v2: `createOrder()` + `captureOrder()` + `testConnection()`
- `app/api/zahlung/route.ts` — `POST {slug}` sipariş açar, `POST {slug, orderId}` tahsil eder,
  başarılı tahsilat `updateInvitation` ile veritabanına yazılır
- Sihirbazın son adımında PayPal butonu; kimlik bilgisi yoksa "ödeme aktifleşecek" notu gösterir
- Ödeme **yönlendirmeyle** yapılır, sayfaya PayPal scripti gömülmez (izin gerekmez)

Kimlik bilgileri **panelden** giriliyor: Entegrasyonlar → PayPal → Client ID, Secret, mod.
"Bağlantıyı test et" düğmesi ödeme başlatmadan anahtarları doğrular. Panelde boş bırakılırsa
ortam değişkenleri geçerli kalır:

```
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_MODE=live          # veya sandbox
```

PayPal tarafında gereken: **Business hesabı** → developer.paypal.com → Apps & Credentials →
Live → Create App → Client ID + Secret. Hesap parolası hiçbir yerde kullanılmaz.

---

## Video

Videolar **yüklenmez**, YouTube veya Vimeo'da barındırılıp gömülür — depolama, transcode ve
bant genişliği maliyeti olmaz, her cihazda sorunsuz oynar. Panelden yalnızca link yapıştırılır.

| Nerede | Panel alanı |
|---|---|
| Portfolyo hikâyesi | Portfolyo → ilgili çekim → "Düğün filmi" |
| Müşteri galerisi | Müşteri galerileri → galeri → "Düğün filmi" |
| Davetiye açılış videosu | Sihirbaz → Bölümler → Video intro (YouTube, Vimeo veya doğrudan MP4) |

`lib/video.ts` YouTube (`youtu.be`, `watch?v=`, `/embed/`, `/shorts/`), Vimeo ve doğrudan
video dosyası linklerini tanır. Tanımadığı bir link girilirse video bölümü hiç görünmez —
sayfada kırık bir kutu kalmaz.

## Ratgeber (blog)

Local SEO'nun sürekliliğini sağlayan bölüm. Üç örnek yazı hazır (fotoğrafçı seçerken sorulacak
sorular, ışığa göre akış planı, Alman-Türk düğünü), panelden sınırsız eklenebilir.

**SEO açısından asıl kazanç iç bağlantıda:** her yazıya bir şehir ve/veya mekân bağlanıyor.
Yazı o sayfalara link veriyor, şehir sayfası da kenar sütununda ilgili yazıları listeliyor —
yani `/ratgeber/hochzeit-zeitplan-licht` ile `/hochzeitsfotograf/ludwigsburg` karşılıklı besleniyor.

- `BlogPosting` + `FAQPage` schema (yazı başına SSS bloğu)
- `datePublished`, `inLanguage`, breadcrumb
- Metinde `## ` ile başlayan satır `<h2>` ara başlığa dönüşür
- Sitemap'e ve DE/TR hreflang'a otomatik girer

## Sayfa yapısı

```
/[locale]                      locale = de | tr   ( / -> /de yönlendirmesi next.config.ts'de )
  /                            Ana sayfa
  /leistungen                  Hizmetler
  /preise                      Fiyatlar & paketler   (Offer schema)
  /portfolio                   Referans çekimler
  /portfolio/[slug]            Düğün hikâyesi        (Article schema)
  /regionen                    Tüm bölgeler
  /ratgeber                    BLOG / REHBER
  /ratgeber/[slug]             Blog yazısı           (BlogPosting + FAQPage schema)
  /hochzeitsfotograf/[stadt]   ŞEHİR SEO SAYFASI     (10 adet)
  /hochzeitslocations          Tüm düğün mekânları
  /hochzeitslocations/[slug]   MEKÂN SEO SAYFASI     (7 adet)
  /ueber-mich                  Hakkımda              (Person schema)
  /kontakt                     İletişim formu
  /galerie · /galerie/[code]   Müşteri galerisi
  /einladung · /einladung/[slug]  Davetiye sihirbazı + yayınlanan davetiye
  /admin/...                   Yönetim paneli
  /impressum /datenschutz /agb
```

API: `/api/kontakt` · `/api/galerie/auth` · `/api/galerie/auswahl` · `/api/einladung` · `/api/einladung/rsvp` · `/api/zahlung`

## SEO

- Canonical + `hreflang` (de-DE, tr-TR, x-default) her sayfada
- `app/sitemap.ts` tüm diller ve alt sayfalar, `alternates.languages` ile
- `app/robots.ts` galeri / davetiye / admin indekslemeye kapalı
- Schema.org: LocalBusiness+Photograph, Service, FAQPage, Offer, BreadcrumbList, Article, Person
- LocalBusiness şeması adres/telefon/e-posta/puanı **panelden** okur, `areaServed` da panelde
  tanımlı şehirlerden gelir — işletme taşınınca Google eski adresi göstermeye devam etmez
- Şehir ve mekân sayfalarının her biri **benzersiz** metin (doorway page riski yok)
- `app/sitemap.ts` **panelden** okur — yeni eklenen şehir, mekân ve blog yazısı otomatik girer
  (saatlik yenilenir); sabit listeye bağlı değil
- Fontlar self-hosted (`public/fonts`, `app/fonts.css`) → Google Fonts CDN bağlantısı yok
- Görseller AVIF/WebP, `sizes` tanımlı, sabit oranlarla CLS = 0
- **Başlık ve açıklamalar panelden** (`SEO & meta`): her sayfa iki dilde, karakter sayacı ve
  Google önizlemesiyle. Alan boş bırakılırsa sayfanın kendi metni geçerli olur — hiçbir sayfa
  başlıksız kalmaz. Şehir/mekân/blog gibi çoklu sayfalar için `{name}`, `{title}` yer tutuculu
  başlık şablonları (`lib/marketing.ts`)
- Panelde `noindex` işaretlenen sayfa hem `robots` etiketi alır hem sitemap'ten düşer
- Search Console ve Bing doğrulama etiketleri panelden (`Entegrasyonlar`)

## Ölçüm: Analytics, Google Ads, Meta Pixel

Kimlikler panelden giriliyor (`Entegrasyonlar`), kod tarafında iş yok. Kural şu: **izin yoksa
istek yok** — reddeden ziyaretçinin tarayıcısı Google veya Meta'ya tek bir bağlantı bile açmaz.

| Ne zaman yüklenir | Ne yüklenir |
|---|---|
| İstatistik izni | Google Analytics 4 (`anonymize_ip`) |
| Pazarlama izni | Google Ads, Meta Pixel |
| İkisinden biri | Google Tag Manager (kullanılıyorsa) |

- **Consent Mode v2** (`components/Tracking.tsx`): script yüklenmeden önce tüm izinler `denied`,
  seçimden sonra `update` ile gerçek duruma çekilir; `ads_data_redaction` ve `url_passthrough` açık.
  AB'de Google Ads bunu şart koşuyor
- Dönüşümler: **form gönderimi**, **davetiye oluşturma** (tutarıyla), **telefon numarasına tıklama**
  (tel: linkleri tek yerden dinleniyor, her butona kod eklenmiyor)
- Ads dönüşüm etiketleri ve talep değeri panelde; boşsa o dönüşüm hiç gönderilmez
- Uygulama kodu `track("contact")` diyor, gerisini `components/Tracking.tsx` çözüyor (`lib/track.ts`)

## DSGVO / GDPR

- Granüler cookie consent (`components/CookieConsent.tsx`), ön işaretli kutu yok, "reddet" eşit görünürlükte
- Google Analytics yalnızca izin sonrası yüklenir (`NEXT_PUBLIC_GA_ID`)
- Google Maps **iki tıklamalı** (`components/ContactMap.tsx`): izin yokken tek bir istek bile
  gitmez, yalnızca adres + "haritayı yükle" kutusu görünür. Kullanıcı butona basınca **veya**
  çerez bandında pazarlama iznini verince harita anında yüklenir (`al:consent` olayı).
  "Yol tarifi al" butonu her durumda çalışır — düz bağlantı, arka planda veri aktarımı yok
- Veriler ve yüklenen görseller **AB'de** (Neon fra1 + Vercel Blob fra1) — Datenschutz metnindeki
  "AB'de sunucu" ifadesi teknik olarak da doğru
- YouTube/Vimeo videoları da **iki tıklamalı** (`components/VideoEmbed.tsx`): izin yokken oynatıcı
  yüklenmez, sağlayıcının önizleme görseli bile çekilmez — yerine sitenin kendi karesi konur.
  YouTube `youtube-nocookie.com`, Vimeo `dnt=1` ile gömülür
- Impressum / Datenschutz / AGB şablonları panelden düzenlenebilir; adres, telefon ve e-posta
  iletişim bilgilerinden `{legalName}` `{owner}` `{street}` `{zip}` `{city}` `{email}` `{phone}`
  yer tutucularıyla otomatik dolar (yayın öncesi hukuki kontrol notu sayfalarda duruyor)

---

## Mimari — veri katmanı

| Dosya | Görevi | Not |
|---|---|---|
| `lib/db.ts` | Neon bağlantısı, tablo şeması, soğuk başlangıç için tekrar denemesi | — |
| `lib/store.ts` | Tüm veri (müşteri, galeri, davetiye, taslak, RSVP, talep, ödeme, içerik) — async | — |
| `lib/integrations.ts` | Dış servis anahtarları (PayPal, Google, Meta, serbest anahtarlar) | Ayrı tablo: sırlar içerik tablosuna karışmaz |
| `lib/marketing.ts` | Sayfa başlıkları/açıklamaları ve başlık şablonları | Varsayılanlar; panelden değişir |
| `lib/coupon.ts` | Okunabilir kupon kodu üretimi | `crypto` ile, tahmin edilemez |
| `lib/track.ts` | Dönüşüm bildirimi (izin varsa) | — |
| `lib/media.ts` | Görsel yükleme/silme (Vercel Blob) | — |
| `lib/seed.ts` | Yalnızca ilk açılışta yazılan demo verisi | Müşterinin gerçek verisiyle değişir |
| `lib/cms.ts` | Düzenlenebilir içerik katmanı (metin, paket, hizmet, şehir, mekân, portfolyo, hakkımda, yasal) | Postgres'ten okur/yazar |
| `lib/content.ts` · `lib/about.ts` · `lib/legal.ts` | `cms.ts`'in okuduğu **varsayılan** içerikler | Panelden değiştirilir, kod sabit kalır |
| `lib/images.ts` | Tek görsel kaynağı | Fotoğrafçının kendi kareleri |
| `lib/pricing.ts` | Davetiye fiyatları | — |
| `lib/paypal.ts` | Ödeme | Env değişkenleri girilince aktif |

Fonksiyon adları aynı kaldı, yalnızca `async` oldular — kalıcı depolama senkron okunamaz.

> **Kalıcılık:** Veriler Neon Postgres'te (Vercel Marketplace, bölge **fra1 / Frankfurt**), yüklenen
> görseller Vercel Blob'da (yine **fra1**) tutulur. Sunucu yeniden başlasa da panel değişiklikleri,
> talepler, RSVP'ler ve yüklenen fotoğraflar yerinde kalır. İlk açılışta boş veritabanına demo
> içeriği bir kez yazılır (`lib/seed.ts`), sonrasında yalnızca panelde yapılan geçerlidir.

## Görseller

Demo görselleri Unsplash'ten **temsili** karelerdir (`lib/photos.json`, 266 adet, 7 kategori).

```bash
bash scripts/fetch-photos.sh     # Unsplash aramalarını indirir (curl, varsayılan UA şart)
node scripts/build-photos.mjs    # lib/photos.json üretir
```

## Ortam değişkenleri

```
NEXT_PUBLIC_SITE_URL=https://atelier-lumiere.de   # boşsa VERCEL_PROJECT_PRODUCTION_URL kullanılır
NEXT_PUBLIC_GA_ID=G-XXXXXXX                       # opsiyonel, yalnızca izin sonrası
ADMIN_KEY=...                                     # /admin parolası (varsayılan: demo)
PAYPAL_CLIENT_ID= / PAYPAL_CLIENT_SECRET= / PAYPAL_MODE=

DATABASE_URL=...                                  # Neon — `vercel integration add neon` ile otomatik
BLOB_READ_WRITE_TOKEN=...                         # Vercel Blob — `vercel blob create-store` ile otomatik
```

İkisi de Vercel projesine bağlıyken `vercel env pull` ile `.env.local` dosyasına iner.
Veritabanı erişilemezse site 500 vermez, varsayılan içerikle ayakta kalır (`lib/cms.ts`).

---

## Sıradaki işler

- [x] Panelden düzenlenmeyen son kalemler — hizmetler, süreç adımları, genel SSS, yorumlar, "Hakkımda", yasal sayfalar artık panelde (`lib/about.ts`, `lib/legal.ts`, `lib/cms.ts`)
- [x] İletişim sayfasına DSGVO uyumlu harita, konumu panelden yönetiliyor
- [x] Veritabanı geçişi — Neon Postgres + Vercel Blob (fra1), veriler artık kalıcı
- [x] Video: YouTube/Vimeo gömme (yükleme değil) — portfolyo, müşteri galerisi, davetiye intro
- [x] Ratgeber (blog) — 3 örnek yazı, panelden yönetim, şehir/mekân iç bağlantısı, BlogPosting+FAQ schema
- [x] Sitemap artık panelden okuyor (yeni şehir/mekân/yazı otomatik girer)
      · **Müşteriye söylenmeli:** videolar YouTube/Vimeo hesabına yüklenir, siteye link yapıştırılır
- [x] Müşteri modülü: kayıt açınca galeri + kişisel kupon birlikte oluşur, seçim panelde görünür
- [x] Kişisel ücretsiz davetiye kuponu (tek kullanım, sunucuda doğrulama) — sabit kod listesi kaldırıldı
- [x] Davetiye taslağı: otomatik kayıt + cihazlar arası devam linki, panelde taslak listesi
- [x] SEO & meta paneli: başlık/açıklama (DE+TR), Google önizlemesi, paylaşım görseli, noindex, şablonlar
- [x] Entegrasyonlar paneli: PayPal (test butonuyla), GA4/GTM/Ads + dönüşümler, Meta Pixel,
      Search Console/Bing, sonradan eklenecek API'ler için serbest anahtar listesi
- [x] PayPal tahsilat kaydı düzeltildi — capture sonrası `paid` artık veritabanına yazılıyor

### Teslimden önce kapatılması gerekenler

Aşağıdakiler ilanda istenen ya da müşteriye söz verilen ama demoda **henüz olmayan** kalemler.
Sırasıyla yapılacak.

1. **Bölge içeriği: Stuttgart → Krumbach ve çevresi**
   Demo Baden-Württemberg üzerine kurulu (Stuttgart, Ludwigsburg, Esslingen, Böblingen,
   Heilbronn, Tübingen, Pforzheim… + Schloss Solitude, SI-Centrum gibi mekânlar).
   Müşteri **Krumbach**'ta; hedefi Ulm, Neu-Ulm, Günzburg, Memmingen, Augsburg, München,
   sonrasında Stuttgart, Friedrichshafen, Bregenz, St. Gallen.
   Panelden değiştirilebilir ama iş tıklamak değil: **10 şehir + 7 mekân için sıfırdan
   benzersiz Almanca SEO metni** yazılacak (doorway page riski taşımamalı).
   Mekân listesi müşteriden gelecek. Teslim süresi tahmininde bu kalem ayrıca hesaplanmalı.

### Müşteriden / dışarıdan bekleyenler

- [ ] PayPal: Business hesabı `akyel.business@gmail.com`. Gereken **Client ID + Secret**
      (developer.paypal.com → Apps & Credentials → Live). Panelden girilecek, önce sandbox'ta test
- [ ] Hosting: müşteride **ALL-INKL (KAS paneli)** var. Orası PHP tabanlı, bu uygulamayı çalıştıramaz.
      Yapılacak: alan adı ALL-INKL'de kalsın, **DNS Vercel'e yönlendirilsin**; e-posta ALL-INKL'de kalır
- [ ] Google Search Console doğrulaması (GA hazır: `NEXT_PUBLIC_GA_ID`, GSC meta etiketi henüz yok)
- [ ] Müşteriden gelecek gerçek fotoğraflar ve marka adı (`lib/site.ts`)
- [ ] Yasal metinlerin avukat kontrolü
- [ ] Hosting sorusuna net cevap: Vercel (serverless + global CDN + otomatik SSL + git tabanlı
      geri alma). Müşteri cPanel/WordPress bekliyor olabilir; aylık maliyet kalemi de buradan çıkar

## Teknik notlar (tekrar düşmemek için)

- Almanca/Türkçe metinleri **Write** aracıyla yaz; bash heredoc UTF-8'i bozuyor
- Aynı sebeple `\n`, `\r` gibi kaçış dizileri de heredoc'tan geçerken bozuluyor —
  ters bölü içeren kod için **Edit/Write** kullan (bkz. `lib/actions.ts` → `CRLF`)
- JSX içinde `„…"` tırnakları string'i erken kapatabiliyor — düz `"` kullanma
- Server action'lı `<button formAction={...}>` üzerinde `name`/`value` **işe yaramaz**:
  React `name`'i kendi action id'siyle eziyor, `formData.get("idx")` hep `null` dönüyor.
  Parametre geçmek için `action.bind(null, i)` kullan (bkz. `deleteService`, `deleteLegalSection`)
- `<textarea>` içeriği tarayıcıdan **CRLF** olarak geliyor; `lib/actions.ts` içindeki
  `field()` bunu normalleştirir — paragraf ayrımı (`paras()`) buna bağlı
- `lib/store.ts` `server-only`; client bileşenler oradan **sadece tip** import edebilir
  (değerler için `lib/invite.ts` → `defaultSections`)
- next/font/google bu ortamda Turbopack ile derlenmiyor; fontlar bu yüzden self-hosted
- Port 3000 başka bir projede dolu; dev sunucusu `-p 4300` ile açıldı
- Neon ücretsiz katmanda boşta kalınca uyur; ilk sorgu 10 sn'lik bağlantı zaman aşımına
  takılabiliyor — `lib/db.ts` içindeki `withRetry` bunu karşılıyor (build bu yüzden bir kez patlamıştı)
- `next build` 11 worker'ı paralel çalıştırıyor; tohumlama bu yüzden `seed_marker` tablosuna
  atomik `INSERT ... ON CONFLICT DO NOTHING RETURNING` ile kilitlendi, yoksa demo verisi çoğalıyor
- Neon sürücüsü yalnızca etiketli şablon kabul eder (``sql`SELECT ...` ``); `sql("...")` hata verir
- `„…"` yazarken kapanış tırnağı düz `"` olursa JS string'i erken kapanır — kapanış `“` olmalı,
  JSX metninde `&bdquo; &ldquo;` kullan
- Toplu dosya düzenlemesini bash heredoc'la yapma: `\1` gibi kaçış dizileri bozuluyor.
  Betiği dosyaya yaz, `python betik.py` diye çalıştır (UTF-8 açıkça belirtilerek)
- `server-only` modüllerden (`store.ts`, `integrations.ts`) client bileşenler **sadece tip** alabilir
- Formun içine konan yükleme düğmesi `type="button"` olmalı, yoksa tıklayınca formu gönderir
