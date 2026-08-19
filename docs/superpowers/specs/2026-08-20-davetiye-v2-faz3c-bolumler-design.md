# Davetiye v2 — Faz 3C: Bölüm sistemi

**Tarih:** 2026-08-20
**Durum:** tasarım onaylandı, plan yazılacak
**Kapsam:** sadece `php/` — Next.js tarafı (`app/`, `lib/`) bu spec dışında ve hiç değişmiyor.
**Öncesi:** [Faz 1](2026-08-19-davetiye-v2-design.md) (format ve gösterim),
[Faz 2](2026-08-19-davetiye-v2-faz2-panel-design.md) (panelde düzenleme),
[Faz 3A](2026-08-19-davetiye-v2-faz3a-vitrin-design.md) (satış vitrini),
[Faz 3B](2026-08-19-davetiye-v2-faz3b-sihirbaz-design.md) (sihirbaz)

## 1. Amaç

3B'nin ürettiği davetiye bir karttan ibaret: zarf açılıyor, isimler ve tarih
görünüyor, bitiyor. Eski motorun davetiyesi ise kartın altında bir belge
taşıyor — adres ve harita, geri sayım, aileler, program.

Faz 1 bunun için `sections` alanını açtı ve **bilerek boş bıraktı**
(`Design.php:89`, `:158`). Faz C onu dolduruyor.

## 2. "Bölüm" iki ayrı şeyin adı — hangisi kastediliyor

Bu ayrım yazılmazsa kaçınılmaz olarak karışır:

| Nerede | Ne demek |
|---|---|
| **Eski motor** | Davetiye kaydındaki sekiz açma/kapama (`sections['rsvp']` …). Her biri `templates/pages/invitation.php` içinde elle yazılmış markup, kendi veri alanları ve kendi fiyatı. Sıra `Pricing::SECTION_KEYS` ile sabit. |
| **v2 formatı** | Tasarım belgesindeki `sections` dizisi (`Section[]`). Bugün her zaman boş. |

Faz C **ikincisini** kuruyor. Eski motora dokunulmuyor; `Pricing.php` bu fazın
diff'inde hiç geçmiyor.

## 3. Kararlar

### 3.1 Tasarımcı kurar, müşteri açıp kapar

Belgeler çelişiyordu: format `sections`'ı **tasarım belgesine** koymuş
(grafikerin eseri), ana spec ise Faz 3'e "müşteri sürükle-sırala + göster/gizle"
yazmıştı. İkisi aynı anda olamaz.

**Karar:** bölümler tasarım belgesinde yaşar. Hangi bölümler var, hangi sırada,
hangi renk ve yazı jetonuyla — grafikerin kararı. Müşteri yalnızca **izin
verilenleri açıp kapatır** ve içeriğini doldurur.

Gerekçe: 3B'nin bütün mimarisi buna dayanıyor. İzinler belgede yaşar, snapshot
onları dondurur, `personalize()` süzer. Sırayı davetiyenin verisine taşımak,
3B'de bilerek kaçındığımız "belge + üstüne yamalar" mantığını geri getirirdi —
ve o mantık doğduğu anda renderer, önizleme, panel ve D'deki düzenleme ekranı
onu ayrı ayrı bilmek zorunda kalır.

Bedeli açıkça: müşteri sırayı değiştiremez. Ana spec'in "sürükle-sırala"
sözü **iptal edildi**, ertelenmedi.

### 3.2 Dört tür; RSVP ve fiyatlı bölümler dışarıda

| Tür | Yazar mı | Fiyatı | Faz |
|---|---|---|---|
| `location` | hayır | 0 | **C** |
| `countdown` | hayır | 0 | **C** |
| `family` | hayır | 0 | **C** |
| `program` | hayır | 0 | **C** |
| `rsvp` | **evet** | 0 | C2 |
| `menu` | hayır | 19 € | D |
| `music` | hayır | 29 € | D |
| `video` | hayır | 49 € | D |

**Fiyatlı üçü D'de**, çünkü fiyat hesabı D'nin konusu. 3B'yi D'den ayırma
gerekçesi buydu: bölümsüz bir fiyat kuralı icat etmemek. Tersi de geçerli —
fiyatlı bölümleri C'ye almak C'ye fiyat mantığı sokar ve D gelince atılır.

