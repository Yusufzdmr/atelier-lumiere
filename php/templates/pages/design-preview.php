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

use function Atelier\e;

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
<style><?= $styles ?></style>

<?php /*
  Vollflaechig, nicht als Kaestchen mit Ueberschrift. Die erste Fassung zeigt
  unter /designs/{thema} die echte Einladungsseite ueber den ganzen Bildschirm
  (InviteController::designPreview rendert pages/invitation). Ein Vorschau-
  kaestchen von 384 px daneben zu stellen und "sieht es gleich aus?" zu fragen
  waere keine Frage, auf die es eine Antwort geben kann.

  Die Kenndaten stehen deshalb in einer kleinen Leiste unten, ausserhalb der
  Buehne, wo sie den Vergleich nicht stoeren.
*/ ?>

  <?php if ($warnings !== []): ?>
    <ul class="fixed bottom-14 left-4 z-[60] max-w-xs border border-amber-500/40 bg-amber-50 p-3 text-xs text-ink/80">
      <?php foreach ($warnings as $warning): ?>
        <li><?= e($warning['kind']) ?> — <?= e($warning['element']) ?><?php
          if ($warning['detail'] !== '') {
            echo ' (', e($warning['detail']), ')';
          }
        ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="<?= e($scope) ?> d-stage fixed inset-0 z-50 overflow-hidden"
       style="background: var(--d-bg, #EFE7DC);">

      <!-- Die Seite: Hintergrund und Zeichnung, immer sichtbar. -->
      <div class="d-page absolute inset-0"><?= $seite ?></div>

      <?php /*
        Die Karte behaelt ihr Seitenverhaeltnis und ihre Breite - wie beim
        Original, wo sie mitten auf der Buehne liegt.

        container-type steht hier ein zweites Mal, und das ist kein Versehen:
        cqw rechnet gegen den NAECHSTEN Kasten mit container-type. Stuende es
        nur auf der Buehne, waeren 11 cqw elf Prozent der Fensterbreite statt
        elf Prozent der Karte - die Namen kaemen dreifach zu gross heraus.
        Die Schriftgroessen sind an der Karte gemessen, also muss die Karte
        der Bezug sein.
      */ ?>
      <div class="absolute inset-0 flex items-center justify-center px-6">
        <div class="d-card t-card relative w-full max-w-2xl overflow-hidden"
             data-speed="<?= $tempo ?>"
             style="aspect-ratio: <?= $ratio ?>; background: var(--d-paper);
                    container-type: inline-size;
                    /* Die Ebenen der Karte tragen eigene z-index-Werte aus
                       Design::css(). Ohne eigenen Stapelkontext klettern sie
                       aus der Karte heraus und legen sich ueber das Kuvert -
                       dann faengt der Name den Klick ab und nichts oeffnet. */
                    isolation: isolate;"><?= $karte ?></div>
      </div>
      <!--
        Das Kuvert. Die Attribute sind der Vertrag von invitation.js:
        [data-envelope] ist die Huelle, [data-envelope-open] der Anklickpunkt,
        data-animation waehlt die Bewegung der Karte, data-intro-ms sagt, wie
        lange eine Auftaktszene laeuft.

        Der Aufbau ist derselbe wie in pages/invitation.php - t-envelope,
        t-sheet, t-flap, t-seal - und das ist Absicht, nicht Bequemlichkeit:
        das Stylesheet bringt fuer diese vier Klassen bereits das Aufklappen
        mit ([data-open=true] .t-flap dreht die Lasche, .t-sheet faehrt heraus,
        .t-seal bricht). Ein Kuvert ist Verhalten des Betrachters; nachzubauen,
        was daneben schon funktioniert, waere eine zweite Baustelle.

        Was hier NICHT steht: die Farben. Jede kommt als Palettenmarke aus dem
        Dokument. Und was sonst auf dem Kuvert liegt, kommt aus den Ebenen mit
        spot=envelope.

        Achtung, Kleinschreibung: die Marken heissen --d-envelopeflap,
        --d-envelopeedge, --d-paperedge, --d-sealtext. key() schreibt klein,
        und ein camelCase-Name trifft nichts und faellt still auf den Ersatz.

        Das Kuvert steht ZULETZT im Markup, also ueber der Karte - so wie im
        Original, wo die Buehne mit z-50 ueber allem liegt und die Karte
        ausserhalb von ihr steht. Stuende die Karte darueber, waere das Kuvert
        nicht anklickbar und nichts wuerde sich je oeffnen.
      -->
      <div class="d-envelope idle-<?= e($idle) ?> absolute inset-0 z-30 flex flex-col items-center justify-center gap-9 px-6"
           data-envelope
           data-animation="<?= e($karteAn) ?>"
           data-intro-ms="<?= $introMs ?>"
           style="background: var(--d-bg);">

        <button type="button" data-envelope-open
                class="t-envelope relative w-full max-w-sm border shadow-[0_30px_60px_-25px_rgba(0,0,0,.45)]"
                style="aspect-ratio: 8 / 5; background: var(--d-envelope);
                       border-color: var(--d-envelopeedge);"
                aria-label="<?= $locale === 'de' ? 'Einladung öffnen' : 'Open the invitation' ?>">

          <span class="t-sheet" style="background: var(--d-paper); border: 1px solid var(--d-paperedge);">
            <span class="font-display text-2xl font-light tracking-[0.14em]"
                  style="color: var(--d-accent);"><?= e($initialen) ?></span>
          </span>

          <span class="t-flap" style="background: var(--d-envelopeflap);"></span>

          <?php /* Zwei Ebenen mit Absicht: aussen sitzt die Mitte, innen bewegt
                   sich das Siegel. In einem Element wuerde sealBreak mit seinem
                   transform die Zentrierung ueberschreiben und das Siegel
                   spraenge in die Ecke. Dasselbe Problem, dieselbe Loesung wie
                   in der ersten Fassung. */ ?>
          <span class="absolute left-1/2 top-[46%] z-[6] -translate-x-1/2 -translate-y-1/2">
            <span class="t-seal relative flex h-16 w-16 items-center justify-center font-display text-lg"
                  style="background-color: var(--d-seal); color: var(--d-sealtext);"><?= e($initialen) ?></span>
          </span>

          <?= $kuvert ?>
        </button>

        <p class="text-[0.62rem] uppercase tracking-[0.28em]" style="color: var(--d-accent);">
          <?= $locale === 'de' ? 'Tippen zum Öffnen' : 'Tap to open' ?>
        </p>
      </div>
  </div>

  <div class="fixed inset-x-0 bottom-0 z-[60] flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-ink/80 px-4 py-2 text-center text-xs text-cream">
    <span><?= e($design['name'][$locale] ?? $design['name']['de']) ?></span>
    <span><?= e($design['category']) ?></span>
    <span>Fassung <?= (int) $design['version'] ?></span>
    <span><?= count($design['layers']) ?> Ebenen</span>
    <span>Auftakt <?= e($intro) ?></span>
    <span>Karte <?= e($karteAn) ?> (<?= $tempo ?> ms)</span>
    <span><?= $locale === 'de' ? 'Kuvert anklicken' : 'click the envelope' ?></span>
  </div>
