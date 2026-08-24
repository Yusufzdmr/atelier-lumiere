<?php
/**
 * Nachtraeglich aendern: dieselben Felder wie im Assistenten, zwei Tabs.
 *
 * Ohne Skript stehen beide Tabs untereinander und ein Absenden reicht -
 * dieselbe Regel wie im Assistenten. invite-v2.js blendet sie ein und aus; es
 * entscheidet nichts, welche Felder es gibt, steht schon fest, bevor diese
 * Datei laeuft (DesignWizard::choices() auf dem eingefrorenen Sockel).
 *
 * Absichtlich OHNE [data-sections]: der Assistent laesst sich die Abschnitte
 * bei jeder Aenderung vom Server neu zeichnen, und jede dieser Anfragen liefe
 * hier durch manageZugang() und damit gegen die Bremse (60 je zehn Minuten).
 * Ein Paar, das zwanzig Felder durchgeht, saesse mitten im Bearbeiten vor
 * einer 404-Seite. ladeAbschnitte() steigt ohne diesen Knoten sofort aus
 * (invite-v2.js:87), also bleibt die Vorschau hier ein einmaliges,
 * serverseitig gezeichnetes Bild - und die Karte laeuft ueber [data-live]
 * trotzdem live mit.
 *
 * @var string $locale
 * @var array<string,mixed> $design      der eingefrorene Sockel, vollstaendig
 * @var array<string,mixed> $choices
 * @var list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}> $filme
 * @var array<string,string> $values     Formularnamen, nicht Datennamen
 * @var array<string,mixed> $wahl        was der Kunde beim Veroeffentlichen waehlte
 * @var bool   $darfDesign               Spec §4: ohne wahl kein Design-Tab
 * @var string $gastPfad
 * @var string $stand                    data['updatedAt'], reist versteckt mit
 * @var string $scope
 * @var string $styles
 * @var string $sectionCss
 * @var string $karte
 * @var string $abschnitte
 * @var string $csrf
 * @var string $error
 * @var bool   $ok
 */

use function Atelier\e;
use Atelier\Design;
use Atelier\Http;
use Atelier\I18n;
use Atelier\Ui;

$t = static fn (string $key): string => I18n::t('invitation2.' . $key);
$old = static fn (string $feld): string => (string) ($values[$feld] ?? '');

// Was der Kunde beim Veroeffentlichen an dieser Ebene gewaehlt hat, oder null.
$gewaehlt = static function (string $id) use ($wahl): array {
    $layers = is_array($wahl['layers'] ?? null) ? $wahl['layers'] : [];
    return is_array($layers[$id] ?? null) ? $layers[$id] : [];
};

// Ein Farbfeld sendet immer einen Wert mit, auch wenn niemand es beruehrt hat -
// ohne value faellt der Browser auf #000000 zurueck und das Speichern saehe
// fuer jede erlaubte Ebene Schwarz. Zuerst die Wahl des Kunden, dann die
// Ausgangsfarbe des Sockels. style.color ist ein Markenname, nicht der Wert
// selbst - der Wert steht in der Palette.
$farbeVon = static function (string $id) use ($design, $gewaehlt): string {
    $eigen = $gewaehlt($id)['color'] ?? null;
    if (is_string($eigen) && $eigen !== '') {
        return $eigen;
    }
    foreach ($design['layers'] as $el) {
        if ((string) $el['id'] === $id) {
            $marke = (string) ($el['style']['color'] ?? '');
            return (string) ($design['palette'][$marke]['value'] ?? '#000000');
        }
    }
    return '#000000';
};