**RSVP kendi dilimine (C2)**, çünkü yapısal olarak farklı: dördü salt gösterim,
RSVP tek **yazan** bölüm. Kendi POST yolu, kendi tablosu (`rsvps`), kendi
güvenlik yüzeyi var. C aynı anda hem yeni bir düzen modeli tanımlayıp hem yeni
bir yazma yolu açmasın — 3A'yı 3B'den ayıran mantığın aynısı.

`rsvps` tablosunda slug çakışması riski yok: 3B `InvitationsV2::slugAvailable()`
ile slug'ı **iki tabloda birden** benzersiz kıldı, yani C2 geldiğinde ortak
tablo güvenle kullanılabilir ve şema değişmez.

### 3.3 Sahne davetiye sayfasında akışa girer

**Kısıt:** v2'de sayfa, zarf ve kartın üçü birden tek bir
`fixed inset-0 z-50` sahnenin içinde (`design-stage.php:62`). Zarf açılınca
`display:none` oluyor ama sahne kalıyor — davetiye için doğru, kart görünmeye
devam etmeli. Sonucu: **sahnenin altında hiçbir şey kayamaz.**

Eski motor bunu başka türlü kurmuş: kart normal akışın içinde, yalnızca
**zarf** tam ekran katman (`invitation.php:136` dış sarmalayıcı
`relative min-h-screen`, `:228` zarf `fixed inset-0`).

**Karar:** paylaşılan partial bir kip kazanır.

```php
<div class="<?= e($scope) ?> d-stage <?= $fest ? 'fixed inset-0 z-50' : 'relative min-h-screen' ?> overflow-hidden"
```

- Vitrin önizlemesi `$fest = true` → **bugünkü dize birebir**.
- Davetiye sayfası `$fest = false` → ekran yüksekliğinde normal blok, altında
  bölümler akar.

Sahnenin **içindeki** hiçbir şey değişmiyor: `d-page` ve `d-envelope` ikisi de
`absolute inset-0`, yani pencereye değil sahneye göre konumlanmışlar
(`design-stage.php:66`, `:117`). Her iki kipte de doğru davranıyorlar.

Reddedilen iki yol:

- **Bölümler kart yuvasında katman olsun.** Formatla en tutarlısı ama çalışmaz:
  3 satırlık program ile 12 satırlık program aynı yüzde kutusuna sığmaz.
  Değişken uzunluktaki içerik mutlak konumlanamaz.
- **Bölümler yukarı kayan ayrı bir katman olsun.** Sahneye dokunmaz ama yeni
  bir etkileşim modeli ve daha çok JavaScript icat eder; müşterinin alışık
  olduğu davranış da değil.

## 4. `Section`

Katmanlarla **aynı kalıp** — kasıtlı: panel, sihirbaz ve snapshot mantığı zaten
o kalıba göre kurulu, ikinci bir kalıp öğrenmek gerekmesin.

| Alan | Tip | Not |
|---|---|---|
| `id` | string | belgede tekil, `a-z0-9-` (`Design::key()` normalizasyonu) |
| `type` | string | sabit katalog: `location` \| `countdown` \| `family` \| `program` |
| `title` | `{de, en}` | misafire görünen başlık; boşsa başlık basılmaz |
| `enabled` | bool | tasarımcının varsayılanı |
| `style` | `{color, font}` | **jeton anahtarı**, ham değer değil — katmanlardaki gibi |
| `permissions` | `{edit, hide}` | `edit` ana şalter; kapalıysa `hide` sayılmaz |

**Sıra = dizi sırası.** `layers`'ta sıranın z-index olması gibi; ikinci bir
`order` alanı doğmuyor.

`style.color` ve `style.font` jeton anahtarıdır ve renderer onları
`var(--d-<anahtar>)` / `var(--df-<anahtar>)` olarak basar — Faz 3B §5.1'de
öğrenilen ders: ham değer yazmak geçersiz CSS üretir.

### Tanınmayan tür düşer

`complete()` kataloğa uymayan `type`'ı **sessizce atar**. Gerekçe Faz 1'in
kuralıyla aynı: panelden ya da JSON'dan gelen bir değer yüzünden tasarım
açılmamazlık etmesin.

