# Davetiye v2 — Faz 3C2: RSVP

**Tarih:** 2026-08-20
**Durum:** tasarım onaylandı, plan yazılmadı — yeni oturum buradan başlar
**Kapsam:** sadece `php/` — Next.js tarafı (`app/`, `lib/`) bu spec dışında ve hiç değişmiyor.
**Öncesi:** [Faz 1](2026-08-19-davetiye-v2-design.md), [Faz 2](2026-08-19-davetiye-v2-faz2-panel-design.md),
[Faz 3A](2026-08-19-davetiye-v2-faz3a-vitrin-design.md), [Faz 3B](2026-08-19-davetiye-v2-faz3b-sihirbaz-design.md),
[Faz 3C](2026-08-20-davetiye-v2-faz3c-bolumler-design.md)

## 1. Amaç ve neden ayrı dilim

Faz C bölüm sistemini kurdu ve dört **gösterim** türü getirdi: adres, geri sayım,
aileler, program. RSVP kasten dışarıda bırakıldı, çünkü diğer dördünden yapısal
olarak farklı: **tek yazan bölüm.** Kendi POST yolu, kendi tablosu, kendi
güvenlik yüzeyi var. C aynı anda hem yeni bir düzen modeli tanımlayıp hem yeni
bir yazma yolu açmasın diye ayrıldı — 3A'yı 3B'den ayıran mantığın aynısı.

## 2. C2 hem toplar hem gösterir

**Karar:** C2 yalnızca yanıt toplamakla yetinmez; çiftin yanıtları okuduğu
ekranı da getirir.

Gerekçe: okunamayan bir RSVP özellik değil. Faz 3B `manageKey`'i her davetiyenin
`data`'sına bugünden yazmıştı ve gerekçesi şuydu — "sonradan eklemek, o güne
kadar yayınlanmış davetiyeleri kapı dışında bırakır". O anahtarın bugüne kadar
karşılığı yoktu; C2 onu kullanan ilk faz.

Reddedilen iki yol:

- **Yalnız toplama, okuma D'ye.** Daha küçük bir dilim, ama D gelene kadar
  yanıtları yalnızca veritabanından görmek mümkün olurdu. "Cevaplar nerede"
  sorusuna cevabımız olmazdı.
- **Yanıtlar çifte e-posta olarak gitsin.** `data.email` bugün yazılmıyor (Faz 3B
  e-posta göndermeyi D'ye erteledi), yani önce onu toplamak gerekirdi. Ayrıca
  e-posta kaybolur; liste kalmaz.

## 3. `rsvp` kataloğun beşinci türü

`DesignSections::TYPES` beşe çıkar. Dördü gösterim, beşincisi **form**.

Aynı `Section` şekli, aynı `edit`/`hide` izinleri, aynı "sıra = dizi sırası"
kuralı, aynı jeton tabanlı renk ve yazı tipi. Bölüm sistemi büyümüyor; içine
bir tür daha giriyor.

**Sihirbazda içerik sorulmaz.** `DesignWizard::choices()` bu tür için `fields`
alanını **boş** döndürür: müşterinin dolduracağı bir şey yok, yalnızca izin
verilmişse açıp kapatıyor. Bu, Faz C'nin `location` ve `countdown` için
kurduğu kalıbın aynısı.

### Düğün geçince kaybolur

`visible()` içinde geri sayımla **aynı kural**: `data.date` boş değilse ve
geçmişse form basılmaz. Geçmiş bir düğüne cevap toplamak gürültüdür, ve
kural ikinci kez icat edilmiyor.

Tarih hiç yoksa form basılır — tarihsiz bir davetiye de cevap isteyebilir.

## 4. Form davetiyenin kendi adresine gönderir

`/{locale}/v2/einladung/{slug}` rotası `get`'ten **`any`**'ye çıkar ve `show()`
POST'u karşılar. Eski motor da böyle yapıyor (`InviteController::show` →
`saveRsvp`); yeni bir uç nokta icat edilmiyor.

Misafirin doldurduğu alanlar — eskisinin aynısı, misafir belirteci hariç:

| Alan | Sınır | Not |
|---|---|---|
| `name` | 60 | **zorunlu**; boşsa yanıt kaydedilmez |
| `coming` | `1` \| `0` | geliyor / gelmiyor |
| `count` | 1 … 20 | aralık dışı **kırpılır**, reddedilmez |
| `note` | 300 | serbest metin |

Misafire özel bağlantı (`?gast=`) **yok**: misafir listesi Faz D'de, yani C2'de
eşleştirilecek bir liste bulunmuyor. Elimizdeki tek kimlik misafirin yazdığı
isim.

## 5. Aynı isim üzerine yazar

**Karar:** aynı davetiyede aynı isimle gelen ikinci yanıt birincinin **yerine
geçer**.

