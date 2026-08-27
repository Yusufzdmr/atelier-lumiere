# Açık işler — 27.08.2026 (akşam)

Bu dosya, 27 Ağustos oturumunun sonunda **bitmemiş** olanları taşır. Biten
işler git geçmişinde duruyor (`e289ddf` → `4d9dee3`, 23 commit), tekrar
anlatılmıyor.

Öğleden sonraki hâline göre ne değişti: 1. madde çözüldü ve yayına alındı,
yerine çerçevedeki sürüklemenin yarısı açık kaldı. 2. madde olduğu gibi
duruyor.

---

## 1 · Filmin oynadığını bir kez gözle görmek

**Durum: neredeyse kapalı.** Asıl hata bulundu, düzeltildi, yayına alındı ve
ölçüldü. Geriye tek bir şey kaldı ve bunu ben yapamam.

Çerçeve artık ekranda: cihaz görünümüne geçince `y = 232`, tamamen görünür.
Ama çerçevenin içindeki filmin **oynadığını** göremedim — otomasyon
tarayıcısında hiçbir video çözülmüyor. Kontrol ettim: bambaşka bir dosya
(`/assets/intro/lumina-swans.mp4`), farklı köken, CSP yok — o da `readyState 0`
ile takıldı. Yani gördüğüm siyah dikdörtgen ölçüm ortamının kusuru, sitenin
değil.

**Yapılacak:** kendi tarayıcınla `/de/admin/designs/testyusuf1` → *Telefon*.
Film geliyorsa madde tamamen kapanır.

Bu arada dosyayı da inceledim, sağlam: H.264 High 3.1, 478×850, sessiz,
0,97 saniye, 174 KB, sunucu `video/mp4` ve range destekli servis ediyor.
Dikkat: doküman bir yerde 5,2 saniyelik bir filmden söz ediyordu — şu anki
dosya bir saniyenin altında. Kasıtlı mıydı, bilmiyorum.

### Kök neden neydi (ve dokümandaki hipotez neden yanlıştı)

Buradaki eski "en güçlü hipotez" `transform: scale`'di. **Çürüdü**, iki ayrı
nedenle:

- `design-edit.php:177` zaten `transform-origin: top left` veriyor, yani
  ölçekleme konumsal kayma üretmiyor;
- daha temel olarak, bir `transform` yalnızca **aynı belgedeki**
  `position: fixed` öğeler için içeren blok oluşturur. Film kaplaması
  iframe'in kendi belgesinde; dıştaki `<iframe>` elemanına uygulanan transform
  onun iç viewport'unu etkilemez.

Gerçek sebep bambaşkaydı ve orta sütunun DOM sırasındaydı: **kart → canlı
bölümler → çerçeve**. Cihaz görünümüne geçince `karte.hidden = true` oluyordu
ama aradaki canlı bölümler kutusuna dokunulmuyordu; kutu yukarı kayıp çerçeveyi
ekranın dışına itiyordu.

Canlı sunucuda, `testyusuf1` üzerinde ölçülen:

| Ne | Sonuç |
|---|---|
| Canlı bölümler kutusu gizleniyor mu | **hayır** |
| Kutunun yüksekliği | 2677 px |
| Telefon çerçevesinin başlangıcı | **y = 2909** |
| Pencere yüksekliği | 855 |
| Çerçeve ekranda mı | **hayır** |
| Kutu gizlenince çerçeve | **y = 232**, tamamen görünür |

Filmle ilgili eski ölçümlerin hepsi doğruymuş: film oradaydı, `readyState 4`,
kaplama görünür, işaretçi testi VIDEO döndürüyordu. Sadece çerçevenin kendisi
üç ekran aşağıdaydı. "Ölçümle görüntü çelişiyor" denen şey buydu — ölçülen
şey çerçevenin **içi**, görülen şey çerçevenin **dışıydı**.

Düzeltme `abschnitteZeigen()` (`design-editor.js`): kararı tek yere topluyor.
Tek yer olması şart, çünkü cihaz düğmeleri formda `button[type=button]` ve
tıklama canlı önizlemenin yeniden çekilmesini de tetikliyor — iki yerde
karar verilseydi kutu 400 ms sonra kendiliğinden geri gelirdi.

