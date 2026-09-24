<?php
/**
 * Tek galerinin detayı — albümcü için. Eski selection.php'nin devamı,
 * token yerine oturum + {code} kullanıyor.
 *
 * @var string $locale
 * @var array<string,mixed> $gallery
 * @var array<string,mixed>|null $selection
 * @var list<array{nr:int,url:string,original:?string,name:string}> $photos
 * @var array{nr:int,url:string,original:?string,name:string}|null $cover
 * @var string $code
 * @var string $dateLong
 */

use function Atelier\e;
use Atelier\I18n;

$de = $locale === 'de';
$couple = (string) ($gallery['couple'] ?? '');
$withOriginal = array_values(array_filter($photos, static fn (array $p): bool => $p['original'] !== null));
?>
<div class="mx-auto max-w-5xl px-5 py-14 sm:py-20">

  <a href="<?= e(I18n::path('/albumcu', $locale)) ?>"
     class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
    <?= $de ? '← Zur Liste' : '← Listeye dön' ?>
  </a>

  <div class="mt-6 text-[0.62rem] uppercase tracking-[0.24em] text-muted"><?= $de ? 'Bildauswahl' : 'Fotoğraf seçimi' ?></div>
  <h1 class="font-display mt-2 text-3xl font-light text-ink sm:text-4xl"><?= e($couple) ?></h1>
  <p class="mt-3 text-sm text-muted">
    <?= e($dateLong) ?><?= (string) ($gallery['venue'] ?? '') !== '' ? ' · ' . e((string) $gallery['venue']) : '' ?>
  </p>

  <?php if ($photos === []) : ?>
    <p class="mt-10 border border-sand-deep p-6 text-sm leading-relaxed text-muted">
      <?= $de ? 'Es ist noch keine Auswahl getroffen worden.' : 'Henüz bir seçim yapılmadı.' ?>
    </p>
  <?php else : ?>

    <div class="mt-8 flex flex-wrap items-center gap-6 border-y border-sand-deep py-6">
      <div>
        <div class="font-display text-3xl font-light text-ink"><?= count($photos) ?></div>
        <div class="mt-1 text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Bilder' : 'Kare' ?></div>
      </div>

      <?php if ($withOriginal !== []) : ?>
        <a href="<?= e(I18n::path('/albumcu/' . $code . '/zip', $locale)) ?>"
           class="ml-auto bg-ink px-8 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          <?= $de ? 'Alle herunterladen (ZIP)' : 'Tümünü indir (ZIP)' ?>
        </a>
      <?php endif; ?>
    </div>

    <?php if (count($withOriginal) < count($photos)) : ?>
      <p class="mt-5 border border-gold/50 bg-sand/40 px-5 py-3 text-[0.84rem] leading-relaxed text-ink">
        <?= $de
          ? 'Von ' . count($photos) . ' Bildern liegen ' . count($withOriginal) . ' in voller Auflösung vor. Bitte beim Fotografen nach den übrigen fragen.'
          : count($photos) . ' karenin ' . count($withOriginal) . ' tanesi tam çözünürlükte. Kalanlar için fotoğrafçıya sorun.' ?>
      </p>
    <?php endif; ?>

    <?php if ($cover !== null) : ?>
      <div class="mt-6 flex items-center gap-4 border-l-2 border-gold pl-5">
        <img src="<?= e($cover['url']) ?>" alt="" class="h-20 w-16 object-cover">
        <div>
          <div class="text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Titelbild' : 'Kapak fotoğrafı' ?></div>
          <div class="mt-1 text-[0.9rem] text-ink"><?= $de ? 'Nr.' : 'No.' ?> <?= (int) $cover['nr'] ?> — <?= e($cover['name']) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ((string) ($selection['note'] ?? '') !== '') : ?>
      <div class="mt-6 border-l-2 border-gold pl-5">
        <div class="text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Notiz des Paares' : 'Çiftin notu' ?></div>
        <p class="mt-2 text-[0.95rem] leading-relaxed text-ink">&bdquo;<?= e((string) $selection['note']) ?>&ldquo;</p>
      </div>
    <?php endif; ?>

    <div class="mt-10 grid gap-6 sm:grid-cols-3 lg:grid-cols-4">
      <?php foreach ($photos as $photo) : ?>
        <figure>
          <div class="relative aspect-[3/4] overflow-hidden bg-sand">
            <img src="<?= e($photo['url']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
            <span class="absolute left-0 top-0 bg-ink/80 px-2 py-1 text-[0.62rem] tracking-[0.1em] text-cream">
              <?= (int) $photo['nr'] ?>
            </span>
          </div>
          <figcaption class="mt-2 break-all font-mono text-[0.68rem] text-muted"><?= e($photo['name']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
