<?php
/**
 * Die acht Abschnitte des Editors. Liegt neben design-edit.php und nicht darin:
 * das Geruest mit der Vorschau ist eine Sache, die Felder sind eine andere, und
 * beide zusammen waeren eine Datei, die niemand mehr ueberblickt.
 *
 * Erwartet aus design-edit.php: $design, $tr, $label, $feld, $auf, $zu,
 * $textEbenen, $bindEbenen, $bildEbenen.
 *
 * @var array<string,mixed> $design
 */

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\SectionRegistry;
use Atelier\Themes;
use function Atelier\e;
?>

<?= $auf($tr ? '1 · Genel' : '1 · Allgemein') ?>
  <div class="grid gap-5 sm:grid-cols-2">
    <label class="<?= $label ?>">DE
      <input name="name_de" value="<?= e($design['name']['de']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>">EN
      <input name="name_en" value="<?= e($design['name']['en']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>"><?= $tr ? 'Kategori' : 'Kategorie' ?>
      <input name="category" value="<?= e((string) $design['category']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>"><?= $tr ? 'Sıra' : 'Reihenfolge' ?>
      <input name="sort" type="number" value="<?= (int) $design['sort'] ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?> sm:col-span-2"><?= $tr ? 'Etiketler (virgülle)' : 'Schlagworte (mit Komma)' ?>
      <input name="tags" value="<?= e(implode(', ', $design['tags'])) ?>" class="<?= $feld ?>"></label>
  </div>
<?= $zu ?>

<?= $auf($tr ? '2 · Renkler' : '2 · Farben') ?>
  <div class="grid gap-5 sm:grid-cols-2">
    <?php foreach ($design['palette'] as $marke => $eintrag) : ?>
      <?php $istHex = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $eintrag['value']) === 1; ?>
      <div>
        <span class="<?= $label ?>"><?= e($eintrag['label']['de'] ?? $marke) ?></span>
        <div class="mt-1 flex items-center gap-2">
          <input type="color" value="<?= e($istHex ? (string) $eintrag['value'] : '#B08D57') ?>"
                 class="h-9 w-10 shrink-0 cursor-pointer border border-sand-deep bg-transparent p-0"
                 data-farbwahl="<?= e($marke) ?>" <?= $istHex ? '' : 'title="rgba"' ?>>
          <input name="palette_<?= e($marke) ?>" value="<?= e((string) $eintrag['value']) ?>"
                 class="<?= $feld ?> font-mono text-[0.8rem]" data-farbfeld="<?= e($marke) ?>">
        </div>
        <label class="mt-2 flex items-center gap-2 text-[0.66rem] text-muted">
          <input type="checkbox" name="palette_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
          <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf ändern' ?>
        </label>
      </div>
    <?php endforeach; ?>
  </div>
<?= $zu ?>

<?= $auf($tr ? '3 · Yazı tipleri' : '3 · Schriften') ?>
  <?php foreach ($design['fonts'] as $marke => $eintrag) : ?>
    <div class="space-y-3 border-b border-sand-deep pb-4">
      <div class="grid gap-4 sm:grid-cols-5">
        <label class="<?= $label ?>"><?= e($marke) ?>
          <select name="font_family_<?= e($marke) ?>" class="<?= $feld ?>" data-schriftfeld="<?= e($marke) ?>">
            <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
              <option value="<?= e($familie) ?>" <?= $eintrag['family'] === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
            <?php endforeach; ?>
          </select></label>
        <?php /*
          Die Groesse der Marke ist ein FAKTOR in Prozent, keine Punktzahl:
          100 laesst alles, wie es ist, 120 macht jede Zeile dieser Schrift
          eine Fuenftel groesser. Wie gross eine EINZELNE Zeile ist, steht
          weiter unten bei der Anordnung - eine Ueberschrift und eine
          Bildunterschrift teilen sich die Schrift, nicht die Groesse.
        */ ?>
        <label class="<?= $label ?>"><?= $tr ? 'büyüklük %' : 'Größe %' ?>
          <input name="font_size_<?= e($marke) ?>" type="number" min="1" max="400"
                 value="<?= (int) $eintrag['size'] ?>" class="<?= $feld ?>" data-groessefeld="<?= e($marke) ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'ağırlık' : 'Gewicht' ?>
          <input name="font_weight_<?= e($marke) ?>" type="number" step="100" min="100" max="900"
                 value="<?= (int) $eintrag['weight'] ?>" class="<?= $feld ?>" data-gewichtfeld="<?= e($marke) ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'laufweite' : 'Laufweite' ?>
          <input name="font_tracking_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['tracking'] ?>" class="<?= $feld ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'satır yüksekliği' : 'Zeilenhöhe' ?>
          <input name="font_line_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['lineHeight'] ?>" class="<?= $feld ?>"></label>
      </div>
      <label class="flex items-center gap-2 text-[0.66rem] text-muted">
        <input type="checkbox" name="font_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
        <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf ändern' ?>
      </label>
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?= $auf($tr ? '4 · Metinler' : '4 · Texte') ?>
  <?php foreach ($textEbenen as $ebene) : ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?> · DE
        <input name="text_de_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['de']) ?>"
               class="<?= $feld ?>" data-textfeld="<?= e($ebene['id']) ?>"></label>
      <label class="<?= $label ?>">EN
        <input name="text_en_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['en']) ?>" class="<?= $feld ?>"></label>
    </div>
  <?php endforeach; ?>
  <?php if ($bindEbenen !== []) : ?>
    <p class="<?= $label ?>"><?= $tr ? 'Çiftin verisinden gelenler (düzenlenemez):' : 'Kommt aus den Daten des Paares (nicht editierbar):' ?></p>
    <ul class="space-y-1 text-sm text-muted">
      <?php foreach ($bindEbenen as $ebene) : ?>
        <li><?= e($ebene['label'] ?: $ebene['id']) ?> — <code><?= e((string) $ebene['bind']) ?></code></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?= $zu ?>