## 5. Katalog neden sabit — formatın vaadinin büküldüğü tek yer

v2'nin sözü şuydu: tasarım **veridir**, yeni tasarım için kod yazılmaz.
Bölümlerde bu kısmen bükülüyor ve nedeni yazılmalı:

Bir geri sayımın tiklemesi, bir harita bağlantısının adres kodlaması kod ister.
Bunlar katman değil **bileşen**. Yani:

- bölümleri **dizmek, biçimlendirmek, açıp kapamak, doldurmak** → veri
- **yeni bir bölüm türü eklemek** → kod

Bu bilinçli bir sınır, eksiklik değil. Alternatifi (bölümleri de serbest
katmanlarla ifade etmek) geri sayımı ve haritayı ifade edemez.

## 6. Neyin nereye gittiği

3B'nin kuralı aynen sürüyor, uzatılıyor:

```
bölümün açık/kapalı olması, sırası, rengi  ──►  design_snapshot   (belge)
aile isimleri, program satırları           ──►  data              (davetiye)
```

Müşteri bir bölümü kapatırsa `personalize()` bunu belgeye işler ve donar. Ayrı
bir "müşteri tercihleri" tablosu doğmaz.

### `data`'nın büyümesi

3B `data`'yı düz tutmuştu, çünkü `Design::bindValues()` düz okuyor. Bölüm
içeriği **bind değil**, yani `bindValues()`'un sözleşmesine dokunmadan iç içe
durabilir:

```json
{
  "families": { "bride": "Familie Weber", "groom": "Familie Yılmaz" },
  "program":  [ { "time": "15:00", "title": "Trauung" },
                { "time": "18:30", "title": "Dinner" } ]
}
```

`bindValues()` bu iki anahtarı görmez ve görmemeli — kartın üstündeki dinamik
alanlar bölümlerden bağımsız kalır.

### Hangi tür neyi okur

| Tür | Veriden | Yeni veri | Kod gerektiren yanı |
|---|---|---|---|
| `location` | `venue`, `address` | yok | harita bağlantısı üretmek |
| `countdown` | `date`, `time` | yok | tiklemek (JS) |
| `family` | — | `families` | yok |
| `program` | — | `program` | yok |

İlk ikisi 3B'nin **zaten sorduğu** alanlardan besleniyor: sıfır yeni soruyla
davetiye içerik kazanıyor.

## 7. Boş bölüm basılmaz

`program` açık ama satır yoksa görünmez. `location` açık ama adres yoksa
görünmez. `countdown` geçmiş bir tarihte görünmez.

Bu, `Design::html()`'in boş bağlı metni atlaması (`Design.php:501`) ile aynı
kural — ikinci kez icat edilmiyor, aynı cümle bölümler için de geçerli.

Sonucu: müşteri bir bölümü kapatmak zorunda değil; doldurmadığı bölüm zaten
görünmez.

## 8. Kod nerede duracak

Yeni dosya `php/src/DesignSections.php`. Sınır net:

| Sınıf | Sorumluluğu |
|---|---|
| `Design` | format ve kart/zarf/sayfa basımı |
| `DesignWizard` | müşteri neye dokunabilir |
| `DesignSections` | bir bölüm nedir, hangileri görünür, nasıl basılır |

`Design.php` 1100+ satır; 3B'de aynı gerekçeyle `DesignWizard` ayrılmıştı.
Hepsi saf fonksiyon — veritabanı, oturum, `$_POST` yok — yani `bin/test.php`
altında `config.php` olmadan koşar.

### Üretilen arayüz

```php
DesignSections::complete(array $doc): array          // sections'ı normalize eder
DesignSections::visible(array $doc, array $data): array   // gerçekten basılacaklar
DesignSections::html(array $doc, array $data, string $locale): string
DesignSections::css(array $doc, string $scope): string    // bölüm başına jeton kuralları
```

`visible()` ayrı duruyor çünkü hem renderer hem sihirbaz onu soruyor: renderer
basmak için, sihirbaz "bu bölüm için içerik sormalı mıyım" diye.

## 9. Sihirbazın kazandığı adım

