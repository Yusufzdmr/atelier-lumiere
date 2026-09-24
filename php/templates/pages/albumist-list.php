<?php
/**
 * Albümcü girişi sonrası: hazır olan galerilerin listesi.
 *
 * @var string $locale
 * @var list<array{gallery:array<string,mixed>,cover:array{nr:int,url:string,original:?string,name:string}|null,count:int}> $rows
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
?>
<div class="mx-auto max-w-3xl px-5 py-14 sm:py-20">
  <div class="flex items-center justify-between">
    <h1 class="font-display text-3xl font-light text-ink sm:text-4xl"><?= $de ? 'Hazır Galerien' : 'Hazır galeriler' ?></h1>
    <a href="<?= e(I18n::path('/albumcu/abmelden', $locale)) ?>"
       class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
      <?= $de ? 'Abmelden' : 'Çıkış yap' ?>
    </a>
  </div>

  <?php if ($rows === []) : ?>
    <p class="mt-10 border border-sand-deep p-6 text-sm leading-relaxed text-muted">
      <?= $de ? 'Noch keine Galerie bereit.' : 'Henüz hazır galeri yok.' ?>
    </p>
  <?php else : ?>
    <div class="mt-8 divide-y divide-sand-deep border-y border-sand-deep">
      <?php foreach ($rows as $row) : ?>
        <?php $gallery = $row['gallery']; $code = (string) ($gallery['code'] ?? ''); ?>
        <a href="<?= e(I18n::path('/albumcu/' . $code, $locale)) ?>"
           class="flex items-center gap-4 py-5 transition-colors hover:bg-sand/30">
          <?php if ($row['cover'] !== null) : ?>
            <img src="<?= e($row['cover']['url']) ?>" alt="" class="h-14 w-11 object-cover">
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="text-[0.95rem] text-ink"><?= e((string) ($gallery['couple'] ?? '')) ?></div>
            <div class="mt-1 text-[0.78rem] text-muted">
              <?= e(Dates::short((string) ($gallery['date'] ?? ''))) ?>
              · <?= $row['count'] ?> <?= $de ? 'Bilder' : 'kare' ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
