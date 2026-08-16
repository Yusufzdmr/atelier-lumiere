<?php
/**
 * Übersicht der Hochzeitslocations.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $venues
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
?>
<?= Ui::pageHero(
    'venues-index',
    $de ? 'Hochzeitslocations in der Region Stuttgart' : 'Wedding venues in the Stuttgart region',
    I18n::t('home.venuesEyebrow'),
    I18n::t('venue.lead')
) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('venue.all')],
  ]) ?>

  <div class="grid gap-8 md:grid-cols-2">
    <?php foreach ($venues as $i => $venue) : ?>
      <?php $slug = (string) ($venue['slug'] ?? ''); ?>
      <?= Ui::revealOpen($i * 70) ?>
        <a href="<?= e($p('/hochzeitslocations/' . $slug)) ?>" class="group block">
          <?= Ui::photo('venue-' . $slug, (string) ($venue['name'] ?? ''), '16/10', '', '(max-width: 768px) 100vw, 50vw', 900, 563) ?>
          <div class="mt-5 flex items-baseline justify-between gap-4">
            <h2 class="font-display text-2xl font-light text-ink transition-colors group-hover:text-gold"><?= e((string) ($venue['name'] ?? '')) ?></h2>
            <span class="shrink-0 text-[0.65rem] uppercase tracking-[0.18em] text-gold"><?= e(I18n::pick($venue['type'] ?? null, $locale)) ?></span>
          </div>
          <p class="mt-2 text-[0.9rem] leading-relaxed text-muted"><?= e(I18n::pick($venue['lead'] ?? null, $locale)) ?></p>
          <div class="mt-3 text-[0.7rem] uppercase tracking-[0.16em] text-muted">
            <?= e((string) ($venue['city'] ?? '')) ?> · <?= e(I18n::pick($venue['capacity'] ?? null, $locale)) ?>
          </div>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>

  <?= Ui::revealOpen(120, 'mt-16 border border-sand-deep bg-sand/40 p-8') ?>
    <p class="text-[0.9rem] leading-relaxed text-muted">
      <?= $de
          ? 'Eure Location ist nicht dabei? Kein Problem – wir fahren vor der Hochzeit einmal hin, prüfen Licht und Wege und stimmen den Zeitplan darauf ab. Das ist in allen Tagespaketen enthalten.'
          : 'Your venue is not on the list? No problem – we drive out once before the wedding, look at the light and the routes and build the timeline around them. That is part of every full-day package.' ?>
    </p>
    <a href="<?= e($p('/kontakt')) ?>" class="mt-6 inline-block bg-ink px-7 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold"><?= e(I18n::t('nav.cta')) ?></a>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>
