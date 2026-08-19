# Fiyat hesaplayıcı — müşteri isteği (kaydedildi, başlanmadı)

**Tarih:** 2026-08-19
**Kaynak:** Ayhan, WhatsApp
**Durum:** kayıtlı, planlanmadı. Ayhan "en son ona da el at" dedi.

## İstenen

Fiyat sayfasında paketlerin yanında ek hizmetler duruyor. Müşteri ek hizmetleri
tıkladıkça **toplam fiyat canlı güncellensin**; ne alacağını ve ne ödeyeceğini
görsün; seçim iletişim formuna geçsin.

**Ödeme sistemi yok.** Sadece gösterim + forma aktarım.

Ayhan'ın kendi cümleleri:

> Bu ek işlerde yani pakete gelecek ilave bölümü — tıkladıkça paket fiyatı
> oynaması lazım.
>
> Burdaki hizmet bölümü fiyat değişmiyor ya, en son ona da el at. Ek paket bir
> şey ekleme yapabilsin. Ödeme sistemi olmayacak ama müşteri ne ödeyecek, ne
> alacak görsün, forma eklensin.

## Bulunan engel — fiyatlar sayı değil, metin

`site_content` içinde:

```json
packages: [
  { "name": {...}, "price": "690 €" },
  { "name": {...}, "price": "1.890 €" },
  { "name": {...}, "price": "3.490 €" }
]
addons: [
  { "name": {...}, "price": "+ 450 €" },
  { "name": {...}, "price": "+ 490 €" },
  { "name": {...}, "price": "+ 390 €" },
  { "name": {...}, "price": "+ 590 €" },
  ... 6 kayıt
]
```

Hepsi serbest metin. Toplama yapabilmek için sayıya çevirmek gerekiyor. Alman
biçimi kullanılıyor: `1.890 €` — nokta binlik ayırıcı.

## Seçenekler

| | Yaklaşım | Artı | Eksi |
|---|---|---|---|
| **A** | Metni ayrıştır (`"1.890 €"` → 1890) | Bugünkü veriyle hemen çalışır, panelde hiçbir değişiklik yok, Ayhan tek satır girmez | Ayhan ileride `"ab 250 €"` / `"auf Anfrage"` yazarsa o kalem hesaplanamaz |
| **B** | Panele ayrı sayısal alan ekle | Sağlam, belirsizlik yok | 9 kaydın yeniden girilmesi gerekir |

**Öneri: A + emniyet.** Ayrıştırılabilen kalemler toplama girer.
Ayrıştırılamayan kalem seçilebilir kalır ama toplama katılmaz, ve panelde
"bu kalem hesaba girmiyor" uyarısı çıkar. Böylece sistem sessizce yanlış
toplam göstermez — bu projede en çok can yakan hata biçimi tam olarak bu.

## Kaba kapsam

- `templates/pages/prices.php` — paket seçimi (radio), ek hizmet seçimi (checkbox)
- yeni `public/assets/prices.js` — canlı toplam
- `src/Pricing.php` — fiyat metnini sayıya çeviren ayrıştırıcı + ayrıştırılamayanı işaretleme
- iletişim formu — seçimi taşıyan alan (`templates/partials/contact-form.php`)
- panel — "hesaba girmiyor" uyarısı (Fiyatlar & paketler sekmesi)

CSP `script-src 'self'` — script dosya olarak gelmeli, satır içi blok olmamalı.

## Karıştırılmasın

`src/Pricing.php` bugün **davetiye sihirbazının** fiyatını hesaplıyor
(`BASE`, `SECTION_PRICES`, `total()`) — fotoğraf paketleriyle ilgisi yok.
İki farklı fiyat dünyası aynı dosyada olacaksa isimlendirme ayrılmalı.
