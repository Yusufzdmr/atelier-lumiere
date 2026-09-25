# Davetiye v1'in kaldırılması — sadece v2 kalıyor

**Tarih:** 2026-09-25
**Kapsam:** Sadece `php/` — Next.js tarafı bu spec dışında.
**Kaynak:** Yusuf: "davetiye kısmı çok karman çorman oldu" → iki paralel
davetiye sistemi (v1/v2) + admin panelindeki belirsiz "Designs" sekmeleri.

## Amaç

`docs/superpowers/specs/2026-08-19-davetiye-v2-design.md` ile başlayan
ikinci nesil davetiye motoru (v2) artık birincil sistem: canlıda **10
gerçek müşteri davetiyesi v2'de**, v1'de sadece Claude'un kendi test
verisi (`test-claude-*`, 2026-08-18, gerçek müşteri değil). v1'in son
gerçek özellik commit'i 2026-08-18'de kalmış, v2 o tarihten beri sürekli
geliştiriliyor. Yusuf'un kararı: **v2 tek sistem olsun, v1 tamamen gitsin.**

İlk bakışta "iki ayrı sistemi sil" gibi görünüyordu, ama inceleme
gösterdi ki v1'in `Invitations.php` ve `Themes.php` sınıfları zamanla
davetiyeyle ilgisiz yerlere de sızmış paylaşılan altyapı haline gelmiş
(bkz. §2, §3). Bu yüzden kapsam "iki dosyayı sil" değil, "v1'in
müşteri/misafir tarafını sil, paylaşılan çekirdeği ayıkla" şeklinde.

## Kapsam dışı

- URL şeması değişikliği — `/v2/einladung`, `/v2/designs` adresleri
  **aynen kalıyor**. 10 gerçek müşterinin misafirlere WhatsApp'tan
  gönderdiği linkler zaten bu adresler; "v2" öneki koddan/admin
  etiketlerinden kalkıyor ama URL'den kalkmıyor.
- v2'ye ödeme/kupon sistemi eklemek — `InviteV2Controller.php`'nin kendi
  yorumu bunun "Phase D"de geleceğini söylüyor, bu spec'in işi değil.
  `CustomerAdminController`'daki kupon-davetiye eşleştirmesi bu yüzden
  şimdilik üretici olmadan kalıyor (§5) — kapsam dışı, sonraki faz.
  Kupon sistemi kendisi (`Customers` sınıfındaki alanlar) kalıyor, sadece
  hangi davetiyenin kullandığı gösterimi düşüyor.
- DB tablolarını (`invitations`, `invite_guests`, `rsvps`) DROP etmek —
  şema `CREATE TABLE IF NOT EXISTS` deseniyle çalışıyor (bkz. `DURUM.md`),
  kullanılmayan tablo bırakmak var olan pratikle tutarlı (`share` alanı
  albümcü işinde aynı şekilde bırakıldı). 2 test kaydı ayrı, elle silinir
  (§6).
- `Invitations.php`'in dışına taşınan `slug()`'ı ayrı bir sınıfa
  (`Slugs.php` gibi) taşımak — çalışıyor, dokunan çok yer var, isim
  temizliği bu spec'in amacı değil. Sadece dosyanın geri kalanı düşüyor.

---

## 1. Silinecek dosyalar (tamamen)

| Dosya | Neden güvenli |
|---|---|
| `src/Controllers/InviteController.php` | v1'in sihirbaz/gösterim/yönetim/ödeme/kupon denetleyicisi, başka hiçbir yerden çağrılmıyor |
| `src/Guests.php` | Kişiye özel davetiye linkleri (misafir başına) — sadece `InviteController`, `Invitations.php`, v1 şablonları kullanıyor. v2'nin kendi misafir/RSVP mekanizması yok (henüz), bu özellik v1'e özel |
| `templates/pages/invite-wizard.php` | v1 sihirbazı |
| `templates/pages/invite-manage.php` | v1'in "verwalten" (çiftin misafir listesi) sayfası |
| `templates/pages/invitation.php` | v1'in gerçek davetiye kartı — sadece `InviteController::show()`/`designPreview()` render ediyor |
| `templates/pages/designs.php` | v1'in vitrin sayfası (`/designs`) |
| `public/assets/invite.js` | v1 sihirbazının JS'i (kupon kontrolü dahil) |
| `public/assets/invite-manage.js` | v1 "verwalten" sayfasının JS'i |

