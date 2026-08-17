<?php
/**
 * Eine Kundenakte: Bilder, Auswahl des Paares, Stammdaten, Gutschein.
 *
 * @var string $locale
 * @var array<string,mixed> $customer
 * @var array<string,mixed>|null $gallery
 * @var array<string,mixed>|null $selection
 * @var list<array{thumb:string,full:string,upload:bool}> $photos
 * @var list<array{slug:string,at:string,couple:string,rsvps:int,exists:bool}> $usedFor
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';
$box = 'border border-sand-deep p-6';

$coupon = $customer['coupon'];
$picks = $selection === null ? [] : array_map('intval', (array) ($selection['picks'] ?? []));
$archived = $customer['status'] === 'archived';
$hidden = '<input type="hidden" name="csrf" value="' . e($csrf) . '">';
?>
<div class="space-y-10">
  <div class="flex flex-wrap items-baseline justify-between gap-4">
    <div>
      <a href="<?= e(I18n::path('/admin/kunden', $locale)) ?>"
         class="text-[0.68rem] uppercase tracking-[0.16em] text-muted hover:text-gold">
        ← <?= $de ? 'Alle Kunden' : 'Tüm müşteriler' ?>
      </a>
      <h2 class="font-display mt-2 text-2xl text-ink"><?= e((string) $customer['couple']) ?></h2>
      <div class="mt-1 text-[0.76rem] text-muted">
        <?= $de ? 'Anmeldung' : 'Giriş' ?>: <strong class="text-ink"><?= e((string) $customer['code']) ?></strong> ·
        <?= $de ? 'Passwort' : 'Parola' ?>: <strong class="text-ink"><?= e((string) $customer['password']) ?></strong>
      </div>
    </div>

    <div class="flex flex-wrap gap-3">
      <a href="<?= e(I18n::sitePath('/galerie/' . $customer['code'], $locale)) ?>" target="_blank" rel="noopener"
         class="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= $de ? 'Galerie ansehen' : 'Galeriyi gör' ?> ↗
      </a>
      <form method="post">
        <?= $hidden ?>
        <input type="hidden" name="was" value="<?= $archived ? 'aktivieren' : 'archivieren' ?>">
        <button class="border border-sand-deep px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
          <?= $archived ? ($de ? 'Wieder aktivieren' : 'Yeniden aktif et') : ($de ? 'Archivieren' : 'Arşivle') ?>
        </button>
      </form>
    </div>
  </div>

  <?php if ($archived) : ?>
    <p class="border border-sand-deep bg-sand/40 px-4 py-3 text-sm text-muted">
      <?= $de
        ? 'Dieser Kunde ist archiviert: Der Gutschein greift nicht mehr. Die Galerie und die Bilder bleiben erhalten.'
        : 'Bu müşteri arşivde: kuponu artık çalışmaz. Galerisi ve fotoğrafları duruyor.' ?>
    </p>
  <?php endif; ?>

  <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
    <!-- ------------------------------ Bilder ------------------------------ -->
    <div class="space-y-6">
      <form method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4 <?= $box ?>">
        <?= $hidden ?>
        <input type="hidden" name="was" value="fotos">
        <div class="min-w-[16rem] flex-1">
          <label class="<?= $label ?>" for="g-fotos"><?= $de ? 'Bilder hochladen' : 'Fotoğraf yükle' ?></label>
          <input id="g-fotos" type="file" name="fotos[]" accept="image/*" multiple
                 class="mt-2 w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
          <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
            <?= $de
              ? 'Mehrere auf einmal möglich. Die Bilder werden serverseitig auf 1600 px verkleinert.'
              : 'Aynı anda birden çok seçebilirsiniz. Fotoğraflar sunucuda 1600 piksele küçültülür.' ?>
          </p>
        </div>
        <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
          <?= $de ? 'Hochladen' : 'Yükle' ?>
        </button>
      </form>

      <?php if ($selection !== null) : ?>
        <div class="border border-gold/50 bg-sand/30 p-5">
          <div class="text-[0.62rem] uppercase tracking-[0.18em] text-gold">
            <?= $de ? 'Auswahl des Paares' : 'Çiftin seçimi' ?>
          </div>
          <p class="mt-2 text-[0.9rem] text-ink">
            <?= count($picks) ?> <?= $de ? 'Bilder ausgewählt' : 'kare seçildi' ?> ·
            <?= e(Dates::short((string) ($selection['at'] ?? ''))) ?>
          </p>
          <?php if ((string) ($selection['note'] ?? '') !== '') : ?>
            <p class="mt-3 border-t border-sand-deep pt-3 text-[0.85rem] italic leading-relaxed text-ink">
              &bdquo;<?= e((string) $selection['note']) ?>&ldquo;
            </p>
          <?php endif; ?>

          <?php
          /*
           * Nummern allein sagen nichts: „3, 7, 12“ hilft weder beim Setzen des
           * Albums noch beim Nachschauen. Also die Bilder selbst, mit ihrem
           * Dateinamen darunter und einem Knopf, der die volle Auflösung holt.
           */
          $chosen = $gallery === null ? [] : \Atelier\Galleries::selectedPhotos($gallery, $selection);
          $full = array_values(array_filter($chosen, static fn (array $p): bool => $p['original'] !== null));
          ?>
          <?php if ($chosen !== []) : ?>
            <div class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-6">
              <?php foreach ($chosen as $photo) : ?>
                <figure>
                  <a href="<?= e($photo['url']) ?>" target="_blank" rel="noopener"
                     class="relative block aspect-[3/4] overflow-hidden border border-sand-deep">
                    <img src="<?= e($photo['url']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
                    <span class="absolute left-0 top-0 bg-ink/80 px-1.5 py-0.5 text-[0.58rem] text-cream"><?= (int) $photo['nr'] ?></span>
                  </a>
                  <figcaption class="mt-1 break-all font-mono text-[0.6rem] text-muted"><?= e($photo['name']) ?></figcaption>
                </figure>
              <?php endforeach; ?>
            </div>

            <?php /* ------------------- Link für den Albumhersteller ------------------- */ ?>
            <?php $share = (array) (($gallery ?? [])["share"] ?? []); ?>
            <div class="mt-6 border-t border-sand-deep pt-5">
              <div class="text-[0.62rem] uppercase tracking-[0.18em] text-gold">
                <?= $de ? 'Für den Albumhersteller' : 'Albümcü için' ?>
              </div>

              <?php if (($share['token'] ?? '') === '') : ?>
                <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
                  <?= $de
                    ? 'Erzeugt einen geheimen Link. Wer ihn hat, sieht genau diese Bilder und lädt sie als ZIP – ohne Zugang zur Galerie.'
                    : 'Gizli bir bağlantı üretir. Bağlantıya sahip olan tam bu fotoğrafları görür ve ZIP olarak indirir — galeriye erişmeden.' ?>
                </p>
                <form method="post" class="mt-3">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="was" value="freigabe">
                  <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Link erzeugen (30 Tage)' : 'Bağlantı üret (30 gün)' ?>
                  </button>
                </form>
              <?php else : ?>
                <?php $shareUrl = \Atelier\Config::url() . \Atelier\I18n::sitePath('/auswahl/' . (string) $share['token'], $locale); ?>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                  <code class="min-w-0 flex-1 break-all border border-sand-deep bg-cream px-4 py-3 text-[0.76rem] text-ink"><?= e($shareUrl) ?></code>
                  <button type="button" data-copy="<?= e($shareUrl) ?>"
                          class="border border-ink px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Kopieren' : 'Kopyala' ?>
                  </button>
                  <a href="<?= e(\Atelier\I18n::sitePath('/auswahl/' . (string) $share['token'] . '/zip', $locale)) ?>"
                     class="border border-gold px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-gold transition-colors hover:bg-gold hover:text-white">
                    ZIP
                  </a>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-4">
                  <span class="text-[0.75rem] text-muted">
                    <?= $de ? 'Gültig bis' : 'Geçerlilik' ?>: <?= e(Dates::short((string) ($share['expires'] ?? ''))) ?>
                    · <?= count($full) ?>/<?= count($chosen) ?> <?= $de ? 'in voller Auflösung' : 'tam çözünürlükte' ?>
                  </span>
                  <form method="post" class="ml-auto">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="was" value="freigabe-aus">
                    <button class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-ink hover:underline">
                      <?= $de ? 'Link abschalten' : 'Bağlantıyı kapat' ?>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
        <?php foreach ($photos as $i => $photo) : ?>
          <div class="group relative aspect-[3/4] overflow-hidden border border-sand-deep">
            <img src="<?= e($photo['thumb']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
            <span class="absolute left-1.5 top-1.5 bg-ink/70 px-1.5 py-0.5 text-[0.55rem] text-cream"><?= $i + 1 ?></span>
            <?php if (in_array($i, $picks, true)) : ?>
              <span class="absolute right-1.5 top-1.5 bg-gold px-1.5 py-0.5 text-[0.55rem] text-white">&#9829;</span>
            <?php endif; ?>
            <?php if ($photo['upload']) : ?>
              <form method="post" class="absolute inset-x-0 bottom-0">
                <?= $hidden ?>
                <input type="hidden" name="was" value="foto-loeschen">
                <input type="hidden" name="foto" value="<?= $i ?>">
                <button data-confirm="<?= $de ? 'Dieses Bild löschen?' : 'Bu fotoğraf silinsin mi?' ?>"
                        class="w-full bg-ink/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-90 transition-opacity group-hover:opacity-100">
                  <?= $de ? 'Löschen' : 'Sil' ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if ($photos === []) : ?>
          <p class="col-span-full text-sm text-muted">
            <?= $de ? 'Noch keine Bilder hochgeladen.' : 'Henüz fotoğraf yüklenmedi.' ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- ---------------------------- Seitenspalte --------------------------- -->
    <div class="space-y-6">
      <form method="post" class="<?= $box ?>">
        <?= $hidden ?>
        <input type="hidden" name="was" value="daten">
        <h3 class="font-display text-lg text-ink"><?= $de ? 'Kundendaten' : 'Müşteri bilgileri' ?></h3>

        <div class="mt-5 space-y-5">
          <div>
            <label class="<?= $label ?>" for="c-couple"><?= $de ? 'Paar / Name' : 'Çift / ad' ?></label>
            <input id="c-couple" name="couple" value="<?= e((string) $customer['couple']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="c-pass"><?= $de ? 'Neues Passwort' : 'Yeni parola' ?></label>
            <input id="c-pass" name="password" class="<?= $input ?>"
                   placeholder="<?= $de ? 'leer = unverändert' : 'boş = değişmez' ?>">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="<?= $label ?>" for="c-mail">E-Mail</label>
              <input id="c-mail" name="email" value="<?= e((string) $customer['email']) ?>" class="<?= $input ?>">
            </div>
            <div>
              <label class="<?= $label ?>" for="c-phone">Telefon</label>
              <input id="c-phone" name="phone" value="<?= e((string) $customer['phone']) ?>" class="<?= $input ?>">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="<?= $label ?>" for="c-date"><?= $de ? 'Hochzeit' : 'Düğün' ?></label>
              <input id="c-date" type="date" name="date" value="<?= e((string) $customer['date']) ?>" class="<?= $input ?>">
            </div>
            <div>
              <label class="<?= $label ?>" for="c-exp"><?= $de ? 'Galerie bis' : 'Galeri bitişi' ?></label>
              <input id="c-exp" type="date" name="expires" value="<?= e((string) ($gallery['expires'] ?? '')) ?>" class="<?= $input ?>">
            </div>
          </div>
          <div>
            <label class="<?= $label ?>" for="c-venue">Location</label>
            <input id="c-venue" name="venue" value="<?= e((string) $customer['venue']) ?>" class="<?= $input ?>">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="<?= $label ?>" for="c-paket">Paket</label>
              <input id="c-paket" name="packageName" value="<?= e((string) $customer['packageName']) ?>" class="<?= $input ?>">
            </div>
            <div>
              <label class="<?= $label ?>" for="c-amount"><?= $de ? 'Betrag' : 'Tutar' ?></label>
              <input id="c-amount" name="amount" value="<?= e((string) $customer['amount']) ?>" class="<?= $input ?>">
            </div>
          </div>
          <div>
            <label class="<?= $label ?>" for="c-video">
              <?= $de ? 'Hochzeitsfilm (YouTube / Vimeo)' : 'Düğün filmi (YouTube / Vimeo)' ?>
            </label>
            <input id="c-video" name="video" value="<?= e((string) ($gallery['videoUrl'] ?? '')) ?>" class="<?= $input ?>" placeholder="https://">
          </div>
          <div>
            <label class="<?= $label ?>" for="c-notes"><?= $de ? 'Interne Notiz' : 'İç not' ?></label>
            <textarea id="c-notes" name="notes" rows="3" class="<?= $input ?> resize-none"><?= e((string) $customer['notes']) ?></textarea>
          </div>
        </div>

        <button class="mt-7 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          <?= $de ? 'Speichern' : 'Kaydet' ?>
        </button>
      </form>

      <!-- ------------------------------ Gutschein --------------------------- -->
      <form method="post" class="<?= $box ?>">
        <?= $hidden ?>
        <h3 class="font-display text-lg text-ink"><?= $de ? 'Gutschein Einladung' : 'Davetiye kuponu' ?></h3>
        <p class="mt-2 text-[0.76rem] leading-relaxed text-muted">
          <?= $de
            ? 'Mit diesem Code erstellt das Paar seine digitale Einladung kostenlos.'
            : 'Bu kodla çift dijital davetiyesini ücretsiz oluşturur.' ?>
        </p>

        <div class="mt-5">
          <label class="<?= $label ?>" for="c-coupon">Code</label>
          <input id="c-coupon" name="coupon" value="<?= e((string) $coupon['code']) ?>" class="<?= $input ?> font-mono">
        </div>

        <label class="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
          <input type="checkbox" name="couponActive" <?= $coupon['active'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#B08D57]">
          <?= $de ? 'Aktiv' : 'Aktif' ?>
        </label>
        <label class="mt-2 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
          <input type="checkbox" name="couponOnce" <?= $coupon['once'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#B08D57]">
          <?= $de ? 'Nur einmal einlösbar' : 'Tek kullanımlık' ?>
        </label>

        <div class="mt-4">
          <label class="<?= $label ?>" for="c-cexp"><?= $de ? 'Gültig bis' : 'Son geçerlilik' ?></label>
          <input id="c-cexp" type="date" name="couponExpires" value="<?= e((string) $coupon['expires']) ?>" class="<?= $input ?>">
        </div>

        <?php if ($usedFor !== []) : ?>
          <div class="mt-5 border-t border-sand-deep pt-4">
            <div class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Eingelöst' : 'Kullanıldı' ?></div>
            <ul class="mt-2 space-y-1.5">
              <?php foreach ($usedFor as $use) : ?>
                <li class="text-[0.78rem]">
                  <a href="<?= e(I18n::sitePath('/einladung/' . $use['slug'], $locale)) ?>" target="_blank" rel="noopener"
                     class="text-gold underline-offset-4 hover:underline">/<?= e($use['slug']) ?></a>
                  <span class="ml-2 text-muted">
                    <?= e(Dates::short($use['at'])) ?><?php
                      if ($use['couple'] !== '') { echo ' · ' . e($use['couple']); }
                      if ($use['rsvps'] > 0) { echo ' · ' . $use['rsvps'] . ' RSVP'; }
                      if (!$use['exists']) { echo ' · ' . ($de ? 'gelöscht' : 'silinmiş'); }
                    ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="mt-6 flex flex-wrap gap-3">
          <button name="was" value="gutschein"
                  class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= $de ? 'Speichern' : 'Kaydet' ?>
          </button>
          <button name="was" value="gutschein-neu"
                  data-confirm="<?= $de ? 'Neuen Code erzeugen? Der alte gilt dann nicht mehr.' : 'Yeni kod üretilsin mi? Eski kod geçersiz olur.' ?>"
                  class="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-gold">
            <?= $de ? 'Neuer Code' : 'Yeni kod' ?>
          </button>
          <?php if ($usedFor !== []) : ?>
            <button name="was" value="gutschein-frei"
                    data-confirm="<?= $de ? 'Gutschein wieder freigeben?' : 'Kupon yeniden açılsın mı?' ?>"
                    class="px-3 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:text-gold hover:underline">
              <?= $de ? 'Wieder freigeben' : 'Yeniden aç' ?>
            </button>
          <?php endif; ?>
        </div>
      </form>

      <!-- -------------------------------- Löschen --------------------------- -->
      <form method="post" class="border border-red-700/30 p-6">
        <?= $hidden ?>
        <input type="hidden" name="was" value="loeschen">
        <h3 class="font-display text-lg text-ink"><?= $de ? 'Endgültig löschen' : 'Kalıcı olarak sil' ?></h3>
        <p class="mt-2 text-[0.76rem] leading-relaxed text-muted">
          <?= $de
            ? 'Löscht Kundenakte, Galerie, Auswahl und alle hochgeladenen Bilder. Nicht rückgängig zu machen – zum Bestätigen den Anmeldenamen eintippen.'
            : 'Müşteri kaydını, galeriyi, seçimi ve yüklenen tüm fotoğrafları siler. Geri alınamaz – onaylamak için giriş adını yazın.' ?>
        </p>
        <input name="confirm" class="<?= $input ?> mt-4" placeholder="<?= e((string) $customer['code']) ?>">
        <button data-confirm="<?= $de ? 'Wirklich unwiderruflich löschen?' : 'Gerçekten geri dönüşsüz silinsin mi?' ?>"
                class="mt-5 w-full border border-red-700/50 px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-red-700 transition-colors hover:bg-red-700 hover:text-white">
          <?= $de ? 'Unwiderruflich löschen' : 'Geri dönüşsüz sil' ?>
        </button>
      </form>
    </div>
  </div>
</div>
