<?php
/**
 * Stadtseite – der eigentliche Motor der lokalen Sichtbarkeit.
 * Jede Stadt hat eigenen Text; nichts ist hier automatisch erzeugt.
 *
 * @var string $locale
 * @var array<string,mixed> $city
 * @var list<array<string,mixed>> $cityVenues
 * @var list<array<string,mixed>> $neighbours
 * @var list<array<string,mixed>> $cityPosts
 * @var list<array<string,mixed>> $packages
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;
use Atelier\View;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
$name = (string) ($city['name'] ?? '');
$slug = (string) ($city['slug'] ?? '');
$h1 = $de ? 'Hochzeitsfotograf ' . $name : 'Wedding photographer ' . $name;
?>
<?= Ui::pageHero('city-' . $slug, $h1, I18n::t('city.metaTitleSuffix'), I18n::pick($city['lead'] ?? null, $locale), 'lg') ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('city.allCities'), 'href' => $p('/regionen')],
      ['name' => $name],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
    <div>
      <?= Ui::revealOpen(0, 'prose-lux max-w-none') ?>
        <h2><?= $de ? 'Hochzeitsfotografie in ' . e($name) : 'Wedding photography in ' . e($name) ?></h2>
        <?php foreach (I18n::pickList($city['body'] ?? null, $locale) as $paragraph) : ?>
          <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>

        <h3><?= e(I18n::t('city.spots')) ?></h3>
        <ul>
          <?php foreach ((array) ($city['spots'] ?? []) as $spot) : ?>
            <li><strong><?= e((string) ($spot['name'] ?? '')) ?></strong> – <?= e(I18n::pick($spot['note'] ?? null, $locale)) ?></li>
          <?php endforeach; ?>
        </ul>
      <?= Ui::revealClose() ?>

      <?= Ui::revealOpen(120, 'mt-12 grid grid-cols-2 gap-4') ?>
        <?= Ui::photo('city-' . $slug . '-a', $h1 . ' 1', '3/4', '', '(max-width: 768px) 50vw, 25vw', 600, 800) ?>
        <?= Ui::photo('city-' . $slug . '-b', $h1 . ' 2', '3/4', '', '(max-width: 768px) 50vw, 25vw', 600, 800) ?>
      <?= Ui::revealClose() ?>
    </div>

    <aside class="lg:sticky lg:top-28 lg:self-start">
      <?= Ui::revealOpen() ?>
        <div class="border border-sand-deep bg-sand/40 p-7">
          <div class="eyebrow"><?= e(I18n::pick($city['kreis'] ?? null, $locale)) ?></div>
          <dl class="mt-5 space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
              <dt class="text-muted"><?= e(I18n::t('city.drive')) ?></dt>
              <dd class="text-ink"><?= e(I18n::pick($city['drive'] ?? null, $locale)) ?></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
              <dt class="text-muted"><?= e(I18n::t('city.district')) ?></dt>
              <dd class="text-right text-ink"><?= e(I18n::pick($city['kreis'] ?? null, $locale)) ?></dd>
            </div>
            <?php foreach ($packages as $package) : ?>
              <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                <dt class="text-muted"><?= e(I18n::pick($package['name'] ?? null, $locale)) ?></dt>
                <dd class="text-ink"><?= e((string) ($package['price'] ?? '')) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          <div class="mt-7"><?= Ui::btn($p('/kontakt'), I18n::t('nav.cta'), 'solid', 'w-full') ?></div>
        </div>
      <?= Ui::revealClose() ?>

      <?php if ($cityPosts !== []) : ?>
        <?= Ui::revealOpen(110, 'mt-8') ?>
          <h3 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('blog.nav')) ?></h3>
          <ul class="mt-4 space-y-2.5">
            <?php foreach (array_slice($cityPosts, 0, 3) as $post) : ?>
              <li>
                <a href="<?= e($p('/ratgeber/' . (string) ($post['slug'] ?? ''))) ?>" class="link-underline text-[0.92rem] leading-snug text-ink-soft hover:text-gold"><?= e(I18n::pick($post['title'] ?? null, $locale)) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?= Ui::revealClose() ?>
      <?php endif; ?>

      <?php if ($cityVenues !== []) : ?>
        <?= Ui::revealOpen(120, 'mt-8') ?>
          <h3 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('city.venues')) ?></h3>
          <ul class="mt-4 space-y-2.5">
            <?php foreach ($cityVenues as $venue) : ?>
              <li>
                <a href="<?= e($p('/hochzeitslocations/' . (string) ($venue['slug'] ?? ''))) ?>" class="link-underline text-[0.92rem] text-ink-soft hover:text-gold"><?= e((string) ($venue['name'] ?? '')) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?= Ui::revealClose() ?>
      <?php endif; ?>
    </aside>
  </div>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('sand') ?>
  <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
    <?= Ui::head(I18n::t('city.faq'), 'FAQ') ?>
    <?= Ui::revealOpen(100) ?>
      <?= Ui::accordion(array_map(
          static fn (array $f): array => [
              'q' => I18n::pick($f['q'] ?? null, $locale),
              'a' => I18n::pick($f['a'] ?? null, $locale),
          ],
          (array) ($city['faq'] ?? [])
      )) ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>

<?php if ($neighbours !== []) : ?>
  <?= Ui::sectionOpen() ?>
    <?= Ui::head(I18n::t('city.allCities'), I18n::t('city.neighbours')) ?>
    <?= Ui::revealOpen(100) ?>
      <div class="mt-8 flex flex-wrap gap-2.5">
        <?php foreach ($neighbours as $neighbour) : ?>
          <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($neighbour['slug'] ?? ''))) ?>"
             class="border border-sand-deep px-5 py-2.5 text-[0.8rem] text-ink-soft transition-colors hover:border-gold hover:text-gold">
            <?= $de ? 'Hochzeitsfotograf ' : 'Wedding photographer ' ?><?= e((string) ($neighbour['name'] ?? '')) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?= Ui::revealClose() ?>
  <?= Ui::sectionClose() ?>
<?php endif; ?>

<?= Ui::sectionOpen('ink', '', 'anfrage') ?>
  <div class="grid gap-14 lg:grid-cols-2 lg:gap-20">
    <div>
      <?= Ui::head(I18n::t('city.cta') . ' ' . $name, I18n::t('nav.contact'), I18n::t('city.ctaText'), 'left', 'light') ?>
    </div>
    <?= Ui::revealOpen(120, 'bg-cream p-8 sm:p-10') ?>
      <?= View::partial('partials/contact-form', ['locale' => $locale, 'preset' => $name, 'csrf' => $csrf]) ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
