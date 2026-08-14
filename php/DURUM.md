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
| **Yönetim paneli — 15 sekmenin hepsi** | Giriş (deneme sınırlı), Genel bakış, Metinler & iletişim, Fiyatlar & paketler, **Hizmetler & süreç**, **Şehirler**, **Mekânlar**, **Portfolyo**, **Rehber**, **Müşteriler**, **Davetiyeler**, Temalar, Hakkımda & yorumlar, Yasal metinler, SEO & meta, Entegrasyonlar |
| Liste düzenleyicisi | `src/Lists.php` + `templates/admin/list.php` + `src/Controllers/ListAdminController.php` — şehir/mekân/portfolyo/rehber/hizmet aynı kalıptan: aç, düzenle, kaydet, ekle, sırala (↑↓), sil. Her kayıt kendi formunda (10 şehir tek düğmeye gitmez) |
| Müşteriler | `src/Customers.php` + `CustomerAdminController` — kayıt açınca galeri **otomatik** oluşur (parola ve kupon otomatik üretilir, galeri bitişi düğün + 2 yıl). Fotoğraf yükleme/silme, çiftin seçimi (kalpli kareler + notu), kupon yönetimi (kod/aktif/tek kullanım/son tarih/yeni kod/yeniden aç), arşivle, giriş adını yazdırarak kalıcı sil |
| Davetiyeler | `InviteAdminController` — davetiye listesi, ödendi/kupon/ödenmedi rozeti, RSVP'ler (kabul/ret/kişi sayısı + notlar), müşteri kaydına bağlantı, yarım kalan taslaklar, silme |
| Temalar | Renkler, Canva arka planı yükleme, animasyon seçimi + süre, kendi CSS'i (`.theme-<id>` altına sınırlanıyor, `@import`/`expression(` temizleniyor), canlı önizleme, ekle/kopyala/sil |
| Entegrasyonlar | PayPal (ID/Secret/mod + bağlantı testi), GA4/GTM/Ads + 3 dönüşüm etiketi, Meta Pixel, Search Console/Bing, serbest anahtar listesi (`Integrations::value('AD')`) |
| Davetiye | Sihirbaz (tek form, 5 adım, JS'siz de çalışır), tema seçimi, canlı önizleme, kupon (sunucuda), taslak + devam linki, fotoğraf yükleme, davetiye sayfası (zarf animasyonu, geri sayım, program, menü, harita, müzik), RSVP, PayPal turu |

Hepsi yerelde MariaDB 10.4'e karşı test edildi: kaydetme, ekleme, sıralama, silme,
çift dilli satır alanları (bir dili düzenlerken diğeri korunuyor), fotoğraf
yükleme/silme (dosyalar dahil), müşteri + galeri + kupon döngüsü, davetiye ve
taslak silme. 15 sekme × 2 dil = 30 sayfa uyarısız açılıyor.

## Kalan — bu sırayla

### 1. Kişiye / aileye özel davetiye + WhatsApp önizlemesi

Müşteriden gelen istek. Tasarım aynı kalır, hitap edilen kişi değişir:

- **Tek tek**: müşteri bir kişi/aile adı girer → o kişiye özel ayrı davetiye + ayrı link
  (`/de/einladung/ayse-mehmet/familie-mueller` gibi)
- **Toplu**: misafir listesi yüklenir ya da isimler girilir → sistem hepsi için
  otomatik ayrı davetiye üretir
- Ana davetiye tek kayıt kalmalı; kişiselleştirme onun üstünde ince bir katman olmalı
  (`invite_guests` tablosu: slug + guest slug + hitap adı), yoksa 200 misafir
  200 kopya davetiye demektir
- Yönetim: Davetiyeler sekmesinde kişi listesi, link kopyalama, tek tek silme

**WhatsApp / Open Graph**: davetiye linki paylaşılınca düz link değil, kart görünsün.
Her davetiye için dinamik OG: „Ayşe & Mehmet – Hochzeitseinladung“, düğün tarihi,
kapak görseli. Müşteri önizleme görselini panelden değiştirebilsin.
Not: OG görselinin **mutlak URL** olması ve 1200×630 üretilmesi gerekir; şu an
`Media` yalnızca 1600 px uzun kenar üretiyor.

### 2. Modüler tema sistemi

Şu anki tema tek parça (renkler + arka plan + animasyon + CSS). İstenen:

- Renk, font, zarf, mühür, dekorasyon, arka plan ve animasyonlar **ayrı ayrı** yönetilsin
- Aynı temanın varyasyonları: Ivory, Rose, Sage, Dark
- Arka plan dışında şeffaf PNG/WebP/SVG öğeler (çiçek, çerçeve, monogram, yaprak)
  tek tek eklenebilsin; **konum, boyut, opaklık, katman sırası** panelden ayarlansın
- Animasyonda tür + hangi öğe önce/sonra + süre + gecikme
- Kopyalama var; **import/export** (JSON) eklenecek
- **Versiyonlama**: tema değişince eski davetiyeler bozulmamalı. Davetiye kaydı
  temanın kimliğini değil, o anki **anlık görüntüsünü** (veya sürüm numarasını)
  tutmalı. Bu, yapılacakların en kritik parçası — sonradan eklemek zor
- Büyük Canva PNG/JPG otomatik optimize; WebP/AVIF (`imagewebp` GD'de var,
  AVIF PHP 8.1+ ve libavif gerektirir — ALL-INKL'de kontrol edilmeli)
- Custom CSS izolasyonu ve güvenlik kontrolleri **korunacak** (zaten var)
- Canlı önizlemede telefon / tablet / masaüstü geçişi

Müşteri tarafı basit kalmalı: **tema seç → bilgileri gir → isterse kişiye özel
isimleri ekle → önizle → oluştur/paylaş.** Gelişmişlik panelde kalsın, sihirbazda değil.

### 3. Çerez izni + ölçüm
Next sürümündeki `../components/CookieConsent.tsx` ve `../components/Tracking.tsx`
mantığı PHP + `assets/consent.js` olarak:
- Ön işaretli kutu yok, "reddet" eşit görünürlükte, karar `localStorage` (`al-consent-v1`)
- İstatistik izni → GA4; pazarlama izni → Ads + Meta Pixel; ikisinden biri → GTM
- **Consent Mode v2**: script yüklenmeden önce hepsi `denied`, sonra `update`
- Dönüşümler: iletişim formu, davetiye oluşturma, `tel:` tıklaması (tek dinleyici)
- Kimlikler zaten panelde: `Integrations::publicTracking()`

### 4. ALL-INKL'e yayın
1. KAS → veritabanı oluştur, `schema.sql` içe aktar (phpMyAdmin)
2. `config.example.php` → `config.php`, veritabanı + `admin_key` + `mail_to` doldur
3. Dosyaları FTP/SSH ile yükle; alan adının kök klasörü **`public/`** olmalı
   (KAS'ta ayarlanamazsa bir üst dizine yönlendiren ikinci `.htaccess` gerekir)
4. `node ../scripts/export-to-php.mjs` → `php bin/import.php` ile içerik + galeriler
5. Let's Encrypt (KAS'ta tek tık), `uploads/` yazılabilir olmalı (755)
6. **GD açık mı kontrol et** (`phpinfo()`), yoksa görseller küçültülmeden yüklenir
7. Test listesi: iki dil, iletişim formu e-postası, galeri girişi, davetiye oluşturma,
   PayPal sandbox turu, sitemap, robots, 404

### 5. Krumbach bölge içeriği
Şu an şehirler Stuttgart demo seti (Stuttgart, Ludwigsburg, Esslingen, Böblingen,
Waiblingen, Heilbronn, Tübingen, Nürtingen, Pforzheim, Schwäbisch Gmünd).
Hedef: Krumbach merkezli — Ulm, Neu-Ulm, Günzburg, Memmingen, Augsburg, München,
sonra Stuttgart, Friedrichshafen, Bregenz, St. Gallen.
**10 şehir + 7 mekân için sıfırdan benzersiz Almanca metin** yazılacak (doorway page
riski taşımamalı). Mekân listesi müşteriden bekleniyor. Panelden girilebilir
(Şehirler/Mekânlar sekmeleri hazır) ama iş tıklamak değil, metin yazmak.

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
- **XAMPP'ta GD kapalı gelebilir**: `C:\xampp\php\php.ini` içinde `;extension=gd`
  satırındaki noktalı virgülü kaldır, sunucuyu yeniden başlat. Kapalıyken görseller
  küçültülmeden saklanır (artık hata vermez ama 6000 px dosyalar birikir)
- **Windows'ta PHP `/tmp` yolunu bilmez** (Git Bash bilir). Test dosyalarını
  `C:/Users/.../Temp/...` gibi tam yolla yaz, yoksa `file_put_contents` sessizce patlar
- MySQL komut satırı `€` ve `ı`'yı `?` gösterir — bu ekran sorunu, veri doğru.
  Kontrol için `php -r` ile oku
- Neon (Next sürümü) boşta uyur; ilk sorgu yavaş olabilir

## Nerede ne var (panel)

```
src/Admin.php                    sekme listesi (TABS), giriş, CSRF, geri yönlendirme
src/Form.php                     alan tanımından form üretir ve geri okur
src/Lists.php                    içerik listelerinde ekle/sil/sırala/yükle
src/Content.php                  içerik dokümanı (tek JSON kaydı)
src/Customers.php                müşteri + kupon; galeriyle senkron
src/Controllers/
  AdminController.php            genel bakış, temalar, entegrasyonlar
  ContentAdminController.php     sabit alanlı sekmeler (metinler, paketler, SEO…)
  ListAdminController.php        liste sekmeleri (hizmet, şehir, mekân, portfolyo, rehber)
  CustomerAdminController.php    müşteri listesi ve müşteri kartı
  InviteAdminController.php      davetiyeler, RSVP'ler, taslaklar
templates/admin/                 layout, login, overview, content, list, customers,
                                 customer, customer-missing, invitations, themes,
                                 integrations
```

## Müşteriden bekleyenler

- PayPal Client ID + Secret (hesap: `akyel.business@gmail.com`, Business olmalı)
- Krumbach bölgesi mekân listesi
- Gerçek fotoğraflar ve marka adı
- Yasal metinlerin avukat kontrolü
- ALL-INKL KAS erişimi (paylaşılan oturum linki değil — kendi girişiyle)
- Kişiselleştirilmiş davetiye için: örnek misafir listesi (hangi biçimde geliyor —
  Excel, WhatsApp'tan kopyala-yapıştır, elle?)
