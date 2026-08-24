<?php
/**
 * Die acht Ablauf-Zeilen - geteilt vom Assistenten und vom
 * Bearbeiten-Bildschirm.
 *
 * Erwartet im Gueltigkeitsbereich: $old, $t, $field, $locale.
 * Dieselbe Uebergabe-Art wie bei admin/design-edit-sections.php: der Baustein
 * liest, was die Seite ohnehin schon berechnet hat, statt es ein zweites Mal
 * herzuleiten.
 *
 * Warum geteilt: der Assistent zeigte alle acht Zeilen flach, der
 * Bearbeiten-Bildschirm faltete die leeren weg - dieselbe Aufgabe, zwei
 * Antworten, und die schlechtere stand ausgerechnet dort, wo das Paar zum
 * ersten Mal hinschaut.
 */

use Atelier\SectionRegistry;
use function Atelier\e;
?>
<?php
/*
 * Wie viele Zeilen stehen offen? Sichtbar sind die ausgefuellten und eine
 * leere zum Weiterschreiben; der Rest wandert hinter das <details>. Die
 * Rechnung steht hier und nicht bei den Aufrufern: sie gehoert zu diesen
 * Zeilen, und zweimal gefuehrt liefe sie irgendwann auseinander.
 */
$progLetzteVoll = -1;
for ($z = 0; $z < 8; $z++) {
    if ($old('prog_time_' . $z) !== '' || $old('prog_title_' . $z) !== ''
        || $old('prog_icon_' . $z) !== '') {
        $progLetzteVoll = $z;
    }
}
$progOffen = min(7, $progLetzteVoll + 1);
?>
<?php /*
   Acht Zeilen, immer - der Schreibpfad liest alle acht Namen, ein
   Hinzufuegen-Knopf funktioniert ohne Skript ohnehin nicht (wie im
   Assistenten). Repariert wird aber selten mit acht leeren
   Feldern vor Augen: sichtbar sind die ausgefuellten Zeilen und
   eine leere zum Weiterschreiben ($progOffen, oben berechnet),
   der Rest steckt in einem <details> - reines HTML, offen oder
   zu, ohne Skript. Ein geschlossenes <details> versteckt nur den
   Anblick; die Werte darin reisen beim Absenden trotzdem mit,
   genau wie jedes andere unsichtbare Feld.
*/ ?>
<?php for ($z = 0; $z < 8; $z++) : ?>
  <?php if ($z === $progOffen + 1) : ?>
    <details class="wz-more">
      <summary>
        <?= (int) (8 - $z) ?> <?= e($locale === 'de' ? 'weitere Zeilen' : 'more rows') ?>
      </summary>
      <div class="wz-more-body">
  <?php endif; ?>
  <?php /*
     Drei Felder statt zwei: Uhrzeit, Art, eigener Titel.

     Die Art ist eine Liste und kein Textfeld - genau das ist der Punkt. Wer
     "Torte" auswaehlt, bekommt das Zeichen dazu und muss nichts schreiben:
     bleibt der Titel leer, druckt der Katalog seinen Vorschlag. Wer etwas
     hineinschreibt, gewinnt - dieselbe Regel wie bei den Voreinstellungen
     eines Abschnitts.

     Die Etiketten sind die gedruckten Titel und nicht die Namen aus dem
     Panel: das Paar waehlt "Tortenanschnitt", nicht "Torte".
  */ ?>
  <div class="mt-3 grid gap-3 sm:grid-cols-[5rem_10rem_1fr]">
    <input name="prog_time_<?= $z ?>" class="<?= $field ?>" maxlength="80"
           placeholder="<?= e($t('programTime')) ?>" value="<?= e($old('prog_time_' . $z)) ?>">

    <select name="prog_icon_<?= $z ?>" class="<?= $field ?>">
      <option value=""><?= e($locale === 'de' ? '— ohne Zeichen —' : '— no icon —') ?></option>
      <?php foreach (SectionRegistry::icons() as $kennung => $eintrag) : ?>
        <option value="<?= e((string) $kennung) ?>"
                <?= $old('prog_icon_' . $z) === (string) $kennung ? 'selected' : '' ?>>
          <?= e(SectionRegistry::iconTitle((string) $kennung, $locale)) ?></option>
      <?php endforeach; ?>
    </select>

    <input name="prog_title_<?= $z ?>" class="<?= $field ?>" maxlength="80"
           placeholder="<?= e($t('programTitle')) ?>" value="<?= e($old('prog_title_' . $z)) ?>">
  </div>
<?php endfor; ?>
<?php if ($progOffen < 7) : ?>
      </div>
    </details>
<?php endif; ?>
