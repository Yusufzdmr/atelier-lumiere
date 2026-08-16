<?php
/**
 * Eine Hochzeitsreportage.
 *
 * @var string $locale
 * @var array<string,mixed> $story
 * @var array<string,mixed>|null $venue
 * @var array<string,mixed>|null $city
 * @var list<array<string,mixed>> $others
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;
use Atelier\Video;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
$seeds = (array) ($story['seeds'] ?? []);
$uploads = (array) ($story['uploads'] ?? []);
$couple = (string) ($story['couple'] ?? '');
$gallery = $uploads !== [] ? $uploads : $seeds;
?>
<?= Ui::pageHero(
    (string) ($seeds[0] ?? ''),
    $couple,
    I18n::pick($story['venue'] ?? null, $locale) . ' · ' . I18n::pick($story['month'] ?? null, $locale),
    I18n::pick($story['intro'] ?? null, $locale),
    'lg'
) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('portfolio.title'), 'href' => $p('/portfolio')],
      ['name' => $couple],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-20">
    <?= Ui::revealOpen(0, 'prose-lux max-w-none') ?>
      <?php foreach (I18n::pickList($story['body'] ?? null, $locale) as $paragraph) : ?>
        <p><?= e($paragraph) ?></p>
      <?php endforeach; ?>
      <blockquote class="my-8 border-l-2 border-gold pl-6">
        <p class="font-display text-xl font-light not-italic text-ink">&ldquo;<?= e(I18n::pick($story['quote'] ?? null, $locale)) ?>&rdquo;</p>
        <cite class="mt-3 block text-[0.7rem] uppercase not-italic tracking-[0.18em] text-muted"><?= e($couple) ?></cite>
      </blockquote>
    <?= Ui::revealClose() ?>

    <?= Ui::revealOpen(120) ?>
      <div class="border border-sand-deep bg-sand/40 p-7">
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
            <dt class="text-muted"><?= e(I18n::t('portfolio.venue')) ?></dt>
            <dd class="text-right text-ink"><?= e(I18n::pick($story['venue'] ?? null, $locale)) ?></dd>
          </div>
          <div class="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
            <dt class="text-muted"><?= e(I18n::t('portfolio.month')) ?></dt>
            <dd class="text-ink"><?= e(I18n::pick($story['month'] ?? null, $locale)) ?></dd>
          </div>
          <div class="flex justify-between gap-4 pb-1">
            <dt class="text-muted"><?= e(I18n::t('portfolio.guests')) ?></dt>
            <dd class="text-ink"><?= e((string) ($story['guests'] ?? '')) ?></dd>
          </div>
        </dl>

        <div class="mt-6 space-y-2">
          <?php if ($venue !== null) : ?>
            <a href="<?= e($p('/hochzeitslocations/' . (string) ($venue['slug'] ?? ''))) ?>"
               class="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"><?= e((string) ($venue['name'] ?? '')) ?></a>
          <?php endif; ?>
          <?php if ($city !== null) : ?>
            <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''))) ?>"
               class="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $de ? 'Hochzeitsfotograf ' : 'Wedding photographer ' ?><?= e((string) ($city['name'] ?? '')) ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?= Ui::revealClose() ?>
  </div>

  <?php
  /*
   * Der Hochzeitsfilm. Das Feld gab es im Adminbereich längst – „Hochzeitsfilm
   * (YouTube / Vimeo)“ unter jeder Reportage –, nur zeigte diese Seite ihn nie
   * an: eingetragen, gespeichert, und dann nirgends zu sehen.
   *
   * Zwei Klicks wie überall sonst: vor dem Antippen geht keine Anfrage an
   * YouTube, egal ob jemand eingewilligt hat.
   */
  $film = (string) ($story['videoUrl'] ?? '');
  ?>
  <?php if ($film !== '' && Video::isSupported($film)) : ?>
    <div class="mt-16">
      <?= Ui::revealOpen(0) ?>
        <div class="text-[0.62rem] uppercase tracking-[0.24em] text-gold">
          <?= $de ? 'Der Film' : 'The film' ?>
        </div>
        <div class="mt-5">
          <?= Video::embedBox($film, $couple . ' – Film', (string) ($gallery[0] ?? '')) ?>
        </div>
      <?= Ui::revealClose() ?>
    </div>
  <?php endif; ?>

  <?php if ($gallery !== []) : ?>
    <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($gallery as $i => $seed) : ?>
        <?= Ui::revealOpen(($i % 3) * 70) ?>
          <?= Ui::photo((string) $seed, $couple . ' ' . ($i + 1), $i % 5 === 0 ? '4/3' : '3/4', '', '(max-width: 640px) 100vw, 33vw', 700, 900) ?>
        <?= Ui::revealClose() ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?= Ui::sectionClose() ?>

<?php if ($others !== []) : ?>
  <?= Ui::sectionOpen('sand') ?>
    <?= Ui::head(I18n::t('portfolio.more'), I18n::t('home.portfolioEyebrow')) ?>
    <div class="mt-12 grid gap-8 md:grid-cols-3">
      <?php foreach ($others as $i => $other) : ?>
        <?= Ui::revealOpen($i * 80) ?>
          <a href="<?= e($p('/portfolio/' . (string) ($other['slug'] ?? ''))) ?>" class="group block">
            <?= Ui::photo(
                (string) (((array) ($other['seeds'] ?? []))[0] ?? ''),
                (string) ($other['couple'] ?? ''),
                '4/3',
                '',
                '(max-width: 768px) 100vw, 33vw',
                700,
                525
            ) ?>
            <h3 class="font-display mt-4 text-xl font-normal text-ink transition-colors group-hover:text-gold"><?= e((string) ($other['couple'] ?? '')) ?></h3>
            <p class="mt-1 text-[0.8rem] uppercase tracking-[0.16em] text-muted"><?= e(I18n::pick($other['venue'] ?? null, $locale)) ?></p>
          </a>
        <?= Ui::revealClose() ?>
      <?php endforeach; ?>
    </div>
  <?= Ui::sectionClose() ?>
<?php endif; ?>
