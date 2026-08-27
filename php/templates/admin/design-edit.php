<?php
/**
 * Der Theme Builder: drei Spalten statt einer Formularwand.
 *
 * Links die Abschnitte - was die Seite ueberhaupt zeigt und in welcher
 * Reihenfolge. In der Mitte die Karte, und zwar die echte: derselbe
 * Stilblock, dasselbe Markup wie beim Gast, nur kleiner. Rechts die
 * Einstellungen dessen, was gerade ausgewaehlt ist; ist nichts ausgewaehlt,
 * stehen dort die Einstellungen der Vorlage selbst.
 *
 * Vorher lagen alle neun Bloecke untereinander in einer Spalte, und die
 * Abschnitte waren Zeilen mit je zehn Feldern nebeneinander. Damit liess sich
 * arbeiten, wenn man wusste, was wo steht - aber nicht schnell, und "schnell
 * eine Vorlage bauen" war die ganze Aufgabe.
 *
 * Bearbeitet die Oberflaeche: Farben, Schriften, Texte, Bilder, Bewegung,
 * Anordnung, Abschnitte, Kundenrechte. Seit der vierten Phase gehoert auch
 * der Kasten jeder Ebene hierher - Position, Groesse, Drehung, Stapel. Was
 * NICHT aus diesem Formular kommt, ist das Seitenverhaeltnis der Karte;
 * tests/design_admin.php haelt diese Grenze.
 *
 * EIN Formular ueber alle drei Spalten. Das ist Absicht und keine
 * Bequemlichkeit: die Fassungspruefung im Controller schuetzt genau einen
 * Absendevorgang. Drei Formulare waeren drei Zeitpunkte, und zwei davon
 * wuerden die Arbeit des dritten still ueberschreiben.
 *
 * Die Gestaltung dieser Seite steht als eigener Stilblock hier und nicht als
 * Tailwind-Klassen: php/public/assets/style.css ist eine fertig uebersetzte
 * Datei ohne Bauschritt, und eine Klasse, die darin fehlt, tut schweigend
 * nichts. lg:grid-cols-[...], lg:sticky und lg:top-28 fehlen darin - die
 * zweispaltige Anordnung, die hier vorher stand, hat es also nie gegeben.
 * Eigene Regeln haengen an keinem Bauschritt.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $seite
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var array{draft:list<string>,published:list<string>} $veraltet
 * @var bool $fragen
 * @var string $csrf
 * @var string $locale
 */

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\I18n;
use Atelier\SectionRegistry;
use Atelier\Themes;
use function Atelier\e;

$tr    = $locale === 'tr';
$p     = static fn (string $to): string => I18n::path($to, $locale);
$label = 'text-[0.66rem] uppercase tracking-[0.16em] text-muted';
$feld  = 'mt-1 block w-full border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink';

$ok     = (string) ($_GET['ok'] ?? '');
$fehler = (string) ($_GET['fehler'] ?? '');

// Ein Block: zu, bis jemand ihn braucht. Élysée hat vierzehn Ebenen, und alle
// Felder gleichzeitig offen sind eine Wand.
$auf = static function (string $titel): string {
    return '<details class="b-falte"><summary>' . e($titel) . '</summary><div class="b-falte-inhalt">';
};
$zu = '</div></details>';

