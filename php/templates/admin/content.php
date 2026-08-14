<?php
/**
 * Allgemeine Inhaltsseite des Adminbereichs.
 *
 * Bekommt Abschnitte mit Feldbeschreibungen und macht daraus ein Formular –
 * so sehen Texte, Preise, „Über mich“ und SEO gleich aus und verhalten sich
 * gleich.
 *
 * @var string $locale
 * @var string $title
 * @var string $intro
 * @var list<array{title:string,hint?:string,fields:list<array<string,mixed>>,grid?:string}> $sections
 * @var array<string,mixed> $data
 * @var array<string,mixed> $originals  Stand beim Einspielen – für „zurücksetzen“
 * @var string $csrf
 * @var string $reset  optionaler Wert für „auf Standard zurücksetzen“
 */

use function Atelier\e;
use Atelier\Form;

$de = $locale === 'de';
?>
<form method="post" class="space-y-10">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="was" value="save">

  <div>
    <h2 class="font-display text-xl text-ink"><?= e($title) ?></h2>
    <?php if ($intro !== '') : ?>
      <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted"><?= e($intro) ?></p>
    <?php endif; ?>
  </div>

  <?php foreach ($sections as $section) : ?>
    <section class="border border-sand-deep p-6">
      <h3 class="font-display text-xl text-ink"><?= e($section['title']) ?></h3>
      <?php if (($section['hint'] ?? '') !== '') : ?>
        <p class="mt-2 max-w-3xl text-[0.78rem] leading-relaxed text-muted"><?= e($section['hint']) ?></p>
      <?php endif; ?>

      <div class="mt-6">
        <?= Form::fields($section['fields'], $data, $section['grid'] ?? 'md:grid-cols-2', $originals ?? []) ?>
      </div>
    </section>
  <?php endforeach; ?>

  <?php /* Die Reiter sind lang; der Knopf soll nicht am Ende einer Reise stehen. */ ?>
  <div class="sticky bottom-0 -mb-8 flex flex-wrap items-center gap-4 border-t border-sand-deep bg-cream/95 py-4 backdrop-blur lg:-mb-10">
    <button class="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $de ? 'Speichern' : 'Kaydet' ?>
    </button>

    <?php if (($reset ?? '') !== '') : ?>
      <button name="was" value="<?= e($reset) ?>"
              data-confirm="<?= $de ? 'Wirklich auf die Standardtexte zurücksetzen?' : 'Varsayılan metinlere dönülsün mü?' ?>"
              class="px-4 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-muted hover:text-gold">
        <?= $de ? 'Auf Standard zurücksetzen' : 'Varsayılana döndür' ?>
      </button>
    <?php endif; ?>
  </div>
</form>
