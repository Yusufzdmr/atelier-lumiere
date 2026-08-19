# Davetiye v2 — Faz 3A: Satış vitrini

**Tarih:** 2026-08-19
**Durum:** tasarım onaylandı, plan yazılacak
**Öncesi:** [Faz 1](2026-08-19-davetiye-v2-design.md) (format ve gösterim), [Faz 2](2026-08-19-davetiye-v2-faz2-panel-design.md) (panelde düzenleme)

## 1. Faz 3 tek spec değil

Spec'in faz tablosunda "Faz 3" diye yazılan şey dört bağımsız alt sistem:

| | Ne | Durum |
|---|---|---|
| **A** | Satış vitrini: katalog, süzgeç, tam ekran demo | **bu belge** |
| **B** | Beş adımlı sihirbaz (izin bayraklarını okur) | kendi spec'i |
| **C** | Bölüm sistemi (`sections`) | kendi spec'i; formatın en az tanımlanmış parçası |
| **D** | Ödeme / kupon / RSVP devralma | B ve C bitmeden anlamsız |

Sıra A → B → C → D. Her biri kendi başına çalışan bir yazılım üretiyor.

## 2. Amaç

Bugün `/{locale}/v2/designs` bir karşılaştırma sayfası: "aynı vorlagalar, ama
veriden". Müşteri için yazılmamış. A onu **vitrine** çeviriyor: müşteri
tasarımları görür, tam ekran dener, beğendiğiyle davetiye oluşturmaya gider.

## 3. Kapsam

**Var:** vitrin sayfası (başlık, kategori süzgeci, kartlar), tam ekran demo,
"bu tasarımla oluştur" düğmesi, yalnızca `active` tasarımların görünmesi.

**Yok:** fiyat gösterimi (davetiyenin ayrı bir fiyatı yok; paket fiyatları
başka bir yerde ve o ayrı bir karar), v2 sihirbazı (B), bölümler (C),
ödeme/kupon (D).

## 4. Kararlar

1. **Vitrin `/{locale}/v2/designs`'in yerine geçer.** Eski `/{locale}/designs`
   ve eski sihirbaz **el değmeden** kalır; iki motor yan yana durmaya devam
   eder ve hangisinin kalacağı Faz 3 bitince karara bağlanır.
2. **"Oluştur" düğmesi bugün eski sihirbaza gider:**
   `/{locale}/einladung?design=<slug>`.

## 5. Oluştur düğmesinin kuralı

Eski sihirbaz `?design=` parametresini **tema kimliklerine karşı sınıyor**
(`InviteController.php:74`) ve tanımadığını sessizce yok sayıyor. Yani:

- `elysee`, `noir` gibi v2 slug'ı bir temayla aynıysa → sihirbaz o tasarımla açılır.
- Panelden kopyalanmış bir tasarımda (`elysee-nacht`) karşılık yoktur → sihirbaz
  hiçbir şey seçmeden açılır.

Bu yüzden düğme **yalnızca aynı kimlikte bir tema varsa** basılır. Yoksa yerine
sessiz bir not gelir: "bu tasarım yakında sihirbazda". Sessizce çalışmayan bir
düğme, olmayan düğmeden kötüdür.

Karar `Design::creatable(array $designs, array $themeIds): array` içinde —
hangi slug'ların sihirbaza gidebildiğini döndüren saf bir fonksiyon. Şablon
karar vermez; testi vardır. B geldiğinde bu kural tek yerde değişir.

## 6. Vitrin sayfası

- Başlık ve bir paragraf **sözlükten** (`invitation2.title`, `invitation2.lead`),
  sabit metin değil — panelin "Sayfa metinleri" sekmesi onları düzenleyebilsin.
- **Kategori süzgeci** veriden gelir (`luxury`, `modern`, …), adres satırında
  taşınır (`?kategorie=`), sabit liste değildir.
- Kartlar: `Design::all('active')`, `sort` sonra ada göre sıralı. Kart, gerçek
  davetiyenin kendisi (`Design::css()` + `html()`), altında ad ve kategori, iki
  düğme: **Ansehen** (tam ekran demo) ve **Mit diesem Design erstellen**.
- Hiç aktif tasarım yoksa sayfa boş kalmaz: bir cümle ve iletişim bağlantısı.

## 7. Tam ekran demo

