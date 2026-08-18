<?php
/**
 * Admin parolasını değiştir.
 *
 * @var string $locale
 * @var string $csrf
 * @var string $error  '' = hata yok; 'current' | 'short' | 'mismatch' | 'hash'
 * @var bool $hasHash  DB'de parola hash'i var mı — bilgi mesajı için
 */

use function Atelier\e;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';

$errors = [
    'current'  => ['de' => 'Aktuelles Passwort stimmt nicht.', 'tr' => 'Mevcut parola doğru değil.'],
    'short'    => ['de' => 'Neues Passwort ist zu kurz (mindestens 8 Zeichen).', 'tr' => 'Yeni parola çok kısa (en az 8 karakter).'],
    'mismatch' => ['de' => 'Die beiden neuen Passwörter stimmen nicht überein.', 'tr' => 'İki yeni parola aynı değil.'],
    'hash'     => ['de' => 'Passwort konnte nicht gespeichert werden.', 'tr' => 'Parola kaydedilemedi.'],
];
$errorMessage = $error !== '' ? ($errors[$error][$locale] ?? '') : '';
?>
<div class="max-w-md space-y-8">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Zugang' : 'Erişim' ?></h2>
    <p class="mt-2 text-sm leading-relaxed text-muted">
      <?php if ($hasHash) : ?>
        <?= $de
          ? 'Das Adminpasswort steht in der Datenbank. Zum Ändern hier ein neues setzen.'
          : 'Admin parolası veri tabanında tutuluyor. Değiştirmek için burada yeni bir tane belirle.' ?>
      <?php else : ?>
        <?= $de
          ? 'Noch nutzt der Adminbereich das Passwort aus config.php. Setzt hier eines, greift ab sofort die Datenbank.'
          : 'Panel şu an config.php\'deki parolayı kullanıyor. Burada bir tane belirlediğinde, bundan sonra veri tabanı geçerli olur.' ?>
      <?php endif; ?>
    </p>
  </div>

  <?php if ($errorMessage !== '') : ?>
    <p class="border border-red-700/40 bg-red-50 px-4 py-3 text-sm text-red-700">
      <?= e($errorMessage) ?>
    </p>
  <?php endif; ?>

  <form method="post" class="space-y-6" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div>
      <label class="<?= $label ?>" for="current"><?= $de ? 'Aktuelles Passwort' : 'Mevcut parola' ?></label>
      <input id="current" type="password" name="current" required autocomplete="current-password"
             class="<?= $input ?> mt-2">
    </div>

    <div>
      <label class="<?= $label ?>" for="new"><?= $de ? 'Neues Passwort' : 'Yeni parola' ?></label>
      <input id="new" type="password" name="new" required minlength="8" autocomplete="new-password"
             class="<?= $input ?> mt-2">
      <p class="mt-2 text-[0.72rem] text-muted">
        <?= $de ? 'Mindestens 8, zwölf oder mehr Zeichen sind besser.' : 'En az 8 karakter — on iki ve üzeri daha iyi.' ?>
      </p>
    </div>

    <div>
      <label class="<?= $label ?>" for="confirm"><?= $de ? 'Neues Passwort (Wiederholung)' : 'Yeni parola (tekrar)' ?></label>
      <input id="confirm" type="password" name="confirm" required minlength="8" autocomplete="new-password"
             class="<?= $input ?> mt-2">
    </div>

    <button type="submit"
            class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
      <?= $de ? 'Passwort ändern' : 'Parolayı değiştir' ?>
    </button>
  </form>
</div>