/*
 * Eine Ebene des eingefrorenen Sockels zu ihrer Kennung. $choices traegt nur
 * die Rechte, nicht den Namen - und der Name ist das, was das Paar lesen
 * soll. Ohne ihn stand hier die Kennung selbst, also "foto" oder "zier2":
 * ein Datenbankschluessel. Dieselbe Regel wie im Panel, im Assistenten und
 * bei den Abschnitten, die Kennung nur als letzter Ausweg.
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
    $name = trim((string) ($ebene($id)['label'] ?? ''));
    return $name !== '' ? $name : $id;
};

$label = 'text-[0.62rem] uppercase tracking-[0.18em] text-muted';
$field = 'mt-2 w-full border border-sand-deep bg-cream px-4 py-3 text-sm text-ink';

$inputTypes = ['date' => 'date', 'time' => 'time'];

$fieldTitles = [
    'bride'   => $t('fieldBride'),   'groom'   => $t('fieldGroom'),
    'date'    => $t('fieldDate'),    'time'    => $t('fieldTime'),
    'venue'   => $t('fieldVenue'),   'address' => $t('fieldAddress'),
    'message' => $t('fieldMessage'), 'hashtag' => $t('fieldHashtag'),
];

// Ein Tab, wenn das Design zu ist (Spec §4) - sonst zwei.
$tabs = [$t('editTabTexts')];
if ($darfDesign) {
    $tabs[] = $t('editTabDesign');
}

?>
<?= Ui::pageHero('invite2-edit-hero', $t('editTitle'), I18n::t('nav.invitation2'), $t('editLead')) ?>

<?= Ui::sectionOpen() ?>

<?php /*
   Eigene Regeln statt Tailwind-Klassen: die PHP-Fassung laedt ein FERTIG
   gebautes style.css, kein JIT. Eine Klasse, die dort nicht drinsteht, tut
   schlicht nichts. Dieselben drei Grund-Regeln wie im Assistenten
   (invite-v2-wizard.php) - dort steht der ausfuehrliche Grund - plus ein paar
   eigene fuer diesen Bildschirm.
*/ ?>
<style>
  .wz-grid { margin-inline: auto; max-width: 72rem; }
  @media (min-width: 1024px) {
    .wz-grid { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 3rem; align-items: start; }
    .wz-side { position: sticky; top: 7rem; }
  }
  @media (max-width: 1023px) { .wz-side { margin-top: 3rem; } }
  .wz-card { aspect-ratio: 2 / 3; background: var(--d-bg, #EFE7DC); }
  .wz-quiet { margin: 0; border: 0; padding: 0; min-inline-size: 0; }

  /* Nur der Rahmen fuer die Adresse, die sich nicht aendert - kein Grid. */
  .wz-linkband { margin-inline: auto; max-width: 72rem; }

<?= \Atelier\View::partial('partials/schritt-tabs-css') ?>

  /* Ueberschrift einer Gruppe von Feldern (FAMILIEN, ABLAUF, EBENEN, ...):
     eine zweite, groessere Type-Ebene, damit sie nicht wie ein Feldlabel wie
     BRAUT oder DATUM aussieht, nur zufaellig groesser gesetzt. */
  .wz-heading {
    margin-bottom: .6rem;
    font-family: var(--font-display);
    font-weight: 400;
    font-size: 1.15rem;
    letter-spacing: .01em;
    color: var(--color-ink);
  }

  /* Eine Ebenen-Karte im Design-Tab: ein Rahmen statt eines Trennstrichs ueber
     die volle Breite - so passen mehrere nebeneinander, siehe sm:grid-cols-2
     am Aufruf. */
  .wz-layer { border: 1px solid var(--color-sand-deep); padding: 1rem; }

<?= \Atelier\View::partial('partials/bildfeld-css') ?>

<?= \Atelier\View::partial('partials/ablauf-css') ?>
</style>

<?php if ($ok) : ?>
  <p class="mx-auto mb-8 max-w-2xl border-l-2 border-gold px-5 py-4 text-sm text-ink"><?= e($t('editSaved')) ?></p>
<?php endif; ?>

<?php if ($error !== '') : ?>
  <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
    <?= e($t('error' . ucfirst($error))) ?>
  </p>
<?php endif; ?>

<?php /*
   Der Link eurer Gaeste stand bisher ganz unten, unter dem Speichern-Knopf -
   auf einem Bildschirm, dessen Versprechen genau das ist: er bleibt gleich.
   Deshalb hier, direkt unter der Hero-Zeile, die dasselbe schon sagt (siehe
   editLead) - die Zusage steht, bevor irgendein Feld beruehrt wird.
*/ ?>
<div class="wz-linkband mb-10 flex flex-wrap items-center justify-between gap-4 border border-sand-deep bg-sand/30 px-6 py-5">
  <div>
    <p class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e($t('editGuestLink')) ?></p>
    <p class="mt-1 break-all text-base text-ink">
      <a class="text-gold link-underline" href="<?= e($gastPfad) ?>"><?= e($gastPfad) ?></a>
    </p>
  </div>
  <span class="shrink-0 text-[0.62rem] uppercase tracking-[0.18em] text-gold">
    <?= e($locale === 'de' ? 'bleibt gleich' : 'stays the same') ?>
  </span>
</div>

<div class="wz-grid">
  <form method="post" enctype="multipart/form-data" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php /*
       Der Stand beim Oeffnen reist mit. Stimmt er beim Absenden nicht mehr
       mit dem gespeicherten ueberein, hat jemand in einem anderen Tab
       gespeichert und es wird nichts geschrieben (Spec §7).
    */ ?>
    <input type="hidden" name="stand" value="<?= e($stand) ?>">

    <ol class="wz-tabs mb-10 flex flex-wrap gap-x-8 gap-y-2 border-b border-sand-deep pb-4" data-steps>
      <?php foreach ($tabs as $i => $titel) : ?>
        <?php /*
           Der erste Tab startet aktiv (text-ink), nicht erst durch invite-v2.js'
           show(0) - bei nur einem Tab (Spec §4, editLocked) kehrt das Skript
           vor show(0) zurueck (steps.length < 2), und ohne Skript laeuft
           show() ohnehin nie. So stimmt der erste Tab in allen drei Faellen.
        */ ?>
        <li data-step-label="<?= $i ?>" class="<?= $i === 0 ? 'text-ink' : 'text-muted' ?>">
          <?php /*
             Blosser Text, nie ein <button> vom Server. Der Server weiss nicht,
             ob im Browser ein Skript laeuft - und ein Knopf, den niemand
             bedient, ist genau die Behauptung ohne Wirkung, gegen die dieser
             Bildschirm sonst argumentiert (dasselbe Argument, das hier vorher
             nur fuer den Fall eines einzelnen Tabs gezogen wurde).

             Das Skript am Ende dieser Datei RUESTET diesen Text zum Knopf auf,
             und erst dann, wenn es die Zurueck/Weiter-Knoepfe wirklich
             vorfindet - es also auch etwas zu schalten gibt. Faellt das Skript
             aus, faellt invite-v2.js aus oder ist es ein einzelner Tab, bleibt
             hier Text: nichts sieht klickbar aus, was nicht klickt.
          */ ?>
          <span class="wz-tab-num"><?= $i + 1 ?></span><?= e($titel) ?>
        </li>
      <?php endforeach; ?>
    </ol>

    <fieldset data-step="0" class="space-y-8">
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

      <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
        <?php
          // Der Titel des Grafikers, falls vorhanden - sonst die Kennung als
          // letzter Ausweg (dieselbe Regel wie in DesignSections::html()).
          $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
          if ($secTitel === '') {
              $secTitel = (string) ($abschnitt['title']['de'] ?? '');
          }
          if ($secTitel === '') {
              $secTitel = (string) $sid;
          }
          $hatFeld = $abschnitt['fields'] !== [];
        ?>
        <?php if (!$hatFeld) { continue; } ?>
        <div class="border-t border-sand-deep pt-6">
          <h3 class="wz-heading"><?= e($secTitel) ?></h3>

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

          <?php /*
             Dieselben Felder wie im Assistenten, aus demselben Teil. Vorher
             stand hier eine Kopie, die nur "text" kannte - Hashtag,
             Kontoinhaber, IBAN und Bilder fehlten beim Bearbeiten, und das
             faellt niemandem auf, der die Einladung gerade erst angelegt hat.
          */ ?>
          <?= \Atelier\View::partial('partials/abschnitt-felder', [
              'abschnitt' => $abschnitt, 'sid' => (string) $sid, 'old' => $old, 't' => $t,
              'label' => $label, 'field' => $field, 'locale' => $locale,
              'fotos' => \Atelier\DesignSections::sectionPhotos($daten ?? [], (string) $sid),
          ]) ?>

          <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
            <?= \Atelier\View::partial('partials/ablauf-zeilen', compact('old','t','field','locale')) ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </fieldset>

    <?php if ($darfDesign) : ?>
      <fieldset data-step="1" class="space-y-10">
        <div>
          <h3 class="wz-heading"><?= e($locale === 'de' ? 'Farben & Schrift' : 'Colors & type') ?></h3>

          <div class="grid gap-6 sm:grid-cols-2">
            <?php foreach ($choices['palette'] as $marke => $eintrag) : ?>
              <?php
                $vorher = $wahl['palette'][$marke] ?? null;
                $wert = is_string($vorher) && $vorher !== '' ? $vorher : (string) $eintrag['value'];
              ?>
              <div>
                <label class="<?= $label ?>" for="p-<?= e((string) $marke) ?>">
                  <?= e($eintrag['label'][$locale] ?? $eintrag['label']['de'] ?? $marke) ?>
                </label>
                <input id="p-<?= e((string) $marke) ?>" type="color" name="palette_<?= e((string) $marke) ?>"
                       value="<?= e($wert) ?>" class="<?= $field ?> h-12">
              </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <?php foreach ($choices['fonts'] as $marke => $eintrag) : ?>
              <?php
                $vorher = $wahl['fonts'][$marke] ?? null;
                $wert = is_string($vorher) && $vorher !== '' ? $vorher : (string) $eintrag['family'];
              ?>
              <div>
                <label class="<?= $label ?>" for="s-<?= e((string) $marke) ?>"><?= e((string) $marke) ?></label>
                <select id="s-<?= e((string) $marke) ?>" name="fonts_<?= e((string) $marke) ?>" class="<?= $field ?>">
                  <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                    <option value="<?= e($familie) ?>" <?= $wert === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php
          // Nur Ebenen mit mindestens einem bedienbaren Recht - eine Karte
          // ohne jedes Feld waere ein leerer Rahmen mit nichts als ihrer
          // Kennung darin (dieselbe Regel wie im Assistenten).
          $wzLayers = [];
          foreach ($choices['layers'] as $id => $rechte) {
              if ($rechte['color'] || $rechte['font'] || $rechte['text'] || $rechte['hide'] || $rechte['photo']) {
                  $wzLayers[$id] = $rechte;
              }
          }
        ?>
        <?php if ($wzLayers !== []) : ?>
          <div class="border-t border-sand-deep pt-8">
            <h3 class="wz-heading"><?= e($locale === 'de' ? 'Ebenen' : 'Layers') ?></h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <?php foreach ($wzLayers as $id => $rechte) : ?>
                <?php $eigen = $gewaehlt((string) $id); ?>
                <div class="wz-layer">
                  <div class="<?= $label ?>"><?= e($ebeneName((string) $id)) ?></div>

                  <?php if ($rechte['color']) : ?>
                    <input type="color" name="layer_color_<?= e((string) $id) ?>" value="<?= e($farbeVon((string) $id)) ?>" class="mt-3 h-10 w-full border border-sand-deep bg-cream">
                  <?php endif; ?>

                  <?php if ($rechte['font']) : ?>
                    <?php $fontVorher = is_string($eigen['font'] ?? null) ? $eigen['font'] : ''; ?>
                    <select name="layer_font_<?= e((string) $id) ?>" class="<?= $field ?>">
                      <option value=""><?= e($locale === 'de' ? '— wie im Design —' : '— as the design has it —') ?></option>
                      <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
                        <option value="<?= e($familie) ?>" <?= $fontVorher === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php endif; ?>

                  <?php if ($rechte['text']) : ?>
                    <?php
                      $textVorher = is_array($eigen['text'] ?? null) ? $eigen['text'] : [];
                      $textWert = is_string($textVorher['de'] ?? null) ? $textVorher['de'] : '';
                    ?>
                    <input type="text" name="layer_text_<?= e((string) $id) ?>" class="<?= $field ?>" maxlength="600" value="<?= e($textWert) ?>">
                  <?php endif; ?>

                  <?php $ebeneTyp = (string) ($ebene((string) $id)['type'] ?? ''); ?>
                  <?php if ($rechte['photo'] && $ebeneTyp === 'video') : ?>
                    <?php
                      /*
                       * Auswahl statt Upload - dieselbe Ueberlegung wie im
                       * Assistenten. Vorausgewaehlt ist der Film, der heute
                       * laeuft: die eigene Wahl, sonst der der Vorlage.
                       */
                      $filmJetzt = is_string($eigen['src'] ?? null) && $eigen['src'] !== ''
                          ? (string) $eigen['src']
                          : (string) ($ebene((string) $id)['src'] ?? '');
                    ?>
                    <label class="mt-4 block <?= $label ?>">
                      <?= e($locale === 'de' ? 'Film' : 'Film') ?>
                      <select name="film_<?= e((string) $id) ?>" class="<?= $field ?> normal-case tracking-normal">
                        <option value=""><?= $locale === 'de' ? 'Unveraendert' : 'Unchanged' ?></option>
                        <?php foreach ($filme as $film) : ?>
                          <option value="<?= e($film['id']) ?>" <?= $film['mp4'] === $filmJetzt ? 'selected' : '' ?>>
                            <?= e($film['label'] ?: $film['id']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  <?php elseif ($rechte['photo']) : ?>
                    <?php
                      /*
                       * Was heute auf der Einladung steht - und das ist hier
                       * NICHT das Bild der Vorlage: hat das Paar beim
                       * Veroeffentlichen ein eigenes hochgeladen, liegt dessen
                       * Pfad in seiner Wahl. Erst wenn dort keiner steht, gilt
                       * der des eingefrorenen Sockels. Genau diese Reihenfolge
                       * benutzt auch das Speichern (sammleWahl), sonst zeigte
                       * die Platte etwas anderes als die Karte.
                       *
                       * safeSrc(), weil der Wert aus dem Dokument in ein
                       * src-Attribut geht - dieselbe Pruefung wie auf der
                       * Karte selbst.
                       */
                      $fotoEigen = is_string($eigen['src'] ?? null) ? $eigen['src'] : '';
                      $fotoJetzt = Design::safeSrc($fotoEigen !== ''
                          ? $fotoEigen
                          : (string) ($ebene((string) $id)['src'] ?? ''));
                    ?>
                    <div class="wz-photo wz-photo--schmal mt-4">
                      <div>
                        <div class="wz-photo-platte">
                          <?php if ($fotoJetzt !== '') : ?>
                            <img src="<?= e($fotoJetzt) ?>" alt="" data-photo-preview="<?= e((string) $id) ?>">
                          <?php else : ?>
                            <img alt="" hidden data-photo-preview="<?= e((string) $id) ?>">
                            <span class="wz-photo-leer" data-photo-empty="<?= e((string) $id) ?>"><?= e($t('photoEmpty')) ?></span>
                          <?php endif; ?>
                        </div>
                        <p class="<?= $label ?> wz-photo-bu"
                           data-photo-caption="<?= e((string) $id) ?>"
                           data-photo-chosen="<?= e($t('photoChosen')) ?>"><?= $fotoJetzt !== '' ? e($t('photoCurrent')) : '' ?></p>
                      </div>

                      <div>
                        <?php /* Wie im Assistenten: der Server druckt das nackte
                                 Feld, das Skript am Fuss dieser Datei macht
                                 daraus den Knopf im Haus-Schnitt. Ohne Skript
                                 bleibt es stehen und tut vollstaendig seine
                                 Arbeit. */ ?>
                        <div data-photo-slot="<?= e((string) $id) ?>"
                             data-photo-label="<?= e($t('photoChoose')) ?>">
                          <input id="b-<?= e((string) $id) ?>" type="file" name="layer_src_<?= e((string) $id) ?>"
                                 accept="image/jpeg,image/png,image/webp" class="<?= $field ?>"
                                 data-photo-input="<?= e((string) $id) ?>">
                        </div>
                        <p class="wz-photo-hint"><?= e($t('photoHint')) ?></p>
                        <p class="wz-photo-hint"><?= e($t('editPhotoNote')) ?></p>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($rechte['hide']) : ?>
                    <label class="mt-3 flex items-center gap-2 text-[0.62rem] uppercase tracking-[0.18em] text-muted">
                      <input type="checkbox" name="layer_hidden_<?= e((string) $id) ?>" <?= !empty($eigen['hidden']) ? 'checked' : '' ?>>
                      <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
                    </label>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php
          // Nur die Abschnitte, die sich ueberhaupt ausblenden lassen -
          // dieselbe Bedingung wie zuvor, nur einmal vorab statt in jeder
          // Runde neu geprueft.
          $wzHideSections = [];
          foreach ($choices['sections'] as $sid => $abschnitt) {
              if ($abschnitt['hide']) {
                  $wzHideSections[$sid] = $abschnitt;
              }
          }
        ?>
        <?php if ($wzHideSections !== []) : ?>
          <div class="border-t border-sand-deep pt-8">
            <h3 class="wz-heading"><?= e($locale === 'de' ? 'Abschnitte' : 'Sections') ?></h3>
            <div class="divide-y divide-sand-deep border border-sand-deep">
              <?php foreach ($wzHideSections as $sid => $abschnitt) : ?>
                <?php
                  $sekWahl = is_array($wahl['sections'] ?? null) ? $wahl['sections'] : [];
                  $aus = !empty($sekWahl[$sid]['hidden']);
                  $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
                  if ($secTitel === '') { $secTitel = (string) ($abschnitt['title']['de'] ?? ''); }
                  if ($secTitel === '') { $secTitel = (string) $sid; }
                ?>
                <label class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-ink">
                  <span><?= e($secTitel) ?></span>
                  <span class="flex items-center gap-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted">
                    <input type="checkbox" name="sec_hidden_<?= e((string) $sid) ?>" <?= $aus ? 'checked' : '' ?>>
                    <?= e($t('sectionHide')) ?>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </fieldset>
    <?php else : ?>
      <?php /*
         Kein stiller Verzicht, sondern ein Satz auf dem Bildschirm (Spec §4):
         diese Einladung hat keine gespeicherte Wahl, ihr Sockel ist bereits
         personalisiert, und eine neue Auswahl darauf waere verlustbehaftet.
         Der linke Goldstrich ist derselbe wie bei der Erfolgsmeldung oben -
         eine Auskunft, keine Fehlermeldung (die traegt einen vollen Rahmen,
         siehe $error oben).
      */ ?>
      <div class="mt-10 border-t border-sand-deep pt-6">
        <div class="border-l-2 border-gold bg-sand/20 px-6 py-5">
          <p class="text-[0.62rem] uppercase tracking-[0.18em] text-gold"><?= e($t('editTabDesign')) ?></p>
          <p class="mt-3 text-[0.9rem] leading-relaxed text-ink-soft"><?= e($t('editLocked')) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <div class="mt-10 border-t border-sand-deep pt-6">
      <button type="submit" class="border border-ink px-8 py-4 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= e($t('editSave')) ?>
      </button>
    </div>
  </form>

  <aside class="wz-side">
    <p class="mb-4 text-[0.62rem] uppercase tracking-[0.18em] text-muted">
      <?= e($locale === 'de' ? 'So sehen es eure Gäste' : 'What your guests see') ?>
    </p>

    <style><?= $styles ?><?= $sectionCss ?></style>
    <?php /*
       Absichtlich OHNE data-preview: invite-v2.js' paint() wuerde beim Laden
       sofort darueberschreiben, mit den rohen Formularwerten statt dem, was
       der Server gerade gezeichnet hat - wedding_date kaeme als "2027-06-19"
       statt "19. Juni 2027" heraus, wedding_weekday bliebe leer, weil
       JavaScript den Wochentag nicht herleiten kann. Im Assistenten ist das
       richtig: dort ist die Karte eine grobe, lebendige Skizze von etwas, das
       noch entsteht. Hier ist sie das nicht - diese Einladung ist bereits
       veroeffentlicht, und dieser Bildschirm existiert genau dafuer, dass ein
       Paar sieht, was seine Gaeste sehen. Der Tausch: die Karte folgt der
       Tastatur nicht mehr live, dafuer zeigt sie immer die Wahrheit, die der
       Server auch drucken wuerde. Die Tabs bleiben unberuehrt - preview()
       endet nach der Schritt-Umschaltung mit return, wenn es keinen
       [data-preview]-Knoten findet.
    */ ?>
    <div class="<?= e($scope) ?> wz-card mx-auto w-full max-w-xs"
         style="position:relative;container-type:inline-size;"><?= $karte ?></div>

    <?php /*
       disabled fieldset, kein blosses CSS: der rsvp-Abschnitt druckt ein
       echtes Formular mit Absenden-Knopf. In der Vorschau darf es nicht
       abschicken - es steht ausserhalb dieses Formulars und wuerde eine
       eigene Anfrage an dieselbe Adresse stellen. Ein deaktiviertes fieldset
       schaltet jedes Bedienelement darin ab, in jedem Browser. Die Bildunterschrift
       oben ordnet den ganzen Block (Karte und Abschnitte, RSVP eingeschlossen)
       als Ansicht ein, nicht als etwas zum Ausfuellen; .wz-sections daempft
       das RSVP-Formular zusaetzlich mit Deckkraft und setzt die Ueberschriften
       der Abschnitte (.d-sec-title, aus DesignSections::css()) auf die
       Auszeichnungsschrift der Karte statt auf die fette serifenlose
       Standardschrift - dieser Style-Block steht bewusst NACH dem obigen mit
       $sectionCss, bei gleicher Spezifitaet gewinnt die spaetere Regel.

       Ohne data-sections: siehe der Kommentar am Kopf dieser Datei.
    */ ?>
    <fieldset disabled class="wz-quiet">
      <div class="<?= e($scope) ?> wz-sections mx-auto mt-6 w-full max-w-xs text-[0.8rem]"><?= $abschnitte ?></div>
    </fieldset>
    <style>
      .wz-sections .d-sec-title { font-family: var(--font-display); font-weight: 400; font-size: 1.15rem; letter-spacing: .01em; }
      .wz-sections .d-sec-form { opacity: .55; }
    </style>
  </aside>
</div>

<?php /*
   Dieses Skript steht ausserhalb von .wz-grid, nicht mehr zwischen Formular
   und Vorschau. Dort war es zwar unschaedlich - die Browser-Grundregel
   script{display:none} macht daraus kein Grid-Element -, aber die
   Zweispaltigkeit haette dann an einer Regel gehangen, die niemand hier
   liest.

   Was es tut: es ruestet die Reiter zu Schaltflaechen auf und laesst sie die
   Zurueck/Weiter-Knoepfe bedienen, statt selbst einen zweiten Zustand fuer
   den aktuellen Schritt zu fuehren. invite-v2.js haelt "at" in seiner eigenen
   Closure - griffen wir hier direkt auf [hidden] zu, widerspraechen sich zwei
   Wahrheiten (unsere und seine), und die Zurueck/Weiter-Knoepfe zeigten
   irgendwann den falschen Schritt an. Ein Klick auf einen Reiter klickt darum
   den passenden echten Knopf: dieselbe Pflichtfeld-Pruefung, derselbe Scroll,
   dieselbe Beschriftung wie bei einem Klick von Hand.

   Aufgeruestet wird ERST, wenn feststeht, dass es etwas zu schalten gibt -
   sonst bleibt der Reiter Text. Deshalb DOMContentLoaded und nicht sofort:
   invite-v2.js kommt mit defer (layout.php), laeuft also nach dem Parsen und
   VOR diesem Ereignis. Zum Zeitpunkt des Aufrufs stehen seine Knoepfe also
   schon da. Sofort ausgefuehrt fanden wir nichts vor und muessten raten.

   nonce Pflicht, nicht Kosmetik: Http::harden() nimmt in script-src 'self'
   und den Nonce dieser Antwort auf (Http::nonce(), siehe Http.php - dort
   stehen beide Traeger namentlich; bei hinterlegter Messkennung kommen noch
   die Messdienste hinzu). Ein <script> ohne diesen Nonce fuehrt der Browser
   gar nicht erst aus: kein Fehler in der Konsole, es passiert einfach nichts.
*/ ?>
<script nonce="<?= e(Http::nonce()) ?>">
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  var form = document.querySelector('[data-wizard]');
  if (!form) return;

  /*
   * Das Bildfeld: Platte, Knopf, Dateiname - dasselbe wie im Assistenten,
   * mit EINEM Unterschied, und der ist der Kern dieses Bildschirms: die
   * Karte bleibt unberuehrt.
   *
   * Im Assistenten wandert das gewaehlte Bild auch auf die Karte, weil dort
   * etwas entsteht. Hier ist die Einladung veroeffentlicht, die Karte zeigt
   * absichtlich, was der SERVER zeichnen wuerde (siehe der Kommentar zu
   * data-preview weiter oben), und ein Bild, das nur im Browser darauf
   * liegt, waere genau die Vermischung, die dieser Bildschirm vermeidet.
   * Die Platte beweist die Wahl; die Karte sagt die Wahrheit.
   *
   * FileReader und nicht createObjectURL: img-src erlaubt data:, aber nicht
   * blob: - mit einer blob-URL bliebe die Platte leer.
   */
  Array.prototype.forEach.call(document.querySelectorAll('[data-photo-input]'), function (feld) {
    var kennung = feld.getAttribute('data-photo-input');
    var bild  = document.querySelector('[data-photo-preview="' + kennung + '"]');
    if (!bild) return;
    var titel = document.querySelector('[data-photo-caption="' + kennung + '"]');
    var leer  = document.querySelector('[data-photo-empty="' + kennung + '"]');
    var fach  = document.querySelector('[data-photo-slot="' + kennung + '"]');
    var name  = null;

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

      if (name) {
        name.textContent = datei.name;
        name.hidden = false;
      }

      var leser = new FileReader();
      leser.onload = function () {
        bild.src = leser.result;
        bild.hidden = false;
        if (leer) leer.hidden = true;
        if (titel) titel.textContent = titel.getAttribute('data-photo-chosen');
      };
      leser.readAsDataURL(datei);
    });
  });


<?= \Atelier\View::partial('partials/schritt-tabs-js') ?>
});
</script>

<?= Ui::sectionClose() ?>
