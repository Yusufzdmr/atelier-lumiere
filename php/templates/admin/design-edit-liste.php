<?php
/**
 * Die linke Spalte: was die Seite zeigt, und in welcher Reihenfolge.
 *
 * Sie verwaltet nur den Bau der Seite - Reihenfolge, Sichtbarkeit,
 * Auswaehlen, Wegnehmen. Was ein Abschnitt sagt und wie er aussieht, steht
 * rechts in seiner Tafel (design-edit-tafeln.php).
 *
 * Die Reihenfolge haengt an EINEM versteckten Feld, einer Reihe von Nummern.
 * Die Nummer ist die Kennung einer Zeile und aendert sich beim Schieben NICHT
 * - sonst verloere beim Umsortieren jedes Feld seinen Wert, weil die
 * Feldnamen die Nummer tragen (sec_title_de_3 und so weiter). Wer nicht in
 * der Reihe steht, ist geloescht: Umordnen und Wegnehmen sind dieselbe
 * Bewegung, deshalb dasselbe Feld.
 *
 * Ohne JavaScript bleibt die Reihe stehen, wie der Server sie geschrieben hat
 * - dann aendert sich nichts, statt dass jemand einen Abschnitt daran
 * verliert, dass ein Skript nicht laedt.
 *
 * Erwartet aus design-edit.php: $design, $tr, $label, $feld.
 * Gibt weiter an design-edit-tafeln.php: $sekmeler.
 *
 * @var array<string,mixed> $design
 */

use Atelier\SectionRegistry;
use function Atelier\e;

/*
 * Immer eine leere Zeile mehr, damit ein Abschnitt ohne Umweg dazukommt.
 * Sie traegt dieselben Schluessel wie eine echte - sonst muesste jede Stelle,
 * die die Liste liest, den Sonderfall kennen.
 */
$sekmeler = $design['sections'];
$sekmeler[] = ['id' => '', 'type' => '', 'variant' => 'default', 'settings' => [],
               'title' => ['de' => '', 'en' => ''],
               'enabled' => false, 'style' => ['color' => '', 'font' => ''],
               'permissions' => ['edit' => false, 'hide' => false]];

$neuIndex = array_key_last($sekmeler);
$sprache  = $tr ? 'tr' : 'de';
?>

<?php /*
   Der Weg zurueck. Ohne ihn ist die Tafel der Vorlage nach dem ersten Klick
   auf einen Abschnitt nicht mehr erreichbar - und dort stehen Farben,
   Schriften, Ebenen und die Veroeffentlichung.
*/ ?>
<ul class="b-liste" style="margin-bottom:1rem;">
  <li class="b-zeile" data-aktiv data-sec-zeile="thema">
    <button type="button" class="b-greifer" data-sec-waehl>
      <?= $tr ? 'Tema' : 'Vorlage' ?>
      <small><?= $tr ? 'renkler, yazılar, katmanlar' : 'Farben, Schriften, Ebenen' ?></small>
    </button>
  </li>
</ul>

<div class="b-fein" style="margin-bottom:0.6rem;"><?= $tr ? 'Bölümler' : 'Abschnitte' ?></div>

<input type="hidden" name="sec_reihenfolge"
       value="<?= e(implode(',', array_keys($sekmeler))) ?>" data-sec-reihe>

<ul class="b-liste" data-sec-liste>
  <?php foreach ($sekmeler as $i => $abschnitt) : ?>
    <?php
      $neu      = $i === $neuIndex;
      $art      = (string) $abschnitt['type'];
      $gestalt  = (string) ($abschnitt['variant'] ?? 'default');
      $etikett  = SectionRegistry::variants($art)[$gestalt][$sprache] ?? $gestalt;
      $name     = trim((string) $abschnitt['title']['de']) !== ''
          ? (string) $abschnitt['title']['de']
          : ((string) $abschnitt['id'] !== '' ? (string) $abschnitt['id'] : ($tr ? '(adsız)' : '(ohne Titel)'));
    ?>
    <li class="b-zeile" data-sec-zeile="<?= $i ?>" <?= $neu ? 'data-sec-neu' : '' ?>>
      <?php if ($neu) : ?>
        <button type="button" class="b-greifer" data-sec-waehl>
          <?= $tr ? '+ Bölüm ekle' : '+ Abschnitt' ?>
          <small><?= $tr ? 'kimlik ve tür gir, kaydet' : 'Kennung und Art eintragen, speichern' ?></small>
        </button>
      <?php else : ?>
        <?php /*
           Das Auge steht in der ZEILE und nicht in der Tafel: an- und
           abschalten gehoert zum Bau der Seite, nicht zum Inhalt eines
           Abschnitts - und man will beim Umschalten die ganze Liste sehen.
        */ ?>
        <label class="b-auge" title="<?= $tr ? 'açık/kapalı' : 'an/aus' ?>">
          <input type="checkbox" name="sec_on_<?= $i ?>" <?= $abschnitt['enabled'] ? 'checked' : '' ?>>
        </label>
        <button type="button" class="b-greifer" data-sec-waehl>
          <?= e($name) ?>
          <small><?= e($art) ?><?= $art !== '' ? ' · ' . e((string) $etikett) : '' ?></small>
        </button>
        <button type="button" class="b-knopf" data-sec-hoch title="<?= $tr ? 'yukarı' : 'nach oben' ?>">↑</button>
        <button type="button" class="b-knopf" data-sec-runter title="<?= $tr ? 'aşağı' : 'nach unten' ?>">↓</button>
        <button type="button" class="b-knopf" data-sec-weg
                data-wort-weg="<?= $tr ? 'sil' : 'weg' ?>"
                data-wort-zurueck="<?= $tr ? 'geri' : 'zurück' ?>"><?= $tr ? 'sil' : 'weg' ?></button>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>

<p class="b-fein" style="margin-top:0.8rem;line-height:1.7;text-transform:none;letter-spacing:0.02em;">
  <?= $tr
    ? 'Sıra buradaki sıradır. Göz kapalıysa bölüm sayfada görünmez. "sil" kaydedene kadar geri alınabilir.'
    : 'Die Reihenfolge hier ist die Reihenfolge auf der Seite. Geschlossenes Auge heisst: steht nicht auf der Seite. "weg" laesst sich zurücknehmen, solange nicht gespeichert wurde.' ?>
</p>
