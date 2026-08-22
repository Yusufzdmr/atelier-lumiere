# Davetiye v2 — Video, motif ve sadeleşme · Uygulama planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Davetiye v2'de video birinci sınıf bir malzeme olur, süsleme koddan dosyaya taşınır, ve 32 hareket seçeneği 8'e iner.

**Architecture:** `video`, `Design` belgesinde `photo`'nun kardeşi bir katman tipidir — kendi tablosu, kendi editörü, kendi konum kavramı yoktur; `spot` alanı onu da yerleştirir. Açılış videosu ayrı bir sahiptedir (tema alanı, grafikerin kararı), katman videosu ayrı (kitaplık, çiftin seçimi). Süsleme `Scenes::html()`'in ürettiği SVG'den, temanın `decorations` dizisindeki dosyalara taşınır — ve `Scenes.php` ancak her tema dışa aktarılıp gözle doğrulandıktan sonra silinir.

**Tech Stack:** PHP 8 (framework yok, Composer yok), MariaDB, önceden derlenmiş CSS, düz JS (modül yok).

**Spec:** [`docs/superpowers/specs/2026-08-23-davetiye-v2-video-ve-motif-design.md`](../specs/2026-08-23-davetiye-v2-video-ve-motif-design.md)

## Global Constraints

- **Kapsam yalnızca `php/`.** `app/`, `lib/`, `scripts/` (Next.js sürümü) bu planın dışında; tek satır değişmez.
- **Testler:** `php bin/test.php [filtre]` ile koşar. `php/tests/` altındaki dosyalar düz PHP'dir, `require` edilirler; kullanılabilir yardımcılar yalnızca `assert_same`, `assert_true`, `assert_contains`, `assert_not_contains`, `needs_db`. Test koşucusu `config.php` yüklemez — **testler veritabanına dokunamaz.**
- **Otomatik yükleyici:** `Atelier\Foo` → `php/src/Foo.php`. `Atelier\Controllers\Bar` → `php/src/Controllers/Bar.php`.
- **CSS derlenmiştir.** `php/public/assets/style.css` elle bakılan, önceden üretilmiş bir dosyadır; Tailwind JIT **yoktur**. Var olmayan bir sınıf adı sessizce hiçbir şey yapmaz. Yeni stil ya `style.css`'e elle eklenir ya da şablondaki `<style>` bloğuna yazılır.
- **Kod içi açıklamalar Almanca**, mevcut dosyaların üslubuyla. Türkçe yalnızca panelin TR arayüz metinlerinde (`$tr ? '…' : '…'`).
- **Her yazan uç noktada CSRF** vardır: `Security::checkCsrf($_POST['csrf'] ?? null)`.
- **Hiçbir `src` doğrudan yazılmaz.** Her görsel/video yolu `Design::safeSrc()`'den geçer; o yalnızca `/uploads/…` ve `/assets/…` kabul eder.
- **`Media::MAX_VIDEO` = 100 MB.** Sunucu yeniden kodlamaz.
- **Commit mesajları** bu deponun üslubunda: Almanca, tek satır özet, altında neden.
- **Görev 9 (`Scenes.php` silme) Görev 8 tamamlanıp gözle doğrulanmadan yapılamaz.** Sıra bir öneri değil, kapıdır.

---

## Dosya haritası

| Dosya | Sorumluluk | Görev |
|---|---|---|
| `php/src/Design.php` | `video` katmanının şeması, çizimi, stili; `poster` alanı; `fromPost` | 1, 2 |
| `php/tests/design_complete.php` | `poster` tamamlanıyor mu | 1 |
| `php/tests/design_html.php` | `video` çizimi | 1 |
| `php/tests/design_css.php` | `video` için `object-fit` | 1 |
| `php/tests/design_admin.php` | `poster_` formdan okunuyor mu | 2 |
| `php/templates/admin/design-edit.php` | `$videoEbenen` süzgeci | 2 |
| `php/templates/admin/design-edit-sections.php` | Video katmanı bölümü + yeni katman tipi seçici | 2 |
| `php/src/Controllers/DesignAdminController.php` | Video/poster yükleme, video katmanı oluşturma, kitaplık işlemleri | 2, 3 |
| `php/src/DesignVideos.php` | **Yeni.** Video kitaplığının şeması ve saklanması | 3 |
| `php/tests/design_videos.php` | **Yeni.** Kitaplık şeması | 3 |
| `php/templates/admin/designs.php` | Kitaplık bloğu | 3 |
| `php/src/DesignWizard.php` | Video katmanı `photo` hakkına bağlanır | 4 |
| `php/tests/design_wizard.php` | Aynısının testi | 4 |
| `php/templates/pages/invite-v2-wizard.php` | Çift kitaplıktan seçer | 4 |
| `php/src/Controllers/InviteV2Controller.php` | Seçilen videonun belgeye yazılması | 4 |
| `php/src/Themes.php` | `introVideo`/`introPoster` alanları; budanmış hareket listeleri | 5, 7 |
| `php/templates/admin/themes.php` | Açılış videosu yükleme kutusu | 5 |
| `php/src/Controllers/AdminController.php` | Açılış videosu yükleme/silme | 5 |
| `php/templates/partials/design-stage.php` | Açılış videosu düğümü | 5 |
| `php/public/assets/invitation.js` | Oynatmayı başlatır, açılış videosunu bekler | 6 |
| `php/tests/themes_motion.php` | **Yeni.** Budanmış listeler | 7 |
| `php/bin/scene-to-decorations.php` | **Yeni.** Dışa aktarılan SVG'yi temanın `decorations`'ına yazar | 8 |
| `php/src/Scenes.php` | **Silinir** | 9 |
| `php/templates/pages/invitation.php` | İki `Scenes::` çağrısı kalkar | 9 |
| `php/bin/seed-designs.php` | İki referans tasarım | 10 |

---

## Task 1: `video` bir katman tipi olur

**Files:**
- Modify: `php/src/Design.php` — `completeElement()` (183-250), `css()` (365), `html()` (548)
- Test: `php/tests/design_complete.php`, `php/tests/design_html.php`, `php/tests/design_css.php`

**Interfaces:**
- Consumes: `Design::TYPES` (zaten `'video'` içeriyor), `Design::safeSrc()`, `Themes::SPOTS`
- Produces: `poster` adında yeni bir katman alanı (string, `safeSrc` süzgecinden geçmiş). `Design::html()` `video` tipi için `<video …>` düğümü basar. Görev 2 ve 4 bu ikisine dayanır.

- [ ] **Step 1: Şema testini yaz (başarısız olacak)**

`php/tests/design_complete.php` dosyasının **sonuna** ekle:

```php
/* --- Video: poster ist ein eigenes Feld und geht durch dieselbe Pruefung --- */

$doc = Design::complete(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/film.mp4', 'poster' => '/uploads/film.jpg'],
    ['id' => 'fremd', 'type' => 'video', 'src' => '/uploads/a.mp4', 'poster' => 'https://beispiel.de/x.jpg'],
    ['id' => 'ohne', 'type' => 'video', 'src' => '/uploads/b.mp4'],
]]);

assert_same('video', $doc['layers'][0]['type'], 'complete: video bleibt video');
assert_same('/uploads/film.jpg', $doc['layers'][0]['poster'], 'complete: eigener Poster kommt durch');
assert_same('', $doc['layers'][1]['poster'], 'complete: fremder Host wird zu leer');
assert_same('', $doc['layers'][2]['poster'], 'complete: ohne Angabe ist der Poster leer');
```

- [ ] **Step 2: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_complete
```

Beklenen: `complete: eigener Poster kommt durch` başarısız — `poster` anahtarı yok (`Undefined array key`).

- [ ] **Step 3: `poster` alanını ekle**

`php/src/Design.php`, `completeElement()` içindeki `$defaults` dizisine `'src' => ''` satırının **altına**:

```php
            'poster'      => '',
```

Ve aynı fonksiyonda `$el['src'] = (string) $el['src'];` satırının **altına**:

```php
        // Der Poster ist ein Bild, keine zweite Quelle: er wird sofort
        // geprueft und nicht erst beim Zeichnen. Ein fremder Host hat in
        // einem poster-Attribut nichts verloren - dieselbe Regel wie bei src,
        // nur frueher, weil hier kein Zweig ihn spaeter noch abfangen wuerde.
        $el['poster'] = self::safeSrc((string) $el['poster']);
```

- [ ] **Step 4: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php design_complete
```

Beklenen: PASS.

- [ ] **Step 5: Çizim testini yaz (başarısız olacak)**

`php/tests/design_html.php` dosyasının **sonuna** ekle:

```php
/* --- Video: wird gezeichnet, aber nie von allein gestartet --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'spot' => 'page',
     'src' => '/uploads/film.mp4', 'poster' => '/uploads/film.jpg'],
]];
$html = Design::html($doc, [], 'de');

assert_contains($html, '<video', 'html: video wird gezeichnet');
assert_contains($html, '/uploads/film.mp4', 'html: die Quelle steht da');
assert_contains($html, 'poster="/uploads/film.jpg"', 'html: der Poster steht da');
assert_contains($html, 'muted', 'html: video ist stumm');
assert_contains($html, 'playsinline', 'html: video laeuft im Fluss, nicht im Vollbild');
assert_contains($html, 'loop', 'html: video laeuft in der Schleife');
assert_contains($html, 'd-spot-page', 'html: video traegt seinen Ort');

// Das Wichtigste: OHNE autoplay. Sonst dreht sich der Film hinter dem
// geschlossenen Kuvert, sieht ihn niemand, und das Handy zahlt.
assert_not_contains($html, 'autoplay', 'html: video startet NICHT von allein');

/* --- Video ohne Quelle: der Knoten bleibt, wie bei photo --- */

$leer = Design::html(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => ''],
]], [], 'de');

assert_contains($leer, '<video', 'html: leeres Video behaelt seinen Knoten');
assert_contains($leer, 'hidden', 'html: und ist versteckt');

/* --- Fremde Quelle faellt weg --- */

$fremd = Design::html(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => 'https://beispiel.de/f.mp4'],
]], [], 'de');

assert_not_contains($fremd, 'beispiel.de', 'html: fremder Host wird verworfen');
```

- [ ] **Step 6: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_html
```

Beklenen: `html: video wird gezeichnet` başarısız — bugün `video` sessizce atlanıyor.

- [ ] **Step 7: `video` dalını yaz**

`php/src/Design.php`, `html()` fonksiyonunda `shape` dalından sonra duran

```php
            // video: Faz 3. Bis dahin wird das Element still uebersprungen.
```

satırını **şununla değiştir**:

```php
            if ($el['type'] === 'video') {
                $src = self::safeSrc($el['src']);

                // Kein autoplay. Das Attribut wuerde den Film hinter dem
                // geschlossenen Kuvert laufen lassen - unsichtbar, und im
                // Mobilfunk bezahlt. invitation.js startet ihn, wenn die
                // Karte frei liegt (und gar nicht bei reduzierter Bewegung).
                $attr = ' muted loop playsinline preload="metadata" aria-hidden="true"';

                if ($src === '') {
                    // Wie bei photo: der Knoten bleibt stehen, damit die
                    // Vorschau im Assistenten etwas hat, worin sie das
                    // gerade Gewaehlte zeigen kann.
                    $out .= '<video class="' . e($class) . '"' . $attr . ' hidden></video>';
                    continue;
                }

                $poster = self::safeSrc($el['poster']);

                $out .= '<video class="' . e($class) . '" src="' . e($src) . '"'
                    . ($poster !== '' ? ' poster="' . e($poster) . '"' : '')
                    . $attr . '></video>';
            }
```

- [ ] **Step 8: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php design_html
```

