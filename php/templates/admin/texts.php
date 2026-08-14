<?php
/**
 * Die festen Seitentexte, gruppenweise.
 *
 * Links steht immer, was ursprünglich da stand – das ist der eigentliche Punkt
 * dieser Seite: ändern zu können, ohne den alten Wortlaut zu verlieren.
 *
 * @var string $locale
 * @var string $title
 * @var string $intro
 * @var string $group
 * @var string $caption
 * @var list<array{key:string,label:string,changed:int,active:bool}> $groups
 * @var list<array{key:string,original:array<string,string>,current:array<string,string>,changed:bool}> $entries
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Texts;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2 text-[0.9rem] text-ink outline-none focus:border-gold';
$changedCount = 0;
foreach ($entries as $entry) {
    if ($entry['changed']) {
        $changedCount++;
    }
}
?>
<form method="post" class="space-y-8">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="was" value="save">

  <div>
    <h2 class="font-display text-xl text-ink"><?= e($title) ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted"><?= e($intro) ?></p>
  </div>

  <?php /* ---------------------------- Gruppenleiste --------------------------- */ ?>
  <nav class="flex flex-wrap gap-2 border-y border-sand-deep py-4">
    <?php foreach ($groups as $item) : ?>
      <a href="?gruppe=<?= e($item['key']) ?>"
         class="border px-3.5 py-2 text-[0.68rem] uppercase tracking-[0.14em] transition-colors <?= $item['active']
           ? 'border-gold bg-sand/50 text-ink'
           : 'border-sand-deep text-muted hover:border-gold hover:text-ink' ?>">
        <?= e($item['label']) ?>
        <?php if ($item['changed'] > 0) : ?>
          <span class="ml-1.5 text-gold">·&nbsp;<?= $item['changed'] ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="flex flex-wrap items-baseline justify-between gap-3">
    <h3 class="font-display text-lg text-ink"><?= e($caption) ?></h3>
    <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">
      <?= count($entries) ?> <?= $de ? 'Texte' : 'metin' ?><?php if ($changedCount > 0) : ?>
        · <span class="text-gold"><?= $changedCount ?> <?= $de ? 'geändert' : 'değişti' ?></span>
      <?php endif; ?>
    </p>
  </div>

  <?php /* ------------------------------- Die Texte ---------------------------- */ ?>
  <div class="space-y-px bg-sand-deep">
    <?php foreach ($entries as $entry) : ?>
      <?php $key = $entry['key']; ?>
      <div class="bg-cream p-5 <?= $entry['changed'] ? 'border-l-2 border-gold' : '' ?>">
        <div class="grid gap-6 md:grid-cols-2">
          <?php foreach (I18n::LOCALES as $lang) : ?>
            <?php
              $name = Texts::field($key, $lang);
              $original = $entry['original'][$lang];
              $current = $entry['current'][$lang];
              $differs = $original !== '' && $current !== $original;
              // Mehrzeilige Texte brauchen ein Textfeld, sonst sieht man nur den Anfang.
              $long = mb_strlen($current) > 90 || str_contains($current, "\n");
            ?>
            <div>
              <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="<?= e($name) ?>">
                <?= e(strtoupper($lang)) ?>
              </label>

              <?php if ($long) : ?>
                <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="<?= max(2, min(8, (int) ceil(mb_strlen($current) / 70) + 1)) ?>"
                          class="<?= $input ?> resize-y"><?= e($current) ?></textarea>
              <?php else : ?>
                <input id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($current) ?>" class="<?= $input ?>">
              <?php endif; ?>

              <?php if ($differs) : ?>
                <div class="mt-2 flex flex-wrap items-start gap-x-3 gap-y-1 border-l-2 border-sand-deep pl-3">
                  <p class="min-w-0 flex-1 whitespace-pre-line text-[0.72rem] leading-relaxed text-muted">
                    <span class="uppercase tracking-[0.14em]"><?= $de ? 'Vorher' : 'Öncesi' ?>:</span>
                    <?= e(mb_strlen($original) > 220 ? mb_substr($original, 0, 220) . ' …' : $original) ?>
                  </p>
                  <button name="was" value="reset:<?= e($key) ?>|<?= e($lang) ?>"
                          class="shrink-0 text-[0.62rem] uppercase tracking-[0.14em] text-muted underline-offset-4 hover:text-gold hover:underline">
                    <?= $de ? 'zurücksetzen' : 'geri al' ?>
                  </button>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-3 font-mono text-[0.62rem] text-muted/70"><?= e($key) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($entries === []) : ?>
    <p class="border border-sand-deep p-5 text-sm text-muted"><?= $de ? 'Diese Gruppe ist leer.' : 'Bu grup boş.' ?></p>
  <?php endif; ?>

  <div class="sticky bottom-0 -mb-8 flex flex-wrap items-center gap-4 border-t border-sand-deep bg-cream/95 py-4 backdrop-blur lg:-mb-10">
    <button class="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $de ? 'Speichern' : 'Kaydet' ?>
    </button>
    <span class="text-[0.72rem] text-muted">
      <?= $de ? 'Gilt für: ' : 'Kapsam: ' ?><?= e($caption) ?>
    </span>
  </div>
</form>
