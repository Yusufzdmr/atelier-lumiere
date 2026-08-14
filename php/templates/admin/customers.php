<?php
/**
 * Kundenliste mit dem Formular zum Anlegen.
 *
 * @var string $locale
 * @var list<array{customer:array<string,mixed>,photos:int,selection:array<string,mixed>|null}> $active
 * @var list<array{customer:array<string,mixed>,photos:int,selection:array<string,mixed>|null}> $archived
 * @var array<string,mixed> $campaign
 * @var string $error
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';

/** Eine Zeile der Liste. */
$row = static function (array $entry) use ($de, $locale): string {
    $c = $entry['customer'];
    $coupon = $c['coupon'];
    $used = $coupon['usedFor'] !== [];

    $state = $used
        ? ($de ? 'eingelöst' : 'kullanıldı')
        : ($coupon['active'] ? ($de ? 'offen' : 'açık') : ($de ? 'gesperrt' : 'kapalı'));

    $facts = array_values(array_filter([
        (string) $c['venue'],
        (string) $c['date'],
        (string) $c['packageName'],
    ], static fn (string $v): bool => $v !== ''));

    $html = '<a href="' . e(I18n::path('/admin/kunden/' . $c['code'], $locale)) . '" '
        . 'class="block border border-sand-deep p-5 transition-colors hover:border-gold">'
        . '<div class="flex flex-wrap items-baseline justify-between gap-3">'
        . '<span class="font-display text-lg text-ink">' . e((string) $c['couple']) . '</span>'
        . '<span class="text-[0.68rem] uppercase tracking-[0.16em] text-muted">'
        . $entry['photos'] . ' ' . ($de ? 'Bilder' : 'kare');

    if ($entry['selection'] !== null) {
        $html .= '<span class="ml-3 text-gold">' . count((array) ($entry['selection']['picks'] ?? []))
            . ' ' . ($de ? 'ausgewählt' : 'seçildi') . '</span>';
    }

    $html .= '</span></div>'
        . '<div class="mt-1.5 text-[0.76rem] text-muted">'
        . e($facts === [] ? ($de ? 'ohne Angaben' : 'bilgi yok') : implode(' · ', $facts))
        . '</div>'
        . '<div class="mt-3 flex flex-wrap items-center gap-2 text-[0.62rem] uppercase tracking-[0.14em]">'
        . '<span class="border border-sand-deep px-2 py-1 text-muted">'
        . ($de ? 'Login' : 'Giriş') . ': ' . e((string) $c['code']) . '</span>'
        . '<span class="border px-2 py-1 ' . ($used ? 'border-sand-deep text-muted' : 'border-gold text-gold') . '">'
        . ($de ? 'Gutschein' : 'Kupon') . ': ' . e($state) . '</span>';

    if ($entry['selection'] !== null) {
        $html .= '<span class="border border-gold px-2 py-1 text-gold">'
            . ($de ? 'Auswahl liegt vor' : 'Seçim geldi') . '</span>';
    }

    return $html . '</div></a>';
};
?>
<div class="grid gap-12 lg:grid-cols-[1.2fr_0.8fr]">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Kunden' : 'Müşteriler' ?></h2>
    <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Ein Kunde ist ein Auftrag: Zugang zur Galerie, Gutschein für die digitale Einladung und die Auftragsdaten in einem Datensatz. Beim Anlegen entsteht die Galerie automatisch mit.'
        : 'Bir müşteri = bir iş: galeri girişi, dijital davetiye kuponu ve iş bilgileri tek kayıtta. Kaydı açtığınızda galerisi de otomatik oluşur.' ?>
    </p>

    <?php if ($error === 'code') : ?>
      <p class="mt-4 border border-red-700/40 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= $de
          ? 'Diesen Anmeldenamen gibt es schon. Bitte einen anderen wählen.'
          : 'Bu giriş adı zaten var. Lütfen başka bir ad seçin.' ?>
      </p>
    <?php elseif ($error === 'couple') : ?>
      <p class="mt-4 border border-red-700/40 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= $de ? 'Ohne Namen geht es nicht.' : 'Ad olmadan olmaz.' ?>
      </p>
    <?php endif; ?>

    <div class="mt-7 space-y-4">
      <?php foreach ($active as $entry) : ?>
        <?= $row($entry) ?>
      <?php endforeach; ?>
      <?php if ($active === []) : ?>
        <p class="text-sm text-muted"><?= $de ? 'Noch kein Kunde angelegt.' : 'Henüz müşteri yok.' ?></p>
      <?php endif; ?>
    </div>

    <?php if ($archived !== []) : ?>
      <div class="mt-10">
        <h3 class="text-[0.66rem] uppercase tracking-[0.18em] text-muted">
          <?= $de ? 'Archiv' : 'Arşiv' ?> (<?= count($archived) ?>)
        </h3>
        <div class="mt-4 space-y-4 opacity-60">
          <?php foreach ($archived as $entry) : ?>
            <?= $row($entry) ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="h-fit space-y-8">
    <form method="post" class="border border-sand-deep p-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="neu">
      <h2 class="font-display text-xl text-ink"><?= $de ? 'Neuer Kunde' : 'Yeni müşteri' ?></h2>

      <div class="mt-6 space-y-6">
        <div>
          <label class="<?= $label ?>" for="k-couple"><?= $de ? 'Paar / Name' : 'Çift / ad' ?></label>
          <input id="k-couple" name="couple" required class="<?= $input ?>" placeholder="Elif &amp; Marco">
        </div>
        <div>
          <label class="<?= $label ?>" for="k-code"><?= $de ? 'Anmeldename' : 'Giriş adı' ?></label>
          <input id="k-code" name="code" class="<?= $input ?>"
                 placeholder="<?= $de ? 'leer = aus dem Namen' : 'boş = addan üretilir' ?>">
        </div>
        <div>
          <label class="<?= $label ?>" for="k-pass"><?= $de ? 'Passwort' : 'Parola' ?></label>
          <input id="k-pass" name="password" class="<?= $input ?>"
                 placeholder="<?= $de ? 'leer = automatisch' : 'boş = otomatik' ?>">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="<?= $label ?>" for="k-date"><?= $de ? 'Hochzeit' : 'Düğün' ?></label>
            <input id="k-date" type="date" name="date" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="k-exp"><?= $de ? 'Galerie bis' : 'Galeri bitişi' ?></label>
            <input id="k-exp" type="date" name="expires" class="<?= $input ?>">
          </div>
        </div>
        <div>
          <label class="<?= $label ?>" for="k-venue">Location</label>
          <input id="k-venue" name="venue" class="<?= $input ?>" placeholder="Schloss Solitude, Stuttgart">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="<?= $label ?>" for="k-paket">Paket</label>
            <input id="k-paket" name="packageName" class="<?= $input ?>"
                   placeholder="<?= $de ? 'Ganztagesreportage' : 'Tam gün' ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="k-amount"><?= $de ? 'Betrag' : 'Tutar' ?></label>
            <input id="k-amount" name="amount" class="<?= $input ?>" placeholder="1.890 €">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="<?= $label ?>" for="k-mail">E-Mail</label>
            <input id="k-mail" type="email" name="email" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="k-phone">Telefon</label>
            <input id="k-phone" name="phone" class="<?= $input ?>">
          </div>
        </div>
        <div>
          <label class="<?= $label ?>" for="k-notes"><?= $de ? 'Interne Notiz' : 'İç not' ?></label>
          <textarea id="k-notes" name="notes" rows="2" class="<?= $input ?> resize-none"></textarea>
        </div>

        <div class="border-t border-sand-deep pt-5">
          <label class="<?= $label ?>" for="k-coupon"><?= $de ? 'Gutscheincode Einladung' : 'Davetiye kupon kodu' ?></label>
          <input id="k-coupon" name="coupon" class="<?= $input ?> font-mono"
                 placeholder="<?= $de ? 'leer = automatisch erzeugen' : 'boş = otomatik üret' ?>">
          <label class="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
            <input type="checkbox" name="couponOnce" checked class="h-4 w-4 accent-[#B08D57]">
            <?= $de ? 'Nur einmal einlösbar' : 'Tek kullanımlık' ?>
          </label>
          <div class="mt-4">
            <label class="<?= $label ?>" for="k-cexp"><?= $de ? 'Gültig bis (optional)' : 'Son geçerlilik (isteğe bağlı)' ?></label>
            <input id="k-cexp" type="date" name="couponExpires" class="<?= $input ?>">
          </div>
        </div>
      </div>

      <button class="mt-8 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Kunde anlegen' : 'Müşteriyi oluştur' ?>
      </button>
    </form>

    <form method="post" class="border border-sand-deep p-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="kampagne">
      <h2 class="font-display text-xl text-ink"><?= $de ? 'Aktionscode' : 'Kampanya kodu' ?></h2>
      <p class="mt-2 text-[0.78rem] leading-relaxed text-muted">
        <?= $de
          ? 'Gilt zusätzlich zu den Kundencodes – etwa für eine Messe oder eine Aktion. Wer ihn kennt, erstellt die Einladung kostenlos.'
          : 'Müşteri kodlarına ek olarak geçerlidir – fuar ya da kampanya için. Kodu bilen davetiyeyi ücretsiz oluşturur.' ?>
      </p>
      <div class="mt-5">
        <label class="<?= $label ?>" for="k-camp">Code</label>
        <input id="k-camp" name="campaignCode" value="<?= e((string) ($campaign['code'] ?? '')) ?>" class="<?= $input ?> font-mono">
      </div>
      <label class="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
        <input type="checkbox" name="campaignActive" <?= !empty($campaign['active']) ? 'checked' : '' ?> class="h-4 w-4 accent-[#B08D57]">
        <?= $de ? 'Aktiv' : 'Aktif' ?>
      </label>
      <button class="mt-6 w-full border border-ink px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= $de ? 'Speichern' : 'Kaydet' ?>
      </button>
    </form>
  </div>
</div>
