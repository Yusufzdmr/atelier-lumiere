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
  @media (min-width:1120px){.b-buehne{position:sticky;top:5.5rem;}}

  .b-liste{list-style:none;margin:0;padding:0;display:grid;gap:0.35rem;}
  .b-zeile{display:flex;align-items:center;gap:0.35rem;padding:0.45rem 0.5rem;
           border:1px solid var(--color-sand-deep,#dccebc);}
  .b-zeile[data-aktiv]{border-color:var(--color-ink,#14110f);background:var(--color-sand,#ede4d8);}
  .b-zeile[data-weg]{opacity:0.4;}
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

  .b-fuss{position:sticky;bottom:0;display:flex;flex-wrap:wrap;align-items:center;gap:0.8rem;
          background:var(--color-cream,#faf7f2);border-top:1px solid var(--color-sand-deep,#dccebc);
          padding:0.9rem 0;margin-top:1rem;}
  .b-speichern{background:var(--color-ink,#14110f);color:var(--color-cream,#faf7f2);border:0;
               cursor:pointer;padding:0.75rem 1.6rem;font-size:0.66rem;letter-spacing:0.2em;
               text-transform:uppercase;}
  .b-speichern:hover{background:var(--color-gold,#b08d57);}
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

  <div class="b-schale">

    <?php /* --- Links: was die Seite zeigt ------------------------------- */ ?>
    <div class="b-spalte">
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
        <div class="<?= e($scope) ?> relative overflow-hidden border border-sand-deep"
             data-design-preview
             style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <div class="absolute inset-0"><?= $seite ?></div>
          <div class="absolute inset-0"><?= $karte ?></div>
        </div>
        <p class="b-fein" style="margin-top:0.75rem;">
          <?= $tr
            ? 'Renk, yazı, metin ve yerleşim anında değişir. Hareket ve görsel için kaydet.'
            : 'Farbe, Schrift, Text und Anordnung ändern sich sofort. Bewegung und Bild brauchen ein Speichern.' ?>
        </p>
      </div>
    </div>

    <?php /* --- Rechts: die Einstellungen des Ausgewaehlten ---------------- */ ?>
    <div class="b-spalte">
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
    <span class="b-fein" data-sec-hinweis>
      <?= $tr ? 'Soldan bir bölüm seç, ayarları sağda açılır.' : 'Links einen Abschnitt wählen - seine Einstellungen stehen dann rechts.' ?>
    </span>
  </div>
</form>
