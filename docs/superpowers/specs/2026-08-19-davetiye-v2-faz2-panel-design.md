# Davetiye v2 — Faz 2: Panelde tasarım kataloğu ve editör

**Tarih:** 2026-08-19
**Durum:** tasarım onaylandı, plan yazılacak
**Öncesi:** [Faz 1 spec'i](2026-08-19-davetiye-v2-design.md) — format, gösterim, iki tasarım (Élysée, Noir) veriden sürülüyor

## 1. Amaç

Faz 1 tasarımı bir belgeye çevirdi ve o belgeden sayfa üretti; ama belgeyi
değiştirmenin tek yolu komut satırı (`php bin/seed-designs.php`). Faz 2 bunu
panele taşıyor: Ayhan tasarımın rengini, yazısını, metnini, görselini ve
hareketini kendi değiştirebilsin, yenisini kopyalayabilsin, yayına almadan önce
neyin eksik olduğunu görebilsin.

Faz 2'nin ürettiği şey **bir editör**, bir builder değil. Katmanların yeri ve
ölçüsü Faz 4'e ait; burada yüzey düzenleniyor.

## 2. Kapsam

**Var:**

- Panelde görsel kartlı tasarım kataloğu, kategori süzgeci
- Kopyalama ve eski temalardan aktarma
- Aktif/pasif
- Sekiz bölümlü form editörü, kaydetmeden canlı önizleme
- Yayın kontrol listesi — uyarır, engellemez

**Yok, bilerek:**

| Ne | Nerede |
|---|---|
| Katman kutusu (`box`: x/y/w/rotate/anchor/flip) | Faz 4 — görsel builder |
| Katman ekleme/silme/sıralama | Faz 4 |
| `canvas` (oran, güvenli alan) | ölçülmüş sözleşme; değişirse tasarım yeniden ölçülür |
| `sections` (metin, geri sayım, program, RSVP…) | Faz 3 |
| Tasarım silme | Faz 3 — önce "bu tasarımdan kaç davetiye çıktı" sayacı gerekiyor |
| Müşteri tarafı (satış sayfası, sihirbaz) | Faz 3 |

## 3. Kararlar

Dördü de 2026-08-19'da soruldu ve yanıtlandı:

1. **Editör yüzeyi düzenler.** Renk, yazı tipi, sabit metin, görsel, hareket ve
   müşteri izinleri. Geometri Faz 4'te.
2. **Canlı önizleme, kaydetmeden.** Aynı sayfada, gerçek kartın küçültülmüş
   hâli üzerinde.
3. **Yayın kontrolü uyarır, engellemez.** Aktife alma düğmesi uyarı sayısını
   söyler ve onay ister.
4. **Yeni tasarım iki yoldan doğar:** kopyalama ve eski temadan aktarma.

## 4. Mimari

Yeni dosya: `php/src/Controllers/DesignAdminController.php`. Faz 1'de
`DesignController::admin()` içinde duran salt okunur liste oraya taşınır —
genel sayfalar (`index`, `preview`) ile panel işleri aynı sınıfta durmasın.

| Rota | İş |
|---|---|
| `any /{locale}/admin/designs` | Katalog. POST'ta `was`: `kopyala`, `temadan`, `durum` |
| `any /{locale}/admin/designs/{slug}` | Editör. POST'ta `was=kaydet` |

Panelin kalıbı korunur: `Admin::requireLogin()` (ziyaret sayacını kendisi
tutar — elle `recordVisit()` çağrılmaz), her POST'ta `Security::checkCsrf()`,
eylemden sonra kendi adresine 302.

**Birleştirme saf bir fonksiyonda:** `Design::fromPost(array $doc, array $post): array`.
Kayıtlı belgeyi ve POST'u alır, yeni belgeyi döndürür, yan etkisi yoktur.
Kontrolörde oturum, CSRF, yönlendirme ve kayıt kalır. Sebep: koşucu saf
fonksiyon sınıyor, ve bu fazın sınırı (geometriye dokunmama) ancak sınanabilir
bir yerde durursa gerçekten durur.

**Belge istemciden gelmez.** Kayıtlı belge okunur, POST alanları onun üstüne
uygulanır, sonra `Design::complete()`. Formun eksik ya da fazladan alanı
belgenin yapısını bozamaz; bilinmeyen enum değeri formatın kendi kuralıyla
varsayılana düşer.

## 5. Katalog ekranı

`php/templates/admin/designs.php` yeniden yazılır.

- **Görsel kartlar:** her kart gerçek tasarımın küçültülmüş hâli —
  `Design::css($design, '.d-' . $id)` + `Design::html()`, genel katalogdaki
  yöntemin aynısı. Panelde görünen, müşterinin göreceğinin ta kendisi.
- Kart altında: ad, kategori, durum rozeti, sürüm, katman sayısı, uyarı sayısı
  (0 ise em dash, değilse `text-gold`).
- Kart eylemleri: **Düzenle**, **Kopyala**, **Aktif/Pasif**, **Önizle** (genel
  önizleme, yeni sekme).
- Üstte kategori süzgeci — kategoriler veriden gelir, sabit liste değil.
- **Temadan oluştur:** tema + ad + slug. `Design::fromTheme()` belgeyi kurar,
  kayıt `draft` doğar, doğrudan editöre düşer. Sahne sanatı dosyaları yoksa
  uyarı: önce `php bin/export-scene-art.php <id>`.

Sınıflar panelin kendi paletinden (`text-muted`, `border-sand-deep`,
`text-gold`). Tailwind'in varsayılan gri/amber tonları derlenmiş CSS'te yok ve
yazılırsa hiçbir şey yapmaz — Faz 1'de üç kez yaşandı.

## 6. Editör bölümleri

Faz tablosu yedi bölüm sayıyordu; sekiz oluyor, çünkü "Yazılar" tek başlık
altında hem yazı tipi markalarını hem sabit metinleri taşıyamaz: ikisi belgede
ayrı yerde durur ve ayrı izinlere bağlanır.

| Bölüm | Alanlar | Belgede |
|---|---|---|
| Genel | ad (de/en), kategori, etiketler, sıra | `name`, `category`, `tags`, `sort` |
| Renkler | marka başına değer + görünen ad (de/tr) | `palette.<ad>.value`, `.label` |
| Yazı tipleri | aile (üç kendi barındırdığımız yazıdan biri), ağırlık, laufweite, satır yüksekliği, ölçek | `fonts.<ad>` |
| Metinler | sabit metinli katmanların de/en karşılığı; `bind` taşıyanlar salt okunur | `layers[].text` |
| Görseller | katman dosyaları | `layers[].src` |
| Animasyon | `intro`, `idle`, `card`, `nameMove`, `particle`, `reveal`, hız/gecikme; katman başına hareket | `animation.*`, `layers[].motion` |
| Müşteri izinleri | katman başına `Design::PERMISSIONS`; renk ve yazı markalarının `customer` bayrağı | `layers[].permissions`, `palette.*.customer`, `fonts.*.customer` |
| Yayın | durum, uyarı listesi, aktife alma | `status` |

`cover` alanı belgede duruyor ama Faz 2'de düzenlenmiyor: katalog kartı
tasarımın kendisini basıyor, yani bir kapak görseline ihtiyaç yok. Bir gün
paylaşım görseli (OG) gerekirse o zaman anlam kazanır; şimdi form alanı açmak,
kimsenin bakmadığı bir alanı doldurtmak olur.

Durum iki yerden değişebilir — katalogdaki aç/kapa ve editördeki Yayın bölümü —
ama ikisi de aynı sunucu yolundan geçer: uyarılar yeniden hesaplanır, onay
istenir, sonra yazılır. İki kapı, tek kilit.

## 7. Canlı önizlemenin sınırı

Önizleme kutusu kartın gerçek işaretlemesidir: sayfa yüklenirken sunucu
`Design::css()` + `Design::html()` ile basar, `.d-<id>` kapsamında,
küçültülmüş. Ayrı bir "temsilî kart" yoktur.

**Anında değişenler** — JS yalnızca CSS değişkenlerine ve metin düğümlerine
dokunur, yeniden çizim yok:

- renk markaları (`--d-*`)
- yazı tipi alanları (`--df-*`, ağırlık, laufweite, satır yüksekliği)
- sabit metinler

**Kaydetmek gerekenler:** animasyon eksenleri ve katman hareketleri (keyframe'i
`Design::css()` sunucuda seçer), görsel dosyası değişimi. Bu alanların yanında
"kaydet ve tam ekran aç" düğmesi durur.

Sebep: JS'e keyframe üretme işi verilirse panelde bir doğruluk, sayfada başka
bir doğruluk olur — ve ikisi zamanla ayrışır.

## 8. Kaydetme, sürümleme, çakışma, yayın

- **Sürüm kendiliğinden.** `Design::save()` içerik değişmediyse sürümü
  artırmaz (Faz 1'den, testi var). Üç kez kaydetmek üç sürüm üretmez.
- **Çakışma.** Formda gizli `version` taşınır; kayıttaki sürümden küçükse
  kaydedilmez: "bu tasarım sen açtıktan sonra başka bir yerde değiştirildi".
- **Yayın onayı.** Aktife alma POST'unda uyarılar sunucuda yeniden hesaplanır.
  Uyarı varsa düğme sayıyı söyler ve onay ister; onaylanınca geçer.
- **Görsel yükleme Faz 2'de yok.** Görseller bölümü katmanın dosya yolunu
  gösterir ve düzenletir, ama dosya yüklemez. Sebebi kapsam değil ihtiyaç:
  bugünkü iki tasarımın görsel katmanlarının hepsi
  `bin/export-scene-art.php`'nin ürettiği SVG'ler, ve onları elle değiştirmek
  zaten yanlış — bir sonraki dışa aktarma sessizce ezer. Gerçek bir fotoğraf
  katmanı (`type=photo`) ilk kez gerektiğinde yükleme eklenir ve **o zaman da**
  mevcut `Media` sınıfından geçer, yalnızca `public/uploads/` altına yazar.

## 9. Testler

`php/tests/design_admin.php`:

| Test | Neyi tutuyor |
|---|---|
| Bilinmeyen enum varsayılana düşüyor | elle düzenlenmiş form çökertmesin |
| İzin bayrakları gidip geliyor | Faz 3 bunlara güvenecek |
| Renk değeri temizleniyor, `rgba()` korunuyor | mevcut temalar `rgba` kullanıyor |
| Boş metin eski değeri silmiyor | yanlışlıkla boşaltma veri kaybı olmasın |
| Eski sürümle kaydetme reddediliyor | çakışma sessiz kalmasın |
| `box`, `canvas`, `sections` POST'tan hiç etkilenmiyor | fazın sınırı testle dursun |

Sonuncusu bu fazın bekçisi: editör yüzeyi düzenler, geometriye dokunmaz — ve
bunu bir cümle değil, bir test söyler.

## 10. Bitti sayılma ölçütü

- [ ] Katalogda iki tasarım görsel kartla duruyor, kategori süzgeci çalışıyor
- [ ] Kopyalama yeni slug ile yeni bir taslak üretiyor ve editöre düşürüyor
- [ ] Temadan oluşturma 14 temanın herhangi biriyle çalışıyor; sahne dosyası
      yoksa açıkça söylüyor
- [ ] Renk ve yazı değişikliği önizlemede **kaydetmeden** görünüyor
- [ ] Kaydedip genel sayfayı açınca aynısı görünüyor (panel ile sayfa ayrışmıyor)
- [ ] Uyarılı bir tasarım aktife alınırken sayı söyleniyor ve onay isteniyor
- [ ] İki sekmede aynı tasarım açılıp ikisi de kaydedilince ikincisi reddediliyor
- [ ] `git diff` ile: `box`, `canvas`, `sections` alanlarına yazan tek satır yok
- [ ] Eski panel sekmeleri ve genel sayfalar bozulmamış (hepsi 200)

## 11. Riskler

**Form büyür.** Sekiz bölüm, katman başına alanlar — Élysée'de 14 katman var.
Azaltma: bölümler `details` ile kapalı doğar, katman alanları yalnızca ilgili
bölümde görünür, ve geometri hiç girmez.

**Canlı önizleme ile gerçek sayfa ayrışabilir.** Azaltma: önizleme gerçek
işaretlemenin kendisidir; JS yalnızca değişken ve metin dokunur. Keyframe
üretimi istemciye hiç geçmez.

**İki kişi aynı anda düzenler.** Azaltma: gizli sürüm alanı ve reddetme.

**Faz 4'ün işi buraya sızabilir.** "Bir de şu kutuyu buradan ayarlayalım" en
kolay eklenen şeydir. Azaltma: sınır testle bekçilidir.
