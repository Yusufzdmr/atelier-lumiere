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
| Görsel yükleme | `src/Media.php` — GD ile 1600 px JPEG, tür dosya içeriğinden, silme upload klasörüyle sınırlı |
| Video | YouTube/Vimeo, iki tıklamalı (izin öncesi sağlayıcıya istek yok) |
| Yönetim paneli | Giriş (deneme sınırlı), Genel bakış, **Metinler & iletişim**, **Fiyatlar & paketler**, **Hakkımda & yorumlar**, **Yasal metinler**, **SEO & meta**, **Temalar**, **Entegrasyonlar** |
| Temalar | Renkler, Canva arka planı yükleme, animasyon seçimi + süre, kendi CSS'i (`.theme-<id>` altına sınırlanıyor, `@import`/`expression(` temizleniyor), canlı önizleme, ekle/kopyala/sil |
| Entegrasyonlar | PayPal (ID/Secret/mod + bağlantı testi), GA4/GTM/Ads + 3 dönüşüm etiketi, Meta Pixel, Search Console/Bing, serbest anahtar listesi (`Integrations::value('AD')`) |
| Davetiye | Sihirbaz (tek form, 5 adım, JS'siz de çalışır), tema seçimi, canlı önizleme, kupon (sunucuda), taslak + devam linki, fotoğraf yükleme, davetiye sayfası (zarf animasyonu, geri sayım, program, menü, harita, müzik), RSVP, PayPal turu |

Hepsi yerelde MariaDB 10.4'e karşı test edildi.

## Kalan — bu sırayla

### 1. Panelde 7 sekme
`src/Controllers/` içine, mevcut kalıpla:

- **Hizmetler & süreç** (`/leistungen`) — hizmet blokları + süreç adımları, ekle/sil
- **Şehirler** (`/staedte`) — 10 şehir: metin, çekim noktaları, SSS, komşular, ekle/sil
- **Mekânlar** (`/locations`) — ışık notu, kurallar, zaman planı, ekle/sil
- **Portfolyo** (`/portfolio`) — çekimler + fotoğraf yükleme + düğün filmi
- **Rehber** (`/ratgeber`) — yazılar + görsel
- **Müşteriler** (`/kunden`) — müşteri kaydı açınca galeri + kupon birlikte oluşur; giriş bilgisi, fotoğraf yükleme, çiftin seçimi, kupon yönetimi (tek kullanım/aktif/süre), arşivle, onaylı kalıcı sil. **Next sürümünde bunun tamamı var**: `../lib/store.ts` (Customer, checkCoupon, redeemCoupon) ve `../app/[locale]/admin/kunden/` — mantığı oradan çevir
- **Davetiyeler** (`/einladungen`) — davetiye listesi, RSVP'ler, taslaklar, silme

Sabit alanlı olanlar için `src/Form.php` (alan tanımından form üretir) yeter.
Liste tipi olanlar (şehir, mekân, portfolyo, rehber, müşteri) ekle/sil gerektirdiği
için ortak bir liste düzenleyicisi yazmak mantıklı — her biri için ayrı şablon yazma.

### 2. Çerez izni + ölçüm
Next sürümündeki `../components/CookieConsent.tsx` ve `../components/Tracking.tsx`
mantığı PHP + `assets/consent.js` olarak:
- Ön işaretli kutu yok, "reddet" eşit görünürlükte, karar `localStorage` (`al-consent-v1`)
- İstatistik izni → GA4; pazarlama izni → Ads + Meta Pixel; ikisinden biri → GTM
- **Consent Mode v2**: script yüklenmeden önce hepsi `denied`, sonra `update`
- Dönüşümler: iletişim formu, davetiye oluşturma, `tel:` tıklaması (tek dinleyici)
- Kimlikler zaten panelde: `Integrations::publicTracking()`

### 3. ALL-INKL'e yayın
1. KAS → veritabanı oluştur, `schema.sql` içe aktar (phpMyAdmin)
2. `config.example.php` → `config.php`, veritabanı + `admin_key` + `mail_to` doldur
3. Dosyaları FTP/SSH ile yükle; alan adının kök klasörü **`public/`** olmalı
   (KAS'ta ayarlanamazsa bir üst dizine yönlendiren ikinci `.htaccess` gerekir)
4. `node ../scripts/export-to-php.mjs` → `php bin/import.php` ile içerik + galeriler
5. Let's Encrypt (KAS'ta tek tık), `uploads/` yazılabilir olmalı (755)
6. Test listesi: iki dil, iletişim formu e-postası, galeri girişi, davetiye oluşturma,
   PayPal sandbox turu, sitemap, robots, 404

### 4. Krumbach bölge içeriği
Şu an şehirler Stuttgart demo seti (Stuttgart, Ludwigsburg, Esslingen, Böblingen,
Waiblingen, Heilbronn, Tübingen, Nürtingen, Pforzheim, Schwäbisch Gmünd).
Hedef: Krumbach merkezli — Ulm, Neu-Ulm, Günzburg, Memmingen, Augsburg, München,
sonra Stuttgart, Friedrichshafen, Bregenz, St. Gallen.
**10 şehir + 7 mekân için sıfırdan benzersiz Almanca metin** yazılacak (doorway page
riski taşımamalı). Mekân listesi müşteriden bekleniyor. Panelden girilebilir ama iş
tıklamak değil, metin yazmak.

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

- **Şablona sınıf eklediysen Tailwind'i yeniden derle**, yoksa stil yok
- `„…"` yazarken kapanış tırnağı düz `"` olursa PHP string'i erken kapanır → kapanış `"`;
  JSX/HTML metninde `&bdquo; &ldquo;`
- Toplu dosya düzenlemesini **bash heredoc ile yapma**: `\1` gibi kaçışlar bozuluyor.
  Betiği dosyaya yaz, `PYTHONIOENCODING=utf-8 python betik.py` ile çalıştır
- Form içindeki yükleme/yardımcı düğmeler `type="button"` olmalı
- PHP'nin yerleşik sunucusu statik dosyaları `public/dev-router.php` olmadan vermez
- Aynı porta ikinci sunucu açılırsa eskisi cevap vermeye devam eder — `netstat -ano | grep 8080`
- Neon (Next sürümü) boşta uyur; ilk sorgu yavaş olabilir

## Son commit'ler

```
27b4584  PHP port: invitation wizard, invitation page, RSVP and PayPal
faf6129  PHP admin: content tabs via a field description
59301c7  PHP admin: theme editor with uploads, animation and custom CSS
d927fbc  PHP admin: login, overview and the integrations tab
e7a92e6  PHP port: customer gallery, uploads, video embeds
12a6e82  PHP port: all public pages, contact form, sitemap and robots
1268203  Start the PHP port: foundation, data migration and home page
```

## Müşteriden bekleyenler

- PayPal Client ID + Secret (hesap: `akyel.business@gmail.com`, Business olmalı)
- Krumbach bölgesi mekân listesi
- Gerçek fotoğraflar ve marka adı
- Yasal metinlerin avukat kontrolü
- ALL-INKL KAS erişimi (paylaşılan oturum linki değil — kendi girişiyle)
