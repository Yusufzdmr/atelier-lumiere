<?php
/**
 * Die Tafel eines Abschnitts: was er sagt, wie er aussieht, was der Kunde
 * daran darf.
 *
 * Eine Tafel je Zeile der linken Liste, alle im Markup, sichtbar hoechstens
 * eine. Der Server rendert sie einmal; das Skript blendet um. Kein zweiter
 * Endpunkt, kein Nachladen - und beim Absenden geht alles gemeinsam mit,
 * auch was gerade nicht zu sehen ist. Genau deshalb verliert das Umschalten
 * nichts.
 *
 * Vorher standen dieselben Felder als Zeile mit zehn Spalten nebeneinander.
 * Das war lesbar fuer den, der sie geschrieben hat.
 *
 * ALLES steht im Markup, auch was zur gewaehlten Art gerade nicht passt: die
 * Gestalten aller Arten, die Einstellungen aller Arten. Das Skript blendet
 * aus, was nicht dazugehoert. Der Grund ist derselbe wie bei den Tafeln
 * selbst - so aendert sich die Art OHNE Speichern, und der Katalog bleibt
 * die einzige Quelle. Und es ist gefahrlos: fromPost() liest nur die
 * Schluessel, die zur tatsaechlich gewaehlten Art gehoeren; ein
 * mitgeschicktes sec_set_map_3 an einem Countdown faellt still weg.
 *
 * Was hier NICHT steht: an/aus. Das Auge gehoert in die Liste, weil es zum
 * Bau der Seite gehoert und nicht zum Inhalt eines Abschnitts - und weil man
 * beim Umschalten die ganze Liste sehen will.
 *
 * Erwartet aus design-edit-liste.php: $sekmeler, $neuIndex. Aus
 * design-edit.php: $design, $tr, $label, $feld.
 *
 * @var list<array<string,mixed>> $sekmeler
 */

use Atelier\DesignSections;
use Atelier\SectionRegistry;
use Atelier\View;
use function Atelier\e;

$sprache = $tr ? 'tr' : 'de';
$katalog = SectionRegistry::all();
$gemein  = SectionRegistry::commonSettings();

/*
 * Der Katalog als JSON, einmal. Das Skript baut daraus die Gestaltliste neu,
 * wenn jemand die Art wechselt. Eine zweite Liste im Skript waere eine
 * zweite Wahrheit - und die im Skript gewinnt beim Ansehen, waehrend die im
 * PHP beim Drucken gewinnt.
 */
$fuerSkript = [];
foreach ($katalog as $art => $eintrag) {
    $fuerSkript[$art] = [];
    foreach ($eintrag['variants'] as $kennung => $etikett) {
        $fuerSkript[$art][(string) $kennung] = (string) ($etikett[$sprache] ?? $kennung);
    }
}
?>

