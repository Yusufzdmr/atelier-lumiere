<?php
/**
 * Übersicht: was seit dem letzten Blick hereingekommen ist.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $leads
 * @var list<array<string,mixed>> $selections
 * @var list<array<string,mixed>> $galleries
 * @var list<array<string,mixed>> $invitations
 * @var list<array<string,mixed>> $rsvps
 * @var list<array<string,mixed>> $customers
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
$p = static fn (string $to): string => I18n::path($to, $locale);

// Jede Zahl fuehrt dorthin, wo man mit ihr etwas tun kann – eine Kachel, die
// nur zaehlt, laesst einen danach suchen.
$tiles = [
    [$de ? 'Anfragen' : 'Talepler', count($leads), '#anfragen'],
    [$de ? 'Kunden' : 'Müşteriler', count($customers), $p('/admin/kunden')],
    [$de ? 'Galerien' : 'Galeriler', count($galleries), $p('/admin/kunden')],
    [$de ? 'Albumauswahlen' : 'Albüm seçimleri', count($selections), '#auswahlen'],
    [$de ? 'Einladungen' : 'Davetiyeler', count($invitations), $p('/admin/einladungen')],
    [$de ? 'Zusagen' : 'Katılım bildirimleri', count($rsvps), $p('/admin/einladungen')],
];

// Was gerade wirklich Arbeit ist: neue Anfragen und Auswahlen der letzten Woche.
$fresh = static function (array $rows, string $field): int {
    $limit = date('c', strtotime('-7 days'));
    $count = 0;
    foreach ($rows as $row) {
        if ((string) ($row[$field] ?? '') >= $limit) {
            $count++;
        }
    }
    return $count;
};
$newLeads = $fresh($leads, 'at');
$newPicks = $fresh($selections, 'at');
?>
<div class="space-y-12">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Übersicht' : 'Genel bakış' ?></h2>
    <p class="mt-2 text-sm text-muted">
      <?php if ($newLeads > 0 || $newPicks > 0) : ?>
        <?= $de ? 'Neu in den letzten sieben Tagen: ' : 'Son yedi günde yeni: ' ?>
        <?php $parts = [];
          if ($newLeads > 0) { $parts[] = $newLeads . ' ' . ($de ? 'Anfragen' : 'talep'); }
          if ($newPicks > 0) { $parts[] = $newPicks . ' ' . ($de ? 'Albumauswahlen' : 'albüm seçimi'); }
          echo '<span class="text-gold">' . e(implode(' · ', $parts)) . '</span>'; ?>
      <?php else : ?>
        <?= $de ? 'In den letzten sieben Tagen ist nichts Neues hereingekommen.' : 'Son yedi günde yeni bir şey gelmedi.' ?>
      <?php endif; ?>
    </p>
  </div>

  <div class="grid gap-px bg-sand-deep sm:grid-cols-3 lg:grid-cols-6">
    <?php foreach ($tiles as [$caption, $value, $href]) : ?>
      <a href="<?= e($href) ?>" class="group bg-cream p-5 transition-colors hover:bg-sand/40">
        <div class="font-display text-3xl font-light text-ink"><?= (int) $value ?></div>
        <div class="mt-1 text-[0.62rem] uppercase tracking-[0.16em] text-muted transition-colors group-hover:text-gold">
          <?= e($caption) ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php /* ------------------------- Haeufige Handgriffe ------------------------ */ ?>
  <div class="flex flex-wrap gap-3">
    <a href="<?= e($p('/admin/kunden')) ?>" class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
      <?= $de ? 'Kunde anlegen' : 'Müşteri oluştur' ?>
    </a>
    <a href="<?= e($p('/admin/portfolio')) ?>" class="border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
      <?= $de ? 'Reportage einstellen' : 'Çekim ekle' ?>
    </a>
    <a href="<?= e($p('/admin/ratgeber')) ?>" class="border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
      <?= $de ? 'Beitrag schreiben' : 'Yazı yaz' ?>
    </a>
    <a href="<?= e($p('/admin/texte')) ?>" class="border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
      <?= $de ? 'Seitentexte ändern' : 'Sayfa metinlerini değiştir' ?>
    </a>
  </div>

  <!-- ------------------------------ Anfragen ------------------------------ -->
  <section id="anfragen" class="scroll-mt-24">
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Anfragen' : 'Talepler' ?></h2>

    <?php if ($leads === []) : ?>
      <p class="mt-3 text-sm text-muted"><?= $de ? 'Noch keine Anfragen.' : 'Henüz talep yok.' ?></p>
    <?php else : ?>
      <div class="mt-5 space-y-4">
        <?php foreach ($leads as $lead) : ?>
          <div class="border border-sand-deep p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
              <div class="font-display text-lg text-ink"><?= e((string) ($lead['name'] ?? '')) ?></div>
              <div class="text-[0.7rem] uppercase tracking-[0.16em] text-muted">
                <?= e(Dates::short((string) ($lead['at'] ?? ''))) ?>
              </div>
            </div>

            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-[0.8rem] text-muted">
              <a href="mailto:<?= e((string) ($lead['email'] ?? '')) ?>" class="text-gold hover:underline"><?= e((string) ($lead['email'] ?? '')) ?></a>
              <?php if (($lead['phone'] ?? '') !== '') : ?>
                <a href="tel:<?= e((string) $lead['phone']) ?>" class="hover:text-gold"><?= e((string) $lead['phone']) ?></a>
              <?php endif; ?>
              <?php if (($lead['date'] ?? '') !== '') : ?>
                <span><?= $de ? 'Datum' : 'Tarih' ?>: <?= e((string) $lead['date']) ?></span>
              <?php endif; ?>
              <?php if (($lead['location'] ?? '') !== '') : ?>
                <span><?= e((string) $lead['location']) ?></span>
              <?php endif; ?>
              <?php if (($lead['guests'] ?? '') !== '') : ?>
                <span><?= e((string) $lead['guests']) ?> <?= $de ? 'Gäste' : 'kişi' ?></span>
              <?php endif; ?>
              <?php if (($lead['service'] ?? '') !== '') : ?>
                <span><?= e((string) $lead['service']) ?></span>
              <?php endif; ?>
            </div>

            <p class="mt-3 whitespace-pre-line text-[0.9rem] leading-relaxed text-ink"><?= e((string) ($lead['message'] ?? '')) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- --------------------------- Albumauswahlen --------------------------- -->
  <section id="auswahlen" class="scroll-mt-24">
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Albumauswahlen' : 'Albüm seçimleri' ?></h2>

    <?php if ($selections === []) : ?>
      <p class="mt-3 text-sm text-muted"><?= $de ? 'Noch keine Auswahl eingegangen.' : 'Henüz seçim gelmedi.' ?></p>
    <?php else : ?>
      <div class="mt-5 space-y-4">
        <?php foreach ($selections as $selection) : ?>
          <div class="border border-sand-deep p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
              <div class="font-display text-lg text-ink"><?= e((string) ($selection['couple'] ?? '')) ?></div>
              <div class="text-[0.7rem] uppercase tracking-[0.16em] text-gold">
                <?= count((array) ($selection['picks'] ?? [])) ?> <?= $de ? 'Bilder' : 'kare' ?>
              </div>
            </div>
            <div class="mt-2 break-all text-[0.8rem] text-muted">
              <?= $de ? 'Bildnummern' : 'Kare numaraları' ?>:
              <?= e(implode(', ', array_map(static fn ($i): int => ((int) $i) + 1, (array) ($selection['picks'] ?? [])))) ?>
            </div>
            <?php if (($selection['note'] ?? '') !== '') : ?>
              <p class="mt-3 border-t border-sand-deep pt-3 text-[0.88rem] italic leading-relaxed text-ink">
                &bdquo;<?= e((string) $selection['note']) ?>&ldquo;
              </p>
            <?php endif; ?>
            <div class="mt-3 text-[0.7rem] uppercase tracking-[0.16em] text-muted">
              <a href="<?= e($p('/admin/kunden')) ?>" class="hover:text-gold"><?= e((string) ($selection['code'] ?? '')) ?></a>
              · <?= e(Dates::short((string) ($selection['at'] ?? ''))) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
