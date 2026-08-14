<?php
/**
 * Startseite – Reihenfolge und Inhalte wie in app/[locale]/page.tsx.
 *
 * @var string $locale
 * @var array<string,mixed> $hero
 * @var array<string,mixed> $stats
 * @var list<array<string,mixed>> $services
 * @var list<array<string,mixed>> $process
 * @var list<array<string,mixed>> $testimonials
 * @var list<array<string,mixed>> $faq
 * @var list<array<string,mixed>> $cities
 * @var list<array<string,mixed>> $venues
 * @var list<array<string,mixed>> $stories
 */

use function Atelier\e;
use Atelier\Content;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$about = Content::get('about');
$values = array_slice((array) ($about['values'] ?? []), 0, 4);
?>

<!-- ---------------- Hero ---------------- -->
<section class="relative h-[100svh] min-h-[600px] w-full overflow-hidden">
  <div class="absolute inset-0 animate-kenburns">
    <img src="<?= e(Images::img('lumiere-hero-main', 1920, 1280)) ?>"
         alt="<?= e(Images::alt('lumiere-hero-main', 'Hochzeitsfotografie Stuttgart')) ?>"
         class="h-full w-full object-cover" fetchpriority="high" decoding="async">
  </div>
  <div class="absolute inset-0 bg-gradient-to-t from-ink/85 via-ink/35 to-ink/45"></div>

  <div class="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-end px-5 pb-28 sm:px-8 sm:pb-32">
    <div class="anim-up eyebrow text-gold-soft" style="animation-delay: .2s"><?= e(I18n::pick($hero['eyebrow'] ?? null, $locale)) ?></div>
    <h1 class="anim-up headline mt-6 max-w-3xl text-5xl text-cream sm:text-6xl md:text-7xl"
        style="white-space: pre-line; animation-delay: .4s"><?= e(I18n::pick($hero['title'] ?? null, $locale)) ?></h1>
    <p class="anim-up mt-7 max-w-xl text-[1rem] leading-relaxed text-cream/75" style="animation-delay: .65s"><?= e(I18n::pick($hero['text'] ?? null, $locale)) ?></p>
    <div class="anim-up mt-10 flex flex-wrap gap-3" style="animation-delay: .85s">
      <?= Ui::btn($p('/kontakt'), I18n::t('home.heroCta'), 'solid', '!bg-cream !text-ink hover:!bg-gold hover:!text-cream') ?>
      <?= Ui::btn($p('/portfolio'), I18n::t('home.heroCta2'), 'light') ?>
    </div>
  </div>

  <div class="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-center">
    <div class="mx-auto h-12 w-px animate-pulse bg-cream/40"></div>
    <span class="mt-2 block text-[0.58rem] uppercase tracking-[0.3em] text-cream/50"><?= e(I18n::t('home.scroll')) ?></span>
  </div>
</section>

<!-- ---------------- Intro + Zahlen ---------------- -->
<?= Ui::sectionOpen() ?>
  <div class="grid gap-14 lg:grid-cols-[1.1fr_1fr] lg:gap-20">
    <div>
      <?= Ui::head(I18n::t('home.introTitle'), I18n::t('home.introEyebrow'), I18n::t('home.introText')) ?>

      <?= Ui::revealOpen(150) ?>
        <div class="mt-12 grid grid-cols-2 gap-8 sm:grid-cols-4">
          <?= Ui::stat((string) ($stats['weddings'] ?? ''), I18n::t('home.statsWeddings')) ?>
          <?= Ui::stat((string) ($stats['years'] ?? ''), I18n::t('home.statsYears')) ?>
          <?= Ui::stat((string) ($stats['delivery'] ?? ''), I18n::t('home.statsDelivery')) ?>
          <?= Ui::stat((string) ($stats['rating'] ?? ''), I18n::t('home.statsRating')) ?>
        </div>
      <?= Ui::revealClose() ?>

      <?php if ($values !== []) : ?>
        <?= Ui::revealOpen(220, 'mt-12 border-t border-sand-deep pt-11') ?>
          <h3 class="eyebrow"><?= e(I18n::pick($about['valuesTitle'] ?? null, $locale)) ?></h3>
          <div class="mt-7 grid gap-x-10 gap-y-7 sm:grid-cols-2">
            <?php foreach ($values as $value) : ?>
              <div class="relative pl-5">
                <span class="absolute left-0 top-[0.62em] h-px w-3 bg-gold"></span>
                <h4 class="font-display text-[1.06rem] font-normal leading-snug text-ink"><?= e(I18n::pick($value['t'] ?? null, $locale)) ?></h4>
                <p class="mt-1.5 text-[0.86rem] leading-relaxed text-muted"><?= e(I18n::pick($value['d'] ?? null, $locale)) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
          <a href="<?= e($p('/ueber-mich')) ?>" class="link-underline mt-9 inline-block text-[0.68rem] uppercase tracking-[0.2em] text-ink"><?= e(I18n::t('nav.about')) ?> →</a>
        <?= Ui::revealClose() ?>
      <?php endif; ?>
    </div>

    <?= Ui::revealOpen(200, '', true) ?>
      <?= Ui::photo('lumiere-intro', 'Brautpaar Stuttgart', '4/5', '', '(max-width: 1024px) 100vw, 45vw', 900, 1125) ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Leistungen ---------------- -->
