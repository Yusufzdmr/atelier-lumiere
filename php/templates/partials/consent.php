<?php
/**
 * Cookie-Hinweis und die Einstellungen dahinter.
 *
 * Der Kasten steht im HTML, ist aber ausgeblendet: das Skript zeigt ihn nur,
 * wenn noch keine Entscheidung vorliegt. Ohne JavaScript bleibt er weg – dann
 * wird auch nichts geladen, was eine Einwilligung bräuchte. Das ist die
 * richtige Richtung: im Zweifel weniger.
 *
 * Die Kennungen kommen als Datenfeld herein, nicht als Skriptblock. So bleibt
 * die Inhaltsrichtlinie eng (script-src 'self').
 *
 * @var string $locale
 * @var array<string,mixed> $tracking
 */

use function Atelier\e;
use Atelier\I18n;

$box = 'h-4 w-4 accent-[#B08D57]';
$row = 'flex cursor-pointer items-center gap-3 text-ink';
?>
<div data-tracking="<?= e((string) json_encode($tracking, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>" hidden></div>

<div data-consent hidden class="fixed inset-x-0 bottom-0 z-[60] p-3 sm:p-5">
  <div class="mx-auto max-w-3xl border border-sand-deep bg-cream p-5 shadow-[0_20px_60px_-20px_rgba(20,17,15,0.35)] sm:p-7">
    <h2 class="font-display text-lg font-normal text-ink"><?= e(I18n::t('cookie.title')) ?></h2>
    <p class="mt-2 text-sm leading-relaxed text-muted"><?= e(I18n::t('cookie.text')) ?></p>

    <div data-consent-details hidden class="mt-4 space-y-2 border-t border-sand pt-4 text-sm">
      <label class="flex cursor-not-allowed items-center gap-3 text-muted">
        <input type="checkbox" checked disabled class="<?= $box ?>">
        <?= e(I18n::t('cookie.necessary')) ?>
      </label>
      <?php /* Nichts ist vorangekreuzt – ein Haken, den niemand gesetzt hat, ist keine Einwilligung. */ ?>
      <label class="<?= $row ?>">
        <input type="checkbox" data-consent-stats class="<?= $box ?>">
        <?= e(I18n::t('cookie.stats')) ?>
      </label>
      <label class="<?= $row ?>">
        <input type="checkbox" data-consent-marketing class="<?= $box ?>">
        <?= e(I18n::t('cookie.marketing')) ?>
      </label>
    </div>

    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center">
      <button type="button" data-consent-all
              class="bg-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-opacity hover:opacity-85">
        <?= e(I18n::t('cookie.acceptAll')) ?>
      </button>
      <?php /* Ablehnen steht gleichwertig daneben, nicht als kleiner Link darunter. */ ?>
      <button type="button" data-consent-none
              class="border border-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= e(I18n::t('cookie.acceptNecessary')) ?>
      </button>
      <button type="button" data-consent-settings
              class="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:underline">
        <?= e(I18n::t('cookie.settings')) ?>
      </button>
      <button type="button" data-consent-save hidden
              class="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-gold underline-offset-4 hover:underline">
        <?= e(I18n::t('cookie.save')) ?>
      </button>
      <a href="<?= e(I18n::path('/datenschutz', $locale)) ?>"
         class="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:underline sm:ml-auto">
        <?= e(I18n::t('cookie.more')) ?>
      </a>
    </div>
  </div>
</div>
