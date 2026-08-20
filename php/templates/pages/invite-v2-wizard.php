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
 * @var list<string> $steps
 * @var array<string,mixed> $choices
 * @var array<string,string> $values
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $abschnitte  was unter der Karte steht, serverseitig gezeichnet
 * @var string $sectionCss
 * @var string $csrf
 * @var string $token      Kennung des Entwurfs, leer solange keiner gespeichert ist
 * @var string $draftLink  gesetzt, wenn gerade eben gespeichert wurde
 * @var string $error
 * @var array<string,mixed>|null $done
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$p = static fn (string $path): string => I18n::path($path, $locale);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');
// Ein Farbfeld sendet immer einen Wert mit, auch wenn niemand es beruehrt
// hat - ohne value faellt der Browser auf #000000 zurueck. publish() saehe
// dann fuer jede erlaubte Ebene Schwarz, egal was das Design wirklich
// vorsieht. style.color ist ein Markenname, nicht der Wert selbst - der Wert
// steht in der Palette.
$farbeVon = static function (string $id) use ($design): string {
    foreach ($design['layers'] as $el) {
        if ((string) $el['id'] === $id) {
            $marke = (string) ($el['style']['color'] ?? '');
            return (string) ($design['palette'][$marke]['value'] ?? '#000000');
        }
    }
    return '#000000';
};

$label = 'text-[0.62rem] uppercase tracking-[0.18em] text-muted';
$field = 'mt-2 w-full border border-sand-deep bg-cream px-4 py-3 text-sm text-ink';