<?= Ui::sectionOpen('sand') ?>
  <?= Ui::head(I18n::t('home.servicesTitle'), I18n::t('home.servicesEyebrow')) ?>
  <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($services as $i => $service) : ?>
      <?= Ui::revealOpen($i * 90) ?>
        <a href="<?= e($p('/leistungen#' . (string) ($service['slug'] ?? ''))) ?>" class="group block">
          <?= Ui::photo((string) ($service['seed'] ?? ''), I18n::pick($service['title'] ?? null, $locale), '4/5', '', '(max-width: 640px) 100vw, 25vw', 640, 800) ?>
          <h3 class="font-display mt-5 text-xl font-normal text-ink transition-colors group-hover:text-gold"><?= e(I18n::pick($service['title'] ?? null, $locale)) ?></h3>
          <p class="mt-2 text-[0.88rem] leading-relaxed text-muted"><?= e(I18n::pick($service['short'] ?? null, $locale)) ?></p>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Portfolio ---------------- -->
<?= Ui::sectionOpen() ?>
  <div class="flex flex-wrap items-end justify-between gap-6">
    <?= Ui::head(I18n::t('home.portfolioTitle'), I18n::t('home.portfolioEyebrow')) ?>
    <?= Ui::revealOpen() ?>
      <a href="<?= e($p('/portfolio')) ?>" class="link-underline text-[0.72rem] uppercase tracking-[0.2em] text-gold"><?= e(I18n::t('home.portfolioAll')) ?> →</a>
    <?= Ui::revealClose() ?>
  </div>

  <div class="mt-14 grid gap-8 md:grid-cols-2">
    <?php foreach ($stories as $i => $story) : ?>
      <?php $wide = $i % 3 === 0; ?>
      <?= Ui::revealOpen($i * 80, $wide ? 'md:col-span-2' : '') ?>
        <a href="<?= e($p('/portfolio/' . (string) ($story['slug'] ?? ''))) ?>" class="group block">
          <?= Ui::photo(
              (string) (($story['seeds'] ?? [])[0] ?? ''),
              (string) ($story['couple'] ?? '') . ' – ' . I18n::pick($story['venue'] ?? null, $locale),
              $wide ? '16/9' : '4/3',
              '',
              '(max-width: 768px) 100vw, 50vw',
              1200,
              $wide ? 675 : 900
          ) ?>
          <div class="mt-5 flex flex-wrap items-baseline justify-between gap-3">
            <h3 class="font-display text-2xl font-light text-ink transition-colors group-hover:text-gold"><?= e((string) ($story['couple'] ?? '')) ?></h3>
            <span class="text-[0.68rem] uppercase tracking-[0.18em] text-muted">
              <?= e(I18n::pick($story['venue'] ?? null, $locale)) ?> · <?= e((string) ($story['guests'] ?? '')) ?> <?= e(I18n::t('portfolio.guests')) ?>
            </span>
          </div>
          <p class="mt-2 max-w-2xl text-[0.9rem] leading-relaxed text-muted"><?= e(I18n::pick($story['intro'] ?? null, $locale)) ?></p>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Ablauf ---------------- -->
<?= Ui::sectionOpen('sand') ?>
  <?= Ui::head(I18n::t('home.processTitle'), I18n::t('home.processEyebrow')) ?>
  <div class="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($process as $i => $step) : ?>
      <?= Ui::revealOpen($i * 100) ?>
        <div class="font-display text-5xl font-light text-gold/40"><?= e((string) ($step['step'] ?? '')) ?></div>
        <h3 class="font-display mt-4 text-xl font-normal text-ink"><?= e(I18n::pick($step['title'] ?? null, $locale)) ?></h3>
        <p class="mt-3 text-[0.88rem] leading-relaxed text-muted"><?= e(I18n::pick($step['text'] ?? null, $locale)) ?></p>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Galerie & Einladung ---------------- -->
