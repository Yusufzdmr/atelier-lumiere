# Açık işler — 27.08.2026

Bu dosya, 27 Ağustos oturumunun sonunda **bitmemiş** olanları taşır. Biten
işler git geçmişinde duruyor (`e289ddf` → `967ba35`, 21 commit), tekrar
anlatılmıyor.

---

## 1 · Telefon/Tablet çerçevesinde açılış filmi görünmüyor

**Belirti.** Tasarım düzenleyicide (`/{dil}/admin/designs/{slug}`) "Telefon"
sekmesine geçildiğinde, temanın açılış filmi ekrana gelmiyor. Onun yerine
sayfanın altındaki bölümler görünüyor.

**Ölçülenler (canlı sunucuda, `testyusuf1`).**

| Ne | Sonuç |
|---|---|
| Genel sayfa filmi içeriyor mu | evet, `data-intro-film`, `/assets/vorlagen/film.mp4` |
| Çerçevedeki `<video>` düğümü | var |
| `readyState` | 4 (tam yüklü) |
| Boyut | 390×741 |
| Kaplamanın (`[data-intro-video]`) hesaplanmış stili | `display:flex`, `opacity:1`, `visibility:visible`, `z-index:40`, `position:fixed` |
| `elementFromPoint(195,300)` | VIDEO döndürüyor |
| Çerçevenin iç kaydırması | 0 (kaydırılmamış) |
| Ekran görüntüsü | **filmi göstermiyor**, bölümü gösteriyor |

Yani DOM "görünür" diyor, ekran "yok" diyor. **Ölçümle görüntü çelişiyor.**

**En güçlü hipotez.** Çerçeve `passeAn()` içinde `transform: scale(...)` ile
küçültülüyor (`design-editor.js`, `[data-ansicht-rahmen] iframe`). Bir
`transform`, altındaki `position: fixed` öğelerin hangi kutuya göre
konumlandığını değiştirir. Kaplama `fixed inset-0` ile duruyor
(`partials/design-stage.php`). Doğrulanmadı.

**Denenecekler.**
- Çerçevenin `transform: scale`'ini geçici kaldırıp filmin gelip gelmediğine
  bakmak — hipotezi tek adımda ya doğrular ya çürütür.
- Doğrularsa: ölçekleme yerine `width`/`height` ile küçültmek, ya da kaplamayı
  `absolute` yapmak (o zaman sahnenin akışı da gözden geçirilmeli).

**Aciliyet: düşük.** Film artık "Kart" önizlemesinde görünüyor
(`[data-vorspann]`, `design-edit.php`), yani bakılacak bir yer var.

---

## 2 · Müziği YouTube/Spotify ile gömme

**Durum.** Doğrudan adresle müzik **çalışıyor**: `Design::safeAudio()` yerel
yolların yanı sıra `https://` adreslerini kabul ediyor (yalnız https — davetiye
https üzerinden çalışıyor, http bir kaynağı tarayıcı "karışık içerik" diye
reddeder ve ses sessizce çalmaz). Panelde ses alanının etiketi bunu söylüyor.

**Yapılmayan.** YouTube/Spotify gömme. Bilerek: bu bir alan değil, ayrı bir iş.

- iframe gerekir, `Http.php` içindeki `frame-src` politikasına eklenmeli
- çerez onayı (`consent.js`) kapsamına girer — üçüncü taraf içerik
- tarayıcıların otomatik başlatma kısıtları
- **ve en önemlisi:** davetiyenin arka planda çalan müziği bu gömülülerle
  çalışmıyor. `music/default` biçimi sesi sayfanın altına koyar ve kuvert
  açılınca başlatır; bir YouTube iframe'i bunu yapamaz.

Yani istenirse önce ürün kararı gerekiyor: "arka plan müziği" mi, yoksa
"tıklayıp dinlenen bir gömülü çalar" mı.

---

## Yayına alınmamış commit

`967ba35` ("Einen Vorspann wieder wegnehmen") oturum kapanırken henüz
sunucuya çıkmamıştı. Kontrol:

```bash
curl -s https://45-147-46-177.sslip.io/assets/design-editor.js | grep -c introWeg
```

`1` dönerse çıkmış demektir. `0` dönerse `php/YAYIN-VPS.md` içindeki kısa yol.

---

## Bu oturumda iki kez tekrarlanan hata — dikkat

Önizlemeye yeni bir parça eklerken **parçanın kendisi** ölçüldü,
**etrafındaki sayfaya ne yaptığı** ölçülmedi. İki kez canlıya bozuk çıktı:

1. Canlı bölüm önizlemesi, içine RSVP bölümünün formunu getirdi; o formun boş
   `csrf` alanı editörün formunda ikinci kez göründü. PHP aynı isimden
   sonuncuyu alır → token boşaldı → **Kaydet bozulacaktı**. Düzeltme:
   yerleştirilen alanlar kilitleniyor (`el.disabled = true`).
2. Aynı önizleme orta sütunu 2705 px büyüttü, sayfa 1100 → 4154 px oldu; yan
   sütunlar `sticky` olmadığı için aşağı inince liste ve ayar paneli kayboldu.

**Kural:** bir parçayı bir başkasının formuna/sütununa yerleştirirken
- `new FormData(form)` ile formun gerçekte ne gönderdiğini,
- `document.documentElement.scrollHeight` ile sayfanın boyunu

önce ve sonra ölç.
