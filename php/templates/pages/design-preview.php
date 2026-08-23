<?php
/**
 * Eine Vorlage der zweiten Fassung, in voller Groesse.
 *
 * Drei Ebenen ineinander, wie beim Original: die Seite traegt Hintergrund und
 * Zeichnung, darauf liegt das Kuvert, darin die Karte. Welche Ebene wohin
 * gehoert, sagt ihr `spot` – der Controller hat sie schon getrennt.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $seite
 * @var string $kuvert
 * @var string $karte
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var string $initialen
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use Atelier\View;
use function Atelier\e;

/** @var string $abschnitte */

$ratio = str_replace(':', ' / ', (string) $design['canvas']['ratio']);
$intro = (string) $design['animation']['intro'];
// Wie die Karte hereinkommt - beim Original steht das in data-animation.
$karteAn = (string) $design['animation']['card'];
$tempo = (int) $design['animation']['speed'];
// Wie lange eine Auftaktszene laeuft. Die zweite Fassung hat noch keine
// eigenen Szenen, also null - das Skript ueberspringt sie dann.
$introMs = 0;
// Die ruhige Dauerbewegung des Kuverts - dieselbe Klasse wie im Original.
$idle = (string) $design['animation']['idle'];
?>
<?= View::partial('partials/design-stage', [
    'design'    => $design,
    'scope'     => $scope,
    'styles'    => $styles,
    'seite'     => $seite,
    'kuvert'    => $kuvert,
    'karte'     => $karte,
    'locale'    => $locale,
    // Diese fuenf stammen aus $design, werden aber hier - nicht in der
    // Buehne selbst - berechnet: die Buehne teilt sich mit der Leiste
    // unten dieselben Werte (tempo, karteAn), also soll es nur eine
    // Berechnung geben.
    'ratio'     => $ratio,
    'tempo'     => $tempo,
    'karteAn'   => $karteAn,
    'introMs'   => $introMs,
    // Der Oeffnungsfilm steht im Dokument, nicht im lebenden Thema: bei einer
    // Einladung im eingefrorenen Sockel, hier in der Vorlage selbst.
    'introVideo'  => (string) $design['intro']['video'],
    'introPoster' => (string) $design['intro']['poster'],
    'idle'      => $idle,
    // Nicht aus $design ableitbar: initialen kommt aus den (Beispiel-)
    // Werten des Paares, warnings betrifft die konkrete Vorlage.
    'initialen' => $initialen,
    'warnings'  => $warnings,
    /*
     * Im Fluss, nicht fest.
     *
     * Bis hierher lag die Buehne fest ueber allem, mit der Begruendung "hier
     * gibt es nichts, was darunter scrollen muesste". Das galt, solange eine
     * Vorlage nur aus Ebenen bestand. Seit Faz 3C hat sie Abschnitte - und
     * eine feste Buehne haelt sie unerreichbar unter sich fest. Wer wischte,
     * fand nichts.
     */
    'fest'      => false,
]) ?>
<?php /*
   Unter der Buehne, nicht darin - dieselbe Anordnung wie auf der echten
   Einladung (pages/invite-v2-show.php): die Karte hat einen festen Rahmen,
   die Abschnitte haben eine variable Laenge.

   Der Abstand unten haelt den festen Balken frei, der gleich darunter kommt.
*/ ?>
<?php if ($abschnitte !== '') : ?>
  <?php /*
     Zwei Kaesten, nicht einer: die Flaeche geht von Kante zu Kante, damit das
     Papier der Karte einfach weiterlaeuft; der Text darin bleibt in seiner
     Spalte. Ein einzelner zentrierter Kasten waere eine schwarze Saeule auf
     cremefarbenem Grund - genau der Bruch, der weg soll.
  */ ?>
  <?php
    /*
     * Erst das eigene Feld, dann die Karte: der Grafiker kann unten ein
     * anderes Blatt hinlegen, muss aber nicht. Leer heisst "wie die Karte".
     */
    $papier = Design::safeSrc((string) ($design['sectionsBg'] ?? ''));
    foreach ($papier === '' ? $design['layers'] : [] as $ebene) {
        if (in_array($ebene['type'], ['photo', 'image'], true)
            && $ebene['spot'] === 'card' && (string) $ebene['src'] !== '') {
            $papier = Design::safeSrc((string) $ebene['src']);
            break;
        }
    }
  ?>
  <div class="<?= e($scope) ?> d-sec-flaeche"
       <?= $papier !== '' ? 'style="--d-sec-blatt:url(\'' . e($papier) . '\')"' : '' ?>>
    <div class="d-sections mx-auto max-w-2xl px-6 py-16 pb-32">
      <?= $abschnitte ?>
    </div>
  </div>
<?php endif; ?>
  <?php if ($intern) : ?>
  <div class="fixed inset-x-0 bottom-0 z-[60] flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-ink/80 px-4 py-2 text-center text-xs text-cream">
    <span><?= e($design['name'][$locale] ?? $design['name']['de']) ?></span>
    <span><?= e($design['category']) ?></span>
    <span>Fassung <?= (int) $design['version'] ?></span>
    <span><?= count($design['layers']) ?> Ebenen</span>
    <span>Auftakt <?= e($intro) ?></span>
    <span>Karte <?= e($karteAn) ?> (<?= $tempo ?> ms)</span>
    <span><?= $locale === 'de' ? 'Kuvert anklicken' : 'click the envelope' ?></span>
  </div>
  <?php else : ?>
    <?php /*
       Fuer den Gast: keine Fassungsnummer, keine Ebenenzahl. Zwei Wege - zurueck
       zur Auswahl, oder mit dieser Vorlage anfangen.
    */ ?>
    <div class="fixed inset-x-0 bottom-0 z-[60] flex flex-wrap items-center justify-center gap-6 bg-ink/80 px-6 py-4 text-[0.62rem] uppercase tracking-[0.16em] text-cream">
      <a href="<?= e(I18n::path('/v2/designs', $locale)) ?>" class="transition-colors hover:text-gold">
        <?= $locale === 'de' ? 'Alle Designs' : 'All designs' ?>
      </a>
      <?php /*
         Zweite Fassung, ohne Themen-Weiche - dieselbe Entscheidung wie im
         Schaufenster (designs-v2.php): der Assistent baut aus dem
         Design-Dokument, ein Thema braucht er nicht mehr.
      */ ?>
      <a href="<?= e(I18n::path('/v2/einladung', $locale) . '?design=' . rawurlencode((string) $design['slug'])) ?>"
         class="border border-cream px-5 py-2.5 transition-colors hover:bg-cream hover:text-ink">
        <?= $locale === 'de' ? 'Mit diesem Design erstellen' : 'Create with this design' ?>
      </a>
    </div>
  <?php endif; ?>
