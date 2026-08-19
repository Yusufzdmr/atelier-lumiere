# Davetiye v2 — Faz 3B: Sihirbaz

**Tarih:** 2026-08-19
**Durum:** tasarım onaylandı, plan yazılacak
**Kapsam:** sadece `php/` — Next.js tarafı (`app/`, `lib/`) bu spec dışında ve hiç değişmiyor.
**Öncesi:** [Faz 1](2026-08-19-davetiye-v2-design.md) (format ve gösterim),
[Faz 2](2026-08-19-davetiye-v2-faz2-panel-design.md) (panelde düzenleme),
[Faz 3A](2026-08-19-davetiye-v2-faz3a-vitrin-design.md) (satış vitrini),
[3B kararları](2026-08-19-davetiye-v2-faz3b-kararlar.md) (bu belgeden önce alınan üç karar)

## 1. Amaç

3A vitrini kurdu: müşteri tasarımları görüyor, tam ekran deniyor. Ama "bu
tasarımla oluştur" düğmesi hâlâ **eski motora** gidiyor — v2 tasarımıyla v2
davetiyesi üretmenin yolu yok. `invitations_v2` tablosu Faz 1'den beri boş.

3B o yolu açıyor: müşteri bir v2 tasarımı seçer, sihirbazdan geçer, sonunda
`/{locale}/v2/einladung/{slug}` adresinde **gerçek bir davetiye** durur.

## 2. Kararların düzeltilmesi

[Kararlar belgesi](2026-08-19-davetiye-v2-faz3b-kararlar.md) üç karar taşıyordu.
İkisi aynen geçerli:

1. **Sihirbaz eskisinin yanında durur** — `/{locale}/v2/einladung`, eskisine
   dokunulmaz. Geçerli.
2. **Davetiye `invitations_v2`'de saklanır**, `design_snapshot` yayınlanmışı
   dondurur. Geçerli.

Üçüncüsü — "eskinin beş adımı korunur: çift ve tarih → mekân → metinler →
görseller → önizleme" — **yanlış bir hatırlamaya dayanıyordu.** Eski sihirbaz
beş değil altı adım ve sırası bu değil (`invite-wizard.php:54`,
`data-step="0..5"`):

| # | Adım | Alanlar | 3B'deki karşılığı |
|---|---|---|---|
| 0 | Anlass & Design | `eventType`, `theme` | 3A vitrininde seçilmiş oluyor |
| 1 | Bewegung | `anim_intro/idle/card/name/particle/reveal` | v2'de tasarımın `animation` alanında |
| 2 | Eure Angaben | `bride`, `groom`, `message`, `closing`, `hashtag`, `email` | **kalıyor** |
| 3 | Feier | `event{i}_name/date/time/venue/address` | **kalıyor**, tek etkinliğe iniyor (§7) |
| 4 | Abschnitte | `section_*`, program/menü/aile/müzik/video | Faz C (`sections`) |
| 5 | Fotos & Link | `photos[]`, `guests`, `slug`, `coupon` | fotoğraf ve adres kalıyor; misafir ve kupon Faz D |

Yani korunacak beş adımlık bir sıra ortada yok. Adım listesi §6'da yeniden
kuruluyor — ve **sabit değil, dokümandan türüyor**.

## 3. Açık dört sorunun cevabı

### 3.1 Ödeme ve kupon → Faz D. Vitrindeki düğme de D'de çevrilir.

Kararlar belgesi "düğme 3B bitince v2'ye döner" diyordu; **erteleniyor**.

`Pricing::total()` fiyatı bölümlere göre hesaplıyor (`$sections`, çok etkinlik
farkı, kupon). Bölümler Faz C. 3B'de bölümsüz bir fiyat kuralı icat etmek, C
gelince atılacak kod demek. Ödemeyi D'ye bırakıp düğmeyi bugün çevirmek ise
canlıda **ödemesiz davetiye üreten bir kapı** açar — vitrin herkese açık.