`DesignWizard::steps()` beşinci anahtarı öğrenir: `abschnitte`. Görünme kuralı
bugünküyle **aynı mantıkta** — belgede müşterinin dokunabileceği bir bölüm
(`edit`+`hide`) ya da doldurması gereken içerik (`family`, `program`) varsa
görünür, yoksa görünmez. Boş adım yasağı sürüyor.

`choices()` bir anahtar daha döndürür:

```php
'sections' => [
  'prog-1' => ['type' => 'program', 'hide' => true,  'fields' => ['program']],
  'ort-1'  => ['type' => 'location', 'hide' => false, 'fields' => []],
]
```

Şablon yine hiçbir karar vermiyor; `choices()` tek doğru kaynak olmaya devam
ediyor.

Adım sırası: `angaben` → `bilder` → `abschnitte` → `design` → `veroeffentlichen`.
Bölümler künyeden sonra, biçimden önce — müşteri önce **ne yazacağını**, sonra
**nasıl görüneceğini** düşünsün.

## 10. `personalize()` ve güvenlik sınırı

Bölüm açma/kapama `personalize()` üzerinden belgeye uygulanır ve aynı beyaz
liste kuralına tabidir: `edit` kapalıysa ya da `hide` verilmemişse gelen seçim
**sessizce düşer**. Katmanlarda olduğu gibi hata sayfası üretilmez — izin
panelden kapanmış, form açıkken doldurulmuş olabilir.

Bölüm **içeriği** (`families`, `program`) `publish()` üzerinden `data`'ya gider
ve `Security::clean()` ile sınırlanır. Program satırı sayısı ve satır uzunluğu
üst sınırlıdır; sınır aşılırsa fazlası atılır, istek reddedilmez.

## 11. Panel editörü

Faz 2'nin editörü sekiz bölümlüydü; dokuzuncusu geliyor: **Bölümler**.
Tür ekle/sil, yukarı/aşağı sırala, başlık (de/en), renk ve yazı jetonu,
`edit`/`hide` kutuları.

`Design::fromPost()` bugün `sections`'a **bilerek dokunmuyor**
(`Design.php:869`: "die Abschnitte der dritten [Phase]"), ve o sınırı
`tests/design_admin.php:90` tutuyor. Faz C o testi **kasıtlı olarak**
değiştirir; test dosyasına neden değiştiğini yazar. Sınırın kaldırıldığı gün
kayda geçsin diye.

Sürükle-sırala arayüzü **yok**: sıra tasarımcıda ve panelde yukarı/aşağı
düğmesi yeter. Sürükleme kendi başına bir iş ve bu fazın konusu değil.

## 12. Testler

`php/tests/design_sections.php`, saf ve veritabanısız:

| Test | Neyi tutuyor |
|---|---|
| `complete()` bilinmeyen `type`'ı düşürüyor | uydurma tür belgeye girmesin |
| `complete()` altı alanı da varsayılanlıyor | eksik alan basımda çökmesin |
| sıra dizi sırasından geliyor | ikinci bir `order` alanı doğmasın |
| `edit` kapalıyken `hide` sayılmıyor | ana şalter bölümlerde de şalter |
| `visible()` içeriksiz `program`'ı düşürüyor | boş başlık görünmesin |
| `visible()` adressiz `location`'ı düşürüyor | eksik veri boş kutu üretmesin |
| `visible()` geçmiş tarihte `countdown`'ı düşürüyor | geçmiş düğüne geri sayım olmaz |
| `visible()` `enabled=false` bölümü düşürüyor | tasarımcının kapattığı kapalı kalsın |
| `html()` başlığı boşken başlık basmıyor | boş `<h2>` çıkmasın |
| `css()` jetonu `var(--d-…)` olarak basıyor | ham renk geçersiz CSS üretmesin |
| `steps()` dokunulamaz bölümlerde `abschnitte` açmıyor | boş adım yasağı sürüyor |
| `choices()['sections']` yalnızca `edit`'li bölümleri veriyor | tek doğru kaynak kuralı |
| `personalize()` izinsiz bölüm kapatmayı düşürüyor | **güvenlik sınırı** |
| `personalize()` izinli kapatmayı belgeye işliyor | izin verilen gerçekten çalışsın |

Ayrıca `tests/design_admin.php`'deki sınır testi güncellenir (§11).

