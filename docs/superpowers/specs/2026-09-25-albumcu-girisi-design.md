# Albümcü girişi — kalıcı oturum, eski link akışının yerine

**Tarih:** 2026-09-25
**Kapsam:** Sadece `php/` — backlog `docs/backlog/2026-09-25-galeri-yenileme.md`'nin 3. maddesi.
**İlgili:** Aynı backlog'un 1. maddesi (misafir salt-okunur girişi) ve kapak
fotoğrafı seçimi commit `64b4a03`'te zaten canlıya alındı — bu spec onlara
dokunmuyor.

## Amaç

Albüm baskı firması şu an seçili fotoğrafları görmek için Yusuf'un ürettiği,
süreli (30 gün) ve şifresiz bir link bekliyor — her yeni hazır galeri için
Yusuf'un link üretip göndermesi gerekiyor. Yusuf'un kararı: albümcünün kendi
kalıcı kullanıcı adı/parolası olsun, admin panelin dışında, hazır olan tüm
müşterileri kendisi görsün.

## Kapsam dışı

- Backlog'un 2. maddesi (çift formunun genişlemesi: albüm modeli/rengi, müzik
  tercihi, sosyal medya izni, kargo adresi). Bu alanlar henüz yok; "hazır"
  tanımı bu yüzden şimdilik sadece fotoğraf seçimine dayanıyor (aşağıda).
- Backlog'un 4. maddesi (fotoğraf kategorileri/Shooting-Salon grupları).
- Birden fazla albümcü hesabı — şu an tek ortak hesap yeterli (Yusuf'un
  kararı, 2026-09-25). İkinci bir firma gerekirse ayrı bir iş olarak ele
  alınır.
- "Hazır" rozetinin admin tarafında görünür bir badge olarak gösterilmesi —
  Ayhan'ın orijinal fikriydi ("badge yeşile dönene kadar indirilemez"), ama
  Yusuf'un kararıyla liste zaten sadece hazır olanları gösteriyor; hazır
  olmayanlar albümcüye hiç görünmüyor.

---

## 1. Oturum modeli

Yeni `src/Albumist.php`, `src/Admin.php`'deki `login`/`isLoggedIn`/`logout`/
`requireLogin` desenini birebir izliyor, ama tamamen ayrı bir oturum
anahtarıyla:

- `Security::session()` — aynı çerez (`al_session`), admin ve galeri
  oturumlarıyla aynı mekanizma, farklı `$_SESSION` anahtarı: `albumist`
  (bool), `albumistSince`, `albumistSeen` (admin'deki `IDLE`/`LIFETIME` ile
  aynı süreler: 4 saat boşta, 12 saat toplam).
- Hesap bilgisi `config.php`'de iki yeni anahtar: `albumist_user` (düz metin)
  ve `albumist_key` (admin_key ile aynı kural: `$2y$`/`$argon2` ile
  başlıyorsa `password_verify`, değilse `hash_equals` düz metin). Kullanıcı
  adı `hash_equals` ile zaman-sabit karşılaştırılır.
- Giriş denemesi `Security::throttle('albumist-login', 8, 900)` ile bremsli
  — admin girişiyle aynı sınır.
- `config.example.php`'ye örnek iki satır eklenir, `admin_key`'in yanındaki
  açıklamayla aynı üslupta (hash üretme komutu dahil).

Admin oturumuyla karışmaz: biri aktifken diğeri otomatik açılmaz, ikisi de
`requireLogin` kendi kontrolünü yapar.

## 2. "Hazır" mantığı

`src/Galleries.php`'ye yeni statik metot:

```php
public static function isReady(?array $selection): bool
{
    return $selection !== null && (array) ($selection['picks'] ?? []) !== [];
}
```

Bir galeri "hazır" sayılır ⇔ `Galleries::selection($code)` doludur ve en az
bir kare seçilmiştir. Bu, mevcut ZIP akışının zaten kullandığı ölçütle aynı
— yeni bir DB alanı veya migration gerekmiyor.

2. madde (albüm modeli, kargo adresi vb.) eklendiğinde bu tanım
genişletilecek; o zamana kadar sadece fotoğraf seçimi yeterli.

## 3. Rotalar ve denetleyiciler

Yeni `src/Controllers/AlbumistController.php`, dört metot:

| Metot | Rota | Ne yapar |
|---|---|---|
| `login()` | `GET/POST /{locale}/albumcu` | Girişli değilse form; girişliyse hazır galerilerin listesi |
| `show(array $params)` | `GET /{locale}/albumcu/{code}` | Tek galerinin detayı: seçilen kareler, kapak, not, ZIP düğmesi |
| `zip(array $params)` | `GET /{locale}/albumcu/{code}/zip` | ZIP indirme — mevcut `SelectionController::zip()`'teki dosya toplama/isimlendirme mantığı buraya taşınıyor, token yerine oturum + `{code}` kullanıyor |
| `logout()` | `GET /{locale}/albumcu/abmelden` | Çıkış |

`login()` ve `show()`/`zip()` başında `Albumist::requireLogin($locale)`
çağrılır (Admin'deki gibi, girişli değilse formu basar ve `exit`).

`show()`/`zip()` ayrıca `{code}`'un gerçekten hazır bir galeriye ait
olduğunu kontrol eder (`Galleries::find($code)` + `isReady`) — hazır
olmayan veya var olmayan bir kod için 404.

`public/index.php`'de admin ile aynı `$admin_` sarmalayıcısı kullanılır
(sadece `de`/`tr`, `I18n::isAdminLocale`) — bu iç bir araç, halka açık
sayfa değil. Rotalar admin bloğunun hemen altına, eski `/auswahl/*`
rotalarının olduğu yere eklenir.

## 4. Şablonlar

Üç yeni dosya, `templates/pages/` altında (bare layout, `selection.php`'nin
izlediği desen — `'bare' => true` meta, site header/footer yok):

- `albumist-login.php` — `templates/admin/login.php`'nin görsel deseni
  (aynı form, aynı sınıflar), metin "Albümcü girişi" / "Druckerei-Login".
- `albumist-list.php` — yeni. Her hazır galeri için: çift adı, düğün
  tarihi, kapak fotoğrafı küçük önizleme (varsa), seçilen kare sayısı,
  detay sayfasına link.
- `albumist-show.php` — `selection.php`'den uyarlama: aynı içerik (seçilen
  kareler grid'i, kapak, not, ZIP düğmesi), token yerine `{code}` ve oturum
  kullanır, alt kısımdaki "link X tarihine kadar geçerli" notu kalkar
  (artık süreli link yok).

## 5. Eski akışın kaldırılması

Yusuf'un kararı: yeni giriş eskisinin **yerine** geçiyor, yan yana durmuyor.

Silinecekler:
- `src/Controllers/SelectionController.php`
- `templates/pages/selection.php`
- `public/index.php`'de `/auswahl/{token}` ve `/auswahl/{token}/zip` rotaları
- `src/Galleries.php`: `shareCreate()`, `shareRevoke()`, `shareFind()`
- `templates/admin/customer.php`: "Für den Albumhersteller" bloğu (mevcut
  135-182. satırlar: "Link erzeugen" düğmesi, link kutusu, "Link
  abschalten")
- `src/Controllers/CustomerAdminController.php`: `freigabe`/`freigabe-aus`
  `$_POST['was']` dalları

Kontrol: `grep -rn "share\|auswahl" php/src php/templates php/public` sonrası
kalan tek referans olmamalı (dosya adı `selectedPhotos`/`coverPhoto` gibi
alakasız isimler hariç — bunlar kalıyor, `AlbumistController` onları da
kullanıyor).

## 6. Test

`php/tests/albumist.php` — mevcut `tests/galeri_tercihleri.php` deseninde,
framework'süz `assert_same` ile:

- `Galleries::isReady(null)` → `false`
- `Galleries::isReady(['picks' => []])` → `false`
- `Galleries::isReady(['picks' => [0, 2]])` → `true`

Giriş bremsı ayrı test edilmiyor — `Security::throttle` zaten test kapsamında
ve `Albumist::login` onu birebir admin ile aynı şekilde çağırıyor.

## Hata durumları

- Yanlış kullanıcı adı/parola → `login.php` formu, "Parola hatalı" mesajı
  (admin login'deki gibi, hangi alanın yanlış olduğu belirtilmez).
- Var olmayan/hazır olmayan `{code}` → 404 (`PageController::notFound`,
  admin dışı sayfalarda kullanılan aynı desen).
- `ZipArchive` yoksa veya seçilen karelerin hiçbirinde orijinal dosya yoksa
  → 404 (mevcut `SelectionController::zip()` davranışı aynen taşınıyor).