$textEbenen = array_filter($design['layers'], static fn (array $l): bool => $l['type'] === 'text' && (string) $l['bind'] === '');
$bindEbenen = array_filter($design['layers'], static fn (array $l): bool => (string) $l['bind'] !== '');
$bildEbenen = array_filter($design['layers'], static fn (array $l): bool => in_array($l['type'], ['image', 'photo'], true));
$videoEbenen = array_filter($design['layers'], static fn (array $l): bool => $l['type'] === 'video');
?>
<style><?= $styles ?></style>
<style>
  /* Der Bau. Eigene Regeln, kein Tailwind - siehe Kopf dieser Datei. */
  .b-kopf{position:sticky;top:0;z-index:30;display:flex;flex-wrap:wrap;align-items:center;
          justify-content:space-between;gap:1rem;background:var(--color-cream,#faf7f2);
          border-bottom:1px solid var(--color-sand-deep,#dccebc);padding:0.9rem 0;margin-bottom:1.5rem;}
  .b-kopf-links{display:flex;flex-wrap:wrap;align-items:baseline;gap:1rem;min-width:0;}
  .b-kopf-rechts{display:flex;align-items:center;gap:0.6rem;}
  .b-name{font-size:1.25rem;color:var(--color-ink,#14110f);}
  .b-fein{font-size:0.66rem;letter-spacing:0.16em;text-transform:uppercase;color:var(--color-muted,#7a6f65);}
  a.b-fein:hover{color:var(--color-ink,#14110f);}

  .b-schale{display:grid;gap:1.5rem;align-items:start;}
  @media (min-width:1120px){.b-schale{grid-template-columns:248px minmax(0,1fr) 372px;}}
  .b-spalte{min-width:0;}
  /* Die Karte laeuft mit, waehrend rechts gescrollt wird - sie ist der Grund,
     warum ueberhaupt jemand hier ist. */
  /* Und sie scrollt in sich selbst, statt die Seite lang zu machen.

     Ohne diese Grenze wuchs die Seite mit der lebenden Vorschau von rund 1100
     auf 4154 Pixel - gemessen an bild. Wer sich einen Abschnitt ansah, hatte
     Liste und Tafel weit ueber sich, und der Editor war keine drei Spalten
     mehr, sondern eine lange Rolle mit zwei leeren Raendern.

     Jetzt ist die Mitte so hoch wie das Fenster und rollt darin. Die Seite
     ist wieder so lang wie die Spalten daneben. */
  @media (min-width:1120px){
    .b-buehne{position:sticky;top:5.5rem;
              max-height:calc(100vh - 7rem);max-height:calc(100dvh - 7rem);
              overflow-y:auto;overscroll-behavior:contain;
              padding-right:0.35rem;}
  }

  /* Und seit der lebenden Vorschau laufen auch die beiden Randspalten mit.

     Bis die Abschnitte lebten, war die Mitte ungefaehr so hoch wie die
     Spalten daneben - man scrollte kaum. Jetzt haengen 2705 Pixel Abschnitte
     unter der Karte, die Seite ist 4154 hoch, und wer sich einen Abschnitt
     ansieht, hat die Liste zum Auswaehlen weit ueber sich: "soldan bolum
     sectigimde asagi kaydiriyor guzel ama sol taraftaki bolumler kismi da
     kaysin, yukari tekrar cikmayayim".

     Also bleiben beide Raender stehen und bekommen ihren eigenen Ueberlauf.
     Scrollen tut dann nur noch das, was wirklich lang ist: die Vorschau.

     overscroll-behavior:contain, damit das Ende einer Spalte nicht die ganze
     Seite weiterschiebt. Und erst ab 1120px - darunter stehen die Spalten
     ohnehin untereinander, und ein festgeklebter Kasten waere dort im Weg. */
  @media (min-width:1120px){
    .b-spalte-fest{position:sticky;top:5.5rem;
                   max-height:calc(100vh - 7rem);max-height:calc(100dvh - 7rem);
                   overflow-y:auto;overscroll-behavior:contain;
                   /* Luft fuer den eigenen Balken, damit er nicht auf den
                      Kaesten klebt. */
                   padding-right:0.35rem;}
  }

  .b-liste{list-style:none;margin:0;padding:0;display:grid;gap:0.35rem;}
  .b-zeile{display:flex;align-items:center;gap:0.35rem;padding:0.45rem 0.5rem;
           border:1px solid var(--color-sand-deep,#dccebc);}
  .b-zeile[data-aktiv]{border-color:var(--color-ink,#14110f);background:var(--color-sand,#ede4d8);}
  .b-zeile[data-weg]{opacity:0.4;}
  /* Ziehbar: der Greifer sagt es, solange die Maus darauf steht. Auf dem
     Telefon gibt es kein Ziehen - dort bleiben die Pfeile der einzige Weg,
     und deshalb bleiben sie ueberall. */
  .b-zeile[draggable="true"] .b-greifer{cursor:grab;}
  .b-zeile[data-zieht]{opacity:0.5;outline:1px dashed var(--color-gold,#b08d57);}
  .b-greifer{flex:1;min-width:0;text-align:left;border:0;background:none;padding:0;
             cursor:pointer;color:var(--color-ink,#14110f);font:inherit;font-size:0.82rem;}
  .b-greifer small{display:block;font-size:0.6rem;letter-spacing:0.14em;text-transform:uppercase;
                   color:var(--color-muted,#7a6f65);}
  .b-knopf{border:1px solid var(--color-sand-deep,#dccebc);background:transparent;cursor:pointer;
           padding:0.1rem 0.4rem;font-size:0.7rem;line-height:1.5;color:var(--color-muted,#7a6f65);}
  .b-knopf:hover{color:var(--color-ink,#14110f);}
  .b-auge{display:inline-flex;align-items:center;cursor:pointer;padding:0 0.15rem;}
  .b-auge input{margin:0;}

  .b-panel[hidden]{display:none;}
  .b-falte{border:1px solid var(--color-sand-deep,#dccebc);margin-bottom:0.5rem;}
  .b-falte summary{cursor:pointer;padding:0.85rem 1rem;font-size:0.66rem;letter-spacing:0.16em;
                   text-transform:uppercase;color:var(--color-muted,#7a6f65);}
  .b-falte-inhalt{border-top:1px solid var(--color-sand-deep,#dccebc);padding:1rem;display:grid;gap:1.1rem;}
  .b-gruppe{display:grid;gap:0.7rem;}
  @media (min-width:520px){.b-zwei{grid-template-columns:1fr 1fr;}}

  /* Die Tafel eines Abschnitts steht offen - sie IST der Inhalt der Spalte,
     und ein Dreieck davor waere ein Klick, der nichts entscheidet. */
  .b-falte-offen{border:1px solid var(--color-sand-deep,#dccebc);padding:1rem;display:grid;gap:1.1rem;}

  .b-karten{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:0.4rem;}
  .b-karte{border:1px solid var(--color-sand-deep,#dccebc);background:transparent;cursor:pointer;
           padding:0.6rem 0.4rem;text-align:left;color:var(--color-ink,#14110f);font:inherit;
           font-size:0.76rem;line-height:1.3;}
  .b-karte:hover{border-color:var(--color-ink,#14110f);}
  .b-karte[data-aktiv]{border-color:var(--color-ink,#14110f);background:var(--color-sand,#ede4d8);}
  .b-karte small{display:block;margin-top:0.2rem;font-size:0.6rem;color:var(--color-muted,#7a6f65);}

  .b-geraete{display:flex;flex-wrap:wrap;gap:0.35rem;margin-bottom:0.6rem;}
  .b-knopf[data-aktiv]{border-color:var(--color-ink,#14110f);color:var(--color-ink,#14110f);}
  /* Der Rahmen wird auf die Spalte heruntergerechnet, statt sie zu sprengen:
     ein Telefon ist 390 breit, ein Schreibtisch 1280, und die Spalte ist,
     was sie ist. Die Hoehe folgt derselben Rechnung - sonst stuende unter
     einem verkleinerten Rahmen ein Loch in seiner vollen Hoehe. */
  .b-rahmen{border:1px solid var(--color-sand-deep,#dccebc);overflow:hidden;}
  .b-rahmen iframe{border:0;display:block;transform-origin:top left;background:#fff;}

  /* Ziehen statt tippen.

     Der Rahmen um die gewaehlte Ebene und seine acht Griffe. Beides liegt IM
     Vorschaukasten und wird vom Skript in Pixeln hingestellt (offsetLeft,
     offsetTop, offsetWidth, offsetHeight) - deshalb steht hier keine Groesse,
     nur das Aussehen.

     Der Rahmen selbst faengt keine Klicks: er liegt ueber der Ebene, und wer
     die Ebene schieben will, greift durch ihn hindurch. Nur die Griffe
     nehmen den Zeiger an.

     Warum als eigene Regeln und nicht als Tailwind: siehe Kopf dieser Datei -
     style.css ist fertig gebaut, eine erfundene Klasse taete schweigend
     nichts. */
  /* Der Finger gehoert der Karte, nicht der Seite.

     Ohne touch-action nimmt der Browser eine Wischbewegung fuer sich und
     scrollt - das Ziehen kommt am Telefon nie an. Nur auf den Ebenen und den
     Griffen, nicht auf der ganzen Vorschau: neben der Karte soll die Seite
     weiter scrollen, sonst sitzt man am Telefon in der Vorschau fest. */
  [data-design-preview] .d-el{cursor:move;touch-action:none;}
  .b-griff{touch-action:none;}
  /* Ein Finger ist breiter als zehn Pixel. Die Griffe bleiben so gross, wie
     sie aussehen - nur ihr Fangbereich waechst, und zwar ausschliesslich
     dort, wo mit dem Finger gezielt wird. */
  @media (pointer: coarse) {
    .b-griff::before{content:"";position:absolute;inset:-12px;}
  }
  [data-design-preview][data-zieht]{user-select:none;}
  [data-design-preview][data-zieht] .d-el{cursor:grabbing;}
  [data-design-preview] .d-el[contenteditable]{cursor:text;outline:1px dashed var(--color-gold,#b08d57);}
  .b-rahmen-wahl{position:absolute;z-index:9999;pointer-events:none;transform-origin:center;
                 outline:1px solid var(--color-gold,#b08d57);outline-offset:1px;}
  .b-griff{position:absolute;width:10px;height:10px;box-sizing:border-box;pointer-events:auto;
           background:var(--color-cream,#faf7f2);border:1px solid var(--color-gold,#b08d57);}
  /* INNEN an der Kante, nicht mittig darauf. Der Vorschaukasten schneidet ab,
     was ueber ihn hinausragt (overflow:hidden - sonst liefe eine Ebene, die
     ueber die Karte hinaussteht, in den Editor hinein). Ein Griff mittig auf
     der Kante waere damit bei jeder Ebene, die die Karte fuellt, zur Haelfte
     weggeschnitten - gemessen an einer Ebene mit x=0: fuenf Pixel zum
     Treffen, und an der rechten Kante gar keine. */
  .b-griff[data-griff="nw"]{left:0;top:0;cursor:nwse-resize;}
  .b-griff[data-griff="n"]{left:50%;top:0;margin-left:-5px;cursor:ns-resize;}
  .b-griff[data-griff="ne"]{left:100%;top:0;margin-left:-10px;cursor:nesw-resize;}
  .b-griff[data-griff="e"]{left:100%;top:50%;margin:-5px 0 0 -10px;cursor:ew-resize;}
  .b-griff[data-griff="se"]{left:100%;top:100%;margin:-10px 0 0 -10px;cursor:nwse-resize;}
  .b-griff[data-griff="s"]{left:50%;top:100%;margin:-10px 0 0 -5px;cursor:ns-resize;}
  .b-griff[data-griff="sw"]{left:0;top:100%;margin-top:-10px;cursor:nesw-resize;}
  .b-griff[data-griff="w"]{left:0;top:50%;margin-top:-5px;cursor:ew-resize;}
  /* Ist der Kasten duenner als zwei Griffe, weichen sie nach aussen aus -
     sonst liegen der obere und der untere uebereinander und nur einer von
     beiden ist zu treffen. Gemessen an einer Textzeile von 14 Pixeln. */
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="nw"],
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="n"],
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="ne"]{margin-top:-10px;}
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="sw"],
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="s"],
  .b-rahmen-wahl[data-eng] .b-griff[data-griff="se"]{margin-top:0;}
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="nw"],
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="w"],
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="sw"]{margin-left:-10px;}
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="ne"],
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="e"],
  .b-rahmen-wahl[data-schmal] .b-griff[data-griff="se"]{margin-left:0;}
  /* An einem Text ziehen die Ecken die Schrift, nicht den Kasten - ein
     runder Griff sagt, dass hier etwas anderes passiert als an den Kanten. */
  .b-rahmen-wahl[data-schrift] .b-griff[data-griff="nw"],
  .b-rahmen-wahl[data-schrift] .b-griff[data-griff="ne"],
  .b-rahmen-wahl[data-schrift] .b-griff[data-griff="se"],
  .b-rahmen-wahl[data-schrift] .b-griff[data-griff="sw"]{border-radius:50%;
           background:var(--color-gold,#b08d57);}
  /* Und dieselbe Ebene in der Liste rechts, damit man die Zeile nicht sucht. */
  [data-ebene][data-gewaehlt]{outline:1px solid var(--color-gold,#b08d57);}

  .b-fuss{position:sticky;bottom:0;display:flex;flex-wrap:wrap;align-items:center;gap:0.8rem;
          background:var(--color-cream,#faf7f2);border-top:1px solid var(--color-sand-deep,#dccebc);
          padding:0.9rem 0;margin-top:1rem;}
  .b-speichern{background:var(--color-ink,#14110f);color:var(--color-cream,#faf7f2);border:0;
               cursor:pointer;padding:0.75rem 1.6rem;font-size:0.66rem;letter-spacing:0.2em;
               text-transform:uppercase;}
  .b-speichern:hover{background:var(--color-gold,#b08d57);}
  .b-schritte{display:flex;gap:0.35rem;}
  .b-schritte .b-knopf{padding:0.55rem 0.7rem;}
  .b-schritte .b-knopf[disabled]{opacity:0.35;cursor:default;}
  .b-meldung{border-left:2px solid var(--color-gold,#b08d57);padding:0.75rem 1rem;font-size:0.86rem;
             margin-bottom:1rem;}
  .b-meldung-fehler{border-left-color:#b91c1c;color:#b91c1c;}
</style>

<form method="post" enctype="multipart/form-data" data-design-form>
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="was" value="kaydet">
  <input type="hidden" name="version" value="<?= (int) $design['version'] ?>">

  <div class="b-kopf">
    <div class="b-kopf-links">
      <a href="<?= e($p('/admin/designs')) ?>" class="b-fein"><?= $tr ? '← Temalar' : '← Themen' ?></a>
      <span class="b-name"><?= e($design['name']['de']) ?></span>
      <span class="b-fein"><?= e((string) $design['status']) ?> · <?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
    </div>
    <div class="b-kopf-rechts">
      <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank" class="b-knopf">
        <?= $tr ? 'Tam ekran' : 'Ganz ansehen' ?>
      </a>
      <?php /* Derselbe kurze Weg wie in der Liste - hier, wo man gerade
               gebaut hat, ist er am naechsten. */ ?>
      <a href="<?= e(I18n::path('/v2/einladung', 'de') . '?design=' . rawurlencode((string) $design['slug'])) ?>"
         target="_blank" class="b-knopf">
        <?= $tr ? 'Davetiye oluştur' : 'Einladung anlegen' ?>
      </a>
      <button class="b-speichern"><?= $tr ? 'Kaydet' : 'Speichern' ?></button>
    </div>
  </div>

  <?php if ($ok === 'gespeichert') : ?>
    <p class="b-meldung"><?= $tr ? 'Kaydedildi.' : 'Gespeichert.' ?></p>
  <?php endif; ?>
  <?php if ($fehler === 'veraltet') : ?>
    <p class="b-meldung b-meldung-fehler">
      <?= $tr
        ? 'Bu tasarım sen açtıktan sonra başka bir yerde değiştirildi. Sayfayı tazele, sonra yeniden dene — yoksa onun işini silersin.'
        : 'Diese Vorlage wurde geändert, nachdem du sie geöffnet hast. Bitte neu laden und noch einmal – sonst überschreibst du fremde Arbeit.' ?>
    </p>
  <?php endif; ?>
  <?php if ($fehler === 'csrf') : ?>
    <p class="b-meldung b-meldung-fehler"><?= $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen.' ?></p>
  <?php endif; ?>

  <?php /*
     --- Wer haengt noch an einer aelteren Fassung? -----------------------

     Eine verschickte Einladung traegt ihre eigene Kopie dieser Vorlage. Das
     ist das Versprechen aus Phase 3B und es bleibt: was hier steht, ist der
     einzige Weg, es fuer eine bestimmte Einladung aufzuheben.

     Zwei Faecher, weil sie nicht gleich schwer wiegen. Ein Entwurf ist
     niemandem geschickt worden - ein Knopf. Eine veroeffentlichte Einladung
     liegt bei den Gaesten, und die Frage davor stellt die SEITE: ein Link
     fuehrt in den Zustand, in dem der Satz und der endgueltige Knopf stehen.
     Kein confirm() im Browser - im ganzen Haus steht kein onclick=, und die
     Richtlinie laesst nur eigene Skriptdateien zu.

     Die Knoepfe gehoeren zum zweiten Formular am Fuss der Datei (form="..."):
     sie duerfen die Vorlage nicht nebenbei mitspeichern.
  */ ?>
  <?php
  $nEntwurf = count($veraltet['draft']);
  $nNetz    = count($veraltet['published']);
  ?>

  <?php if ($ok === 'aufgefrischt') : ?>
    <p class="b-meldung">
      <?= $tr
        ? e((string) (int) ($_GET['n'] ?? 0)) . ' davetiye güncellendi.'
        : e((string) (int) ($_GET['n'] ?? 0)) . ' Einladungen nachgezogen.' ?>
    </p>
  <?php endif; ?>

  <?php if ($fragen && $nNetz > 0) : ?>
    <div class="b-meldung b-meldung-fehler">
      <p><?= $tr
        ? 'Bu ' . $nNetz . ' davetiyenin bağlantısı misafirlerin elinde. Güncellersen gördükleri kart değişir: '
          . 'sildiğin bir bölüm kaybolur, eklediğin bir bölüm şablonun hazır metniyle belirir. '
          . 'Çiftin kendi yazdıkları duruyor. Geri alınamaz.'
        : 'Bei diesen ' . $nNetz . ' Einladungen ist der Link schon bei den Gästen. Nach dem Nachziehen sehen sie '
          . 'eine andere Karte: ein gelöschter Abschnitt verschwindet, ein neuer erscheint mit dem Vorschlagstext '
          . 'der Vorlage. Was das Paar selbst geschrieben hat, bleibt. Rückgängig geht es nicht.' ?></p>
      <p style="margin-top:0.75rem;">
        <button class="b-knopf" form="b-auffrischen" name="was" value="auffrischen-veroeffentlicht">
          <?= $tr ? 'Evet, ' . $nNetz . ' davetiyeyi güncelle' : 'Ja, ' . $nNetz . ' Einladungen nachziehen' ?>
        </button>
        <a class="b-fein" style="margin-left:0.75rem;" href="<?= e($p('/admin/designs/' . $design['slug'])) ?>">
          <?= $tr ? 'Vazgeç' : 'Abbrechen' ?>
        </a>
      </p>
    </div>
  <?php elseif ($nEntwurf > 0 || $nNetz > 0) : ?>
    <p class="b-meldung">
      <?= $tr
        ? 'Bu tasarımın eski bir sürümüne bağlı: ' . $nEntwurf . ' taslak, ' . $nNetz . ' yayında.'
        : 'An einer älteren Fassung dieser Vorlage hängen: ' . $nEntwurf . ' Entwürfe, ' . $nNetz . ' veröffentlichte.' ?>
      <?php if ($nEntwurf > 0) : ?>
        <button class="b-knopf" style="margin-left:0.75rem;" form="b-auffrischen"
                name="was" value="auffrischen-entwuerfe">
          <?= $tr ? 'Taslakları güncelle (' . $nEntwurf . ')' : 'Entwürfe nachziehen (' . $nEntwurf . ')' ?>
        </button>
      <?php endif; ?>
      <?php if ($nNetz > 0) : ?>
        <a class="b-fein" style="margin-left:0.75rem;"
           href="<?= e($p('/admin/designs/' . $design['slug'])) ?>?auffrischen=veroeffentlicht">
          <?= $tr ? 'Yayındakilere de bak (' . $nNetz . ')' : 'Auch die veröffentlichten (' . $nNetz . ')' ?>
        </a>
      <?php endif; ?>
    </p>
  <?php endif; ?>


  <div class="b-schale">

    <?php /* --- Links: was die Seite zeigt ------------------------------- */ ?>
    <div class="b-spalte b-spalte-fest">
      <?php include __DIR__ . '/design-edit-liste.php'; ?>
    </div>

    <?php /*
       --- Mitte: die Karte ------------------------------------------------

       Die Vorschau ist die Karte selbst, nicht ihr Abbild: derselbe
       Stilblock, dasselbe Markup wie auf der oeffentlichen Seite, nur
       kleiner. Das Skript aendert daran ausschliesslich CSS-Variablen,
       Textknoten und Inline-Kaesten - es zeichnet nichts nach.
    */ ?>
    <div class="b-spalte">
      <div class="b-buehne">
        <?php /*
           Zwei Ansichten, und sie beantworten verschiedene Fragen.

           "Karte" ist die Karte selbst - dieselbe, die der Gast sieht, nur
           kleiner. Sie folgt jedem Tastendruck ohne Speichern, weil das
           Skript nur CSS-Variablen, Textknoten und Inline-Kaesten setzt.

           Die drei Geraete zeigen die GANZE Seite in einem Rahmen, und zwar
           die oeffentliche Adresse selbst - Kuvert, Film, Karte, Abschnitte,
           alles. Sie zeigt den GESPEICHERTEN Stand: ein Rahmen holt sich die
           Seite vom Server, und der kennt nur, was in der Datenbank steht.
           Das ist keine Einschraenkung, die sich beheben liesse, sondern der
           Unterschied zwischen "was ich gerade tippe" und "was da draussen
           steht" - deshalb sagt es die Zeile darunter auch so.

           Telefon zuerst: Einladungen werden auf Telefonen geoeffnet, fast
           nie am Schreibtisch.
        */ ?>
        <div class="b-geraete">
          <button type="button" class="b-knopf" data-ansicht="karte" data-aktiv><?= $tr ? 'Kart' : 'Karte' ?></button>
          <button type="button" class="b-knopf" data-ansicht="390"><?= $tr ? 'Telefon' : 'Telefon' ?></button>
          <button type="button" class="b-knopf" data-ansicht="820"><?= $tr ? 'Tablet' : 'Tablet' ?></button>
          <button type="button" class="b-knopf" data-ansicht="1280"><?= $tr ? 'Masaüstü' : 'Schreibtisch' ?></button>
        </div>

        <div class="<?= e($scope) ?> relative overflow-hidden border border-sand-deep"
             data-design-preview
             style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <div class="absolute inset-0"><?= $seite ?></div>
          <div class="absolute inset-0"><?= $karte ?></div>
        </div>

        <?php /*
           Und darunter die Abschnitte - lebend.

           Sie stehen nicht IN der Vorschau, sondern darunter: die Karte hat
           einen festen Rahmen (Seitenverhaeltnis), die Abschnitte eine
           variable Laenge. Genau dieselbe Anordnung wie auf der echten
           Seite, wo die Buehne im Fluss steht und die Flaeche darunter
           weiterlaeuft.

           Der Kasten ist beim Laden leer und wird es bleiben, wenn kein
           Skript laeuft - dann ist es wie bisher: die Karte lebt, die
           Abschnitte stehen im Rahmen. Gefuellt wird er vom Server, nicht
           hier: das Skript schickt das Formular an .../vorschau und legt die
           Antwort hinein.

           overflow-hidden, weil die Flaeche von Kante zu Kante geht und die
           Spalte des Panels schmaler ist als eine Seite.
        */ ?>
        <div class="overflow-hidden border border-t-0 border-sand-deep"
             data-live-abschnitte hidden
             data-adresse="<?= e($p('/admin/designs/' . $design['slug'] . '/vorschau')) ?>"></div>

        <?php /*
           Der Rahmen entsteht erst beim ersten Klick auf ein Geraet. Ein
           iframe im Markup laedt die Seite mit - samt Kuvertfilm - und zwar
           jedes Mal, wenn jemand den Editor oeffnet, auch wenn er ihn nie
           ansieht.
        */ ?>
        <div class="b-rahmen" data-ansicht-rahmen hidden
             data-adresse="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>"
             data-wort-seite="<?= $tr
                ? 'Bu çerçeve KAYDEDİLMİŞ hâli gösterir — sayfanın kendisini sunucudan alır. Değişikliği görmek için önce kaydet.'
                : 'Der Rahmen zeigt den GESPEICHERTEN Stand - er holt die Seite vom Server. Erst speichern, dann sieht man die Änderung.' ?>"></div>

        <?php /*
           Die Karte ist anfassbar, und das sieht man ihr nicht an - deshalb
           steht es darunter. Zwei Zeilen: die erste sagt, was die Hand kann,
           die zweite, was die Ansicht zeigt.

           Das Kuvert fehlt in dieser Aufzaehlung mit Absicht: die Vorschau
           baut nur Seite und Karte, eine Kuvertebene hat hier gar keinen
           Knoten und bleibt bei ihren Zahlen.
        */ ?>
        <p class="b-fein" style="margin-top:0.75rem;">
          <?= $tr
            ? 'Kartın üstünde: sürükle → yeri · kenar tutamakları → en/boy · köşeler → yazıda büyüklük, resimde boyut · çift tık → yazıyı yerinde düzenle (Enter biter, Esc geri alır) · ok tuşları → %1, Shift ile %5 · Esc → seçimi bırak. Kuvert katmanları burada görünmez, onlar sayılarla kalır.'
            : 'Auf der Karte: ziehen → Platz · Griffe an den Kanten → Breite/Höhe · Ecken → beim Text die Schriftgröße, beim Bild die Größe · Doppelklick → Text an Ort und Stelle (Enter beendet, Esc nimmt zurück) · Pfeiltasten → 1 %, mit Umschalt 5 % · Esc → Auswahl lösen. Kuvertebenen stehen nicht in dieser Vorschau, sie bleiben bei ihren Zahlen.' ?>
        </p>

        <p class="b-fein" style="margin-top:0.5rem;" data-ansicht-hinweis>
          <?= $tr
            ? 'Kart: renk, yazı, metin ve katman yerleşimi anında değişir. BÖLÜMLER kartta değil, kartın altında sayfada — soldan birini seçince çerçeveye geçilir ve o bölüme kaydırılır. Çerçeve KAYDEDİLMİŞ hâli gösterir, yani yeni yazdığın bir bölüm önce kaydedilmeli.'
            : 'Karte: Farbe, Schrift, Text und der Kasten einer Ebene ändern sich sofort. ABSCHNITTE stehen nicht auf der Karte, sondern darunter auf der Seite — wer links einen auswählt, bekommt den Rahmen und darin die Stelle. Der Rahmen zeigt den GESPEICHERTEN Stand.' ?>
        </p>
      </div>
    </div>

    <?php /* --- Rechts: die Einstellungen des Ausgewaehlten ---------------- */ ?>
    <div class="b-spalte b-spalte-fest">
      <?php /*
         Alle Tafeln stehen im Markup, sichtbar ist eine. Der Server rendert
         sie einmal, das Skript blendet um - kein zweiter Endpunkt, kein
         Nachladen, und beim Absenden geht alles gemeinsam mit. Eine Tafel,
         die man nicht sieht, traegt ihre Werte trotzdem; genau deshalb
         verliert das Umschalten nichts.
      */ ?>
      <div class="b-panel" data-panel="thema">
        <?php include __DIR__ . '/design-edit-sections.php'; ?>
      </div>
      <?php include __DIR__ . '/design-edit-tafeln.php'; ?>
    </div>

  </div>

  <div class="b-fuss">
    <button class="b-speichern"><?= $tr ? 'Kaydet' : 'Speichern' ?></button>
    <?php /*
       Zurueck und vor.

       Strg+Z gibt es am Telefon nicht, und gebraucht wird es dort mehr als am
       Schreibtisch: wer ausprobiert, verstellt auch mal etwas, das gut war.
       Ohne Weg zurueck probiert man nicht mehr - und Ausprobieren ist genau
       das, wofuer diese Seite da ist.

       Hier unten und nicht oben in der Kopfzeile: der Balken ist fest, und
       der Daumen liegt ohnehin neben Speichern.

       type="button", sonst schickt der Knopf das Formular ab - er steht mitten
       darin. Ohne Skript bleiben beide gesperrt, statt etwas zu versprechen,
       das sie dann nicht halten.
    */ ?>
    <span class="b-schritte">
      <button type="button" class="b-knopf" data-zurueck disabled
              title="<?= $tr ? 'Geri al (Ctrl+Z)' : 'Zurück (Strg+Z)' ?>">↶ <?= $tr ? 'Geri al' : 'Zurück' ?></button>
      <button type="button" class="b-knopf" data-vor disabled
              title="<?= $tr ? 'İleri al (Ctrl+Shift+Z)' : 'Vor (Strg+Umschalt+Z)' ?>">↷ <?= $tr ? 'İleri' : 'Vor' ?></button>
    </span>
    <?php /*
       Die Bestaetigung gehoert dorthin, wo gedrueckt wurde.

       Sie stand nur ganz oben, ueber der Karte - und wer speichert, sieht auf
       den Knopf unten und danach auf die Karte in der Mitte. Sie kam also an,
       nur nie ins Auge: "1 kaydedildi gormedim". Gemessen an der lebenden
       Seite: die Meldung stand bei 200 Pixeln, der Knopf steht am unteren
       Rand. Oben bleibt sie stehen, hier kommt sie dazu.
    */ ?>
    <?php if ($ok === 'gespeichert') : ?>
      <span class="b-fein" style="color:var(--color-ink,#14110f);">
        <?= $tr ? '✓ Kaydedildi.' : '✓ Gespeichert.' ?>
      </span>
    <?php endif; ?>
    <span class="b-fein" data-sec-hinweis>
      <?= $tr ? 'Soldan bir bölüm seç, ayarları sağda açılır.' : 'Links einen Abschnitt wählen - seine Einstellungen stehen dann rechts.' ?>
    </span>
  </div>
</form>
<?php /*
   Das zweite Formular, leer bis auf das Token.

   Die Auffrischknoepfe stehen oben im grossen Formular und zeigen mit
   form="b-auffrischen" hierher - ein Formular im Formular waere ungueltig,
   und im grossen mitzufahren hiesse, die Vorlage nebenbei zu speichern.
*/ ?>
<form method="post" id="b-auffrischen">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
</form>
