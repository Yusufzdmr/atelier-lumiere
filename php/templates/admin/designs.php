<?php
/**
 * Liste der Designs der zweiten Fassung. Nur lesen (Faz 1).
 *
 * Die Klassen kommen aus der Palette des Panels (text-muted, border-sand-deep,
 * text-gold). Tailwinds Standardgrau steht nicht in der kompilierten CSS und
 * waere hier wirkungslos.
 *
 * @var list<array<string,mixed>> $designs
 * @var array<string,list<array{kind:string,element:string,detail:string}>> $warnings
 * @var string $locale
 */

use Atelier\I18n;
use function Atelier\e;

$tr = $locale === 'tr';
?>
<h2 class="font-display text-xl text-ink"><?= $tr ? 'Tasarımlar (v2)' : 'Designs (v2)' ?></h2>

<p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
  <?= $tr
    ? 'Davetiye sisteminin ikinci sürümü. Bu listede düzenleme yok — Faz 1 yalnızca formatı ve gösterimi kuruyor. Yeni kayıt için: php bin/seed-designs.php'
    : 'Die zweite Fassung des Einladungssystems. Hier wird nur gelesen – Faz 1 baut Format und Darstellung. Neue Einträge: php bin/seed-designs.php' ?>
</p>

<?php if ($designs === []): ?>
  <p class="mt-8 text-sm text-muted">
    <?= $tr ? 'Henüz tasarım yok.' : 'Noch kein Design.' ?>
  </p>
<?php else: ?>
  <table class="mt-8 w-full text-sm">
    <thead class="border-b border-sand-deep text-left text-[0.62rem] uppercase tracking-[0.16em] text-muted">
      <tr>
        <th class="py-2"><?= $tr ? 'Ad' : 'Name' ?></th>
        <th class="py-2"><?= $tr ? 'Kategori' : 'Kategorie' ?></th>
        <th class="py-2"><?= $tr ? 'Durum' : 'Zustand' ?></th>
        <th class="py-2"><?= $tr ? 'Sürüm' : 'Fassung' ?></th>
        <th class="py-2"><?= $tr ? 'Element' : 'Elemente' ?></th>
        <th class="py-2"><?= $tr ? 'Uyarı' : 'Hinweise' ?></th>
        <th class="py-2"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($designs as $design): ?>
        <?php $meldungen = $warnings[$design['id']] ?? []; ?>
        <tr class="border-b border-sand-deep">
          <td class="py-3 text-ink"><?= e($design['name']['de']) ?></td>
          <td class="py-3 text-muted"><?= e($design['category']) ?></td>
          <td class="py-3 text-muted"><?= e($design['status']) ?></td>
          <td class="py-3 text-muted"><?= (int) $design['version'] ?></td>
          <td class="py-3 text-muted"><?= count($design['layers']) ?></td>
          <td class="py-3 <?= $meldungen === [] ? 'text-muted' : 'text-gold' ?>">
            <?= $meldungen === [] ? '—' : count($meldungen) ?>
          </td>
          <td class="py-3 text-right">
            <a class="underline" target="_blank"
               href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>">
              <?= $tr ? 'Önizle' : 'Ansehen' ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