Üçüncü yol seçildi: **düğme eski sihirbaza bakmaya devam eder.** `Design::creatable()`
ve şablondaki kullanımı 3B'de **hiç değişmez**; kural zaten tek yerde, D'de tek
satır. v2 sihirbazı bu arada `/{locale}/v2/einladung` adresinden doğrudan
erişilebilir ve kendi başına test edilir.

Bedeli açıkça: **3B bittiğinde v2 sihirbazı hiçbir yerden bağlantılı değildir.**
Bu bilinçli — para yolunu bozmamanın karşılığı.

Davetiye `data.paid = false` ile doğar. Alan şimdiden var, çünkü D geldiğinde
"eski kayıtlar ödenmiş mi" sorusunun cevabı belirsiz kalmasın.

### 3.2 Adres → `/{locale}/v2/einladung/{slug}`

Eskisinin yanına koymak (`/{locale}/einladung/{slug}`) yönlendiricide aynı
deseni iki tabloya birden sordurur; iki motoru birbirine bağlar ve v1'in
`show()` yolunu değiştirmeyi gerektirir — "eskisine dokunulmaz" kararına aykırı.

Buna karşılık bir tedbir: **slug her iki tabloda birden benzersizdir.**

```php
InvitationsV2::slugAvailable($slug)   // invitations_v2 VE invitations, ikisine birden bakar
```

Gerekçe: `v2/` öneki bilerek geçici (Faz 1 §1). Önek kalktığı gün
`/{locale}/einladung/{slug}` **tek** bir davetiyeye çözülmek zorunda. Bugün
bedava, sonradan imkânsız — yayınlanmış adresler yeniden adlandırılamaz.

### 3.3 RSVP → Faz C

RSVP bir *bölüm* (`sections['rsvp']`), bölümler C'nin konusu. Faz 1'in işaret
ettiği "`rsvps` tablosunda slug çakışması" riskini §3.2'deki genel benzersizlik
zaten kapatıyor: C geldiğinde `rsvps` şemasına dokunmak gerekmeyecek, ortak
tablo güvenle kullanılabilir.

### 3.4 Misafire özel adres → Faz D

`/einladung/{slug}/{gast}` kişiye özel hitap gösteriyor. v2'de bunun karşılığı
`guest_name` diye bir `bind` olurdu — ama `Design::BINDS` **format** kararı ve
formata anahtar eklemek bölümlerle birlikte (C/D) verilmesi gereken bir karar.
3B misafir listesi bilmiyor: `guests` alanı yok, `Guests::addMany` çağrılmıyor.

**Ama `manageKey` bugünden yazılır.** Sayfası 3B'de yok; anahtar `data` içinde
duruyor. Gerekçesi `design_snapshot`'ınkiyle aynı ders: sonradan eklemek,
o güne kadar yayınlanmış davetiyeleri kapı dışında bırakır. `data` zaten JSON,
şema değişmiyor.

## 4. Mimarinin çekirdeği: sihirbazın çıktısı bir doküman

Sihirbaz "müşteri neyi değiştirdi" listesi saklamıyor. Müşterinin izinli
seçimleri tasarım dokümanına **uygulanıyor**, sonuç `design_snapshot` olarak
donduruluyor.

```
seçilen tasarım ──► DesignWizard::personalize(doc, choices) ──► design_snapshot
                              (izin süzgeci)                          │
künye alanları ──────────────────────────────────────────────► data ──┘
```

Kazancı: davetiyeyi ekrana basmak **Faz 1'in ta kendisi** olur —

```php
Design::css($snapshot, $scope)
Design::html($snapshot, Design::bindValues($data, $locale), $locale, $spot)
```

Renderer'a tek satır eklenmiyor. Yeni bir "asıl doküman + üstüne yamalar"
birleştirme mantığı doğmuyor; o mantık doğsaydı renderer'ın, önizlemenin,
panelin ve D'deki düzenleme ekranının hepsi onu ayrı ayrı bilmek zorunda kalırdı.

D'de "sonradan düzenle" geldiğinde sihirbaz snapshot'a karşı yeniden açılır:
izinler snapshot'ın **içinde** seyahat ettiği için aynı kurallar kendiliğinden
geçerli olur, tasarımın bugünkü hâline bakmak gerekmez.