<?= Ui::sectionOpen('ink') ?>
  <?= Ui::head(I18n::t('home.toolsTitle'), I18n::t('home.toolsEyebrow'), '', 'center', 'light') ?>
  <div class="mt-16 grid gap-10 md:grid-cols-2">
    <?php
    $tools = [
        ['lumiere-tool-gallery', 'home.toolGalleryTitle', 'home.toolGalleryText', 'home.toolGalleryCta', '/galerie', 0],
        ['lumiere-tool-invite', 'home.toolInviteTitle', 'home.toolInviteText', 'home.toolInviteCta', '/einladung', 120],
    ];
    foreach ($tools as [$seed, $titleKey, $textKey, $ctaKey, $href, $delay]) :
    ?>
      <?= Ui::revealOpen($delay) ?>
        <div class="group h-full border border-cream/15 p-8 transition-colors hover:border-gold/50 sm:p-10">
          <?= Ui::photo($seed, I18n::t($titleKey), '16/10', '', '(max-width: 768px) 100vw, 45vw', 900, 563) ?>
          <h3 class="font-display mt-7 text-2xl font-light text-cream"><?= e(I18n::t($titleKey)) ?></h3>
          <p class="mt-3 text-[0.9rem] leading-relaxed text-cream/60"><?= e(I18n::t($textKey)) ?></p>
          <a href="<?= e($p($href)) ?>" class="mt-7 inline-block border border-cream/40 px-6 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-cream hover:text-ink"><?= e(I18n::t($ctaKey)) ?></a>
        </div>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Locations ---------------- -->
<?= Ui::sectionOpen() ?>
  <?= Ui::head(I18n::t('home.venuesTitle'), I18n::t('home.venuesEyebrow'), I18n::t('home.venuesText')) ?>
  <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($venues as $i => $venue) : ?>
      <?= Ui::revealOpen($i * 70) ?>
        <a href="<?= e($p('/hochzeitslocations/' . (string) ($venue['slug'] ?? ''))) ?>"
           class="group flex h-full flex-col justify-between border border-sand-deep p-6 transition-colors hover:border-gold">
          <div>
            <div class="text-[0.62rem] uppercase tracking-[0.2em] text-gold"><?= e(I18n::pick($venue['type'] ?? null, $locale)) ?></div>
            <h3 class="font-display mt-3 text-xl font-normal text-ink"><?= e((string) ($venue['name'] ?? '')) ?></h3>
            <p class="mt-2 text-[0.85rem] leading-relaxed text-muted"><?= e((string) ($venue['city'] ?? '')) ?></p>
          </div>
          <span class="mt-6 text-[0.68rem] uppercase tracking-[0.18em] text-muted transition-colors group-hover:text-gold"><?= e(I18n::t('common.readMore')) ?> →</span>
        </a>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
  <?= Ui::revealOpen(150, 'mt-8') ?>
    <a href="<?= e($p('/hochzeitslocations')) ?>" class="link-underline text-[0.72rem] uppercase tracking-[0.2em] text-gold"><?= e(I18n::t('venue.all')) ?> →</a>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>

<!-- ---------------- Regionen ---------------- -->
<?= Ui::sectionOpen('sand') ?>
  <?= Ui::head(I18n::t('home.citiesTitle'), I18n::t('home.citiesEyebrow'), I18n::t('home.citiesText')) ?>
  <?= Ui::revealOpen(120) ?>
    <div class="mt-10 flex flex-wrap gap-2.5">
      <?php foreach ($cities as $city) : ?>
        <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''))) ?>"
           class="border border-sand-deep bg-cream px-5 py-2.5 text-[0.8rem] text-ink-soft transition-colors hover:border-gold hover:text-gold"><?= e((string) ($city['name'] ?? '')) ?></a>
      <?php endforeach; ?>
    </div>
  <?= Ui::revealClose() ?>
<?= Ui::sectionClose() ?>

<!-- ---------------- Stimmen ---------------- -->
<?= Ui::sectionOpen() ?>
  <?= Ui::head(I18n::t('home.testimonialsTitle'), I18n::t('home.testimonialsEyebrow'), '', 'center') ?>
  <div class="mt-14 grid gap-10 md:grid-cols-3">
    <?php foreach ($testimonials as $i => $voice) : ?>
      <?= Ui::revealOpen($i * 110) ?>
        <div class="flex h-full flex-col">
          <div class="font-display text-4xl leading-none text-gold/50">&ldquo;</div>
          <p class="font-display mt-3 flex-1 text-lg font-light leading-relaxed text-ink"><?= e(I18n::pick($voice['text'] ?? null, $locale)) ?></p>
          <div class="mt-6 text-[0.68rem] uppercase tracking-[0.18em] text-muted">
            <?= e((string) ($voice['name'] ?? '')) ?> · <?= e(I18n::pick($voice['city'] ?? null, $locale)) ?>
          </div>
        </div>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>
<?= Ui::sectionClose() ?>

<!-- ---------------- Fragen ---------------- -->
<?= Ui::sectionOpen('sand') ?>
  <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
    <?= Ui::head(I18n::t('home.faqTitle'), I18n::t('home.faqEyebrow')) ?>
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

<!-- ---------------- Kontakt ---------------- -->
<?= Ui::sectionOpen('ink', 'relative overflow-hidden') ?>
  <div class="relative z-10 mx-auto max-w-2xl text-center">
    <?= Ui::head(I18n::t('home.ctaTitle'), 'Kontakt', I18n::t('home.ctaText'), 'center', 'light') ?>
    <?= Ui::revealOpen(150, 'mt-10') ?>
      <?= Ui::btn($p('/kontakt'), I18n::t('home.ctaButton'), 'light') ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