Beklenen: PASS.

- [ ] **Step 9: Stil testini yaz (başarısız olacak)**

`php/tests/design_css.php` dosyasının **sonuna** ekle:

```php
/* --- Video fuellt seine Flaeche wie ein Bild --- */

$mitFilm = Design::css(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/f.mp4',
     'box' => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100]],
]], '.d-x');

assert_contains($mitFilm, 'object-fit:cover', 'css: video wird beschnitten, nicht gezerrt');

/* --- Ohne Hoehe hat object-fit nichts zu tun: die Regel bleibt trotzdem
       harmlos, aber die Hoehe muss auto sein --- */

$ohneHoehe = Design::css(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/f.mp4',
     'box' => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 0]],
]], '.d-x');

assert_contains($ohneHoehe, 'height:auto', 'css: ohne Hoehe bestimmt der Film seine Proportion selbst');
```

- [ ] **Step 10: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_css
```

Beklenen: `css: video wird beschnitten, nicht gezerrt` başarısız.

- [ ] **Step 11: `object-fit` koşuluna `video` ekle**

`php/src/Design.php`, `css()` içinde (~365. satır):

```php
                . ($el['type'] === 'image' || $el['type'] === 'photo' ? 'object-fit:cover;' : '')
```

**şununla değiştir:**

```php
                . (in_array($el['type'], ['image', 'photo', 'video'], true) ? 'object-fit:cover;' : '')
```

- [ ] **Step 12: Tüm testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS. Mevcut testlerin hiçbiri kırılmamalı — `poster` yeni bir alan, eski belgelerde boş.

- [ ] **Step 13: Commit**

```bash
git add php/src/Design.php php/tests/design_complete.php php/tests/design_html.php php/tests/design_css.php
git commit -m "$(cat <<'EOF'
Der Film ist jetzt eine Ebene und nicht mehr eine Zeile Kommentar

Design::TYPES kannte 'video' seit Faz 2, html() hat es still uebersprungen.
Jetzt zeichnet es - mit poster als eigenem Feld, das durch dieselbe
safeSrc-Pruefung geht wie src, nur frueher.

Ohne autoplay, mit Absicht: das Attribut liesse den Film hinter dem
geschlossenen Kuvert laufen. Wer ihn startet, steht in Aufgabe 6.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: Editörde video katmanı — düzenlemek ve yenisini kurmak

**Files:**
- Modify: `php/src/Design.php` — `fromPost()` (965 civarı, katman döngüsü)
- Modify: `php/templates/admin/design-edit.php` — süzgeç satırları (39-41)
- Modify: `php/templates/admin/design-edit-sections.php` — „5 · Görseller" bölümü (102-200)
- Modify: `php/src/Controllers/DesignAdminController.php` — `mitHochgeladenenBildern()` (364), `mitNeuerBildebene()` (292)
- Test: `php/tests/design_admin.php`

**Interfaces:**
- Consumes: Görev 1'in `poster` alanı ve `video` tipi
- Produces: Form alanları `video_<id>` (dosya), `poster_<id>` (dosya), `src_<id>` (yol), `posterpfad_<id>` (yol); yeni katman için `neue_ebene_typ` = `photo` | `video`. Görev 3 kitaplık seçimini `src_<id>` üzerinden yazar.

- [ ] **Step 1: `fromPost` testini yaz (başarısız olacak)**

`php/tests/design_admin.php` dosyasının **sonuna** ekle:

```php
/* --- Video-Ebene: Pfad und Poster kommen aus dem Formular --- */

$mitFilm = Design::complete([
    'id' => 'pruef2', 'slug' => 'pruef2',
    'layers' => [['id' => 'film', 'type' => 'video', 'spot' => 'page']],
]);

$neu = Design::fromPost($mitFilm, [
    'src_film'        => '/uploads/designs/a.mp4',
    'posterpfad_film' => '/uploads/designs/a.jpg',
]);

assert_same('/uploads/designs/a.mp4', $neu['layers'][0]['src'], 'fromPost: Videopfad wird uebernommen');
assert_same('/uploads/designs/a.jpg', $neu['layers'][0]['poster'], 'fromPost: Posterpfad wird uebernommen');

/* --- Ein fremder Poster kommt nicht durch, auch nicht ueber das Formular --- */

$fremd = Design::fromPost($mitFilm, ['posterpfad_film' => 'https://beispiel.de/x.jpg']);
assert_same('', $fremd['layers'][0]['poster'], 'fromPost: fremder Poster wird verworfen');
```

- [ ] **Step 2: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_admin
```

Beklenen: `fromPost: Posterpfad wird uebernommen` başarısız — boş geliyor.

- [ ] **Step 3: `fromPost`'a poster alanını ekle**

`php/src/Design.php`, `fromPost()` içindeki katman döngüsünde

```php
            $quelle = $text('src_' . $id);
            if ($quelle !== null && $quelle !== '') {
                $doc['layers'][$i]['src'] = $quelle;
            }
```

bloğunun **altına**:

```php
            // Eigenes Feld und nicht src: ein Video hat zwei Adressen, und
            // wer den Film tauscht, will nicht jedes Mal auch das Standbild
            // neu setzen muessen. complete() prueft beide.
            $bild = $text('posterpfad_' . $id);
            if ($bild !== null) {
                $doc['layers'][$i]['poster'] = $bild;
            }
```

> **Dikkat:** `!== ''` kontrolü **yok** — poster'ı boşaltmak geçerli bir istektir (kaldırmak). `src` için o kontrol var, çünkü boş bir `src` katmanı kör eder.

- [ ] **Step 4: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php design_admin
```

Beklenen: PASS.

- [ ] **Step 5: Editörde video katmanlarını ayır**

`php/templates/admin/design-edit.php`, 41. satırın **altına**:

```php
$videoEbenen = array_filter($design['layers'], static fn (array $l): bool => $l['type'] === 'video');
```

- [ ] **Step 6: Video bölümünü şablona ekle**

`php/templates/admin/design-edit-sections.php`, „5 · Görseller" bölümünü kapatan `<?= $zu ?>` satırından **sonra** ekle:

```php
<?= $auf($tr ? '5b · Videolar' : '5b · Videos') ?>
  <?php if ($videoEbenen === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu tasarımda video katmanı yok.' : 'Diese Vorlage hat keine Videoebene.' ?></p>
  <?php endif; ?>
  <?php foreach ($videoEbenen as $ebene) : ?>
    <?php
      $filmQuelle  = Design::safeSrc((string) $ebene['src']);
      $filmPoster  = Design::safeSrc((string) $ebene['poster']);
    ?>
    <div class="border-t border-sand-deep pt-5 first:border-0 first:pt-0">
      <div class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?></div>

      <div class="mt-3 flex items-start gap-5">
        <div class="w-24 shrink-0">
          <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand">
            <?php if ($filmQuelle !== '') : ?>
              <video src="<?= e($filmQuelle) ?>" muted preload="metadata"
                     <?= $filmPoster !== '' ? 'poster="' . e($filmPoster) . '"' : '' ?>
                     class="h-full w-full object-cover"></video>
            <?php else : ?>
              <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
                <?= $tr ? 'video yok' : 'kein Video' ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="w-full">
          <label class="<?= $label ?>"><?= $tr ? 'Yeni video yükle' : 'Neues Video hochladen' ?>
            <input type="file" name="video_<?= e($ebene['id']) ?>"
                   accept="video/mp4,video/webm,video/quicktime" class="<?= $feld ?>"></label>

          <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da yol' : 'oder Pfad' ?>
            <input name="src_<?= e($ebene['id']) ?>" value="<?= e((string) $ebene['src']) ?>"
                   class="<?= $feld ?> font-mono text-[0.78rem]"></label>

          <label class="<?= $label ?> mt-4 block"><?= $tr ? 'Kapak görseli yükle' : 'Standbild hochladen' ?>
            <input type="file" name="poster_<?= e($ebene['id']) ?>"
                   accept="image/png,image/jpeg,image/webp" class="<?= $feld ?>"></label>

          <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da kapak yolu' : 'oder Standbild-Pfad' ?>
            <input name="posterpfad_<?= e($ebene['id']) ?>" value="<?= e((string) $ebene['poster']) ?>"
                   class="<?= $feld ?> font-mono text-[0.78rem]"></label>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php /*
     Die Vorgaben stehen schon einmal im Haus: templates/admin/themes.php
     schreibt sie beim Karten-Hintergrundvideo aus. Dieselben Zahlen, damit
     nicht zwei Antworten auf dieselbe Frage im Panel stehen.
  */ ?>
  <ul class="mt-4 space-y-1 text-[0.72rem] leading-relaxed text-muted">
    <li><span class="uppercase tracking-[0.14em] text-gold"><?= $tr ? 'Oran' : 'Format' ?>:</span>
      <?= $tr ? 'dikey 9:16 veya 3:4 – örn. 1080 × 1920 ya da 1080 × 1440.'
              : 'hochkant 9:16 oder 3:4 – z. B. 1080 × 1920 bzw. 1080 × 1440.' ?></li>
    <li><span class="uppercase tracking-[0.14em] text-gold"><?= $tr ? 'Çözünürlük' : 'Auflösung' ?>:</span>
      <?= $tr ? 'en az 720 × 1280, tercihen 1080 × 1920.' : 'mindestens 720 × 1280, besser 1080 × 1920.' ?></li>
    <li><span class="uppercase tracking-[0.14em] text-gold"><?= $tr ? 'Süre' : 'Länge' ?>:</span>
      <?= $tr ? '4–10 s yeterli (döngü olur).' : '4–10 s reichen (läuft als Schleife).' ?></li>
    <li><span class="uppercase tracking-[0.14em] text-gold"><?= $tr ? 'İçerik' : 'Inhalt' ?>:</span>
      <?= $tr ? 'yavaş, sakin hareket. Yazı, logo veya insan yüzü olmamalı.'
              : 'ruhige, langsame Bewegung. Kein Text, kein Logo, keine Gesichter.' ?></li>
    <li><span class="uppercase tracking-[0.14em] text-gold"><?= $tr ? 'Boyut' : 'Größe' ?>:</span>
      <?= $tr ? 'en fazla 100 MB. Yüklemeden önce sıkıştır – sunucu yeniden kodlamaz.'
              : 'höchstens 100 MB. Vorher komprimieren – der Server transkodiert nicht.' ?></li>
  </ul>
<?= $zu ?>
```

- [ ] **Step 7: Yeni katman kutusuna tip seçici ekle**

`php/templates/admin/design-edit-sections.php`, „Yeni görsel katmanı" bloğundaki `neue_ebene_label` etiketinin **önüne** ekle:

```php
      <label class="<?= $label ?>"><?= $tr ? 'Ne' : 'Was' ?>
        <select name="neue_ebene_typ" class="<?= $feld ?>">
          <option value="photo"><?= $tr ? 'Görsel' : 'Bild' ?></option>
          <option value="video"><?= $tr ? 'Video' : 'Video' ?></option>
        </select></label>
```

Aynı bloktaki başlığı ve açıklamayı da genelleştir:

```php
    <div class="<?= $label ?>"><?= $tr ? 'Yeni görsel/video katmanı' : 'Neue Bild- oder Videoebene' ?></div>
```

- [ ] **Step 8: Yüklemeleri karşıla**

`php/src/Controllers/DesignAdminController.php`, `mitHochgeladenenBildern()` fonksiyonundaki `foreach` döngüsünün **başındaki** tip süzgecinin altına, `image`/`photo` dalından sonra bir `video` dalı gelecek şekilde gövdeyi şuna çevir:

