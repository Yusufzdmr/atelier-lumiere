# Theme Builder — kalan işler

24.08.2026'da Yusuf onayladı, sırası gelince yapılacak. Spec: paylaşılan
"Section-Based Theme Builder" dokümanı (sohbette, depoda değil).

Yapılanlar bu üçünün öncesi: `SectionRegistry` (tip / görünüm / ayar ayrımı),
üç kolonlu editör, hazır başlangıç düzenleri, çoğaltma, cihaz önizlemesi.
Commit'ler `8f6c3d5`…`3d66c6d`.

## 1 · Yeni bölüm tipleri

Onaylandı, ama **hangileri olduğu Yusuf'a sorulacak** — bölümün adı ve neyi
gösterdiği müşterinin gördüğü şey, yani onun kararı.

- `footer` — kapanış sözü, hashtag. Ucuz: yeni bir tip + bir `variantCss`
  bloğu, mevcut `text` tipine çok benziyor.
- `gift` — hesap bilgisi / hediye notu. Ucuz, ama IBAN gibi bir alanın
  müşteri tarafında düzenlenebilir olması gerekiyor (`sectionText` gibi
  kimliğe bağlı bir alan yeter).
- `music` — arka plan müziği. Orta: bir ses dosyası ve bir çalar; otomatik
  çalma tarayıcılarda engelli, yani bir düğmeye bağlanmalı.
- `gallery` — **pahalı olan bu.** Müşterinin kendi fotoğraflarını yüklemesi
  gerekiyor: `invitations_v2` tarafında bir dosya deposu, `Media::store`
  üzerinden yükleme, boyut/adet sınırı, ve sihirbazda yeni bir adım.
  Ayrı bir iş olarak planlanmalı, diğer üçüyle birlikte değil.

## 2 · Yayın durumu (taslak / yayında)

Onaylandı. `invitations_v2` iki kolon alacak:

```sql
ALTER TABLE invitations_v2
  ADD COLUMN status       VARCHAR(16) NOT NULL DEFAULT 'published',
  ADD COLUMN published_at TIMESTAMP   NULL DEFAULT NULL;
```

**Dikkat:** Projede migration mekanizması yok — `schema.sql` yalnızca
`CREATE TABLE IF NOT EXISTS` kullanıyor. Yani bu ALTER canlıda **elle**
çalıştırılacak, ve `schema.sql` de güncellenecek ki yeni kurulumlar aynı
şemayı alsın.

Varsayılan `published` olmalı: bugün var olan bütün davetiyeler yayında ve
linkleri dağıtılmış durumda. `draft` varsayılanı hepsini bir anda kapatırdı.

Ödeme kapısı buraya oturacak (spec §14: müşteri önce sonucu görsün, sonra
ödesin) — ama fiyat kararı ayrı, bkz. hafızadaki `fiyatlandirma-karari`.

## 3 · Undo/redo + sürükle-bırak

Onaylandı, risksiz, tamamen tarayıcı tarafı.

- Undo/redo: form durumunun anlık görüntüsü, ctrl+z / ctrl+shift+z. Spec §18
  uyarıyor: her fare hareketini geçmişe yazma, slider'larda debounce.
- Sürükle-bırak: `↑↓` düğmeleri zaten var ve `sec_reihenfolge` alanını
  yazıyor; sürükleme aynı alana yazacak, yeni bir mekanizma gerekmiyor.

**Autosave yapılmayacak** (spec §19 istiyor olsa da). Sebep: editör tek bir
form ve tek bir `version` kilidiyle çalışıyor. Otomatik kayıt, iki sekme açık
olduğunda birinin işini diğerinin üzerine yazar — kilit tam olarak bunu
engellemek için var. İstenirse önce kilit modeli değişmeli.
