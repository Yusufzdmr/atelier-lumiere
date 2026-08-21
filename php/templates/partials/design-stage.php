<?php
/**
 * Die Buehne: Seite, Kuvert, Karte - und was sich dabei bewegt.
 *
 * Zwei Seiten zeigen dieselbe Buehne: die Vorschau eines Designs (mit
 * Beispieldaten) und eine echte Einladung (mit den Daten des Paares). Der
 * Unterschied steht nur in der Leiste darunter, und die druckt die
 * aufrufende Seite selbst.
 *
 * Ueber die sieben Kernwerte hinaus (design, scope, styles, seite, kuvert,
 * karte, locale) braucht die Buehne noch das, was sich NICHT allein aus
 * $design ableiten laesst: initialen ist der Name des Paares (Beispieldaten
 * hier, echte Daten in Aufgabe 8), warnings betrifft die konkrete Vorlage.
 * ratio, tempo, karteAn, introMs und idle stammen zwar aus $design, werden
 * hier aber unveraendert von der aufrufenden Seite uebernommen, statt ein
 * zweites Mal berechnet zu werden - eine Berechnung, eine Quelle der
 * Wahrheit.
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
 * @var bool $fest
 */

use function Atelier\e;
?>
<style><?= $styles ?></style>

<?php /*
   Die Hoehe der Buehne.

   Bis hierher stand hier min-h-screen, also glatte 100vh - und ALLES darin
   ist absolute inset-0. Die Karte konnte ihre Hoehe also gar nicht an die
   Buehne weitergeben, und die blieb blind einen ganzen Bildschirm hoch.
   Am Telefon faellt das auseinander: die Karte haengt an der Fensterbreite
   (w-full, max-w-2xl, festes Seitenverhaeltnis), die Buehne nicht. Gemessen
   an einer echten Einladung:

       1920 px breit : Karte 521 von 911 px  ->  43 % leer
        414 px breit : Karte 284 von 844 px  ->  66 % leer
        390 px breit : Karte 265 von 844 px  ->  69 % leer
        360 px breit : Karte 242 von 844 px  ->  71 % leer

   Je schmaler das Geraet, desto schlimmer - der Fehler waechst genau dorthin,
   wo die meisten Gaeste die Einladung oeffnen. Und das Argument, das die
   volle Hoehe rechtfertigt, traegt am Telefon nicht: das geschlossene Kuvert
   ist max-w-sm bei 8/5 und will dort dieselben ~265 px wie die Karte, nicht
   844. Auf dem Schreibtisch liest die Weite als Luft, am Telefon als Fehler.

   Deshalb: die Karte laeuft im Fluss und gibt der Buehne ihre Hoehe. Alles
   andere bleibt absolute inset-0 und legt sich darueber - Zeichnung, Kuvert,
   Ebenen, unveraendert. Ab 768 px kommt die volle Hoehe zurueck, weil sie
   dort tut, was sie soll.

   100dvh mit 100vh davor: am Telefon zaehlt vh den Streifen hinter der
   Adressleiste mit, die Seite ist also hoeher als das Sichtbare - dieselbe
   Beschwerde, zweite Ursache. Die erste Zeile ist der Ersatz fuer Browser,
   die dvh nicht kennen.

   Von Hand geschrieben und nicht als Klasse: style.css ist FERTIG gebaut,
   min-h-[100dvh] steht dort nicht und taete still gar nichts.
*/ ?>
<style>
  .d-stage { display: flex; align-items: center; }

  /* Der einzige Knoten im Fluss. z-10 haelt ihn ueber der Zeichnung
     (auto) und unter dem Kuvert (z-30) - dieselbe Ordnung wie vorher,
     nur ohne absolute. */
  .d-stage-mitte {
    position: relative;
    z-index: 10;
    width: 100%;
    /* Der Abstand ist tragend, nicht Zierde: er ist die Luft, die dem
       GESCHLOSSENEN Kuvert bleibt. Es liegt absolute inset-0 und ist damit
       genau so hoch wie die Buehne - ohne diesen Abstand waere die Buehne
       exakt so hoch wie die Karte, und das Kuvert (max-w-sm bei 8/5 plus
       Beschriftung) stiesse bei 390 px auf die Kante und wuerde von
       overflow:hidden abgeschnitten.

       Als eigene Regel und nicht als py-12: die Klasse steht NICHT in der
       gebauten style.css (py-10 und py-16 schon) - sie taete still gar
       nichts, und genau das ist hier passiert, bevor nachgemessen wurde. */
    padding-block: 3rem;
  }

  @media (min-width: 768px) {
    .d-stage--fluss { min-height: 100vh; min-height: 100dvh; }
  }
</style>

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
    <ul class="fixed bottom-6 left-4 z-[60] max-w-xs border border-gold bg-cream p-3 text-xs text-ink-soft">
      <?php foreach ($warnings as $warning): ?>
        <li><?= e($warning['kind']) ?> — <?= e($warning['element']) ?><?php
          if ($warning['detail'] !== '') {
            echo ' (', e($warning['detail']), ')';
          }
        ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php /*
  Zwei Rollen, eine Buehne. Im Schaufenster liegt sie ueber allem: dort ist
  die Karte das Einzige, was zaehlt. Auf einer echten Einladung steht sie im
  Fluss, damit die Abschnitte darunter scrollen koennen - fixed inset-0
  liesse darunter nichts zu. Alles INNERHALB ist absolute inset-0, also an
  der Buehne aufgehaengt und nicht am Fenster: beide Rollen funktionieren
  ohne weitere Aenderung.
*/ ?>
  <div class="<?= e($scope) ?> d-stage <?= $fest ? 'fixed inset-0 z-50' : 'relative d-stage--fluss' ?> overflow-hidden"
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
      <div class="d-stage-mitte flex items-center justify-center px-6">
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

