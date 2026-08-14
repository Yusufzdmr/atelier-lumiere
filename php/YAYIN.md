# Yayına alma — ALL-INKL KAS

| | |
|---|---|
| Sunucu | `w0219c08.kasserver.com` · 85.13.168.190 |
| Kök dizin | `/www/htdocs/w0219c08/` |
| PHP | 5.6 – 8.5 var → **8.3 seçin** |
| MariaDB | 10.11.14 (şema 10.4+ istiyor, fazlasıyla yeter) |
| Disk | 250 GB, 0.9 GB dolu — fotoğraflar için bol bol yer |

Bu liste sırayla yapılacak. Her adımın sonunda **kontrol** satırı var — orada
takılırsanız devam etmeyin, o adımı çözelim. Sonraki adım öncekinin üstüne
kuruluyor.

> **Parola uyarısı:** KAS parolanızı, FTP parolanızı ya da veritabanı parolanızı
> sohbete yazmayın. Hiçbirine ihtiyacım yok. Sunucu adı, alan adı, hata mesajı
> ve ekran görüntüsü yeterli.

---

## 1. Veritabanı

KAS → **Datenbanken** → yeni veritabanı oluştur.

- Bir parola belirleyin (uzun olsun, sonra config.php'ye yazacaksınız)
- Karakter seti **utf8mb4** olmalı. Seçenek çıkmazsa varsayılan kalsın,
  şema zaten kendi başına utf8mb4 kuruyor
- Oluşunca KAS size şunları verir: **veritabanı adı** (`d0xxxxxx` gibi),
  **kullanıcı adı** (genelde aynı), **host**

Host için `w0219c08.kasserver.com` yazın. Çalışmazsa `localhost` deneyin —
ALL-INKL'de ikisi de sık çalışıyor, adım 5'te belli olacak.

MariaDB sürümünüz 10.11; şema en az 10.4 istiyor, yani sorun yok.

**Kontrol:** Veritabanı listede görünüyor.

---

## 2. Tabloları ve içeriği kur

KAS → Datenbanken → veritabanının yanındaki **phpMyAdmin**.

Sırayla iki dosya, **bu sırayla**:

1. `php/schema.sql` → İçe Aktar (Importieren) → dosyayı seç → Git
2. `php/data/inhalte.sql` → aynı şekilde

İkincisi 233 KB. phpMyAdmin „dosya çok büyük" derse, KAS'ta yükleme sınırını
artırın ya da dosyayı bana söyleyin, parçalara bölerim.

**Kontrol:** Soldaki listede 12 tablo var (`site_content`, `customers`,
`galleries`, `invitations`, `invite_guests`, `invite_drafts`, `rsvps`,
`selections`, `leads`, `payments`, `integrations`, `throttle`).
`site_content` tablosunda 2 satır var.

---

## 3. Dosyaları yükle

FTP ya da KAS dosya yöneticisiyle `php/` klasörünün **içeriğini** yükleyin.

**Nereye:** kök dizinin içine bir alt klasör açın —

```
/www/htdocs/w0219c08/atelier/
```

Doğrudan kök dizine (`/www/htdocs/w0219c08/`) atmayın; orada başka siteler ve
KAS'ın kendi dosyaları olabilir, karışır.

Yüklendiğinde şöyle görünecek:

```
/www/htdocs/w0219c08/atelier/public/     ← alan adı buraya bakacak
/www/htdocs/w0219c08/atelier/src/
/www/htdocs/w0219c08/atelier/config.php  ← adım 5'te siz oluşturacaksınız
```

Yüklenecekler:

```
public/          ← alan adının kök klasörü buraya bakacak (adım 4)
src/
templates/
data/
bin/
schema.sql
config.example.php
```

**Yüklenmeyecekler:** `DURUM.md`, `YAYIN.md`, `assets/` (Tailwind kaynağı —
derlenmiş `public/assets/style.css` zaten yüklü olacak).

Gizli dosyaları atlamayın: `public/.htaccess` ve `public/uploads/.htaccess`.
FTP programları noktayla başlayan dosyaları gizler — „gizli dosyaları göster"
seçeneğini açın. Bu ikisi olmadan site çalışmaz ve yüklemeler korumasız kalır.

**Kontrol:** Sunucuda `public/index.php` ve `public/.htaccess` duruyor.

---

## 4. Alan adını `public/` klasörüne bağla

KAS → **Domain** → alan adı → yol (Pfad) ayarı:

```
/www/htdocs/w0219c08/atelier/public
```

Bu **önemli**: kök klasör `public/` olmalı. Bir üst klasörü gösterirseniz
`config.php`, `src/` ve `data/` tarayıcıdan erişilebilir olur.

KAS'ta yol değiştirilemiyorsa söyleyin — bir üst dizine, her şeyi `public/`
içine yönlendiren ikinci bir `.htaccess` yazarım.

Aynı ekranda **PHP sürümünü 8.3** yapın. 8.4 ve 8.5 de var ama 8.3 şu an en
oturmuşu; kod 8.1 ve üstünde çalışıyor, 5.6 ve 7.4 çalışmaz.

**Kontrol:** Alan adını açınca beyaz sayfa ya da veritabanı hatası geliyor
(henüz `config.php` yok — bu beklenen).

---

## 5. `config.php`

`config.example.php` dosyasını kopyalayıp **`config.php`** adıyla
`/www/htdocs/w0219c08/atelier/` içine koyun — yani `public/` klasörünün **bir
üstüne**, tarayıcıdan erişilemeyecek yere. Sonra doldurun:

```php
'site_url'  => 'https://alanadiniz.de',   // sondaki eğik çizgi olmadan
'dev'       => false,                      // canlıda mutlaka false
'db_host'   => 'w0219c08.kasserver.com',   // olmazsa 'localhost' deneyin
'db_port'   => 3306,
'db_name'   => 'd0xxxxxx',
'db_user'   => 'd0xxxxxx',
'db_pass'   => '...',                      // adım 1'de belirlediğiniz
'admin_key' => '...',                      // aşağıya bakın
'mail_from' => 'website@alanadiniz.de',    // kendi alan adınızdan olmalı
'mail_to'   => 'size@ulasan-adres.de',
```

**Yönetim parolası:** düz metin yerine hash koyun. Yerelde bir kez çalıştırın:

```
php -r "echo password_hash('SECTIGINIZ-PAROLA', PASSWORD_DEFAULT), PHP_EOL;"
```

Çıkan `$2y$...` dizisini `admin_key` olarak yazın. Böylece parola hiçbir
dosyada açık durmaz. Düz metin de çalışır ama en az 12 karakter olsun.

**Kontrol:** Alan adı açılıyor, ana sayfa geliyor.

---

## 6. SSL

KAS → Domain → **SSL-Schutz** → Let's Encrypt → aç.

Sertifika birkaç dakikada geliyor. Geldikten sonra `.htaccess` zaten
tüm trafiği HTTPS'e yönlendiriyor, ek bir şey yapmanıza gerek yok.

**Kontrol:** `http://` ile açınca `https://`ye düşüyor, kilit simgesi var.

---

## 7. Klasör izinleri

`/www/htdocs/w0219c08/atelier/public/uploads/` klasörü **yazılabilir** olmalı (755).

Klasör yoksa oluşturun ve içine `public/uploads/.htaccess` dosyasını koyun.

FTP programında klasöre sağ tık → izinler (CHMOD) → 755.

**Kontrol:** Bir sonraki adımdaki kontrol sayfası „yazılabilir" diyor.

---

## 8. Kontrol sayfası

Panele girin: `https://alanadiniz.de/de/admin` — parola adım 5'te belirlediğiniz.

Sonra **Ayarlar → Yayın kontrolü**.

13 madde kontrol ediliyor. Hedef: kırmızı kalmasın. Beklenen kırmızılar ve
çözümleri:

| Kırmızı | Ne yapılacak |
|---|---|
| `config.php`: dev true | Adım 5'te `false` yapın |
| İçerik: adres, telefon eksik | Adım 9 |
| HTTPS aktif değil | Adım 6 |
| Upload klasörü yazılamıyor | Adım 7 |
| Tablolar eksik | Adım 2 eksik kalmış |

Sarı olanlar okunup geçilebilir. Takıldığınız kırmızıyı ekran görüntüsüyle
gönderin.

---

## 9. Kendi bilgilerinizi girin

Panelden, sırayla:

1. **İçerik → Metinler & iletişim**: sokak, posta kodu, şehir, telefon
   (görünen ve link hâli), e-posta, Instagram, çalışma saatleri.
   **Adres ve telefon Impressum için yasal zorunluluk** — boş bırakmayın
2. **İçerik → Yasal metinler**: Impressum, Datenschutz, AGB. Yer tutucular
   (`{street}`, `{city}` …) iletişim bilgilerinden otomatik doluyor
3. **Ayarlar → Entegrasyonlar**: PayPal Client ID + Secret. Önce **sandbox**
   modunda „bağlantıyı test et" düğmesine basın, çalışınca `live` yapın
4. **Görünüm → Temalar** ve **İçerik → Portfolyo / Rehber**: gerçek fotoğraflar

**Kontrol:** Yayın kontrolü sayfasında kırmızı kalmadı.

---

## 10. Elle test (kimse atlamasın)

- İletişim formunu doldurup gönderin → mail geliyor mu? **Spam klasörüne de bakın**
- `/de/galerie` → demo galeriye girin (`elif-marco` / `solitude24`), bir kare seçip gönderin
- `/de/einladung` → bir davetiye oluşturun. Sonra linki **WhatsApp'a yapıştırın**
  → isimler, tarih ve görselden oluşan kart çıkıyor mu?
- Davetiyede „Misafir listesi" linkini açın, bir isim ekleyin, kişisel linki deneyin
- **PayPal'ı gerçek küçük bir tutarla** deneyin (1 €) — sandbox'ta çalışması
  canlıda çalışacağı anlamına gelmiyor
- Çerez uyarısı: „Sadece gerekli" → sayfayı yenileyin → uyarı geri gelmemeli
- Var olmayan bir adres açın (`/de/asdasd`) → kendi 404 sayfanız gelmeli
- İki dili de **telefonda** gezin

---

## 11. Yayından sonra

- **Google Search Console**: alan adını doğrulayın (Ayarlar → Entegrasyonlar'da
  doğrulama alanı var), `sitemap.xml` gönderin
- Demo verileri temizleyin: `elif-marco` galerisi ve demo davetiyeler
  (Müşteriler ve Davetiyeler sekmelerinden silin)
- Portfolyo ve mekânlar hâlâ Stuttgart demo verisi — gerçek çekimler gelince
  değişecek. Bunlar sitede görünüyor, yayından önce ya doldurun ya kaldırın

---

## Takılırsanız

Şunları gönderin, parolasız çözeriz:

- Hangi adımda olduğunuz
- Ekrandaki **tam hata mesajı** (ekran görüntüsü en iyisi)
- Beyaz sayfa geliyorsa: KAS → **Logs / Fehlerprotokolle** bölümündeki son satırlar

`dev` ayarını geçici olarak `true` yapıp hatayı ekranda görmek de bir yol —
ama **çözünce hemen `false`'a alın**, yoksa hata mesajları ziyaretçilere de
görünür.
