<?php
/**
 * Kopfbereich. Der Zustand (gescrollt, Menü offen) sitzt in assets/app.js –
 * hier stehen nur die Klassen, die das Skript umschaltet.
 *
 * @var string $locale
 * @var string $path   aktueller Pfad, für die Sprachumschaltung
 */

use function Atelier\e;
use Atelier\I18n;

$p = static fn (string $to): string => I18n::path($to, $locale);

/*
 * Oben steht nur, wofuer jemand herkommt. Leistungen, Locations und Regionen
 * sind Seiten fuer die Suche, nicht fuer die Leiste: sie stehen in der
 * Fusszeile und sind von Startseite, Regionen-, Stadt- und Locationseiten
 * verlinkt, dazu vollstaendig in der sitemap.xml. Aus dem Index nimmt sie das
 * nicht – aus dem Weg des Besuchers schon.
 *
 * Die Galerie ist kein Schaufenster, sondern der Login des Paares. Wer sie
 * braucht, hat den Link in seiner Mail; im Menue verwechselt man sie mit dem
 * Portfolio. Sie steht deshalb ebenfalls unten.
 */
$links = [
    [$p('/portfolio'), I18n::t('nav.portfolio')],
    [$p('/preise'), I18n::t('nav.prices')],
    [$p('/ueber-mich'), I18n::t('nav.about')],
];

// Abgesetzt und in Gold: das ist das eigene Produkt, nicht eine Seite mehr.
$extra = [
    [$p('/einladung'), I18n::t('nav.invitation')],
];

// /de/preise → /tr/preise
$otherPath = static function (string $to) use ($path): string {
    $parts = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));
    if ($parts !== [] && I18n::isLocale($parts[0])) {
        $parts[0] = $to;
    } else {
        array_unshift($parts, $to);
    }
    return '/' . implode('/', $parts);
};
?>
<header id="site-header" class="fixed inset-x-0 top-0 z-50 transition-all duration-500 bg-transparent py-6">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">
    <a href="<?= e($p('')) ?>" class="group flex flex-col leading-none" aria-label="Atelier Lumière">
      <span class="font-display whitespace-nowrap text-xl font-light tracking-[0.14em] text-ink sm:text-2xl">ATELIER LUMIÈRE</span>
      <span class="mt-1 hidden text-[0.6rem] uppercase tracking-[0.32em] text-muted xl:block">Hochzeitsfotografie · Stuttgart</span>
    </a>

    <nav class="hidden items-center gap-5 lg:flex xl:gap-6">
      <?php foreach ($links as [$href, $label]) : ?>
        <a href="<?= e($href) ?>" class="link-underline whitespace-nowrap text-[0.78rem] uppercase tracking-[0.12em] transition-colors <?= $path === $href ? 'text-gold' : 'text-ink-soft hover:text-gold' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>

      <span class="h-4 w-px bg-sand-deep" aria-hidden="true"></span>

      <?php foreach ($extra as [$href, $label]) : ?>
        <a href="<?= e($href) ?>" class="link-underline whitespace-nowrap text-[0.78rem] uppercase tracking-[0.12em] transition-colors <?= $path === $href ? 'text-gold' : 'text-gold/80 hover:text-gold' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-3">
      <div class="hidden items-center gap-1 sm:flex">
        <?php foreach (I18n::LOCALES as $l) : ?>
          <a href="<?= e($otherPath($l)) ?>" hreflang="<?= $l === 'tr' ? 'tr-TR' : 'de-DE' ?>"
             class="px-1.5 py-1 text-[0.7rem] uppercase tracking-[0.18em] transition-colors <?= $l === $locale ? 'text-gold' : 'text-muted hover:text-ink' ?>"><?= strtoupper($l) ?></a>
        <?php endforeach; ?>
      </div>

      <a href="<?= e($p('/kontakt')) ?>" class="hidden whitespace-nowrap border border-ink px-5 py-2.5 text-[0.7rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream md:inline-block"><?= e(I18n::t('nav.cta')) ?></a>

      <button id="menu-toggle" type="button" aria-label="<?= e(I18n::t('nav.menu')) ?>" aria-expanded="false"
              class="relative z-50 flex h-10 w-10 flex-col items-center justify-center gap-[5px] lg:hidden">
        <span class="bar block h-px w-6 bg-ink transition-transform duration-300"></span>
        <span class="bar block h-px w-6 bg-ink transition-opacity duration-300"></span>
        <span class="bar block h-px w-6 bg-ink transition-transform duration-300"></span>
      </button>
    </div>
  </div>
</header>

<div id="menu-overlay" class="pointer-events-none fixed inset-0 z-40 bg-cream opacity-0 transition-all duration-500 lg:hidden">
  <div class="flex h-full flex-col justify-center px-8 pt-20">
    <nav class="flex flex-col gap-1">
      <?php foreach (array_merge($links, $extra) as $i => [$href, $label]) : ?>
        <a href="<?= e($href) ?>" style="transition-delay: <?= $i * 45 ?>ms"
           class="menu-item font-display translate-y-3 border-b border-sand-deep/40 py-4 text-2xl font-light text-ink opacity-0 transition-all duration-500"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="mt-8 flex items-center justify-between">
      <div class="flex gap-3">
        <?php foreach (I18n::LOCALES as $l) : ?>
          <a href="<?= e($otherPath($l)) ?>" class="text-xs uppercase tracking-[0.22em] <?= $l === $locale ? 'text-gold' : 'text-muted' ?>"><?= $l === 'de' ? 'Deutsch' : 'Türkçe' ?></a>
        <?php endforeach; ?>
      </div>
      <a href="<?= e($p('/kontakt')) ?>" class="bg-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-cream"><?= e(I18n::t('nav.cta')) ?></a>
    </div>
  </div>
</div>
