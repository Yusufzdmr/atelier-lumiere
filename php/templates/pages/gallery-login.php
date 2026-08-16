<?php
/**
 * Anmeldung zur Kundengalerie.
 *
 * @var string $locale
 * @var string $error
 * @var string $presetCode
 * @var string $csrf
 * @var string|null $couple
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
$de = $locale === 'de';
$couple = $couple ?? '';

$steps = $de
    ? [
        'Ihr bekommt von uns eine E-Mail mit Galerie-Code und Passwort.',
        'Alle Bilder in voller Auflösung – teilbar mit Familie und Freunden.',
        'Favoriten mit dem Herz markieren und die Auswahl fürs Album absenden.',
        'Wir sehen eure Auswahl sofort und gestalten das Album danach.',
    ]
    : [
        'You get an e-mail from us with the gallery code and the password.',
        'Every picture in full resolution – shareable with family and friends.',
        'Mark favourites with the heart and send the selection for the album.',
        'We see your selection straight away and build the album around it.',
    ];

$field = 'w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors focus:border-gold';
$label = 'block text-[0.68rem] uppercase tracking-[0.2em] text-muted';
?>
<?= Ui::pageHero('gallery-hero', $couple !== '' ? $couple : I18n::t('gallery.title'), I18n::t('gallery.protected'), I18n::t('gallery.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('gallery.title')],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-2 lg:gap-20">
    <?= Ui::revealOpen() ?>
      <form method="post" class="max-w-md space-y-7">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <div>
          <label class="<?= $label ?>" for="code"><?= e(I18n::t('gallery.code')) ?></label>
          <input id="code" name="code" required value="<?= e($presetCode) ?>" autocomplete="off" class="<?= $field ?>" placeholder="elif-marco">
        </div>

        <div>
          <label class="<?= $label ?>" for="password"><?= e(I18n::t('gallery.password')) ?></label>
          <input id="password" name="password" type="password" required autocomplete="current-password" class="<?= $field ?>">
        </div>

        <?php if ($error !== '') : ?>
          <p class="text-sm text-red-700"><?= e(I18n::t('gallery.wrong')) ?></p>
        <?php endif; ?>

        <button type="submit" class="w-full bg-ink px-8 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold sm:w-auto">
          <?= e(I18n::t('gallery.open')) ?>
        </button>
      </form>
    <?= Ui::revealClose() ?>

    <?= Ui::revealOpen(120) ?>
      <ol class="space-y-7">
        <?php foreach ($steps as $i => $step) : ?>
          <li class="flex gap-5">
            <span class="font-display text-3xl font-light text-gold/40">0<?= $i + 1 ?></span>
            <span class="pt-2 text-[0.95rem] leading-relaxed text-muted"><?= e($step) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php
      /*
       * Vier Punkte beschreiben, was hinter dem Passwort liegt. Zeigen tun sie
       * nichts. Wer die Galerie einmal offen gesehen hat, versteht sie in zehn
       * Sekunden – deshalb steht sie hier zum Anschauen.
       */
      ?>
      <div class="mt-10 border-t border-sand-deep pt-8">
        <p class="text-[0.9rem] leading-relaxed text-muted">
          <?= $de
            ? 'Noch keinen Code? So sieht eine fertige Galerie aus – mit Bildern zum Durchsehen und dem Herz zum Aussuchen.'
            : 'No code yet? This is what a finished gallery looks like – photographs to browse and the heart to pick with.' ?>
        </p>
        <a href="<?= e(I18n::path('/galerie/beispiel', $locale)) ?>"
           class="mt-5 inline-block border border-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
          <?= $de ? 'Beispielgalerie ansehen' : 'See an example gallery' ?> →
        </a>
      </div>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
