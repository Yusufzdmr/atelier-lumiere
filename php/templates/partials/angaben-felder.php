<?php
/**
 * Die Angaben des Paares - einmal, fuer beide Wege.
 *
 * Der Assistent und das Bearbeiten fragen dasselbe, und bis heute stand es
 * zweimal da: derselbe Block in invite-v2-wizard.php und in
 * invite-v2-edit.php. Genau die Lage, die abschnitt-felder.php schon einmal
 * aufgeloest hat - und sie ist prompt wieder eingetreten: die Ortssuche kam
 * in den Assistenten und fehlte beim Bearbeiten. Ausgerechnet dort, wo ein
 * Paar seine Adresse nachtraegt, wenn auf der fertigen Einladung keine Karte
 * stand.
 *
 * WELCHE Felder es sind, sagt DesignWizard::choices() - hier steht nur, wie
 * sie aussehen.
 *
 * @var array<string,mixed> $choices     was dieses Design anbietet
 * @var array<string,string> $fieldTitles Beschriftungen
 * @var array<string,string> $inputTypes  Feldarten (date, time)
 * @var callable $old                     frueher Eingegebenes
 * @var string $label
 * @var string $field
 * @var string $locale
 */

use function Atelier\e;
?>
<div class="grid gap-7 sm:grid-cols-2">
  <?php foreach ($choices['fields'] as $feld) : ?>
    <div<?= $feld === 'message' ? ' class="sm:col-span-2"' : '' ?>>
      <label class="<?= $label ?>" for="f-<?= e($feld) ?>"><?= e($fieldTitles[$feld]) ?></label>

      <?php if ($feld === 'message') : ?>
        <textarea id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" rows="4"
                  class="<?= $field ?>" data-live="<?= e($feld) ?>"><?= e($old($feld)) ?></textarea>
      <?php else : ?>
        <input id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" class="<?= $field ?>"
               type="<?= e($inputTypes[$feld] ?? 'text') ?>"
               value="<?= e($old($feld)) ?>" data-live="<?= e($feld) ?>"
               <?= $feld === 'address' ? 'data-ortsuche autocomplete="off"' : '' ?>
               <?= in_array($feld, ['bride', 'groom'], true) ? 'required' : '' ?>>
      <?php endif; ?>

      <?php if ($feld === 'address') : ?>
        <?php /*
           Die Adresse wird gesucht, nicht getippt.

           Vorher stand hier ein leeres Textfeld, und ob der Kartendienst die
           eingetippte Anschrift kennt, stellte sich erst auf der fertigen
           Einladung heraus - dann naemlich, wenn keine Karte kam. Das Paar
           hatte weder einen Hinweis noch eine zweite Chance.

           Jetzt fragt das Feld beim Tippen dasselbe Verzeichnis, das spaeter
           die Karte zeichnet, und legt die Treffer darunter. Was hier
           angeklickt wird, HAT eine Karte.

           Das Feld bleibt ein gewoehnliches Textfeld: ohne Skript tippt man
           weiter wie bisher, und wer einen Ort eintraegt, den das Verzeichnis
           nicht kennt, bekommt ihn auch. Die Liste ist eine Hilfe, keine
           Schranke.
        */ ?>
        <div class="relative">
          <ul data-ortliste hidden
              class="absolute z-20 mt-1 w-full border border-sand-deep bg-cream shadow-lg"></ul>
        </div>
        <p data-ortnotiz class="mt-2 text-[0.8rem] text-muted" hidden></p>
        <img data-ortkarte hidden alt=""
             class="mt-3 w-full max-w-[16rem] border border-sand-deep">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
