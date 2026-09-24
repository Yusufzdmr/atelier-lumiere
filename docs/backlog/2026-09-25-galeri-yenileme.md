# Galeri yenileme — Ayhan'ın üç parçalı isteği ve devamı (kaydedildi, başlanmadı)

**Tarih:** 2026-09-25
**Kaynak:** Ayhan, WhatsApp üzerinden Yusuf'a, sonra sohbette parça parça
netleşti. Bkz. `php/DURUM.md`'deki "galeri tercihleri" (4 stil + anket, bu
tarihte canlıya alındı — commit `7c0fd1e`, `d97a7eb`) bu işin öncesi ve şu an
bu backlog'un 2. maddesiyle kısmen çakışıyor/yer değiştirecek.

**Durum:** kayıtlı, planlanmadı. Beş ayrı alt-iş var, hepsi aynı oturumda
üst üste geldi, hiçbiri tam netleşmeden koda girilmedi. Sıra Yusuf'un kararı.

## Ayhan'ın orijinal özeti (birebir)

> 1. Giriş (Misafirler / Aile ve Arkadaşlar): şifreli ortak bir link, sadece
> izleme — beğeni/değişiklik yok.
>
> 2. Giriş (Sadece Gelin ve Damat): kalp ile beğeni (albüm seçimi için), 1 adet
> kapak (Titelbild) fotoğrafı seçimi. Ayrı form: albüm modeli ve rengi, 4
> farklı filtre (indoor/outdoor — bu muhtemelen zaten yapılan "galeri
> tercihleri" stil seçimiyle aynı şey), video için müzik tercihi (1x slow, 2x
> parti müziği — **anlamı netleşmedi**), sosyal medya izni, kargo adresi.
>
> 3. Giriş (Albüm Baskı Firması): "Hazır" (Fertig) rozeti tüm seçimler
> bitince yeşile döner, o zamana kadar indirilemez. Beğenilen fotoğrafları
> indirme, seçilen kapak fotoğrafı, albüm modeli/rengi, kargo adresi +
> iletişim.

## 1 · Misafir salt-okunur girişi — hiç yok

Şu an tek giriş var: çiftin şifreli girişi (`GalleryController`). Misafir/aile
için ayrı, şifreli ama salt-okunur bir mod (fotoğraf + highlight video izler,
kalp/değişiklik yapamaz) tamamen yeni.

**Netleşmemiş:** tek ortak link mi (tüm misafirler aynı şifre), yoksa çiftin
linkinden mi türeyecek?

## 2 · Çift formunun genişlemesi

Mevcut "galeri tercihleri" (4 stil + anket, `/galerie/{code}/tercihler`) bunun
bir kısmını zaten karşılıyor (4 filtre = 4 stil). Eklenecekler:

- **Kapak fotoğrafı (Titelbild):** kalp-seçiminden ayrı, galerideki fotoğraflardan
  tek bir tanesini "kapak" olarak işaretleme. Yeni bir veri alanı — `Galleries.php`'de
  yok.
- **Albüm modeli + rengi:** admin panelden yönetilen sabit bir liste mi
  (`Stiller & anket` gibi), yoksa serbest metin mi — netleşmedi.
- **Müzik tercihi:** "1x slow, 2x parti müziği" — bir slow bir de parti şarkısı
  mı seçilecek, admin bir liste mi sunacak, yoksa oran/tercih mi — netleşmedi.
- **Sosyal medya izni:** evet/hayır checkbox, muhtemelen "fotoğraflarınızı
  portfolyoda/Instagram'da kullanabilir miyiz" anlamında.
- **Kargo adresi:** ad soyad, adres, posta kodu, şehir, telefon — standart
  kargo formu.

**Soru:** bunlar "galeri tercihleri" sekmesine mi eklenecek (aynı formun
devamı), yoksa ayrı bir üçüncü sekme mi olacak?

## 3 · Albümcü — ayrı kalıcı giriş