`personalize()` aynı zamanda **güvenlik sınırıdır**: kilitli bir katmanın
rengini değiştirmeye çalışan POST sessizce düşer, hata sayfası üretmez (§9).

### Karşılığında verilen

Tasarımcı bir tasarımı sonradan düzeltirse yayınlanmış davetiyeler düzelmez.
Bu `design_snapshot`'ın **amacı**, kazası değil (Faz 1 §1). Eski motorda
aynı ihtiyaç için `Themes::refreshTheme` var; v2 karşılığı gerekirse ayrı bir
iş — 3B'de yok.

## 5. İzinlerin anlamı

`Design::PERMISSIONS = ['edit','color','font','photo','text','hide']` Faz 2'de
tanımlandı ama **anlamları hiçbir yerde yazmıyor**; panel altı kutuyu ham
adlarıyla basıyor (`design-edit-sections.php:165`). 3B onlara anlam veren faz,
tanım burada sabitleniyor:

| Bayrak | Anlamı | Sihirbazda karşılığı |
|---|---|---|
| `edit` | **ana şalter.** Kapalıysa aşağıdaki beşi sayılmaz | katman sihirbazda hiç görünmez |
| `color` | bu katmanın rengi jetondan koparılıp ayrı verilebilir | renk seçici |
| `font` | bu katmanın yazı tipi jetondan koparılabilir | yazı tipi listesi |
| `text` | sabit metni değiştirilebilir (`bind` taşıyanda anlamsız, yok sayılır) | metin kutusu (de/en) |
| `photo` | görseli değiştirilebilir (`image`/`photo` tipinde anlamlı) | dosya yükleme |
| `hide` | tümden gizlenebilir | aç/kapa kutusu |

`edit`'in ana şalter olması, panelde bir katmanı kilitlemenin **tek** kutu
olmasını sağlıyor. Alternatifi (beş kutuyu tek tek kapatmak) unutmaya açık ve
unutulan kutu müşteriye yetki demek.

