# Yayına alma — kendi VPS'ine (geçici demo)

Bu, `YAYIN.md`'nin (ALL-INKL) yanına gelen ikinci hedef: müşterinin bakabilmesi
için **kısa süreli** bir demo adresi. Asıl yayın hâlâ ALL-INKL; buradaki kurulum
onun yerine geçmez, önüne geçer.

| | |
|---|---|
| Hedef | Kendi VPS'iniz, root erişimi |
| Gereken | PHP **8.1+**, MariaDB/MySQL, Apache (tercih) veya nginx |
| PHP eklentileri | `pdo_mysql`, `mbstring`, `json`, `curl`, `fileinfo`, `zip` + **GD** |
| Yüklenecek | `atelier-php.tar.gz` — 6.3 MB, 155 dosya, içinde `config.php` **yok** |

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

Apache öneriyorum, nginx değil — **sebebi somut**: depoda iki `.htaccess`
dosyası var ve biri güvenlik için. `public/uploads/.htaccess` o klasörde PHP
motorunu kapatıyor, yani yüklenen bir dosya bir gün programa dönüşemesin diye
ikinci sıra savunma. nginx `.htaccess` okumaz; nginx'e geçilecekse o kural
elle yazılmalı (aşağıda 6b).

```bash
apt update
apt install -y apache2 mariadb-server \
  php php-fpm php-mysql php-mbstring php-curl php-zip php-gd php-xml
a2enmod rewrite headers
```

**Kontrol:** `php -v` → 8.1 veya üstü. `php -m | grep -E 'gd|zip|curl'` → üçü de
listede. GD yoksa görseller küçültülmeden saklanır (hata vermez ama 6000 px
dosyalar birikir).

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

## 6. Alan adı `public/` klasörüne bakmalı

### 6a. Apache

```apache
<VirtualHost *:80>
    ServerName demo.alan-adiniz.de
    DocumentRoot /var/www/atelier/public

    <Directory /var/www/atelier/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

`AllowOverride All` şart — yoksa depodaki iki `.htaccess` yok sayılır ve
yüklenen dosyalar için PHP motoru kapanmaz.

### 6b. nginx kullanılacaksa

`.htaccess` okunmadığı için o iki dosyanın işini elle yapmak gerekir:

```nginx
root /var/www/atelier/public;
index index.php;

location / { try_files $uri $uri/ /index.php?$query_string; }

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}

# public/uploads/.htaccess'in karşılığı — bu blok atlanırsa
# yüklenen bir dosya çalıştırılabilir hâle gelir.
location ^~ /uploads/ {
    location ~ \.php$ { return 403; }
}
```

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
apt install -y certbot python3-certbot-apache
certbot --apache -d demo.alan-adiniz.de
```

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
