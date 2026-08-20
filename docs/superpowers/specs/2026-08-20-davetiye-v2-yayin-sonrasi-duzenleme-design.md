# Davetiye v2 — Yayın sonrası düzenleme

**Tarih:** 2026-08-20
**Durum:** tasarım onaylandı, plan yazılmadı — yeni oturum buradan başlar
**Kapsam:** sadece `php/`. Next.js tarafı (`app/`, `lib/`) bu spec dışında.
**Öncesi:** [Faz 3C2 RSVP](2026-08-20-davetiye-v2-faz3c2-rsvp-design.md) ve onun
öncesindeki fazlar. Bu belge, sihirbaz iyileştirmelerinin dördüncü ve son
diliminin kararıdır (öncekiler: taslak kaydetme, bölüm önizlemesi, `text` bölüm
türü — üçü de yayında).

## 1. Amaç

Yayınlanmış bir davetiyede bir yazım hatasını düzeltmek bugün **imkânsız.**
Çift, yeni bir davetiye kurup yeni adresi misafirlere yeniden göndermek
zorunda. Saat değişti, mekân adresi yanlış yazıldı, damadın adında harf hatası
var — hepsi aynı çıkmaza gidiyor.

Bu dilim, `manageKey` ile açılan bir düzenleme ekranı getirir.

## 2. Neden ayrı ve neden en sona bırakıldı

Diğer üç dilim mevcut yapının içine sığdı. Bu dilim **Faz 3B'nin bir sözüne
dokunuyor:** "yayınlanan belge donar". O söz gerçek bir koruma: panelde şablonu
sonradan değiştiren tasarımcı, gönderilmiş bir davetiyeyi değiştirmemeli.

Kolay yol — düzenlemede `personalize()`'ı güncel tasarım üzerinde tekrar
çalıştırmak — o sözü sessizce bozar: müşteri rengini değiştirmek isterken
kartın **düzeni** de o günden beri şablonda ne değiştiyse onunla birlikte
değişir. Misafire gönderilen link ertesi gün başka bir kart gösterir.

## 3. Karar: `design_snapshot` kişiselleştirilmemiş temeli tutar

Bugün:

```
design_snapshot = personalize(design, wahl)      // sonuç donar, girdi atılır
```

Bundan sonra:

```
design_snapshot = design                          // şablon donar
data['wahl']    = wahl                            // seçimler saklanır
basılan kart    = personalize(snapshot, wahl)     // her basımda hesaplanır
```

Kazanç: müşterinin seçimleri **donmuş şablonun** üstüne uygulanır, güncel
şablonun değil. Söz korunur ve düzenleme mümkün olur.

### Geriye dönük uyum yapısaldır, tesadüf değil

Mevcut davetiyelerde snapshot **zaten kişiselleştirilmiş** ve `wahl` yok.
Onlarda `personalize(snapshot, boş)` çalışacak — ve bu **kimliktir.**

Ölçüldü, varsayılmadı (`elysee`, 9173 bayt):

| Kontrol | Sonuç |
|---|---|
| `personalize($d, boş) === $d` | evet |
| İki kez uygulamak | evet |
| Aynı seçimi iki kez uygulamak | evet (idempotent) |

Yani **göç yok, veri dönüştürme yok.** Eski davetiyeler bugünkü çıktılarını
bayt bayt korur.

### Reddedilen iki yol

- **Temel şablonu `data`'ya kopyala.** Snapshot anlamını korur ama her davetiye
  şablonun ikinci bir kopyasını taşır (onlarca KB) ve "gerçek şablon hangisi"
  sorusu iki cevaplı olur.
- **Yeni sütun.** En temiz ayrım, ama **şema değişikliği.** Bu proje şemayı
  Faz 1'den beri hiç değiştirmedi: RSVP `rsvps`'i, taslaklar `invite_drafts`'i
  eski motorla paylaştı. O disiplin bir sütun için bozulmaz.

## 4. Bedeli açıkça: eski davetiyelerin tasarımı kilitli

`wahl` taşımayan bir davetiyede temel zaten kişiselleştirilmiş. Üstüne yeni bir
seçim uygulamak kayıplıdır — gizlenmiş bir katmanı geri getirmek, üzerine
yazılmış bir rengi geri almak mümkün değil.

