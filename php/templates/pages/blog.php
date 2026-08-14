<?php
/**
 * Ratgeber-Übersicht.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $posts
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('lum-blog-hero', I18n::t('blog.title'), I18n::t('blog.nav'), I18n::t('blog.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('blog.title')],
  ]) ?>

  <?php if ($posts === []) : ?>
    <p class="text-muted"><?= e(I18n::t('blog.empty')) ?></p>
  <?php else : ?>
    <div class="grid gap-12 md:grid-cols-2 lg:gap-x-16">
      <?php foreach ($posts as $i => $post) : ?>
        <?php $uploads = (array) ($post['uploads'] ?? []); ?>
        <?= Ui::revealOpen(($i % 2) * 90) ?>
          <a href="<?= e($p('/ratgeber/' . (string) ($post['slug'] ?? ''))) ?>" class="group block">
            <?= Ui::photo(
                (string) ($uploads[0] ?? ($post['seed'] ?? '')),
                I18n::pick($post['title'] ?? null, $locale),
                '3/2',
                '',
                '(max-width: 768px) 100vw, 45vw',
                900,
                600
            ) ?>
            <time datetime="<?= e((string) ($post['date'] ?? '')) ?>" class="mt-5 block text-[0.66rem] uppercase tracking-[0.2em] text-gold">
              <?= e(Dates::long((string) ($post['date'] ?? ''), $locale)) ?>
            </time>
            <h2 class="font-display mt-2 text-2xl font-normal leading-snug text-ink transition-colors group-hover:text-gold"><?= e(I18n::pick($post['title'] ?? null, $locale)) ?></h2>
            <p class="mt-3 text-[0.92rem] leading-relaxed text-muted"><?= e(I18n::pick($post['excerpt'] ?? null, $locale)) ?></p>
            <span class="link-underline mt-4 inline-block text-[0.68rem] uppercase tracking-[0.2em] text-ink"><?= e(I18n::t('blog.readMore')) ?> →</span>
          </a>
        <?= Ui::revealClose() ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?= Ui::sectionClose() ?>
