<?php
/**
 * Die acht Abschnitte des Editors. Liegt neben design-edit.php und nicht darin:
 * das Geruest mit der Vorschau ist eine Sache, die Felder sind eine andere, und
 * beide zusammen waeren eine Datei, die niemand mehr ueberblickt.
 *
 * Erwartet aus design-edit.php: $design, $tr, $label, $feld, $auf, $zu,
 * $textEbenen, $bindEbenen, $bildEbenen.
 *
 * @var array<string,mixed> $design
 */

use Atelier\Design;
use Atelier\Themes;
use function Atelier\e;
?>

<?= $auf($tr ? '1 · Genel' : '1 · Allgemein') ?>
  <div class="grid gap-5 sm:grid-cols-2">
    <label class="<?= $label ?>">DE
      <input name="name_de" value="<?= e($design['name']['de']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>">EN
      <input name="name_en" value="<?= e($design['name']['en']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>"><?= $tr ? 'Kategori' : 'Kategorie' ?>
      <input name="category" value="<?= e((string) $design['category']) ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?>"><?= $tr ? 'Sıra' : 'Reihenfolge' ?>
      <input name="sort" type="number" value="<?= (int) $design['sort'] ?>" class="<?= $feld ?>"></label>
    <label class="<?= $label ?> sm:col-span-2"><?= $tr ? 'Etiketler (virgülle)' : 'Schlagworte (mit Komma)' ?>
      <input name="tags" value="<?= e(implode(', ', $design['tags'])) ?>" class="<?= $feld ?>"></label>
  </div>
<?= $zu ?>

<?= $auf($tr ? '2 · Renkler' : '2 · Farben') ?>
  <div class="grid gap-5 sm:grid-cols-2">
    <?php foreach ($design['palette'] as $marke => $eintrag) : ?>
      <?php $istHex = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $eintrag['value']) === 1; ?>
      <div>
        <span class="<?= $label ?>"><?= e($eintrag['label']['de'] ?? $marke) ?></span>
        <div class="mt-1 flex items-center gap-2">
          <input type="color" value="<?= e($istHex ? (string) $eintrag['value'] : '#B08D57') ?>"
                 class="h-9 w-10 shrink-0 cursor-pointer border border-sand-deep bg-transparent p-0"
                 data-farbwahl="<?= e($marke) ?>" <?= $istHex ? '' : 'title="rgba"' ?>>
          <input name="palette_<?= e($marke) ?>" value="<?= e((string) $eintrag['value']) ?>"
                 class="<?= $feld ?> font-mono text-[0.8rem]" data-farbfeld="<?= e($marke) ?>">
        </div>
        <label class="mt-2 flex items-center gap-2 text-[0.66rem] text-muted">
          <input type="checkbox" name="palette_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
          <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf ändern' ?>
        </label>
      </div>
    <?php endforeach; ?>
  </div>
<?= $zu ?>

<?= $auf($tr ? '3 · Yazı tipleri' : '3 · Schriften') ?>
  <?php foreach ($design['fonts'] as $marke => $eintrag) : ?>
    <div class="space-y-3 border-b border-sand-deep pb-4">
      <div class="grid gap-4 sm:grid-cols-4">
        <label class="<?= $label ?>"><?= e($marke) ?>
          <select name="font_family_<?= e($marke) ?>" class="<?= $feld ?>" data-schriftfeld="<?= e($marke) ?>">
            <?php foreach (['Cormorant Garamond', 'Jost', 'Great Vibes'] as $familie) : ?>
              <option value="<?= e($familie) ?>" <?= $eintrag['family'] === $familie ? 'selected' : '' ?>><?= e($familie) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label class="<?= $label ?>"><?= $tr ? 'ağırlık' : 'Gewicht' ?>
          <input name="font_weight_<?= e($marke) ?>" type="number" step="100" min="100" max="900"
                 value="<?= (int) $eintrag['weight'] ?>" class="<?= $feld ?>" data-gewichtfeld="<?= e($marke) ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'laufweite' : 'Laufweite' ?>
          <input name="font_tracking_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['tracking'] ?>" class="<?= $feld ?>"></label>
        <label class="<?= $label ?>"><?= $tr ? 'satır yüksekliği' : 'Zeilenhöhe' ?>
          <input name="font_line_<?= e($marke) ?>" type="number" value="<?= (int) $eintrag['lineHeight'] ?>" class="<?= $feld ?>"></label>
      </div>
      <label class="flex items-center gap-2 text-[0.66rem] text-muted">
        <input type="checkbox" name="font_customer_<?= e($marke) ?>" <?= $eintrag['customer'] ? 'checked' : '' ?>>
        <?= $tr ? 'müşteri değiştirebilir' : 'Kunde darf ändern' ?>
      </label>
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?= $auf($tr ? '4 · Metinler' : '4 · Texte') ?>
  <?php foreach ($textEbenen as $ebene) : ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <label class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?> · DE
        <input name="text_de_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['de']) ?>"
               class="<?= $feld ?>" data-textfeld="<?= e($ebene['id']) ?>"></label>
      <label class="<?= $label ?>">EN
        <input name="text_en_<?= e($ebene['id']) ?>" value="<?= e($ebene['text']['en']) ?>" class="<?= $feld ?>"></label>
    </div>
  <?php endforeach; ?>
  <?php if ($bindEbenen !== []) : ?>
    <p class="<?= $label ?>"><?= $tr ? 'Çiftin verisinden gelenler (düzenlenemez):' : 'Kommt aus den Daten des Paares (nicht editierbar):' ?></p>
    <ul class="space-y-1 text-sm text-muted">
      <?php foreach ($bindEbenen as $ebene) : ?>
        <li><?= e($ebene['label'] ?: $ebene['id']) ?> — <code><?= e((string) $ebene['bind']) ?></code></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?= $zu ?>

