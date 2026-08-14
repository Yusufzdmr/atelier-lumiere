<?php
/**
 * Die private Galerie eines Paares.
 *
 * Auswahl und Lightbox macht assets/gallery.js; ohne Skript bleiben die
 * Bilder trotzdem sichtbar und anklickbar.
 *
 * @var string $locale
 * @var array<string,mixed> $gallery
 * @var list<array{thumb:string,full:string,upload:bool}> $photos
 * @var array<string,mixed>|null $selection
 * @var string $dateLong
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Video;

$couple = (string) ($gallery['couple'] ?? '');
$code = (string) ($gallery['code'] ?? '');
$picks = array_map('intval', (array) ($selection['picks'] ?? []));
$videoUrl = (string) ($gallery['videoUrl'] ?? '');
?>
<div class="pb-40" data-gallery data-code="<?= e($code) ?>" data-csrf="<?= e($csrf) ?>"
     data-endpoint="<?= e(I18n::path('/galerie/' . $code . '/auswahl', $locale)) ?>"
     data-picks="<?= e(implode(',', $picks)) ?>">

  <div class="mx-auto max-w-7xl px-5 pt-32 sm:px-8 sm:pt-40">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="eyebrow"><?= e(I18n::t('gallery.protected')) ?></div>
        <h1 class="headline mt-3 text-4xl sm:text-5xl"><?= e($couple) ?></h1>
        <p class="mt-3 text-sm text-muted">
          <?= e((string) ($gallery['venue'] ?? '')) ?> · <?= e($dateLong) ?> · <?= count($photos) ?> <?= e(I18n::t('gallery.photos')) ?>
        </p>
      </div>
      <a href="<?= e(I18n::path('/galerie/abmelden', $locale)) ?>"
         class="border border-sand-deep px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
        <?= $locale === 'de' ? 'Abmelden' : 'Çıkış' ?>
      </a>
    </div>

    <p class="mt-6 max-w-xl border-l-2 border-gold pl-4 text-sm leading-relaxed text-muted"><?= e(I18n::t('gallery.selectHint')) ?></p>

    <?php if ($videoUrl !== '' && Video::isSupported($videoUrl)) : ?>
      <div class="mt-10 max-w-3xl">
        <?= Video::embedBox($videoUrl, $couple . ' – Film', (string) ($photos[0]['full'] ?? '')) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mx-auto mt-12 max-w-7xl px-5 sm:px-8">
    <div class="columns-2 gap-3 sm:columns-3 sm:gap-4 lg:columns-4">
      <?php foreach ($photos as $i => $photo) : ?>
        <?php $ratio = $i % 5 === 0 ? '3/4' : ($i % 3 === 0 ? '4/5' : '1/1'); ?>
        <div class="group relative mb-3 break-inside-avoid sm:mb-4">
          <button type="button" data-photo="<?= $i ?>" data-full="<?= e($photo['full']) ?>"
                  class="relative block w-full overflow-hidden bg-sand" style="aspect-ratio: <?= e($ratio) ?>"
                  aria-label="<?= e($couple . ' ' . ($i + 1)) ?>">
            <img src="<?= e($photo['thumb']) ?>" alt="<?= e($couple . ' ' . ($i + 1)) ?>" loading="lazy" decoding="async"
                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          </button>

          <button type="button" data-pick="<?= $i ?>"
                  aria-pressed="<?= in_array($i, $picks, true) ? 'true' : 'false' ?>"
                  class="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full bg-cream/85 text-lg transition-colors hover:bg-cream">
            <span data-heart>♥</span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php /* Leiste am unteren Rand: Zähler, Nachricht, Absenden */ ?>
  <div class="fixed inset-x-0 bottom-0 z-40 border-t border-sand-deep bg-cream/95 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-5 py-4 sm:px-8">
      <div class="text-[0.8rem] text-ink">
        <strong data-count><?= count($picks) ?></strong> <?= e(I18n::t('gallery.selected')) ?>
      </div>

      <input type="text" data-note maxlength="800" value="<?= e((string) ($selection['note'] ?? '')) ?>"
             placeholder="<?= $locale === 'de' ? 'Nachricht an uns (optional)' : 'Bize not (isteğe bağlı)' ?>"
             class="min-w-[12rem] flex-1 border-b border-sand-deep bg-transparent px-0 py-2 text-[0.9rem] text-ink outline-none focus:border-gold">

      <button type="button" data-send
              class="bg-ink px-7 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-50">
        <?= e(I18n::t('gallery.send')) ?>
      </button>

      <span data-status class="text-[0.78rem] text-gold"></span>
    </div>
  </div>

  <?php /* Lightbox */ ?>
  <div data-lightbox class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/95 p-4">
    <button type="button" data-close class="absolute right-5 top-5 text-2xl text-cream/70 hover:text-cream" aria-label="<?= e(I18n::t('gallery.close')) ?>">×</button>
    <button type="button" data-prev class="absolute left-4 text-3xl text-cream/60 hover:text-cream" aria-label="<?= e(I18n::t('gallery.prev')) ?>">‹</button>
    <img data-lightbox-image src="" alt="" class="max-h-[86vh] max-w-full object-contain">
    <button type="button" data-next class="absolute right-4 text-3xl text-cream/60 hover:text-cream" aria-label="<?= e(I18n::t('gallery.next')) ?>">›</button>
    <div class="absolute bottom-6 flex items-center gap-5 text-[0.72rem] uppercase tracking-[0.18em] text-cream/70">
      <span data-position></span>
      <button type="button" data-lightbox-pick class="text-cream hover:text-gold">♥</button>
      <a data-download href="" download class="hover:text-gold"><?= e(I18n::t('gallery.download')) ?></a>
    </div>
  </div>
</div>

<div class="hidden" data-sent-text><?= e(I18n::t('gallery.sent')) ?></div>
<div class="hidden" data-sending-text><?= e(I18n::t('gallery.sending')) ?></div>
<div class="hidden" data-of-text><?= e(I18n::t('gallery.of')) ?></div>
