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
 * @var list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}> $filme
 * @var string $csrf
 * @var string $token      Kennung des Entwurfs, leer solange keiner gespeichert ist
 * @var string $draftLink  gesetzt, wenn gerade eben gespeichert wurde
 * @var string $error
 * @var array<string,mixed>|null $done
 */

use function Atelier\e;
use Atelier\Design;
use Atelier\Http;
use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$p = static fn (string $path): string => I18n::path($path, $locale);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');
/*
 * Ein Haken schickt nichts, wenn er aus ist - im Entwurf steht dann gar kein
 * Schluessel. Also fragt diese Funktion nach dem Schluessel, nicht nach dem
 * Wert: `$old(...) === 'on'` waere richtig fuer den Browser und falsch fuer
 * jeden anderen Absender.
 */
$anHaken = static fn (string $feld): bool => array_key_exists($feld, $values);
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

/*
 * Eine Ebene des Dokuments zu ihrer Kennung. $choices traegt nur die Rechte,
 * nicht den Namen - und der Name ist das, was das Paar lesen soll. Ohne diese
 * Suche stand auf dem Bildschirm die Kennung selbst, also "foto" oder
 * "zier2": ein Datenbankschluessel. Dieselbe Regel wie im Panel und bei den
 * Abschnitten, die Kennung nur als letzter Ausweg.
 */
