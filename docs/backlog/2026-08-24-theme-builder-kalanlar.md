# Theme Builder — kalan işler

24.08.2026'da Yusuf onayladı, sırası gelince yapılacak. Spec: paylaşılan
"Section-Based Theme Builder" dokümanı (sohbette, depoda değil).

Yapılanlar bu üçünün öncesi: `SectionRegistry` (tip / görünüm / ayar ayrımı),
üç kolonlu editör, hazır başlangıç düzenleri, çoğaltma, cihaz önizlemesi.
Commit'ler `8f6c3d5`…`3d66c6d`.

## 1 · Yeni bölüm tipleri

`footer`, `gift`, `music` **yapıldı** (24.08.2026, commit `9b99236`). Yanında
asıl iş şu oldu: müşterinin dolduracağı alanlar artık katalogda
(`SectionRegistry::inputs`), dört ayrı yerde değil.

Kalan tek tip:

- `gallery` — **pahalı olan bu.** Müşterinin kendi fotoğraflarını yüklemesi
  gerekiyor: `invitations_v2` tarafında bir dosya deposu, `Media::store`
  üzerinden yükleme, boyut/adet sınırı, ve sihirbazda yeni bir adım.
  Ayrı bir iş olarak planlanmalı.

Not: hangi tasarımın hangi bölümü taşıyacağı Yusuf'un kararı. Test sırasında
`bild`'e eklenen üç deneme bölümü tekrar kaldırıldı.

## 2 · Yayın durumu (taslak / yayında) — **yapıldı**

Commit `f27404d`. `ALTER TABLE` **gerekmedi**: yeni bir tablo (`invite_status`)
kuruldu, çünkü `schema.sql` baştan sona `CREATE TABLE IF NOT EXISTS` ve elle
tekrar tekrar çalıştırılabiliyor. Bir `ALTER`, o dosyadaki ikinci çalıştırmada
patlayacak ilk satır olurdu.

**Canlıya alırken:** tek yapılacak şey `schema.sql`'i her zamanki gibi yeniden
içe aktarmak. Sıralama riski yok — kod önce gitse bile satır olmayan her
davetiye "yayında" sayılıyor.

Ödeme kapısı buraya oturacak (spec §14) — fiyat kararı ayrı, bkz. hafızadaki
`fiyatlandirma-karari`.

## 3 · Undo/redo + sürükle-bırak — **yapıldı**

Commit `f50dbd5`. Ctrl+Z / Ctrl+Shift+Z / Ctrl+Y, ve hem bölüm hem katman
listesinde sürükleyerek sıralama. Oklar duruyor: telefonda HTML5 sürükleme yok.

**Autosave yapılmadı** ve yapılmayacak (spec §19 istese de). Sebep: editör tek
bir form ve tek bir `version` kilidiyle çalışıyor. Otomatik kayıt, iki sekme
açık olduğunda birinin işini diğerinin üzerine yazar — kilit tam olarak bunu
engellemek için var. İstenirse önce kilit modeli değişmeli.

