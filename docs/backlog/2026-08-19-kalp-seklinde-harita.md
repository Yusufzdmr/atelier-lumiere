# Konumu kalp şeklinde haritayla göstermek — istek (kaydedildi, başlanmadı)

**Tarih:** 2026-08-19
**Kaynak:** Yusuf, oturum içinde ("konumu kalp şeklinde haritayla gösterebilir miyiz")
**Durum:** kayıtlı, planlanmadı. Davetiye v2 Faz 1'in kapsamı dışında.

## İstenen

Mekânın konumu düz bir kutu yerine **kalp şeklinde** bir haritayla görünsün.

## Bugün ne var

- **İletişim sayfası** (`templates/pages/contact.php:86`): iki tıkla açılan
  Google haritası. Onay verilmeden Google'dan hiçbir şey yüklenmiyor
  (`data-map` + `data-map-load`), altında "Route planen" bağlantısı.
- **Davetiye** (`templates/pages/invitation.php:312`): harita **yok**, yalnızca
  Google Maps'e yol tarifi bağlantısı var.
- CSP `frame-src` zaten `https://www.google.com`'a izin veriyor
  (`src/Http.php:95`), yani gömme teknik olarak engelli değil.

## Nasıl yapılabilir (iki yol)

1. **Gömülü haritayı maskele.** `clip-path` ile bir kalp yolu, iframe'in
   kendisine uygulanır. Ucuz, harita kaydırılabilir kalır. Ama Google'a bağlı:
   iki tıklı onay davetiyeye de taşınmalı, yoksa sitenin bugünkü veri koruma
   duruşu bozulur.
2. **Durağan, kendi barındırdığımız görsel.** Mekânın çevresi bir kez harita
   karosundan üretilir, kalple maskelenip `public/assets/` altına konur. Onay
   gerekmez, kâğıt estetiğine daha yakın, ama zum/kaydırma yok.

Davetiye için 2 daha uygun görünüyor; iletişim sayfası için 1.

## Ayhan'a sorulacaklar

- Harita **davetiyede** mi, **iletişim sayfasında** mı, ikisinde de mi?
- Kalbin içi harita mı olacak, yoksa harita düz kalıp üstündeki **işaret** mi
  kalp olacak?
- Kaydırılabilir/zumlanabilir olması şart mı? (Şartsa Google onayı da gelir.)

## Nereye oturur

Davetiye tarafında bölümler (`sections`) Faz 3'e ait; harita bölümü de oraya
düşer. İletişim sayfasında ise bugünkü kutunun yerine geçebilir, fazlardan
bağımsız küçük bir iş.
