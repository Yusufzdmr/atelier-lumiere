<?php
/**
 * Über mich.
 *
 * @var string $locale
 * @var array<string,mixed> $about
 * @var array<string,mixed> $stats
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$name = (string) ($about['name'] ?? '');
?>
<?= Ui::pageHero('about-hero', $name, I18n::t('about.title'), I18n::pick($about['lead'] ?? null, $locale), 'lg') ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('about.title')],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-20">
    <?= Ui::revealOpen(0, 'prose-lux max-w-none') ?>
      <?php foreach (I18n::pickList($about['body'] ?? null, $locale) as $paragraph) : ?>
        <p><?= e($paragraph) ?></p>
      <?php endforeach; ?>
    <?= Ui::revealClose() ?>

    <?= Ui::revealOpen(120, '', true) ?>
      <?= Ui::photo('about-portrait', $name, '4/5', '', '(max-width: 1024px) 100vw, 45vw', 800, 1000) ?>
    <?= Ui::revealClose() ?>
  </div>

  <?= Ui::revealOpen(100, 'mt-16 grid grid-cols-2 gap-8 border-y border-sand-deep py-10 sm:grid-cols-4') ?>
    <?= Ui::stat((string) ($stats['weddings'] ?? ''), I18n::t('home.statsWeddings')) ?>
    <?= Ui::stat((string) ($stats['years'] ?? ''), I18n::t('home.statsYears')) ?>
    <?= Ui::stat((string) ($stats['delivery'] ?? ''), I18n::t('home.statsDelivery')) ?>
    <?= Ui::stat((string) ($stats['rating'] ?? ''), I18n::t('home.statsRating')) ?>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('sand') ?>
  <h2 class="headline text-3xl sm:text-4xl"><?= e(I18n::pick($about['valuesTitle'] ?? null, $locale)) ?></h2>
  <div class="mt-12 grid gap-10 sm:grid-cols-2">
    <?php foreach ((array) ($about['values'] ?? []) as $i => $value) : ?>
      <?= Ui::revealOpen($i * 90) ?>
        <h3 class="font-display text-xl font-normal text-ink"><?= e(I18n::pick($value['t'] ?? null, $locale)) ?></h3>
        <p class="mt-2.5 text-[0.92rem] leading-relaxed text-muted"><?= e(I18n::pick($value['d'] ?? null, $locale)) ?></p>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>

  <?= Ui::revealOpen(150, 'mt-16') ?>
    <h2 class="font-display text-2xl font-light text-ink"><?= e(I18n::pick($about['gearTitle'] ?? null, $locale)) ?></h2>
    <?= Ui::bullets(I18n::pickList($about['gear'] ?? null, $locale), 'prose-lux mt-5 max-w-2xl') ?>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('ink') ?>
  <div class="mx-auto max-w-2xl text-center">
    <h2 class="headline text-3xl text-cream sm:text-4xl"><?= e(I18n::t('home.ctaTitle')) ?></h2>
    <p class="mt-5 text-cream/65"><?= e(I18n::t('home.ctaText')) ?></p>
    <div class="mt-9"><?= Ui::btn($p('/kontakt'), I18n::t('home.ctaButton'), 'light') ?></div>
  </div>
<?= Ui::sectionClose() ?>
