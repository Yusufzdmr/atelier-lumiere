<?php
/**
 * Ratgeber-Beitrag. Die interne Verlinkung zu Stadt und Location ist der
 * eigentliche Zweck: der Beitrag zieht, die Geldseiten profitieren.
 *
 * @var string $locale
 * @var array<string,mixed> $post
 * @var array<string,mixed>|null $city
 * @var array<string,mixed>|null $venue
 * @var list<array<string,mixed>> $more
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
$uploads = (array) ($post['uploads'] ?? []);
$cover = (string) ($uploads[0] ?? ($post['seed'] ?? ''));
$title = I18n::pick($post['title'] ?? null, $locale);
$faq = (array) ($post['faq'] ?? []);
?>
<?= Ui::pageHero($cover, $title, Dates::long((string) ($post['date'] ?? ''), $locale), I18n::pick($post['excerpt'] ?? null, $locale)) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('blog.title'), 'href' => $p('/ratgeber')],
      ['name' => $title],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
    <?= Ui::revealOpen(0, 'prose-lux max-w-none') ?>
      <?php foreach (I18n::pickList($post['body'] ?? null, $locale) as $paragraph) : ?>
        <?php if (str_starts_with($paragraph, '## ')) : ?>
          <h2><?= e(substr($paragraph, 3)) ?></h2>
        <?php else : ?>
          <p><?= e($paragraph) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    <?= Ui::revealClose() ?>

    <div class="space-y-6">
      <?php if (isset($uploads[1])) : ?>
        <?= Ui::revealOpen(100, '', true) ?>
          <?= Ui::photo((string) $uploads[1], $title, '4/5', '', '(max-width: 1024px) 100vw, 40vw', 800, 1000) ?>
        <?= Ui::revealClose() ?>
      <?php endif; ?>

      <?php if ($city !== null || $venue !== null) : ?>
        <?= Ui::revealOpen(140, 'border border-sand-deep bg-sand/40 p-6') ?>
          <h2 class="text-[0.66rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('blog.related')) ?></h2>
          <div class="mt-5 space-y-2.5">
            <?php if ($city !== null) : ?>
              <a href="<?= e($p('/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''))) ?>"
                 class="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
                <?= $de ? 'Hochzeitsfotograf ' : 'Wedding photographer ' ?><?= e((string) ($city['name'] ?? '')) ?>
              </a>
            <?php endif; ?>
            <?php if ($venue !== null) : ?>
              <a href="<?= e($p('/hochzeitslocations/' . (string) ($venue['slug'] ?? ''))) ?>"
                 class="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"><?= e((string) ($venue['name'] ?? '')) ?></a>
            <?php endif; ?>
          </div>
        <?= Ui::revealClose() ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($faq !== []) : ?>
    <div class="mt-20 grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
      <h2 class="headline text-3xl sm:text-4xl"><?= e(I18n::t('blog.faq')) ?></h2>
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
  <?php endif; ?>
<?= Ui::sectionClose() ?>

<?php if ($more !== []) : ?>
  <?= Ui::sectionOpen('sand') ?>
    <?= Ui::head(I18n::t('blog.more'), I18n::t('blog.nav')) ?>
    <div class="mt-12 grid gap-10 md:grid-cols-2">
      <?php foreach ($more as $i => $other) : ?>
        <?php $otherUploads = (array) ($other['uploads'] ?? []); ?>
        <?= Ui::revealOpen($i * 90) ?>
          <a href="<?= e($p('/ratgeber/' . (string) ($other['slug'] ?? ''))) ?>" class="group block">
            <?= Ui::photo(
                (string) ($otherUploads[0] ?? ($other['seed'] ?? '')),
                I18n::pick($other['title'] ?? null, $locale),
                '3/2',
                '',
                '(max-width: 768px) 100vw, 45vw',
                800,
                533
            ) ?>
            <h3 class="font-display mt-4 text-xl font-normal leading-snug text-ink transition-colors group-hover:text-gold"><?= e(I18n::pick($other['title'] ?? null, $locale)) ?></h3>
            <p class="mt-2 text-[0.88rem] leading-relaxed text-muted"><?= e(I18n::pick($other['excerpt'] ?? null, $locale)) ?></p>
          </a>
        <?= Ui::revealClose() ?>
      <?php endforeach; ?>
    </div>
  <?= Ui::sectionClose() ?>
<?php endif; ?>
