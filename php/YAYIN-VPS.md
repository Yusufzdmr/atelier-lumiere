# Yayına alma — kendi VPS'ine (geçici demo)

Bu, `YAYIN.md`'nin (ALL-INKL) yanına gelen ikinci hedef: müşterinin bakabilmesi
için **kısa süreli** bir demo adresi. Asıl yayın hâlâ ALL-INKL; buradaki kurulum
onun yerine geçmez, önüne geçer.

| | |
|---|---|
| Hedef | Kendi VPS'iniz (45.147.46.177), root erişimi |
| Sunucuda hâlihazırda | **nginx 1.18 / Ubuntu — ve canlı bir site: `gidonla.com`** |
| Gereken | PHP **8.1+** FPM, MariaDB/MySQL |
| PHP eklentileri | `pdo_mysql`, `mbstring`, `json`, `curl`, `fileinfo`, `zip` + **GD** |
| Yüklenecek | `atelier-php.tar.gz` — 6.3 MB, 155 dosya, içinde `config.php` **yok** |

> **⚠ Bu makinede yayında bir site var.** Kurulumdan önce dışarıdan baktım:
> 80 ve 443 açık, nginx cevap veriyor ve `gidonla.com`'a yönlendiriyor. Yani
> buradaki her adım **eklemeli** olmalı:
>
> - **`apache2` kurmayın.** 80. portu nginx tutuyor; Apache kurulumu ya
>   başlamaz ya da çakışır. Bu belgenin ilk hâli Apache öneriyordu — o öneri
>   boş bir sunucu içindi, burada canlı siteyi düşürürdü
> - Mevcut `sites-enabled` dosyalarına **dokunmayın**, yalnızca yeni bir tane
>   ekleyin
> - `certbot`'u yalnızca yeni alan adıyla çalıştırın (`-d demo...`), çıplak
>   `certbot --nginx` mevcut sertifikalara da karışır
> - `systemctl reload nginx` kullanın, `restart` değil — yeniden başlatmak
>   canlı siteyi birkaç saniye düşürür, `reload` düşürmez

> **Parola uyarısı — `YAYIN.md`'dekiyle aynı, burada daha da önemli:**
> Sunucu parolasını, veritabanı parolasını ya da panel parolasını **sohbete
> yazmayın**. Hiçbirine ihtiyaç yok. Bir kez yazıldıysa o parola yanmış sayılır:
> `passwd` ile değiştirin ve aşağıdaki 1. adımla anahtara geçin. Root parolasıyla
> açık duran bir SSH portu, IP'yi kimse bilmese bile sürekli denenir.

---

## 1. Önce SSH anahtarı — parolayı bir daha yazmamak için

Kendi bilgisayarınızda (anahtarınız zaten varsa bu adımı atlayın):

```bash
ssh-keygen -t ed25519 -C "atelier"
```

Sonra anahtarı sunucuya taşıyın (bu **tek seferlik** parola sorar):

```bash
ssh-copy-id root@SUNUCU-IP
```

**Kontrol:** `ssh root@SUNUCU-IP` parola sormadan açılıyorsa tamam. Bundan
sonrasını ben de çalıştırabilirim, çünkü artık ortada sır yok.

Bittiğinde parola girişini tamamen kapatın — bu tek satır, sunucuyu tarayan
botların büyük kısmını devre dışı bırakır:

```bash
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl reload ssh
```

## 2. Sunucu paketleri

**Önce ne olduğuna bakın, sonra kurun.** Sunucuda zaten nginx ve bir site var;
PHP ile veritabanı da kurulu olabilir:

```bash
nginx -v
php -v 2>/dev/null || echo "PHP yok"
mysql --version 2>/dev/null || echo "MySQL/MariaDB yok"
ls /etc/nginx/sites-enabled/
```

Eksik olanları kurun — **`apache2` listede yok, bilerek**:

```bash
apt update
apt install -y mariadb-server php-fpm php-mysql php-mbstring \
  php-curl php-zip php-gd php-xml
```

PHP zaten kuruluysa yalnız eksik eklentileri ekleyin; sürüm 8.1'in altındaysa
mevcut siteyi de etkileyeceği için sürüm yükseltmeye **girmeyin**, onun yerine
yan yana kurulum (`php8.3-fpm`) yapıp yalnız bu siteyi ona bağlayın.

**Kontrol:** `php -v` → 8.1+. `php -m | grep -E 'gd|zip|curl|mysql'` → dördü de
listede. GD yoksa görseller küçültülmeden saklanır (hata vermez ama 6000 px
dosyalar birikir). `systemctl status php*-fpm` → çalışıyor olmalı.

## 3. Veritabanı

```bash
mysql -e "CREATE DATABASE atelier CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'atelier'@'localhost' IDENTIFIED BY 'BURAYA-UZUN-BIR-PAROLA';"
mysql -e "GRANT ALL PRIVILEGES ON atelier.* TO 'atelier'@'localhost'; FLUSH PRIVILEGES;"
```

Parolayı siz uydurun ve **bana söylemeyin** — birazdan `config.php`'ye siz
yazacaksınız.

## 4. Dosyalar

```bash
mkdir -p /var/www/atelier
tar xzf atelier-php.tar.gz -C /var/www/atelier
```