<?= $auf($tr ? '5 · Görseller' : '5 · Bilder') ?>
  <?php if ($bildEbenen === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu tasarımda görsel katman yok.' : 'Diese Vorlage hat keine Bildebene.' ?></p>
  <?php endif; ?>
  <?php foreach ($bildEbenen as $ebene) : ?>
    <label class="<?= $label ?>"><?= e($ebene['label'] ?: $ebene['id']) ?>
      <input name="src_<?= e($ebene['id']) ?>" value="<?= e((string) $ebene['src']) ?>"
             class="<?= $feld ?> font-mono text-[0.78rem]"></label>
  <?php endforeach; ?>
  <p class="<?= $label ?>">
    <?= $tr
      ? 'assets/designs/ altındaki dosyalar dışa aktarma betiğinin ürünü; elle değiştirme, sonraki export ezer.'
      : 'Was unter assets/designs/ liegt, erzeugt das Exportskript; von Hand geändert, überschreibt es der nächste Export.' ?>
  </p>
<?= $zu ?>

<?= $auf($tr ? '6 · Animasyon' : '6 · Bewegung') ?>
  <?php
  $achsen = [
      'anim_intro'    => [$tr ? 'Giriş' : 'Auftakt',      Themes::INTROS,                 (string) $design['animation']['intro']],
      'anim_idle'     => [$tr ? 'Boşta' : 'Ruhe',         Themes::IDLES,                  (string) $design['animation']['idle']],
      'anim_card'     => [$tr ? 'Kart' : 'Karte',         array_keys(Themes::ANIMATIONS), (string) $design['animation']['card']],
      'anim_name'     => [$tr ? 'İsimler' : 'Namen',      Themes::NAME_ANIMATIONS,        (string) $design['animation']['nameMove']],
      'anim_particle' => [$tr ? 'Partikül' : 'Teilchen',  Themes::PARTICLES,              (string) $design['animation']['particle']],
      'anim_reveal'   => [$tr ? 'Açılış' : 'Enthüllung',  Themes::REVEALS,                (string) $design['animation']['reveal']],
  ];
  ?>
  <div class="grid gap-4 sm:grid-cols-3">
    <?php foreach ($achsen as $name => [$titel, $liste, $wert]) : ?>
      <label class="<?= $label ?>"><?= e($titel) ?>
        <select name="<?= e($name) ?>" class="<?= $feld ?>">
          <?php foreach ($liste as $option) : ?>
            <option value="<?= e((string) $option) ?>" <?= $wert === (string) $option ? 'selected' : '' ?>><?= e((string) $option) ?></option>
          <?php endforeach; ?>
        </select></label>
    <?php endforeach; ?>
    <label class="<?= $label ?>"><?= $tr ? 'hız (ms)' : 'Tempo (ms)' ?>
      <input name="anim_speed" type="number" value="<?= (int) $design['animation']['speed'] ?>" class="<?= $feld ?>"></label>
  </div>

  <p class="<?= $label ?>"><?= $tr ? 'Katman hareketleri' : 'Bewegung je Ebene' ?></p>
  <?php foreach ($design['layers'] as $ebene) : ?>
    <div class="grid items-center gap-3 sm:grid-cols-4">
      <span class="text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
      <select name="move_<?= e($ebene['id']) ?>" class="<?= $feld ?>">
        <?php foreach (Themes::MOVES as $m) : ?>
          <option value="<?= e($m) ?>" <?= $ebene['motion']['move'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="delay_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['delay'] ?>" class="<?= $feld ?>">
      <input name="duration_<?= e($ebene['id']) ?>" type="number" value="<?= (int) $ebene['motion']['duration'] ?>" class="<?= $feld ?>">
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?= $auf($tr ? '7 · Müşteri izinleri' : '7 · Kundenrechte') ?>
  <p class="<?= $label ?>">
    <?= $tr ? 'Faz 3 sihirbazı bu bayrakları okuyacak: müşteri neye dokunabilir.' : 'Der Assistent der dritten Phase liest diese Haken: was der Kunde anfassen darf.' ?>
  </p>
  <?php foreach ($design['layers'] as $ebene) : ?>
    <div class="flex flex-wrap items-center gap-4 border-b border-sand-deep py-2">
      <span class="w-56 text-sm text-ink"><?= e($ebene['label'] ?: $ebene['id']) ?></span>
      <?php foreach (Design::PERMISSIONS as $recht) : ?>
        <label class="flex items-center gap-2 text-[0.66rem] text-muted">
          <input type="checkbox" name="perm_<?= e($recht) ?>_<?= e($ebene['id']) ?>" <?= $ebene['permissions'][$recht] ? 'checked' : '' ?>>
          <?= e($recht) ?>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?= $zu ?>

<?= $auf($tr ? '8 · Yayın' : '8 · Veröffentlichen') ?>
  <p class="text-sm text-ink">
    <?= $tr ? 'Durum' : 'Zustand' ?>: <strong><?= e((string) $design['status']) ?></strong>
    · <?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?>
  </p>
  <?php if ($warnings === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Uyarı yok.' : 'Keine Hinweise.' ?></p>
  <?php else : ?>
    <ul class="space-y-1 text-sm text-gold">
      <?php foreach ($warnings as $w) : ?>
        <li><?= e($w['kind']) ?> — <?= e($w['element']) ?><?= $w['detail'] !== '' ? ' (' . e($w['detail']) . ')' : '' ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <p class="<?= $label ?>"><?= $tr ? 'Aktife alma katalogdan yapılır.' : 'Das Aktivieren geschieht im Katalog.' ?></p>
<?= $zu ?>