Bu yüzden: **`wahl` yoksa düzenleme ekranı yalnızca metinleri sunar.** Tasarım
sekmesi gösterilmez, "bu davetiye eski sürümle yayınlandı" diye açıklanır.
Yeni yayınlanan her davetiyede ikisi de açıktır.

Sessiz bir kısıtlama değil, ekranda yazan bir cümle olmalı.

## 5. Yetki: `manageKey`, ikinci kez

Düzenleme ekranı `/{locale}/v2/einladung/{slug}/{manageKey}/bearbeiten`
altında oturur — yanıt ekranının kardeşi.

Aynı kurallar, çünkü aynı anahtar:

| | |
|---|---|
| Yanlış anahtar | **404**, 403 değil |
| Karşılaştırma | `hash_equals()` |
| Boş saklanmış anahtar | `hash_equals`'tan **önce** reddedilir |
| İndeksleme | `noindex` |
| Önbellek | `Cache-Control: private, no-store` |

**Anahtar artık üç kapı açıyor:** yanıtları okumak, davetiyeyi düzenlemek ve
(yakında) misafir listesi. Bu, anahtarın değerini yükseltir ve iki şeyi
zorunlu kılar:

- **Sel kontrolü gelmeli.** Yanıt ekranında gerekmediğine karar verilmişti
  (128 bit tahmin edilemez). Yazma yetkisi verirken o karar yeniden
  düşünülmeli: `Security::throttle('v2-manage-' . $slug, 60, 600)`.
- **Anahtarın yenilenebilmesi** artık gerçek bir ihtiyaç. Kapsam dışı (§9) ama
  bu fazın açtığı borç olarak yazılı.

## 6. Ekran ve neyin donuk kaldığı

İki sekme: **metinler** ve **tasarım.**

Metinler sekmesi sihirbazın `angaben` ve `abschnitte` adımlarının aynısıdır —
aynı alanlar, aynı sınırlar, aynı `data` anahtarları. Tasarım sekmesi
`design` adımının aynısı, `wahl` üzerinde çalışır.

**Donuk kalanlar, ve neden:**

| Ne | Neden |
|---|---|
| `slug` | misafirlere gönderilmiş adres; değişirse link ölür |
| `manageKey` | çiftin kendi kapısı; yenileme ayrı bir karar (§9) |
| Şablon (snapshot) | Faz 3B'nin sözü; bu fazın tamamı onu korumak için var |
| `createdAt`, `paid` | kayıt bilgisi, müşterinin alanı değil |
| Yanıtlar (`rsvps`) | misafirlerin sözü, çiftin değil |

**Fotoğraflar:** yeni bir görsel yüklenebilir, mevcut görsel `wahl['layers']`
içinde yolu ile duruyor. Yükleme yoksa eski yol korunur — sihirbazın
davranışının aynısı.

## 7. Eşzamanlılık

Çift iki sekmede aynı anda düzenlerse ikincisi birincisini ezer. Bu fazda
**kabul ediliyor** — bir davetiyeyi tek bir çift düzenliyor ve çakışma penceresi
saniyeler.

Ama sessiz olmamalı: kayıtta `data['updatedAt']` tutulur ve form onu gizli alan
olarak taşır. Gönderilen değer kayıttakinden eskiyse **kaydedilmez**, "bu
davetiye başka bir sekmede değişti, sayfayı yenileyin" denir. Panelin tasarım
düzenleyicisi bunu zaten böyle yapıyor (`fehler=veraltet`) — ikinci kez icat
edilmiyor.

## 8. Güvenlik

| Ne | Nasıl |
|---|---|
| CSRF | POST'ta ilk kontrol |
| Sel | `v2-manage-{slug}`, 60/600 (§5) |
| Metin | `Security::clean()`, sihirbazdaki sınırların aynısı |
| Anahtar | `hash_equals()`, boş anahtar önce reddedilir |
| Kaçış | basılan her değer `e()` |
| Dizi girdisi | `is_string()` koruması — RSVP yolunda yazıldı, burada tekrarlanır |

**Yanıtlar dokunulmaz.** Düzenleme `rsvps` tablosuna yazmaz, okumaz, silmez.

## 9. Kapsam dışı ve nedeni

