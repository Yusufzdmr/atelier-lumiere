<?php
/**
 * Die eigenen Felder eines Abschnitts - einmal, fuer beide Wege.
 *
 * Der Assistent und das Bearbeiten fragen dasselbe, und bis heute stand es
 * zweimal da: in invite-v2-wizard.php und in invite-v2-edit.php, jeweils ein
 * fest verdrahteter Block fuer "text". Als der Katalog Felder dazubekam
 * (Hashtag, Kontoinhaber, IBAN, Bilder), wuchs nur die eine Kopie mit - beim
 * Bearbeiten fehlten sie, und das faellt niemandem auf, der die Einladung
 * gerade erst angelegt hat.
 *
 * WELCHE Felder es sind, sagt der Katalog (SectionRegistry::inputs). Die
 * Obergrenze steht dort ebenfalls; dasselbe maxlength hier und dieselbe Zahl
 * im Controller waeren zwei Stellen, an denen sie auseinanderlaufen kann.
 *
 * Der Feldname traegt die Kennung des Abschnitts: ein Dokument kann mehrere
 * Textbloecke haben ("Dress Code", "Anfahrt"), und ein fester Name waere fuer
 * beide derselbe. Fuer den Schluessel 'text' ergibt sec_<schluessel>_<kennung>
 * genau den bisherigen Namen - es aendert sich also an keiner bestehenden
 * Einladung etwas.
 *
 * @var array<string,mixed> $abschnitt  ein Eintrag aus $choices['sections']
 * @var string $sid                     seine Kennung
 * @var callable $old                   frueher Eingegebenes
 * @var callable $t                     Woerterbuch
 * @var string $label
 * @var string $field
 * @var string $locale
 * @var list<string> $fotos             schon abgelegte Bilder (beim Anlegen leer)
 */

use function Atelier\e;

$fotos = $fotos ?? [];
?>
<?php foreach (($abschnitt['inputs'] ?? []) as $schluessel => $feld) : ?>
  <?php
    $feldName = 'sec_' . $schluessel . '_' . $sid;
    $etikett  = $feld['label'][$locale] ?? $feld['label']['de'] ?? $schluessel;
  ?>
  <div class="mt-3">
    <label class="<?= $label ?>" for="sf-<?= e($sid . '-' . $schluessel) ?>"><?= e((string) $etikett) ?></label>

    <?php if ((string) $feld['type'] === 'photos') : ?>
      <?php /*
         Der erste Inhalt, der keine Zeichen sind. multiple, weil niemand acht
         Bilder einzeln auswaehlen will - und die Obergrenze steht daneben,
         weil ein Feld, das mehr nimmt als es behaelt, den Rest schweigend
         wegwirft.

         Die schon abgelegten Bilder stehen darunter, jedes mit einem Haken
         zum Wegnehmen. Ohne ihn waere die einzige Art, ein Bild loszuwerden,
         alle acht neu hochzuladen.
      */ ?>
      <input id="sf-<?= e($sid . '-' . $schluessel) ?>" type="file" multiple
             name="<?= e($feldName) ?>[]"
             accept="image/png,image/jpeg,image/webp"
             class="<?= $field ?>">
      <p class="mt-2 text-[0.8rem] text-muted">
        <?= $locale === 'tr'
            ? 'En fazla ' . (int) $feld['max'] . ' fotoğraf.'
            : 'Höchstens ' . (int) $feld['max'] . ' Bilder.' ?>
      </p>

      <?php if ($fotos !== []) : ?>
        <div class="mt-3 grid grid-cols-4 gap-2">
          <?php foreach ($fotos as $bild) : ?>
            <label class="block cursor-pointer">
              <img src="<?= e($bild) ?>" alt="" class="block aspect-square w-full object-cover">
              <span class="mt-1 flex items-center gap-1 text-[0.66rem] text-muted">
                <input type="checkbox" name="sec_photo_weg_<?= e($sid) ?>[]" value="<?= e($bild) ?>">
                <?= $locale === 'tr' ? 'kaldır' : 'weg' ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php elseif ((string) $feld['type'] === 'textarea') : ?>
      <textarea id="sf-<?= e($sid . '-' . $schluessel) ?>" name="<?= e($feldName) ?>" rows="4"
                maxlength="<?= (int) $feld['max'] ?>"
                class="<?= $field ?>"><?= e($old($feldName)) ?></textarea>
      <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('sectionTextNote')) ?></p>

    <?php else : ?>
      <input id="sf-<?= e($sid . '-' . $schluessel) ?>" name="<?= e($feldName) ?>"
             maxlength="<?= (int) $feld['max'] ?>"
             class="<?= $field ?>" value="<?= e($old($feldName)) ?>">
    <?php endif; ?>
  </div>
<?php endforeach; ?>
