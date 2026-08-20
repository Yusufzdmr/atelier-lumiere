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
?>
<?= Ui::pageHero('invite2-edit-hero', $t('editTitle'), I18n::t('nav.invitation2'), $t('editLead')) ?>

<?= Ui::sectionOpen() ?>

<?php /*
   Eigene Regeln statt Tailwind-Klassen: die PHP-Fassung laedt ein FERTIG
   gebautes style.css, kein JIT. Eine Klasse, die dort nicht drinsteht, tut
   schlicht nichts. Dieselben drei Regeln wie im Assistenten
   (invite-v2-wizard.php) - dort steht der ausfuehrliche Grund.
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
</style>

<?php if ($ok) : ?>
  <p class="mx-auto mb-8 max-w-2xl border-l-2 border-gold px-5 py-4 text-sm text-ink"><?= e($t('editSaved')) ?></p>
<?php endif; ?>

<?php if ($error !== '') : ?>
  <p class="mx-auto mb-8 max-w-2xl border border-ink px-5 py-4 text-sm text-ink">
    <?= e($t('error' . ucfirst($error))) ?>
  </p>
<?php endif; ?>

<div class="wz-grid">
  <form method="post" enctype="multipart/form-data" data-wizard>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php /*
       Der Stand beim Oeffnen reist mit. Stimmt er beim Absenden nicht mehr
       mit dem gespeicherten ueberein, hat jemand in einem anderen Tab
       gespeichert und es wird nichts geschrieben (Spec §7).
    */ ?>
    <input type="hidden" name="stand" value="<?= e($stand) ?>">

    <ol class="mb-10 flex flex-wrap gap-x-6 gap-y-2 border-b border-sand-deep pb-4 text-[0.62rem] uppercase tracking-[0.16em]" data-steps>
      <?php foreach ($tabs as $i => $titel) : ?>
        <li data-step-label="<?= $i ?>" class="text-muted"><?= $i + 1 ?>. <?= e($titel) ?></li>
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
          <div class="<?= $label ?>"><?= e($secTitel) ?></div>

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
               Feste Zeilenzahl statt Hinzufuegen-Knopf: ohne Skript
               funktioniert das Formular sonst nicht - dieselbe Entscheidung
               wie im Assistenten.
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
    </fieldset>

    <?php if ($darfDesign) : ?>
      <fieldset data-step="1" class="space-y-8">
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

        <?php foreach ($choices['layers'] as $id => $rechte) : ?>
          <?php $eigen = $gewaehlt((string) $id); ?>
          <div class="border-t border-sand-deep pt-6">
            <div class="<?= $label ?>"><?= e((string) $id) ?></div>

            <?php if ($rechte['color']) : ?>
              <input type="color" name="layer_color_<?= e((string) $id) ?>" value="<?= e($farbeVon((string) $id)) ?>" class="<?= $field ?> h-12">
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
              <label class="mt-3 flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="layer_hidden_<?= e((string) $id) ?>" <?= !empty($eigen['hidden']) ? 'checked' : '' ?>>
                <?= e($locale === 'de' ? 'ausblenden' : 'hide') ?>
              </label>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php foreach ($choices['sections'] as $sid => $abschnitt) : ?>
          <?php if (!$abschnitt['hide']) { continue; } ?>
          <?php
            $sekWahl = is_array($wahl['sections'] ?? null) ? $wahl['sections'] : [];
            $aus = !empty($sekWahl[$sid]['hidden']);
            $secTitel = (string) ($abschnitt['title'][$locale] ?? '');
            if ($secTitel === '') { $secTitel = (string) ($abschnitt['title']['de'] ?? ''); }
            if ($secTitel === '') { $secTitel = (string) $sid; }
          ?>
          <div class="border-t border-sand-deep pt-6">
            <div class="<?= $label ?>"><?= e($secTitel) ?></div>
            <label class="mt-3 flex items-center gap-2 text-sm text-muted">
              <input type="checkbox" name="sec_hidden_<?= e((string) $sid) ?>" <?= $aus ? 'checked' : '' ?>>
              <?= e($t('sectionHide')) ?>
            </label>
          </div>
        <?php endforeach; ?>
      </fieldset>
    <?php else : ?>
      <?php /*
         Kein stiller Verzicht, sondern ein Satz auf dem Bildschirm (Spec §4):
         diese Einladung hat keine gespeicherte Wahl, ihr Sockel ist bereits
         personalisiert, und eine neue Auswahl darauf waere verlustbehaftet.
      */ ?>
      <p class="mt-10 border-t border-sand-deep pt-6 text-[0.85rem] leading-relaxed text-muted">
        <?= e($t('editLocked')) ?>
      </p>
    <?php endif; ?>

    <div class="mt-10 border-t border-sand-deep pt-6">
      <button type="submit" class="border border-ink px-8 py-4 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= e($t('editSave')) ?>
      </button>

      <p class="mt-6 text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e($t('editGuestLink')) ?></p>
      <p class="mt-2 break-all text-sm">
        <a class="text-gold underline" href="<?= e($gastPfad) ?>"><?= e($gastPfad) ?></a>
      </p>
    </div>
  </form>

  <aside class="wz-side">
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
       schaltet jedes Bedienelement darin ab, in jedem Browser.

       Ohne data-sections: siehe der Kommentar am Kopf dieser Datei.
    */ ?>
    <fieldset disabled class="wz-quiet">
      <div class="<?= e($scope) ?> mx-auto mt-6 w-full max-w-xs text-[0.8rem]"><?= $abschnitte ?></div>
    </fieldset>
  </aside>
</div>

<?= Ui::sectionClose() ?>
