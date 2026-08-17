<?php
/**
 * Referenzreportagen.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $stories
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('portfolio-hero', I18n::t('portfolio.title'), I18n::t('home.portfolioEyebrow'), I18n::t('portfolio.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('portfolio.title')],
  ]) ?>

  <div class="space-y-20">
    <?php foreach ($stories as $story) : ?>
      <?php
      // Eigene Bilder gehen vor - sonst zeigt die Uebersicht Platzhalter,
      // obwohl die Reportage schon Fotos hat.
      $seeds = ((array) ($story['uploads'] ?? [])) ?: ((array) ($story['seeds'] ?? []));
      ?>
      <?= Ui::revealOpen(60) ?>
        <a href="<?= e($p('/portfolio/' . (string) ($story['slug'] ?? ''))) ?>" class="group block">
          <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
              <?= Ui::photo((string) ($seeds[0] ?? ''), (string) ($story['couple'] ?? ''), '16/10', '', '(max-width: 640px) 100vw, 66vw', 1200, 750) ?>
            </div>
            <div class="hidden sm:block">
              <?= Ui::photo((string) ($seeds[1] ?? ($seeds[0] ?? '')), (string) ($story['couple'] ?? '') . ' 2', '4/5', '', '33vw', 600, 750) ?>
            </div>
          </div>
          <div class="mt-6 flex flex-wrap items-baseline justify-between gap-4">
            <h2 class="font-display text-3xl font-light text-ink transition-colors group-hover:text-gold sm:text-4xl"><?= e((string) ($story['couple'] ?? '')) ?></h2>
            <div class="text-[0.68rem] uppercase tracking-[0.18em] text-muted">
              <?= e(I18n::pick($story['venue'] ?? null, $locale)) ?> · <?= e(I18n::pick($story['month'] ?? null, $locale)) ?> · <?= e((string) ($story['guests'] ?? '')) ?> <?= e(I18n::t('portfolio.guests')) ?>
            </div>
          </div>
          <p class="mt-3 max-w-3xl text-[0.95rem] leading-relaxed text-muted"><?= e(I18n::pick($story['intro'] ?? null, $locale)) ?></p>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>
