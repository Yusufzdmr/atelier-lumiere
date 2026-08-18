<?php
/**
 * Die fertige Einladung.
 *
 * Der Umschlag liegt über der Karte, bis jemand darauf tippt – erst dann
 * startet auch die Musik (Browser lassen Ton ohne Zutun nicht zu).
 *
 * @var string $locale
 * @var array<string,mixed> $invitation
 * @var array<string,mixed>|null $guest  persönlich adressierte Fassung
 * @var array<string,mixed> $theme
 * @var string $style
 * @var string $dateLong
 * @var string $weekday
 * @var list<array<string,mixed>> $rsvps
 * @var bool $sent
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Intro;
use Atelier\Scenes;
use Atelier\Video;

$de = $locale === 'de';
$sections = (array) ($invitation['sections'] ?? []);
$events = (array) ($invitation['events'] ?? []);
$first = (array) ($events[0] ?? []);
$photos = (array) ($invitation['photos'] ?? []);
$bride = (string) ($invitation['bride'] ?? '');
$groom = (string) ($invitation['groom'] ?? '');
$initials = mb_strtoupper(mb_substr($bride, 0, 1) . mb_substr($groom, 0, 1));
// Persönliche Fassung: „Liebe Familie Müller“ steht über allem anderen.
$guestName = (string) (($guest ?? null)['name'] ?? '');
// Gezeichnete Hintergrundkunst des Themas – leer, wenn das Thema keine will.
$scene = Scenes::html((string) ($theme['scene'] ?? 'botanical'), $theme);

// Die vier wählbaren Bewegungen. Jede kommt aus dem Panel; unbekannte Werte
// fallen auf die Voreinstellung zurück, damit ein alter Datensatz die Karte
// nicht ohne Bewegung – oder schlimmer: unsichtbar – stehen lässt.
$nameAnim = (string) ($theme['nameAnimation'] ?? 'write');
if (!in_array($nameAnim, \Atelier\Themes::NAME_ANIMATIONS, true)) {
    $nameAnim = 'write';
}
$particle = (string) ($theme['particle'] ?? 'petal');
if (!in_array($particle, \Atelier\Themes::PARTICLES, true)) {
    $particle = 'petal';
}
// Eröffnungsszene: die gespielte Abfolge vor der Karte.
$introKind = (string) ($theme['intro'] ?? 'none');
if (!in_array($introKind, \Atelier\Themes::INTROS, true)) {
    $introKind = 'none';
}
$introHtml = Intro::html($introKind, $theme);
$backdropHtml = Intro::backdrop($introKind, $theme);
$introMs = \Atelier\Themes::introDuration($introKind);

$idleKind = (string) ($theme['idle'] ?? 'breathe');
if (!in_array($idleKind, \Atelier\Themes::IDLES, true)) {
    $idleKind = 'breathe';
}

$revealKind = (string) ($theme['reveal'] ?? 'up');
if (!in_array($revealKind, \Atelier\Themes::REVEALS, true)) {
    $revealKind = 'up';
}

/**
 * Einen Namen ausgeben – je nach gewählter Bewegung als ganzes Wort oder
 * Buchstabe für Buchstabe.
 *
 * Beim Buchstabenlauf bekommt jedes Zeichen seinen eigenen Versatz (--i).
 * Leerzeichen bleiben Zeichen, sonst rutschen die Wörter zusammen.
 */
$writeName = static function (string $name, string $kind, float $delay): string {
    // Beim Buchstabenlauf traegt jedes Zeichen eine eigene Bewegung; der
    // Goldverlauf der Umhuellung erreicht sie dann nicht mehr (siehe
    // .t-name-solid im Stylesheet), also volle Farbe statt Verlauf.
    $classes = $kind === 'letters' ? 't-script t-name-solid' : 't-script foil';
    if ($kind === 'write') {
        $classes .= ' write';
    } elseif ($kind === 'fade') {
        $classes .= ' t-name-fade';
    } elseif ($kind === 'rise') {
        $classes .= ' t-name-rise';
    }
    // 'glow' und 'none' bekommen keine Eingangsbewegung; 'glow' behält das
    // wandernde Gold, das in .foil ohnehin steckt.

    $style = ' style="--write-delay: ' . $delay . 's"';
    $size = ' text-[3.1rem] leading-[1.05] sm:text-[4.4rem]';

    if ($kind !== 'letters') {
        return '<span class="' . $classes . $size . '"' . $style . '>' . e($name) . '</span>';
    }

    $out = '<span class="' . $classes . $size . '"' . $style . '>';
    $chars = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($chars as $i => $char) {
        $out .= '<span class="t-letter" style="--i: ' . $i . '">' . e($char) . '</span>';
    }
    return $out . '</span>';
};
$occasion = \Atelier\Invitations::occasionLine((string) ($invitation['eventType'] ?? ''), $locale);

