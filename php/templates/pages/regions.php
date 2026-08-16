<?php
/**
 * Übersicht aller Stadtseiten – der Einstieg in die lokale Verlinkung.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $cities
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
?>
<?= Ui::pageHero(
    'regions-index',
    $de ? 'Wo wir fotografieren' : 'Where we photograph',
    I18n::t('home.citiesEyebrow'),
    I18n::t('home.citiesText')
) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('city.allCities')],
  ]) ?>

  <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($cities as $i => $city) : ?>
      <?php $slug = (string) ($city['slug'] ?? ''); ?>
      <?= Ui::revealOpen($i * 50) ?>
        <a href="<?= e($p('/hochzeitsfotograf/' . $slug)) ?>" class="group block h-full">
          <?= Ui::photo('city-' . $slug, (string) ($city['name'] ?? ''), '4/3', '', '(max-width: 640px) 100vw, 33vw', 800, 600) ?>
          <h2 class="font-display mt-4 text-xl font-normal text-ink transition-colors group-hover:text-gold">
            <?= $de ? 'Hochzeitsfotograf ' : 'Wedding photographer ' ?><?= e((string) ($city['name'] ?? '')) ?>
          </h2>
          <p class="mt-2 text-[0.85rem] leading-relaxed text-muted"><?= e(I18n::pick($city['lead'] ?? null, $locale)) ?></p>
          <div class="mt-3 text-[0.68rem] uppercase tracking-[0.16em] text-gold"><?= e(I18n::pick($city['drive'] ?? null, $locale)) ?></div>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>
