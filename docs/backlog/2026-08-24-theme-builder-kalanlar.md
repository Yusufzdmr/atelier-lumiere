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

**`gallery` de yapıldı** (commit `a0edac3`). Kuyrukta bölüm tipi kalmadı.

Galeri yapılırken bir boşluk çıktı ve kapatıldı: sihirbaz ile düzenleme
şablonları aynı bloğu iki kopya halinde taşıyordu, ve katalog alan
kazandığında yalnız biri büyümüştü — düzenleme sayfasında hashtag, hesap
sahibi ve IBAN yoktu. İkisi de artık `partials/abschnitt-felder.php`'yi
kullanıyor.

Not: hangi tasarımın hangi bölümü taşıyacağı Yusuf'un kararı. Test sırasında
`bild`'e eklenen deneme bölümleri tekrar kaldırıldı.

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

**Autosave — 01.09.2026'da yapıldı.** Burada "yapılmayacak" yazıyordu ve
gerekçesi şuydu: editör tek bir form ve tek bir `version` kilidiyle çalışıyor,
otomatik kayıt iki sekme açıkken birinin işini diğerinin üzerine yazar. Not şöyle
bitiyordu: *"İstenirse önce kilit modeli değişmeli."*

Kilit modeli değişmedi — **kilide bir anahtar eklendi**. Sunucu her kayıttan
sonra yeni sürüm numarasını JSON ile geri veriyor, o sekme onu üstüne yazıyor,
ikinci sekme kendi eski numarasını tutmaya devam ediyor ve ilk kaydında
(otomatik ya da elle) "veraltet" ile duruyor. Yani kilit korunuyor.

Yusuf'un isteği: *"kaydete basmak zorunda kalmayım, foto yüklediysem oto
kaydetsin, yazıyı değiştirirken oto kaydetsin, ayarlarını falan, en son kaydete
basınca yine kaydetsin."* Ayrıntı ve ölçüm: `php/DURUM.md`.

