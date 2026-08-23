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
 * Was hier NICHT steht: an/aus. Das Auge gehoert in die Liste, weil es zum
 * Bau der Seite gehoert und nicht zum Inhalt eines Abschnitts - und weil man
 * beim Umschalten die ganze Liste sehen will.
 *
 * Erwartet aus design-edit-liste.php: $sekmeler. Aus design-edit.php:
 * $design, $tr, $label, $feld.
 *
 * @var list<array<string,mixed>> $sekmeler
 */

use Atelier\DesignSections;
use Atelier\SectionRegistry;
use function Atelier\e;

$sprache = $tr ? 'tr' : 'de';
?>

<?php foreach ($sekmeler as $i => $abschnitt) : ?>
  <?php
    $art       = (string) $abschnitt['type'];
    $varianten = SectionRegistry::variants($art);
    $schema    = SectionRegistry::settings($art);
    $werte     = is_array($abschnitt['settings'] ?? null) ? $abschnitt['settings'] : [];
  ?>
  <div class="b-panel" data-panel="sec-<?= $i ?>" hidden>

    <div class="b-falte" open>
      <div class="b-falte-inhalt">
        <div class="b-gruppe b-zwei">
          <label class="<?= $label ?>"><?= $tr ? 'kimlik' : 'Kennung' ?>
            <input class="<?= $feld ?>" name="sec_id_<?= $i ?>"
                   value="<?= e((string) $abschnitt['id']) ?>"
                   placeholder="<?= $tr ? 'ör. ablauf' : 'z. B. ablauf' ?>"></label>

          <label class="<?= $label ?>"><?= $tr ? 'tür' : 'Art' ?>
            <select class="<?= $feld ?>" name="sec_type_<?= $i ?>">
              <option value=""><?= $tr ? '— yok —' : '— keine —' ?></option>
              <?php foreach (DesignSections::TYPES as $typ) : ?>
                <option value="<?= e($typ) ?>" <?= $art === $typ ? 'selected' : '' ?>><?= e($typ) ?></option>
              <?php endforeach; ?>
            </select></label>
        </div>

        <?php /*
           Die Gestalt kommt aus dem Katalog, nicht von hier: eine Liste im
           Panel und eine im Code waeren zwei Wahrheiten, und die im Panel
           gewinnt beim Ansehen, waehrend die im Code beim Drucken gewinnt.
           So entstehen Knoepfe, die nichts tun.

           Was ein Abschnitt anbieten kann, haengt an seiner ART. Eine frische
           Zeile hat noch keine - Kennung und Art eintragen, speichern, dann
           steht es da. Derselbe Weg wie bei einer neuen Ebene.
        */ ?>
        <?php if (count($varianten) > 1) : ?>
          <label class="<?= $label ?>"><?= $tr ? 'görünüm' : 'Gestalt' ?>
            <select class="<?= $feld ?>" name="sec_variant_<?= $i ?>" data-sec-gestalt="<?= $i ?>">
              <?php foreach ($varianten as $kennung => $etikett) : ?>
                <option value="<?= e((string) $kennung) ?>"
                  <?= (string) ($abschnitt['variant'] ?? 'default') === (string) $kennung ? 'selected' : '' ?>>
                  <?= e($etikett[$sprache] ?? (string) $kennung) ?></option>
              <?php endforeach; ?>
            </select></label>
        <?php elseif ($art !== '') : ?>
          <?php /*
             Genau eine Gestalt: das Feld trotzdem mitschicken, sonst faellt
             sie beim naechsten Speichern still auf die Voreinstellung - was
             heute dasselbe waere, aber nicht mehr, sobald diese Art eine
             zweite Gestalt bekommt.
          */ ?>
          <input type="hidden" name="sec_variant_<?= $i ?>"
                 value="<?= e((string) ($abschnitt['variant'] ?? 'default')) ?>">
        <?php endif; ?>

        <div class="b-gruppe b-zwei">
          <label class="<?= $label ?>"><?= $tr ? 'başlık DE' : 'Titel DE' ?>
            <input class="<?= $feld ?>" name="sec_title_de_<?= $i ?>"
                   value="<?= e((string) $abschnitt['title']['de']) ?>" data-sec-titel="<?= $i ?>"></label>
          <label class="<?= $label ?>"><?= $tr ? 'başlık EN' : 'Titel EN' ?>
            <input class="<?= $feld ?>" name="sec_title_en_<?= $i ?>"
                   value="<?= e((string) $abschnitt['title']['en']) ?>"></label>
        </div>

        <?php foreach ($schema as $schluessel => $s) : ?>
          <?php if ((string) $s['type'] === 'bool') : ?>
            <label class="flex items-center gap-2 text-[0.66rem] text-muted">
              <input type="checkbox" name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>"
                     <?= ($werte[$schluessel] ?? $s['default']) ? 'checked' : '' ?>>
              <?= e($s['label'][$sprache] ?? (string) $schluessel) ?></label>
          <?php elseif ((string) $s['type'] === 'select') : ?>
            <label class="<?= $label ?>"><?= e($s['label'][$sprache] ?? (string) $schluessel) ?>
              <select class="<?= $feld ?>" name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>">
                <?php foreach ($s['options'] as $option) : ?>
                  <option value="<?= e((string) $option) ?>"
                    <?= (string) ($werte[$schluessel] ?? $s['default']) === (string) $option ? 'selected' : '' ?>>
                    <?= e((string) $option) ?></option>
                <?php endforeach; ?>
              </select></label>
          <?php endif; ?>
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
           Die Rechte des Kunden. edit ist der Hauptschalter, wie bei den
           Ebenen: ist er aus, zaehlt hide nicht - so ist Sperren ein Haken
           und nicht zwei.
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

  </div>
<?php endforeach; ?>