`public/index.php`'de: `use Atelier\Controllers\InviteController;` satırı
ve şu rotalar kalkar: `/{locale}/designs`, `/{locale}/designs/{thema}`,
`/{locale}/einladung`, `/{locale}/einladung/{slug}/zahlung`,
`/{locale}/einladung/{slug}/verwalten`, `/{locale}/einladung/{slug}`,
`/{locale}/einladung/{slug}/{gast}`, `/api/kupon`.

## 2. `Invitations.php` — sadece `slug()` kalıyor

`Invitations::slug()` davetiyeyle ilgisiz dört yerde kullanılıyor:
`Customers::create()` (müşteri kodu üretimi), `Guests.php` (8 çağrı —
ama `Guests.php` kendisi de gidiyor, §1), `Lists.php` (genel admin liste
sıralaması), `OgImage.php`, `ListAdminController.php`. Bunların hepsi
`Invitations.php` gidince kırılır, o yüzden `slug()` (ve varsa özel
yardımcıları) dosyada kalıyor, geri kalan yirmi metot (`find`, `all`,
`create`, `update`, `delete`, `slugAvailable`, `kindLabel`,
`occasionLine`, `theme`, `themeOutdated`, `refreshTheme`, `manageKey`,
`checkManageKey`, `manageUrl`, `addRsvp`, `rsvps`, `saveDraft`, `draft`,
`drafts`, `deleteDraft`, `checkCoupon`, `redeemCoupon`) siliniyor.

## 3. `Themes.php` — dokunulmuyor

`src/Controllers/DesignAdminController.php`'deki `ausThema()` (admin
panelinde "eski bir temanın renk/yazı tipini yeni bir v2 tasarımına
uygula" özelliği, aktif ve kullanılıyor) doğrudan `Themes::find()` ve
`Design::fromTheme()` → `Themes::complete()` üzerinden çalışıyor.
`Themes.php` silinirse bu özellik kırılır. Sınıf, sabitleri (`SPOTS`,
`MOVES`, `FONTS`, `INTROS`, `SCENES` vb.) ve depolama metotları
(`all/find/save/complete`) aynen kalıyor — sadece §4'te admin'deki
etiketi değişiyor.

## 4. Admin paneli

**Sekme etiketleri** (`src/Admin.php::TABS`, `einladung` grubu):
- `/themen` — şu an `'de' => 'Designs', 'tr' => 'Tasarımlar'`. Yeni:
  `'de' => 'Farbvorlagen', 'tr' => 'Renk Şablonları'` (Yusuf'un kararı).
  Sekmenin kendisi (`AdminController::themes()`, `templates/admin/themes.php`)
  değişmiyor — sadece ne olduğunu doğru söylüyor artık.
- `/designs` — şu an `'de' => 'Designs (v2)', 'tr' => 'Tasarımlar (v2)'`.
  Yeni: `'de' => 'Designs', 'tr' => 'Tasarımlar'` — artık tek sistem
  olduğu için "(v2)" ayrımına gerek yok.

**`InviteAdminController.php` + `templates/admin/invitations.php`** —
tamamen v1 şeklinde yazılmış, v2 sadece ayrı bir ek blok (`$v2` değişkeni,
şablonda kendi `<?php if ($v2 !== []) : ?>` bölümü). Yeniden yazım:
- `index()`'teki tüm v1 mantığı (rsvp/misafir/kupon zenginleştirmeli
  `$rows` inşası, `Invitations::`/`Guests::` çağrıları, `loeschen`/
  `entwurf-loeschen`/`gast-loeschen` aksiyonları) kalkar.
- `v2-zustand` aksiyonu ve `InvitationsV2::all()` kalır — şablonun `$v2`
  bloğu zaten bağımsız çalışıyordu, o kod neredeyse aynen kalabilir,
  artık tek liste odur.
- "Liegengebliebene Entwürfe" (yarım kalan taslaklar) bölümü
  `Invitations::drafts()` yerine `InvitationsV2::` eşdeğerini kullanacak
  şekilde sadeleşir (v2 kendi taslak metotlarına zaten sahip —
  `InvitationsV2::draft/saveDraft/deleteDraft`; bir de listeleyen bir
  metot gerekiyorsa — yoksa eklenir), `fassung`'a göre `/einladung` mü
  `/v2/einladung` mü diye dallanma kalkar (hep `/v2/einladung`).

