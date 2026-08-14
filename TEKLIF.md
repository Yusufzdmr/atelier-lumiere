# Teklif — Almanya için Profesyonel Düğün Foto & Video Web Sitesi

> **Not (sana özel, müşteriye gönderme):** Fiyatlar ve süreler önerilerdir, kendi marjına göre düzenle.
> Canlı demo linkini ilanın altına koy: müşteri 17 sorunun cevabını zaten çalışır halde görecek.

**Canlı demo:** `https://<proje>.vercel.app/de` — Türkçe: `/tr`
**Demo erişimleri:** Galeri `elif-marco` / `solitude24` · Davetiye kodu `lumiere2026` · Panel `/de/admin?key=demo`

---

## 1. Projenin toplam fiyatı

| Paket | Kapsam | Fiyat |
|---|---|---|
| **A — Web Sitesi + Teknik SEO** | Tasarım, tüm sayfalar, DSGVO, hız optimizasyonu, GSC/GA kurulumu | **2.400 €** |
| **B — A + Local SEO Paketi** | 10 şehir sayfası + 7 mekân (Hochzeitslocation) sayfası, schema, içerik | **3.600 €** |
| **C — Komple Sistem (önerilen)** | B + müşteri galerisi (seçim sistemi) + dijital davetiye + ödeme altyapısı | **4.900 €** |

Ödeme planı: %40 başlangıç, %30 tasarım onayı, %30 teslim.

**Opsiyonel aylık SEO bakımı:** 450 €/ay (min. 6 ay) — içerik üretimi, GBP yönetimi, backlink çalışması, aylık rapor.

## 2. Fiyata dahil olanlar