<script type="application/json" data-sec-katalog>
  <?= json_encode($fuerSkript, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<?php foreach ($sekmeler as $i => $abschnitt) : ?>
  <?php
    $neu       = $i === $neuIndex;
    $art       = (string) $abschnitt['type'];
    $gestalt   = (string) ($abschnitt['variant'] ?? 'default');
    $varianten = SectionRegistry::variants($art);
    $werte     = is_array($abschnitt['settings'] ?? null) ? $abschnitt['settings'] : [];
  ?>
  <div class="b-panel" data-panel="sec-<?= $i ?>" hidden>
    <div class="b-falte-offen">

      <?php if ($neu) : ?>
        <?php /*
           Zwei Schritte, nicht ein leeres Feld: erst WAS der Abschnitt zeigt,
           dann WIE er aussieht. Wer hier eine Kennung tippen musste, bevor
           irgendetwas zu sehen war, hat die Liste der Arten im Kopf gebraucht -
           und die steht im Katalog, nicht im Kopf.
        */ ?>
        <div class="b-gruppe">
          <span class="<?= $label ?>"><?= $tr ? 'ne göstersin?' : 'Was soll er zeigen?' ?></span>
          <div class="b-karten">
            <?php foreach ($katalog as $kArt => $kEintrag) : ?>
              <?php /*
                 Der Name der Art, und darunter, was hineingehoert.

                 Hier stand die rohe Kennung und darunter der Name der
                 Voreinstellungs-Gestalt. Beides half nicht: "gift" sagte
                 nicht, dass es um eine Kontonummer geht, und "Sade"
                 beschrieb eine Gestalt, die man erst waehlt, wenn man die
                 Art schon verstanden hat. Der Hinweis beantwortet die Frage,
                 die an dieser Stelle wirklich gestellt wird.
              */ ?>
              <button type="button" class="b-karte" data-sec-art="<?= e((string) $kArt) ?>" data-fuer="<?= $i ?>">
                <?= e(SectionRegistry::typeLabel((string) $kArt, $sprache) ?: (string) $kArt) ?>
                <small><?= e(SectionRegistry::typeHint((string) $kArt, $sprache)) ?></small>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="b-gruppe b-zwei">
        <label class="<?= $label ?>"><?= $tr ? 'kimlik' : 'Kennung' ?>
          <input class="<?= $feld ?>" name="sec_id_<?= $i ?>"
                 value="<?= e((string) $abschnitt['id']) ?>"
                 data-sec-kennung="<?= $i ?>"
                 placeholder="<?= $tr ? 'ör. ablauf' : 'z. B. ablauf' ?>"></label>

        <label class="<?= $label ?>"><?= $tr ? 'tür' : 'Art' ?>
          <select class="<?= $feld ?>" name="sec_type_<?= $i ?>" data-sec-art-feld="<?= $i ?>">
            <option value="" <?= $art === '' ? 'selected' : '' ?>><?= $tr ? '— yok —' : '— keine —' ?></option>
            <?php /* Der WERT bleibt die Kennung - sie steht im Dokument und in
                     jeder verschickten Einladung. Uebersetzt wird die Zeile,
                     die der Grafiker liest. */ ?>
            <?php foreach (DesignSections::TYPES as $typ) : ?>
              <option value="<?= e($typ) ?>" <?= $art === $typ ? 'selected' : '' ?>>
                <?= e(SectionRegistry::typeLabel($typ, $sprache) ?: $typ) ?></option>
            <?php endforeach; ?>
          </select></label>
      </div>

      <?php /*
         Die Gestalt steht IMMER als Auswahl da, auch wenn die Art nur eine
         kennt oder noch keine gewaehlt ist. Sonst muesste das Skript ein Feld
         erfinden, sobald jemand die Art wechselt - und ein Feld, das es beim
         Absenden nur manchmal gibt, ist genau die Sorte Unterschied, die man
         erst am fehlenden Wert bemerkt.
      */ ?>
      <label class="<?= $label ?>"><?= $tr ? 'görünüm' : 'Gestalt' ?>
        <select class="<?= $feld ?>" name="sec_variant_<?= $i ?>" data-sec-gestalt="<?= $i ?>">
          <?php if ($varianten === []) : ?>
            <option value="default" selected>default</option>
          <?php else : ?>
            <?php foreach ($varianten as $kennung => $etikett) : ?>
              <option value="<?= e((string) $kennung) ?>" <?= $gestalt === (string) $kennung ? 'selected' : '' ?>>
                <?= e($etikett[$sprache] ?? (string) $kennung) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select></label>

      <div class="b-gruppe b-zwei">
        <label class="<?= $label ?>"><?= $tr ? 'başlık DE' : 'Titel DE' ?>
          <input class="<?= $feld ?>" name="sec_title_de_<?= $i ?>"
                 value="<?= e((string) $abschnitt['title']['de']) ?>" data-sec-titel="<?= $i ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'başlık EN' : 'Titel EN' ?>
          <input class="<?= $feld ?>" name="sec_title_en_<?= $i ?>"
                 value="<?= e((string) $abschnitt['title']['en']) ?>"></label>
      </div>

      <?php /*
         Was in diesem Abschnitt STEHEN KOENNTE.

         Der Titel gehoert der Vorlage, der Text dem Paar - das war die
         Trennung, und sie war zu scharf: der Grafiker baute eine Ueberschrift
         ueber nichts und sah im Schaufenster einen Platzhalter, den er nicht
         aendern konnte.

         Es bleibt eine Voreinstellung. Schreibt das Paar etwas, gewinnt das
         Paar; laesst es das Feld leer, steht wieder das hier. Ein Wert, den
         niemand ueberschreiben kann, waere ein fester Text - und dann braucht
         der Abschnitt kein Recht "bearbeitbar" mehr.

         Bilder haben keine Voreinstellung: die Fotos eines fremden Paares
         stuenden sonst in jeder Einladung.
      */ ?>
      <?php $eingaben = SectionRegistry::inputs($art); ?>
      <?php if ($eingaben !== []) : ?>
        <div class="b-gruppe">
          <span class="<?= $label ?>"><?= $tr ? 'varsayılan içerik' : 'Voreinstellung' ?></span>
          <?php foreach ($eingaben as $schluessel => $s) : ?>
            <?php /*
               Dateien haben keine Voreinstellung, die man tippen koennte.

               Bei Bildern stand das schon hier; beim Lied fehlte es und das
               Formular bot dem Grafiker ein Textfeld "Şarkınız" an. Was er
               dort hineinschreibt, landet in defaults['track'] - und
               DesignSections::inhalt() greift auf defaults zurueck, wenn das
               Paar nichts hochgeladen hat. Ein getippter Satz waere damit
               ein Dateipfad geworden. Die Voreinstellung einer Tonspur hat
               ihren eigenen Platz: die Einstellung "Tasarımın ses dosyası"
               weiter oben, die durch safeSrc geht.
            */ ?>
            <?php if (in_array((string) $s['type'], ['photos', 'audio'], true)) { continue; } ?>
            <?php $vorgabe = (string) ($abschnitt['defaults'][$schluessel] ?? ''); ?>
            <label class="<?= $label ?>"><?= e($s['label'][$sprache] ?? $s['label']['de'] ?? (string) $schluessel) ?>
              <?php if ((string) $s['type'] === 'textarea') : ?>
                <textarea class="<?= $feld ?>" rows="3"
                          name="sec_def_<?= e((string) $schluessel) ?>_<?= $i ?>"
                          maxlength="<?= (int) $s['max'] ?>"><?= e($vorgabe) ?></textarea>
              <?php else : ?>
                <input class="<?= $feld ?>" name="sec_def_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       maxlength="<?= (int) $s['max'] ?>" value="<?= e($vorgabe) ?>">
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php /* Was jede Art hat. Dieselbe Vorlage wie unten - eine Art von
               Feld wird an einer Stelle gebaut, nicht an zweien. */ ?>
      <?php foreach ($gemein as $schluessel => $s) : ?>
        <?= View::partial('partials/einstellung-feld', compact(
            'schluessel', 's', 'werte', 'i', 'sprache', 'tr', 'label', 'feld'
        )) ?>
      <?php endforeach; ?>

      <?php /* Und was nur eine bestimmte Art hat. */ ?>
      <?php foreach ($katalog as $kArt => $kEintrag) : ?>
        <?php foreach ($kEintrag['settings'] as $schluessel => $s) : ?>
          <?php if (array_key_exists($schluessel, $gemein)) { continue; } ?>
          <div data-fuer-art="<?= e((string) $kArt) ?>" <?= $art === (string) $kArt ? '' : 'hidden' ?>>
            <?= View::partial('partials/einstellung-feld', compact(
                'schluessel', 's', 'werte', 'i', 'sprache', 'tr', 'label', 'feld'
            )) ?>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <div class="b-gruppe b-zwei">
        <label class="<?= $label ?>"><?= $tr ? 'renk markası' : 'Farbmarke' ?>
          <input class="<?= $feld ?>" name="sec_color_<?= $i ?>"
                 value="<?= e((string) $abschnitt['style']['color']) ?>" placeholder="accent"></label>
        <label class="<?= $label ?>"><?= $tr ? 'yazı markası' : 'Schriftmarke' ?>
          <input class="<?= $feld ?>" name="sec_font_<?= $i ?>"
                 value="<?= e((string) $abschnitt['style']['font']) ?>" placeholder="body"></label>
      </div>
      <?php /*
         Das eigene Blatt des Abschnitts.

         Es steht hier bei Farbe und Schrift und nicht bei den Einstellungen
         der Art: ein Blatt hat jeder Abschnitt, ein Kartenhaken nur der Ort.

         Zwei Passungen, und die Voreinstellung ist die des grossen Blattes -
         Breite an der Karte, nach unten wiederholt. Der Grund steht in
         DesignSections::baseline(): mit cover und einer Bindung ans Fenster
         kam dasselbe Blatt unten fast doppelt so gross heraus wie oben, und
         genau das ist einmal aufgefallen.
      */ ?>
      <?php $blatt = $abschnitt['style']; ?>
      <div class="b-gruppe">
        <div class="flex items-start gap-4">
          <div class="w-16 shrink-0">
            <div class="flex aspect-square items-center justify-center overflow-hidden border border-sand-deep bg-sand">
              <?php if ($blatt['bg'] !== '') : ?>
                <img src="<?= e($blatt['bg']) ?>" alt="" class="h-full w-full object-cover">
              <?php else : ?>
                <span class="px-1 text-center text-[0.58rem] leading-tight text-muted">
                  <?= $tr ? 'arka plan yok' : 'kein Blatt' ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="w-full">
            <label class="<?= $label ?>"><?= $tr ? 'arka plan yükle' : 'Blatt hochladen' ?>
              <input type="file" name="sec_bg_datei_<?= $i ?>" class="<?= $feld ?>"
                     accept="image/png,image/jpeg,image/webp,image/svg+xml"></label>

            <label class="<?= $label ?> mt-3 block"><?= $tr ? 'ya da yol (boş bırakınca kalkar)' : 'oder Pfad (leer entfernt es)' ?>
              <input class="<?= $feld ?> font-mono text-[0.78rem]" name="sec_bg_<?= $i ?>"
                     value="<?= e((string) $blatt['bg']) ?>"></label>

            <label class="<?= $label ?> mt-3 block"><?= $tr ? 'nasıl otursun' : 'wie es sitzt' ?>
              <select class="<?= $feld ?>" name="sec_bgfit_<?= $i ?>">
                <option value="blatt" <?= $blatt['bgFit'] === 'blatt' ? 'selected' : '' ?>>
                  <?= $tr ? 'kart genişliğinde, aşağı tekrarlı' : 'Breite der Karte, nach unten wiederholt' ?></option>
                <option value="cover" <?= $blatt['bgFit'] === 'cover' ? 'selected' : '' ?>>
                  <?= $tr ? 'bölümü kaplasın' : 'füllt den Abschnitt' ?></option>
              </select></label>
          </div>
        </div>
      </div>


      <?php /*
         Der freie Schmuck dieses Abschnitts.

         "Gerektiginde ayni bolume birden fazla gorsel veya video elementi
         ekleyebilmeliyim." Am Countdown gibt es das (3d); hier ist dasselbe
         fuer jeden Abschnitt - Bild oder Film, so viele wie noetig, jedes
         mit eigener Groesse, Verschiebung, Abstand und Ebene.

         Am ABSCHNITT und nicht an der Art: zwei Textbloecke derselben
         Vorlage duerfen verschieden geschmueckt sein.

         Eine leere Zeile steht immer unten, damit auch ohne Skript etwas
         entstehen kann; der Knopf daneben klont sie. Geloescht wird durch
         Leeren des Pfads - ein eigener Knopf waere ein zweiter Zustand im
         Formular, und einer, der bis zum Speichern luegt.
      */ ?>
      <?php
        $dekoZeilen = is_array($abschnitt['deko'] ?? null) ? $abschnitt['deko'] : [];
        $dekoZeilen[] = ['src' => '', 'video' => '', 'anchor' => 'titel', 'side' => 'nach',
                         'size' => 100, 'x' => 0, 'y' => 0, 'gap' => 0, 'z' => 0];
        $dekoAnker = ['titel' => ['de' => 'Überschrift', 'tr' => 'Başlık'],
                      'inhalt' => ['de' => 'Inhalt', 'tr' => 'İçerik']];
      ?>
      <div class="b-gruppe">
        <div class="flex items-center justify-between gap-3">
          <span class="<?= $label ?>"><?= $tr ? 'süsleme (resim / video)' : 'Schmuck (Bild / Film)' ?></span>
          <button type="button" class="border border-sand-deep px-2 py-1 text-[0.66rem] uppercase tracking-[0.14em] text-muted hover:text-ink"
                  data-cd-mehr="deko_<?= $i ?>">+</button>
        </div>

        <input type="hidden" name="sec_deko_n_<?= $i ?>" value="<?= count($dekoZeilen) ?>"
               data-cd-zahl="deko_<?= $i ?>">

        <div class="mt-2 space-y-3" data-cd-liste="deko_<?= $i ?>">
          <?php foreach (array_values($dekoZeilen) as $d => $dk) : ?>
            <?php
              $dPfad = (string) (($dk['video'] ?? '') !== '' ? $dk['video'] : ($dk['src'] ?? ''));
              $dn = 'sec_deko_' . $i . '_' . $d . '_';
            ?>
            <div class="space-y-2" data-cd-zeile>
              <div class="b-gruppe b-zwei">
                <label class="<?= $label ?>"><?= $tr ? 'dosya' : 'Datei' ?>
                  <input type="file" class="<?= $feld ?>" name="sec_dekodatei_<?= $i ?>_<?= $d ?>"
                         accept="image/png,image/webp,image/svg+xml,image/jpeg,video/mp4,video/webm"></label>
                <label class="<?= $label ?>"><?= $tr ? 'yol (boş = yok)' : 'Pfad (leer = keiner)' ?>
                  <input class="<?= $feld ?> font-mono text-[0.78rem]"
                         name="<?= $dn ?>src" value="<?= e($dPfad) ?>"></label>
              </div>

              <div class="b-gruppe b-zwei">
                <label class="<?= $label ?>"><?= $tr ? 'nereye' : 'woran' ?>
                  <select class="<?= $feld ?>" name="<?= $dn ?>anchor">
                    <?php foreach ($dekoAnker as $wert => $wort) : ?>
                      <option value="<?= e((string) $wert) ?>" <?= ($dk['anchor'] ?? '') === $wert ? 'selected' : '' ?>>
                        <?= e($wort[$sprache] ?? (string) $wert) ?></option>
                    <?php endforeach; ?>
                  </select></label>
                <label class="<?= $label ?>"><?= $tr ? 'hangi yanına' : 'welche Seite' ?>
                  <select class="<?= $feld ?>" name="<?= $dn ?>side">
                    <option value="vor" <?= ($dk['side'] ?? '') === 'vor' ? 'selected' : '' ?>>
                      <?= $tr ? 'öncesine' : 'davor' ?></option>
                    <option value="nach" <?= ($dk['side'] ?? 'nach') !== 'vor' ? 'selected' : '' ?>>
                      <?= $tr ? 'sonrasına' : 'dahinter' ?></option>
                  </select></label>
              </div>

              <div class="b-gruppe b-fuenf">
                <label class="<?= $label ?>"><?= $tr ? 'boyut' : 'Grösse' ?>
                  <input type="number" min="10" max="2000" class="<?= $feld ?>"
                         name="<?= $dn ?>size" value="<?= (int) ($dk['size'] ?? 100) ?>"></label>
                <label class="<?= $label ?>">X
                  <input type="number" min="-400" max="400" class="<?= $feld ?>"
                         name="<?= $dn ?>x" value="<?= (int) ($dk['x'] ?? 0) ?>"></label>
                <label class="<?= $label ?>">Y
                  <input type="number" min="-400" max="400" class="<?= $feld ?>"
                         name="<?= $dn ?>y" value="<?= (int) ($dk['y'] ?? 0) ?>"></label>
                <label class="<?= $label ?>"><?= $tr ? 'mesafe' : 'Abstand' ?>
                  <input type="number" min="0" max="400" class="<?= $feld ?>"
                         name="<?= $dn ?>gap" value="<?= (int) ($dk['gap'] ?? 0) ?>"></label>
                <label class="<?= $label ?>"><?= $tr ? 'katman' : 'Ebene' ?>
                  <input type="number" min="-5" max="5" class="<?= $feld ?>"
                         name="<?= $dn ?>z" value="<?= (int) ($dk['z'] ?? 0) ?>"></label>
              </div>

              <?php if ($dPfad !== '') : ?>
                <img class="h-6 w-6 shrink-0 object-contain" src="<?= e($dPfad) ?>" alt="">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php /*
         Die Rechte des Kunden. edit ist der Hauptschalter, wie bei den
         Ebenen: ist er aus, zaehlt hide nicht - so ist Sperren ein Haken und
         nicht zwei.
      */ ?>
      <div class="b-gruppe">
        <span class="<?= $label ?>"><?= $tr ? 'müşteri hakları' : 'Kundenrechte' ?></span>
        <label class="flex items-center gap-2 text-[0.66rem] text-ink">
          <input type="checkbox" name="perm_sec_edit_<?= $i ?>" <?= $abschnitt['permissions']['edit'] ? 'checked' : '' ?>>
          <?= $tr ? 'Düzenlenebilir' : 'Bearbeitbar' ?></label>
        <label class="flex items-center gap-2 text-[0.66rem] text-muted">
          <input type="checkbox" name="perm_sec_hide_<?= $i ?>" <?= $abschnitt['permissions']['hide'] ? 'checked' : '' ?>>
          <?= $tr ? 'Gizlenebilir' : 'Ausblendbar' ?></label>
      </div>

    </div>
  </div>
<?php endforeach; ?>
