<?php
/**
 * Fußbereich mit Anschrift und den ersten acht Stadtseiten – die interne
 * Verlinkung, die den lokalen Suchergebnissen zugutekommt.
 *
 * @var string $locale
 */

use function Atelier\e;
use Atelier\Content;
use Atelier\I18n;

$p = static fn (string $to): string => I18n::path($to, $locale);
$c = Content::get('contact');
$cities = array_slice(Content::listed('cities'), 0, 8);

$navLinks = [
    [$p('/leistungen'), I18n::t('nav.services')],
    [$p('/portfolio'), I18n::t('nav.portfolio')],
    [$p('/preise'), I18n::t('nav.prices')],
    [$p('/hochzeitslocations'), I18n::t('nav.locations')],
    // Verteilerseite zu den zehn Stadtseiten – seit sie nicht mehr oben steht,
    // haengt ihre Erreichbarkeit an dieser Zeile.
    [$p('/regionen'), I18n::t('nav.cities')],
    [$p('/ratgeber'), I18n::t('blog.nav')],
    [$p('/galerie'), I18n::t('nav.gallery')],
    [$p('/einladung'), I18n::t('nav.invitation')],
    [$p('/designs'), 'Designs'],
    [$p('/kontakt'), I18n::t('nav.contact')],
];
?>
<footer class="relative mt-24 bg-ink text-cream">
  <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
    <div class="grid gap-12 md:grid-cols-4">
      <div class="md:col-span-1">
        <div class="font-display text-2xl font-light tracking-[0.16em]">ATELIER LUMIÈRE</div>
        <p class="mt-4 max-w-xs text-sm leading-relaxed text-cream/60"><?= e(I18n::t('footer.tagline')) ?></p>
        <div class="mt-6 flex gap-4 text-[0.7rem] uppercase tracking-[0.2em] text-cream/60">
          <a href="<?= e((string) ($c['instagram'] ?? '#')) ?>" class="hover:text-gold" rel="noopener noreferrer" target="_blank">Instagram</a>
          <a href="https://vimeo.com/" class="hover:text-gold" rel="noopener noreferrer" target="_blank">Vimeo</a>
        </div>
      </div>

      <div>
        <h3 class="text-[0.7rem] uppercase tracking-[0.24em] text-gold"><?= e(I18n::t('footer.nav')) ?></h3>
        <ul class="mt-5 space-y-2.5 text-sm text-cream/70">
          <?php foreach ($navLinks as [$href, $label]) : ?>
            <li><a href="<?= e($href) ?>" class="hover:text-gold"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3 class="text-[0.7rem] uppercase tracking-[0.24em] text-gold"><?= e(I18n::t('footer.regions')) ?></h3>
        <ul class="mt-5 grid grid-cols-1 gap-2.5 text-sm text-cream/70 sm:grid-cols-2 md:grid-cols-1">
          <?php foreach ($cities as $city) : ?>
            <li>
              <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''))) ?>" class="hover:text-gold"><?= e((string) ($city['name'] ?? '')) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3 class="text-[0.7rem] uppercase tracking-[0.24em] text-gold"><?= e(I18n::t('footer.contact')) ?></h3>
        <address class="mt-5 space-y-2.5 text-sm not-italic text-cream/70">
          <div><?= e((string) ($c['street'] ?? '')) ?></div>
          <div><?= e((string) ($c['zip'] ?? '')) ?> <?= e((string) ($c['city'] ?? '')) ?></div>
          <div class="pt-2">
            <a href="tel:<?= e((string) ($c['phone'] ?? '')) ?>" class="hover:text-gold"><?= e((string) ($c['phoneHuman'] ?? '')) ?></a>
          </div>
          <div>
            <a href="mailto:<?= e((string) ($c['email'] ?? '')) ?>" class="hover:text-gold"><?= e((string) ($c['email'] ?? '')) ?></a>
          </div>
          <div class="pt-2 text-cream/50"><?= e(I18n::pick($c['hours'] ?? null, $locale)) ?></div>
        </address>

        <ul class="mt-6 space-y-2 text-sm text-cream/70">
          <li><a href="<?= e($p('/impressum')) ?>" class="hover:text-gold">Impressum</a></li>
          <li><a href="<?= e($p('/datenschutz')) ?>" class="hover:text-gold">Datenschutz</a></li>
          <li>
            <button type="button" data-consent-open class="hover:text-gold">
              <?= e(\Atelier\I18n::t('cookie.settings')) ?>
            </button>
          </li>
          <li><a href="<?= e($p('/agb')) ?>" class="hover:text-gold">AGB</a></li>
        </ul>
      </div>
    </div>

    <div class="mt-14 flex flex-col gap-3 border-t border-cream/10 pt-7 text-xs text-cream/40 sm:flex-row sm:items-center sm:justify-between">
      <div>© <?= date('Y') ?> Atelier Lumière Hochzeitsfotografie. <?= e(I18n::t('footer.rights')) ?></div>
      <div><?= e(I18n::t('footer.demo')) ?></div>
    </div>
  </div>
</footer>
