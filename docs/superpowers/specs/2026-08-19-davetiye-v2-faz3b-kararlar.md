# Faz 3B (sihirbaz) — alınmış kararlar

**Tarih:** 2026-08-19
**Durum:** kararlar alındı, spec yazılmadı. Yeni oturum buradan başlar.

Faz 3A biterken bağlam sınırına gelindi; spec'i yazmak yerine onu belirleyecek
üç soru soruldu ve yanıtlandı. Aşağıdakiler **karar**, öneri değil.

## 1. Sihirbaz eskisinin yanında durur

Yeni sihirbaz `/{locale}/v2/einladung` adresinde kurulur. Eski `/{locale}/einladung`
çalışmaya devam eder; ona **dokunulmaz**.

Vitrindeki (3A) "bu tasarımla oluştur" düğmesi 3B bitince oraya çevrilir — bugün
`/{locale}/einladung?design=<slug>`'a gidiyor ve o kural tek yerde duruyor
(`Design::creatable()`). Yeni sihirbaz tasarımı **belgeden** okuyacağı için
"temada karşılığı var mı" sorusu da o gün anlamını yitirir ve `creatable()`
tamamen kalkar.

Gerekçe: eski sihirbaz ödemeyi, kuponu ve RSVP'yi de taşıyor. Yerine geçmek,
devralmayı (D) aynı anda yapmayı zorunlu kılardı. Yan yana durmak, bir şey
tutmazsa düğmeyi geri almayı tek satıra indiriyor.

## 2. Davetiye `invitations_v2` tablosunda saklanır

Faz 1'de bu tablo tam bunun için kuruldu ve şeması hazır:

```sql
invitations_v2 (slug, design_id, design_snapshot, data, created_at)
```

`design_snapshot` yayınlanmış davetiyeyi **dondurur**: tasarım sonradan
değiştirilse bile o davetiye aynı kalır. Eski `invitations` tablosuna
dokunulmaz.

Sonucu: panelin bugünkü davetiye listesi (`/admin/einladungen`) yeni davetiyeleri
**görmez**. Bu bilerek — panel tarafı 3B'nin kapsamında ayrıca ele alınmalı ya
da D'ye bırakılmalı. Spec bunu açıkça yazmalı.

## 3. Beş adım kalır

Eskinin sırası korunur: çift ve tarih → mekân → metinler → görseller →
önizleme ve onay. Müşteri o akışa alışkın ve eski sihirbazın davranışı elimizde.

Yenisinin farkı: **izin bayraklarını okur.** Faz 2'de her katmana `edit`,
`color`, `font`, `photo`, `text`, `hide` bayrakları verildi; sihirbaz müşteriye
yalnızca izin verilen alanları gösterir.

## Spec yazılırken cevaplanacaklar

Bunlar karara bağlanmadı, spec turunda sorulmalı:

1. Ödeme ve kupon 3B'de mi, D'de mi? (Öneri: D — ama o zaman 3B'nin ürettiği
   davetiye "ödenmemiş" durumda kalır ve bir yerde beklemesi gerekir.)
2. Yeni davetiyenin genel adresi ne olacak: `/{locale}/v2/einladung/{slug}` mi,
   yoksa eskisinin yanında `/{locale}/einladung/{slug}` mi? (İkincisi çakışır.)
3. RSVP yeni davetiyelerde çalışacak mı, yoksa D'ye mi kalacak?
4. Misafire özel adres (`/einladung/{slug}/{gast}`) 3B'de mi?

## Nereden devam edilir

`superpowers:brainstorming` ile bu dört soruyu sor, tasarımı bölüm bölüm sun,
sonra spec'i `docs/superpowers/specs/2026-08-19-davetiye-v2-faz3b-sihirbaz-design.md`
olarak yaz. Zemin hazır: format (Faz 1), panel (Faz 2), vitrin (3A) çalışıyor ve
canlıda.