/**
 * Die Schmuckelemente eines Ortes ausgeben.
 *
 * Position, Groesse und Bewegung stehen im Stilblock des Themas; hier steht
 * nur das Bild mit seiner Klasse. Alternativtext bleibt leer: Blumen und
 * Rahmen sind Zierde, keine Information – ein Vorleseprogramm soll sie
 * ueberspringen.
 */
$decorations = static function (string $spot) use ($theme): string {
    $html = '';
    foreach ((array) ($theme['decorations'] ?? []) as $deco) {
        if (!is_array($deco) || (string) ($deco['src'] ?? '') === '' || ($deco['spot'] ?? 'card') !== $spot) {
            continue;
        }
        $html .= '<img src="' . e((string) $deco['src']) . '" alt="" aria-hidden="true" loading="lazy"'
            . ' class="t-deco-' . e((string) $deco['id']) . '">';
    }
    return $html;
};
$field = 'w-full border-b bg-transparent px-0 py-2.5 text-[0.95rem] outline-none';
$coming = 0;
foreach ($rsvps as $rsvp) {
    if (!empty($rsvp['coming'])) {
        $coming += (int) ($rsvp['count'] ?? 1);
    }
}
?>
<style><?= $style ?></style>

<div class="theme-<?= e((string) $theme['id']) ?> relative min-h-screen overflow-hidden" style="background: <?= e((string) $theme['bg']) ?>">
  <?php
/*
 * Nur in der Designvorschau: der Weg von „gefaellt mir" zu „dann nehme ich
 * das". Die Vorschau ist eine echte Einladung mit Beispieldaten, erkennbar
 * am Slug.
 */
if ((string) ($invitation['slug'] ?? '') === 'vorschau') : ?>
  <?php
  /*
   * Die Leiste sagt nicht nur „Vorschau", sondern welche sechs Bewegungen
   * gerade laufen. Im Panel stehen dafuer nur Auswahllisten mit Namen –
   * ohne diese Zeile weiss niemand, was „Dunkelkammer" oder „Halka" tut.
   */
  $tl = $de ? 'de' : 'tr';
  $moves = [
      [$de ? 'Szene' : 'Sahne',        \Atelier\Themes::introLabel($introKind, $tl)],
      [$de ? 'Wartet' : 'Beklerken',   \Atelier\Themes::idleLabel($idleKind, $tl)],
      [$de ? 'Karte' : 'Kart',         \Atelier\Themes::animationLabel((string) $theme['animation'], $tl)],
      [$de ? 'Namen' : 'İsimler',      \Atelier\Themes::nameAnimationLabel($nameAnim, $tl)],
      [$de ? 'Teilchen' : 'Parçacık',  \Atelier\Themes::particleLabel($particle, $tl)],
      [$de ? 'Abschnitte' : 'Bölümler', \Atelier\Themes::revealLabel($revealKind, $tl)],
  ];
  // „Nochmal ansehen" ist bewusst ein Link auf dieselbe Adresse: das
  // Neuladen setzt die ganze Abfolge zurueck, ohne eine Zeile Skript –
  // und die Inhaltsrichtlinie verbietet Handler im HTML ohnehin.
  $again = \Atelier\I18n::path('/designs/' . (string) ($theme['id'] ?? ''), $locale);
  $query = array_filter([
      'intro' => $introKind, 'idle' => $idleKind, 'animation' => (string) $theme['animation'],
      'nameAnimation' => $nameAnim, 'particle' => $particle, 'reveal' => $revealKind,
      'scene' => (string) ($theme['scene'] ?? ''),
  ], static fn (string $v): bool => $v !== '');
  ?>
  <?php /* Ueber Kuvert (z-50) und Szene (z-60): in der Vorschau soll man die
           Angaben auch dann lesen koennen, wenn noch nichts geoeffnet ist. */ ?>
  <div class="fixed inset-x-0 bottom-0 z-[70] border-t border-sand-deep bg-cream/95 px-5 py-3 backdrop-blur">
    <div class="mx-auto max-w-5xl">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-[0.72rem] uppercase tracking-[0.18em] text-muted"><?= e((string) ($theme['name'] ?? '')) ?></span>
        <div class="flex items-center gap-3">
          <a href="<?= e($again . '?' . http_build_query($query)) ?>"
             class="border border-sand-deep px-4 py-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:border-gold hover:text-gold">
            <?= $de ? 'Nochmal ansehen' : 'Tekrar oynat' ?>
          </a>
          <a href="<?= e(\Atelier\I18n::path('/designs', $locale)) ?>"
             class="text-[0.68rem] uppercase tracking-[0.16em] text-muted hover:text-gold">
            <?= $de ? 'Alle Designs' : 'All designs' ?>
          </a>
          <a href="<?= e(\Atelier\I18n::path('/einladung', $locale)) ?>?design=<?= e((string) ($theme['id'] ?? '')) ?>"
             class="bg-ink px-4 py-2.5 text-[0.62rem] uppercase tracking-[0.12em] text-cream transition-colors hover:bg-gold sm:px-5 sm:text-[0.68rem] sm:tracking-[0.16em]">
            <?= e(\Atelier\I18n::t('invite.useDesign')) ?>
          </a>
        </div>
      </div>

      <?php /* Auf dem Telefon zog sich diese Liste ueber fuenf Zeilen und die
               Leiste nahm die halbe Seite. Dort laeuft sie jetzt in einer
               Zeile und wird geschoben; ab sm bricht sie wieder um. */ ?>
      <dl class="mt-2.5 flex gap-x-5 gap-y-1 overflow-x-auto text-[0.68rem] leading-relaxed sm:flex-wrap sm:overflow-visible [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <?php foreach ($moves as [$key, $value]) : ?>
          <div class="flex shrink-0 gap-1.5 whitespace-nowrap sm:whitespace-normal">
            <dt class="uppercase tracking-[0.14em] text-gold"><?= e($key) ?></dt>
            <dd class="text-muted"><?= e($value) ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    </div>
  </div>