Karşılaştırma normalleştirilmiş isimle yapılır (kırpılmış, küçük harf —
`mb_strtolower`), saklanan ise misafirin yazdığı hâlidir. Okuma ekranı her satır
için "son güncelleme" tarihini gösterir.

Gerekçe: çiftin sorusu "kim geliyor", ve o sorunun tek bir cevabı olmalı. Fikrini
değiştiren bir misafirin listede iki kez görünmesi, çifti tarihten hangisinin
geçerli olduğunu çıkarmaya zorlar.

**Bedeli açıkça:** aynı adlı iki gerçek kişi (iki "Mehmet") birbirini ezer, ve
isim tek kimliğimiz olduğu için bunu ayırt edemeyiz. Misafir listesi Faz D'de
gelince gerçek kimlik de gelir; o zaman eşleştirme isme değil belirtece dayanır.

### Neden yeni bir sınıf

`Invitations.php` dokunulmazlar listesinde ve `Invitations::addRsvp()` **ekler**,
üzerine yazmaz. Bu yüzden yazma yolu `InvitationsV2::saveRsvp()` olur —
`rsvps` tablosu paylaşılır, kod paylaşılmaz.

Tablo paylaşımı güvenli: Faz 3B slug'ı `invitations` ve `invitations_v2`
tablolarında **birden** benzersiz kıldı (`InvitationsV2::slugAvailable()`), yani
bir v2 slug'ı hiçbir zaman bir v1 davetiyesinin yanıtlarıyla karışamaz. Şema
değişmiyor.

## 6. Okuma ekranı

`/{locale}/v2/einladung/{slug}/{manageKey}` — salt okunur.

| | |
|---|---|
| Yanlış anahtar | **404** |
| Karşılaştırma | `hash_equals()` |
| İndeksleme | `noindex` |
| Liste | isim, geliyor mu, kişi sayısı, not, son güncelleme |
| Özet | gelen kişi toplamı ve yanıt sayısı |

**Neden 404, 403 değil:** 403 davetiyenin var olduğunu doğrular. Anahtarı
bilmeyen birine "burada bir davetiye var ama giremezsin" demenin faydası yok;
"böyle bir sayfa yok" demek doğru cevap.

**`hash_equals` neden:** anahtar karşılaştırması zamanlama saldırısına açık bir
eşitlik kontrolüyle yapılmamalı. Anahtar 32 onaltılık karakter ve tek koruma o.

Ekranda **düzenleme yok**: yanıt silme, değiştirme, dışa aktarma — hepsi D.
C2'nin okuma ekranı bir liste, bir panel değil.

## 7. Güvenlik

| Ne | Nasıl |
|---|---|
| CSRF | `Security::checkCsrf()` — POST'ta ilk kontrol |
| Sel | `Security::throttle('rsvp-v2-' . $slug, 20, 600)` — eskisinin ölçüsü, **ayrı anahtar** |
| Metin | `Security::clean()` alan başına, §4'teki sınırlarla |
| Sayı | 1 … 20 arasına kırpılır |
| Anahtar | `hash_equals()`, yanlışsa 404 |
| Kaçış | okuma ekranında ve formda basılan her değer `e()`'den geçer |

**Yanıt hiçbir zaman belgeye girmez.** `rsvps` tablosuna yazılır;
`design_snapshot` ve `invitations_v2.data` dokunulmaz. Bu, Faz 3B'nin "belge
donar" kuralını korur: bir misafirin yazdığı hiçbir şey davetiyenin tasarımına
sızamaz.

Sel kontrolünün anahtarı eskisinden **ayrı** (`rsvp-v2-`), ki bir motorda
biriken deneme diğerini kilitlemesin.

## 8. Yeni ve değişen dosyalar

| Dosya | İşi |
|---|---|
| `src/DesignSections.php` | `TYPES`'a `rsvp`; `visible()` tarih kuralı; `html()` formu basar |
| `src/InvitationsV2.php` | `saveRsvp()`, `rsvps(string $slug): array` |
| `src/Controllers/InviteV2Controller.php` | `show()` POST'u karşılar; `replies()` okuma ekranı |
| `templates/pages/invite-v2-replies.php` | **yeni** — salt okunur liste |
| `public/index.php` | `show()` rotası `any`; yeni `{slug}/{key}` rotası |
| `data/dict.php` | yeni anahtarlar, üç dile de |
| `tests/design_sections.php` | `rsvp` türünün görünürlük ve basım testleri |
| `tests/invitations_v2_rsvp.php` | **yeni** — üzerine yazma mantığı |

`DesignWizard.php` **değişmez**: `fields` boş döndüğü için `rsvp` mevcut
`match` ifadesinin `default` dalına düşer.

## 9. Testler

Saf olanlar (`bin/test.php`, veritabanısız):

