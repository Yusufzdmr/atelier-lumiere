# Açık işler — 27.08.2026 (akşam)

Bu dosya, 27 Ağustos oturumunun sonunda **bitmemiş** olanları taşır. Biten
işler git geçmişinde duruyor (`e289ddf` → `126bcec`, 27 commit), tekrar
anlatılmıyor.

Öğleden sonraki hâline göre ne değişti: 1. madde çözüldü ve yayına alındı
(geriye tek bir gözle bakma kaldı), 3. madde bütünüyle bitti. 2. madde olduğu
gibi duruyor — ve tek gerçek açık iş o.

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

**İlk düzeltme yanlıştı ve aynı akşam geri alındı.** Kutuyu cihaz görünümünde
gizlemek çerçeveyi ekrana getirdi, ama aynı anda **kaydedilmemiş bir bölüm
değişikliğinin görülebildiği tek yeri** de ortadan kaldırdı: çerçeve sayfayı
sunucudan alıyor, ondan haberi yok. Sağdan bir bölümün hizasını değiştiren
hiçbir şey göremez oldu — "sağdaki özelliklerden değiştiriyorum ama hiçbir şey
olmuyor".

Hata kutunun varlığı değil, **yeri**ymiş: kartla çerçevenin arasında duruyordu.
Çerçeve öne alınınca kimseyi itmiyor ve ikisi de görünüyor — **kart, çerçeve,
bölümler**. Gizleme tamamen kalktı; kutu yine yalnızca gösterilecek bir şey
olup olmadığına bakıyor.

Canlıda ölçülen (düzeltmeden sonra): çerçeve y=292 ve ekranda, bölümler kutusu
y=1022'de ve görünür; bir bölümün hizası değiştirilince kutunun içeriği
değişiyor (6801 → 6841 bayt), geri alınınca aynen dönüyor.

Ders: belirtiyi değil sebebi düzelt. Skriptte bir fonksiyon yerine markup'ta
bir satır yeterdi, ve o satır yan hasar üretmezdi.

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

## 3 · Çerçevede sürükleme — bitti

**Durum: altı parçanın altısı da bitti, yayında, tek tek ölçüldü.**

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

**Sonradan biten (3 ve 4).** Seçim çerçevesi artık kök başına bir tane ve
kendi belgesinde doğuyor; ofset zinciri kökün ta kendisine kadar toplanıyor
(kart katmanlarında üç seviye: `.d-el` → `.d-card` → `.d-stage-mitte` →
`.d-stage`). Tutamak kuralları JS'te ikinci kez yazılmıyor, editörün derlenmiş
stil bloğundan kopyalanıp `[data-design-preview]` → `[data-zieht-bereit]`
çevriliyor — içlerinde `touch-action:none` de var, o olmadan çerçevede parmak
sayfayı kaydırırdı. Canlıda ölçüldü: kurallar enjekte, 8 tutamak, seçim
çerçevesi katmanla birebir örtüşüyor (51,101, 291×40).

**Ve 5 ile 6 da bitti.** Cihaz görünümüne geçince çerçevedeki zarf kendiliğinden
açılıyor (`kuvertAuf`, aynı `oeffneRahmen()` üzerinden — soldan bölüm seçmenin
zaten kullandığı yol), yani kart artık görünüyor ve kör sürüklenmiyor. Çift tık
da her kökte geçerli.

Çift tıkta bir tuzak vardı, taşımada görünmeyen cinsten: seçim ve Range,
düğümün durduğu **belgeye** ait. Editörün `document.createRange()`'iyle
çerçevedeki bir düğümü işaretlemek belge sınırını aşar ve hiçbir şey seçmez.
`el.ownerDocument` ve onun `defaultView`'ı kullanılıyor. Canlıda ölçüldü:
seçim çerçevenin penceresinde kuruldu, metin ("Wir heiraten") baştan sona
seçili.

**Kalan: yok.** Dördü de yayında ve ölçüldü.

Bir not, ileride yanlış yorumlanmasın diye: düzenlemeyi programatik `blur()`
ile bitirmek çalışmıyor — ama bu çerçeveye özgü değil. Kart kutusunda, yani
aylardır çalışan yolda, birebir aynı sonuç çıkıyor. Enter ve Escape ikisinde de
çalışıyor. Gerçek bir tıklamayla çıkışı kendi elinle bir kez denersen iyi olur.

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
