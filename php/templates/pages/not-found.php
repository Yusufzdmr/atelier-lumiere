<?php
/**
 * @var string $locale
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$de = $locale === 'de';
?>
<?= Ui::sectionOpen('cream', 'pt-40') ?>
  <div class="mx-auto max-w-xl text-center">
    <div class="eyebrow">404</div>
    <h1 class="headline mt-4 text-4xl sm:text-5xl"><?= $de ? 'Diese Seite gibt es nicht' : 'Böyle bir sayfa yok' ?></h1>
    <p class="mt-5 text-[0.98rem] leading-relaxed text-muted">
      <?= $de
          ? 'Vielleicht wurde sie verschoben. Über die Startseite geht es weiter.'
          : 'Sayfa taşınmış olabilir. Ana sayfadan devam edebilirsiniz.' ?>
    </p>
    <div class="mt-10 flex flex-wrap justify-center gap-3">
      <?= Ui::btn(I18n::path('', $locale), $de ? 'Zur Startseite' : 'Ana sayfaya', 'solid') ?>
      <?= Ui::btn(I18n::path('/kontakt', $locale), e(I18n::t('nav.contact')), 'outline') ?>
    </div>
  </div>
<?= Ui::sectionClose() ?>