<?= $auf($tr ? '5 · Görseller' : '5 · Bilder') ?>
  <?php if ($bildEbenen === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu tasarımda görsel katman yok.' : 'Diese Vorlage hat keine Bildebene.' ?></p>
  <?php endif; ?>
  <?php foreach ($bildEbenen as $ebene) : ?>
    <?php $bildQuelle = Design::safeSrc((string) $ebene['src']); ?>
    <div class="border-t border-sand-deep pt-5 first:border-0 first:pt-0">
      <div class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?></div>

      <div class="mt-3 flex items-start gap-5">
        <?php /*
           Was heute an dieser Stelle steht. Der Pfad allein sagt es nicht:
           "elysee-3.svg" ist kein Bild, das man wiedererkennt, und wer eine
           von vierzehn Ebenen tauschen will, muss sehen, welche.

           safeSrc() statt des rohen Wertes: im Feld darunter darf jemand
           tippen, was er will, und das landete sonst ungeprueft in einem
           src-Attribut. Dieselbe Pruefung, die auch die Karte anwendet.
        */ ?>
        <div class="w-24 shrink-0">
          <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
               data-vorschau-fuer="bild_<?= e($ebene['id']) ?>">
            <?php if ($bildQuelle !== '') : ?>
              <img src="<?= e($bildQuelle) ?>" alt="" class="h-full w-full object-contain">
            <?php else : ?>
              <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
                <?= $tr ? 'görsel yok' : 'kein Bild' ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="w-full">
          <label class="<?= $label ?>"><?= $tr ? 'Yeni görsel yükle' : 'Neues Bild hochladen' ?>
            <input type="file" name="bild_<?= e($ebene['id']) ?>"
                   accept="image/png,image/jpeg,image/webp,image/svg+xml"
                   class="<?= $feld ?>"></label>

          <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da yol' : 'oder Pfad' ?>
            <input name="src_<?= e($ebene['id']) ?>" value="<?= e((string) $ebene['src']) ?>"
                   class="<?= $feld ?> font-mono text-[0.78rem]"></label>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <p class="<?= $label ?>">
    <?= $tr
      ? 'Yükleme uploads/designs/ altına gider ve yol alanını kendisi doldurur; SVG temizlenip geçirilir, diğerleri saydamlığı koruyarak küçültülür. assets/designs/ altındakiler ise dışa aktarma betiğinin ürünü — elle değiştirme, sonraki export ezer.'
      : 'Ein Upload landet unter uploads/designs/ und schreibt das Pfadfeld selbst; SVG wird geputzt durchgereicht, alles andere mit Transparenz verkleinert. Was unter assets/designs/ liegt, erzeugt dagegen das Exportskript; von Hand geändert, überschreibt es der nächste Export.' ?>
  </p>

  <?php /*
     Eine neue Bildebene anlegen.

     Bis hierher konnte der Editor Ebenen nur AENDERN - fromPost laeuft ueber
     die vorhandenen und legt keine an. Eine Vorlage ohne Fotoebene bot dem
     Paar deshalb nirgends ein Feld fuer sein eigenes Bild, und daran war im
     Panel nichts zu machen.

     Die drei Zuschnitte sind ein Startpunkt, kein Urteil: sie stehen alle
     ueber die volle Breite, und danach steht die Ebene im Abschnitt
     "Anordnung" mit ihrem Kasten da wie jede andere. Solange es dort keine
     Felder gab, war der Zuschnitt endgueltig - deshalb waren es genau drei.
  */ ?>
  <div class="mt-6 border-t border-sand-deep pt-5">
    <div class="<?= $label ?>"><?= $tr ? 'Yeni görsel/video katmanı' : 'Neue Bild- oder Videoebene' ?></div>
    <p class="mt-2 text-[0.78rem] leading-relaxed text-muted">
      <?= $tr
        ? 'Adı yazıp kaydedersen katman eklenir ve müşterinin sihirbazında kendi görselini yükleyebileceği bir alan olarak çıkar. En alta, her şeyin arkasına konur. Boş bırakırsan hiçbir şey olmaz.'
        : 'Trägst du einen Namen ein und speicherst, entsteht die Ebene - und im Assistenten des Paares ein Feld, in das es sein eigenes Bild lädt. Sie kommt nach ganz hinten, unter alles andere. Leer gelassen passiert nichts.' ?>
    </p>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <label class="<?= $label ?>"><?= $tr ? 'Ne' : 'Was' ?>
        <select name="neue_ebene_typ" class="<?= $feld ?>">
          <option value="photo"><?= $tr ? 'Görsel' : 'Bild' ?></option>
          <option value="video"><?= $tr ? 'Video' : 'Video' ?></option>
        </select></label>

      <label class="<?= $label ?>"><?= $tr ? 'Adı (müşteri bunu görür)' : 'Name (das Paar liest ihn)' ?>
        <input name="neue_ebene_label" value="" maxlength="60" class="<?= $feld ?>"
               placeholder="<?= $tr ? 'ör. Fotoğrafınız' : 'z. B. Euer Foto' ?>"></label>

      <label class="<?= $label ?>"><?= $tr ? 'Nerede' : 'Wo' ?>
        <select name="neue_ebene_spot" class="<?= $feld ?>">
          <?php foreach (Themes::SPOTS as $wert => $namen) : ?>
            <option value="<?= e($wert) ?>" <?= $wert === 'card' ? 'selected' : '' ?>>
              <?= e($namen[$tr ? 'tr' : 'de']) ?>
            </option>
          <?php endforeach; ?>
        </select></label>

      <label class="<?= $label ?>"><?= $tr ? 'Kaplama' : 'Zuschnitt' ?>
        <select name="neue_ebene_schnitt" class="<?= $feld ?>">
          <option value="voll"><?= $tr ? 'Tamamını kaplar' : 'Ganze Fläche' ?></option>
          <option value="oben"><?= $tr ? 'Üst yarı' : 'Obere Hälfte' ?></option>
          <option value="unten"><?= $tr ? 'Alt yarı' : 'Untere Hälfte' ?></option>
        </select></label>

      <label class="<?= $label ?>"><?= $tr ? 'Başlangıç görseli (isteğe bağlı)' : 'Startbild (optional)' ?>
        <input type="file" name="neue_ebene_bild"
               accept="image/png,image/jpeg,image/webp,image/svg+xml,video/mp4,video/webm" class="<?= $feld ?>"></label>
    </div>
  </div>
<?= $zu ?>

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
          <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
               data-vorschau-fuer="video_<?= e($ebene['id']) ?>">
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
     Der Vorspann. Er steht bei den Videos und nicht bei der Bewegung, weil
     er eine Datei ist und keine Auswahl - und er gehoert dem DOKUMENT: das
     Thema hat ihn einmal mitgegeben (fromTheme), ab hier entscheidet die
     Vorlage. Eine versendete Einladung behaelt ohnehin ihren eingefrorenen.
  */ ?>
  <div class="mt-6 border-t border-sand-deep pt-5">
    <div class="<?= $label ?>"><?= $tr ? 'Açılış videosu (zarf)' : 'Öffnungsfilm (Kuvert)' ?></div>
    <p class="mt-2 text-[0.78rem] leading-relaxed text-muted">
      <?= $tr
        ? 'Kart gelmeden önce oynar. Boşsa bugünkü çizilmiş zarf çalışır. Dikey 9:16 en iyi oturur, en çok 20 saniye. '
          . 'Film bitince karta 600 ms içinde eriyerek geçer — özel bir bitiş karesi hazırlaman gerekmez.'
        : 'Läuft, bevor die Karte kommt. Leer bedeutet die bisherige gezeichnete Klappe. Hochkant 9:16 sitzt am besten, höchstens 20 Sekunden. '
          . 'Am Ende blendet der Film in 600 ms in die Karte über — ein eigenes Schlussbild braucht er nicht.' ?>
    </p>

    <div class="mt-4 flex items-start gap-5">
      <div class="w-24 shrink-0">
        <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
             data-vorschau-fuer="intro_datei">
          <?php $introQuelle = Design::safeSrc((string) $design['intro']['video']); ?>
          <?php if ($introQuelle !== '') : ?>
            <video src="<?= e($introQuelle) ?>" muted preload="metadata"
                   <?php $introBild = Design::safeSrc((string) $design['intro']['poster']); ?>
                   <?= $introBild !== '' ? 'poster="' . e($introBild) . '"' : '' ?>
                   class="h-full w-full object-cover"></video>
          <?php else : ?>
            <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
              <?= $tr ? 'video yok' : 'kein Film' ?>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="w-full">
        <label class="<?= $label ?>"><?= $tr ? 'Video yükle' : 'Film hochladen' ?>
          <input type="file" name="intro_datei" accept="video/mp4,video/webm,video/quicktime" class="<?= $feld ?>"></label>

        <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da yol (boş bırakınca kalkar)' : 'oder Pfad (leer entfernt ihn)' ?>
          <input name="intro_video" value="<?= e((string) $design['intro']['video']) ?>"
                 class="<?= $feld ?> font-mono text-[0.78rem]"></label>

        <?php /*
           Das Standbild bekommt denselben Kasten wie alles andere.

           Es war das einzige Medienfeld ohne einen: zwei Felder, ein Pfad -
           und die Frage "was liegt da eigentlich" nur zu beantworten, indem
           man die Einladung aufmacht.
        */ ?>
        <?php $posterBild = Design::safeSrc((string) $design['intro']['poster']); ?>
        <div class="mt-4 flex items-start gap-5">
          <div class="w-24 shrink-0">
            <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
                 data-vorschau-fuer="intro_poster_datei">
              <?php if ($posterBild !== '') : ?>
                <img src="<?= e($posterBild) ?>" alt="" class="h-full w-full object-contain">
              <?php else : ?>
                <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
                  <?= $tr ? 'kapak yok' : 'kein Standbild' ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="w-full">
            <label class="<?= $label ?>"><?= $tr ? 'Kapak görseli yükle' : 'Standbild hochladen' ?>
              <input type="file" name="intro_poster_datei" accept="image/png,image/jpeg,image/webp" class="<?= $feld ?>"></label>

            <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da kapak yolu' : 'oder Standbild-Pfad' ?>
              <input name="intro_poster" value="<?= e((string) $design['intro']['poster']) ?>"
                     class="<?= $feld ?> font-mono text-[0.78rem]"></label>
          </div>
        </div>

        <?php /*
           Wie lange der Film laeuft, bevor die Karte kommt.

           Bis heute stand hier gar nichts, und die zweite Fassung schickte
           eine Null an die Buehne: das Skript nimmt dann die Laenge des Films
           selbst und deckelt sie bei sechs Sekunden. Wer einen Film von zwoelf
           Sekunden hinlegt, bekommt sechs - und kann daran nichts aendern.

           Leer bleibt genau dieses Verhalten. Eine Zahl deckelt: drei
           eingetragen heisst nach drei Sekunden kommt die Karte, egal wie
           lang der Film ist.
        */ ?>
        <label class="<?= $label ?> mt-4 block">
          <?= $tr ? 'süre (saniye, boş = filmin kendi boyu)' : 'Dauer (Sekunden, leer = so lang wie der Film)' ?>
          <input name="intro_sekunden" type="number" step="0.1" min="0" max="20"
                 value="<?= ((float) $design['intro']['seconds']) > 0 ? e((string) (float) $design['intro']['seconds']) : '' ?>"
                 class="<?= $feld ?>"></label>
      </div>
    </div>
  </div>

  <?php /*
     Der Grund unter den Abschnitten - also das, was der Gast sieht, wenn er
     unter der Karte weiterwischt.

     Leer heisst nicht "keiner", sondern "derselbe wie auf der Karte": die
     Seite nimmt dann die hinterste Bildebene. Das ist fast immer richtig,
     deshalb steht hier nichts drin. Wer unten ein anderes Blatt will - ein
     ruhigeres, ein dunkleres -, legt es hier hinein.
  */ ?>
  <div class="mt-6 border-t border-sand-deep pt-5">
    <div class="<?= $label ?>"><?= $tr ? 'Alt bölümlerin arka planı' : 'Grund unter den Abschnitten' ?></div>
    <p class="mt-2 text-[0.78rem] leading-relaxed text-muted">
      <?= $tr
        ? 'Boş bırakırsan kartın kâğıdı aşağıda da devam eder. Başka bir görsel koyarsan onu kullanır. Sabit durur, yazı üstünden kayar.'
        : 'Leer gelassen laeuft das Papier der Karte nach unten weiter. Ein eigenes Bild hier ersetzt es. Es steht fest, der Text scrollt darueber.' ?>
    </p>

    <div class="mt-4 flex items-start gap-5">
      <div class="w-24 shrink-0">
        <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
             data-vorschau-fuer="sectionsbg_datei">
          <?php $grundBild = Design::safeSrc((string) $design['sectionsBg']); ?>
          <?php if ($grundBild !== '') : ?>
            <img src="<?= e($grundBild) ?>" alt="" class="h-full w-full object-cover">
          <?php else : ?>
            <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
              <?= $tr ? 'kart gibi' : 'wie die Karte' ?>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="w-full">
        <label class="<?= $label ?>"><?= $tr ? 'Görsel yükle' : 'Bild hochladen' ?>
          <input type="file" name="sectionsbg_datei" accept="image/png,image/jpeg,image/webp" class="<?= $feld ?>"></label>

        <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da yol (boş = kart gibi)' : 'oder Pfad (leer = wie die Karte)' ?>
          <input name="sectionsbg" value="<?= e((string) $design['sectionsBg']) ?>"
                 class="<?= $feld ?> font-mono text-[0.78rem]"></label>

        <?php /*
           Das Blatt des Schlusses.

           "Son sayfa da ayri eklensin - bastaki sayfa cicekler yukarda, son
           sayfada asagida." Das Blatt oben traegt die Struktur ueber die ganze
           Laenge; dieses hier liegt darueber, unten, und wird NICHT gezogen -
           eine Ranke, die sich mit der Laenge der Einladung streckt, sieht
           sofort falsch aus.

           Leer heisst: kein Schluss, alles wie bisher.
        */ ?>
        <div class="mt-6 border-t border-sand-deep pt-4">
          <div class="<?= $label ?>"><?= $tr ? 'Son sayfanın kâğıdı' : 'Blatt des Schlusses' ?></div>
          <p class="mt-2 text-[0.78rem] leading-relaxed text-muted">
            <?= $tr
              ? 'En alta bir kez konur, gerilmez. Çiçekleri altta olan bir kâğıt buraya.'
              : 'Liegt einmal ganz unten und wird nicht gezogen. Ein Blatt, dessen Blumen unten sitzen, gehört hierher.' ?>
          </p>

          <?php $schlussBild = Design::safeSrc((string) ($design['sectionsBgEnd'] ?? '')); ?>
          <div class="mt-3 flex items-start gap-5">
            <div class="w-24 shrink-0">
              <div class="flex aspect-[4/5] items-center justify-center overflow-hidden border border-sand-deep bg-sand"
                   data-vorschau-fuer="sectionsbg_end_datei">
                <?php if ($schlussBild !== '') : ?>
                  <img src="<?= e($schlussBild) ?>" alt="" class="h-full w-full object-contain">
                <?php else : ?>
                  <span class="px-2 text-center text-[0.62rem] leading-tight text-muted">
                    <?= $tr ? 'yok' : 'keins' ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="w-full">
              <label class="<?= $label ?>"><?= $tr ? 'Görsel yükle' : 'Bild hochladen' ?>
                <input type="file" name="sectionsbg_end_datei" accept="image/png,image/jpeg,image/webp" class="<?= $feld ?>"></label>

              <label class="<?= $label ?> mt-4 block"><?= $tr ? 'ya da yol (boş = yok)' : 'oder Pfad (leer = keins)' ?>
                <input name="sectionsbg_end" value="<?= e((string) ($design['sectionsBgEnd'] ?? '')) ?>"
                       class="<?= $feld ?> font-mono text-[0.78rem]"></label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

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

<?php /*
   5c · Die Anordnung. Der Kasten jeder Ebene, ihre Stapelfolge und der Weg,
   eine wegzunehmen.

   Bis hierher war das die einzige Stelle, an der eine Vorlage nur ueber die
   Datenbank entstehen konnte: eine neue Ebene fiel in einen von drei festen
   Zuschnitten und liess sich danach nie wieder bewegen.

   Die Liste steht in der Reihenfolge des Dokuments, und die IST die
   Stapelfolge - Design::css() schreibt z-index als index+1. Deshalb ist die
   UNTERSTE Zeile die vorderste Ebene, und die Knoepfe heissen "nach vorn"
   und "nach hinten" statt hoch und runter: hoch und runter waeren an dieser
   Stelle zweideutig, und die Zahl daneben sagt es noch einmal.

   Loeschen nimmt die Zeile nur aus der Reihe. Gespeichert wird es erst mit
   dem Formular - ein Neuladen holt die Ebene zurueck, solange nicht
   gespeichert wurde.
*/ ?>
<?= $auf($tr ? '5c · Yerleşim ve sıra' : '5c · Anordnung und Stapel') ?>
  <p class="text-[0.78rem] leading-relaxed text-muted">
    <?= $tr
      ? 'Sayılar kartın yüzdesidir, pikseli değil: kart büyüyünce her şey birlikte büyür. Yazdığın an sağdaki kart oynar, kaydetmeden görürsün. Listede AŞAĞIDAKİ katman üsttedir.'
      : 'Die Zahlen sind Prozent der Karte, keine Pixel: waechst die Karte, waechst alles mit. Die Karte daneben bewegt sich sofort, ohne Speichern. Die UNTERSTE Zeile ist die vorderste Ebene.' ?>
  </p>

  <?php if ($design['layers'] === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu tasarımda katman yok.' : 'Diese Vorlage hat keine Ebene.' ?></p>
  <?php endif; ?>

  <?php
  /*
    Die Reihe der Kennungen. Sie ist die Wahrheit ueber Ordnung UND Bestand:
    fromPost() baut die Ebenenliste daraus. Das Skript schreibt sie neu, wenn
    jemand schiebt oder loescht; ohne Skript bleibt sie stehen, wie sie ist,
    und dann aendert sich nichts - kein stiller Verlust.
  */
  $reihe = implode(',', array_map(static fn (array $l): string => (string) $l['id'], $design['layers']));

  $masse = [
      'x'       => [$tr ? 'x %' : 'x %',            -50, 150],
      'y'       => [$tr ? 'y %' : 'y %',            -50, 150],
      'w'       => [$tr ? 'en %' : 'Breite %',        1, 200],
      'h'       => [$tr ? 'boy %' : 'Höhe %',         0, 200],
      'rotate'  => [$tr ? 'açı °' : 'Drehung °',   -180, 180],
      'opacity' => [$tr ? 'saydam %' : 'Deckkraft %', 0, 100],
  ];

  $ankerNamen = [
      'topleft'     => $tr ? 'sol üst'  : 'oben links',
      'topright'    => $tr ? 'sağ üst'  : 'oben rechts',
      'bottomleft'  => $tr ? 'sol alt'  : 'unten links',
      'bottomright' => $tr ? 'sağ alt'  : 'unten rechts',
  ];

  $klein = 'mt-1 block w-full border border-sand-deep bg-transparent px-2 py-1.5 text-sm text-ink';
  $knopf = 'border border-sand-deep px-2 py-1 text-[0.66rem] uppercase tracking-[0.14em] text-muted hover:text-ink';
  ?>

  <input type="hidden" name="ebenen_reihenfolge" value="<?= e($reihe) ?>" data-ebenen-reihe>

  <div data-ebenen-liste class="space-y-3">
    <?php foreach ($design['layers'] as $nr => $ebene) : ?>
      <div class="border border-sand-deep p-3" data-ebene="<?= e((string) $ebene['id']) ?>">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-sm text-ink">
            <?= e($ebene['label'] ?: $ebene['id']) ?>
            <span class="<?= $label ?>">· <?= e((string) $ebene['type']) ?> · <?= e((string) $ebene['spot']) ?></span>
          </span>
          <div class="flex items-center gap-1">
            <span class="<?= $label ?>" data-ebene-stufe>z <?= (int) $nr + 1 ?></span>
            <button type="button" class="<?= $knopf ?>" data-ebene-hinten title="<?= $tr ? 'arkaya' : 'nach hinten' ?>">↑</button>
            <button type="button" class="<?= $knopf ?>" data-ebene-vorn title="<?= $tr ? 'öne' : 'nach vorn' ?>">↓</button>
            <button type="button" class="<?= $knopf ?>" data-ebene-weg
                    data-wort-weg="<?= $tr ? 'sil' : 'weg' ?>" data-wort-zurueck="<?= $tr ? 'geri al' : 'zurück' ?>"><?= $tr ? 'sil' : 'weg' ?></button>
          </div>
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-4">
          <?php foreach ($masse as $mass => [$titel, $min, $max]) : ?>
            <label class="<?= $label ?>"><?= e($titel) ?>
              <input type="number" name="box_<?= e($mass) ?>_<?= e((string) $ebene['id']) ?>"
                     value="<?= (int) $ebene['box'][$mass] ?>" min="<?= (int) $min ?>" max="<?= (int) $max ?>"
                     class="<?= $klein ?>"
                     data-kasten="<?= e((string) $ebene['id']) ?>" data-mass="<?= e($mass) ?>"></label>
          <?php endforeach; ?>

          <?php if ($ebene['type'] === 'text') : ?>
            <?php /*
              Die Groesse DIESER Zeile - Zehntelprozent der Kartenbreite, wie
              Design::css() sie schreibt. Bis hierher stand die Zahl im
              Dokument und wurde gedruckt, war aber nirgends zu erreichen:
              eine Ebene liess sich verschieben, drehen und faerben, nur nicht
              groesser machen. Die Marke oben skaliert alles in ihrer Schrift
              auf einmal, hier steht die einzelne Zeile.
            */ ?>
            <label class="<?= $label ?>"><?= $tr ? 'yazı büyüklüğü' : 'Schriftgröße' ?>
              <input type="number" name="style_size_<?= e((string) $ebene['id']) ?>"
                     value="<?= (int) $ebene['style']['size'] ?>" min="1" max="500"
                     class="<?= $klein ?>"
                     data-schriftgroesse="<?= e((string) $ebene['id']) ?>"
                     data-schriftmarke="<?= e((string) $ebene['style']['font']) ?>"></label>
          <?php endif; ?>

          <label class="<?= $label ?>"><?= $tr ? 'çapa' : 'Anker' ?>
            <select name="box_anchor_<?= e((string) $ebene['id']) ?>" class="<?= $klein ?>"
                    data-kasten="<?= e((string) $ebene['id']) ?>" data-mass="anchor">
              <?php foreach (Design::ANCHORS as $anker) : ?>
                <option value="<?= e($anker) ?>" <?= $ebene['box']['anchor'] === $anker ? 'selected' : '' ?>>
                  <?= e($ankerNamen[$anker] ?? $anker) ?></option>
              <?php endforeach; ?>
            </select></label>

          <div class="flex items-end gap-3">
            <label class="flex items-center gap-2 text-[0.66rem] text-muted">
              <input type="checkbox" name="box_flipx_<?= e((string) $ebene['id']) ?>" <?= $ebene['box']['flipx'] ? 'checked' : '' ?>
                     data-kasten="<?= e((string) $ebene['id']) ?>" data-mass="flipx">
              <?= $tr ? 'yatay çevir' : 'spiegeln ↔' ?></label>
            <label class="flex items-center gap-2 text-[0.66rem] text-muted">
              <input type="checkbox" name="box_flipy_<?= e((string) $ebene['id']) ?>" <?= $ebene['box']['flipy'] ? 'checked' : '' ?>
                     data-kasten="<?= e((string) $ebene['id']) ?>" data-mass="flipy">
              <?= $tr ? 'dikey çevir' : 'spiegeln ↕' ?></label>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?= $zu ?>

<?= $auf($tr ? '6 · Animasyon' : '6 · Bewegung') ?>
  <?php
  $achsen = [
      'anim_intro'    => [$tr ? 'Giriş' : 'Auftakt',      Themes::INTROS,                 (string) $design['animation']['intro']],
      'anim_idle'     => [$tr ? 'Boşta' : 'Ruhe',         Themes::IDLES,                  (string) $design['animation']['idle']],
      'anim_card'     => [$tr ? 'Kart' : 'Karte',         Themes::ANIMATIONS, (string) $design['animation']['card']],
      'anim_name'     => [$tr ? 'İsimler' : 'Namen',      Themes::NAME_ANIMATIONS,        (string) $design['animation']['nameMove']],
      'anim_particle' => [$tr ? 'Partikül' : 'Teilchen',  Themes::PARTICLES,              (string) $design['animation']['particle']],
      'anim_reveal'   => [$tr ? 'Açılış' : 'Enthüllung',  Themes::REVEALS,                (string) $design['animation']['reveal']],
  ];
  ?>
  <?php /*
     Was gespeichert ist, steht zur Wahl - auch wenn der Katalog es nicht mehr
     kennt.

     Gefunden am 24.08.2026 an noir: das Dokument trug idle=pulse,
     reveal=side, particle=spark, nameMove=letters und card=seal. Die Listen
     hier boten je zwei bis drei Woerter, keines davon war der gespeicherte
     Wert - und ein <select> ohne passende Option waehlt die erste. Einmal
     oeffnen, einmal speichern, und die ganze Bewegung einer Vorlage war eine
     andere. Sichtbar erst auf der Seite, nie hier.

     Die schmalen Listen sind Absicht (siehe Themes: "Weniger, mit Absicht" -
     der Kunde wollte weniger Bewegung). Deshalb wird die Liste NICHT wieder
     breit: der alte Wert erscheint nur, weil er der aktuelle ist, und
     verschwindet, sobald jemand einen anderen waehlt. Neu waehlen kann man
     ihn nicht - wegwerfen aber auch niemand aus Versehen.
  */ ?>
  <div class="grid gap-4 sm:grid-cols-3">
    <?php foreach ($achsen as $name => [$titel, $liste, $wert]) : ?>
      <label class="<?= $label ?>"><?= e($titel) ?>
        <select name="<?= e($name) ?>" class="<?= $feld ?>">
          <?php foreach (Themes::withCurrent($liste, $wert) as $option) : ?>
            <option value="<?= e((string) $option) ?>" <?= $wert === (string) $option ? 'selected' : '' ?>>
              <?= e((string) $option) ?><?= in_array((string) $option, $liste, true) ? '' : ($tr ? ' (eski)' : ' (alt)') ?></option>
          <?php endforeach; ?>
        </select></label>
    <?php endforeach; ?>
    <label class="<?= $label ?>"><?= $tr ? 'hız (ms)' : 'Tempo (ms)' ?>
      <input name="anim_speed" type="number" value="<?= (int) $design['animation']['speed'] ?>" class="<?= $feld ?>"></label>
  </div>

  <p class="<?= $label ?>"><?= $tr ? 'Katman hareketleri' : 'Bewegung je Ebene' ?></p>
  <?php foreach ($design['layers'] as $ebene) : ?>
    <div class="grid items-center gap-3 sm:grid-cols-4">
      <span class="text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
      <select name="move_<?= e($ebene['id']) ?>" class="<?= $feld ?>">
        <?php foreach (Themes::MOVES as $m) : ?>
          <option value="<?= e($m) ?>" <?= $ebene['motion']['move'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="delay_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['delay'] ?>" class="<?= $feld ?>">
      <input name="duration_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['duration'] ?>" class="<?= $feld ?>">
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?= $auf($tr ? '7 · Müşteri izinleri' : '7 · Kundenrechte') ?>
  <p class="<?= $label ?>">
    <?= $tr
        ? 'Sihirbaz bu bayrakları okur. "Düzenlenebilir" ana şalterdir: kapalıyken diğer beşi sayılmaz.'
        : 'Der Assistent liest diese Haken. „Bearbeitbar" ist der Hauptschalter: ist er aus, zählen die anderen fünf nicht.' ?>
  </p>
  <?php
  // Mapping der Berechtigungsnamen zu Etiketten in Deutsch und Türkisch
  $rechteNamen = [
      'edit'  => ['de' => 'Bearbeitbar', 'tr' => 'Düzenlenebilir'],
      'color' => ['de' => 'Farbe',       'tr' => 'Renk'],
      'font'  => ['de' => 'Schrift',     'tr' => 'Yazı tipi'],
      'photo' => ['de' => 'Bild',        'tr' => 'Görsel'],
      'text'  => ['de' => 'Text',        'tr' => 'Metin'],
      'hide'  => ['de' => 'Ausblendbar', 'tr' => 'Gizlenebilir'],
  ];
  ?>
  <?php foreach ($design['layers'] as $ebene) : ?>
    <div class="flex flex-wrap items-center gap-4 border-b border-sand-deep py-2">
      <span class="w-56 text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
      <?php foreach (Design::PERMISSIONS as $recht) : ?>
        <label class="flex items-center gap-2 text-[0.66rem] <?= $recht === 'edit' ? 'text-ink' : 'text-muted' ?>">
          <input type="checkbox" name="perm_<?= e($recht) ?>_<?= e($ebene['id']) ?>" <?= $ebene['permissions'][$recht] ? 'checked' : '' ?>>
          <?= e($rechteNamen[$recht][$tr ? 'tr' : 'de']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?php /*
   Die Abschnitte standen hier einmal als Zeilen mit je zehn Feldern
   nebeneinander - Kennung, Art, Gestalt, zwei Titel, zwei Marken, drei Haken.
   Sie sind in die linke Liste (design-edit-liste.php) und ihre Tafeln
   (design-edit-tafeln.php) gezogen: der BAU der Seite gehoert nach links,
   der INHALT eines Abschnitts nach rechts, und beides zusammen in eine
   Tabellenzeile zu pressen war der Grund, warum niemand hier schnell
   arbeiten konnte.
*/ ?>

<?= $auf($tr ? '9 · Yayın' : '9 · Veröffentlichen') ?>
  <p class="text-sm text-ink">
    <?= $tr ? 'Durum' : 'Zustand' ?>: <strong><?= e((string) $design['status']) ?></strong>
    · <?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?>
  </p>
  <?php if ($warnings === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Uyarı yok.' : 'Keine Hinweise.' ?></p>
  <?php else : ?>
    <ul class="space-y-1 text-sm text-gold">
      <?php foreach ($warnings as $w) : ?>
        <li><?= e($w['kind']) ?> — <?= e($w['element']) ?><?= $w['detail'] !== '' ? ' (' . e($w['detail']) . ')' : '' ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <p class="<?= $label ?>"><?= $tr ? 'Aktife alma katalogdan yapılır.' : 'Das Aktivieren geschieht im Katalog.' ?></p>
<?= $zu ?>