**Netleşti (2026-09-25):** mevcut sistem zaten var —
`Galleries::shareCreate/shareFind` + `SelectionController` (`/auswahl/{token}`,
`/auswahl/{token}/zip`): admin müşteri kartından "Link erzeugen" ile süreli
(30 gün), şifresiz bir link üretiyor, albümcü o linkten seçili fotoğrafları
görüp ZIP indiriyor. **Bu link akışı yeterli değil** — Yusuf'un kararı:
albümcünün **kendi kalıcı kullanıcı adı/parolası** olsun, admin panelin
dışında, "hazır" olan tüm müşterileri kendisi görsün — link üretip göndermek
Yusuf'a kalmasın.

Gerekenler:
- Yeni bir giriş/oturum türü (`Security::session()`'dan ayrı, admin'den ayrı)
- "Hazır" tanımı: şu an sadece fotoğraf seçimi mi, yoksa 2. maddedeki tüm form
  (kapak fotoğrafı, albüm modeli, kargo adresi vb.) tamamlanınca mı — 2.
  madde bitmeden bu tanımlanamaz
- Liste görünümü (birden fazla müşteri), her biri için ZIP indirme +
  kapak fotoğrafı + albüm modeli/rengi + kargo adresi/iletişim gösterimi
- Tek ortak albümcü hesabı mı, yoksa albümcü sayısı birden fazlaysa
  her biri ayrı mı — **netleşmedi**

## 4 · Fotoğraf kategorileri (Shooting / Salon gibi)

Yusuf'un kendi cümlesi (2026-09-25):

> Shooting resimleri oluyor mesela parkta özel atıyorum 50 atıyorum salonda
> 300 400e yakın bunları ayrı bi dosya gibi düğmeye basınca shooting resimleri
> açılsın düğmeye basınca salon resimleri açılsın ama bunları önizleme resmi
> bir kaç tanesi gözüksün falan

Galerideki fotoğraflar tek bir liste yerine adlandırılmış gruplara ayrılacak
(ör. "Shooting" ~50 kare, "Salon" ~300-400 kare). Düğme/sekme ile grup
değiştirme, her grup düğmesinde birkaç küçük önizleme fotoğrafı.

**Netleşmemiş:** grup sayısı/adları sabit mi yoksa admin her galeri için
serbestçe mi tanımlıyor (muhtemelen ikincisi — galeri başına farklı grup
isimleri gerekir). `Galleries::photos()` şu an düz bir liste döndürüyor,
kalp-seçim indexleri de bu düz listenin sırasına göre çalışıyor
(`selectedPhotos()`) — gruplama eklenirse bu index mantığının bozulmaması
gerekiyor.

## 5 · Görsel tasarım referansı

Ayhan'ın örnek gönderdiği site: `https://galerie.akyelvideo.com/cansuayse-yusuf`
(gerçek bir müşterinin galerisi, sadece düzen için referans — fotoğrafları
kopyalamayacağız). Beğenilen noktalar:

- Giriş ekranı: büyük fotoğraf solda, çiftin adı + tarih sağda, ince çizgi
  ayraç, serif başlık
- Sol kenarda dikey ikon menüsü (fotoğraflar / favoriler / sepet / galeri /
  indir / paylaş / iletişim / hesap)
- Fotoğrafların altında "5 yıldız Google değerlendirmesi yaparsanız orijinal
  kalite + filigransız" teşviki (bizim işimizle doğrudan ilgili değil, ayrı
  bir pazarlama taktiği — not düşüldü, iş listesine alınmadı)

Genel galeri tasarımının (mevcut `templates/pages/gallery.php`) bu yöne mi
çekileceği, yoksa sadece giriş ekranındaki isim/tarih yerleşiminin mi
alınacağı netleşmedi.

## Sıralama önerisi

Bağımlılık zinciri: 2. madde (çift formu) bitmeden 3. maddedeki "hazır" tanımı
oturmaz. 1. ve 4. madde bağımsız. 5. madde bir tasarım girdisi, tek başına iş
değil — 2. ve 4. maddeye uygulanacak.

Önceki oturumda önerilen sıra: **önce 2 (çift formu), sonra 3 (albümcü
girişi), en son 1 (misafir linki)** — 4. madde (fotoğraf kategorileri) ne
zaman girer, netleşmedi.