| Test | Neyi tutuyor |
|---|---|
| `visible()` geçmiş tarihte `rsvp`'yi düşürüyor | geçmiş düğüne cevap toplanmasın |
| `visible()` tarihsiz davetiyede `rsvp`'yi basıyor | tarihsiz davetiye de cevap isteyebilir |
| `html()` formu CSRF alanıyla basıyor | korumasız form çıkmasın |
| `html()` misafirin girdiği hiçbir şeyi ham basmıyor | XSS |
| isim normalleştirmesi büyük/küçük ve boşluk farkını eşitliyor | "  Mehmet " ile "mehmet" aynı kişi |

Veritabanı gerektirenler (`needs_db()` korumalı, satırlarını temizler):

| Test | Neyi tutuyor |
|---|---|
| aynı isimle ikinci yanıt **üzerine yazıyor**, satır sayısı artmıyor | §5'in kararı |
| farklı isim yeni satır açıyor | üzerine yazma fazla ısırmasın |
| farklı slug'daki aynı isim etkilenmiyor | davetiyeler birbirine karışmasın |
| `rsvps(slug)` yalnızca o slug'ı döndürüyor | sızıntı olmasın |

## 10. Kapsam dışı ve nedeni

| Ne | Neden | Faz |
|---|---|---|
| Misafire özel bağlantı (`?gast=`) | misafir listesi yok; eşleştirilecek bir şey bulunmuyor | D |
| E-posta bildirimi | `data.email` bugün yazılmıyor | D |
| Okuma ekranından silme/düzenleme | C2'nin ekranı liste, panel değil | D |
| Dışa aktarma (CSV) | aynı gerekçe | D |
| Fiyatlı bölümler (menü, müzik, video) | fiyat hesabı D'nin konusu | D |
| Panelde bölüm önizlemesi | Faz C'den devreden eksiklik, kendi dilimi | sonra |

## 11. Bitti sayılma ölçütü

- [ ] Panelden bir tasarıma `rsvp` bölümü eklenebiliyor, `edit`+`hide` verilebiliyor.
- [ ] Yayınlanan davetiyede kartın altında form görünüyor; CSRF alanı var.
- [ ] Misafir cevap veriyor, sayfa tekrar yüklendiğinde teşekkür görünüyor.
- [ ] Aynı isimle ikinci cevap **üzerine yazıyor** — satır sayısı artmıyor.
- [ ] Geçmiş tarihli davetiyede form basılmıyor.
- [ ] `/{locale}/v2/einladung/{slug}/{manageKey}` yanıtları listeliyor ve toplamı gösteriyor.
- [ ] Yanlış anahtar **404** dönüyor, 403 değil.
- [ ] Yanıt `design_snapshot`'a **hiç** dokunmuyor.
- [ ] `/de/v2/designs/elysee` vitrin çıktısı **bayt bayt** değişmemiş (25427).
- [ ] Eski motora dokunulmadı: `InviteController.php`, `invitation.php`,
      `Invitations.php`, `Themes.php`, `Pricing.php` diff'te geçmiyor.
- [ ] `php bin/test.php` geçiyor; Faz C'nin 429 kontrolü + yenileri.

## 12. Riskler

**Herkese açık bir yazma yolu açılıyor.** Bugüne kadar v2'de tek yazma yolu
sihirbazdı ve o da bağlantısızdı. RSVP formu yayınlanmış her davetiyede duruyor.
Azaltma: CSRF, slug başına sel kontrolü, alan sınırları, ve yanıtın belgeye
hiçbir zaman girmemesi. Yine de bu, fazın en dikkat isteyen yüzeyi.

**İsim tek kimlik.** Aynı adlı iki misafir birbirini ezer (§5). Azaltma: bilinçli
ve yazılı; D'de misafir listesi gelince belirtece dayanan gerçek kimlik gelir.

**`manageKey` tek koruma.** Anahtarı bilen yanıtları görür; iptal etme,
yenileme, süre sınırı yok. Azaltma: anahtar 32 onaltılık karakter ve `noindex`;
gerekirse yenileme D'nin konusu.

**`rsvps` tablosu iki motorla paylaşılıyor.** Azaltma: slug iki tabloda birden
benzersiz (Faz 3B), yani çakışma yapısal olarak imkânsız — ama bu güvence
`InvitationsV2::slugAvailable()`'ın korunmasına bağlı, ve o bağ yazılı olmalı.

## 13. Nereden devam edilir

Bu belge **karar**, plan değil. Yeni oturum `superpowers:writing-plans` ile
buradan başlar: dosya dökümü §8'de, testler §9'da, ölçüt §11'de hazır.

Zemin çalışıyor ve canlıda: format (Faz 1), panel (Faz 2), vitrin (3A),
sihirbaz (3B), bölümler (3C). Faz C'nin dersi de aktarılmalı — üç Critical'in
üçü de "yalnızca henüz var olmayan veri ortaya çıkınca çalışan kod"du. C2'de
o sınıfın en olası hâli: **hiç yanıt yokken okuma ekranı**, ve **bölümü olmayan
bir tasarımda form**. İkisi de plana ölçüt olarak girmeli.
