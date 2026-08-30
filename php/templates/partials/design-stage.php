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
 * @var string $introVideo   Oeffnungsfilm des Themas, leer = die gezeichnete Klappe
 * @var string $introPoster
 */

use function Atelier\e;
use Atelier\Design;
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
             style="aspect-ratio: <?= e($ratio) ?>; background: var(--d-paper);
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
      <?php
        $introFilm = Design::safeSrc((string) ($introVideo ?? ''));
        $introBild = Design::safeSrc((string) ($introPoster ?? ''));
      ?>
      <?php if ($introFilm !== '') : ?>
        <?php /*
           Der Vorspann liegt UEBER dem Kuvert (z-40 gegen z-30) und
           verschwindet, wenn er durch ist. Kein autoplay, wie bei den Ebenen:
           invitation.js startet ihn beim Klick auf das Kuvert und blendet ihn
           danach aus. Ohne Skript sieht der Gast ihn nie und bekommt sofort
           die Karte - das ist die richtige Reihenfolge, nicht ein Fehler.
        */ ?>
        <?php /*
           Der Film laeuft in seiner eigenen Groesse, nicht ueber die ganze
           Flaeche.

           Zuerst stand hier "h-full w-full object-cover" - und das war der
           Fehler, nicht die Datei. Der Film des Kunden ist 478 x 850 bei
           1,5 Mbit/s, also sauber fuer das, was er ist. Ueber ein Fenster von
           1280 gezogen sind das 2,7-fach hochskaliert, und dann sieht jede
           saubere Datei weich aus.

           object-contain statt cover, und die Breite gedeckelt: 30rem sind
           480 px, also ungefaehr seine eigene Breite. So wird er nie
           groesser gezeigt, als er ist - und bleibt scharf. Bringt der
           Grafiker spaeter einen groesseren, hebt eine Zahl hier den Deckel.

           Der dunkle Grund dahinter ist die Seitenfarbe des Themas: der Film
           fuellt die Flaeche nicht mehr, also muss etwas darunter liegen.
        */ ?>
        <?php /*
           NICHT versteckt, und das ist der Kern.

           Das erste Bild des Films IST das geschlossene Kuvert - echtes
           Papier, echtes Siegel. Es waere Unsinn, daneben noch eine
           gezeichnete Klappe zu zeigen und den Film erst danach
           hervorzuholen. Der Film liegt also von Anfang an da, auf z-40 ueber
           dem gezeichneten Kuvert, und ist selbst der Anklickpunkt.

           NACHTRAG - genau das war der Fehler. "Sein erstes Bild IST das
           geschlossene Kuvert" stimmt nur, wenn der Browser dieses Bild auch
           zeichnet. Ein <video> ohne autoplay tut das auf iOS nicht. Uebrig
           blieb der deckende Kasten in der Grundfarbe der Vorlage - bei
           elysee (#EFE7DC) also eine weisse Flaeche, ohne Kuvert, ohne
           Siegel, ohne Hinweis, ueber einer Seite, deren Scrollen
           invitation.js gerade gesperrt hat. Der Gast sah nichts und konnte
           nichts tun; nur ein Tippen ins Leere half.

           Jetzt liegt das gezeichnete Kuvert wieder darunter und traegt den
           Hinweis, und der Film schiebt sich erst darueber, wenn er
           tatsaechlich laeuft (invitation.js, Ereignis "playing"). Zwei
           Kuverts hintereinander gibt es trotzdem nicht: lief der Film,
           faellt das gezeichnete stumm weg, statt danach noch aufzuklappen.

           Und sein LETZTES Bild ist das Blatt der Karte - gemessen, nicht
           vermutet: bei 5,20 s stehen dort dieselben Goldecken und dieselben
           zwei Senkrechten wie auf bild.jpg. Deshalb ist er so breit wie die
           Karte (42rem, dasselbe max-w-2xl) und nicht so breit wie er selbst:
           nur dann geht der Film in die Karte ueber, ohne dass es einen
           Schnitt gibt. Das kostet 1,4-fache Vergroesserung - der Preis fuer
           eine unsichtbare Naht, und weit weg von den 2,7 von vorhin.
        */ ?>
        <?php /*
           Ueber dem ganzen Bildschirm und nicht ueber der Buehne.

           Bis hierher war der Film so breit wie die Karte (max-w-2xl, das
           Seitenverhaeltnis des Dokuments). Das hatte einen Grund: sein
           letztes Bild war das Blatt der Karte, und nur bei gleicher Breite
           ging er ohne Schnitt in sie ueber.

           Der Grund ist weg. Seit der Vorspann in 600 ms ausblendet, braucht
           er kein passendes Schlussbild mehr - und ein Film, der auf einem
           Telefon in einem Kaestchen mit Raendern laeuft, sieht aus wie ein
           Video in einer Seite und nicht wie das Oeffnen einer Einladung.

           fixed und nicht absolute: die Buehne ist nicht so hoch wie das
           Fenster. Gefahrlos, weil die Seite waehrend des Vorspanns ohnehin
           stillsteht.
        */ ?>
        <?php /*
           Unsichtbar, bis er etwas zeigt.

           opacity:0 und pointer-events:none sind zusammen der ganze
           Unterschied zu vorher: der Kasten liegt zwar ueber dem Kuvert,
           verdeckt es aber nicht und faengt auch den Finger nicht ab - der
           geht auf den Knopf darunter, der eine aria-label traegt und
           aussieht wie ein Kuvert. Erst wenn der Film laeuft, dreht
           invitation.js die Deckkraft auf 1.

           Die Ueberblendung dauert so lang wie die am anderen Ende
           (UEBERGANG_MS im Skript). Sie steht hier und nicht dort, weil sie
           auch fuer das EINblenden gilt und das Skript sie sonst zweimal
           setzen muesste.
        */ ?>
        <div class="fixed inset-0 z-40 flex items-center justify-center"
             style="background: var(--d-bg, #0b0a09);
                    opacity:0; pointer-events:none; transition: opacity 600ms ease;"
             data-intro-video>
          <?php /*
             Derselbe Kasten wie die Karte: volle Breite bis max-w-2xl, das
             Seitenverhaeltnis des Dokuments, object-cover. Nur so liegt das
             letzte Bild des Films genau dort, wo gleich das Blatt der Karte
             liegt - sonst springt die Groesse im Schnitt.

             Vorher stand hier max-h-full: dann bestimmte die Fensterhoehe die
             Breite, der Film kam mit 478 px heraus und die Karte mit 672, und
             man sah den Sprung.
          */ ?>
          <?php /*
             Ganz zu sehen und nicht formatfuellend.

             Zuerst stand hier object-cover: der Film fuellte den Bildschirm
             und wurde dafuer beschnitten. Auf dem Telefon war das kein
             kleiner Schnitt - ein Telefon ist deutlich laenger als 9:16,
             und der Film kam entsprechend vergroessert heraus. "Cok buyuk
             video kucult."

             Also contain: der Kasten bleibt der ganze Bildschirm, der Film
             steht darin vollstaendig. Was oben und unten bleibt, traegt die
             Grundfarbe der Vorlage - kein Kaestchen mit Rand, sondern ein
             Film in seiner eigenen Groesse auf dunklem Grund.
          */ ?>
          <video class="h-full w-full cursor-pointer object-contain" data-intro-film
                 src="<?= e($introFilm) ?>"
                 <?= $introBild !== '' ? 'poster="' . e($introBild) . '"' : '' ?>
                 muted playsinline preload="auto"></video>
        </div>
      <?php endif; ?>
      <?php /*
         Das gezeichnete Kuvert - und zwar immer.

         Hier stand bis zum 30. August die Umkehrung: bringt das Thema einen
         Oeffnungsfilm mit, faellt unser Kuvert ganz weg (Huelle, Klappe,
         Siegel, Hinweis), weil der Film ja selbst ein echtes zeigt. Der
         Gedanke war richtig, die Voraussetzung nicht - er setzte voraus,
         dass der Film zu sehen ist, bevor ihn jemand gestartet hat. Auf iOS
         ist er das nicht, und dann war die Einladung eine weisse Flaeche.

         Das Kuvert wird jetzt immer gedruckt und traegt immer den Hinweis.
         Es IST die Aufforderung, und eine Aufforderung, die von der
         Ladefaehigkeit eines Videos abhaengt, ist keine.

         Zwei Kuverts hintereinander bleiben trotzdem ausgeschlossen - nur an
         einer anderen Stelle. Frueher wurde das gezeichnete gar nicht erst
         gebaut; jetzt entscheidet invitation.js, ob es aufklappt: lief der
         Film, faellt es beim Uebergang stumm weg. Der Unterschied ist, dass
         die Entscheidung dort getroffen wird, wo man WEISS, ob der Film lief.
      */ ?>
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

