<?php
/**
 * Erstellte Einladungen, ihre Zusagen und die liegengebliebenen Entwürfe.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $rows
 * @var list<array<string,mixed>> $drafts
 * @var int $total
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
$hidden = '<input type="hidden" name="csrf" value="' . e($csrf) . '">';

$eventNames = [
    'wedding'      => ['de' => 'Hochzeit', 'tr' => 'Düğün'],
    'multi'        => ['de' => 'Mehrere Feiern', 'tr' => 'Birden çok tören'],
    'henna'        => ['de' => 'Henna-Abend', 'tr' => 'Kına gecesi'],
    'engagement'   => ['de' => 'Verlobung', 'tr' => 'Nişan'],
    'circumcision' => ['de' => 'Beschneidungsfest', 'tr' => 'Sünnet'],
    'birthday'     => ['de' => 'Geburtstag', 'tr' => 'Doğum günü'],
    'corporate'    => ['de' => 'Firmenfeier', 'tr' => 'Kurumsal'],
];
?>
<div class="space-y-10">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Einladungen' : 'Davetiyeler' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Alles, was über den Assistenten entstanden ist – mit den Zusagen der Gäste. Der Link ist das, was das Paar weitergibt.'
        : 'Sihirbazla oluşturulan her şey – misafir cevaplarıyla birlikte. Bağlantı, çiftin paylaştığı adrestir.' ?>
    </p>
    <div class="mt-5 flex flex-wrap gap-6 text-[0.72rem] uppercase tracking-[0.16em] text-muted">
      <span><?= count($rows) ?> <?= $de ? 'Einladungen' : 'davetiye' ?></span>
      <span><?= $total ?> <?= $de ? 'Rückmeldungen' : 'cevap' ?></span>
      <span><?= count($drafts) ?> <?= $de ? 'Entwürfe' : 'taslak' ?></span>
    </div>
  </div>

  <div class="space-y-3">
    <?php foreach ($rows as $row) : ?>
      <?php $invitation = $row['invitation']; ?>
      <details class="group border border-sand-deep">
        <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-4 p-5">
          <span class="min-w-0">
            <span class="font-display text-lg text-ink">
              <?= e(trim((string) ($invitation['bride'] ?? '') . ' & ' . (string) ($invitation['groom'] ?? ''), ' &')) ?>
            </span>
            <span class="ml-3 text-[0.72rem] text-muted">
              /<?= e($row['slug']) ?>
              <?php if ($row['theme'] !== '') : ?> · <?= e($row['theme']) ?><?php endif; ?>
            </span>
          </span>
          <span class="flex shrink-0 flex-wrap items-center gap-2 text-[0.62rem] uppercase tracking-[0.14em]">
            <?php if (!empty($invitation['paid'])) : ?>
              <span class="border border-gold px-2 py-1 text-gold">
                <?= $row['customer'] !== null ? ($de ? 'Gutschein' : 'Kupon') : ($de ? 'bezahlt' : 'ödendi') ?>
              </span>
            <?php else : ?>
              <span class="border border-red-700/50 px-2 py-1 text-red-700"><?= $de ? 'offen' : 'ödenmedi' ?></span>
            <?php endif; ?>
            <span class="border border-sand-deep px-2 py-1 text-muted">
              <?= $row['guests'] ?> <?= $de ? 'Gäste' : 'kişi' ?>
            </span>
          </span>
        </summary>

        <div class="space-y-6 border-t border-sand-deep p-6">
          <dl class="grid gap-x-8 gap-y-3 text-[0.8rem] sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <dt class="text-[0.6rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Anlass' : 'Tür' ?></dt>
              <dd class="mt-1 text-ink">
                <?= e($eventNames[(string) ($invitation['eventType'] ?? '')][$locale] ?? (string) ($invitation['eventType'] ?? '')) ?>
              </dd>
            </div>
            <div>
              <dt class="text-[0.6rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Erstellt' : 'Oluşturuldu' ?></dt>
              <dd class="mt-1 text-ink"><?= e(Dates::short((string) ($invitation['createdAt'] ?? ''))) ?></dd>
            </div>
            <div>
              <dt class="text-[0.6rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Preis' : 'Fiyat' ?></dt>
              <dd class="mt-1 text-ink">
                <?= !empty($invitation['paid']) && (int) ($invitation['price'] ?? 0) === 0
                    ? ($de ? 'kostenlos' : 'ücretsiz')
                    : e((string) ($invitation['price'] ?? '')) . ' €' ?>
              </dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
              <dt class="text-[0.6rem] uppercase tracking-[0.18em] text-muted">Link</dt>
              <dd class="mt-1 break-all">
                <a href="<?= e($row['url']) ?>" target="_blank" rel="noopener" class="text-gold underline-offset-4 hover:underline">
                  <?= e($row['url']) ?>
                </a>
              </dd>
            </div>
            <?php if ($row['customer'] !== null) : ?>
              <div class="sm:col-span-2 lg:col-span-3">
                <dt class="text-[0.6rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Kunde' : 'Müşteri' ?></dt>
                <dd class="mt-1">
                  <a href="<?= e(I18n::path('/admin/kunden/' . $row['customer']['code'], $locale)) ?>"
                     class="text-gold underline-offset-4 hover:underline">
                    <?= e((string) $row['customer']['couple']) ?>
                  </a>
                </dd>
              </div>
            <?php endif; ?>
          </dl>

          <?php /* --------------------- Persönliche Fassungen -------------------- */ ?>
          <div class="border-t border-sand-deep pt-5">
            <h4 class="text-[0.62rem] uppercase tracking-[0.18em] text-muted">
              <?= $de ? 'Persönliche Einladungen' : 'Kişiye özel davetiyeler' ?> (<?= count($row['personal']) ?>)
            </h4>
            <p class="mt-2 text-[0.74rem] leading-relaxed text-muted">
              <?= $de
                ? 'Das Paar pflegt die Liste selbst über seinen Verwaltungslink. Hier steht sie nur mit, falls jemand anruft.'
                : 'Listeyi çift kendi yönetim bağlantısından düzenler. Burada yalnızca biri aradığında bakmak için duruyor.' ?>
            </p>

            <?php if ($row['personal'] === []) : ?>
              <p class="mt-3 text-sm text-muted"><?= $de ? 'Keine – die Einladung wird als ein Link geteilt.' : 'Yok – davetiye tek bağlantı olarak paylaşılıyor.' ?></p>
            <?php else : ?>
              <ul class="mt-3 space-y-2">
                <?php foreach ($row['personal'] as $guest) : ?>
                  <li class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-sand-deep/60 pb-2 text-[0.82rem]">
                    <span class="text-ink"><?= e((string) $guest['name']) ?></span>
                    <a href="<?= e((string) $guest['url']) ?>" target="_blank" rel="noopener"
                       class="min-w-0 break-all text-[0.74rem] text-gold underline-offset-4 hover:underline">
                      /<?= e((string) $guest['token']) ?>
                    </a>
                    <form method="post" class="ml-auto">
                      <?= $hidden ?>
                      <input type="hidden" name="was" value="gast-loeschen">
                      <input type="hidden" name="slug" value="<?= e($row['slug']) ?>">
                      <input type="hidden" name="token" value="<?= e((string) $guest['token']) ?>">
                      <button data-confirm="<?= $de ? 'Diesen Namen entfernen?' : 'Bu isim kaldırılsın mı?' ?>"
                              class="text-[0.62rem] uppercase tracking-[0.14em] text-muted transition-colors hover:text-red-800">
                        <?= $de ? 'Entfernen' : 'Kaldır' ?>
                      </button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <div class="mt-4 break-all text-[0.72rem] text-muted">
              <?= $de ? 'Verwaltungslink des Paares' : 'Çiftin yönetim bağlantısı' ?>:
              <a href="<?= e((string) $row['manage']) ?>" target="_blank" rel="noopener" class="text-gold underline-offset-4 hover:underline">
                <?= e((string) $row['manage']) ?>
              </a>
            </div>
          </div>

          <div class="border-t border-sand-deep pt-5">
            <h4 class="text-[0.62rem] uppercase tracking-[0.18em] text-muted">
              <?= $de ? 'Rückmeldungen' : 'Cevaplar' ?>
              — <?= $row['yes'] ?> <?= $de ? 'Zusagen' : 'kabul' ?>,
              <?= $row['no'] ?> <?= $de ? 'Absagen' : 'ret' ?>,
              <?= $row['guests'] ?> <?= $de ? 'Personen' : 'kişi' ?>
            </h4>

            <?php if ($row['rsvps'] === []) : ?>
              <p class="mt-3 text-sm text-muted"><?= $de ? 'Noch keine Rückmeldung.' : 'Henüz cevap yok.' ?></p>
            <?php else : ?>
              <ul class="mt-3 space-y-2">
                <?php foreach ($row['rsvps'] as $rsvp) : ?>
                  <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-sand-deep/60 pb-2 text-[0.82rem]">
                    <span class="<?= !empty($rsvp['coming']) ? 'text-ink' : 'text-muted line-through' ?>">
                      <?= e((string) ($rsvp['name'] ?? '')) ?>
                    </span>
                    <?php if (!empty($rsvp['coming'])) : ?>
                      <span class="text-[0.68rem] uppercase tracking-[0.14em] text-gold">
                        <?= (int) ($rsvp['count'] ?? 1) ?> <?= $de ? 'Personen' : 'kişi' ?>
                      </span>
                    <?php else : ?>
                      <span class="text-[0.68rem] uppercase tracking-[0.14em] text-muted"><?= $de ? 'kommt nicht' : 'gelmiyor' ?></span>
                    <?php endif; ?>
                    <span class="text-[0.7rem] text-muted"><?= e(Dates::short((string) ($rsvp['at'] ?? ''))) ?></span>
                    <?php if ((string) ($rsvp['note'] ?? '') !== '') : ?>
                      <span class="w-full text-[0.78rem] italic text-muted">&bdquo;<?= e((string) $rsvp['note']) ?>&ldquo;</span>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <form method="post" class="border-t border-sand-deep pt-5">
            <?= $hidden ?>
            <input type="hidden" name="was" value="loeschen">
            <input type="hidden" name="slug" value="<?= e($row['slug']) ?>">
            <button data-confirm="<?= $de
                      ? 'Einladung samt Fotos und allen Rückmeldungen löschen? Der Link ist danach tot.'
                      : 'Davetiye, fotoğrafları ve tüm cevaplarıyla silinsin mi? Bağlantı artık açılmaz.' ?>"
                    class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
              <?= $de ? 'Diese Einladung löschen' : 'Bu davetiyeyi sil' ?>
            </button>
          </form>
        </div>
      </details>
    <?php endforeach; ?>

    <?php if ($rows === []) : ?>
      <p class="border border-sand-deep p-5 text-sm text-muted">
        <?= $de ? 'Noch keine Einladung erstellt.' : 'Henüz davetiye oluşturulmadı.' ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- --------------------------------- Entwürfe -------------------------------- -->
  <?php if ($drafts !== []) : ?>
    <section>
      <h3 class="font-display text-lg text-ink"><?= $de ? 'Liegengebliebene Entwürfe' : 'Yarım kalan taslaklar' ?></h3>
      <p class="mt-2 max-w-3xl text-[0.8rem] leading-relaxed text-muted">
        <?= $de
          ? 'Jemand hat angefangen und nicht abgeschickt. Nach 120 Tagen räumt sich das von selbst.'
          : 'Biri başlamış ama göndermemiş. 120 gün sonra kendiliğinden temizlenir.' ?>
      </p>

      <div class="mt-5 space-y-2">
        <?php foreach ($drafts as $draft) : ?>
          <div class="flex flex-wrap items-center justify-between gap-4 border border-sand-deep p-4">
            <div class="min-w-0">
              <div class="text-[0.88rem] text-ink"><?= e((string) ($draft['label'] ?? '—')) ?></div>
              <div class="mt-1 break-all text-[0.72rem] text-muted">
                <?= e(Dates::short((string) ($draft['updatedAt'] ?? ''))) ?> ·
                <a href="<?= e(I18n::sitePath('/einladung', $locale)) ?>?taslak=<?= e((string) ($draft['token'] ?? '')) ?>"
                   class="text-gold underline-offset-4 hover:underline">
                  <?= $de ? 'Entwurf öffnen' : 'Taslağı aç' ?>
                </a>
              </div>
            </div>
            <form method="post">
              <?= $hidden ?>
              <input type="hidden" name="was" value="entwurf-loeschen">
              <input type="hidden" name="token" value="<?= e((string) ($draft['token'] ?? '')) ?>">
              <button data-confirm="<?= $de ? 'Entwurf löschen?' : 'Taslak silinsin mi?' ?>"
                      class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                <?= $de ? 'Löschen' : 'Sil' ?>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
