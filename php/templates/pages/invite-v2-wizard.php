<?php
/**
 * Der Assistent: ein Formular, so viele Schritte wie das Design braucht.
 *
 * Ohne Skript stehen alle Schritte untereinander und ein Absenden reicht -
 * dieselbe Regel wie im alten Assistenten. Das Skript blendet sie ein und aus.
 * Es entscheidet nichts: welche Felder es gibt, steht schon fest, bevor diese
 * Datei laeuft (DesignWizard::choices()).
 *
 * @var string $locale
 * @var array<string,mixed> $design
 * @var list<array<string,mixed>> $designs
 * @var list<string> $steps
 * @var array<string,mixed> $choices
 * @var array<string,string> $values
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $csrf
 * @var string $error
 * @var array<string,mixed>|null $done
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$p = static fn (string $path): string => I18n::path($path, $locale);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');

$label = 'text-[0.62rem] uppercase tracking-[0.18em] text-muted';
$field = 'mt-2 w-full border border-sand-deep bg-cream px-4 py-3 text-sm text-ink';

$stepTitles = [
    'angaben'          => $t('stepAngaben'),
    'bilder'           => $t('stepBilder'),
    'design'           => $t('stepDesign'),
    'veroeffentlichen' => $t('stepPublish'),
];

$fieldTitles = [
    'bride'   => $t('fieldBride'),   'groom'   => $t('fieldGroom'),
    'date'    => $t('fieldDate'),    'time'    => $t('fieldTime'),
    'venue'   => $t('fieldVenue'),   'address' => $t('fieldAddress'),
    'message' => $t('fieldMessage'), 'hashtag' => $t('fieldHashtag'),
];

$inputTypes = ['date' => 'date', 'time' => 'time'];
?>
<?= Ui::pageHero('invite2-hero', $t('wizardTitle'), I18n::t('nav.invitation2'), $t('wizardLead')) ?>

<?= Ui::sectionOpen() ?>

<?php if ($done !== null) : ?>
  <div class="mx-auto max-w-2xl text-center">
    <div class="eyebrow">✓</div>
    <h2 class="headline mt-3 text-3xl"><?= e($t('doneTitle')) ?></h2>
    <p class="mt-6 break-all text-sm text-ink">
      <a class="underline" href="<?= e($done['path']) ?>"><?= e($done['url']) ?></a>
    </p>
  </div>

<?php else : ?>

  <?php if ($error !== '') : ?>
    <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
      <?= e($t('error' . ucfirst($error))) ?>
    </p>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="mx-auto max-w-3xl" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="design" value="<?= e((string) $design['slug']) ?>">

    <ol class="mb-10 flex flex-wrap gap-x-6 gap-y-2 border-b border-sand-deep pb-4 text-[0.62rem] uppercase tracking-[0.16em]" data-steps>
      <?php foreach ($steps as $i => $key) : ?>
        <li data-step-label="<?= $i ?>" class="text-muted"><?= $i + 1 ?>. <?= e($stepTitles[$key]) ?></li>
      <?php endforeach; ?>
    </ol>

    <?php foreach ($steps as $i => $key) : ?>
      <fieldset data-step="<?= $i ?>" class="space-y-8">

        <?php if ($key === 'angaben') : ?>
          <div class="grid gap-7 sm:grid-cols-2">
            <?php foreach ($choices['fields'] as $feld) : ?>
              <div<?= $feld === 'message' ? ' class="sm:col-span-2"' : '' ?>>
                <label class="<?= $label ?>" for="f-<?= e($feld) ?>"><?= e($fieldTitles[$feld]) ?></label>
                <?php if ($feld === 'message') : ?>
                  <textarea id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" rows="4" class="<?= $field ?>" data-live="<?= e($feld) ?>"><?= e($old($feld)) ?></textarea>
                <?php else : ?>
                  <input id="f-<?= e($feld) ?>" name="<?= e($feld) ?>" class="<?= $field ?>"
                         type="<?= e($inputTypes[$feld] ?? 'text') ?>"
                         value="<?= e($old($feld)) ?>" data-live="<?= e($feld) ?>"
                         <?= in_array($feld, ['bride', 'groom'], true) ? 'required' : '' ?>>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($key === 'bilder') : ?>
          <?php foreach ($choices['layers'] as $id => $rechte) : ?>
            <?php if (!$rechte['photo']) { continue; } ?>
            <div>
              <label class="<?= $label ?>" for="b-<?= e($id) ?>"><?= e($id) ?></label>
              <input id="b-<?= e($id) ?>" type="file" name="layer_src_<?= e($id) ?>"
                     accept="image/jpeg,image/png,image/webp" class="<?= $field ?>">
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($key === 'design') : ?>
          <?php foreach ($choices['palette'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>" for="p-<?= e($marke) ?>">
                <?= e($eintrag['label'][$locale] ?? $eintrag['label']['de'] ?? $marke) ?>
              </label>
              <input id="p-<?= e($marke) ?>" type="color" name="palette_<?= e($marke) ?>"
                     value="<?= e((string) $eintrag['value']) ?>" class="<?= $field ?> h-12">
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['fonts'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>" for="s-<?= e($marke) ?>"><?= e($marke) ?></label>
              <select id="s-<?= e($marke) ?>" name="fonts_<?= e($marke) ?>" class="<?= $field ?>">
                <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                  <option value="<?= e($familie) ?>" <?= (string) $eintrag['family'] === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['layers'] as $id => $rechte) : ?>
            <?php if (!$rechte['color'] && !$rechte['font'] && !$rechte['text'] && !$rechte['hide']) { continue; } ?>
            <div class="border-t border-sand-deep pt-6">
              <div class="<?= $label ?>"><?= e($id) ?></div>

              <?php if ($rechte['color']) : ?>
                <input type="color" name="layer_color_<?= e($id) ?>" class="<?= $field ?> h-12">
              <?php endif; ?>

              <?php if ($rechte['font']) : ?>
                <select name="layer_font_<?= e($id) ?>" class="<?= $field ?>">
                  <option value=""><?= e($locale === 'de' ? '— wie im Design —' : '— as the design has it —') ?></option>
                  <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                    <option value="<?= e($familie) ?>"><?= e($familie) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>

              <?php if ($rechte['text']) : ?>
                <input type="text" name="layer_text_<?= e($id) ?>" class="<?= $field ?>" maxlength="600">
              <?php endif; ?>

              <?php if ($rechte['hide']) : ?>
                <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                  <input type="checkbox" name="layer_hidden_<?= e($id) ?>"> <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
                </label>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($key === 'veroeffentlichen') : ?>
          <style><?= $styles ?></style>
          <div class="<?= e($scope) ?> mx-auto aspect-[2/3] w-full max-w-sm" data-preview
               style="position:relative;container-type:inline-size;"><?= $karte ?></div>

          <div>
            <label class="<?= $label ?>" for="f-slug"><?= e($t('fieldSlug')) ?></label>
            <input id="f-slug" name="slug" class="<?= $field ?>" value="<?= e($old('slug')) ?>">
            <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('slugNote')) ?></p>
          </div>

          <button type="submit" class="border border-ink px-8 py-4 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= e($t('publish')) ?>
          </button>
        <?php endif; ?>

      </fieldset>
    <?php endforeach; ?>
  </form>

<?php endif; ?>

<?= Ui::sectionClose() ?>
