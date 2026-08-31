<?php
/**
 * Preise: drei Pakete, Zusatzleistungen, häufige Fragen.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $packages
 * @var list<array<string,mixed>> $addons
 * @var list<array<string,mixed>> $faq
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Packages;
use Atelier\Ui;

$p = static fn (string $to): string => I18n::path($to, $locale);
?>
<?= Ui::pageHero('prices-hero', I18n::t('prices.title'), I18n::t('nav.prices'), I18n::t('prices.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([
      ['name' => 'Home', 'href' => $p('')],
      ['name' => I18n::t('prices.title')],
  ]) ?>

  <?php /*
     Ein Formular um Pakete, Zusatzleistungen und Summe.

     "Tikladikca paket fiyati oynamasi lazim (...) musteri ne odeyecek, ne
     alacak gorsun, forma eklensin."

     method="get" und nicht post: die Auswahl ist keine Anfrage, sondern die
     Vorbereitung einer Anfrage. So funktioniert die Seite auch ohne Skript -
     dann faellt nur die mitlaufende Summe weg, und die Auswahl kommt trotzdem
     am Kontaktformular an, wo der Server sie zusammenrechnet.
  */ ?>
  <form method="get" action="<?= e($p('/kontakt')) ?>" data-preisrechner>

  <div class="grid gap-6 lg:grid-cols-3">
    <?php foreach ($packages as $i => $package) : ?>
      <?php $featured = (bool) ($package['featured'] ?? false); ?>
      <?= Ui::revealOpen($i * 90) ?>
        <div class="flex h-full flex-col border p-8 transition-colors <?= $featured ? 'border-gold bg-sand/40' : 'border-sand-deep hover:border-muted' ?>">
          <?php if ($featured) : ?>
            <div class="mb-4 inline-block self-start bg-gold px-3 py-1 text-[0.6rem] uppercase tracking-[0.2em] text-white"><?= e(I18n::t('prices.popular')) ?></div>
          <?php endif; ?>

          <h2 class="font-display text-2xl font-light text-ink"><?= e(I18n::pick($package['name'] ?? null, $locale)) ?></h2>
          <div class="mt-4 flex items-baseline gap-2">
            <span class="text-[0.7rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('prices.from')) ?></span>
            <span class="font-display text-4xl font-light text-ink"><?= e((string) ($package['price'] ?? '')) ?></span>
          </div>
          <div class="mt-2 text-[0.8rem] text-muted"><?= e(I18n::pick($package['hint'] ?? null, $locale)) ?></div>

          <?= Ui::bullets(I18n::pickList($package['features'] ?? null, $locale), 'prose-lux mt-7 flex-1') ?>

          <?php /*
             Die Wahl steht da, wo bisher der Knopf stand. Der Knopf ist nicht
             verschwunden - er steht jetzt einmal unter der Summe, und das ist
             die Stelle, an der man weiss, was man anfragt.

             Der Preis reist als data-cent mit: gerechnet wird trotzdem auf dem
             Server (Packages::summary), das hier ist die Anzeige waehrend des
             Klickens. Ein Posten ohne rechenbaren Preis traegt kein Attribut -
             er ist waehlbar und faellt aus der Summe, und die Seite sagt es.
          */ ?>
          <?php $cent = Packages::amount((string) ($package['price'] ?? '')); ?>
          <label class="mt-8 flex cursor-pointer items-center gap-3 border border-sand-deep px-5 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-ink">
            <input type="radio" name="paket" value="<?= (int) $i ?>"
                   class="h-4 w-4 shrink-0 accent-[#B08D57]"
                   <?= $cent === null ? '' : 'data-cent="' . (int) $cent . '"' ?>>
            <?= e(I18n::t('prices.choose')) ?>
          </label>
        </div>
      <?= Ui::revealClose() ?>
    <?php endforeach; ?>
  </div>

  <?= Ui::revealOpen(120, 'mt-16') ?>
    <h2 class="font-display text-2xl font-light text-ink"><?= e(I18n::t('prices.addonsTitle')) ?></h2>
    <div class="mt-6 divide-y divide-sand-deep border-y border-sand-deep">
      <?php foreach ($addons as $j => $addon) : ?>
        <?php $cent = Packages::amount((string) ($addon['price'] ?? '')); ?>
        <label class="flex cursor-pointer items-center justify-between gap-6 py-4">
          <span class="flex items-center gap-3">
            <input type="checkbox" name="extra[]" value="<?= (int) $j ?>"
                   class="h-4 w-4 shrink-0 accent-[#B08D57]"
                   <?= $cent === null ? '' : 'data-cent="' . (int) $cent . '"' ?>>
            <span class="text-[0.95rem] text-ink-soft"><?= e(I18n::pick($addon['name'] ?? null, $locale)) ?></span>
          </span>
          <span class="shrink-0 font-display text-lg text-gold"><?= e((string) ($addon['price'] ?? '')) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="mt-6 text-[0.82rem] leading-relaxed text-muted"><?= e(I18n::t('prices.note')) ?></p>

    <?php /*
       Die Summe. Sie steht hidden im Markup und wird vom Skript aufgedeckt:
       ohne Skript gibt es keine mitlaufende Zahl, und ein leeres Feld mit der
       Ueberschrift "Gesamt" waere eine Auskunft, die keine ist. Die Auswahl
       kommt dann trotzdem am Kontaktformular an, und dort rechnet der Server.
    */ ?>
    <div class="mt-10 border border-sand-deep bg-sand/40 p-8">
      <div class="flex items-baseline justify-between gap-4" data-preis-zeile hidden>
        <span class="text-[0.7rem] uppercase tracking-[0.18em] text-muted"><?= e(I18n::t('prices.sumTotal')) ?></span>
        <span class="font-display text-3xl font-light text-ink" data-preis-summe></span>
      </div>
      <p class="text-[0.82rem] leading-relaxed text-muted" data-preis-leer><?= e(I18n::t('prices.sumEmpty')) ?></p>
      <p class="mt-2 text-[0.82rem] leading-relaxed text-muted" data-preis-offen hidden><?= e(I18n::t('prices.sumOpen')) ?></p>
      <button type="submit" class="mt-6 w-full bg-ink px-8 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold sm:w-auto">
        <?= e(I18n::t('prices.cta')) ?>
      </button>
    </div>
  <?= Ui::revealClose() ?>

  </form>
<?= Ui::sectionClose() ?>

<?= Ui::sectionOpen('sand') ?>
  <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
    <?= Ui::head(I18n::t('home.faqTitle'), 'FAQ') ?>
    <?= Ui::revealOpen(100) ?>
      <?= Ui::accordion(array_map(
          static fn (array $f): array => [
              'q' => I18n::pick($f['q'] ?? null, $locale),
              'a' => I18n::pick($f['a'] ?? null, $locale),
          ],
          $faq
      )) ?>
    <?= Ui::revealClose() ?>
  </div>
<?= Ui::sectionClose() ?>
