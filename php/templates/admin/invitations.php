<?php
/**
 * Erstellte Einladungen und die liegengebliebenen Entwürfe.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $v2
 * @var list<array<string,mixed>> $drafts
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
$hidden = '<input type="hidden" name="csrf" value="' . e($csrf) . '">';
?>
<div class="space-y-10">

  <?php /*
     Abschalten und nicht loeschen. Eine verschickte Adresse loescht man
     nicht - wer sie loescht, gibt sie zur Wiederverwendung frei, und der
     naechste Gast, der den alten Link oeffnet, landet auf einer fremden
     Hochzeit. Ein Entwurf antwortet dem Gast wie eine Adresse, die es nicht
     gibt: wer den Link hat, soll nicht denken, er muesse es spaeter noch
     einmal versuchen.
  */ ?>
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Einladungen' : 'Davetiyeler' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Aus dem Assistenten. Abgeschaltet heißt: der Link antwortet wie eine Adresse, die es nicht gibt.'
        : 'Sihirbazdan çıkanlar. Kapalı demek: bağlantı, olmayan bir adres gibi cevap verir.' ?>
    </p>

    <?php if ($v2 === []) : ?>
      <p class="mt-5 border border-sand-deep p-5 text-sm text-muted">
        <?= $de ? 'Noch keine Einladung erstellt.' : 'Henüz davetiye oluşturulmadı.' ?>
      </p>
    <?php else : ?>
      <div class="mt-5 divide-y divide-sand-deep border-y border-sand-deep">
        <?php foreach ($v2 as $ein) : ?>
          <?php
            $slug = (string) ($ein['slug'] ?? '');
            $an   = (string) ($ein['status'] ?? 'published') !== 'draft';
          ?>
          <div class="flex flex-wrap items-center justify-between gap-4 py-3">
            <div class="min-w-0">
              <a class="text-sm text-ink underline decoration-sand-deep underline-offset-4"
                 href="<?= e(I18n::sitePath('/v2/einladung/' . $slug, $locale)) ?>" target="_blank">/<?= e($slug) ?></a>
              <div class="mt-1 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
                <?= e((string) ($ein['design_id'] ?? '')) ?>
                · <?= e(Dates::short((string) ($ein['created_at'] ?? ''))) ?>
                <?php if (!empty($ein['published_at'])) : ?>
                  · <?= $de ? 'seit' : 'şu tarihten beri' ?> <?= e(Dates::short((string) $ein['published_at'])) ?>
                <?php endif; ?>
              </div>
            </div>
            <form method="post" class="flex items-center gap-3">
              <?= $hidden ?>
              <input type="hidden" name="was" value="v2-zustand">
              <input type="hidden" name="slug" value="<?= e($slug) ?>">
              <input type="hidden" name="zustand" value="<?= $an ? 'draft' : 'published' ?>">
              <span class="text-[0.66rem] uppercase tracking-[0.16em] <?= $an ? 'text-ink' : 'text-muted' ?>">
                <?= $an ? ($de ? 'im Netz' : 'yayında') : ($de ? 'abgeschaltet' : 'kapalı') ?>
              </span>
              <button class="border border-sand-deep px-4 py-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-ink">
                <?= $an ? ($de ? 'abschalten' : 'kapat') : ($de ? 'anschalten' : 'aç') ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- --------------------------------- Entwürfe -------------------------------- -->
  <?php if ($drafts !== []) : ?>
    <section>
      <h3 class="font-display text-lg text-ink"><?= $de ? 'Liegengebliebene Entwürfe' : 'Yarım kalan taslaklar' ?></h3>
      <p class="mt-2 max-w-3xl text-[0.8rem] leading-relaxed text-muted">
        <?= $de
          ? 'Jemand hat angefangen und nicht abgeschickt. Nach 120 Tagen räumt sich das von selbst.'
          : 'Biri başlamış ama göndermemiş. 120 gün sonra kendiliğinden temizlenir.' ?>
      </p>

      <div class="mt-5 space-y-2">
        <?php foreach ($drafts as $draft) : ?>
          <div class="flex flex-wrap items-center justify-between gap-4 border border-sand-deep p-4">
            <div class="min-w-0">
              <div class="text-[0.88rem] text-ink"><?= e((string) ($draft['label'] ?? '—')) ?></div>
              <div class="mt-1 break-all text-[0.72rem] text-muted">
                <?= e(Dates::short((string) ($draft['updatedAt'] ?? ''))) ?> ·
                <a href="<?= e(I18n::sitePath('/v2/einladung', $locale)) ?>?taslak=<?= e((string) ($draft['token'] ?? '')) ?>"
                   class="text-gold underline-offset-4 hover:underline">
                  <?= $de ? 'Entwurf öffnen' : 'Taslağı aç' ?>
                </a>
              </div>
            </div>
            <form method="post">
              <?= $hidden ?>
              <input type="hidden" name="was" value="entwurf-loeschen">
              <input type="hidden" name="token" value="<?= e((string) ($draft['token'] ?? '')) ?>">
              <button data-confirm="<?= $de ? 'Entwurf löschen?' : 'Taslak silinsin mi?' ?>"
                      class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                <?= $de ? 'Löschen' : 'Sil' ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