<?php endif; ?>
<?= $scene ?>

  <?php /* Schwebende Teilchen in der Blattfarbe des Themas. Rein zierend,
           deshalb ohne Beschriftung und ohne Klickfläche. Form kommt aus
           dem Thema; bei „nichts" wird gar nichts erst gezeichnet. */ ?>
  <?php if ($particle !== 'none') : ?>
    <div class="t-petals" aria-hidden="true">
      <?php for ($i = 0; $i < 12; $i++) : ?>
        <span class="t-petal t-petal-<?= e($particle) ?>"
              style="left: <?= ($i * 8.4 + 3) % 96 ?>%; background: <?= e((string) $theme['petal']) ?>; color: <?= e((string) $theme['petal']) ?>; opacity: .45;
                     animation-duration: <?= 18 + ($i % 5) * 5 ?>s; animation-delay: -<?= $i * 3 ?>s;
                     transform: scale(<?= 0.7 + ($i % 4) * 0.22 ?>)"></span>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

<?= $decorations('page') ?>
  <?php /* Umschlag – verschwindet nach dem Antippen.
           Klappe, Karte und Siegel sind einzelne Flaechen: erst bricht das
           Siegel, dann schlaegt die Klappe auf, dann hebt sich die Karte
           heraus. Vorher war es ein Rechteck, das ausblendete. */ ?>
  <?= $backdropHtml ?>
  <?= $introHtml ?>

  <?php /* Bei Themen mit dauerhaftem Hintergrund (z. B. Lumina) darf die
           Kuvert-Buehne nicht mit einer vollen Farbe darueberliegen – sonst
           deckt sie das Video wieder zu. Dann faellt der Hintergrund weg
           und die Buehne wird transparent. */ ?>
  <div class="t-envelope-stage idle-<?= e($idleKind) ?> fixed inset-0 z-50 flex flex-col items-center justify-center gap-9 px-6"
       data-envelope data-animation="<?= e((string) $theme['animation']) ?>"
       data-intro-ms="<?= (int) $introMs ?>"
       <?php if ($backdropHtml === '') : ?>style="background: <?= e((string) $theme['bg']) ?>"<?php endif; ?>>
    <?= $scene ?>

    <button type="button" data-envelope-open
            class="t-envelope relative w-full max-w-sm border shadow-[0_30px_60px_-25px_rgba(0,0,0,.45)]"
            style="aspect-ratio: 8 / 5; background: <?= e((string) $theme['envelope']) ?>; border-color: <?= e((string) $theme['envelopeEdge']) ?>"
            aria-label="<?= $de ? 'Einladung öffnen' : 'Open invitation' ?>">
      <span class="t-sheet" style="background: <?= e((string) $theme['paper']) ?>; border: 1px solid <?= e((string) $theme['paperEdge']) ?>">
        <span class="font-display text-2xl font-light tracking-[0.14em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e($initials) ?></span>
      </span>
      <?= Scenes::envelopeArt((string) ($theme['scene'] ?? ''), $theme) ?>
      <span class="t-flap" style="background: <?= e((string) $theme['envelopeFlap']) ?>"></span>
      <?php /* Zwei Ebenen mit Absicht: aussen sitzt die Mitte (Tailwind setzt
               dafuer translate), innen bewegt sich das Siegel. In einem
               Element wuerde jede Animation mit transform die Zentrierung
               ueberschreiben und das Siegel spraenge in die Ecke. */ ?>
      <span class="absolute left-1/2 top-[46%] z-[6] -translate-x-1/2 -translate-y-1/2">
        <span class="t-seal relative flex h-16 w-16 items-center justify-center font-display text-lg"
              style="background-color: <?= e((string) $theme['seal']) ?>; color: <?= e((string) $theme['sealText']) ?>"><?= e($initials) ?></span>
      </span>
      <?= $decorations('envelope') ?>
    </button>

    <div class="t-hint relative text-center">
      <div class="text-[0.6rem] uppercase tracking-[0.34em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e($occasion) ?></div>
      <div class="t-script foil mt-3 text-4xl leading-none"><?= e($bride) ?> &amp; <?= e($groom) ?></div>
      <div class="mt-4 text-[0.62rem] uppercase tracking-[0.28em]" style="color: <?= e((string) $theme['accent']) ?>">
        <?= $de ? 'Tippen zum Öffnen' : 'Tap to open' ?>
      </div>
    </div>
  </div>

  <?php /* Bei einem Backdrop-Thema (Lumina) muss der Karteninhalt eine eigene
           Stapelebene ueber dem Video haben – sonst deckt das fixed z-0
           Backdrop den ganzen normalen Fluss zu. Ohne Backdrop schadet die
           zusaetzliche Klasse nichts. */ ?>
  <div class="mx-auto max-w-2xl px-5 py-16 sm:py-24 <?= $backdropHtml !== '' ? 't-above-backdrop' : '' ?>">
    <div class="t-card rv-<?= e($revealKind) ?> relative overflow-hidden px-6 py-14 text-center sm:px-12"
         data-speed="<?= (int) ($theme['animationSpeed'] ?? 1200) ?>"
         style="background: <?= e((string) $theme['paper']) ?>; color: <?= e((string) $theme['fg']) ?>; border: 1px solid <?= e((string) $theme['paperEdge']) ?>">

      <?= $decorations('card') ?>

      <div class="relative">
        <?php if ($guestName !== '') : ?>
          <div class="mb-9">
            <div class="font-display text-xl font-light italic sm:text-2xl" style="color: <?= e((string) $theme['accent']) ?>">
              <?= e(\Atelier\Guests::salutation($guest ?? [], $locale)) ?>,
            </div>
            <div class="mx-auto mt-6 h-px w-16" style="background: <?= e((string) $theme['accentSoft']) ?>"></div>
          </div>
        <?php endif; ?>

        <div class="text-[0.58rem] uppercase tracking-[0.34em]" style="color: <?= e((string) $theme['soft']) ?>">
          <?= e(\Atelier\Invitations::occasionLine((string) ($invitation['eventType'] ?? ''), $locale)) ?>
        </div>

        <?php /* Wie die Namen kommen, steht im Thema – geschrieben, blendend,
                 aufsteigend, Buchstabe für Buchstabe oder ohne Bewegung. */ ?>
        <h1 class="t-name mt-7 flex flex-col items-center gap-1">
          <?= $writeName($bride, $nameAnim, 0.45) ?>
          <span class="font-display text-2xl italic sm:text-3xl" style="color: <?= e((string) $theme['accent']) ?>">&amp;</span>
          <?= $writeName($groom, $nameAnim, 1.1) ?>
        </h1>

        <div class="mx-auto mt-8 h-px w-28" style="background: <?= e((string) $theme['accent']) ?>"></div>

        <?php foreach ($events as $i => $event) : ?>
          <div class="iv <?= $i > 0 ? 'mt-8 border-t pt-8' : 'mt-8' ?>" style="<?= $i > 0 ? 'border-color:' . e((string) $theme['paperEdge']) : '' ?>">
            <?php if (count($events) > 1 && ($event['name'] ?? '') !== '') : ?>
              <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e((string) $event['name']) ?></div>
            <?php endif; ?>
            <div class="t-date mt-2 text-[0.72rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['soft']) ?>">
              <?= e($i === 0 ? $weekday : '') ?>
            </div>
            <div class="font-display mt-1 text-2xl font-light"><?= e(\Atelier\Dates::long((string) ($event['date'] ?? ''), $locale)) ?></div>
            <?php if (($event['time'] ?? '') !== '') : ?>
              <div class="mt-1 text-[0.9rem]" style="color: <?= e((string) $theme['soft']) ?>"><?= e((string) $event['time']) ?></div>
            <?php endif; ?>
            <?php if (($event['venue'] ?? '') !== '') : ?>
              <div class="mt-3 text-[0.95rem]"><?= e((string) $event['venue']) ?></div>
            <?php endif; ?>
            <?php if (!empty($sections['location']) && ($event['address'] ?? '') !== '') : ?>
              <div class="text-[0.85rem]" style="color: <?= e((string) $theme['soft']) ?>"><?= e((string) $event['address']) ?></div>
              <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(rawurlencode((string) $event['address'])) ?>"
                 target="_blank" rel="noopener noreferrer"
                 class="mt-3 inline-block border px-5 py-2 text-[0.62rem] uppercase tracking-[0.2em]"
                 style="border-color: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['accent']) ?>">
                <?= e(I18n::t('contact.mapRoute')) ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php if (($invitation['message'] ?? '') !== '') : ?>
          <p class="iv mx-auto mt-10 max-w-md text-[0.98rem] leading-relaxed" style="color: <?= e((string) $theme['soft']) ?>">
            <?= e((string) $invitation['message']) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($sections['countdown']) && ($first['date'] ?? '') !== '') : ?>
          <div class="iv mt-10 flex justify-center gap-6" data-countdown="<?= e((string) $first['date'] . 'T' . ((string) ($first['time'] ?? '12:00'))) ?>">
            <?php foreach (['days' => 'countdownDays', 'hours' => 'countdownHours', 'minutes' => 'countdownMin', 'seconds' => 'countdownSec'] as $key => $dictKey) : ?>
              <div>
                <div class="font-display text-3xl font-light" data-<?= e($key) ?>>00</div>
                <div class="mt-1 text-[0.55rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.' . $dictKey)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['family']) && !empty($invitation['families'])) : ?>
          <div class="iv mt-10 text-[0.8rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>">
            <?= e((string) ($invitation['families']['bride'] ?? '')) ?> · <?= e((string) ($invitation['families']['groom'] ?? '')) ?>
          </div>
        <?php endif; ?>

        <?php if ($photos !== []) : ?>
          <div class="iv iv-mask mt-12 grid grid-cols-2 gap-3">
            <?php foreach (array_slice($photos, 0, 4) as $i => $photo) : ?>
              <img src="<?= e((string) $photo) ?>" alt="<?= e($bride . ' & ' . $groom) ?>" loading="lazy" decoding="async"
                   class="h-full w-full object-cover" style="aspect-ratio: <?= $i % 3 === 0 ? '3/4' : '1/1' ?>">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['program']) && ($invitation['program'] ?? []) !== []) : ?>
          <div class="iv mt-12 text-left">
            <div class="text-center text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.programTitle')) ?></div>
            <ol class="mx-auto mt-6 max-w-sm space-y-4">
              <?php foreach ((array) $invitation['program'] as $item) : ?>
                <li class="flex gap-5">
                  <span class="w-14 shrink-0 text-[0.8rem]" style="color: <?= e((string) $theme['accent']) ?>"><?= e((string) ($item['time'] ?? '')) ?></span>
                  <span class="text-[0.92rem]"><?= e((string) ($item['title'] ?? '')) ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['menu']) && ($invitation['menu'] ?? []) !== []) : ?>
          <div class="iv mt-12">
            <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.menuTitle')) ?></div>
            <ul class="mt-5 space-y-2 text-[0.92rem]">
              <?php foreach ((array) $invitation['menu'] as $dish) : ?>
                <li><?= e((string) $dish) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['video']) && Video::isSupported((string) ($invitation['videoUrl'] ?? ''))) : ?>
          <div class="iv mt-12"><?= Video::embedBox((string) $invitation['videoUrl'], $bride . ' & ' . $groom, (string) ($photos[0] ?? '')) ?></div>
        <?php endif; ?>

        <?php if (!empty($sections['rsvp'])) : ?>
          <div class="iv mt-14 border-t pt-10" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
            <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.rsvpTitle')) ?></div>

            <?php if ($sent) : ?>
              <p class="mt-5 text-[0.95rem]"><?= e(I18n::t('invite.rsvpThanks')) ?></p>
            <?php else : ?>
              <form method="post" class="mx-auto mt-6 max-w-sm space-y-5 text-left">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <?php if (($guest ?? null) !== null) : ?>
                  <?php /* Damit das Paar sieht, wer von der Liste noch nicht geantwortet hat. */ ?>
                  <input type="hidden" name="gast" value="<?= e((string) $guest['token']) ?>">
                <?php endif; ?>

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpName')) ?> *</label>
                  <?php /* Wer über seinen persönlichen Link kommt, muss den eigenen Namen nicht abtippen. */ ?>
                  <input name="name" required value="<?= e($guestName) ?>" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <div class="flex gap-4">
                  <label class="flex flex-1 cursor-pointer items-center gap-2 border px-4 py-2.5 text-[0.85rem]" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
                    <input type="radio" name="coming" value="1" checked class="h-4 w-4"> <?= e(I18n::t('invite.rsvpComing')) ?>
                  </label>
                  <label class="flex flex-1 cursor-pointer items-center gap-2 border px-4 py-2.5 text-[0.85rem]" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
                    <input type="radio" name="coming" value="0" class="h-4 w-4"> <?= e(I18n::t('invite.rsvpNotComing')) ?>
                  </label>
                </div>

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpCount')) ?></label>
                  <input type="number" name="count" value="1" min="1" max="20" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpNote')) ?></label>
                  <input name="note" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <button class="w-full px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em]"
                        style="background: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['paper']) ?>">
                  <?= e(I18n::t('invite.rsvpSend')) ?>
                </button>

                <p class="mt-4 text-[0.7rem] leading-relaxed" style="color: <?= e((string) $theme['soft']) ?>">
                  <?= e(I18n::t('invite.rsvpDataNote')) ?>
                </p>
              </form>
            <?php endif; ?>

            <?php if ($coming > 0) : ?>
              <p class="mt-6 text-[0.75rem] uppercase tracking-[0.18em]" style="color: <?= e((string) $theme['soft']) ?>">
                <?= $coming ?> <?= $de ? 'Zusagen' : 'replies' ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (($invitation['closing'] ?? '') !== '') : ?>
          <p class="iv mt-12 font-display text-lg italic"><?= e((string) $invitation['closing']) ?></p>
        <?php endif; ?>

        <?php if (($invitation['hashtag'] ?? '') !== '') : ?>
          <div class="iv t-script foil mt-8 text-3xl"><?= e((string) $invitation['hashtag']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php
    /*
     * Nur eine Datei aus dem eigenen Upload-Ordner. Aeltere Einladungen tragen
     * hier noch den YouTube-Link, den das Feld frueher entgegennahm; daraus
     * wuerde ein <audio> mit einer HTML-Seite darin, ein Knopf, der nichts tut.
     * Lieber kein Knopf als ein toter.
     */
    $music = (string) ($invitation['musicUrl'] ?? '');
    $hasMusic = !empty($sections['music']) && str_starts_with($music, '/uploads/');
    ?>
    <?php if ($hasMusic) : ?>
      <?php /* Ton startet erst nach dem Öffnen – Browser erlauben es nicht anders. */ ?>
      <audio data-music loop preload="none" src="<?= e($music) ?>"></audio>
      <button type="button" data-music-toggle
              class="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full shadow-lg"
              style="background: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['paper']) ?>"
              aria-label="<?= $de ? 'Musik' : 'Music' ?>">♪</button>
    <?php endif; ?>
  </div>
</div>
