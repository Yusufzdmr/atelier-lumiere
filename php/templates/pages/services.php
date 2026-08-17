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
use Atelier\Video;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('services-hero', I18n::t('services.title'), I18n::t('home.servicesEyebrow'), I18n::t('services.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('services.title')],
  ]) ?>

  <?php /* Kapitelleiste: was es gibt, ohne dass jemand dafuer scrollen muss. */ ?>
  <nav class="sticky top-[4.5rem] z-20 -mx-5 mb-10 border-y border-sand-deep bg-cream/90 px-5 backdrop-blur sm:mx-0 sm:px-0">
    <ul class="flex gap-6 overflow-x-auto py-3.5">
      <?php foreach ($services as $i => $service) : ?>
        <li class="shrink-0">
          <a href="#<?= e((string) ($service['slug'] ?? '')) ?>" class="link-underline text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold">
            <span class="text-gold">0<?= $i + 1 ?></span> <?= e(I18n::pick($service['title'] ?? null, $locale)) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="space-y-16 sm:space-y-20">
    <?php foreach ($services as $i => $service) : ?>
      <?php
      $slug = (string) ($service['slug'] ?? '');
      // Beispiele: erst die eigenen Aufnahmen, sonst passende Platzhalter.
      // Ein Abschnitt, der nur erzaehlt, beantwortet nicht die eine Frage,
      // die der Gast wirklich hat: wie sieht das denn aus?
      $shots = array_values(array_filter((array) ($service['uploads'] ?? []), 'is_string'));
      if ($shots === []) {
          $shots = ["svc-$slug-0", "svc-$slug-1", "svc-$slug-2", "svc-$slug-3"];
      }
      $shots = array_slice($shots, 0, 4);
      $film = (string) ($service['videoUrl'] ?? '');
      ?>
      <div id="<?= e($slug) ?>" class="scroll-mt-28">
        <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-20 <?= $i % 2 ? 'lg:[&>*:first-child]:order-2' : '' ?>">
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

        <?php /* Beispielstrecke */ ?>
        <div class="mt-10">
          <?= Ui::revealOpen(0) ?>
            <h3 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('services.examples')) ?></h3>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
              <?php foreach ($shots as $k => $shot) : ?>
                <?= Ui::photo(
                    (string) $shot,
                    I18n::pick($service['title'] ?? null, $locale) . ' ' . ($k + 1),
                    '4/5',
                    '',
                    '(max-width: 640px) 50vw, 25vw',
                    600,
                    750
                ) ?>
              <?php endforeach; ?>
            </div>
          <?= Ui::revealClose() ?>
        </div>

        <?php /* Beispielfilm - nur wenn einer hinterlegt ist */ ?>
        <?php if ($film !== '' && Video::isSupported($film)) : ?>
          <div class="mt-10">
            <?= Ui::revealOpen(0) ?>
              <h3 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('services.exampleFilm')) ?></h3>
              <div class="mt-5">
                <?= Video::embedBox(
                    $film,
                    I18n::pick($service['title'] ?? null, $locale) . ' - ' . I18n::t('services.exampleFilm'),
                    (string) ($shots[0] ?? '')
                ) ?>
              </div>
            <?= Ui::revealClose() ?>
          </div>
        <?php endif; ?>
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