Bunlardan **ayrı** iki bayrak (Faz 1 §2'de ayrımı yazılmış):

| Nerede | Soru | Sihirbazda |
|---|---|---|
| `palette.<ad>.customer` | müşteri **bu jetonu** değiştirebilir mi | tek seçim, jetonu kullanan bütün katmanlar döner |
| `fonts.<ad>.customer` | müşteri **bu yazı jetonunu** değiştirebilir mi | aynı |

### Panel etiketleri düzeltilir

`design-edit-sections.php` bugün `edit`, `color`, `hide` diye ham İngilizce
basıyor. 3B bu kelimelere anlam verdiğine göre etiketleri de veriyor: DE/TR
karşılıkları ve `edit`'in ana şalter olduğunu söyleyen tek cümle. Panelin geri
kalanına dokunulmuyor.

## 6. Sihirbaz adımları dokümandan türer

Adım listesi sabit değil; seçilen tasarım belirler. İki saf fonksiyon:

```php
DesignWizard::steps(array $doc): array     // hangi adımlar, sırasıyla
DesignWizard::choices(array $doc): array   // her adımın içinde ne sorulacak
```

`steps()` yalnızca adım anahtarlarını döndürür. `choices()` şablonun basacağı
alan listesini döndürür ve **sunucu tarafındaki tek doğru kaynaktır** — şablon
"şu izin açık mı" diye belgeye kendisi bakmaz:

```php
[
  'binds'   => ['couple_names', 'wedding_date', 'location_name'],   // dokümanda geçenler
  'palette' => ['accent' => [...]],        // yalnızca customer=true jetonlar
  'fonts'   => ['script' => [...]],        // yalnızca customer=true jetonlar
  'layers'  => [                           // yalnızca edit=true katmanlar
    'name-1' => ['color' => true, 'font' => false, 'text' => false, 'photo' => false, 'hide' => true],
  ],
]
```

`steps()` bu listenin üstünde çalışır: `layers` boşsa ve `palette`/`fonts` boşsa
`design` adımı yoktur.

| Adım | Ne zaman görünür | Neyi sorar |
|---|---|---|
| `angaben` | **her zaman** | dokümanda geçen `bind` anahtarlarının karşılığı — sadece onlar |
| `bilder` | `edit`+`photo` izinli katman varsa | katman başına dosya yükleme |
| `design` | `customer` işaretli jeton **ya da** `edit`+(`color`\|`font`\|`text`\|`hide`) izinli katman varsa | renk/yazı jetonları, katman başına renk/yazı/metin/gizle |
| `veroeffentlichen` | **her zaman** | canlı önizleme, adres (slug), yayınla |

### Neden `bind`, neden izin değil

Kararlar belgesi "sihirbaz izin bayraklarını okur" diyordu. Yarısı doğru; tek
başına alınırsa sihirbaz **boş açılır**. İki ayrı mekanizma var:

- **`bind`** (`Design.php:568`) — `couple_names`, `wedding_date`,
  `location_name`… Davetiye verisinden gelir, izne bakılmaz. *"Hangi alanları
  soracağım"* sorusunun cevabı budur: dokümanın katmanlarında hangi `bind`
  geçiyorsa o alan sorulur, geçmeyen sorulmaz.
- **`permissions`** — *"ekstra ne sunacağım"* sorusunun cevabı.

Bugünkü tohumda (`seed-designs.php`) izinlerin neredeyse hepsi `false`. Yani
Élysée bugün **iki adımda** biter: künye ve yayınlama. Bu doğru davranış — boş
bir "Design" adımı göstermek, doldurulacak hiçbir şeyi olmayan bir ekran demek.
Panelden izin açıldıkça sihirbaz kendiliğinden uzar.

Sorulmayan `bind` yoktur: dokümanda `hashtag` geçmiyorsa hashtag sorulmaz ve
davetiyede yer tutmaz.

### Betiksiz tarayıcı

Eski sihirbazdaki davranış korunur (`invite-wizard.php:5`): betik yoksa bütün
adımlar alt alta dizilir ve tek gönderimle çalışır. Adımları gösterip gizleyen
ve önizlemeyi tazeleyen `assets/invite-v2.js`, sunucu tarafındaki hiçbir kuralı
tekrarlamaz — süzgeç sunucuda, betik yalnızca görünürlük.

## 7. Veri

### `invitations_v2` satırı

Şema Faz 1'de kuruldu, **değişmiyor**:

```sql
invitations_v2 (slug, design_id, design_snapshot, data, created_at)
```

| Sütun | İçerik |
|---|---|
| `slug` | müşterinin seçtiği adres parçası, iki tabloda birden benzersiz (§3.2) |
| `design_id` | `$design['id']` — hangi tasarımdan doğduğu, `slug` değil (tasarım yeniden adlandırılabilir) |
| `design_snapshot` | `personalize()` çıktısı, `Design::complete()`'ten geçmiş tam doküman |
| `data` | aşağıdaki düz nesne |

### `data` şeması

```json
{
  "slug": "marie-jonas",
  "locale": "de",
  "bride": "Marie",
  "groom": "Jonas",
  "date": "2027-06-12",
  "time": "15:00",
  "venue": "Schloss Elmau",
  "address": "Elmau 2, 82493 Krün",
  "message": "…",
  "hashtag": "#mariejonas",
  "email": "",
  "paid": false,
  "manageKey": "…32 hex…",
  "createdAt": "2026-08-19T14:12:00+02:00"
}
```

**Düz, iç içe değil** — çünkü `Design::bindValues()` tam olarak bu alanları
okuyor (`bride`, `groom`, `date`, `time`, `venue`, `address`, `message`,
`hashtag`). Eski `invitations` kaydındaki `events[]` dizisi v2'ye **gelmiyor**:
çok etkinlik, bölümlerle birlikte Faz C'nin konusu ve bugün onu taşımak
`bindValues`'a hangi etkinliği soracağını sordurmak olur.

`closing` alanı da gelmiyor: dokümanda karşılığı olan bir `bind` yok. Gerekirse
formata `closing_text` eklemek C'nin işi.

### Boş bırakılan alan

Bir `bind` sorulup boş bırakılırsa `Design::resolveText()` zaten boş dize
döndürür ve `html()` o elementi hiç basmaz (`Design.php:487`). Yani eksik alan
davetiyeyi bozmaz, o satır görünmez. Zorunlu olan yalnızca ikisi: `bride` ve
`groom` — ikisi de boşsa davetiyenin kime ait olduğu belirsiz kalır.

## 8. Rotalar ve dosyalar

### Rotalar

`public/index.php`, mevcut satırlara dokunmadan, `/{locale}/v2/designs`
satırlarının altına:

```php
$router->any('/{locale}/v2/einladung', $page_(static fn (array $p) => (new InviteV2Controller())->wizard()));
$router->get('/{locale}/v2/einladung/{slug}', $page_(static fn (array $p) => (new InviteV2Controller())->show($p)));
```

Sıra önemli: sabit `/v2/einladung` desenli `{slug}`'dan **önce** gelir.

### Yeni dosyalar

| Dosya | İşi |
|---|---|
| `src/DesignWizard.php` | `steps()`, `choices()`, `personalize()` — saf, veritabanısız |
| `src/InvitationsV2.php` | `create()`, `find()`, `slugAvailable()` — ad temizliği için `Invitations::slug()` çağrılır, kopyalanmaz |
| `src/Controllers/InviteV2Controller.php` | `wizard()`, `show()` |
| `templates/pages/invite-v2-wizard.php` | form |
| `templates/pages/invite-v2-show.php` | davetiyenin kendisi |
| `templates/partials/design-stage.php` | sahne (§8.1) |
| `public/assets/invite-v2.js` | adım geçişi, canlı önizleme |
| `tests/design_wizard.php` | §10 |

### Değişen dosyalar

| Dosya | Değişiklik |
|---|---|
| `public/index.php` | iki rota |
| `data/dict.php` | yeni anahtarlar, üç dil kümesine de |
| `templates/pages/design-preview.php` | sahne partial'a taşınır (§8.1) |
| `templates/admin/design-edit-sections.php` | izin etiketleri (§5) |

`Design.php` **1097 satır** — sihirbazın saf fonksiyonları oraya değil ayrı bir
dosyaya gidiyor. Sınır net: `Design` formatı tanımlar ve basar; `DesignWizard`
müşterinin neye dokunabildiğine karar verir.

`InviteController.php`, `invite-wizard.php`, `Invitations.php`, `Themes.php`,
`Pricing.php` diff'te **hiç geçmez.**

### 8.1 Sahne partial'ı

`design-preview.php` 177 satır; ~148'i sahne (sayfa + zarf + kart + animasyon),
~29'u alt çubuk. Davetiye sayfası aynı sahneyi gerçek veriyle gösteriyor.

148 satırı kopyalamak yerine sahne `templates/partials/design-stage.php`
dosyasına çıkarılır; `$scope, $styles, $seite, $kuvert, $karte, $design, $locale`
alır. `design-preview.php` ve `invite-v2-show.php` onu içerir.

Bu 3A'nın çalışan sayfasına dokunmak demek. Mekanik bir taşıma: çıktı bayt bayt
aynı kalmalı ve planda kendi doğrulama adımı olacak (§12).

### Alt çubuk farkı

| | `design-preview.php` | `invite-v2-show.php` |
|---|---|---|
| panele girmiş | geliştirici çubuğu (3A'daki gibi) | yok |
| müşteri | "Alle Designs" + oluştur düğmesi | **hiçbir şey** |

Davetiye sayfasında alt çubuk yok: davetiyeyi açan misafirin işi kartla, bizim
katalogumuzla değil.

### Meta ve paylaşım

`show()` sayfası `noindex` — davetiye adresleri arama motoruna girmemeli.
Mekanizma hazır: `Seo::forPage('einladung2', ['noindex' => true])` → `layout.php:38`.
OG görseli 3B'de yok (`OgImage` eski kayıt şemasına bağlı); paylaşımda başlık
ve açıklama düz metin olarak gider.

## 9. Güvenlik

Hepsi mevcut yardımcılarla, yenisi icat edilmiyor:

| Ne | Nasıl |
|---|---|
| CSRF | `Security::checkCsrf($_POST['csrf'])`, eski sihirbazdaki gibi |
| Sel | `Security::throttle('invite-v2-create', 8, 900)` — eskisiyle aynı ölçü, **ayrı anahtar** (biri diğerini kilitlemesin) |
| Metin | `Security::clean()` alan başına sınırla |
| Yükleme | `Media::store()` — dosya adına değil **içeriğe** bakar (`getimagesize`), klasör `einladungen/v2/{slug}` |
| Görsel yolu | `Design::safeSrc()` zaten `/uploads` ve `/assets` dışını reddediyor; yüklenen dosya oradan geçer |
| Renk / yazı | `Design::safeColor()`, `safeFont()` — jetondan koparılan değerler de bu süzgeçten geçer |

### İzin süzgeci

`personalize()` **beyaz liste** çalışır: gelen her seçim için önce o katmanın
(ya da jetonun) izni sorulur, izin yoksa seçim **düşer**. Reddedilmez, hata
üretmez — sessizce yok sayılır.

Neden sessiz: izin panelden kapatılmış olabilir, form ise açıkken doldurulmuş.
Bunu hata sayfasıyla karşılamak müşteriye anlamsız gelir; yapması gereken
tasarımın kendi hâlini almaktır.

Slug'a `Invitations::slug()` uygulanır (aynı harf haritası, `ä→ae`, `ş→s`);
alınmışsa sonuna dört haneli onaltılık eklenir, eski motordaki davranış.

## 10. Testler

`tests/design_wizard.php` — `bin/test.php` altında, veritabanısız (çalıştırıcı
`config.php` yüklemiyor, bu yüzden saf fonksiyonlar):

| Test | Neyi tutuyor |
|---|---|
| `steps()` izinsiz belgede iki adım döndürüyor | boş adım gösterilmesin |
| `steps()` `photo` izinli katman gelince `bilder` ekliyor | adım listesi gerçekten dokümandan türesin |
| `steps()` `edit` kapalıyken diğer beş bayrağı saymıyor | ana şalter gerçekten şalter olsun |
| `steps()` `customer` işaretli jeton tek başına `design` adımını açıyor | jeton izni katman izninden bağımsız |
| `choices()` yalnızca dokümanda geçen `bind`'ları soruyor | olmayan alan sorulmasın |
| `personalize()` izinli rengi uyguluyor | izin verilen gerçekten çalışsın |
| `personalize()` izinsiz rengi düşürüyor | **güvenlik sınırı** |
| `personalize()` bilinmeyen katman kimliğini düşürüyor | uydurma kimlikle belge şişirilmesin |
| `personalize()` `customer=false` jetonu düşürüyor | jeton izni gerçekten kilit olsun |
| `personalize()` `bind` taşıyan katmanda `text` iznini yok sayıyor | dinamik alan sabit metinle ezilmesin |
| `personalize()` çıktısı `Design::complete()` ile aynı şekli koruyor | snapshot renderer'a doğrudan gidebilsin |
| `personalize()` girdiyi değiştirmiyor | saf kalsın, aynı belge iki davetiyeye kaynak olabilsin |

## 11. Kapsam dışı ve nedeni

| Ne | Neden | Faz |
|---|---|---|
| Ödeme, kupon, fiyat | `Pricing::total()` bölümlere bağlı (§3.1) | D |
| Vitrindeki düğmenin v2'ye çevrilmesi | ödemesiz kapı açar (§3.1) | D |
| Bölümler (`sections`), program, menü, müzik, video | formatın en az tanımlanmış parçası | C |
| Çok etkinlik (`events[]`) | bölümlerle aynı karar | C |
| RSVP | bölüm (§3.3) | C |
| Misafir listesi, kişiye özel adres | `guest_name` bind'ı format kararı (§3.4) | D |
| Yönetim sayfası (`manageKey` var, sayfası yok) | misafir listesiyle aynı yerde | D |
| E-posta ile bağlantı gönderme | yönetim bağlantısı olmadan gönderilecek tek şey adresin kendisi | D |
| Taslak kaydetme (`invite_drafts`) | eski tablo eski şemayı taşıyor; v2 taslağı ayrı karar | D |
| Panelde v2 davetiye listesi | `/admin/einladungen` eski tabloyu okuyor; yeni davetiyeleri **görmez** | D |
| Animasyon seçimi (eski adım 1) | v2'de tasarımın `animation` alanında, müşteri seçimi ayrı bir izin sorusu | sonra |
| OG görseli | `OgImage` eski kayıt şemasına bağlı | sonra |

**Panel v2 davetiyelerini görmüyor** — kararlar belgesinin uyardığı nokta. 3B
bunu bilerek bırakıyor: `/admin/einladungen` eski tabloyu okuyor ve ona
dokunmak "eskisine dokunulmaz" kararını deler. 3B'de üretilen davetiyeler
yalnızca kendi adreslerinden görünür. D bunu ele alacak.

## 12. Bitti sayılma ölçütü

- [ ] `/de/v2/einladung` açılıyor; tasarım `?design=<slug>` ile geliyor,
      gelmiyorsa aktif tasarımlardan seçtiriliyor.
- [ ] Élysée seçilince **iki adım** görünüyor (künye, yayınlama) — çünkü
      izinleri kapalı. Boş bir "Design" adımı yok.
- [ ] Panelden bir katmana `edit`+`color` verilince sihirbaz **üç adım**
      gösteriyor; tarayıcı tazelemesi dışında hiçbir şey yapılmadan.
- [ ] Yalnızca dokümanda geçen `bind`'lar soruluyor.
- [ ] Yayınla: `invitations_v2`'de satır var, `design_snapshot` dolu ve
      `Design::complete()` şeklinde.
- [ ] `/de/v2/einladung/{slug}` davetiyeyi gösteriyor: zarf, açılış, kart,
      girilen isim ve tarih yerinde.
- [ ] Tasarım panelden sonradan değiştirilince **yayınlanmış davetiye
      değişmiyor** — snapshot işini yapıyor.
- [ ] Eski motora dokunulmadı: `InviteController.php`, `invite-wizard.php`,
      `Invitations.php`, `Themes.php`, `Pricing.php` diff'te geçmiyor.
- [ ] `/de/v2/designs` ve `/de/v2/designs/elysee` sahne taşınmasından sonra
      **görünüş olarak aynı** (§8.1).
- [ ] `php bin/test.php` geçiyor; 3A'nın 275 kontrolü + yeni testler.
- [ ] Betiksiz tarayıcıda adımlar alt alta ve tek gönderimle davetiye oluşuyor.
- [ ] İzinsiz alanı zorlayan elle hazırlanmış POST sessizce düşüyor, davetiye
      tasarımın kendi hâliyle oluşuyor.

## 13. Riskler

**Sihirbaz hiçbir yerden bağlantılı değil.** 3B bitince müşteri ona adresi elle
yazmadan ulaşamaz. Bilinçli (§3.1) ama bir faz boyunca "bitmiş ama görünmeyen"
bir iş demek. Azaltma: D'nin ilk maddesi düğmeyi çevirmek olsun.

**İzinler kapalı olduğu için sihirbaz fakir görünüyor.** İki adımlık bir
sihirbaz "az iş yapılmış" izlenimi verebilir. Gerçekte iş, adımların
dokümandan türemesinde. Azaltma: kabul turunda bir katmana izin açılıp
sihirbazın uzaması gösterilsin — ölçüt listesinde madde olarak var.

**Sahne partial'ı 3A'nın çalışan sayfasına dokunuyor.** Mekanik bir taşıma ama
animasyon markup'ı hassas. Azaltma: taşıma kendi planlama adımı, öncesi ve
sonrası aynı sayfada karşılaştırılıyor.

**`data` düz olduğu için çok etkinlik sonradan geliyor.** C çok etkinliği
getirince `data` şekli değişecek ve 3B'de üretilmiş davetiyelerin okunması
gerekecek. Azaltma: düz şekil `bindValues()`'ın **bugünkü** sözleşmesi; C'de
değişecek olan `bindValues` da olduğu için ikisi birlikte ele alınır. Tek
etkinliği çok etkinliğe yükseltmek (`events[0]`) kayıp içermez.
