<?php
/**
 * Schaufenster der zweiten Fassung.
 *
 * Die Kachel ist die Karte, die der Gast spaeter sieht - derselbe Stilblock,
 * dasselbe Markup. Was hier gut aussieht, sieht auch in der Einladung gut aus.
 *
 * Der Kopf ist fixiert und 94 px hoch; diese Seite hat keinen Hero, also kauft
 * sie sich den Abstand selbst (pt-32).
 *
 * @var list<array<string,mixed>> $designs
 * @var string $styles
 * @var array<string,string> $values
 * @var list<string> $kategorien
 * @var string $filter
 * @var array<string,bool> $machbar
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;

$p  = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
?>
<style><?= $styles ?></style>

<section class="mx-auto max-w-7xl px-6 pb-24 pt-32">
  <h1 class="font-display text-3xl font-light text-ink"><?= e(I18n::t('invitation2.title')) ?></h1>
  <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted"><?= e(I18n::t('invitation2.lead')) ?></p>

  <?php if ($kategorien !== []) : ?>
    <div class="mt-8 flex flex-wrap items-center gap-4 text-[0.66rem] uppercase tracking-[0.16em]">
      <a href="<?= e($p('/v2/designs')) ?>" class="<?= $filter === '' ? 'text-gold' : 'text-muted hover:text-ink' ?>">
        <?= $de ? 'Alle' : 'All' ?>
      </a>
      <?php foreach ($kategorien as $k) : ?>
        <a href="<?= e($p('/v2/designs') . '?kategorie=' . rawurlencode($k)) ?>"
           class="<?= $filter === $k ? 'text-gold' : 'text-muted hover:text-ink' ?>"><?= e($k) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($designs === []) : ?>
    <p class="mt-10 text-sm text-muted">
      <?= $de
        ? 'Hier steht gerade keine Vorlage. Schreibt uns – wir zeigen euch, was möglich ist: '
        : 'No template here right now. Write to us and we will show you what is possible: ' ?>
      <a class="text-gold" href="<?= e($p('/kontakt')) ?>"><?= e(I18n::t('nav.contact')) ?></a>
    </p>
  <?php endif; ?>

  <div class="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design) : ?>
      <?php $slug = (string) $design['slug']; ?>
      <div>
        <a href="<?= e($p('/v2/designs/' . $slug)) ?>" class="group block">
          <div class="d-<?= e($design['id']) ?> relative overflow-hidden"
               style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                      background: var(--d-bg, #EFE7DC);">
            <?= Design::html($design, $values, $locale) ?>
          </div>
          <p class="mt-4 font-display text-lg font-light text-ink group-hover:text-gold">
            <?= e($design['name'][$locale] ?? $design['name']['de']) ?>
          </p>
          <p class="text-xs uppercase tracking-[0.16em] text-muted"><?= e((string) $design['category']) ?></p>
        </a>

        <?php if ($machbar[$slug] ?? false) : ?>
          <a href="<?= e($p('/einladung') . '?design=' . rawurlencode($slug)) ?>"
             class="mt-3 inline-block border border-ink px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= $de ? 'Mit diesem Design erstellen' : 'Create with this design' ?>
          </a>
        <?php else : ?>
          <p class="mt-3 text-[0.64rem] uppercase tracking-[0.16em] text-muted">
            <?= $de ? 'Bald im Assistenten' : 'Coming to the wizard' ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