```php
        foreach (Design::complete($design)['layers'] as $ebene) {
            $typ = (string) ($ebene['type'] ?? '');
            $id  = (string) $ebene['id'];

            if (in_array($typ, ['image', 'photo'], true)) {
                $file = $_FILES['bild_' . $id] ?? null;
                if (!is_array($file) || ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_OK) {
                    continue;
                }

                // storeGraphic und nicht store: die Vorlagen arbeiten mit SVG, und
                // getimagesize() erkennt SVG nicht - store() gaebe hier fuer jede
                // Zeichnung null zurueck. storeGraphic putzt das SVG und behaelt
                // bei allem anderen den Alphakanal, den eine Ebene ueber der Karte
                // braucht.
                $pfad = Media::storeGraphic($file, 'designs');
                if ($pfad !== null) {
                    $post['src_' . $id] = $pfad;
                }
                continue;
            }

            if ($typ === 'video') {
                $film = $_FILES['video_' . $id] ?? null;
                if (is_array($film) && ((int) ($film['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    // storeVideo prueft die Art am Dateiinhalt und laesst nur
                    // mp4/webm/mov durch. Kein Umkodieren - der Server kann es
                    // nicht, und die Vorgabe im Panel sagt das auch so.
                    $pfad = Media::storeVideo($film, 'designs');
                    if ($pfad !== null) {
                        $post['src_' . $id] = $pfad;
                    }
                }

                $bild = $_FILES['poster_' . $id] ?? null;
                if (is_array($bild) && ((int) ($bild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    // store und nicht storeGraphic: ein Standbild ist ein Foto,
                    // kein Schmuck - Transparenz braucht es nicht, und 1600 px
                    // reichen hinter einem Film allemal.
                    $pfad = Media::store($bild, 'designs');
                    if ($pfad !== null) {
                        $post['posterpfad_' . $id] = $pfad;
                    }
                }
            }
        }
```

> İmzalar doğrulandı: `Media::store(array $file, string $folder): ?string` (`src/Media.php:44`), `Media::storeVideo(array $file, string $folder): ?string` (`:275`), `Media::storeGraphic(array $file, string $folder): ?string` (`:326`), `Media::delete(string $url): void` (`:460`).

- [ ] **Step 9: Yeni video katmanının oluşmasını sağla**

`php/src/Controllers/DesignAdminController.php`, `mitNeuerBildebene()` içinde katmanın kurulduğu diziye (`'spot' => …` satırının bulunduğu blok) `type` alanını ekle; bugün orada sabit `photo` var ya da hiç yok:

```php
            'type'  => ($post['neue_ebene_typ'] ?? 'photo') === 'video' ? 'video' : 'photo',
```

Ve aynı fonksiyonun sonundaki başlangıç dosyası yüklemesini tipe göre ayır:

```php
        $start = $_FILES['neue_ebene_bild'] ?? null;
        if (is_array($start) && ((int) ($start['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = $ebene['type'] === 'video'
                ? Media::storeVideo($start, 'designs')
                : Media::storeGraphic($start, 'designs');
            if ($pfad !== null) {
                $ebene['src'] = $pfad;
            }
        }
```

Dosya seçicinin `accept` listesini de genişlet (`design-edit-sections.php`):

```php
        <input type="file" name="neue_ebene_bild"
               accept="image/png,image/jpeg,image/webp,image/svg+xml,video/mp4,video/webm" class="<?= $feld ?>">
```

- [ ] **Step 10: Tüm testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS.

- [ ] **Step 11: Elle doğrula**

1. Panelde bir tasarım aç → „5b · Videolar" bölümü görünüyor, katman yoksa açıklama yazıyor.
2. „Yeni görsel/video katmanı" → tip `Video`, ad „Arka plan filmi", yer `Sayfada`, bir mp4 seç → Kaydet.
3. Sayfa yenilenince katman „5b · Videolar" altında, önizleme karesi poster/ilk kare gösteriyor.
4. Aynı katmana bir jpg poster yükle → Kaydet → poster yolu alanı doldu.
5. Poster yolu alanını **elle boşalt** → Kaydet → poster kalktı (boşaltma çalışıyor).

- [ ] **Step 12: Commit**

```bash
git add php/src/Design.php php/tests/design_admin.php php/templates/admin/design-edit.php php/templates/admin/design-edit-sections.php php/src/Controllers/DesignAdminController.php
git commit -m "$(cat <<'EOF'
Der Editor legt jetzt auch Filme an, nicht nur Bilder

Die Videoebene hat zwei Adressen - den Film und sein Standbild -, deshalb
ein eigenes Formularfeld je Adresse. posterpfad_ darf ausdruecklich leer
abgeschickt werden: einen Poster zu entfernen ist ein Wunsch, eine leere
src waere ein Unfall.

Die Vorgaben (9:16, mindestens 720x1280, 4-10 s, kein Text im Bild) stehen
schon beim Karten-Hintergrundvideo im Themenreiter. Dieselben Zahlen, damit
das Panel nicht zwei Antworten auf eine Frage gibt.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Video kitaplığı

**Files:**
- Create: `php/src/DesignVideos.php`
- Create: `php/tests/design_videos.php`
- Modify: `php/templates/admin/designs.php`
- Modify: `php/src/Controllers/DesignAdminController.php`

**Interfaces:**
- Consumes: `Content::all()` / `Content::mutate()`, `Media::storeVideo()`, `Media::store()`, `Design::safeSrc()`
- Produces:
  - `DesignVideos::complete(array $rows): list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}>` — saf, veritabanısız
  - `DesignVideos::all(): list<…>` — `site_content` JSON'undaki `designVideos` anahtarını okur
  - `DesignVideos::save(list<…> $rows): void`
  - Görev 4 `DesignVideos::all()`'u sihirbazda kullanır.

- [ ] **Step 1: Şema testini yaz (başarısız olacak)**

`php/tests/design_videos.php` **oluştur**:

```php
<?php
declare(strict_types=1);

use Atelier\DesignVideos;

/* --- Vollstaendig machen: fremde Adressen fallen, Kennungen entstehen --- */

$rows = DesignVideos::complete([
    ['id' => 'schwaene', 'label' => 'Schwäne', 'mp4' => '/uploads/videos/a.mp4',
     'webm' => '/uploads/videos/a.webm', 'poster' => '/uploads/videos/a.jpg', 'category' => 'floral'],
    ['label' => 'Ohne Kennung', 'mp4' => '/uploads/videos/b.mp4'],
    ['label' => 'Fremd', 'mp4' => 'https://beispiel.de/c.mp4'],
    ['label' => 'Ohne Film', 'poster' => '/uploads/videos/d.jpg'],
    'kein Array',
]);

assert_same(2, count($rows), 'videos: ohne gueltigen Film faellt der Eintrag weg');
assert_same('schwaene', $rows[0]['id'], 'videos: Kennung bleibt');
assert_same('/uploads/videos/a.webm', $rows[0]['webm'], 'videos: webm kommt durch');
assert_true($rows[1]['id'] !== '', 'videos: fehlende Kennung wird erzeugt');
assert_same('', $rows[1]['webm'], 'videos: ohne webm bleibt das Feld leer');

/* --- Unbekannte Kategorie faellt auf leer, nicht auf Unsinn --- */

$k = DesignVideos::complete([
    ['id' => 'a', 'mp4' => '/uploads/videos/a.mp4', 'category' => 'gibtesnicht'],
    ['id' => 'b', 'mp4' => '/uploads/videos/b.mp4', 'category' => 'floral'],
]);

assert_same('', $k[0]['category'], 'videos: unbekannte Kategorie wird leer');
assert_same('floral', $k[1]['category'], 'videos: bekannte Kategorie bleibt');

/* --- Kennungen sind eindeutig: zwei gleiche waeren im Formular ein Ort --- */

$doppelt = DesignVideos::complete([
    ['id' => 'a', 'mp4' => '/uploads/videos/1.mp4'],
    ['id' => 'a', 'mp4' => '/uploads/videos/2.mp4'],
]);

assert_true($doppelt[0]['id'] !== $doppelt[1]['id'], 'videos: doppelte Kennung wird aufgeloest');
```

- [ ] **Step 2: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_videos
```

Beklenen: fatal — `Class "Atelier\DesignVideos" not found`.

- [ ] **Step 3: Sınıfı yaz**

`php/src/DesignVideos.php` **oluştur**:

```php
<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Die Filmbibliothek der Vorlagen.
 *
 * Warum eine Bibliothek und nicht ein Feld je Vorlage: der Kunde hat gesagt,
 * das Paar findet selbst keinen Hintergrundfilm. Also stellen wir welche hin.
 * Eine Vorlage bringt ihren Standardfilm mit (die src ihrer Videoebene); die
 * Bibliothek ist das, WORAUS das Paar tauschen darf, wenn die Ebene das Recht
 * `photo` traegt.
 *
 * Gespeichert wird im JSON von site_content unter `designVideos` - kein neuer
 * Tabellenname fuer eine Liste, die selten waechst und nie einzeln abgefragt
 * wird.
 *
 * complete() ist rein: keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php.
 */
final class DesignVideos
{
    /** Wo die Liste im Dokument steht. */
    public const KEY = 'designVideos';

    /** Mehr braucht niemand, und eine Auswahl von hundert waere keine mehr. */
    public const MAX = 40;

    /**
     * Die Liste, sauber.
     *
     * Ein Eintrag ohne gueltigen mp4-Pfad faellt weg: er waere im Assistenten
     * ein Name, hinter dem nichts kommt. webm und poster duerfen fehlen.
     *
     * @param array<mixed> $rows
     * @return list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}>
     */
    public static function complete(array $rows): array
    {
        $out = [];
        $gesehen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mp4 = Design::safeSrc((string) ($row['mp4'] ?? ''));
            if ($mp4 === '') {
                continue;
            }

            // Ohne Kennung waere der Eintrag im Formular nicht adressierbar;
            // zweimal dieselbe waere ein Ort fuer zwei Filme.
            $id = Design::key((string) ($row['id'] ?? ''));
            if ($id === '' || isset($gesehen[$id])) {
                $id = bin2hex(random_bytes(4));
            }
            $gesehen[$id] = true;

            $kategorie = Design::key((string) ($row['category'] ?? ''));

            $out[] = [
                'id'       => $id,
                'label'    => Security::clean((string) ($row['label'] ?? ''), 80),
                'mp4'      => $mp4,
                'webm'     => Design::safeSrc((string) ($row['webm'] ?? '')),
                'poster'   => Design::safeSrc((string) ($row['poster'] ?? '')),
                'category' => in_array($kategorie, Design::CATEGORIES, true) ? $kategorie : '',
            ];

            if (count($out) >= self::MAX) {
                break;
            }
        }

        return $out;
    }

    /**
     * Die gespeicherte Liste.
     *
     * @return list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}>
     */
    public static function all(): array
    {
        $roh = Content::all()[self::KEY] ?? [];

        return self::complete(is_array($roh) ? $roh : []);
    }

    /**
     * Einen Eintrag nachschlagen.
     *
     * @return array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /** @param array<mixed> $rows */
    public static function save(array $rows): void
    {
        $sauber = self::complete($rows);

        Content::mutate(static function (array $daten) use ($sauber): array {
            $daten[self::KEY] = $sauber;

            return $daten;
        });
    }
}
```

