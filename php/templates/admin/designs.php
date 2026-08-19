<?php
/**
 * Katalog der zweiten Fassung im Panel.
 *
 * Die Kachel ist keine nachgebaute Vorschau, sondern dieselbe Karte, die der
 * Gast sieht - derselbe Weg wie im oeffentlichen Katalog. Was hier gut
 * aussieht, sieht auch dort gut aus, und was hier kaputt ist, faellt hier auf.
 *
 * @var list<array<string,mixed>> $designs
 * @var array<string,list<array{kind:string,element:string,detail:string}>> $warnings
 * @var string $styles
 * @var array<string,string> $values
 * @var list<string> $kategorien
 * @var string $filter
 * @var list<array<string,mixed>> $themen
 * @var string $csrf
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;

$tr    = $locale === 'tr';
$p     = static fn (string $to): string => I18n::path($to, $locale);
$label = 'text-[0.66rem] uppercase tracking-[0.16em] text-muted';

$ok     = (string) ($_GET['ok'] ?? '');
$fehler = (string) ($_GET['fehler'] ?? '');
$meldungen = [
    'kopiert'                => $tr ? 'Kopyalandı.' : 'Kopiert.',
    'uebernommen'            => $tr ? 'Temadan oluşturuldu.' : 'Aus dem Thema übernommen.',
    'uebernommen_ohne_kunst' => $tr
        ? 'Oluşturuldu — ama bu temanın çizilmiş sahnesi yok: php bin/export-scene-art.php ile dışa aktar.'
        : 'Übernommen – aber dieses Thema hat keine exportierte Szene: php bin/export-scene-art.php.',
    'aktiv'     => $tr ? 'Yayında.' : 'Veröffentlicht.',
    'inaktiv'   => $tr ? 'Yayından kaldırıldı.' : 'Aus der Veröffentlichung genommen.',
    'quelle'    => $tr ? 'Kaynak tasarım bulunamadı.' : 'Die Quellvorlage wurde nicht gefunden.',
    'thema'     => $tr ? 'Tema bulunamadı.' : 'Das Thema wurde nicht gefunden.',
    'name'      => $tr ? 'Ad boş olamaz.' : 'Der Name darf nicht leer sein.',
    'belegt'    => $tr ? 'Bu adla bir tasarım zaten var.' : 'Unter diesem Namen gibt es schon eine Vorlage.',
    'csrf'      => $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen, bitte neu laden.',
    'unbekannt' => $tr ? 'Tanınmayan işlem.' : 'Unbekannte Aktion.',
];
?>
<style><?= $styles ?></style>

<div class="space-y-8">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $tr ? 'Tasarımlar (v2)' : 'Designs (v2)' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $tr
        ? 'Her kart, müşterinin göreceği kartın kendisi. Düzenle dediğinde renkleri, yazıları ve metinleri değiştirirsin; yerleşim bu fazda sabit.'
        : 'Jede Kachel ist die Karte, die der Gast sieht. „Bearbeiten" ändert Farben, Schriften und Texte; die Anordnung bleibt in dieser Phase fest.' ?>
    </p>
  </div>

  <?php if ($ok !== '' && isset($meldungen[$ok])) : ?>
    <p class="border-l-2 border-gold px-4 py-3 text-sm text-ink"><?= e($meldungen[$ok]) ?></p>
  <?php endif; ?>
  <?php if ($fehler !== '' && isset($meldungen[$fehler])) : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700"><?= e($meldungen[$fehler]) ?></p>
  <?php endif; ?>

  <details class="border border-sand-deep">
    <summary class="cursor-pointer p-5 <?= $label ?>">
      <?= $tr ? 'Temadan yeni tasarım' : 'Neues Design aus einem Thema' ?>
    </summary>
    <form method="post" class="flex flex-wrap items-end gap-4 border-t border-sand-deep p-5">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="temadan">
      <label class="<?= $label ?>">
        <?= $tr ? 'Tema' : 'Thema' ?>
        <select name="thema" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
          <?php foreach ($themen as $t) : ?>
            <option value="<?= e((string) $t['id']) ?>"><?= e((string) $t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="<?= $label ?>">
        <?= $tr ? 'Yeni ad' : 'Neuer Name' ?>
        <input name="neuer_name" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
      </label>
      <button class="bg-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
        <?= $tr ? 'Oluştur' : 'Anlegen' ?>
      </button>
    </form>
  </details>

  <?php if ($kategorien !== []) : ?>
    <div class="flex flex-wrap items-center gap-4 <?= $label ?>">
      <a href="<?= e($p('/admin/designs')) ?>" class="<?= $filter === '' ? 'text-gold' : 'hover:text-ink' ?>">
        <?= $tr ? 'Hepsi' : 'Alle' ?>
      </a>
      <?php foreach ($kategorien as $k) : ?>
        <a href="<?= e($p('/admin/designs') . '?kategorie=' . rawurlencode($k)) ?>"
           class="<?= $filter === $k ? 'text-gold' : 'hover:text-ink' ?>"><?= e($k) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($designs === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu süzgeçle tasarım yok.' : 'Kein Design mit diesem Filter.' ?></p>
  <?php endif; ?>

  <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design) : ?>
      <?php
      $id  = (string) $design['id'];
      $ms  = $warnings[$id] ?? [];
      $akt = (string) $design['status'] === 'active';
      ?>
      <div class="border <?= $akt ? 'border-gold' : 'border-sand-deep' ?>">
        <div class="d-<?= e($id) ?> relative overflow-hidden"
             style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <?= Design::html($design, $values, $locale) ?>
        </div>

        <div class="space-y-3 p-5">
          <div class="flex items-baseline justify-between gap-3">
            <span class="font-display text-lg text-ink"><?= e($design['name']['de']) ?></span>
            <span class="text-[0.62rem] uppercase tracking-[0.16em] <?= $akt ? 'text-gold' : 'text-muted' ?>">
              <?= e((string) $design['status']) ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-x-4 gap-y-1 <?= $label ?>">
            <span><?= e((string) $design['category']) ?></span>
            <span><?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
            <span><?= count($design['layers']) ?> <?= $tr ? 'katman' : 'Ebenen' ?></span>
            <span class="<?= $ms === [] ? '' : 'text-gold' ?>">
              <?= $ms === [] ? ($tr ? 'uyarı yok' : 'keine Hinweise') : count($ms) . ($tr ? ' uyarı' : ' Hinweise') ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-2 pt-1 text-[0.62rem] uppercase tracking-[0.16em]">
            <a href="<?= e($p('/admin/designs/' . $design['slug'])) ?>"
               class="border border-ink px-3 py-2 text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $tr ? 'Düzenle' : 'Bearbeiten' ?>
            </a>
            <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank"
               class="border border-sand-deep px-3 py-2 text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Önizle' : 'Ansehen' ?>
            </a>
          </div>

          <form method="post" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="was" value="kopyala">
            <input type="hidden" name="quelle" value="<?= e($id) ?>">
            <input name="neuer_name" placeholder="<?= $tr ? 'kopyanın adı' : 'Name der Kopie' ?>"
                   class="w-56 border border-sand-deep bg-transparent px-2 py-2 text-[0.7rem] text-ink">
            <button class="border border-sand-deep px-3 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Kopyala' : 'Kopieren' ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
