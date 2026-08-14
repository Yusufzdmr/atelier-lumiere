<?php
/**
 * Impressum, Datenschutz, AGB – Inhalt kommt aus dem Adminbereich.
 *
 * @var array<string,mixed> $page
 */

use Atelier\LegalText;
use Atelier\Ui;
?>
<?= Ui::sectionOpen('cream', 'pt-36') ?>
  <?= LegalText::render($page) ?>
<?= Ui::sectionClose() ?>
