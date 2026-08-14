<?php
/**
 * Kontaktseite: Formular links, direkter Draht rechts.
 *
 * Die Karte wird erst nach Einwilligung geladen – vorher geht keine einzige
 * Anfrage an Google. Das ist kein Detail, sondern der Grund, warum die Seite
 * ohne Bauchschmerzen in Deutschland laufen kann.
 *
 * @var string $locale
 * @var array<string,mixed> $contact
 * @var string $csrf
 * @var bool $sent
 * @var array<string,string> $errors
 * @var array<string,string> $values
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;
use Atelier\View;

$p = static fn (string $to): string => I18n::path($to, $locale);
$street = (string) ($contact['street'] ?? '');
$zip = (string) ($contact['zip'] ?? '');
$city = (string) ($contact['city'] ?? '');
$fullAddress = trim("$street, $zip $city", ', ');
$mapsQuery = (string) ($contact['mapsQuery'] ?? '');
$phone = (string) ($contact['phone'] ?? '');
$whatsapp = preg_replace('/\D+/', '', $phone) ?? '';
?>
<?= Ui::pageHero('contact-hero', I18n::t('contact.title'), I18n::t('nav.contact'), I18n::t('contact.lead')) ?>

<?= Ui::sectionOpen('cream', '', 'anfrage') ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('contact.title')],
  ]) ?>

  <div class="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
    <div>
      <?php if ($sent) : ?>
        <div class="border border-gold/40 bg-sand/40 p-8 text-center">
          <div class="font-display text-2xl font-light text-ink">✓</div>
          <p class="mt-3 text-[0.95rem] leading-relaxed text-ink"><?= e(I18n::t('contact.success')) ?></p>
        </div>
      <?php else : ?>
        <?= View::partial('partials/contact-form', [
            'locale' => $locale,
            'preset' => '',
            'csrf'   => $csrf,
            'errors' => $errors,
            'values' => $values,
        ]) ?>
      <?php endif; ?>
    </div>

    <?= Ui::revealOpen(120) ?>
      <div class="border border-sand-deep bg-sand/40 p-8">
        <h2 class="text-[0.68rem] uppercase tracking-[0.22em] text-gold"><?= e(I18n::t('contact.directTitle')) ?></h2>
        <div class="mt-6 space-y-5 text-[0.95rem]">
          <div>
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted">Telefon</div>
            <a href="tel:<?= e($phone) ?>" class="mt-1 block text-ink hover:text-gold"><?= e((string) ($contact['phoneHuman'] ?? '')) ?></a>
          </div>
          <div>
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted">E-Mail</div>
            <a href="mailto:<?= e((string) ($contact['email'] ?? '')) ?>" class="mt-1 block text-ink hover:text-gold"><?= e((string) ($contact['email'] ?? '')) ?></a>
          </div>
          <div>
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted">WhatsApp</div>
            <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer" class="mt-1 block text-ink hover:text-gold"><?= e((string) ($contact['phoneHuman'] ?? '')) ?></a>
          </div>
          <div>
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('contact.studio')) ?></div>
            <address class="mt-1 not-italic text-ink"><?= e($street) ?><br><?= e($zip) ?> <?= e($city) ?></address>
          </div>
          <div>
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('contact.hours')) ?></div>
            <div class="mt-1 text-ink"><?= e(I18n::pick($contact['hours'] ?? null, $locale)) ?></div>
          </div>
        </div>
      </div>

      <?php /* Zwei-Klick-Karte: ohne Einwilligung wird nichts von Google geladen. */ ?>
      <div class="mt-6 border border-sand-deep p-6" data-map data-query="<?= e($mapsQuery !== '' ? $mapsQuery : $fullAddress) ?>">
        <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('contact.mapTitle')) ?></div>
        <p class="mt-3 text-[0.9rem] leading-relaxed text-ink"><?= e($fullAddress) ?></p>
        <p class="mt-3 text-[0.78rem] leading-relaxed text-muted"><?= e(I18n::t('contact.mapNote')) ?></p>
        <div class="mt-5 flex flex-wrap gap-3">
          <button type="button" data-map-load class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold"><?= e(I18n::t('contact.mapLoad')) ?></button>
          <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(rawurlencode($mapsQuery !== '' ? $mapsQuery : $fullAddress)) ?>"
             target="_blank" rel="noopener noreferrer"
             class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"><?= e(I18n::t('contact.mapRoute')) ?></a>
        </div>
      </div>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
