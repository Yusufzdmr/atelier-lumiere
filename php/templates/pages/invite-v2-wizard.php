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
use Atelier\Http;
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
            <?php
              /*
               * Der Name der Ebene, wie der Grafiker ihn vergeben hat - und
               * erst als letzter Ausweg die Kennung. Hier stand vorher die
               * Kennung selbst, also las das Paar "foto" oder "szenetl":
               * einen Datenbankschluessel. Dieselbe Regel gilt schon im Panel
               * (admin/design-edit-sections.php) und bei den Abschnitten
               * gleich darunter; nur dieses eine Feld hielt sie nicht.
               *
               * Und das Bild, das die Vorlage heute an dieser Stelle zeigt:
               * ohne es waere "Neues Bild" eine Frage nach dem Ersatz fuer
               * etwas, das man nie gesehen hat. Es kommt aus dem Dokument,
               * nicht aus einer Eingabe - safeSrc() hat es beim Speichern der
               * Vorlage geprueft.
               */
              $ebeneName = $id;
              $ebeneBild = '';
              foreach ($design['layers'] as $el) {
                  if ((string) $el['id'] === (string) $id) {
                      $ebeneName = ((string) ($el['label'] ?? '')) !== '' ? (string) $el['label'] : $id;
                      $ebeneBild = (string) ($el['src'] ?? '');
                      break;
                  }
              }
            ?>
            <div>
              <label class="<?= $label ?>" for="b-<?= e($id) ?>"><?= e($ebeneName) ?></label>

              <div class="mt-3 flex items-start gap-5">
                <?php /*
                   Der Rahmen steht IMMER, auch wenn die Vorlage an dieser
                   Stelle noch kein Bild hat. Sonst waehlt das Paar eine Datei
                   und sieht nichts - und ein Bildfeld, das nicht zeigt, was
                   man gewaehlt hat, ist die haelfte eines Feldes.

                   aspect-[4/5] statt einer festen Hoehe: h-24 steht gar nicht
                   in der gebauten style.css (w-24 schon) - eine Hoehenklasse
                   zu raten hiesse, sie taete still nichts. Das Verhaeltnis
                   passt ohnehin besser zur Karte als eine Zahl.
                */ ?>
                <div class="shrink-0">
                  <div class="aspect-[4/5] w-24 overflow-hidden border border-sand-deep bg-sand">
                    <?php /* Leer heisst leer: ein <img> ohne src zeigt in
                             manchen Browsern ein kaputtes Symbol, deshalb
                             bleibt es bis zur Wahl ausgeblendet. */ ?>
                    <img<?= $ebeneBild !== '' ? ' src="' . e($ebeneBild) . '"' : '' ?> alt=""
                         class="h-full w-full object-cover<?= $ebeneBild === '' ? ' hidden' : '' ?>"
                         data-photo-preview="<?= e($id) ?>">
                  </div>
                  <?php /* Unter dem Rahmen, nicht darueber: oben steht schon
                           der Name der Ebene, und zwei gleich gesetzte
                           Kleinschriften untereinander lesen sich wie zwei
                           Feldnamen statt wie Name und Bildunterschrift. */ ?>
                  <p class="<?= $label ?> mt-2"
                     data-photo-caption="<?= e($id) ?>"
                     data-photo-chosen="<?= e($t('photoChosen')) ?>"><?= e($t('photoCurrent')) ?></p>
                </div>

                <div class="w-full">
                  <input id="b-<?= e($id) ?>" type="file" name="layer_src_<?= e($id) ?>"
                         accept="image/jpeg,image/png,image/webp" class="<?= $field ?>"
                         data-photo-input="<?= e($id) ?>">
                  <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('photoHint')) ?></p>
                  <?php /* Nur wenn es ueberhaupt ein vorhandenes Bild gibt -
                           sonst verspricht der Satz die Rueckkehr zu etwas,
                           das es nicht gibt. */ ?>
                  <?php if ($ebeneBild !== '') : ?>
                    <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('editPhotoNote')) ?></p>
                  <?php endif; ?>
                </div>
              </div>
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
                <?php /*
                   Feste Zeilenzahl statt Hinzufuegen-Knopf: ohne Skript
                   funktioniert das Formular sonst nicht, und der alte
                   Assistent macht es genauso.
                */ ?>
                <?php for ($z = 0; $z < 8; $z++) : ?>
                  <div class="mt-3 grid gap-3 sm:grid-cols-[5rem_1fr]">
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
              class="wz-draft-btn border border-sand-deep px-6 py-3 text-[0.64rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
        <?= e($t('draftSave')) ?>
      </button>

      <?php if ($draftLink !== '') : ?>
        <div class="mt-5 border border-sand-deep bg-cream/40 p-5">
          <p class="text-[0.7rem] uppercase tracking-[0.16em] text-muted"><?= e($t('draftSaved')) ?></p>
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

  var felder = document.querySelectorAll('[data-photo-input]');
  if (!felder.length) return;

  Array.prototype.forEach.call(felder, function (feld) {
    var kennung = feld.getAttribute('data-photo-input');
    var bild = document.querySelector('[data-photo-preview="' + kennung + '"]');
    if (!bild) return;
    var titel = document.querySelector('[data-photo-caption="' + kennung + '"]');

    feld.addEventListener('change', function () {
      var datei = feld.files && feld.files[0];
      if (!datei) return;

      var leser = new FileReader();
      leser.onload = function () {
        bild.src = leser.result;
        // Hatte die Vorlage hier kein Bild, war der Rahmen bis eben leer.
        bild.classList.remove('hidden');
        // "Zurzeit" stimmt jetzt nicht mehr - im Rahmen steht die eigene
        // Wahl. Das Wort kommt vom Server, damit hier keine zweite
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
