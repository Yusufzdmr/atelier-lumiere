<?php
/**
 * Aufgerufene Kundenakte gibt es nicht (mehr) – etwa nach dem Löschen, wenn
 * jemand den Zurück-Knopf drückt.
 *
 * @var string $locale
 * @var string $code
 */

use function Atelier\e;
use Atelier\I18n;

$de = $locale === 'de';
?>
<div class="max-w-xl">
  <h2 class="font-display text-xl text-ink"><?= $de ? 'Diese Kundenakte gibt es nicht.' : 'Böyle bir müşteri kaydı yok.' ?></h2>
  <p class="mt-3 text-sm leading-relaxed text-muted">
    <?= $de ? 'Gesucht wurde' : 'Aranan' ?>: <strong class="text-ink"><?= e($code) ?></strong>.
    <?= $de
      ? 'Vielleicht wurde sie gelöscht oder der Anmeldename hat sich geändert.'
      : 'Silinmiş ya da giriş adı değişmiş olabilir.' ?>
  </p>
  <a href="<?= e(I18n::path('/admin/kunden', $locale)) ?>"
     class="mt-6 inline-block border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
    <?= $de ? 'Zur Kundenliste' : 'Müşteri listesine' ?>
  </a>
</div>
