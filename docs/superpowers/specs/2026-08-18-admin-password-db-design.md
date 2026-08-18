# Admin parolası veri tabanından

**Tarih:** 2026-08-18
**Kapsam:** Sadece `php/`
**Ilgili dosyalar:** `Admin.php`, `Integrations.php`, yeni controller + template, `layout.php` (yeni sekme)

## Amaç

Admin parolasını `config.php` yerine veri tabanında tut. Operatör parolayı panelden değiştirebilsin, dosya sistemine dokunmasın. `config.php`'deki `admin_key` bootstrap kalır — DB'de hash yoksa devreye girer, ilk giriş için.

## Depolama

Mevcut `integrations` tablosunun JSON'una yeni bir grup eklenir:

```php
'admin' => [
  'passwordHash' => '',   // password_hash(..., PASSWORD_DEFAULT) sonucu
],
```

Yeni tablo yok — `integrations` zaten "gizli anahtarlar" tablosu, tek satırlık. `Integrations::defaults()` genişletilir, `all()` mevcut merge mantığı yeni grubu da toplar.

**Şema değişikliği yok.** `integrations.data` LONGTEXT JSON zaten var; sadece JSON içeriği yeni bir alan öğreniyor. Var olan kayıtlar `merge` sayesinde `passwordHash = ''` alır ve config bootstrap'ına düşer.

## Login akışı

`Admin::login(string $password): bool` yeniden yazılır:

1. Throttle (mevcut) — 8 deneme / 15 dk.
2. `$hash = Integrations::adminPasswordHash()` — DB'den al.
3. `$hash` doluysa: sadece `password_verify($password, $hash)` — config yok sayılır.
4. `$hash` boşsa: mevcut config mantığı devreye girer:
   - `config.php` `admin_key` alanı bcrypt/argon2 hash'iyle başlıyorsa `password_verify`
   - Aksi halde `hash_equals` (zaman sabitli düz metin karşılaştırması)
5. Başarılıysa `session_regenerate_id(true)` (mevcut).

Böylece:
- Sıfırdan kurulumda `config.php`'deki `admin_key` ile ilk giriş yapılır.
- Panelden yeni parola belirlendikten sonra DB hash'i tek kaynak olur.
- Config'i sıfırlamak (`admin_key = ''`) ve DB hash'ini silmek panel'e ulaşımı keser — kilitleyici acil durum yok, DB hash silinirse config'e düşer.

## Değiştirme UI'ı — yeni sekme "Zugang / Erişim"

**Neden yeni sekme:** Entegrasyonlar sayfası kalabalık, ve parola formu farklı davranış gerektiriyor (session yenileme, mevcut parola doğrulaması). Küçük ve tek amaçlı bir sayfa daha temiz.

**Sekme yeri:** `Admin::TABS` içinde, `technik` grubuna `/zugang` href'iyle. Sistem grubunun en üstünde durur. `pinned:true` **değil** — ayda bir açılan bir sekme.

**Sayfa (`templates/admin/access.php`):**

```
Erişim / Zugang
─────────────────

Şu anki parola:  [                 ]   (mevcut parolayı gir)
Yeni parola:     [                 ]   (en az 12 karakter önerilir)
Yeni parola (tekrar): [            ]

[ Parolayı değiştir ]
```

**Controller (`AccessAdminController` — yeni):**
- `GET /admin/zugang`: formu göster
- `POST /admin/zugang`: CSRF kontrol → mevcut parolayı `Admin::verify($password)` ile doğrula (session açmaz, sadece bool döner) → yeni parola en az 8 karakter (12 önerilir) → iki alan aynı → `password_hash($new, PASSWORD_DEFAULT)` → `Integrations::saveAdminPasswordHash($hash)` → session'ı yenile (`session_regenerate_id(true)`) → toast "Parola değiştirildi."

**`Admin` sınıfında refactor:** `login()`'un içinden parola doğrulama parçası yeni bir `public static function verify(string $password): bool` metoduna çıkarılır. `login()` bu metodu çağırır + session açar. `AccessAdminController` sadece `verify()` çağırır. Böylece login mantığı iki yerde çoğaltılmaz.

Hata durumlarında (yanlış mevcut, kısa yeni, eşleşmiyor) form kırmızı bir mesajla yeniden gösterilir — mevcut Integrations sayfasının hata deseni.

## `Integrations` sınıfına eklenen metotlar

```php
public static function adminPasswordHash(): string
{
    return trim((string) (self::all()['admin']['passwordHash'] ?? ''));
}

public static function saveAdminPasswordHash(string $hash): void
{
    $settings = self::all();
    $settings['admin']['passwordHash'] = $hash;
    self::save($settings);
}
```

`defaults()` genişletilir:
```php
'admin' => ['passwordHash' => ''],
```

`all()`'un merge döngüsüne `admin` grubu da eklenir.

## Password warning

`Admin::passwordWarning()` güncellenir:

- DB'de hash varsa → uyarı yok (temiz).
- Hash yoksa VE config `admin_key` mevcut mantığa göre zayıf/varsayılan/kısa → mevcut uyarı, ama sonuna eklenir: "En iyisi: yönetim panelindeki *Erişim* sayfasından yeni bir parola belirle."

Sadece metin değişikliği, mantık aynı.

## Config dosyası

- `config.example.php` `admin_key` yorumu güncellenir: "İlk giriş için. Panelden parola belirlendikten sonra bu alan yok sayılır. Boş bırakma."
- `config.php` (deploy edilen) DOKUNULMAZ — mevcut kurulum sürdüğü sürece bootstrap olarak hâlâ çalışır.

## Kapsam dışı

- Parola sıfırlama e-postası (tek kullanıcı, config bootstrap yeter)
- 2FA
- Kullanıcı yönetimi (hâlâ tek operatör)
- Parola geçmişi / rotasyon zorlaması
- Rate limit yeni sekme için ayrı (login throttle zaten var, değiştirme formu login'den farklı bir yol; kısa formda ek throttle YAGNI)

## Test planı

Otomatik test yok. Manuel kontrol listesi:

1. Fresh kurulum: `integrations` tablosunda kayıt yokken, config `admin_key` ile giriş yapılabilir mi?
2. `/admin/zugang` yeni sekme sidebar'da (Sistem grubu altında) görünüyor mu?
3. Doğru mevcut parola + yeni parola (8+) + iki alan aynı → başarı, toast, session yenilendi (cookie değişti mi kontrol)
4. Yanlış mevcut parola → kırmızı hata, kaydetmedi
5. Yeni parolalar eşleşmiyor → kırmızı hata
6. Kısa yeni parola (< 8) → kırmızı hata
7. Yeni parolayla giriş çıkış sonrası çalışıyor
8. `integrations.data` içinde `admin.passwordHash` bir `$2y$...` string
9. `config.php` `admin_key` değiştirilirse: DB hash varken hiçbir etkisi yok
10. DB hash silinirse: config `admin_key` tekrar devreye giriyor
11. TR + DE locale'de tüm form etiketleri ve hatalar doğru dilde

## Deploy

Şema değişikliği yok. Sadece `git push` + normal deploy. Fazladan adım gerekmez.

Geri alma: önceki commit'e dön. DB'de `admin.passwordHash` alanı JSON içinde kalır ama kimse okumaz — `Admin::login` eski haline döndüğünde config'i okur.