- [ ] **Step 4: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php design_videos
```

Beklenen: PASS.

- [ ] **Step 5: Panel bloğunu ekle**

`php/templates/admin/designs.php` dosyasının **sonuna**, katalog listesinden sonra ekle. `$videos`, `$csrf`, `$tr` değişkenlerinin şablona geldiğinden emin ol (Step 6 controller'da veriyor):

```php
<?php /*
   Die Filmbibliothek. Sie gehoert hierher und nicht in einen eigenen Reiter:
   sie ist Zubehoer der Vorlagen, und ein siebzehnter Reiter fuer eine Liste
   mit einer Handvoll Eintraegen waere genau die Ueberdetaillierung, ueber die
   sich der Kunde beschwert hat.
*/ ?>
<section class="mt-16 border-t border-sand-deep pt-10">
  <h3 class="font-display text-lg text-ink"><?= $tr ? 'Video kitaplığı' : 'Filmbibliothek' ?></h3>
  <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
    <?= $tr
      ? 'Buradaki videolar, izni açık olan video katmanlarında çifte seçenek olarak çıkar. Çift kendi videosunu yükleyemez — bulamaz.'
      : 'Diese Filme bietet der Assistent dem Paar an, wenn die Videoebene das Recht dazu traegt. Eigene Dateien kann das Paar nicht laden - es findet keine.' ?>
  </p>

  <form method="post" enctype="multipart/form-data" class="mt-6 space-y-6">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <?php foreach ($videos as $i => $film) : ?>
      <div class="flex flex-wrap items-start gap-5 border-t border-sand-deep pt-5 first:border-0 first:pt-0">
        <input type="hidden" name="vid_id_<?= (int) $i ?>" value="<?= e($film['id']) ?>">
        <input type="hidden" name="vid_mp4_<?= (int) $i ?>" value="<?= e($film['mp4']) ?>">
        <input type="hidden" name="vid_webm_<?= (int) $i ?>" value="<?= e($film['webm']) ?>">
        <input type="hidden" name="vid_poster_<?= (int) $i ?>" value="<?= e($film['poster']) ?>">

        <video src="<?= e($film['mp4']) ?>" muted preload="metadata"
               <?= $film['poster'] !== '' ? 'poster="' . e($film['poster']) . '"' : '' ?>
               class="h-20 w-14 shrink-0 bg-ink object-cover"></video>

        <label class="<?= $tr ? '' : '' ?> min-w-[14rem] flex-1 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
          <?= $tr ? 'Adı' : 'Name' ?>
          <input name="vid_label_<?= (int) $i ?>" value="<?= e($film['label']) ?>" maxlength="80"
                 class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink"></label>

        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">
          <?= $tr ? 'Kategori' : 'Kategorie' ?>
          <select name="vid_cat_<?= (int) $i ?>"
                  class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink">
            <option value=""><?= $tr ? '—' : '—' ?></option>
            <?php foreach (Design::CATEGORIES as $k) : ?>
              <option value="<?= e($k) ?>" <?= $film['category'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
            <?php endforeach; ?>
          </select></label>

        <button name="was" value="video-loeschen-<?= e($film['id']) ?>"
                class="self-end pb-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-red-700"
                data-confirm="<?= $tr ? 'Bu video silinsin mi?' : 'Diesen Film entfernen?' ?>">
          <?= $tr ? 'Sil' : 'Entfernen' ?>
        </button>
      </div>
    <?php endforeach; ?>

    <div class="border-t border-sand-deep pt-5">
      <div class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Yeni video' : 'Neuer Film' ?></div>
      <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Adı' : 'Name' ?>
          <input name="vid_neu_label" maxlength="80"
                 class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink"></label>
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">mp4 / webm
          <input type="file" name="vid_neu_datei" accept="video/mp4,video/webm,video/quicktime"
                 class="mt-1 w-full text-[0.8rem] text-muted"></label>
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Kapak görseli' : 'Standbild' ?>
          <input type="file" name="vid_neu_poster" accept="image/png,image/jpeg,image/webp"
                 class="mt-1 w-full text-[0.8rem] text-muted"></label>
      </div>
    </div>

    <button name="was" value="videos-kaydet"
            class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $tr ? 'Kitaplığı kaydet' : 'Bibliothek speichern' ?>
    </button>
  </form>
</section>
```

- [ ] **Step 6: Controller'a bağla**

`php/src/Controllers/DesignAdminController.php`:

Katalog görünümünü basan metoda `'videos' => DesignVideos::all(),` ve `'csrf' => Security::csrf(),` değişkenlerini ekle (`Security::csrf(): string` — `src/Security.php:33`; doğrulama `Security::checkCsrf(?string): bool` — `:42`).

`was` dallanmasının olduğu yere iki dal ekle:

```php
        if ($was === 'videos-kaydet') {
            $this->videosSpeichern($locale);
            return;
        }
        if (str_starts_with($was, 'video-loeschen-')) {
            $this->videoLoeschen($locale, substr($was, strlen('video-loeschen-')));
            return;
        }
```

Ve iki metot:

```php
    /**
     * Die Bibliothek speichern - bestehende Zeilen und hoechstens einen neuen
     * Film. Ein Upload je Absenden reicht: mehrere gleichzeitig waeren bei
     * 100 MB je Datei ein Zeitlimit, kein Komfort.
     */
    private function videosSpeichern(string $locale): void
    {
        $ziel = I18n::path('/admin/designs', $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        $rows = [];
        for ($i = 0; $i < DesignVideos::MAX; $i++) {
            if (!isset($_POST['vid_id_' . $i])) {
                continue;
            }
            $rows[] = [
                'id'       => (string) $_POST['vid_id_' . $i],
                'label'    => (string) ($_POST['vid_label_' . $i] ?? ''),
                'mp4'      => (string) ($_POST['vid_mp4_' . $i] ?? ''),
                'webm'     => (string) ($_POST['vid_webm_' . $i] ?? ''),
                'poster'   => (string) ($_POST['vid_poster_' . $i] ?? ''),
                'category' => (string) ($_POST['vid_cat_' . $i] ?? ''),
            ];
        }

        $datei = $_FILES['vid_neu_datei'] ?? null;
        if (is_array($datei) && ((int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::storeVideo($datei, 'videos');
            if ($pfad !== null) {
                $poster = '';
                $bild = $_FILES['vid_neu_poster'] ?? null;
                if (is_array($bild) && ((int) ($bild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    $poster = (string) Media::store($bild, 'videos');
                }
                $rows[] = [
                    'id'       => '',
                    'label'    => (string) ($_POST['vid_neu_label'] ?? ''),
                    'mp4'      => $pfad,
                    'webm'     => '',
                    'poster'   => $poster,
                    'category' => '',
                ];
            }
        }

        DesignVideos::save($rows);

        header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
        exit;
    }

    /**
     * Einen Film aus der Bibliothek nehmen.
     *
     * Die Datei bleibt liegen. Dieselbe Ueberlegung wie bei den Bildebenen:
     * eine bereits versendete Einladung zeigt auf sie, und was fehlt, kostet
     * eine Einladung - was liegen bleibt, kostet Platz.
     */
    private function videoLoeschen(string $locale, string $id): void
    {
        $ziel = I18n::path('/admin/designs', $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        DesignVideos::save(array_values(array_filter(
            DesignVideos::all(),
            static fn (array $row): bool => $row['id'] !== $id
        )));

        header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
        exit;
    }
```

Dosyanın başındaki `use` bloğuna `use Atelier\DesignVideos;` ekle.

- [ ] **Step 7: Testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS.

- [ ] **Step 8: Elle doğrula**

1. `/de/admin/designs` → altta „Filmbibliothek" bölümü.
2. Bir mp4 + jpg yükle, ad ver → Kaydet → liste bir satır büyüdü, önizleme oynuyor.
3. Adı ve kategoriyi değiştir → Kaydet → değişti.
4. „Sil" → satır gitti, `public/uploads/videos/` altındaki dosya **duruyor** (bilerek).
5. CSRF alanını tarayıcı konsolundan boşaltıp gönder → `?fehler=csrf` ile geri döndü, liste değişmedi.

- [ ] **Step 9: Commit**

```bash
git add php/src/DesignVideos.php php/tests/design_videos.php php/templates/admin/designs.php php/src/Controllers/DesignAdminController.php
git commit -m "$(cat <<'EOF'
Die Filme stehen jetzt in einem Regal, aus dem das Paar waehlen darf

Der Kunde: "Muesteri arkaya zor bulur." Also findet er nicht selbst, sondern
nimmt eines von unseren. Die Liste liegt im JSON von site_content unter
designVideos - keine neue Tabelle fuer eine Liste, die selten waechst.

Kein eigener Reiter: sie ist Zubehoer der Vorlagen und steht unter dem
Katalog. Ein siebzehnter Reiter waere genau die Ueberdetaillierung, ueber
die sich der Kunde beschwert hat.

Geloescht wird der Eintrag, nicht die Datei: eine versendete Einladung
zeigt womoeglich darauf.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Çift kitaplıktan seçer

**Files:**
- Modify: `php/src/DesignWizard.php` — `choices()`, `'photo'` hakkı (91. satır civarı)
- Test: `php/tests/design_wizard.php`
- Modify: `php/templates/pages/invite-v2-wizard.php`
- Modify: `php/src/Controllers/InviteV2Controller.php`

**Interfaces:**
- Consumes: `DesignVideos::all()`, `DesignVideos::find()`, Görev 1'in `video` tipi
- Produces: Sihirbaz form alanı `film_<layerId>` (kitaplık kimliği). Controller onu `src` + `poster` olarak belgeye yazar.

- [ ] **Step 1: Testi yaz (başarısız olacak)**

`php/tests/design_wizard.php` dosyasının **sonuna** ekle:

```php
/* --- Eine Videoebene mit photo-Recht wird angeboten --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/a.mp4',
     'permissions' => ['edit' => true, 'photo' => true]],
]];

$wahl = DesignWizard::choices($doc);

assert_true(isset($wahl['layers']['film']), 'wizard: Videoebene wird angeboten');
assert_true($wahl['layers']['film']['photo'], 'wizard: und zwar mit dem photo-Recht');

/* --- Ohne edit bleibt sie gesperrt, wie jede andere Ebene --- */

$zu = DesignWizard::choices(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'permissions' => ['edit' => false, 'photo' => true]],
]]);

assert_true(!isset($zu['layers']['film']), 'wizard: ohne edit wird nichts angeboten');
```

Dosyanın başında `use Atelier\DesignWizard;` yoksa ekle.

- [ ] **Step 2: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php design_wizard
```

Beklenen: `wizard: Videoebene wird angeboten` başarısız.

- [ ] **Step 3: Hakkı bağla**

`php/src/DesignWizard.php`, `choices()` içinde:

```php
                'photo' => $p['photo'] && in_array($el['type'], ['image', 'photo'], true),
```

**şununla değiştir:**

```php
                // video haengt am selben Recht wie photo: es ist dieselbe
                // Frage - "darf das Paar den Inhalt dieser Flaeche tauschen".
                // Ein siebtes Recht waere ein zweiter Name fuer eine Sache.
                'photo' => $p['photo'] && in_array($el['type'], ['image', 'photo', 'video'], true),
```

- [ ] **Step 4: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php design_wizard
```

Beklenen: PASS.

- [ ] **Step 5: Sihirbaza seçim alanını koy**

`php/templates/pages/invite-v2-wizard.php` içinde, `photo` hakkı olan katmanlar için dosya yükleme alanının basıldığı yeri bul (`grep -n "photo" php/templates/pages/invite-v2-wizard.php`). Orada tipe göre ayır — video katmanı **yükleme değil, seçim** gösterir:

```php
<?php if (($ebene['type'] ?? '') === 'video') : ?>
  <?php /*
     Auswahl statt Upload. Nicht aus Bequemlichkeit: ein Paar findet keinen
     Hintergrundfilm im richtigen Format, und was es faende, waere 200 MB
     Querformat mit Text im Bild.
  */ ?>
  <label class="block text-[0.66rem] uppercase tracking-[0.16em] text-muted">
    <?= e($ebene['label'] ?: ($de ? 'Hintergrundfilm' : 'Background film')) ?>
    <select name="film_<?= e($ebene['id']) ?>"
            class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink">
      <option value=""><?= $de ? 'Wie in der Vorlage' : 'As in the template' ?></option>
      <?php foreach ($filme as $film) : ?>
        <option value="<?= e($film['id']) ?>"><?= e($film['label'] ?: $film['id']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
<?php else : ?>
  … (mevcut yükleme alanı olduğu gibi kalır) …
<?php endif; ?>
```

`$filme` değişkenini şablona veren yerde (`InviteV2Controller`'ın sihirbazı basan metodu) ekle:

```php
            'filme' => DesignVideos::all(),
```

- [ ] **Step 6: Seçimi belgeye yaz**

`php/src/Controllers/InviteV2Controller.php`, çiftin seçimlerinin belgeye işlendiği yerde (`personalize` çağrısının hemen öncesi; `grep -n "personalize" php/src/Controllers/InviteV2Controller.php` ile bul) ekle:

```php
        /*
         * Der gewaehlte Film. Nicht der Pfad kommt aus dem Formular, sondern
         * die Kennung - so kann niemand eine fremde Adresse einschleusen, und
         * safeSrc muss hier gar nicht erst greifen. Wer nichts waehlt,
         * behaelt den Film der Vorlage.
         */
        foreach ($doc['layers'] as $i => $ebene) {
            if (($ebene['type'] ?? '') !== 'video') {
                continue;
            }
            $kennung = Security::clean($_POST['film_' . $ebene['id']] ?? '', 64);
            if ($kennung === '') {
                continue;
            }
            $film = DesignVideos::find($kennung);
            if ($film === null) {
                continue;
            }
            $doc['layers'][$i]['src']    = $film['mp4'];
            $doc['layers'][$i]['poster'] = $film['poster'];
        }
```

Dosyanın `use` bloğuna `use Atelier\DesignVideos;` ekle.

- [ ] **Step 7: Testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS.

- [ ] **Step 8: Elle doğrula**

1. Panelde bir video katmanının `edit` + `photo` haklarını aç, kaydet.
2. `/de/v2/…` sihirbazını aç → o adımda **yükleme kutusu değil, açılır liste** var, içinde kitaplıktaki filmler.
3. Bir film seç, davetiyeyi oluştur → davetiye sayfasında o film oynuyor.
4. Hiçbir şey seçmeden oluştur → şablonun kendi filmi oynuyor.
5. Tarayıcı konsolundan `film_<id>` değerini uydurma bir kimlikle gönder → şablonun filmi kaldı, hata yok.

- [ ] **Step 9: Commit**

```bash
git add php/src/DesignWizard.php php/tests/design_wizard.php php/templates/pages/invite-v2-wizard.php php/src/Controllers/InviteV2Controller.php
git commit -m "$(cat <<'EOF'
Das Paar waehlt seinen Film aus dem Regal, es laedt keinen hoch

Die Videoebene haengt am photo-Recht und nicht an einem siebten: es ist
dieselbe Frage - darf das Paar den Inhalt dieser Flaeche tauschen. Ein
zweiter Name fuer eine Sache waere nur ein zweiter Ort zum Vergessen.

Aus dem Formular kommt die Kennung, nicht der Pfad. Damit kann keine fremde
Adresse hereinkommen, und die unbekannte Kennung faellt still auf den Film
der Vorlage zurueck.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Açılış videosu — temanın alanı

**Files:**
- Modify: `php/src/Themes.php` — varsayılanlar (~421)
- Modify: `php/templates/admin/themes.php` — yükleme kutusu (backdropVideo bloğunun yanına)
- Modify: `php/src/Controllers/AdminController.php` — yükleme/silme (~221, ~318)
- Modify: `php/templates/partials/design-stage.php` — açılış düğümü

**Interfaces:**
- Consumes: `Media::storeVideo()`, `Media::store()`
- Produces: Sahnede `<div data-intro-video>` içinde bir `<video data-intro-film>` düğümü. Görev 6 bu iki öznitelikle çalışır.

- [ ] **Step 1: Tema alanlarını ekle**

`php/src/Themes.php`, varsayılanlar dizisinde `'backdropPoster'  => '',` satırının **altına**:

```php
            // Der Vorspann: ein Film vom echten Kuvert, statt der gezeichneten
            // Klappe. Leer = das bisherige CSS-Kuvert. Getrennt von backdrop*,
            // weil das eine VOR der Karte laeuft und das andere DAHINTER.
            'introVideo'      => '',
            'introPoster'     => '',
```

- [ ] **Step 2: Panelde yükleme kutusu**

`php/templates/admin/themes.php`, `backdropPoster` bloğunun **hemen ardından** ekle:

```php
              <div class="md:col-span-2 border-t border-sand-deep pt-6">
                <label class="<?= $label ?>"><?= $de ? 'Öffnungsfilm (Kuvert)' : 'Açılış videosu (zarf)' ?></label>
                <p class="<?= $hint ?>">
                  <?= $de
                      ? 'Ein Film vom echten Kuvert, der laeuft, bevor die Karte kommt. Ohne Film bleibt die bisherige gezeichnete Klappe - nichts geht kaputt.'
                      : 'Kart gelmeden önce oynayan gerçek zarf videosu. Video yoksa bugünkü çizilmiş zarf çalışır — hiçbir şey bozulmaz.' ?>
                </p>
                <ul class="mt-2 space-y-1 text-[0.72rem] leading-relaxed text-muted">
                  <li><span class="uppercase tracking-[0.14em] text-gold"><?= $de ? 'Format' : 'Oran' ?>:</span>
                    <?= $de ? 'hochkant 9:16, 1080 × 1920.' : 'dikey 9:16, 1080 × 1920.' ?></li>
                  <li><span class="uppercase tracking-[0.14em] text-gold"><?= $de ? 'Länge' : 'Süre' ?>:</span>
                    <?= $de ? '2–5 s. Laenger wartet der Gast vor einer geschlossenen Karte.'
                            : '2–5 s. Daha uzunu misafiri kapalı kartın önünde bekletir.' ?></li>
                  <li><span class="uppercase tracking-[0.14em] text-gold"><?= $de ? 'Ende' : 'Bitiş' ?>:</span>
                    <?= $de ? 'auf dem geoeffneten Kuvert stehen bleiben - der Schnitt zur Karte kommt danach.'
                            : 'açılmış zarfın üzerinde bitmeli — karta geçiş ondan sonra olur.' ?></li>
                </ul>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                  <?php if ((string) ($theme['introVideo'] ?? '') !== '') : ?>
                    <video src="<?= e((string) $theme['introVideo']) ?>" muted preload="metadata"
                           class="h-16 w-24 bg-ink object-cover"></video>
                    <button name="was" value="intro-delete" class="text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-red-700"
                            data-confirm="<?= $de ? 'Öffnungsfilm entfernen?' : 'Açılış videosu kaldırılsın mı?' ?>">
                      <?= $de ? 'Film entfernen' : 'Videoyu kaldır' ?>
                    </button>
                  <?php endif; ?>
                </div>
                <input type="file" name="introVideo" accept="video/mp4,video/webm,video/quicktime" class="mt-3 w-full text-[0.8rem] text-muted">
                <input type="file" name="introPoster" accept="image/png,image/jpeg,image/webp" class="mt-3 w-full text-[0.8rem] text-muted">
              </div>
```

- [ ] **Step 3: Controller'da karşıla**

`php/src/Controllers/AdminController.php`:

`'backdrop-delete' => $this->deleteThemeImage('backdropVideo'),` satırının altına:

```php
                'intro-delete'    => $this->deleteThemeImage('introVideo'),
```

Ve `backdropVideo` yüklemesinin yapıldığı bloğun (~318-330) altına, aynı kalıpla:

```php
            // Der Vorspann. Dieselbe Pruefung wie beim Hintergrundvideo -
            // storeVideo liest die Art aus dem Dateiinhalt, nicht aus dem
            // Namen, und laesst nur mp4/webm/mov durch.
            $introFile = $_FILES['introVideo'] ?? null;
            if (is_array($introFile) && ((int) ($introFile['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                $introUrl = Media::storeVideo($introFile, 'themen/' . $id);
                if ($introUrl !== null) {
                    if ((string) ($theme['introVideo'] ?? '') !== '') {
                        Media::delete((string) $theme['introVideo']);
                    }
                    $next['introVideo'] = $introUrl;
                }
            }

            $introBild = $_FILES['introPoster'] ?? null;
            if (is_array($introBild) && ((int) ($introBild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                $introBildUrl = Media::store($introBild, 'themen/' . $id);
                if ($introBildUrl !== null) {
                    $next['introPoster'] = $introBildUrl;
                }
            }
```

- [ ] **Step 4: Sahneye düğümü koy**

`php/templates/partials/design-stage.php`, zarfın (`<div class="d-envelope …" data-envelope …>`) **hemen öncesine** ekle. `$introVideo` ve `$introPoster` değişkenlerini sahneyi çağıran iki yerden (`DesignController`, `InviteV2Controller`) geçir; yoksa boş dizi varsayımıyla:

```php
<?php
  $introFilm   = Design::safeSrc((string) ($introVideo ?? ''));
  $introBild   = Design::safeSrc((string) ($introPoster ?? ''));
?>
<?php if ($introFilm !== '') : ?>
  <?php /*
     Der Vorspann liegt UEBER dem Kuvert (z-40 gegen z-30) und verschwindet,
     wenn er durch ist. Kein autoplay, wie bei den Ebenen: invitation.js
     startet ihn beim Klick auf das Kuvert und blendet ihn danach aus. Ohne
     Skript sieht der Gast ihn nie und bekommt sofort die Karte - das ist die
     richtige Reihenfolge, nicht ein Fehler.
  */ ?>
  <div class="absolute inset-0 z-40" data-intro-video hidden>
    <video class="h-full w-full object-cover" data-intro-film
           src="<?= e($introFilm) ?>"
           <?= $introBild !== '' ? 'poster="' . e($introBild) . '"' : '' ?>
           muted playsinline preload="auto"></video>
  </div>
<?php endif; ?>
```

- [ ] **Step 5: Testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS (bu görev saf sınıflara dokunmuyor, gerileme kontrolü).

- [ ] **Step 6: Elle doğrula**

1. `/de/admin/themen` → bir tema aç → „Öffnungsfilm (Kuvert)" kutusu var.
2. Bir mp4 yükle → Kaydet → küçük önizleme çıktı.
3. O temayı kullanan bir v2 davetiyesini aç → sayfa kaynağında `data-intro-video` düğümü var, `hidden` duruyor (Görev 6 onu açacak).
4. Filmi kaldır → düğüm kayboldu, zarf eskisi gibi.

- [ ] **Step 7: Commit**

```bash
git add php/src/Themes.php php/templates/admin/themes.php php/src/Controllers/AdminController.php php/templates/partials/design-stage.php
git commit -m "$(cat <<'EOF'
Das Kuvert darf jetzt ein Film sein, wenn das Thema einen mitbringt

Beide Referenzen des Kunden oeffnen ihr Kuvert mit einem MP4 von echtem
Papier - herzsiegel envelope-tuscany.mp4, royal intro-video-new. Unseres ist
gezeichnet. Also: bringt das Thema einen Film mit, laeuft er; bringt es
keinen mit, bleibt die bisherige Klappe. Kein Thema geht kaputt.

introVideo und backdropVideo sind getrennt, weil das eine VOR der Karte
laeuft und das andere DAHINTER.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Oynatmayı `invitation.js` başlatır

**Files:**
- Modify: `php/public/assets/invitation.js` — `reveal()` (21-100)

**Interfaces:**
- Consumes: Görev 1'in `<video>` düğümleri, Görev 5'in `[data-intro-video]` / `[data-intro-film]`
- Produces: Davranış. Sonraki görev yok.

- [ ] **Step 1: Açılış videosunu ve katman videolarını başlat**

`php/public/assets/invitation.js`, `reveal()` fonksiyonunun içinde,

```php
      var intro = document.querySelector("[data-intro]");
      var introMs = Number(envelope.getAttribute("data-intro-ms")) || 0;
```

satırlarının **altına** ekle:

```js
      // Der Filmvorspann des Themas. Er ersetzt die gezeichnete Szene, wenn
      // das Thema einen mitbringt - und er sagt selbst, wie lange er dauert,
      // statt dass wir eine Zahl raten.
      var introBox = document.querySelector("[data-intro-video]");
      var introFilm = introBox && introBox.querySelector("[data-intro-film]");
```

Ve `var still = …` satırının **altına**, `if (!intro || still) introMs = 0;` satırının **önüne**:

```js
      // Der Vorspann laeuft nur, wenn Bewegung erwuenscht ist. Wer sie
      // abbestellt hat, bekommt sofort die Karte.
      if (introFilm && !still) {
        introBox.hidden = false;

        // data-intro-ms bleibt die Obergrenze. Laedt der Film nicht - schlechtes
        // Netz, Format vom Server nicht ausgeliefert -, haengt die Einladung
        // sonst vor einem schwarzen Kasten fest.
        var deckel = introMs > 0 ? introMs : 6000;
        var fertig = false;
        var schliessen = function () {
          if (fertig) return;
          fertig = true;
          introBox.hidden = true;
        };

        introFilm.addEventListener("ended", schliessen, { once: true });
        introFilm.addEventListener("error", schliessen, { once: true });
        setTimeout(schliessen, deckel);

        introFilm.play().catch(schliessen);

        // Die Karte wartet, bis der Film durch ist - hoechstens aber deckel.
        introMs = deckel;
      }
```

- [ ] **Step 2: Katman videolarını kart açılınca başlat**

Aynı fonksiyonda, `setTimeout(startReveals, introMs + 1800);` satırının **önüne** ekle:

```js
      // Die Filme der Ebenen. Sie tragen kein autoplay - sonst liefen sie
      // hinter dem geschlossenen Kuvert, unsichtbar und im Mobilfunk bezahlt.
      // Wer Bewegung abbestellt hat, sieht das Standbild und sonst nichts.
      if (!still) {
        setTimeout(function () {
          var filme = document.querySelectorAll("video.d-el");
          for (var i = 0; i < filme.length; i++) {
            filme[i].play().catch(function () {});
          }
        }, introMs);
      }
```

- [ ] **Step 3: Zarfsız durumu da karşıla**

Dosyanın sonundaki `} else {` dalı (zarf yok, örn. paneldeki önizleme) şu anda yalnızca `startReveals()` çağırıyor. Onu şuna çevir:

```js
  } else {
    // Keine Huelle (z. B. Vorschau im Panel): dann gleich losbewegen.
    var ruhig = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!ruhig) {
      var vorschau = document.querySelectorAll("video.d-el");
      for (var v = 0; v < vorschau.length; v++) {
        vorschau[v].play().catch(function () {});
      }
    }
    startReveals();
  }
```

- [ ] **Step 4: Elle doğrula**

Bu dosyanın testi yok — `bin/test.php` PHP koşar, JS koşmaz. Tarayıcıda doğrula:

1. Video katmanı olan bir v2 davetiyesini aç → **zarf kapalıyken** DevTools › Network'te mp4 için `Range` isteği **yok** (yalnızca `preload="metadata"` kadar).
2. Zarfa tıkla → kart açılınca film oynamaya başlıyor.
3. Açılış videosu olan bir temada: tıklayınca önce film oynuyor, bitince kart geliyor.
4. Açılış videosunun `src`'sini DevTools'tan bozuk bir yola çevir, sayfayı yenile, tıkla → `error` yakalanıyor, kart yine de geliyor (kilitlenme yok).
5. DevTools › Rendering › „Emulate prefers-reduced-motion: reduce" → tıkla → hiç video oynamıyor, poster duruyor, kart geliyor.
6. Paneldeki tasarım önizlemesi (zarfsız) → film oynuyor.

- [ ] **Step 5: Commit**

```bash
git add php/public/assets/invitation.js
git commit -m "$(cat <<'EOF'
Erst wenn das Kuvert offen ist, faengt etwas an sich zu bewegen

Die Videoebenen tragen kein autoplay. Ohne diese Zeilen stuenden sie still -
mit autoplay liefen sie hinter dem geschlossenen Kuvert, unsichtbar und im
Mobilfunk bezahlt. Also startet sie das Skript, wenn die Karte frei liegt.

Der Vorspann sagt selbst, wann er fertig ist ("ended"), statt dass wir eine
Zahl raten. data-intro-ms bleibt die Obergrenze: laedt der Film nicht,
haengt der Gast sonst vor einem schwarzen Kasten fest.

Bei prefers-reduced-motion laeuft nichts. Nicht langsamer - gar nicht.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 7: Hareket sözlüğünü buda

**Files:**
- Modify: `php/src/Themes.php` — `IDLES` (93), `NAME_ANIMATIONS` (134), `PARTICLES` (137), `REVEALS` (140), `MOVES` (578)
- Create: `php/tests/themes_motion.php`

**Interfaces:**
- Consumes: `Themes::complete()`'in doğrulama döngüsü (467)
- Produces: Küçülmüş sabit listeler. `INTROS` **dokunulmaz** — o v1'e ait.

- [ ] **Step 1: Testi yaz (başarısız olacak)**

`php/tests/themes_motion.php` **oluştur**:

```php
<?php
declare(strict_types=1);

use Atelier\Themes;

/*
 * Der Kunde: "Cok animasyon. Cizgifilm gibi. Esas olmasi daha elegant ve
 * romantik." Also weniger Auswahl, nicht kleinere Zahlen - eine Liste mit
 * acht Eintraegen laedt nicht dazu ein, sieben davon anzuschalten.
 */

/* --- Teilchen sind ganz weg --- */

assert_same(['none'], Themes::PARTICLES, 'motion: keine Teilchen mehr');

/* --- Von den uebrigen bleibt je eine Bewegung und die Ruhe --- */

assert_same(['breathe', 'none'], Themes::IDLES, 'motion: nur das Atmen bleibt');
assert_same(['fade', 'none'], Themes::NAME_ANIMATIONS, 'motion: Namen blenden ein, mehr nicht');
assert_same(['up', 'none'], Themes::REVEALS, 'motion: eine Richtung reicht');
assert_same(['none', 'fade'], Themes::MOVES, 'motion: Schmuck blendet ein oder steht');

/* --- INTROS gehoert v1 und bleibt unangetastet --- */

assert_same(6, count(Themes::INTROS), 'motion: die Auftakte der ersten Fassung bleiben');

/* --- Ein Wert, den es nicht mehr gibt, faellt auf den Ersatz - er wirft nicht.
       Der Ersatz kommt aus defaultMoves(), NICHT stur aus 'none': das steht
       so in complete() und ist der Grund, warum defaultMoves() in derselben
       Aufgabe mitgeht. Bliebe die alte Tabelle stehen, ersetzte sie einen
       ungueltigen Wert durch den naechsten ungueltigen. --- */

$thema = Themes::complete([
    'id'       => 'safran',        // in der alten Tabelle: confetti + heartbeat
    'particle' => 'confetti',
    'idle'     => 'heartbeat',
    'reveal'   => 'zoom',
]);

assert_same('none', $thema['particle'], 'motion: Konfetti faellt auf none - mehr gibt es nicht');
assert_same('breathe', $thema['idle'], 'motion: Herzschlag faellt auf das Atmen');
assert_same('up', $thema['reveal'], 'motion: zoom faellt auf die eine Richtung');

/* --- Und zwar fuer JEDES Thema gleich: bei einer Auswahl von eins gibt es
       nichts mehr, was "zur Farbwelt passen" koennte --- */

foreach (['elysee', 'noir', 'moderne', 'gibtesnicht'] as $id) {
    $t = Themes::complete(['id' => $id, 'particle' => 'spark', 'idle' => 'ring']);
    assert_same('none', $t['particle'], "motion: $id hat keine Teilchen");
    assert_same('breathe', $t['idle'], "motion: $id atmet");
}
```

- [ ] **Step 2: Çalıştır, başarısız olduğunu gör**

```bash
cd php && php bin/test.php themes_motion
```

Beklenen: `motion: keine Teilchen mehr` başarısız.

- [ ] **Step 3: Listeleri buda**

`php/src/Themes.php` — beş sabiti değiştir:

```php
    /*
     * Weniger, mit Absicht.
     *
     * Der Kunde hat die Einladung mit "Cizgifilm gibi" beschrieben und zwei
     * Referenzen gezeigt, die ueberhaupt nichts bewegen. Die Auswahl war
     * vorher 32 Eintraege gross - und eine grosse Auswahl laedt dazu ein,
     * mehrere davon zu nehmen. Was hier fehlt, fehlt nicht aus Versehen.
     *
     * Die zugehoerigen Keyframes im Stylesheet bleiben stehen: versendete
     * Einladungen tragen die alten Werte in ihrem themeSnapshot und zeigen
     * auf sie. Waehlen kann man sie nicht mehr.
     */

    /** Das Atmen bleibt: es ist kein Schmuck, sondern das Zeichen "fass mich an". */
    public const IDLES = ['breathe', 'none'];

    public const NAME_ANIMATIONS = ['fade', 'none'];

    /** Konfetti, Schnee, Funken - genau das war gemeint mit "zu viel". */
    public const PARTICLES = ['none'];

    public const REVEALS = ['up', 'none'];

    public const MOVES = ['none', 'fade'];
```

`INTROS`'a **dokunma**.

- [ ] **Step 3b: `defaultMoves()`'u da düzelt — yoksa budama tutmaz**

`Themes::complete()` geçersiz bir değeri `'none'`'a değil, `defaultMoves($id)`'e düşürür (`src/Themes.php:467-471`). O tablo bugün tamamen silinen değerlerden oluşuyor (`petal`, `write`, `sheen`, `letters`, `spark`, `confetti`, `glow`, `heartbeat`, `ring`, `tilt`, `snow`, `round`, `mask`, `side`, `zoom`). **Tablo olduğu gibi kalırsa `complete()` bir geçersiz değeri bir başka geçersiz değerle değiştirir ve budama hiçbir şey yapmaz.**

`defaultMoves()` gövdesini **şununla değiştir**:

```php
    private static function defaultMoves(string $id): array
    {
        /*
         * Die Tabelle je Thema ist weg, und zwar folgerichtig: sie stand hier,
         * damit nicht alle Themen dieselbe Antwort bekommen - "Alle Themen auf
         * dieselbe Antwort zu setzen waere das Gegenteil von dem, wozu die
         * Auswahl da ist." Nach der Beschneidung gibt es bei vier der sechs
         * Achsen aber nur noch eine Antwort. Eine Tabelle, die zwischen einer
         * Moeglichkeit waehlt, ist keine Tabelle.
         *
         * Die zwei Achsen mit echter Auswahl bleiben unterschieden:
         * animation (ANIMATIONS, zwoelf Bewegungen der Karte) und intro
         * (INTROS, die Auftakte der ersten Fassung).
         */
        $sets = [
            'elysee'   => ['seal',       'sealLight'],
            'sage'     => ['rise',       'focus'],
            'foresta'  => ['rise',       'focus'],
            'blush'    => ['fade',       'darkroom'],
            'lavande'  => ['blur',       'focus'],
            'noir'     => ['flip',       'darkroom'],
            'bordeaux' => ['zoom',       'sealLight'],
            'pearl'    => ['curtain',    'focus'],
            'marbre'   => ['zoomOut',    'sealLight'],
            'azur'     => ['slideRight', 'focus'],
            'terra'    => ['unfold',     'darkroom'],
            'safran'   => ['zoom',       'party'],
            'rubis'    => ['slideLeft',  'party'],
            'moderne'  => ['fade',       'none'],
        ];

        [$animation, $intro] = $sets[$id] ?? ['seal', 'sealLight'];

        return [
            'animation'     => $animation,
            'intro'         => $intro,
            // Ab hier gibt es nichts mehr zu waehlen.
            'nameAnimation' => 'fade',
            'particle'      => 'none',
            'reveal'        => 'up',
            'idle'          => 'breathe',
        ];
    }
```

`moveLabel()` ve diğer etiket tabloları artık olmayan anahtarları taşıyabilir — zararsızdır, ama silinen anahtarların etiketlerini de kaldır ki tablo yalan söylemesin.

- [ ] **Step 4: Çalıştır, geçtiğini gör**

```bash
cd php && php bin/test.php themes_motion
```

Beklenen: PASS.

- [ ] **Step 5: Tüm testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS. Kırılan varsa, silinmiş bir hareket adını bekleyen eski bir testtir — testi yeni gerçeğe göre düzelt, sabiti geri koyma.

- [ ] **Step 6: Elle doğrula**

1. `/de/admin/themen` → hareket açılır listeleri kısaldı.
2. `/de/admin/designs/<slug>` → „6 · Bewegung" listeleri kısaldı.
3. Veritabanında konfetili eski bir tema varsa aç → seçim `none`'da duruyor, sayfa hata vermiyor.
4. Yayındaki bir v1 davetiyesini aç → **eskisi gibi** görünüyor (snapshot + duran keyframe'ler).

- [ ] **Step 7: Commit**

```bash
git add php/src/Themes.php php/tests/themes_motion.php
git commit -m "$(cat <<'EOF'
32 Bewegungen werden 8, und das Atmen darf bleiben

"Cok animasyon. Cizgifilm gibi." Beide Referenzen des Kunden bewegen
ueberhaupt nichts - herzsiegel ist ein 9351 px langes Dokument mit elf
Abschnitten und null Animation.

Weg sind die Teilchen ganz. Von idle, Namen, Enthuellung und Schmuck bleibt
je eine Bewegung und die Ruhe. Das Atmen des geschlossenen Kuverts bleibt,
weil es kein Schmuck ist, sondern das Zeichen "fass mich an".

INTROS bleibt unangetastet: Intro:: wird nur in pages/invitation.php
gerufen, die zweite Fassung benutzt es nicht.

Die Keyframes im Stylesheet bleiben stehen - versendete Einladungen zeigen
ueber ihren themeSnapshot darauf.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Motifleri dosyaya aktar — v1 kapısı

**Files:**
- Create: `php/bin/scene-to-decorations.php`
- Modify: yok (yalnızca veri)

**Interfaces:**
- Consumes: `bin/export-scene-art.php`'nin ürettiği dosyalar, `Themes::completeDecoration()`
- Produces: Her temanın `decorations` dizisinde, eskiden `Scenes::html()`'in çizdiği parçalar. Görev 9 buna dayanır.

> **Bu görev veri taşır, kod yazmaz.** Bitmeden Görev 9 yapılamaz.

- [ ] **Step 1: Hangi temaların hangi sahneyi kullandığını çıkar**

```bash
cd php && php -r '
require "src/bootstrap.php";
foreach (Atelier\Themes::all() as $t) {
    printf("%-14s scene=%-12s decorations=%d\n",
        $t["id"], $t["scene"] ?: "(botanical)", count($t["decorations"] ?? []));
}'
```

Çıktıyı **kaydet** — bu, doğrulama listesidir. Seed dosyasına değil, buna güven.

- [ ] **Step 2: Her tema için önce/sonra ekran görüntüsü al**

Her tema için `/de/einladung/<örnek-slug>?theme=<id>` (ya da panelin tema önizlemesi) açılıp ekran görüntüsü alınır ve `.superpowers/` altına konur. **Bu adım atlanamaz:** Görev 9'un doğrulaması buna dayanıyor.

- [ ] **Step 3: Dışa aktar**

Step 1'deki listedeki her `<id>` için:

```bash
cd php && php bin/export-scene-art.php <id> --dry   # önce kuru koşu
cd php && php bin/export-scene-art.php <id>
```

Üretilen dosyaların nereye yazıldığını betiğin çıktısı söyler (`public/assets/designs/` altı).

- [ ] **Step 4: Yazıcı betiğini oluştur**

`php/bin/scene-to-decorations.php` **oluştur**:

```php
<?php
declare(strict_types=1);

/**
 * Die exportierte Zeichnung als Schmuckelemente ins Thema schreiben.
 *
 *   php bin/scene-to-decorations.php elysee --dry
 *   php bin/scene-to-decorations.php elysee
 *
 * export-scene-art.php macht aus Scenes::html() Dateien. Dieses Skript macht
 * aus den Dateien Eintraege in theme.decorations - erst danach zeigt das Thema
 * seinen Schmuck ohne Scenes.php.
 *
 * Vorhandene decorations werden NICHT angeruehrt: wer im Panel schon etwas
 * hingelegt hat, soll es behalten. Die neuen kommen dahinter, und die
 * Obergrenze von zwoelf gilt weiter - was nicht mehr passt, wird gemeldet
 * und nicht still weggeworfen.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$id = '';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--dry') {
        $id = $arg;
    }
}
$dry = in_array('--dry', $argv, true);

if ($id === '') {
    exit("Welches Thema? z. B. php bin/scene-to-decorations.php elysee\n");
}

$theme = Themes::find($id);
if ($theme === null) {
    exit("Thema '$id' gibt es nicht.\n");
}

// Was export-scene-art.php abgelegt hat. Das Muster steht dort; hier wird
// nur eingesammelt, was zu diesem Thema gehoert.
$muster = __DIR__ . '/../public/assets/designs/' . $id . '-*.svg';
$dateien = glob($muster) ?: [];

if ($dateien === []) {
    exit("Keine Dateien unter $muster - erst php bin/export-scene-art.php $id laufen lassen.\n");
}

$vorhanden = $theme['decorations'] ?? [];
$platz = 12 - count($vorhanden);

if ($platz <= 0) {
    exit("Das Thema hat schon zwoelf Schmuckelemente. Erst im Panel Platz machen.\n");
}

$neu = [];
foreach ($dateien as $i => $pfad) {
    if (count($neu) >= $platz) {
        fwrite(STDERR, "Achtung: " . (count($dateien) - $platz) . " Datei(en) passen nicht mehr rein.\n");
        break;
    }

    $neu[] = Themes::completeDecoration([
        'id'     => 'szene' . ($i + 1),
        'label'  => 'Szene ' . ($i + 1),
        'src'    => '/assets/designs/' . basename($pfad),
        // Die Szene lag hinter der Karte, auf der Seite - genau wie hier.
        'spot'   => 'page',
        'x'      => '0',
        'y'      => '0',
        'width'  => '100',
        'front'  => false,
        // Nach der Beschneidung der Bewegungen gibt es nur noch fade und none.
        'move'   => 'fade',
    ]);
}

printf("%s: %d vorhanden + %d neu\n", $id, count($vorhanden), count($neu));
foreach ($neu as $d) {
    printf("  %-8s %s\n", $d['id'], $d['src']);
}

if ($dry) {
    echo "Kuehler Lauf - nichts geschrieben.\n";
    exit(0);
}

$theme['decorations'] = array_merge($vorhanden, $neu);
// Die Szene wird nicht mehr gezeichnet: der Schmuck steht jetzt in den Daten.
$theme['scene'] = 'none';

/*
 * Themes::save() nimmt die GANZE Liste, nicht ein Thema (src/Themes.php:347 -
 * es vergleicht jedes gegen den Stand davor, um die Fassungsnummer nur dann
 * zu erhoehen, wenn sich wirklich etwas geaendert hat). Einzeln zu speichern
 * hiesse, alle anderen zu loeschen.
 */
$alle = [];
foreach (Themes::all() as $t) {
    $alle[] = (string) $t['id'] === $id ? $theme : $t;
}
Themes::save($alle);

echo "Geschrieben.\n";
```

> **Not:** `x/y/width` değerlerinin `completeDecoration()`'ın sınırları içinde kaldığını doğrula: `x`/`y` −50…150, `width` 1…200. Tam sayfa için `x=0, y=0, width=100` doğru.

- [ ] **Step 5: Her tema için çalıştır**

Step 1'deki listedeki her `<id>` için:

```bash
cd php && php bin/scene-to-decorations.php <id> --dry
cd php && php bin/scene-to-decorations.php <id>
```

- [ ] **Step 6: Gözle karşılaştır**

Step 2'deki her ekran görüntüsünü yeniden al ve yan yana koy. **Her tema için** cevapla: aynı mı?

Farklı olan tema varsa: `decorations` girdisinin `x/y/width/opacity` değerlerini panelden düzelt. **Farkı görmezden gelip Görev 9'a geçme** — o noktadan sonra geri dönüş yok.

- [ ] **Step 7: Commit**

```bash
git add php/bin/scene-to-decorations.php
git commit -m "$(cat <<'EOF'
Die gezeichnete Szene zieht in die Daten, bevor der Zeichner geht

export-scene-art.php macht aus Scenes::html() Dateien; dieses Skript macht
aus den Dateien Eintraege in theme.decorations. Erst danach zeigt ein Thema
seinen Schmuck ohne Scenes.php - und erst danach darf die Klasse sterben.

Warum das eine Tuer ist und keine Empfehlung: v1 zeichnet die Szene bei
JEDEM Aufruf neu. Der themeSnapshot schuetzt sie nicht. Wer Scenes.php
vorher loescht, loescht den Hintergrund aus Einladungen, die laengst bei
den Gaesten liegen.

Vorhandene decorations bleiben stehen. Was nicht mehr in die zwoelf passt,
wird gemeldet und nicht still weggeworfen.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: `Scenes.php` emekli

**Files:**
- Delete: `php/src/Scenes.php`
- Modify: `php/templates/pages/invitation.php` — 37, 55-56 ve 241. satırlar
- Modify: `php/src/Themes.php` — `SCENES` sabiti ve `complete()`'teki doğrulaması
- Modify: `php/bin/export-scene-art.php` — silinir (kaynağı gitti)
- Modify: `php/templates/admin/themes.php` — sahne seçici kalkar

> **ÖN KOŞUL:** Görev 8'in 6. adımı her tema için „aynı" yanıtını vermiş olmalı. Vermediyse bu görev başlamaz.

- [ ] **Step 1: Ön koşulu doğrula**

```bash
cd php && php -r '
require "src/bootstrap.php";
$kirli = [];
foreach (Atelier\Themes::all() as $t) {
    if (($t["scene"] ?? "") !== "none" && ($t["scene"] ?? "") !== "") { $kirli[] = $t["id"]; }
    if (count($t["decorations"] ?? []) === 0) { $kirli[] = $t["id"] . " (ohne Schmuck)"; }
}
echo $kirli === [] ? "Alle Themen sind umgezogen.\n" : "NICHT fertig: " . implode(", ", $kirli) . "\n";'
```

„NICHT fertig" çıkarsa **dur** ve Görev 8'e dön.

- [ ] **Step 2: Çağrıları kaldır**

`php/templates/pages/invitation.php`:

```php
$scene = Scenes::html((string) ($theme['scene'] ?? 'botanical'), $theme);
```

satırını sil, `$scene` kullanılan yerleri boş dizeye çevir ya da o blokları kaldır. Aynısını 241. satırdaki

```php
<?= Scenes::envelopeArt((string) ($theme['scene'] ?? ''), $theme) ?>
```

için yap. Dosyanın `use` bloğundan `Atelier\Scenes` çıkar.

- [ ] **Step 3: Sabiti ve seçiciyi kaldır**

`php/src/Themes.php`: `SCENES` sabitini sil; `complete()` içinde `scene` alanını doğrulayan bloğu (460 civarı) sil; varsayılanlardaki `'scene' => ''` alanı **kalsın** (eski kayıtlarda duruyor, silinmesi gerekmez).

`php/templates/admin/themes.php`: sahne seçici `<select>`'i kaldır.

- [ ] **Step 4: Sınıfı ve artık aracı sil**

Üçü birden gider. `export-scene-art.php` `Scenes::html()`'i okuyor, `scene-to-decorations.php` de onun ürettiği dosyaları bekliyor — kaynak silinince ikisi de anlamsız kalır:

```bash
git rm php/src/Scenes.php php/bin/export-scene-art.php php/bin/scene-to-decorations.php
```

- [ ] **Step 5: Kalan izleri ara**

```bash
cd php && grep -rn "Scenes\|SCENES\|scene-corner\|scene-wash" src templates public bin data tests
```

Beklenen: sıfır sonuç. `public/assets/style.css` içinde `.scene-*` kuralları kaldıysa onları da sil.

- [ ] **Step 6: Testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS.

- [ ] **Step 7: Elle doğrula**

Görev 8 Step 2'deki listeye göre **her temayı** yeniden aç ve ekran görüntüleriyle karşılaştır. Ayrıca yayındaki en az iki v1 davetiyesini aç.

- [ ] **Step 8: Commit**

```bash
git add -A php/
git commit -m "$(cat <<'EOF'
322 Zeilen, die Blumen gezeichnet haben, sind nicht mehr noetig

Der Schmuck kommt jetzt als Datei aus den decorations - so, wie er bei
beiden Referenzen des Kunden auch kommt (motifs/tuscany/villa.png,
cypress.png, divider.png). Gezeichnetes SVG sieht nach Zeichnung aus, und
genau das war der Vorwurf.

Erst nach Aufgabe 8, und die Pruefung davor ist im Plan: kein Thema mit
scene != none, keines ohne Schmuck. Nachgesehen wurde jedes einzeln, mit
dem Bild von vorher daneben.

export-scene-art.php und scene-to-decorations.php gehen mit: ihre Quelle
war Scenes::html().

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: İki referans tasarım

**Files:**
- Modify: `php/bin/seed-designs.php`

**Interfaces:**
- Consumes: Görev 1-7'nin hepsi
- Produces: Katalogda `film` ve `bild` kimlikli iki v2 tasarımı.

- [ ] **Step 1: Ortak düzeni tanımla**

`php/bin/seed-designs.php` içine, mevcut tasarım kurulumlarının yanına ekle. Düzen Toskana'nın okuması: kenarlarda motif, **ortası boş**, ortada küçük harf aralıklı üst başlık + isimler + tarih.

```php
/*
 * Die zwei Vorlagen, mit denen der Kunde anfangen will: "1. video, 2. resim."
 *
 * Sie unterscheiden sich in genau einer Ebene - der hintersten. Alles andere
 * ist gleich, mit Absicht: was der Kunde vergleichen will, ist der Film gegen
 * das Foto, nicht zwei Entwuerfe gegeneinander.
 *
 * Die Mitte bleibt frei. "Ortasi bos dusun, sadece cicekler var."
 */
$grundEbenen = static function (array $hinten): array {
    return array_merge([$hinten], [
        // Der Rahmen aus Motiven. Er liegt auf der Seite, hinter der Karte,
        // und laesst die Mitte offen - die Datei selbst hat dort nichts.
        [
            'id'    => 'rahmen',
            'label' => 'Blütenrahmen',
            'type'  => 'image',
            'spot'  => 'page',
            'src'   => '',   // Der Grafiker legt die Datei; leer faellt sie weg.
            'box'   => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
            'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1200],
        ],
        [
            'id'    => 'obertitel',
            'label' => 'Überschrift',
            'type'  => 'text',
            'spot'  => 'card',
            'text'  => ['de' => 'WIR HEIRATEN', 'en' => 'WE ARE GETTING MARRIED'],
            'box'   => ['x' => 10, 'y' => 30, 'w' => 80],
            'style' => ['font' => 'body', 'color' => 'soft', 'size' => 26, 'align' => 'center'],
            'motion' => ['move' => 'fade', 'delay' => 200, 'duration' => 1200],
        ],
        [
            'id'    => 'namen',
            'label' => 'Namen',
            'type'  => 'text',
            'spot'  => 'card',
            'bind'  => 'couple_names',
            'box'   => ['x' => 8, 'y' => 42, 'w' => 84],
            'style' => ['font' => 'display', 'color' => 'fg', 'size' => 108, 'align' => 'center'],
            'motion' => ['move' => 'fade', 'delay' => 500, 'duration' => 1400],
        ],
        [
            'id'    => 'datum',
            'label' => 'Datum',
            'type'  => 'text',
            'spot'  => 'card',
            'bind'  => 'wedding_date',
            'box'   => ['x' => 10, 'y' => 60, 'w' => 80],
            'style' => ['font' => 'body', 'color' => 'soft', 'size' => 30, 'align' => 'center'],
            'motion' => ['move' => 'fade', 'delay' => 800, 'duration' => 1200],
        ],
    ]);
};
```

- [ ] **Step 2: İki tasarımı kur**

Aynı dosyada, `$grundEbenen`'den sonra:

```php
$vorlagen = [
    [
        'id'   => 'film',
        'slug' => 'film',
        'name' => ['de' => 'Film', 'en' => 'Film'],
        'category' => 'floral',
        'status'   => 'draft',
        'layers'   => $grundEbenen([
            'id'     => 'hintergrund',
            'label'  => 'Hintergrundfilm',
            'type'   => 'video',
            'spot'   => 'page',
            'src'    => '',      // kommt aus der Bibliothek oder vom Grafiker
            'poster' => '',
            'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
            // Das Recht, aus der Bibliothek zu waehlen: edit ist der
            // Hauptschalter, photo die Erlaubnis fuer den Inhalt.
            'permissions' => ['edit' => true, 'photo' => true],
        ]),
    ],
    [
        'id'   => 'bild',
        'slug' => 'bild',
        'name' => ['de' => 'Bild', 'en' => 'Image'],
        'category' => 'floral',
        'status'   => 'draft',
        'layers'   => $grundEbenen([
            'id'    => 'hintergrund',
            'label' => 'Hintergrundbild',
            'type'  => 'photo',
            'spot'  => 'page',
            'src'   => '',
            'box'   => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
            'permissions' => ['edit' => true, 'photo' => true],
        ]),
    ],
];

foreach ($vorlagen as $roh) {
    // Nicht ueberschreiben, was schon da ist: der Grafiker hat womoeglich
    // laengst Dateien hinterlegt, und ein zweiter Seed-Lauf soll seine Arbeit
    // nicht wegwerfen.
    if (Design::findById($roh['id']) !== null) {
        echo "  {$roh['id']}: gibt es schon, uebersprungen\n";
        continue;
    }
    Design::save(Design::complete($roh));
    echo "  {$roh['id']}: angelegt\n";
}
```

- [ ] **Step 3: Çalıştır**

```bash
cd php && php bin/seed-designs.php
```

Beklenen: `film: angelegt` ve `bild: angelegt`. İkinci kez çalıştır → `gibt es schon, uebersprungen`.

- [ ] **Step 4: Testleri çalıştır**

```bash
cd php && php bin/test.php
```

Beklenen: hepsi PASS.

- [ ] **Step 5: Elle doğrula**

1. `/de/admin/designs` → „Film" ve „Bild" katalogda, taslak.
2. „Film"i aç → „5b · Videolar" altında `hintergrund` katmanı var, boş.
3. Kitaplıktan bir filmi katmana yolla (yol alanına kopyala) → Kaydet → önizlemede film oynuyor.
4. Durumu `active` yap → `/de/v2/designs` vitrininde görünüyor.
5. Sihirbazdan „Film" ile bir davetiye kur → arka planda film, ortası boş, isimler ortada.
6. „Bild" ile aynısını yap → aynı düzen, arkada foto alanı.

- [ ] **Step 6: Commit**

```bash
git add php/bin/seed-designs.php
git commit -m "$(cat <<'EOF'
Zwei Vorlagen, die sich in genau einer Ebene unterscheiden

"1. video, 2. resim. Baslangic olarak bunlarla calisabiliriz." Also: gleiche
Anordnung, gleiche Schrift, gleiche Ruhe - unten liegt einmal ein Film und
einmal ein Foto. Was verglichen werden soll, ist das Material, nicht zwei
Entwuerfe gegeneinander.

Die Mitte bleibt frei: "Ortasi bos dusun, sadece cicekler var." Der Rahmen
liegt auf der Seite und laesst sie offen; auf der Karte stehen nur drei
Zeilen. Bewegung ist ueberall fade - mehr gibt es nach Aufgabe 7 auch nicht.

Die Dateien fehlen mit Absicht. Motive und Filme kommen vom Kunden; die
Vorlagen zeigen das System, nicht das Ergebnis.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Spec kapsam kontrolü

| Spec bölümü | Görev |
|---|---|
| 3.1 Çizim | 1 |
| 3.2 Stil | 1 |
| 3.3 Şema (`poster`) | 1 |
| 3.4 Yer (`spot`) | 1 (yeni kod gerekmiyor — `spot` zaten çalışıyor, test 1'de doğrulanıyor) |
| 3.5 Oynatmayı kim başlatır | 1 (öznitelik yok) + 6 (başlatma) |
| 3.6 Güvenlik | 1, 2 (`safeSrc`, `storeVideo`); CSP'de değişiklik yok |
| 4.1 Kitaplık kaydı | 3 |
| 4.2 Yönerge | 2 (katman kutusu) + 5 (açılış kutusu) — **değerler `templates/admin/themes.php`'deki mevcut metinden alındı, spec'in açık maddesi kapandı** |
| 4.3 İzin | 4 |
| 4.4 Çift yükleyemez | 4 (yükleme kutusu yerine açılır liste) |
| 5. Açılış | 5 + 6 |
| 6.1-6.2 Motif geçişi ve kapı | 8 |
| 6.3 v2 tarafı | Kod gerekmiyor — 10'da kullanılıyor |
| 7. Budama | 7 |
| 8. İki tasarım | 10 |
| 9. Test | 1, 2, 3, 4, 7 (birim) + her görevin „elle doğrula" adımı |

**Spec §11'in açık maddeleri:**

1. **Yönerge değerleri** — kapandı. `php/templates/admin/themes.php` `backdropVideo` kutusunda zaten yazılı: 9:16 veya 3:4, en az 720×1280 (tercihen 1080×1920), 4–10 s, yavaş hareket, yazı/logo/yüz yok. Görev 2 ve 5 aynı değerleri kullanıyor. **Muhtemelen Ayhan'ın „panelde yönerge var" dediği yer burası.**
2. **Hangi tasarımda hangi izin açık** — Görev 10 iki referans tasarımda `edit`+`photo`'yu açıyor. Diğer tasarımlar için karar grafikerin, tasarım tasarım.
3. **v1'in ömrü** — bu plan v1'i yaşatıyor. Kapatma kararı verilirse Görev 8 gereksizleşir ve Görev 9 tek satırlık bir silme olur.
