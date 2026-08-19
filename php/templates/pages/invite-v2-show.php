<?php
/**
 * Eine echte Einladung.
 *
 * Dieselbe Buehne wie die Design-Vorschau, mit den Daten des Paares statt der
 * Beispieldaten - und ohne Leiste darunter: wer diese Seite oeffnet, ist
 * eingeladen und nicht auf Vorlagensuche.
 *
 * Die Buehne (partials/design-stage) liest dreizehn Werte, nicht nur die
 * sieben Kernwerte design/scope/styles/seite/kuvert/karte/locale - ratio,
 * tempo, karteAn, introMs, idle, initialen und warnings fehlten hier einmal,
 * und das ergab eine leere Seitenverhaeltnis-Angabe, eine stillstehende
 * Animation, ein Siegel ohne Initialen und - weil null !== [] wahr ist -
 * eine leere Warnungsbox auf jeder echten Einladung. Der Controller
 * (InviteV2Controller::show) rechnet alle dreizehn vor, diese Vorlage gibt
 * sie nur weiter.
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
 */

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
    'idle'      => $idle,
    'initialen' => $initialen,
    // Immer leer: eine echte Einladung zeigt keine Vorlagenmaengel an.
    'warnings'  => $warnings,
    // Auf der Einladung steht die Buehne im Fluss - darunter kommen die
    // Abschnitte.
    'fest'      => false,
]) ?>
