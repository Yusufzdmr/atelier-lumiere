<?php
/**
 * Eine echte Einladung.
 *
 * Dieselbe Buehne wie die Design-Vorschau, mit den Daten des Paares statt der
 * Beispieldaten - und ohne Leiste darunter: wer diese Seite oeffnet, ist
 * eingeladen und nicht auf Vorlagensuche.
 *
 * Die Buehne (partials/design-stage) liest fünfzehn Werte, nicht nur die
 * sieben Kernwerte design/scope/styles/seite/kuvert/karte/locale - ratio,
 * tempo, karteAn, introMs, idle, initialen, warnings und fest fehlten hier
 * einmal, und das ergab eine leere Seitenverhaeltnis-Angabe, eine
 * stillstehende Animation, ein Siegel ohne Initialen und - weil null !== []
 * wahr ist - eine leere Warnungsbox auf jeder echten Einladung. Der
 * Controller (InviteV2Controller::show) rechnet alle fünfzehn vor, diese
 * Vorlage gibt sie nur weiter.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $seite
 * @var string $kuvert
 * @var string $karte
 * @var string $locale
 * @var string $ratio
 * @var int $tempo
 * @var string $karteAn
 * @var int $introMs
 * @var string $idle
 * @var string $initialen
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var string $abschnitte
 */

use function Atelier\e;
use Atelier\Design;
use Atelier\View;
?>
<?= View::partial('partials/design-stage', [
    'design'    => $design,
    'scope'     => $scope,
    'styles'    => $styles,
    'seite'     => $seite,
    'kuvert'    => $kuvert,
    'karte'     => $karte,
    'locale'    => $locale,
    'ratio'     => $ratio,
    'tempo'     => $tempo,
    'karteAn'   => $karteAn,
    'introMs'   => $introMs,
    // Der Oeffnungsfilm steht im Dokument, nicht im lebenden Thema: bei einer
    // Einladung im eingefrorenen Sockel, hier in der Vorlage selbst.
    'introVideo'  => (string) $design['intro']['video'],
    'introPoster' => (string) $design['intro']['poster'],
    'idle'      => $idle,
    'initialen' => $initialen,
    // Immer leer: eine echte Einladung zeigt keine Vorlagenmaengel an.
    'warnings'  => $warnings,
    // Auf der Einladung steht die Buehne im Fluss - darunter kommen die
    // Abschnitte.
    'fest'      => false,
]) ?>
<?php /*
   Unter der Buehne, nicht darin: die Karte hat einen festen Rahmen, die
   Abschnitte haben eine variable Laenge. Ist nichts auszugeben, steht hier
   auch nichts - kein leerer Kasten.
*/ ?>
<?php if ($abschnitte !== '') : ?>
  <?php /*
     Zwei Kaesten, nicht einer: die Flaeche geht von Kante zu Kante, damit das
     Papier der Karte einfach weiterlaeuft; der Text darin bleibt in seiner
     Spalte. Dieselbe Anordnung wie im Schaufenster.
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
       <?= $papier !== '' ? 'style="background-image:url(\'' . e($papier) . '\')"' : '' ?>>
    <div class="d-sections mx-auto max-w-2xl px-6 py-16">
      <?= $abschnitte ?>
    </div>
  </div>
<?php endif; ?>