$stepTitles = [
    'angaben'          => $t('stepAngaben'),
    'bilder'           => $t('stepBilder'),
    'abschnitte'       => $t('stepAbschnitte'),
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

    <div class="mt-10 border-t border-sand-deep pt-6">
      <p class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e($t('doneRepliesLabel')) ?></p>
      <p class="mt-3 break-all text-sm text-ink">
        <a class="underline" href="<?= e($done['managePath']) ?>"><?= e($done['manageUrl']) ?></a>
      </p>
      <p class="mt-3 text-[0.8rem] text-muted"><?= e($t('doneRepliesWarning')) ?></p>
    </div>
  </div>

<?php else : ?>

  <?php if ($error !== '') : ?>
    <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
      <?= e($t('error' . ucfirst($error))) ?>
    </p>
  <?php endif; ?>

<?php /*
   Zwei Spalten: links gearbeitet, rechts gesehen. Die Vorschau stand bis
   hierher im letzten Schritt - der Kunde tippte also vier Schritte lang ins
   Blaue und sah erst am Ende, was daraus wird. Auf schmalen Schirmen fallen
   die Spalten untereinander, die Vorschau dann unter das Formular.
*/ ?>
<?php /*
   Eigene Regeln statt Tailwind-Klassen: die PHP-Fassung laedt ein FERTIG
   gebautes style.css, kein JIT. Eine Klasse, die dort nicht drinsteht, tut
   schlicht nichts - lg:grid-cols-[...], lg:sticky und lg:top-28 gibt es in
   der gebauten Datei nicht, und die Vorschau fiel deshalb stumm unter das
   Formular. Diese paar Zeilen haengen von keinem Build ab.
*/ ?>
<style>
  .wz-grid { margin-inline: auto; max-width: 72rem; }
  @media (min-width: 1024px) {
    .wz-grid { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 3rem; align-items: start; }
    .wz-side { position: sticky; top: 7rem; }
  }
  /* Unter 1024 px steht die Vorschau unter dem Formular, mit Abstand davor. */
  @media (max-width: 1023px) { .wz-side { margin-top: 3rem; } }
</style>
<div class="wz-grid">
  <form method="post" enctype="multipart/form-data" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php /*
       Die Kennung reist im Formular mit: ohne sie legte jedes Speichern einen
       neuen Entwurf an und der Kunde saemmelte Links, von denen nur der
       letzte stimmt.
    */ ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
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

        <?php if ($key === 'abschnitte') : ?>
          <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
            <?php
              // Der Titel des Grafikers, falls vorhanden - sonst die
              // Kennung als letzter Ausweg (siehe DesignSections::html()
              // fuer dieselbe Regel).
              $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
              if ($secTitel === '') {
                  $secTitel = (string) ($abschnitt['title']['de'] ?? '');
              }
              if ($secTitel === '') {
                  $secTitel = $sid;
              }
            ?>
            <div class="border-t border-sand-deep pt-6">
              <div class="<?= $label ?>"><?= e($secTitel) ?></div>

              <?php if ($abschnitt['hide']) : ?>
                <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                  <input type="checkbox" name="sec_hidden_<?= e($sid) ?>"> <?= e($t('sectionHide')) ?>
                </label>
              <?php endif; ?>

              <?php if (in_array('families', $abschnitt['fields'], true)) : ?>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                  <div>
                    <label class="<?= $label ?>" for="fb"><?= e($t('familyBride')) ?></label>
                    <input id="fb" name="family_bride" class="<?= $field ?>" maxlength="120" value="<?= e($old('family_bride')) ?>">
                  </div>
                  <div>
                    <label class="<?= $label ?>" for="fg"><?= e($t('familyGroom')) ?></label>
                    <input id="fg" name="family_groom" class="<?= $field ?>" maxlength="120" value="<?= e($old('family_groom')) ?>">
                  </div>
                </div>
              <?php endif; ?>

              <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
                <?php /*
                   Feste Zeilenzahl statt Hinzufuegen-Knopf: ohne Skript
                   funktioniert das Formular sonst nicht, und der alte
                   Assistent macht es genauso.
                */ ?>
                <?php for ($z = 0; $z < 8; $z++) : ?>
                  <div class="mt-3 grid gap-3 sm:grid-cols-[8rem_1fr]">
                    <input name="prog_time_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                           placeholder="<?= e($t('programTime')) ?>" value="<?= e($old('prog_time_' . $z)) ?>">
                    <input name="prog_title_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                           placeholder="<?= e($t('programTitle')) ?>" value="<?= e($old('prog_title_' . $z)) ?>">
                  </div>
                <?php endfor; ?>
              <?php endif; ?>
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
                <input type="color" name="layer_color_<?= e($id) ?>" value="<?= e($farbeVon($id)) ?>" class="<?= $field ?> h-12">
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

    <?php /*
       Ausserhalb der Schritte, nicht im letzten: gespeichert wird mitten in
       der Arbeit, und wer bis zum letzten Schritt kommt, veroeffentlicht
       ohnehin. Eigener Name (was=draft) trennt das Speichern vom
       Veroeffentlichen; formnovalidate, weil ein halb ausgefuellter Entwurf
       genau der Fall ist, fuer den es diesen Knopf gibt - die Pflichtfelder
       gelten fuers Veroeffentlichen.
    */ ?>
    <div class="mt-8 border-t border-sand-deep pt-6">
      <button type="submit" name="was" value="draft" formnovalidate
              class="border border-sand-deep px-6 py-3 text-[0.64rem] uppercase tracking-[0.16em] text-muted transition-colors hover:border-ink hover:text-ink">
        <?= e($t('draftSave')) ?>
      </button>

      <?php if ($draftLink !== '') : ?>
        <div class="mt-5 border border-sand-deep bg-cream/60 p-5">
          <p class="text-[0.7rem] uppercase tracking-[0.16em] text-muted"><?= e($t('draftSaved')) ?></p>
          <p class="mt-2 break-all text-sm">
            <a class="text-gold underline" href="<?= e($draftLink) ?>"><?= e($draftLink) ?></a>
          </p>
          <p class="mt-3 text-[0.78rem] leading-relaxed text-muted"><?= e($t('draftWarn')) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </form>

  <aside class="wz-side">
    <style><?= $styles ?><?= $sectionCss ?></style>
    <div class="<?= e($scope) ?> mx-auto aspect-[2/3] w-full max-w-xs" data-preview
         style="position:relative;container-type:inline-size;"><?= $karte ?></div>

    <?php /*
       Die Abschnitte kommen vom Server, auch beim Nachladen: html() kennt die
       Datumsregel, den Kartenlink und die Frage, welcher Abschnitt ueberhaupt
       gedruckt wird. Dasselbe im Browser nachzubauen waere eine zweite
       Wahrheit - siehe previewFragment().
    */ ?>
    <?php /*
       disabled fieldset, kein blosses CSS: der rsvp-Abschnitt druckt ein
       echtes Formular mit Absenden-Knopf. In der Vorschau darf es nicht
       abschicken - es steht ausserhalb des Assistenten-Formulars, wuerde also
       eine eigene Anfrage an dieselbe Adresse stellen. Ein deaktiviertes
       fieldset schaltet jedes Bedienelement darin ab, in jedem Browser.
    */ ?>
    <fieldset disabled class="m-0 border-0 p-0">
      <div class="<?= e($scope) ?> mx-auto mt-6 w-full max-w-xs text-[0.8rem]" data-sections><?= $abschnitte ?></div>
    </fieldset>
  </aside>
</div>

<?php endif; ?>

<?= Ui::sectionClose() ?>
