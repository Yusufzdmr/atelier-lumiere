<?php
/**
 * Anmeldung zum Adminbereich.
 *
 * @var string $locale
 * @var bool $error
 * @var string $csrf
 */

use function Atelier\e;

$de = $locale === 'de';
?>
<div class="mx-auto max-w-sm border border-sand-deep p-8">
  <h2 class="font-display text-xl text-ink"><?= $de ? 'Anmeldung' : 'Giriş' ?></h2>
  <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
    <?= $de
        ? 'Dieser Bereich ist nicht öffentlich. Bitte das Passwort eingeben.'
        : 'Bu bölüm herkese açık değildir. Lütfen parolayı girin.' ?>
  </p>

  <form method="post" class="mt-7 space-y-6">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div>
      <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="password">
        <?= $de ? 'Passwort' : 'Parola' ?>
      </label>
      <input id="password" name="password" type="password" required autocomplete="current-password" autofocus
             class="w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold">
    </div>

    <?php if ($error) : ?>
      <p class="text-sm text-red-700"><?= $de ? 'Passwort falsch.' : 'Parola hatalı.' ?></p>
    <?php endif; ?>

    <button class="w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $de ? 'Anmelden' : 'Giriş yap' ?>
    </button>
  </form>
</div>