`/{locale}/v2/designs/{slug}` bugünkü hâliyle kalıyor — zarf, açılış, kart —
**bir şey dışında:** alt çubuk şu an geliştirici çubuğu ("Élysée · luxury ·
Fassung 5 · 15 Ebenen · Auftakt darkroom"). Müşteri onu görmemeli.

- Panele girmiş kullanıcı için çubuk aynen kalır (Faz 1'den beri işe yarıyor).
- Müşteri için yerine iki bağlantı gelir: **Alle Designs** ve **Mit diesem
  Design erstellen** (aynı kural, §5).

Karar `Admin::isLoggedIn()` ile verilir; yeni bir bayrak icat edilmez.

## 8. Kapsam dışı bırakılan ve nedeni

| Ne | Neden |
|---|---|
| Fiyat | Davetiyenin ayrı fiyatı yok; paket fiyatları `site_content` içinde ve metin olarak duruyor (bkz. `docs/backlog/2026-08-19-fiyat-hesaplayici.md`). Fiyat göstermek önce o veriyi sayıya çevirmeyi gerektirir |
| Arama | İki tasarımla arama kutusu gürültü olur; kategori süzgeci yeter |
| Favori / karşılaştırma | Müşteri hesabı yok; Faz 5'in konusu |

## 9. Testler

`php/tests/design_showcase.php`:

| Test | Neyi tutuyor |
|---|---|
| `creatable()` yalnızca temayla eşleşen slug'ları döndürüyor | sessizce çalışmayan düğme olmasın |
| `creatable()` boş tema listesinde boş dönüyor | tema silinirse vitrin kırılmasın |
| `Design::all('active')` taslakları getirmiyor | yarım tasarım müşteriye görünmesin |

## 10. Bitti sayılma ölçütü — 2026-08-19'da doğrulandı

- [x] `/de/v2/designs` vitrin gibi görünüyor: sözlükten gelen başlık ve
      paragraf, `Alle / luxury / modern` süzgeci, iki kart, kart altında ad ve
      kategori. Başlık sabit menünün altında kalmıyor (128'e karşı 94).
- [x] Yalnızca `active` tasarımlar listeleniyor (`Design::all('active')`).
- [x] Süzgeç adres satırında (`?kategorie=modern`), paylaşılabilir.
- [x] "Mit diesem Design erstellen" iki tasarımda da var — ikisinin de aynı
      kimlikte teması olduğu için. Eşleşmeyende yerine "Bald im Assistenten"
      geliyor; kural `Design::creatable()` içinde ve testi var.
- [x] Düğme sihirbazı o tasarım seçili açıyor: `?design=noir` çağrısında
      sihirbazın işaretli seçeneği `value="noir" … checked`.
- [x] Demo sayfasında müşteri geliştirici çubuğunu görmüyor — çıkışta yalnızca
      "Alle Designs" ve oluştur düğmesi; `if ($intern)` dalında bugünkü çubuk
      olduğu gibi duruyor.
- [x] Başlık ve paragraf üç dilde sözlükte (`invitation2.title`, `.lead`).
- [x] Eski motora **dokunulmadı**: `InviteController.php`, `invite-wizard.php`
      ve `designs.php` diff'te hiç geçmiyor. On bir yol denendi, hepsi 200.
      275 kontrol geçiyor.

**Panelde göz kontrolü gerekmiyor** — bu fazın hepsi genel sayfa, giriş
istemiyor. Yalnızca "panele girmiş kullanıcı eski çubuğu görüyor" maddesi
girişli bir gözle bakınca tam olur; kod tarafında `Admin::isLoggedIn()` dalı
yerinde ve çıkıştaki hâl doğrulandı.

## 11. Riskler

**İki motor yan yana kalmaya devam ediyor.** Vitrin v2'yi satar gibi görünürken
oluşturma eski motorda olur; müşteri farkı görmez ama biz iki yerde bakım
yaparız. Azaltma: bu bilerek geçici, ve §5'teki kural B gelince tek yerde
değişiyor.

**Kopyalanmış tasarımlar satılamaz görünüyor.** Panelden çoğaltılan bir tasarımın
karşılığı eski temada yok, yani düğmesi çıkmıyor. Azaltma: not açıkça "yakında"
diyor; B bu kısıtı tamamen kaldıracak.