$ebene = static function (string $id) use ($design): array {
    foreach ($design['layers'] as $el) {
        if ((string) $el['id'] === $id) {
            return $el;
        }
    }
    return [];
};
$ebeneName = static function (string $id) use ($ebene): string {
    $el = $ebene($id);
    $name = trim((string) ($el['label'] ?? ''));
    return $name !== '' ? $name : $id;
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
      <a class="link-underline" href="<?= e($done['path']) ?>"><?= e($done['url']) ?></a>
    </p>

    <div class="mt-10 border-t border-sand-deep pt-6">
      <p class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e($t('doneRepliesLabel')) ?></p>
      <p class="mt-3 break-all text-sm text-ink">
        <a class="link-underline" href="<?= e($done['managePath']) ?>"><?= e($done['manageUrl']) ?></a>
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

  /*
   * Das Seitenverhaeltnis der Karte, ebenfalls von Hand: aspect-[2/3] steht
   * nicht in der gebauten style.css. Die Klasse stand hier schon vorher - der
   * Kasten war also immer null Pixel hoch, und weil die Karte in cqw rechnet,
   * schrumpfte ihr Inhalt auf nichts. Die Vorschau zeigte deshalb bisher
   * praktisch gar nichts.
   */
  .wz-card {
    aspect-ratio: 2 / 3;
    /*
     * Der Grund der Karte, wie im Schaufenster (designs-v2.php). Ohne ihn
     * stand die Karte auf dem cremefarbenen Seitengrund - und weil ihre
     * Schrift ebenfalls hell ist, war sie da, aber unsichtbar. Die Marke
     * kommt aus dem Design; der Ersatzwert nur, falls eines keine hat.
     */
    background: var(--d-bg, #EFE7DC);
  }

  /* fieldset bringt von Haus aus Rahmen und Innenabstand mit; border-0 gibt es
     in der gebauten Datei nicht. */
  .wz-quiet { margin: 0; border: 0; padding: 0; min-inline-size: 0; }

  /*
   * hover:border-ink steht nicht in der gebauten Datei (nur .border-ink ohne
   * hover, und die Hover-Rahmenfarben, die es gibt - gold, muted, sand -
   * gehoeren zu einer anderen Familie als das text-ink daneben). Dieselbe
   * Marke wie .border-ink, nur eben beim Hover.
   */
  .wz-draft-btn:hover { border-color: var(--color-ink); }

<?= \Atelier\View::partial('partials/schritt-tabs-css') ?>
<?= \Atelier\View::partial('partials/ablauf-css') ?>

<?= \Atelier\View::partial('partials/bildfeld-css') ?>
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
    <?php /*
       Bei welchem Schritt das Paar stand. Ohne das warf ein "Entwurf
       speichern" es zurueck auf Schritt eins und es klickte sich jedes Mal
       von vorn durch - gemessen: vor dem Speichern Schritt 4, danach 1.
       Der Wert reist im Entwurf mit wie jedes andere Feld; gesetzt wird er
       erst im Moment des Absendens (siehe Skript am Fuss der Datei), damit
       er nicht bei jedem Klick mitgefuehrt werden muss.
    */ ?>
    <input type="hidden" name="schritt" value="<?= e($old('schritt')) ?>" data-schritt>

    <ol class="wz-tabs mb-10 flex flex-wrap gap-x-8 gap-y-2 border-b border-sand-deep pb-4" data-steps>
      <?php foreach ($steps as $i => $key) : ?>
        <?php /*
           Der erste Schritt startet aktiv (text-ink), nicht erst durch
           invite-v2.js' show(0): ohne Skript laeuft show() nie, und dann
           stuenden alle Schritte gleich grau da, keiner unterstrichen.
           Dieselbe Regel wie im Bearbeiten-Bildschirm.

           Blosser Text, kein <button> vom Server - der Baustein am Fuss der
           Datei ruestet ihn auf, und erst dann, wenn es etwas zu schalten
           gibt. Ein Knopf, den niemand bedient, ist eine Behauptung ohne
           Wirkung.
        */ ?>
        <li data-step-label="<?= $i ?>" class="<?= $i === 0 ? 'text-ink' : 'text-muted' ?>">
          <span class="wz-tab-num"><?= $i + 1 ?></span><?= e($stepTitles[$key]) ?>
        </li>
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
            <?php $ebeneTyp = (string) ($ebene((string) $id)['type'] ?? ''); ?>
            <?php if ($ebeneTyp === 'video') : ?>
              <?php /*
                 Auswahl statt Upload. Nicht aus Bequemlichkeit: ein Paar
                 findet keinen Hintergrundfilm im richtigen Format, und was es
                 faende, waere 200 MB Querformat mit Text im Bild.
              */ ?>
              <label class="block <?= $label ?>">
                <?= e($ebeneName((string) $id)) ?>
                <select name="film_<?= e($id) ?>" class="<?= $field ?> normal-case tracking-normal">
                  <option value=""><?= $locale === 'de' ? 'Wie in der Vorlage' : 'As in the template' ?></option>
                  <?php foreach ($filme as $film) : ?>
                    <option value="<?= e($film['id']) ?>"><?= e($film['label'] ?: $film['id']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            <?php else : ?>
            <?php
              /*
               * Das Bild, das die Vorlage heute an dieser Stelle zeigt: ohne
               * es waere die Frage nach einem neuen Bild die Frage nach dem
               * Ersatz fuer etwas, das man nie gesehen hat. Es kommt aus dem
               * Dokument, nicht aus einer Eingabe - safeSrc() hat es beim
               * Speichern der Vorlage geprueft.
               */
              $ebeneBild = (string) ($ebene((string) $id)['src'] ?? '');
            ?>
            <div class="wz-photo">
              <?php /*
                 Die Platte steht IMMER, auch wenn die Vorlage an dieser Stelle
                 noch kein Bild hat. Sonst waehlt das Paar eine Datei und sieht
                 nichts - ein Bildfeld, das nicht zeigt, was gewaehlt wurde,
                 ist die Haelfte eines Feldes.
              */ ?>
              <div>
                <div class="wz-photo-platte">
                  <?php /* Ein <img> ohne src zeigt in manchen Browsern ein
                           kaputtes Symbol, deshalb steht es erst da, wenn es
                           etwas zu zeigen gibt. */ ?>
                  <?php if ($ebeneBild !== '') : ?>
                    <img src="<?= e($ebeneBild) ?>" alt="" data-photo-preview="<?= e($id) ?>">
                  <?php else : ?>
                    <img alt="" hidden data-photo-preview="<?= e($id) ?>">
                    <span class="wz-photo-leer" data-photo-empty="<?= e($id) ?>"><?= e($t('photoEmpty')) ?></span>
                  <?php endif; ?>
                </div>
                <p class="<?= $label ?> wz-photo-bu"
                   data-photo-caption="<?= e($id) ?>"
                   data-photo-chosen="<?= e($t('photoChosen')) ?>"><?= $ebeneBild !== '' ? e($t('photoCurrent')) : '' ?></p>
              </div>

              <div class="w-full">
                <p class="wz-photo-name"><?= e($ebeneName((string) $id)) ?></p>

                <?php /*
                   Vom Server das nackte Dateifeld - ohne Skript ist es das
                   einzige, was funktioniert, und es funktioniert vollstaendig.
                   Das Skript am Fuss dieser Datei legt es in ein <label> und
                   macht daraus den Knopf im Haus-Schnitt. Dieselbe Reihenfolge
                   wie bei den Reitern des Bearbeiten-Bildschirms: erst was
                   ohne Skript traegt, dann die Verbesserung.
                */ ?>
                <div data-photo-slot="<?= e($id) ?>"
                     data-photo-label="<?= e($t('photoChoose')) ?>">
                  <input id="b-<?= e($id) ?>" type="file" name="layer_src_<?= e($id) ?>"
                         accept="image/jpeg,image/png,image/webp" class="<?= $field ?>"
                         data-photo-input="<?= e($id) ?>">
                </div>

                <p class="wz-photo-hint"><?= e($t('photoHint')) ?></p>
                <?php /* Nur wenn es ueberhaupt ein vorhandenes Bild gibt -
                         sonst verspricht der Satz die Rueckkehr zu etwas, das
                         es nicht gibt. */ ?>
                <?php if ($ebeneBild !== '') : ?>
                  <p class="wz-photo-hint"><?= e($t('editPhotoNote')) ?></p>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
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
                  <input type="checkbox" name="sec_hidden_<?= e($sid) ?>" <?= $anHaken('sec_hidden_' . $sid) ? 'checked' : '' ?>> <?= e($t('sectionHide')) ?>
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

              <?php if (in_array('text', $abschnitt['fields'], true)) : ?>
                <?php /*
                   Der Feldname traegt die Kennung: ein Dokument kann mehrere
                   Textbloecke haben ("Dress Code", "Anfahrt"), und ein fester
                   Name waere fuer beide derselbe.
                */ ?>
                <div class="mt-3">
                  <label class="<?= $label ?>" for="st-<?= e($sid) ?>"><?= e($t('sectionText')) ?></label>
                  <textarea id="st-<?= e($sid) ?>" name="sec_text_<?= e($sid) ?>" rows="4" maxlength="1200"
                            class="<?= $field ?>"><?= e($old('sec_text_' . $sid)) ?></textarea>
                  <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('sectionTextNote')) ?></p>
                </div>
              <?php endif; ?>

              <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
                <?= \Atelier\View::partial('partials/ablauf-zeilen', compact('old','t','field','locale')) ?>
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
                     value="<?= e($old('palette_' . $marke) !== '' ? $old('palette_' . $marke) : (string) $eintrag['value']) ?>" class="<?= $field ?> h-12"
                     data-live-var="--d-<?= e(Design::key($marke)) ?>">
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['fonts'] as $marke => $eintrag) : ?>
            <div>
              <label class="<?= $label ?>" for="s-<?= e($marke) ?>"><?= e($marke) ?></label>
              <select id="s-<?= e($marke) ?>" name="fonts_<?= e($marke) ?>" class="<?= $field ?>"
                      data-live-var="--df-<?= e(Design::key($marke)) ?>" data-live-quote="1">
                <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                  <?php $fontWahl = $old('fonts_' . $marke) !== '' ? $old('fonts_' . $marke) : (string) $eintrag['family']; ?>
                  <option value="<?= e($familie) ?>" <?= $fontWahl === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>

          <?php foreach ($choices['layers'] as $id => $rechte) : ?>
            <?php if (!$rechte['color'] && !$rechte['font'] && !$rechte['text'] && !$rechte['hide']) { continue; } ?>
            <div class="border-t border-sand-deep pt-6">
              <div class="<?= $label ?>"><?= e($ebeneName((string) $id)) ?></div>

              <?php if ($rechte['color']) : ?>
                <input type="color" name="layer_color_<?= e($id) ?>" value="<?= e($old('layer_color_' . $id) !== '' ? $old('layer_color_' . $id) : $farbeVon($id)) ?>" class="<?= $field ?> h-12"
                       data-live-el="<?= e((string) $id) ?>"
                       data-live-kind="<?= ((string) ($ebene((string) $id)['type'] ?? '')) === 'shape' ? 'background' : 'color' ?>">
              <?php endif; ?>

              <?php if ($rechte['font']) : ?>
                <select name="layer_font_<?= e($id) ?>" class="<?= $field ?>"
                        data-live-el="<?= e((string) $id) ?>" data-live-kind="font">
                  <option value=""><?= e($locale === 'de' ? '— wie im Design —' : '— as the design has it —') ?></option>
                  <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                    <option value="<?= e($familie) ?>" <?= $old('layer_font_' . $id) === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>

              <?php if ($rechte['text']) : ?>
                <input type="text" name="layer_text_<?= e($id) ?>" class="<?= $field ?>" maxlength="600" value="<?= e($old('layer_text_' . $id)) ?>"
                       data-live-el="<?= e((string) $id) ?>" data-live-kind="text">
              <?php endif; ?>

              <?php if ($rechte['hide']) : ?>
                <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                  <input type="checkbox" name="layer_hidden_<?= e($id) ?>" <?= $anHaken('layer_hidden_' . $id) ? 'checked' : '' ?>
                         data-live-el="<?= e((string) $id) ?>" data-live-kind="hide"> <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
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
              class="wz-draft-btn border border-sand-deep px-6 py-3 text-[0.64rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
        <?= e($t('draftSave')) ?>
      </button>

      <?php if ($draftLink !== '') : ?>
        <div class="mt-5 border border-sand-deep bg-cream/40 p-5">
          <p class="text-[0.8rem] leading-relaxed text-ink"><?= e($t('draftSaved')) ?></p>
          <p class="mt-4 text-[0.7rem] uppercase tracking-[0.16em] text-muted"><?= e($t('draftOther')) ?></p>
          <p class="mt-2 break-all text-sm">
            <a class="text-gold link-underline" href="<?= e($draftLink) ?>"><?= e($draftLink) ?></a>
          </p>
          <p class="mt-3 text-[0.78rem] leading-relaxed text-muted"><?= e($t('draftWarn')) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </form>

  <aside class="wz-side">
    <style><?= $styles ?><?= $sectionCss ?></style>
    <div class="<?= e($scope) ?> wz-card mx-auto w-full max-w-xs" data-preview
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
    <fieldset disabled class="wz-quiet">
      <div class="<?= e($scope) ?> mx-auto mt-6 w-full max-w-xs text-[0.8rem]" data-sections><?= $abschnitte ?></div>
    </fieldset>
  </aside>
</div>

<?php endif; ?>

<?php /*
   Die Vorschau des gewaehlten Bildes. Sie steht hier und nicht in
   invite-v2.js, weil sie zu diesem einen Schritt gehoert - und invite-v2.js
   teilt sich der Assistent mit dem Bearbeiten-Bildschirm.

   Kein Hochladen, kein Netz: gezeigt wird die Datei, die schon auf dem
   Rechner des Paares liegt. Das ist der Punkt - wer ein Bild waehlt, will
   sehen, DASS er das richtige gewaehlt hat, bevor er einen Schritt weiter
   geht.

   FileReader und nicht createObjectURL, und das ist gemessen, nicht
   Geschmack: unsere Richtlinie erlaubt Bilder aus 'self', data: und https:
   (Http::policy(), img-src) - blob: steht nicht darin. Mit einer blob-URL
   blieb der Rahmen leer, das Bild meldete complete=true bei naturalWidth 0.
   Im direkten Vergleich auf derselben Seite lud dasselbe Bild als data-URL
   (40 px) und scheiterte als blob-URL. Die Richtlinie dafuer zu erweitern
   waere der falsche Tausch: sie ist der Grund, warum diese Seite so wenig
   Angriffsflaeche hat, und ein Vorschaubild ist ihn nicht wert.

   Ohne Skript bleibt das Bild der Vorlage stehen und das Feld tut trotzdem
   seine Arbeit: gewaehlt wird beim Absenden, nicht hier.

   nonce Pflicht: siehe Http::nonce() - ein <script> ohne diese Kennung
   fuehrt der Browser gar nicht erst aus.
*/ ?>
<script nonce="<?= e(Http::nonce()) ?>">
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

<?= \Atelier\View::partial('partials/schritt-tabs-js') ?>

  var formular = document.querySelector('[data-wizard]');
  var karte = document.querySelector('[data-preview]');

  /*
   * Zurueck zu dem Schritt, bei dem gespeichert wurde.
   *
   * "Entwurf speichern" schickt das Formular ab, die Seite kommt neu, und
   * invite-v2.js beginnt wie immer bei show(0). Wer bei den Ebenen stand,
   * fand sich also wieder bei den Namen und klickte sich erneut durch.
   *
   * Der Schritt wird ERST beim Absenden geschrieben, nicht bei jedem Klick:
   * so gibt es keinen zweiten Zustand, der neben dem von invite-v2.js
   * herlaufen und irgendwann von ihm abweichen koennte. Und
   * zurueckgefahren wird ueber dieselben Weiter-Knoepfe wie von Hand -
   * inklusive der Pflichtfeldpruefung, die dabei nun einmal gilt.
   */
  if (formular) {
    var merker = formular.querySelector('[data-schritt]');
    var schritte = Array.prototype.slice.call(formular.querySelectorAll('[data-step]'));
    var jetzt = function () {
      for (var n = 0; n < schritte.length; n++) {
        if (!schritte[n].hidden) return n;
      }
      return 0;
    };

    if (merker) {
      // Vor dem Absenden festhalten, wo wir stehen - submit deckt den Klick
      // auf "Entwurf speichern" und die Eingabetaste gleichermassen ab.
      formular.addEventListener('submit', function () {
        merker.value = String(jetzt());
      });

      var ziel = parseInt(merker.value, 10);
      if (ziel > 0 && schritte.length > 1) {
        var knoepfe = Array.prototype.slice.call(formular.querySelectorAll('button[type=button]')).slice(-2);
        if (knoepfe.length === 2) {
          var wache = schritte.length + 2;
          while (jetzt() < ziel && wache-- > 0) {
            var vor = jetzt();
            knoepfe[1].click();
            // Bewegt sich nichts, fehlt ein Pflichtfeld - dann bleibt das
            // Paar hier stehen, wo der Browser die Meldung anzeigt, statt
            // gegen eine Wand zu klopfen.
            if (jetzt() === vor) break;
          }
        }
      }
    }
  }

  /*
   * Die Karte folgt auch dem Design-Schritt.
   *
   * invite-v2.js spiegelt nur die Textfelder des ersten Schrittes - Farbe,
   * Schrift, freier Text und Ausblenden aenderten bis hierher gar nichts,
   * und das Paar sah seine Designwahl zum ersten Mal NACH dem
   * Veroeffentlichen. Gerechnet wird auch hier nichts nach: gesetzt werden
   * dieselben CSS-Variablen und Textknoten, die Design::css() erzeugt -
   * genau das Verfahren, das der Design-Editor des Panels seit jeher
   * benutzt (assets/design-editor.js).
   *
   * Welches Feld welchen Knoten bedient, entscheidet der Server: er kennt
   * den Typ der Ebene und schreibt ihn als data-live-kind an das Feld. Ein
   * Formular, das raet, ob eine Ebene Text oder Flaeche ist, raet
   * irgendwann falsch.
   */
  if (formular && karte) {
    formular.querySelectorAll('[data-live-var]').forEach(function (feld) {
      var marke = feld.getAttribute('data-live-var');
      // Schriftnamen brauchen Anfuehrungszeichen, Farben nicht.
      var zitat = feld.hasAttribute('data-live-quote');
      var setz = function () {
        karte.style.setProperty(marke, zitat ? '"' + feld.value + '"' : feld.value);
      };
      feld.addEventListener('input', setz);
      feld.addEventListener('change', setz);
      // Einmal sofort: nach einem "Entwurf speichern" kommt die Seite neu, das
      // Feld traegt die gespeicherte Wahl - die Karte aber zeichnet der Server
      // aus der Vorlage. Ohne diesen Aufruf sagte das Formular #7B2D26 und die
      // Karte zeigte das Gold der Vorlage.
      setz();
    });

    formular.querySelectorAll('[data-live-el]').forEach(function (feld) {
      var ziel = karte.querySelector('.d-el-' + feld.getAttribute('data-live-el'));
      if (!ziel) return;
      var art = feld.getAttribute('data-live-kind');
      // Was die Vorlage vorgibt, bevor jemand etwas eintippt: ein geleertes
      // Feld heisst "wie im Design", nicht "leer" - der Server macht es
      // genauso (er schreibt nur, was nicht leer ist).
      var vorgabe = ziel.textContent;
      var setz = function () {
        if (art === 'color') { ziel.style.color = feld.value; return; }
        if (art === 'background') { ziel.style.background = feld.value; return; }
        if (art === 'font') { ziel.style.fontFamily = feld.value ? '"' + feld.value + '"' : ''; return; }
        if (art === 'text') { ziel.textContent = feld.value !== '' ? feld.value : vorgabe; return; }
        if (art === 'hide') { ziel.style.display = feld.checked ? 'none' : ''; }
      };
      feld.addEventListener('input', setz);
      feld.addEventListener('change', setz);
      setz();   // wie oben: die gespeicherte Wahl gilt auch beim Laden.
    });
  }

  var felder = document.querySelectorAll('[data-photo-input]');
  if (!felder.length) return;

  Array.prototype.forEach.call(felder, function (feld) {
    var kennung = feld.getAttribute('data-photo-input');
    var bild = document.querySelector('[data-photo-preview="' + kennung + '"]');
    if (!bild) return;
    var titel = document.querySelector('[data-photo-caption="' + kennung + '"]');
    var leer  = document.querySelector('[data-photo-empty="' + kennung + '"]');

    /* Das Dateifeld in einen Knopf im Haus-Schnitt legen. Es wird nicht
       ersetzt, sondern verschoben: id, name, accept und die Datenmarken
       reisen mit dem Knoten mit, und was der Browser bereits gewaehlt hat
       (ein Zurueck aus dem naechsten Schritt) bleibt gewaehlt. Sichtbar ist
       das Feld nicht mehr, bedienbar sehr wohl - siehe die Regel im
       Stilblock. */
    var fach = document.querySelector('[data-photo-slot="' + kennung + '"]');
    var name = null;
    if (fach) {
      var knopf = document.createElement('label');
      knopf.className = 'wz-photo-knopf';
      knopf.setAttribute('for', feld.id);
      knopf.appendChild(document.createTextNode(fach.getAttribute('data-photo-label')));
      feld.className = '';
      knopf.appendChild(feld);

      name = document.createElement('p');
      name.className = 'wz-photo-datei';
      name.hidden = true;

      fach.appendChild(knopf);
      fach.appendChild(name);
    }

    feld.addEventListener('change', function () {
      var datei = feld.files && feld.files[0];
      if (!datei) return;

      // Der Name sofort, das Bild sobald es gelesen ist: das Lesen einer
      // grossen Datei dauert, und in dieser Zeit soll die Wahl schon
      // bestaetigt sein.
      if (name) {
        name.textContent = datei.name;
        name.hidden = false;
      }

      // Auch auf der Karte, nicht nur auf der Platte: die Platte beweist,
      // dass die richtige Datei gewaehlt ist - die Karte zeigt, wie sie im
      // Ausschnitt der Vorlage wirkt, und das ist die eigentliche Frage.
      var aufDerKarte = karte ? karte.querySelector('img.d-el-' + kennung) : null;

      var leser = new FileReader();
      leser.onload = function () {
        bild.src = leser.result;
        if (aufDerKarte) {
          aufDerKarte.src = leser.result;
          // Eine photo-Ebene ohne Startbild kommt versteckt aus dem Server
          // (Design::html) - jetzt gibt es etwas zu zeigen.
          aufDerKarte.hidden = false;
        }
        // Hatte die Vorlage hier kein Bild, war die Platte bis eben leer.
        bild.hidden = false;
        if (leer) leer.hidden = true;
        // "Zurzeit" stimmt jetzt nicht mehr - auf der Platte liegt die
        // eigene Wahl. Das Wort kommt vom Server, damit hier keine zweite
        // Uebersetzung entsteht, die eines Tages von der in dict.php
        // abweicht.
        if (titel) titel.textContent = titel.getAttribute('data-photo-chosen');
      };
      // Schlaegt das Lesen fehl, bleibt schlicht stehen, was vorher da war -
      // die Datei ist trotzdem gewaehlt und wird beim Absenden hochgeladen.
      leser.readAsDataURL(datei);
    });
  });
});
</script>

<?= Ui::sectionClose() ?>
