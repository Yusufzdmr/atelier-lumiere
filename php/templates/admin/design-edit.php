<?php
/**
 * Editor der zweiten Fassung. Bearbeitet die Oberflaeche: Farben, Schriften,
 * Texte, Bilder, Bewegung, Kundenrechte. Die Kaesten der Ebenen stehen NICHT
 * hier - sie gehoeren der vierten Phase, und tests/design_admin.php haelt die
 * Grenze.
 *
 * @var array<string,mixed> $design
 * @var string $scope
 * @var string $styles
 * @var string $karte
 * @var string $seite
 * @var list<array{kind:string,element:string,detail:string}> $warnings
 * @var string $csrf
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use Atelier\Themes;
use function Atelier\e;

$tr    = $locale === 'tr';
$p     = static fn (string $to): string => I18n::path($to, $locale);
$label = 'text-[0.66rem] uppercase tracking-[0.16em] text-muted';
$feld  = 'mt-1 block w-full border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink';

$ok     = (string) ($_GET['ok'] ?? '');
$fehler = (string) ($_GET['fehler'] ?? '');

// Ein Abschnitt: zu, bis jemand ihn braucht. Élysée hat vierzehn Ebenen, und
// alle Felder gleichzeitig offen sind eine Wand.
$auf = static function (string $titel): string {
    return '<details class="border border-sand-deep"><summary class="cursor-pointer p-5 text-[0.66rem] uppercase tracking-[0.16em] text-muted">'
         . e($titel) . '</summary><div class="space-y-5 border-t border-sand-deep p-5">';
};
$zu = '</div></details>';

$textEbenen = array_filter($design['layers'], static fn (array $l): bool => $l['type'] === 'text' && (string) $l['bind'] === '');
$bindEbenen = array_filter($design['layers'], static fn (array $l): bool => (string) $l['bind'] !== '');
$bildEbenen = array_filter($design['layers'], static fn (array $l): bool => in_array($l['type'], ['image', 'photo'], true));
?>
<style><?= $styles ?></style>

<div class="space-y-8">
  <div class="flex flex-wrap items-baseline justify-between gap-4">
    <h2 class="font-display text-xl text-ink"><?= e($design['name']['de']) ?></h2>
    <a href="<?= e($p('/admin/designs')) ?>" class="<?= $label ?> hover:text-ink">
      <?= $tr ? 'Kataloğa dön' : 'Zum Katalog' ?>
    </a>
  </div>

  <?php if ($ok === 'gespeichert') : ?>
    <p class="border-l-2 border-gold px-4 py-3 text-sm text-ink"><?= $tr ? 'Kaydedildi.' : 'Gespeichert.' ?></p>
  <?php endif; ?>
  <?php if ($fehler === 'veraltet') : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700">
      <?= $tr
        ? 'Bu tasarım sen açtıktan sonra başka bir yerde değiştirildi. Sayfayı tazele, sonra yeniden dene — yoksa onun işini silersin.'
        : 'Diese Vorlage wurde geändert, nachdem du sie geöffnet hast. Bitte neu laden und noch einmal – sonst überschreibst du fremde Arbeit.' ?>
    </p>
  <?php endif; ?>
  <?php if ($fehler === 'csrf') : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700"><?= $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen.' ?></p>
  <?php endif; ?>

  <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
    <form method="post" class="space-y-4" data-design-form>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="kaydet">
      <input type="hidden" name="version" value="<?= (int) $design['version'] ?>">

      <?php include __DIR__ . '/design-edit-sections.php'; ?>

      <div class="sticky bottom-0 flex flex-wrap items-center gap-3 border-t border-sand-deep bg-cream py-4">
        <button class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          <?= $tr ? 'Kaydet' : 'Speichern' ?>
        </button>
        <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank"
           class="border border-sand-deep px-5 py-3 <?= $label ?> hover:text-ink">
          <?= $tr ? 'Tam ekran aç' : 'Ganz ansehen' ?>
        </a>
        <span class="<?= $label ?>"><?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
      </div>
    </form>

    <?php /*
       Die Vorschau ist die Karte selbst, nicht ihr Abbild: derselbe Stilblock,
       dasselbe Markup wie auf der oeffentlichen Seite, nur kleiner. Das Skript
       aendert daran ausschliesslich CSS-Variablen und Textknoten.
    */ ?>
    <div class="lg:sticky lg:top-28">
      <div class="<?= e($scope) ?> relative overflow-hidden border border-sand-deep"
           data-design-preview
           style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                  background: var(--d-bg, #EFE7DC);">
        <div class="absolute inset-0"><?= $seite ?></div>
        <div class="absolute inset-0"><?= $karte ?></div>
      </div>
      <p class="mt-3 <?= $label ?>">
        <?= $tr
          ? 'Renk, yazı ve metin anında değişir. Hareket ve görsel için kaydet.'
          : 'Farbe, Schrift und Text ändern sich sofort. Bewegung und Bild brauchen ein Speichern.' ?>
      </p>
    </div>
  </div>
</div>
