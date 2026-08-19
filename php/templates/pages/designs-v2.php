<?php
/**
 * Katalog der zweiten Fassung.
 *
 * @var list<array<string,mixed>> $designs
 * @var string $styles
 * @var array<string,string> $values
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;
?>
<style><?= $styles ?></style>

<!-- Der Kopf ist fixiert und 94 px hoch; py-16 sind 64 und die
     Ueberschrift verschwindet darunter. Die anderen Seiten holen den
     Abstand aus Ui::pageHero, diese hat keinen. -->
<section class="mx-auto max-w-7xl px-6 pb-24 pt-32">
  <h1 class="font-display text-3xl font-light text-ink">
    <?= $locale === 'de' ? 'Designs (zweite Fassung)' : 'Designs (second version)' ?>
  </h1>
  <p class="mt-2 max-w-xl text-sm text-muted">
    <?= $locale === 'de'
      ? 'Dieselben Vorlagen, aber vollständig aus Daten gebaut. Steht zum Vergleich neben der ersten Fassung.'
      : 'The same templates, built entirely from data. Here for comparison beside the first version.' ?>
  </p>

  <?php if ($designs === []): ?>
    <p class="mt-10 text-sm text-muted">
      <?= $locale === 'de' ? 'Noch kein Design angelegt.' : 'No design yet.' ?>
      <code>php bin/seed-designs.php</code>
    </p>
  <?php endif; ?>

  <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design): ?>
      <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], $locale)) ?>" class="group block">
        <div class="d-<?= e($design['id']) ?> relative overflow-hidden"
             style="aspect-ratio: <?= str_replace(':', ' / ', (string) $design['canvas']['ratio']) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <?= Design::html($design, $values, $locale) ?>
        </div>
        <p class="mt-3 font-display text-lg font-light text-ink group-hover:text-gold">
          <?= e($design['name'][$locale] ?? $design['name']['de']) ?>
        </p>
        <p class="text-xs uppercase tracking-[0.16em] text-muted">
          <?= e($design['category']) ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