| Ne | Neden | Nereye |
|---|---|---|
| `manageKey` yenileme/iptal | kendi kararı; bu faz ihtiyacı **doğurdu**, çözmüyor | sonraki dilim |
| Ödeme | v2'de `paid` hâlâ yazılıp okunmuyor | Faz D |
| E-posta bildirimi | `data.email` hiç toplanmıyor | Faz D |
| Davetiyeyi silme | geri alınamaz; kendi onay akışını hak ediyor | sonraki dilim |
| Sürüm geçmişi / geri alma | değerli ama ayrı bir sistem | sonra |
| Eski davetiyelerin tasarımını düzenleme | §4'te yazılı, kayıplı | çözümsüz, kabul |

## 10. Testler

Saf olanlar (`bin/test.php`, veritabanısız):

| Test | Neyi tutuyor |
|---|---|
| `personalize(doc, boş)` kimliktir | §3'ün geriye dönük uyum iddiası |
| `personalize` aynı seçimle idempotenttir | iki kez kaydetmek bozmamalı |
| donmuş temel + yeni seçim → yalnızca seçim değişir | şablon sızmasın |
| `updatedAt` eskiyse reddedilir | §7 |

Veritabanı gerektirenler (`needs_db()` korumalı, satırlarını temizler):

| Test | Neyi tutuyor |
|---|---|
| düzenleme `data`'yı değiştirir, `design_snapshot`'a dokunmaz | §3 |
| yanlış anahtarla düzenleme 404 | §5 |
| `wahl` taşımayan davetiyede tasarım düzenlemesi reddedilir | §4 |
| düzenleme `rsvps`'e dokunmaz | §8 |

## 11. Bitti sayılma ölçütü

- [ ] Yayınlanmış bir davetiyede isim düzeltiliyor, misafir linki **aynı** kalıyor.
- [ ] Tasarım seçimi (renk) değiştiriliyor, kartın **düzeni** değişmiyor.
- [ ] Tasarımcı panelde şablonu değiştiriyor — yayınlanmış davetiye **değişmiyor**.
- [ ] `wahl` taşımayan eski bir davetiye açılıyor: metinler düzenlenebiliyor,
      tasarım sekmesi yok, sebebi ekranda yazıyor.
- [ ] Eski davetiyelerin çıktısı bu faz **öncesiyle bayt bayt aynı**.
- [ ] Yanlış anahtar **404**.
- [ ] İki sekmeden ikinci kayıt reddediliyor ve sebebi yazıyor.
- [ ] Düzenleme `rsvps` tablosuna hiç dokunmuyor.
- [ ] `php bin/test.php` geçiyor (bu spec yazılırken 525).
- [ ] Eski motor diff'te geçmiyor.

## 12. Riskler

**Snapshot'ın anlamı değişiyor.** Bu belgedeki en büyük değişiklik bir sütunun
ne demek olduğu. Kodun her yerinde "snapshot = basılacak belge" varsayımı
olabilir; plan bunu **tek tek aramalı**, `grep design_snapshot` ile başlayarak.
Geriye dönük uyum ölçüldü ama yalnızca çıktı düzeyinde.

**Anahtar artık yazma yetkisi.** Okuma ekranında "128 bit yeter" savunulabilirdi.
Yazma açılınca sel kontrolü isteğe bağlı olmaktan çıkar (§5).

**Eski davetiyeler ikinci sınıf olur.** §4'ün bedeli gerçek ve kalıcı. Yayında
kaç davetiye olduğu sayılmalı: azsa elle yeniden yayınlamak bir seçenek.

## 13. Nereden devam edilir

Bu belge **karar**, plan değil. Yeni oturum `superpowers:writing-plans` ile
buradan başlar: dosya dökümü yok — çünkü ilk görev, §12'nin istediği
`design_snapshot` taramasıdır ve dosya listesi ondan çıkar.

Zemin çalışıyor ve canlıda: format (Faz 1), panel (Faz 2), vitrin (3A),
sihirbaz (3B), bölümler (3C), RSVP (3C2), taslaklar, bölüm önizlemesi ve `text`
türü. Süit 525 kontrolle yeşil.

Önceki fazın dersi de aktarılmalı: RSVP'de üç Critical'in üçü de "yalnızca
henüz var olmayan veri ortaya çıkınca çalışan kod"du. Bu fazda o sınıfın en
olası hâli **`wahl` taşımayan eski davetiye** — ve o, üretimde bugün var olan
tek davetiye türü.