- Tam responsive, premium tasarım (Almanca + Türkçe, iki dilli)
- 12 statik sayfa + 10 şehir + 7 mekân landing page = **~40 indekslenebilir sayfa**
- Müşteri galerisi (parola korumalı, favori seçimi, admin bildirimi)
- Dijital davetiye sistemi (kişiye özel link, RSVP, geri sayım, harita, WhatsApp)
- Yönetim paneli (talepler, albüm seçimleri, davetiyeler, RSVP'ler)
- DSGVO paketi: cookie consent (granüler), self-hosted fontlar, Impressum/Datenschutz/AGB şablonları
- Teknik SEO: sitemap, robots, hreflang, canonical, schema markup (LocalBusiness, Service, FAQ, Offer, Breadcrumb, Article)
- Google Search Console + Google Analytics 4 kurulumu ve doğrulaması
- Core Web Vitals optimizasyonu (AVIF/WebP, lazy loading, CDN, sıfır JS bloklayıcı font)
- 1 saatlik video eğitim + yazılı kullanım kılavuzu
- Teslim sonrası 60 gün ücretsiz destek

## 3. Teknoloji

**Next.js 16 (App Router) + TypeScript + Tailwind CSS, Vercel üzerinde.**

Neden WordPress değil:

| Konu | WordPress + Elementor | Bu çözüm |
|---|---|---|
| Hız (Core Web Vitals) | Ağır; eklenti yüküyle LCP 3–5 sn | Statik üretim, LCP < 1,5 sn |
| Güvenlik | Sürekli eklenti güncellemesi, hack riski | Saldırı yüzeyi yok, sunucuda PHP yok |
| Aylık maliyet | Elementor Pro + galeri + form + SEO eklentileri ≈ 250–400 €/yıl | Hosting dahil ≈ 0–20 €/ay |
| Galeri & davetiye | Hazır eklenti, sınırlı, ek lisans | Tam özel, sınırsız |

**İçerik yönetimi:** Sanity CMS (ücretsiz plan yeterli) veya WordPress'i sadece "headless CMS" olarak. Müşteri yazıları, fotoğrafları, videoları, fiyatları, paketleri ve galerileri koddan hiç anlamadan panelden değiştirir.

> Müşteri hosting'i varsa: Next.js paylaşımlı PHP hosting'te çalışmaz. Domaini Vercel'e yönlendiririz (ücretsiz plan bu proje için yeterli), mevcut hosting e-posta için kullanılmaya devam eder. İstenirse müşterinin kendi VPS'ine de kurulabilir.

## 4. Online müşteri galerisi

- Her düğün için ayrı galeri + kod & parola (`site.de/galerie/elif-marco`)
- Fotoğraflar Vercel Blob / S3 (AB bölgesi), otomatik AVIF/WebP dönüşümü, tam çözünürlük indirme
- Her karede kalp butonu → müşteri albüm seçimini yapar → tek tıkla gönderir
- Seçim anında yönetim paneline düşer + e-posta bildirimi (fotoğraf numaraları listesiyle)
- Mobil öncelikli: seçim tarayıcıda saklanır, bağlantı koparsa kaybolmaz
- `noindex` + `robots.txt` ile Google'dan tamamen izole
- **Ek aylık maliyet: yok** (depolama kullanım bazlı, ~1–3 €/ay)

## 5. Dijital davetiye sistemi

- 4 adımlı sihirbaz + canlı önizleme (telefon çerçevesinde)
- Alanlar: gelin/damat, tarih, saat, salon, adres, fotoğraf, mesaj, tasarım seçimi
- Kişiye özel link: `site.de/einladung/ayse-mehmet`
- İçerik: canlı geri sayım, Google Maps yol tarifi, WhatsApp paylaşımı, RSVP (Geliyorum / Gelemiyorum + kişi sayısı + not)
- 4 hazır tasarım (klasik, botanik, modern, altın) — animasyonlu açılış ekranı, yumuşak geçişler
- **Ücretlendirme mantığı:** davetiye 79 €. Fotoğraf/video müşterisi rezervasyon onayında bir kupon kodu alır; kodu girdiğinde ücret 0 €'ya düşer. Kod tek kullanımlık ve müşteri kaydına bağlıdır.
- **Ödeme:** **PayPal Business** (sizde zaten var). Misafir PayPal hesabı olmadan da kredi kartı / banka kartı ile ödeyebiliyor. Komisyon: %2,49 + 0,35 € (Almanya içi). Sabit aylık ücret yok, ek şirket kaydı gerekmiyor. İsterseniz sonradan Stripe de eklenebilir.

## 6. Local SEO — yapılacak işler

**Teknik temel**
- URL yapısı: `/hochzeitsfotograf/stuttgart`, `/hochzeitslocations/schloss-solitude`
- Her sayfada tekil Meta Title / Description, tek H1, mantıklı H2-H3 hiyerarşisi
- Schema: LocalBusiness + Photograph, Service, FAQPage, Offer, BreadcrumbList, Article
- hreflang de/tr + x-default, canonical, XML sitemap (hreflang'li), robots.txt
- Görsel SEO: dosya adları, ALT metinleri, AVIF/WebP, boyut atamaları (CLS = 0)
- Core Web Vitals hedefi: LCP < 2,0 sn / INP < 200 ms / CLS < 0,05

**İçerik**
- Keyword araştırması (Ahrefs/Semrush + Google Suggest + rakip analizi)
- Şehir sayfaları: her biri **benzersiz** metin — o şehirdeki çekim noktaları, ışık saatleri, nikah dairesi, fiyat bilgisi, şehre özel SSS
- Kopyala-yapıştır şehir sayfası yok. Her sayfa gerçekten o şehir hakkında bilgi verir; Google'ın "doorway page" filtresine takılmaz.
- İç linkleme: şehir ↔ mekân ↔ referans çekim üçgeni

**Google Business Profile**
- Kategori: Hochzeitsfotograf (birincil) + Fotograf, Videograf
- Hizmet bölgesi tanımı, hizmet listesi, ürün/paket kartları
- Haftalık post rutini, Q&A doldurma, yorum toplama akışı (teslim e-postasına QR/link)
- GBP ↔ web sitesi bağlantısı: her şehir sayfasında NAP tutarlılığı, UTM'li profil linki
- Yerel dizin kayıtları (Das Örtliche, Gelbe Seiten, 11880, Hochzeitsportale) — NAP birebir aynı

## 7. Düğün mekânları (Hochzeitslocation) SEO stratejisi

Bu, ilanın en kritik maddesi ve rakiplerin çoğunun beceremediği yer.

**Yaklaşım:** Mekân sayfası ≠ mekân tanıtımı. Sayfa, **o mekânda çekim yapmış bir fotoğrafçının bilgisi**ni verir:

1. O mekândaki ışık durumu ve en iyi çekim saatleri
2. Saat saat önerilen zaman planı (nikah, karşılama, çift çekimi, giriş)
3. Mekândaki somut çekim noktaları
4. Mekânın kuralları (izin, flaş yasağı, drone, süsleme çekimi için zaman aralığı)
5. O mekâna özel SSS
6. O mekânda çekilmiş gerçek düğün referansı

Bu içerik Google'ın "helpful content" kriterine uyar, kopya değildir ve mekân araştıran çift için **gerçekten faydalıdır** — bu yüzden sıralanır ve dönüşür.

**Hedef aramalar:** `[Mekân adı] Hochzeitsfotograf`, `[Mekân adı] Hochzeit Fotograf`, `[Mekân adı] Hochzeitsvideo`, `Hochzeit [Mekân adı] Fotos`

**Ölçek:** İlk etapta bölgede yılda en çok düğün yapılan 15–25 mekân. Ayda 3–4 yeni mekân sayfası ile büyütülür.

**Yasal/etik sınır:** Mekân adı bilgilendirme amacıyla kullanılır, marka taklidi yapılmaz, mekânla ticari bağ iddia edilmez. Her sayfada bu yönde bir açıklama satırı vardır (demo'da mevcut).

## 8. Backlink stratejisi

Satın alınan link yok. Yapılacaklar:

1. **Mekân iş birliği:** Her çekimden sonra mekâna 10–15 kare ücretsiz kullanım hakkı → karşılığında "Fotograf: ..." kredisi ve web sitesinden link. En doğal ve en güçlü link kaynağı.
2. **Tedarikçi ağı:** DJ, Brautmoden, Florist, Hochzeitsplaner, Konditorei ile karşılıklı "Partner" sayfaları (aşırıya kaçmadan, gerçek çalışılan firmalarla).
3. **Gerçek düğün hikâyeleri:** Hochzeitsblogs (Hochzeitswahn, Liebelei, Frieden & Freude) gerçek düğün gönderimi — editoryal, güçlü link.
4. **Yerel basın/PR:** Bölge gazetesi ve şehir portallerine sezon haberi ("Bu yıl bölgede düğün trendleri") — yerel otorite.
5. **Dizinler:** Hochzeitsportale (Weddingwire/Hochzeit.click), yerel işletme dizinleri, meslek odası.
6. **Sponsorluk:** Yerel dernek/etkinlik sponsorluğu → .de uzantılı yerel link.

Hedef: ilk 6 ayda 25–40 kaliteli referans domaini, düzenli hızda (spike yok).

## 9–10. Referanslar

> Buraya kendi referans linklerini ekle. Bu demo projeyi de referans olarak verebilirsin:
> `https://<proje>.vercel.app/de` — özellikle şehir ve mekân sayfalarını göster.

## 11. İçerikleri kendim değiştirebilir miyim?

Evet — demo'daki panelden şu anda bile denenebilir (`/de/admin`, parola `demo`):

| Panel bölümü | Ne değişiyor |
|---|---|
| Metinler & iletişim | Ana sayfa başlığı/açıklaması, sayılar, telefon, e-posta, adres, çalışma saatleri (DE + TR) |
| Fiyatlar & paketler | Paket adları, fiyatlar, madde madde içerikler, öne çıkan paket, ek hizmetler |
| **Mekânlar** | Düğün salonu sayfaları: ad, tür, şehir, adres, kapasite, giriş metni, uzun metin, ışık notları, çekim noktaları, kurallar — **iki dilde**; yeni mekân ekleme ve silme |
| Müşteri galerileri | Yeni galeri (kod + parola), toplu fotoğraf yükleme, çiftin albüm seçimini görme |
| Davetiyeler | Oluşturulan davetiyeler, RSVP yanıtları, silme |

Fotoğraf ve video yükleme panelden yapılıyor; görseller tarayıcıda otomatik küçültülüp WebP/AVIF olarak sunuluyor.
Teslimde 1 saatlik ekran kaydı + yazılı kılavuz veriyorum.

## 12. Lisans / araç maliyetleri

| Kalem | Maliyet |
|---|---|
| Hosting (Vercel Hobby/Pro) | 0 € / 20 $ ay |
| Domain (.de) | ~10 €/yıl |
| CMS (Sanity free plan) | 0 € |
| Fotoğraf depolama (Blob/S3) | ~1–3 €/ay (kullanım bazlı) |
| E-posta gönderimi (Resend) | 0 € (3.000 mail/ay'a kadar) |
| PayPal Business | işlem başına %2,49 + 0,35 € (mevcut hesabınız) |
| Cookie consent | 0 € (kendi çözümümüz, Cookiebot'a gerek yok) |
| Fontlar | 0 € (self-hosted) |
| **Toplam sabit** | **~0–25 €/ay** |

WordPress karşılaştırması: Elementor Pro 99 $/yıl + galeri eklentisi 99 $/yıl + form 59 $/yıl + SEO 99 $/yıl + hosting ≈ **400–500 €/yıl**.

## 13. Teslim sonrası destek

- 60 gün ücretsiz hata düzeltme ve soru desteği
- Sonrası: 90 €/ay bakım (güncelleme, yedek kontrolü, küçük düzenlemeler) veya saatlik 65 €
- SEO bakım paketi: 450 €/ay

## 14–17. Takvim

Bugün anlaşılırsa **başlangıç: 2 iş günü içinde**. Toplam süre: **5 hafta**.

| Hafta | Aşama | Çıktı |
|---|---|---|
| 1 | Keşif & strateji | Keyword araştırması, şehir/mekân listesi, site haritası, tasarım yönü |
| 2 | Tasarım | Ana sayfa + iç sayfa tasarımı, onay |
| 3 | Geliştirme | Tüm sayfalar, CMS bağlantısı, DSGVO, hız |
| 4 | Sistemler | Müşteri galerisi + dijital davetiye + ödeme + panel |
| 5 | SEO & yayın | Şehir/mekân içerikleri, schema, GSC/GA, testler, canlıya alma, eğitim |

**Tahmini teslim: anlaşma tarihinden 5 hafta sonra.**
Şehir ve mekân sayfalarının içerikleri yayından sonra da haftalık olarak büyütülür (SEO bakım paketiyle).

---

## Neden ben

Bu teklifi yazmadan önce sistemin **çalışan bir demosunu** kurdum. Yukarıda anlatılan her şey — şehir sayfaları, mekân sayfaları, şifreli galeri, kalp ile albüm seçimi, dijital davetiye ve RSVP, çift dilli yapı, DSGVO cookie yönetimi — demo linkinde şu anda çalışıyor durumda. Teklifi okumak yerine deneyebilirsin.