Paketin içinde şunlar var: `bin/`, `data/` (şema + içerik SQL'i), `public/`,
`src/`, `templates/`, `schema.sql`, `config.example.php`. `config.php` **yok** —
bir sonraki adımda sunucuda oluşuyor, hiçbir yere kopyalanmıyor.

## 5. Tablolar ve içerik

```bash
cd /var/www/atelier
mysql atelier < schema.sql
mysql atelier < data/inhalte.sql
```

`inhalte.sql` içerik dolu geliyor: 10 şehir, mekânlar, portfolyo, rehber
yazıları, yasal metinler — hepsi **Almanca + İngilizce**. Ayrıca `site_content`
id=2'ye dokunulmamış bir kopya yazıyor; paneldeki „öncesi / geri al“ onu
kullanıyor.

**Kontrol:** `mysql atelier -e "SELECT COUNT(*) FROM site_content;"` → 2 satır.

## 6. nginx: yeni bir site bloğu

Mevcut dosyalara dokunmadan **yeni** bir tane:

```bash
nano /etc/nginx/sites-available/atelier
```

```nginx
server {
    listen 80;
    server_name demo.alan-adiniz.de;   # ya da: atelier.gidonla.com

    root /var/www/atelier/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # sürümü kendinizinkiyle değiştirin
    }

    # public/uploads/.htaccess'in karşılığı.
    #
    # Depoda o klasörde PHP motorunu kapatan bir .htaccess var: yüklenen bir
    # dosya bir gün programa dönüşemesin diye ikinci sıra savunma. nginx
    # .htaccess okumaz, yani bu blok atlanırsa o savunma yok demektir.
    location ^~ /uploads/ {
        location ~ \.php$ { return 403; }
    }

    # Gizli dosyalar (.git, .env) hiç servis edilmesin.
    location ~ /\. { deny all; }
}
```

Etkinleştirin ve **düşürmeden** yeniden yükleyin:

```bash
ln -s /etc/nginx/sites-available/atelier /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

**Kontrol:** `nginx -t` „syntax is ok“ demeli. Demeden `reload` yapmayın —
bozuk yapılandırmayla nginx yeniden yüklenmez ve canlı site de etkilenir.

**Alan adı yoksa:** DNS uğraşmadan bakılacaksa `listen 80;` yerine
`listen 8080;` yazıp `server_name _;` yapın; adres `http://45.147.46.177:8080`
olur. Mevcut siteye hiç dokunmaz. SSL olmadığı için panele ve galeriye böyle
bir adresten girmeyin.

## 7. `config.php`

```bash
cd /var/www/atelier
cp config.example.php config.php
nano config.php
```

Doldurulacaklar — `db_pass` ve `admin_key` **sizde kalır**:

| Alan | Değer |
|---|---|
| `site_url` | `https://demo.alan-adiniz.de` (sonunda eğik çizgi yok) |
| `noindex` | **`true`** — aşağıya bakın, bu satır önemli |
| `dev` | `false` |
| `db_name` · `db_user` · `db_pass` | 3. adımdakiler |
| `admin_key` | Hash olarak girin (aşağıda) |
| `mail_to` | Talep bildirimlerinin gideceği adres |

**`noindex` neden `true`:** demo adresi sitenin birebir kopyası. `false`
kalırsa Google iki ayrı adreste aynı içeriği bulur ve hangisinin asıl olduğunu
kendisi seçer — asıl alan adı sıralamada kendi kopyasına yenilebilir. Müşterinin
birinci önceliği SEO'ya zarar vermemekti; bu tek satır tam olarak o. Gerçek alan
adına geçildiğinde `false` yapılır.

Panel parolasını düz metin bırakmayın:

```bash
php -r "echo password_hash('SECTIGINIZ-PAROLA', PASSWORD_DEFAULT), PHP_EOL;"
```

Çıkan `$2y$...` dizisini `admin_key`'e yazın. Böylece parola dosyanın
yedeğinde bile durmaz.

## 8. İzinler

```bash
chown -R www-data:www-data /var/www/atelier
chmod -R 755 /var/www/atelier/public/uploads
```

## 9. SSL

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d demo.alan-adiniz.de
```

**Yalnız yeni alan adını verin** (`-d demo...`). Çıplak `certbot --nginx`
sunucudaki bütün alan adlarını tarar ve canlı sitenin sertifikasına da
karışabilir.

Alan adı yoksa ve IP ile bakılacaksa SSL kurulamaz. O hâlde site `http://IP`
üzerinden açılır; giriş yapılan sayfaları (panel, galeri) böyle bir adreste
kullanmayın — parola şifresiz gider. Demoya sadece bakılacaksa sorun değil.

## 10. Kontrol sayfası

Panelde **Sistem → Vor dem Livegang**. 13 şeyi sunucunun üstünde kontrol ediyor:
PHP sürümü, eklentiler, GD+WebP, veritabanı, 12 tablo, eksik adres/telefon,
`uploads` yazılabilirliği ve `.htaccess`'i, `config.php` (dev/site_url/parola),
HTTPS, e-posta, entegrasyonlar.

**Kontrol:** kırmızı satır kalmamalı. Kalanlar için ne yapılacağı satırın
altında yazıyor.

## 11. Elle test

- İki dil: `/de/` ve `/en/` — İngilizce sayfada Almanca cümle olmamalı
- İletişim formu → e-posta geldi mi, panelde talep göründü mü
- Galeri girişi (demo: `elif-marco` / `solitude24`)
- Davetiye oluşturma, RSVP
- `sitemap.xml`, `robots.txt`, olmayan bir adres (404)
- **`robots.txt` `noindex` modunda her şeyi kapatmalı** — açıksa 7. adıma dönün
