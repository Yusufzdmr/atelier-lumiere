<?php
/**
 * Reiter der Kundengalerie: Bilder und Vorlieben.
 *
 * In der Beispielgalerie gibt es nur die Bilder – der zweite Reiter würde
 * eine Auswahl versprechen, die nirgends ankommt.
 *
 * @var string $locale
 * @var string $code
 * @var string $active 'photos'|'preferences'
 * @var bool $preferencesFilled
 * @var bool $demo
 */

use function Atelier\e;
use Atelier\I18n;

$demo = $demo ?? false;
$de = $locale === 'de';
?>
<?php /* -mb-px und die 6px-Punktgröße stehen nicht in der vorab kompilierten
         style.css (siehe gallery-preferences.php) - von Hand statt geraten. */ ?>
<style>
  .pref-tab-nav a { margin-bottom: -1px; }
  .pref-tab-dot { display: inline-block; width: 6px; height: 6px; border-radius: 9999px; background: var(--color-gold); }
</style>
<?php if (empty($demo)) : ?>
  <nav class="pref-tab-nav mt-8 flex gap-8 border-b border-sand-deep text-[0.72rem] uppercase tracking-[0.18em]">
    <a href="<?= e(I18n::path('/galerie/' . $code, $locale)) ?>"
       class="border-b-2 pb-3 transition-colors <?= $active === 'photos' ? 'border-gold text-ink' : 'border-transparent text-muted hover:text-ink' ?>">
      <?= $de ? 'Bilder' : 'Pictures' ?>
    </a>
    <a href="<?= e(I18n::path('/galerie/' . $code . '/tercihler', $locale)) ?>"
       class="flex items-center gap-2 border-b-2 pb-3 transition-colors <?= $active === 'preferences' ? 'border-gold text-ink' : 'border-transparent text-muted hover:text-ink' ?>">
      <?= $de ? 'Eure Vorlieben' : 'Your preferences' ?>
      <?php if (empty($preferencesFilled)) : ?>
        <span class="pref-tab-dot" aria-hidden="true"
              title="<?= $de ? 'Noch nicht ausgefüllt' : 'Not filled in yet' ?>"></span>
      <?php endif; ?>
    </a>
  </nav>
<?php endif; ?>