Sahnenin `$fest = true` dalını bir PHP testi **tutmuyor**; onu vitrin
sayfasının bayt bayt karşılaştırması tutuyor (`curl` + `diff`, Faz 3B'de
kurulan yöntem). Gerekçe: bir birim testi yalnızca sınıf dizesini
doğrulayabilirdi, oysa karşılaştırma bütün sayfanın değişmediğini gösteriyor —
kapsamı geniş olan kazandı.

## 13. Kapsam dışı ve nedeni

| Ne | Neden | Faz |
|---|---|---|
| RSVP bölümü | tek yazan bölüm; kendi POST yolu ve güvenlik yüzeyi (§3.2) | C2 |
| Menü, müzik, video | fiyatlı; fiyat hesabı D'nin konusu (§3.2) | D |
| Çok etkinlik (`events[]`) | `bindValues()` sözleşmesini değiştirir, ayrı karar | sonra |
| Sürükle-sırala arayüzü | sıra tasarımcıda; yukarı/aşağı yeter (§11) | — |
| Müşterinin sıra değiştirmesi | §3.1 ile **iptal edildi**, ertelenmedi | — |
| Vitrindeki düğmenin v2'ye çevrilmesi | ödemesiz kapı açar; 3B'nin kararı sürüyor | D |
| Bölüm başına arka plan görseli | katman değil bölüm; gerekirse ayrı karar | sonra |

## 14. Bitti sayılma ölçütü

- [ ] Panelde bir tasarıma dört türün dördü de eklenebiliyor, sıralanabiliyor,
      başlık ve jeton verilebiliyor.
- [ ] `edit`+`hide` verilen bölüm sihirbazda görünüyor; verilmeyen görünmüyor.
- [ ] Sihirbaz `family` ve `program` için içerik soruyor, `location` ve
      `countdown` için sormuyor — ikisi 3B'nin zaten sorduğu alanlardan besleniyor.
- [ ] Yayınlanan davetiyede kart yukarıda duruyor, altında açık bölümler
      sırayla akıyor.
- [ ] Doldurulmamış bölüm basılmıyor; geçmiş tarihte geri sayım basılmıyor.
- [ ] Tasarım panelden sonradan değiştirilince yayınlanmış davetiyenin
      bölümleri **değişmiyor** — snapshot işini yapıyor.
- [ ] `/de/v2/designs/elysee` vitrin önizlemesi 3C öncesiyle **bayt bayt aynı**
      (`$fest = true` dalı).
- [ ] İzinsiz bölüm kapatmayı zorlayan elle hazırlanmış POST sessizce düşüyor.
- [ ] Eski motora dokunulmadı: `InviteController.php`, `invitation.php`,
      `invite-wizard.php`, `Invitations.php`, `Themes.php`, **`Pricing.php`**
      diff'te geçmiyor.
- [ ] `php bin/test.php` geçiyor; 3B'nin 339 kontrolü + yenileri.
- [ ] Betiksiz tarayıcıda bölümler görünüyor (geri sayım hariç — o tikleyemez,
      ama tarihi yine de basar).

## 15. Riskler

**Sahnenin kipi vitrini bozabilir.** `$fest` dalı yanlış kurulursa vitrin
önizlemesi değişir. Azaltma: 3B'nin bayt bayt `diff` ölçütü ve taban çizgileri
duruyor; aynı yöntem burada da uygulanacak ve kendi görev adımı olacak.

**Katalog sabit olduğu için beşinci tür kod ister.** Bu bilinçli (§5), ama
müşteri "bir bölüm daha" istediğinde cevap "kod yazmalıyız" olacak. Azaltma:
dört tür eski motorun ücretsiz bölümlerinin tamamı; beşinci istek gelene kadar
katalog yeterli.

**Bölüm içeriği `data`'yı büyütüyor.** `program` bir liste ve listeler zamanla
büyür. Azaltma: satır sayısı ve uzunluğu sınırlı (§10); sınır aşılırsa fazlası
sessizce atılır.

**C2 ve D aynı bölüm listesine dokunacak.** Üç faz aynı `type` kataloğunu
büyütüyor. Azaltma: katalog tek yerde (`DesignSections`), ve her yeni tür
kendi testini getiriyor.
