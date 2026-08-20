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

// Wie viele Ablauf-Zeilen stehen offen? Sichtbar sind die ausgefuellten und
// eine leere zum Weiterschreiben, der Rest wandert hinter <details> (siehe
// unten). Acht Zeilen bleiben acht Zeilen - das Speichern liest alle acht,
// <details> aendert nur den Anblick, nicht welche Felder es gibt.
$progLetzteVoll = -1;
for ($z = 0; $z < 8; $z++) {
    if ($old('prog_time_' . $z) !== '' || $old('prog_title_' . $z) !== '') {
        $progLetzteVoll = $z;
    }
}
$progOffen = min(7, $progLetzteVoll + 1);
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

  /*
   * Die Tabs: bisher genauso klein und grau wie ein Feldlabel, deshalb keine
   * Navigation im Blick. font-display statt der winzigen Grossschrift, dazu
   * ein Strich unter dem aktiven Tab. invite-v2.js tauscht bei jedem
   * Schrittwechsel die ganze class des <li> gegen entweder "text-ink" oder
   * "text-muted" aus (show() in invite-v2.js) - keine dritte Klasse bleibt
   * erhalten. Diese Regeln haengen deshalb bewusst nur an genau diesen beiden
   * Klassen, nicht an einer eigenen: sie greifen so unveraendert, ob das
   * Skript laeuft oder nicht.
   *
   * Bei zwei oder mehr Tabs steckt in jedem <li> ein echtes <button> (siehe
   * Aufruf weiter unten) - das macht den Tab selbst fokussierbar und
   * klickbar, bedient von einem eigenen kleinen Skript, das die von
   * invite-v2.js angehaengten Zurueck/Weiter-Knoepfe anklickt (siehe dort).
   * Bei nur einem Tab (editLocked) steht dort blosser Text: ohne ein Ziel zum
   * Umschalten waere ein Knopf mit Zeigefinger-Cursor eine Behauptung ohne
   * Wirkung. Farbe und Groesse haengen am <li>, nicht am <button>, und werden
   * von diesem per color:inherit uebernommen.
   */
  .wz-tabs [data-step-label] {
    position: relative;
    padding-bottom: .85rem;
    font-family: var(--font-display);
    font-size: 1.05rem;
    letter-spacing: .01em;
  }
  .wz-tabs [data-step-label] > button {
    border: 0; margin: 0; padding: 0; background: none;
    font: inherit; color: inherit; cursor: pointer;
  }
  .wz-tabs [data-step-label].text-ink { color: var(--color-ink); }
  .wz-tabs [data-step-label].text-ink::after {
    content: '';
    position: absolute; left: 0; right: 0; bottom: -1px;
    height: 2px; background: var(--color-gold);
  }
  .wz-tabs [data-step-label].text-muted { color: var(--color-muted); }
  .wz-tab-num { margin-right: .4em; font-size: .8em; color: var(--color-gold); }

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

  /*
   * Die uebrigen, meist leeren Ablauf-Zeilen: reines <details>, kein Skript.
   * list-style entfernt die Standard-Markierung (Chrome/Safari zusaetzlich
   * ueber ::-webkit-details-marker), das + / - davor kommt aus content.
   */
  .wz-more { margin-top: .75rem; border: 1px solid var(--color-sand-deep); }
  .wz-more > summary {
    display: flex; align-items: center; gap: .5rem;
    padding: .75rem 1rem;
    cursor: pointer;
    list-style: none;
    font-size: .72rem; text-transform: uppercase; letter-spacing: .16em;
    color: var(--color-muted);
  }
  .wz-more > summary::-webkit-details-marker { display: none; }
  .wz-more > summary::before { content: '+'; color: var(--color-gold); font-size: 1rem; line-height: 1; }
  .wz-more[open] > summary::before { content: '\2013'; }
  .wz-more[open] > summary { border-bottom: 1px solid var(--color-sand-deep); }
  .wz-more-body { padding: .25rem 1rem 1rem; }
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
          <?php if (count($tabs) > 1) : ?>
            <?php /*
               Ein echtes <button>, kein blosses <li>: nur so ist der Tab per
               Tastatur erreichbar. type="button" - er darf das Formular nicht
               absenden. Was ein Klick darauf bewirkt, steht im Skript am Ende
               dieser Datei, nicht hier.
            */ ?>
            <button type="button"><span class="wz-tab-num"><?= $i + 1 ?></span><?= e($titel) ?></button>
          <?php else : ?>
            <?php // Ein einzelner Tab schaltet nichts um - kein Knopf, sonst ein Zeigefinger-Cursor ohne Wirkung. ?>
            <span class="wz-tab-num"><?= $i + 1 ?></span><?= e($titel) ?>
          <?php endif; ?>
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

          <?php if (in_array('text', $abschnitt['fields'], true)) : ?>
            <div class="mt-3">
              <label class="<?= $label ?>" for="st-<?= e((string) $sid) ?>"><?= e($t('sectionText')) ?></label>
              <textarea id="st-<?= e((string) $sid) ?>" name="sec_text_<?= e((string) $sid) ?>" rows="4" maxlength="1200"
                        class="<?= $field ?>"><?= e($old('sec_text_' . $sid)) ?></textarea>
              <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('sectionTextNote')) ?></p>
            </div>
          <?php endif; ?>

          <?php if (in_array('program', $abschnitt['fields'], true)) : ?>
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
              <div class="mt-3 grid gap-3 sm:grid-cols-[5rem_1fr]">
                <input name="prog_time_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                       placeholder="<?= e($t('programTime')) ?>" value="<?= e($old('prog_time_' . $z)) ?>">
                <input name="prog_title_<?= $z ?>" class="<?= $field ?>" maxlength="80"
                       placeholder="<?= e($t('programTitle')) ?>" value="<?= e($old('prog_title_' . $z)) ?>">
              </div>
            <?php endfor; ?>
            <?php if ($progOffen < 7) : ?>
                  </div>
                </details>
            <?php endif; ?>
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
                  <div class="<?= $label ?>"><?= e((string) $id) ?></div>

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

                  <?php if ($rechte['photo']) : ?>
                    <div class="mt-3">
                      <label class="<?= $label ?>" for="b-<?= e((string) $id) ?>"><?= e($t('editPhoto')) ?></label>
                      <input id="b-<?= e((string) $id) ?>" type="file" name="layer_src_<?= e((string) $id) ?>"
                             accept="image/jpeg,image/png,image/webp" class="<?= $field ?>">
                      <p class="mt-2 text-[0.8rem] text-muted"><?= e($t('editPhotoNote')) ?></p>
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

  <?php /*
     Die Tabs bedienen die Zurueck/Weiter-Knoepfe, statt selbst einen zweiten
     Zustand fuer den aktuellen Schritt zu fuehren. invite-v2.js haelt "at"
     in seiner eigenen Closure - griffen wir hier direkt auf [hidden] zu,
     widerspraechen sich zwei Wahrheiten (unsere und seine), und die
     Zurueck/Weiter-Knoepfe zeigten irgendwann den falschen Schritt an. Ein
     Klick auf einen Tab klickt darum den passenden echten Knopf, so oft wie
     noetig (bei zwei Schritten immer genau einmal) - dieselbe Pflichtfeld-
     Pruefung, derselbe Scroll, dieselbe Beschriftung wie bei einem Klick von
     Hand.

     invite-v2.js haengt Zurueck/Weiter nur an, wenn es mindestens zwei
     Schritte gibt (dieselbe Abfrage: steps.length < 2, siehe dort) - bei nur
     einem Tab (editLocked) gibt es gar keine <button>-Tabs (siehe Aufruf
     oben) und dieses Skript kehrt sofort zurueck. Ohne Skript ueberhaupt
     laeuft auch dieses hier nie - beide Schritte stehen dann wie immer
     untereinander.

     nonce Pflicht, nicht Kosmetik: Http::harden() setzt script-src nur auf
     'self' und den Nonce dieser Antwort (Http::nonce(), siehe Http.php und
     dasselbe Muster im Layout fuer das ld+json-Skript) - ein <script> ohne
     diesen Nonce fuehrt der Browser gar nicht erst aus. Kein Fehler in der
     Konsole, es passiert einfach nichts.
  */ ?>
  <script nonce="<?= e(Http::nonce()) ?>">
  (function () {
    'use strict';
    var form = document.querySelector('[data-wizard]');
    if (!form) return;

    var tabButtons = Array.prototype.slice.call(form.querySelectorAll('[data-step-label] > button'));
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
    if (tabButtons.length < 2 || steps.length < 2) return;

    function current() {
      for (var n = 0; n < steps.length; n++) {
        if (!steps[n].hidden) return n;
      }
      return 0;
    }

    // Die von invite-v2.js angehaengten Knoepfe: type=button, aber ausserhalb
    // jedes [data-step-label] - so lassen sie sich von den Tab-Knoepfen
    // selbst unterscheiden, ohne eine eigene Kennung zu brauchen.
    function navButtons() {
      return Array.prototype.slice.call(form.querySelectorAll('button[type=button]'))
        .filter(function (b) { return !b.closest('[data-step-label]'); });
    }

    tabButtons.forEach(function (btn, i) {
      btn.addEventListener('click', function () {
        var nav = navButtons();
        if (nav.length < 2) return; // invite-v2.js hat sie (noch) nicht angehaengt
        var back = nav[0];
        var next = nav[1];
        var guard = steps.length + 2;
        while (current() !== i && guard-- > 0) {
          (i > current() ? next : back).click();
        }
      });
    });
  })();
  </script>

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

<?= Ui::sectionClose() ?>
