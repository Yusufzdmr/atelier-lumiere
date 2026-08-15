<?php
/**
 * Schaufenster der Einladungsdesigns.
 *
 * Die Kacheln sind keine nachgebauten Farbtupfer, sondern dieselbe Karte, die
 * der Gast spaeter sieht – mit den Farben, Schriften und dem Siegel des
 * Themas. Wer mehr will, oeffnet die Vorschau: eine vollstaendige Einladung
 * mit erfundenem Paar.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $themes
 * @var string $styles  Stilblock aller Themen, siehe Themes::styleBlock()
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Ui;

$de = $locale === 'de';
$p = static fn (string $to): string => I18n::path($to, $locale);
?>

<style><?= $styles ?></style>

<?= Ui::pageHero(
      'designs-hero',
      $de ? 'Wählt euer Design' : 'Tasarımınızı seçin',
      $de ? 'Designs' : 'Tasarımlar',
      $de
        ? 'Jede Vorlage bringt ihre eigene Farbwelt, ihr Kuvert und ihr Siegel mit. Schaut sie in Ruhe an – ändern lässt sich später alles.'
        : 'Her şablonun kendi renk dünyası, zarfı ve mührü var. Acele etmeden bakın — sonradan hepsi değiştirilebilir.'
    ) ?>

<section class="mx-auto max-w-7xl px-5 pb-24 sm:px-8">
  <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($themes as $theme) : ?>
      <?php
      $id = (string) ($theme['id'] ?? '');
      $name = (string) ($theme['name'] ?? '');
      $sub = (string) (($theme['sub'] ?? [])[$locale] ?? '');
      // Zwei Buchstaben wie auf dem echten Siegel des Beispielpaares.
      $initials = $de ? 'MJ' : 'EK';
      ?>
      <article class="theme-<?= e($id) ?> group">
        <a href="<?= e($p('/designs/' . $id)) ?>" class="block"
           aria-label="<?= e(($de ? 'Vorschau öffnen: ' : 'Önizlemeyi aç: ') . $name) ?>">

          <div class="t-card relative flex aspect-[4/5] items-center justify-center overflow-hidden border transition-transform duration-500 group-hover:-translate-y-1"
               style="background: var(--t-bg); border-color: var(--t-paper-edge);">

            <div class="relative flex h-[82%] w-[78%] flex-col items-center justify-center border px-6 text-center"
                 style="background: var(--t-paper); border-color: var(--t-paper-edge);">

              <span class="flex h-11 w-11 items-center justify-center rounded-full text-[0.72rem] tracking-[0.14em]"
                    style="background: var(--t-seal); color: var(--t-seal-text);"><?= e($initials) ?></span>

              <span class="mt-5 text-[0.56rem] uppercase tracking-[0.3em]" style="color: var(--t-soft);">
                <?= $de ? 'Wir heiraten' : 'Evleniyoruz' ?>
              </span>

              <span class="font-display mt-3 text-2xl leading-tight" style="color: var(--t-fg);">
                <?= $de ? 'Marie' : 'Elif' ?>
              </span>
              <span class="font-display text-lg" style="color: var(--t-accent);">&amp;</span>
              <span class="font-display text-2xl leading-tight" style="color: var(--t-fg);">
                <?= $de ? 'Jonas' : 'Kerem' ?>
              </span>

              <span class="mt-4 block h-px w-16" style="background: var(--t-accent);"></span>

              <span class="mt-4 text-[0.62rem] tracking-[0.14em]" style="color: var(--t-soft);">
                20.06.<?= e((string) (((int) date('Y')) + 1)) ?>
              </span>
            </div>
          </div>

          <div class="mt-4 flex items-baseline justify-between gap-3">
            <div>
              <h2 class="font-display text-lg text-ink"><?= e($name) ?></h2>
              <?php if ($sub !== '') : ?>
                <p class="mt-0.5 text-[0.74rem] text-muted"><?= e($sub) ?></p>
              <?php endif; ?>
            </div>
            <span class="whitespace-nowrap text-[0.62rem] uppercase tracking-[0.18em] text-gold">
              <?= $de ? 'Ansehen' : 'Bak' ?> →
            </span>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="mt-16 border-t border-sand-deep pt-10 text-center">
    <p class="text-sm text-muted">
      <?= $de
        ? 'Farben, Schriften und Verzierungen lassen sich in jeder Vorlage anpassen.'
        : 'Renkler, yazı tipleri ve süslemeler her şablonda değiştirilebilir.' ?>
    </p>
    <a href="<?= e($p('/einladung')) ?>"
       class="mt-6 inline-block bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $de ? 'Einladung erstellen' : 'Davetiye oluştur' ?>
    </a>
  </div>
</section>
