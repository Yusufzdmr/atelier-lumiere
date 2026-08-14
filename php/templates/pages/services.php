<?php
/**
 * Leistungen – die Blöcke wechseln die Seite, damit die Liste nicht monoton wirkt.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $services
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('services-hero', I18n::t('services.title'), I18n::t('home.servicesEyebrow'), I18n::t('services.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('services.title')],
  ]) ?>

  <div class="space-y-24">
    <?php foreach ($services as $i => $service) : ?>
      <div id="<?= e((string) ($service['slug'] ?? '')) ?>" class="scroll-mt-28">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20 <?= $i % 2 ? 'lg:[&>*:first-child]:order-2' : '' ?>">
          <?= Ui::revealOpen(0, '', true) ?>
            <?= Ui::photo(
                (string) ($service['seed'] ?? ''),
                I18n::pick($service['title'] ?? null, $locale),
                '4/5',
                '',
                '(max-width: 1024px) 100vw, 50vw',
                900,
                1125
            ) ?>
          <?= Ui::revealClose() ?>

          <?= Ui::revealOpen(120) ?>
            <div class="eyebrow">0<?= $i + 1 ?></div>
            <h2 class="headline mt-4 text-3xl sm:text-4xl"><?= e(I18n::pick($service['title'] ?? null, $locale)) ?></h2>
            <?= Ui::prose(I18n::pickList($service['body'] ?? null, $locale)) ?>
            <h3 class="mt-8 text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('services.includes')) ?></h3>
            <?= Ui::bullets(I18n::pickList($service['bullets'] ?? null, $locale)) ?>
            <div class="mt-8"><?= Ui::btn($p('/preise'), I18n::t('nav.prices'), 'outline') ?></div>
          <?= Ui::revealClose() ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('ink') ?>
  <div class="mx-auto max-w-2xl text-center">
    <h2 class="headline text-3xl text-cream sm:text-4xl"><?= e(I18n::t('home.ctaTitle')) ?></h2>
    <p class="mt-5 text-cream/65"><?= e(I18n::t('home.ctaText')) ?></p>
    <div class="mt-9"><?= Ui::btn($p('/kontakt'), I18n::t('home.ctaButton'), 'light') ?></div>
  </div>
<?= Ui::sectionClose() ?>
