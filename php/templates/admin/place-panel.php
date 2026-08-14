<?php
/**
 * Ortssuche für eine Location.
 *
 * Statt Anschrift und Koordinaten abzutippen: suchen, auf der Karte prüfen,
 * übernehmen. Die Karte kommt über die eigene Adresse herein, damit der
 * Google-Schlüssel nicht im HTML des Adminbereichs steht.
 *
 * @var string $locale
 * @var int $index
 * @var array<string,mixed> $venue     der Eintrag selbst
 * @var array<string,mixed>|null $found  Suchergebnis dieses Durchgangs
 * @var list<array<string,mixed>> $reviews  live geholte Bewertungen
 * @var bool $configured  Schlüssel hinterlegt?
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;

$de = $locale === 'de';
$placeId = (string) ($venue['placeId'] ?? '');
$lat = (float) ($venue['lat'] ?? 0);
$lng = (float) ($venue['lng'] ?? 0);
$hasPlace = $placeId !== '' && ($lat !== 0.0 || $lng !== 0.0);

$hidden = '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
    . '<input type="hidden" name="liste" value="venues">'
    . '<input type="hidden" name="index" value="' . $index . '">';

$mapUrl = static fn (float $la, float $ln): string
    => I18n::path('/admin/karte', $locale) . '?lat=' . rawurlencode((string) $la) . '&lng=' . rawurlencode((string) $ln);
?>
<div class="border border-sand-deep bg-sand/20 p-5">
  <div class="flex flex-wrap items-baseline justify-between gap-3">
    <h4 class="text-[0.62rem] uppercase tracking-[0.18em] text-muted">
      <?= $de ? 'Ort bei Google' : 'Google’da yer' ?>
    </h4>
    <?php if ($hasPlace) : ?>
      <span class="text-[0.66rem] uppercase tracking-[0.14em] text-gold">
        <?= $de ? 'verknüpft' : 'bağlı' ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if (!$configured) : ?>
    <p class="mt-3 text-[0.8rem] leading-relaxed text-muted">
      <?= $de
        ? 'Dafür fehlt noch der Maps-Schlüssel. Er wird unter Einstellungen → Integrationen eingetragen; danach lässt sich hier suchen und die Anschrift mit einem Klick übernehmen.'
        : 'Bunun için Maps anahtarı gerekiyor. Ayarlar → Entegrasyonlar altında giriliyor; sonrasında burada arayıp adresi tek tıkla alabilirsiniz.' ?>
    </p>
    <a href="<?= e(I18n::path('/admin/integrationen', $locale)) ?>"
       class="mt-4 inline-block border border-ink px-5 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
      <?= $de ? 'Zu den Integrationen' : 'Entegrasyonlara git' ?>
    </a>

  <?php else : ?>

    <?php /* ------------------------ Der gesetzte Ort ------------------------ */ ?>
    <?php if ($hasPlace) : ?>
      <div class="mt-4 grid gap-5 sm:grid-cols-[1fr_16rem]">
        <div class="min-w-0">
          <div class="font-display text-lg text-ink"><?= e((string) ($venue['name'] ?? '')) ?></div>
          <div class="mt-1 text-[0.82rem] text-muted"><?= e((string) ($venue['address'] ?? '')) ?></div>
          <div class="mt-2 font-mono text-[0.68rem] text-muted/80">
            <?= e(number_format($lat, 6, '.', '')) ?>, <?= e(number_format($lng, 6, '.', '')) ?>
          </div>

          <div class="mt-4 flex flex-wrap gap-3">
            <form method="post">
              <?= $hidden ?>
              <input type="hidden" name="was" value="ort-bewertungen">
              <button class="border border-sand-deep px-4 py-2 text-[0.64rem] uppercase tracking-[0.16em] text-muted transition-colors hover:border-gold hover:text-gold">
                <?= $de ? 'Was Gäste dort schreiben' : 'Misafirler orası için ne yazmış' ?>
              </button>
            </form>
            <form method="post">
              <?= $hidden ?>
              <input type="hidden" name="was" value="ort-loesen">
              <button class="px-3 py-2 text-[0.64rem] uppercase tracking-[0.16em] text-muted underline-offset-4 hover:text-red-800 hover:underline">
                <?= $de ? 'Verknüpfung lösen' : 'Bağlantıyı kaldır' ?>
              </button>
            </form>
          </div>
        </div>

        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($lat . ',' . $lng)) ?>&amp;query_place_id=<?= e($placeId) ?>"
           target="_blank" rel="noopener" class="block border border-sand-deep">
          <img src="<?= e($mapUrl($lat, $lng)) ?>" alt="" loading="lazy" class="block w-full">
        </a>
      </div>
    <?php endif; ?>

    <?php /* ---------------------------- Suchfeld ---------------------------- */ ?>
    <form method="post" class="mt-5 flex flex-wrap items-end gap-3 <?= $hasPlace ? 'border-t border-sand-deep pt-5' : '' ?>">
      <?= $hidden ?>
      <input type="hidden" name="was" value="ort-suche">
      <div class="min-w-[14rem] flex-1">
        <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="q-<?= $index ?>">
          <?= $hasPlace
            ? ($de ? 'Anderen Ort suchen' : 'Başka yer ara')
            : ($de ? 'Ort suchen' : 'Yer ara') ?>
        </label>
        <input id="q-<?= $index ?>" name="q"
               value="<?= e(trim((string) ($venue['name'] ?? '') . ' ' . (string) ($venue['city'] ?? ''))) ?>"
               class="w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold">
      </div>
      <button class="border border-ink px-5 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= $de ? 'Suchen' : 'Ara' ?>
      </button>
    </form>

    <?php /* --------------------------- Trefferliste -------------------------- */ ?>
    <?php if ($found !== null) : ?>
      <?php if (!$found['ok']) : ?>
        <p class="mt-4 border border-sand-deep bg-cream px-4 py-3 text-[0.82rem] text-muted">
          <?= e(match ($found['error']) {
              'leer'            => $de ? 'Es war nichts eingegeben.' : 'Bir şey yazılmamış.',
              'kein-schluessel' => $de ? 'Kein Maps-Schlüssel hinterlegt.' : 'Maps anahtarı girilmemiş.',
              'zu-oft'          => $de ? 'Zu viele Suchen hintereinander. Kurz warten.' : 'Arka arkaya çok fazla arama. Biraz bekleyin.',
              'abgelehnt'       => $de ? 'Google hat die Anfrage abgelehnt – meist ist der Schlüssel falsch oder die Places API nicht freigeschaltet.' : 'Google isteği reddetti – genelde anahtar yanlış ya da Places API açık değil.',
              default           => $de ? 'Google war gerade nicht erreichbar.' : 'Google’a şu an ulaşılamadı.',
          }) ?>
        </p>
      <?php elseif ($found['places'] === []) : ?>
        <p class="mt-4 text-[0.82rem] text-muted">
          <?= $de ? 'Nichts gefunden. Vielleicht mit dem Ort dahinter versuchen.' : 'Bir şey bulunamadı. Yanına şehri yazıp deneyin.' ?>
        </p>
      <?php else : ?>
        <div class="mt-5 space-y-3">
          <?php foreach ($found['places'] as $place) : ?>
            <div class="grid gap-4 border border-sand-deep bg-cream p-4 sm:grid-cols-[10rem_1fr_auto] sm:items-center">
              <img src="<?= e($mapUrl((float) $place['lat'], (float) $place['lng'])) ?>" alt="" loading="lazy"
                   class="block w-full border border-sand-deep">

              <div class="min-w-0">
                <div class="font-display text-lg text-ink"><?= e((string) $place['name']) ?></div>
                <div class="mt-0.5 text-[0.8rem] text-muted"><?= e((string) $place['address']) ?></div>
                <div class="mt-1.5 flex flex-wrap gap-x-4 text-[0.72rem] text-muted">
                  <?php if ((string) $place['kind'] !== '') : ?>
                    <span><?= e((string) $place['kind']) ?></span>
                  <?php endif; ?>
                  <?php if ((float) $place['rating'] > 0) : ?>
                    <span class="text-gold">
                      ★ <?= e(number_format((float) $place['rating'], 1, ',', '')) ?>
                      · <?= (int) $place['votes'] ?> <?= $de ? 'Bewertungen' : 'değerlendirme' ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <form method="post" class="shrink-0">
                <?= $hidden ?>
                <input type="hidden" name="was" value="ort-uebernehmen">
                <input type="hidden" name="place" value="<?= e((string) $place['placeId']) ?>">
                <button class="w-full bg-ink px-6 py-3 text-[0.64rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold sm:w-auto">
                  <?= $de ? 'Übernehmen' : 'Bunu al' ?>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="mt-3 text-[0.7rem] text-muted">
          <?= $de
            ? 'Übernommen werden Name, Anschrift und Koordinaten. Die Texte der Seite bleiben, wie sie sind.'
            : 'Ad, adres ve koordinat alınır. Sayfanın metinleri olduğu gibi kalır.' ?>
        </p>
      <?php endif; ?>
    <?php endif; ?>

    <?php /* ---------------------------- Bewertungen -------------------------- */ ?>
    <?php if ($reviews !== []) : ?>
      <div class="mt-6 border-t border-sand-deep pt-5">
        <h5 class="text-[0.62rem] uppercase tracking-[0.18em] text-muted">
          <?= $de ? 'Stimmen bei Google' : 'Google’daki yorumlar' ?>
        </h5>
        <p class="mt-2 max-w-3xl text-[0.76rem] leading-relaxed text-muted">
          <?= $de
            ? 'Zum Lesen, nicht zum Übernehmen: Diese Sätze gehören ihren Verfassern, und Google erlaubt es nicht, sie zu speichern oder auf der eigenen Seite weiterzuverwenden. Wofür sie taugen: zu sehen, was Gästen an diesem Ort auffällt – das Licht im Innenhof, die Treppe, der laute Saal – und dann mit eigenen Worten darüber zu schreiben.'
            : 'Okumak için, kopyalamak için değil: bu cümleler yazarlarına aittir ve Google bunları saklamaya ya da kendi sayfanızda kullanmaya izin vermiyor. İşe yaradığı yer şu: misafirlerin orada neyi fark ettiğini görmek – avludaki ışık, merdiven, salonun gürültüsü – ve sonra kendi cümlelerinizle yazmak.' ?>
        </p>

        <div class="mt-4 space-y-3">
          <?php foreach ($reviews as $review) : ?>
            <blockquote class="border-l-2 border-sand-deep bg-cream py-3 pl-4 pr-3">
              <p class="whitespace-pre-line text-[0.85rem] leading-relaxed text-ink"><?= e((string) $review['text']) ?></p>
              <footer class="mt-2 text-[0.7rem] text-muted">
                <span class="text-gold">★ <?= (int) $review['rating'] ?></span>
                ·
                <?php if ((string) $review['link'] !== '') : ?>
                  <a href="<?= e((string) $review['link']) ?>" target="_blank" rel="noopener nofollow"
                     class="underline-offset-4 hover:text-gold hover:underline"><?= e((string) $review['author']) ?></a>
                <?php else : ?>
                  <?= e((string) $review['author']) ?>
                <?php endif; ?>
                <?php if ((string) $review['when'] !== '') : ?>
                  · <?= e((string) $review['when']) ?>
                <?php endif; ?>
                · Google
              </footer>
            </blockquote>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
