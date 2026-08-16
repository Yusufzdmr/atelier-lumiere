<?php
/**
 * Die festen Bildplätze der Website.
 *
 * Ein Kasten je Platz: was gerade dort steht, ein Feld zum Tauschen und –
 * wenn ein eigenes Bild gesetzt ist – der Weg zurück zum Platzhalter.
 *
 * @var string $locale
 * @var array<string,array{de:string,tr:string}> $slots
 * @var array<string,string> $own
 * @var string $title
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Images;

$de = $locale === 'de';
?>
<form method="post" enctype="multipart/form-data" class="space-y-8">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <div>
    <h2 class="font-display text-2xl text-ink"><?= e($title) ?></h2>
    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Solange hier nichts steht, zeigt die Seite ein Platzhalterbild. Ein eigenes Bild ersetzt es überall dort, wo dieser Platz vorkommt. Querformat, mindestens 1600 Punkte breit.'
        : 'Buraya bir şey konmadığı sürece sayfa temsili bir görsel gösterir. Yüklediğiniz görsel, o alanın geçtiği her yerde onun yerini alır. Yatay, en az 1600 piksel genişlik.' ?>
    </p>
  </div>

  <div class="grid gap-6 sm:grid-cols-2">
    <?php foreach ($slots as $slot => $label) : ?>
      <?php $eigen = (string) ($own[$slot] ?? ''); ?>
      <div class="border border-sand-deep p-5">
        <div class="flex items-baseline justify-between gap-3">
          <h3 class="text-[0.82rem] text-ink"><?= e($label[$locale] ?? $label['de']) ?></h3>
          <span class="text-[0.6rem] uppercase tracking-[0.16em] <?= $eigen !== '' ? 'text-gold' : 'text-muted' ?>">
            <?= $eigen !== '' ? ($de ? 'eigenes Bild' : 'kendi görseliniz') : ($de ? 'Platzhalter' : 'temsili') ?>
          </span>
        </div>

        <div class="mt-4 aspect-[3/2] overflow-hidden border border-sand-deep bg-sand">
          <img src="<?= e(Images::img($slot, 800, 534)) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
        </div>

        <input type="file" name="bild[<?= e($slot) ?>]" accept="image/*"
               class="mt-4 w-full text-[0.78rem] text-muted file:mr-3 file:border file:border-sand-deep file:bg-transparent file:px-3 file:py-1.5 file:text-[0.62rem] file:uppercase file:tracking-[0.14em] file:text-ink">

        <?php if ($eigen !== '') : ?>
          <label class="mt-3 flex cursor-pointer items-center gap-2 text-[0.72rem] text-muted hover:text-ink">
            <input type="checkbox" name="weg[<?= e($slot) ?>]" value="1" class="h-3.5 w-3.5 accent-[#B08D57]">
            <?= $de ? 'Entfernen und Platzhalter zeigen' : 'Kaldır ve temsili görseli göster' ?>
          </label>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="sticky bottom-0 -mb-8 flex flex-wrap items-center gap-4 border-t border-sand-deep bg-cream/95 py-4 backdrop-blur lg:-mb-10">
    <button class="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $de ? 'Speichern' : 'Kaydet' ?>
    </button>
    <a href="<?= e(\Atelier\I18n::path('', $locale)) ?>" target="_blank" rel="noopener"
       class="text-[0.68rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold">
      <?= $de ? 'Startseite ansehen' : 'Ana sayfayı gör' ?> ↗
    </a>
  </div>
</form>