---

## 2 · Müziği YouTube/Spotify ile gömme

Değişmedi.

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

## 3 · Çerçevede sürüklemenin ikinci yarısı

**Durum: yarısı bitti, yayında, ölçüldü.**

"Sürükle bırak hala diğer bölümlerde çalışmıyor … telefon tablet masaüstü
kısmında falan da." Sebep basitti: bütün işaretçi bağları kart kutusundaydı
(`vorschau`), ve cihaz görünümünde o kutu tamamen gizleniyor. İframe içinde
tek bir `pointerdown` yoktu — bozulmuş değildi, hiç yapılmamıştı.

**Biten.**

- Çerçeve düzenlemeyi canlı izliyor. Yedi yazma noktası `setzeMarke()` ve
  `knotenAlle()` üzerinden geçiyor. **İkinci çizici değil**: hesap tek yerde
  (`stelle()`), yalnızca sonucun bırakıldığı yer sayısı bir'den ikiye çıktı.
- Çerçevede **iki** kök var, bir değil: `Design::css()` marka değişkenlerini
  kapsam seçicisine yazıyor ve o kapsamı hem `.d-stage` hem `.d-sec-flaeche`
  taşıyor. Ölçüldü: yalnız sahneye yazınca kart boyanıyor (1,2,3), bölümler
  eski renkte kalıyordu (251,246,238). Yarım boyama, hiç boyamamaktan kötü.
- Taşıma çalışıyor. Canlıda ölçülen: katman `washa`, x −16→−6, y −10→−2,
  ve iki kutuda birebir aynı.

**Kalan — dört parça.**

| # | Ne | Neden ayrı |
|---|---|---|
| 3 | Seçim çerçevesi + 8 tutamak iframe içinde çizilsin | `rahmenWahl` `vorschau`'ya ekleniyor; koordinatları o kutuya göre `offsetLeft`/`offsetTop`. Çerçeveye olduğu gibi konulsa yan sütunun genişliği kadar kayar. |
| 4 | `.b-rahmen-wahl` / `.b-griff` stilleri iframe belgesine enjekte edilsin | O CSS `design-edit.php:210`'da; davetiye sayfasında yok, yani tutamaklar stilsiz çıkar. |
| 5 | Sürüklemeden önce çerçevedeki zarf açılsın | Kart zarfın arkasında. `oeffneRahmen()` zaten var, bağlanması gerek. |
| 6 | Çift tıkla metni yerinde düzenleme | `dblclick` hâlâ tek kutuda — `design-editor.js:1097`, kart kutusunda kalan tek olay bağı. |

3 ve 4 birlikte anlamlı: ikisi olmadan çerçevede boyutlandırma yok, sadece
taşıma var.

---

## Bu oturumda iki kez tekrarlanan hata — dikkat

*(Öğleden önceki nottan, hâlâ geçerli.)*

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

**Üçüncü kez oldu.** 1. maddenin kök nedeni tam olarak buydu: canlı bölümler
kutusu 2677 px yer kaplıyor ve yanındaki çerçeveyi ekranın dışına itiyordu.
Aynı kutu, aynı hata, üçüncü tekrar. Kural işe yarıyor — uygulanmadığı için
değil, uygulanmadığında.

---

## Ve bir kural daha: yanlış alarm vermeden önce kontrol deneyi

Bu oturumda az kalsın "canlı site bozuk, davetliler siyah ekran görüyor"
denecekti. Zincir mantıklıydı: film yüklenmiyor → iframe'de de yüklenmiyor →
üst düzey sayfada da yüklenmiyor → dosyayı doğrudan açınca da yüklenmiyor.
Dört ölçüm, hepsi tutarlı, hepsi aynı yöne işaret ediyor.

Eksik olan tek şey **bilinen-iyi bir örnekti**. Bambaşka bir video denendiğinde
o da takıldı: sorun sitede değil, ölçüm ortamındaydı. Dört tutarlı ölçüm, tek
bir kontrol deneyinin yerini tutmuyor.

**Kural:** "X bozuk" demeden önce, X'in yerine çalıştığını bildiğin bir şey
koy. Aynı şekilde bozuluyorsa bozuk olan X değil, ölçen şeydir.
