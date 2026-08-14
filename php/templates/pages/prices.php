<?php
/**
 * Preise: drei Pakete, Zusatzleistungen, häufige Fragen.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $packages
 * @var list<array<string,mixed>> $addons
 * @var list<array<string,mixed>> $faq
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('prices-hero', I18n::t('prices.title'), I18n::t('nav.prices'), I18n::t('prices.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('prices.title')],
  ]) ?>

  <div class="grid gap-6 lg:grid-cols-3">
    <?php foreach ($packages as $i => $package) : ?>
      <?php $featured = (bool) ($package['featured'] ?? false); ?>
      <?= Ui::revealOpen($i * 90) ?>
        <div class="flex h-full flex-col border p-8 transition-colors <?= $featured ? 'border-gold bg-sand/40' : 'border-sand-deep hover:border-muted' ?>">
          <?php if ($featured) : ?>
            <div class="mb-4 inline-block self-start bg-gold px-3 py-1 text-[0.6rem] uppercase tracking-[0.2em] text-white"><?= e(I18n::t('prices.popular')) ?></div>
          <?php endif; ?>

          <h2 class="font-display text-2xl font-light text-ink"><?= e(I18n::pick($package['name'] ?? null, $locale)) ?></h2>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-[0.7rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('prices.from')) ?></span>
            <span class="font-display text-4xl font-light text-ink"><?= e((string) ($package['price'] ?? '')) ?></span>
          </div>
          <div class="mt-2 text-[0.8rem] text-muted"><?= e(I18n::pick($package['hint'] ?? null, $locale)) ?></div>

          <?= Ui::bullets(I18n::pickList($package['features'] ?? null, $locale), 'prose-lux mt-7 flex-1') ?>

          <div class="mt-8"><?= Ui::btn($p('/kontakt'), I18n::t('prices.cta'), $featured ? 'solid' : 'outline', 'w-full') ?></div>
        </div>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>

  <?= Ui::revealOpen(120, 'mt-16') ?>
    <h2 class="font-display text-2xl font-light text-ink"><?= e(I18n::t('prices.addonsTitle')) ?></h2>
    <div class="mt-6 divide-y divide-sand-deep border-y border-sand-deep">
      <?php foreach ($addons as $addon) : ?>
        <div class="flex items-center justify-between gap-6 py-4">
          <span class="text-[0.95rem] text-ink-soft"><?= e(I18n::pick($addon['name'] ?? null, $locale)) ?></span>
          <span class="shrink-0 font-display text-lg text-gold"><?= e((string) ($addon['price'] ?? '')) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="mt-6 text-[0.82rem] leading-relaxed text-muted"><?= e(I18n::t('prices.note')) ?></p>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('sand') ?>
  <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
    <?= Ui::head(I18n::t('home.faqTitle'), 'FAQ') ?>
    <?= Ui::revealOpen(100) ?>
      <?= Ui::accordion(array_map(
          static fn (array $f): array => [
              'q' => I18n::pick($f['q'] ?? null, $locale),
              'a' => I18n::pick($f['a'] ?? null, $locale),
          ],
          $faq
      )) ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