**`CustomerAdminController.php`** — `show()`'daki `usedFor` bloğu
(müşterinin kuponuyla oluşturulan davetiyeleri `Invitations::find()` +
`Invitations::rsvps()` ile gösteren kısım) `Invitations.php` gidince
derlenmez hale gelir. Kaldırılır — üretici olmadığı için (§ Kapsam dışı)
zaten hep boş kalacaktı. `templates/admin/customer.php`'deki karşılık
gelen render bloğu da kalkar. Kupon alanlarının kendisi (`saveCoupon`,
`resetCoupon`, rastgele kupon üretimi) dokunulmadan kalır.

**`AdminController::overview()` + `templates/admin/overview.php`** —
genel bakışın üst sayaç kutularından "Einladungen"/"Davetiyeler"
`count($invitations)` ile v1'in `invitations` tablosunu sayıyor (satır
42, `Db::jsonList('SELECT data FROM invitations ...')`). v1 kalkınca bu
tablo kalıcı olarak boş kalır ama gerçekte 10 v2 davetiyesi var — sayaç
yanlış 0 gösterir. `$invitations` değişkeni `InvitationsV2::all()`'a
(veya hafif bir `COUNT` sorgusuna) çevrilir, sayaç kutusu ve
`Admin::pendingWork()`'e giden parametre buna göre güncellenir.
"Zusagen"/"Katılım bildirimleri" sayacı (`rsvps` tablosu) dokunulmuyor —
v2'nin henüz RSVP'si yok (§ Kapsam dışı, "Phase D"), 0 göstermesi
zaten doğru.

## 5. Site genel navigasyonu

`templates/partials/header.php` ve `templates/partials/footer.php`:
şu an `/einladung` (v1) ve ayrı `/v2/designs` linki yan yana duruyor
(header.php'deki yorum: "Zweite Fassung, zum Vergleich daneben. Eine der
beiden faellt weg, sobald entschieden ist." — bu spec o kararı
uyguluyor). Footer'da ayrıca üçüncü, i18n'siz sabit metinli bir
`/designs` linki daha var.

Değişiklik: her ikisinde de tek bir link kalıyor —
`I18n::t('nav.invitation')` metniyle, `/v2/designs` adresine. `nav.invitation2`
kullanımı ve footer'daki üçüncü `/designs` linki kalkar. (`dict.php`'deki
`nav.invitation2` anahtarının kendisi silinmiyor — kullanılmayan bir
sözlük anahtarı zararsız, temizliği bu spec'in kapsamı değil.)

## 6. Veri temizliği (ayrı, elle onaylı adım)

Canlı DB'deki 2 test kaydı (`test-claude-1787084561`,
`test-claude-full-1787084687`, `invitations` tablosu) — kod
kaldırıldıktan sonra zaten erişilemez hale geliyor ama tabloda kalıntı
olarak durur. Yusuf onaylarsa ayrı bir adımda silinir; bu spec'in kod
değişikliğine bağımlı değil, istenirse atlanabilir.

## 7. Test

Mevcut `tests/*.php` içinde v1'e özel (`InviteController`,
`Invitations::` — `slug()` hariç, `Guests::`) hiçbir saf-fonksiyon testi
yok gibi görünüyor (plan aşamasında `grep -rl "InviteController\|Guests::"
tests/` ile doğrulanacak) — siliniyorsa onlar da gider. Kalan
`Invitations::slug()` zaten dolaylı olarak `Customers`/`Guests`
(kaldırılıyor, bkz §1) testleri üzerinden kapsanıyorsa, doğrudan bir
`tests/invitations-slug.php` eklemek ucuz ve isabetli (boş girdi, Türkçe
karakter, uzunluk sınırı gibi kenar durumlar zaten `slug()` içinde
işleniyor olmalı — mevcut davranış değişmiyor, sadece dosya küçülüyor).

## Hata durumları / geriye dönük uyumluluk

- Eski `/einladung/*`, `/designs*` adreslerine gelen istekler → 404
  (`PageController::notFound`, mevcut desen). Gerçek müşteri linki
  olmadığı için (§ Amaç) pratikte kimseyi etkilemez.
- `/api/kupon` kalkınca, o adrese giden herhangi bir eski tarayıcı sekmesi
  (v1 sihirbazı zaten kalktığı için) sessizce 404 alır — v1 JS'in kendisi
  de silindiği için bu isteği artık kimse atmıyor.
