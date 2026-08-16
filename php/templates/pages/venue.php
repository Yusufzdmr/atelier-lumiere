<?php
/**
 * Einzelne Location: Licht, Ablauf, Hausregeln – Wissen, das ein Paar
 * nirgends sonst findet. Genau deshalb rankt die Seite.
 *
 * @var string $locale
 * @var array<string,mixed> $venue
 * @var array<string,mixed>|null $city
 * @var list<array<string,mixed>> $related
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
$name = (string) ($venue['name'] ?? '');
$slug = (string) ($venue['slug'] ?? '');
$address = (string) ($venue['address'] ?? '');
$h1 = $de ? $name . ' – Hochzeitsfotograf & Videograf' : $name . ' – wedding photographer & videographer';
?>
<?= Ui::pageHero('venue-' . $slug, $h1, I18n::pick($venue['type'] ?? null, $locale), I18n::pick($venue['lead'] ?? null, $locale), 'lg') ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('venue.all'), 'href' => $p('/hochzeitslocations')],
      ['name' => $name],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
    <div>
      <?= Ui::revealOpen(0, 'prose-lux max-w-none') ?>
        <?php foreach (I18n::pickList($venue['body'] ?? null, $locale) as $paragraph) : ?>
          <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>

        <h3><?= e(I18n::t('venue.light')) ?></h3>
        <p><?= e(I18n::pick($venue['light'] ?? null, $locale)) ?></p>

        <h3><?= e(I18n::t('venue.spots')) ?></h3>
        <ul>
          <?php foreach (I18n::pickList($venue['spots'] ?? null, $locale) as $spot) : ?>
            <li><?= e($spot) ?></li>
          <?php endforeach; ?>
        </ul>

        <h3><?= e(I18n::t('venue.rules')) ?></h3>
        <ul>
          <?php foreach (I18n::pickList($venue['rules'] ?? null, $locale) as $rule) : ?>
            <li><?= e($rule) ?></li>
          <?php endforeach; ?>
        </ul>
      <?= Ui::revealClose() ?>

      <?php $timing = (array) ($venue['timing'] ?? []); ?>
      <?php if ($timing !== []) : ?>
        <?= Ui::revealOpen(100, 'mt-14') ?>
          <h3 class="font-display text-2xl font-light text-ink"><?= e(I18n::t('venue.timing')) ?></h3>
          <ol class="mt-6 border-l border-sand-deep">
            <?php foreach ($timing as $row) : ?>
              <li class="relative pb-7 pl-7">
                <span class="absolute -left-[5px] top-1.5 h-2.5 w-2.5 rounded-full bg-gold"></span>
                <div class="text-[0.7rem] uppercase tracking-[0.2em] text-gold"><?= e((string) ($row['time'] ?? '')) ?></div>
                <div class="mt-1.5 text-[0.95rem] leading-relaxed text-ink-soft"><?= e(I18n::pick($row['what'] ?? null, $locale)) ?></div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?= Ui::revealClose() ?>
      <?php endif; ?>

      <?= Ui::revealOpen(140, 'mt-10 grid grid-cols-2 gap-4') ?>
        <?= Ui::photo('venue-' . $slug . '-a', $name . ' 1', '4/5', '', '(max-width: 768px) 50vw, 25vw', 600, 750) ?>
        <?= Ui::photo('venue-' . $slug . '-b', $name . ' 2', '4/5', '', '(max-width: 768px) 50vw, 25vw', 600, 750) ?>
      <?= Ui::revealClose() ?>
    </div>

    <aside class="lg:sticky lg:top-28 lg:self-start">
      <?= Ui::revealOpen() ?>
        <div class="border border-sand-deep bg-sand/40 p-7">
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
              <dt class="text-muted"><?= e(I18n::t('venue.type')) ?></dt>
              <dd class="text-right text-ink"><?= e(I18n::pick($venue['type'] ?? null, $locale)) ?></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
              <dt class="text-muted"><?= e(I18n::t('venue.city')) ?></dt>
              <dd class="text-right text-ink"><?= e((string) ($venue['city'] ?? '')) ?></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
              <dt class="text-muted"><?= e(I18n::t('venue.capacity')) ?></dt>
              <dd class="text-right text-ink"><?= e(I18n::pick($venue['capacity'] ?? null, $locale)) ?></dd>
            </div>
            <div class="flex justify-between gap-4 pb-1">
              <dt class="text-muted"><?= e(I18n::t('venue.address')) ?></dt>
              <dd class="text-right text-ink"><?= e($address) ?></dd>
            </div>
          </dl>

          <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($address)) ?>"
             target="_blank" rel="noopener noreferrer"
             class="mt-6 block border border-ink px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">Google Maps</a>

          <?php if ($city !== null) : ?>
            <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''))) ?>"
               class="mt-3 block bg-ink px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
              <?= $de ? 'Hochzeitsfotograf ' . e((string) ($city['name'] ?? '')) : 'Wedding photographer ' . e((string) ($city['name'] ?? '')) ?>
            </a>
          <?php endif; ?>
        </div>
      <?= Ui::revealClose() ?>

      <?php if ($related !== []) : ?>
        <?= Ui::revealOpen(120, 'mt-8') ?>
          <h3 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('portfolio.title')) ?></h3>
          <ul class="mt-4 space-y-2.5">
            <?php foreach ($related as $story) : ?>
              <li>
                <a href="<?= e($p('/portfolio/' . (string) ($story['slug'] ?? ''))) ?>" class="link-underline text-[0.92rem] text-ink-soft hover:text-gold">
                  <?= e((string) ($story['couple'] ?? '')) ?> – <?= e(I18n::pick($story['month'] ?? null, $locale)) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?= Ui::revealClose() ?>
      <?php endif; ?>

      <p class="mt-8 text-[0.72rem] leading-relaxed text-muted"><?= e(I18n::t('venue.disclaimer')) ?></p>
    </aside>
  </div>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('ink') ?>
  <div class="mx-auto max-w-2xl text-center">
    <h2 class="headline text-3xl text-cream sm:text-4xl"><?= e(I18n::t('home.ctaTitle')) ?></h2>
    <p class="mt-5 text-cream/65"><?= e(I18n::t('home.ctaText')) ?></p>
    <div class="mt-9"><?= Ui::btn($p('/kontakt'), I18n::t('home.ctaButton'), 'light') ?></div>
  </div>
<?= Ui::sectionClose() ?>
