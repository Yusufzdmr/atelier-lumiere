<?php
/**
 * Listenreiter des Adminbereichs.
 *
 * Städte, Locations, Reportagen, Beiträge und Leistungen unterscheiden sich in
 * den Feldern, nicht in der Bedienung: aufklappen, ändern, speichern – und
 * daneben anlegen, verschieben, löschen. Deshalb eine Vorlage für alle fünf.
 *
 * Jeder Eintrag hat sein eigenes Formular. Das ist Absicht: sonst schickte ein
 * Klick alle zehn Städte gleichzeitig ab, und ein Tippfehler in einer Zeile
 * beträfe die ganze Seite.
 *
 * @var string $locale
 * @var string $title
 * @var string $intro
 * @var list<array<string,mixed>> $blocks
 * @var array<string,mixed> $data
 * @var array<string,mixed> $originals
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Form;

$de = $locale === 'de';

/** Verstecktes Beiwerk, das jedes Formular auf dieser Seite braucht. */
$hidden = static function (string $was, string $key, ?int $index = null) use ($csrf): string {
    $html = '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . '<input type="hidden" name="was" value="' . e($was) . '">'
        . '<input type="hidden" name="liste" value="' . e($key) . '">';
    if ($index !== null) {
        $html .= '<input type="hidden" name="index" value="' . $index . '">';
    }
    return $html;
};
?>
<div class="space-y-10">
  <div>
    <h2 class="font-display text-xl text-ink"><?= e($title) ?></h2>
    <?php if ($intro !== '') : ?>
      <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted"><?= e($intro) ?></p>
    <?php endif; ?>
  </div>

  <?php foreach ($blocks as $block) : ?>
    <?php $key = (string) $block['key']; ?>
    <section class="space-y-3">
      <?php if (($block['label'] ?? '') !== '') : ?>
        <div class="border-b border-sand-deep pb-3">
          <h3 class="font-display text-lg text-ink"><?= e((string) $block['label']) ?></h3>
          <?php if (($block['intro'] ?? '') !== '') : ?>
            <p class="mt-1.5 max-w-3xl text-[0.8rem] leading-relaxed text-muted"><?= e((string) $block['intro']) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php foreach ($block['items'] as $item) : ?>
        <?php $i = (int) $item['index']; ?>
        <details class="group border border-sand-deep"<?= !empty($item['open']) ? ' open' : '' ?>>
          <summary class="flex cursor-pointer items-center justify-between gap-4 p-5">
            <span class="min-w-0">
              <span class="font-display text-lg text-ink"><?= e((string) $item['heading']) ?></span>
              <?php if (($item['note'] ?? '') !== '') : ?>
                <span class="ml-3 text-[0.72rem] text-muted"><?= e((string) $item['note']) ?></span>
              <?php endif; ?>
            </span>
            <span class="shrink-0 text-[0.66rem] uppercase tracking-[0.16em] text-gold group-open:hidden">
              <?= $de ? 'Bearbeiten' : 'Düzenle' ?>
            </span>
          </summary>

          <div class="space-y-8 border-t border-sand-deep p-6">
            <?php /* Zusatzfeld eines Reiters – etwa die Ortssuche bei Locations. */ ?>
            <?php if (($item['panel'] ?? '') !== '') : ?>
              <?= $item['panel'] ?>
            <?php endif; ?>

            <?php if (!empty($item['video'])) : ?>
              <?php
                $vBox = $item['video'];
                $vUrl = (string) ($vBox['url'] ?? '');
                $vPoster = (string) ($vBox['poster'] ?? '');
                $vLocal = $vUrl !== '' && str_starts_with($vUrl, '/uploads/');
              ?>
              <div class="space-y-3">
                <form method="post" enctype="multipart/form-data"
                      class="flex flex-wrap items-end gap-4 border border-sand-deep p-5">
                  <?= $hidden('video-upload', $key, $i) ?>
                  <div class="min-w-[16rem] flex-1">
                    <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="vid-<?= e($key) ?>-<?= $i ?>">
                      <?= $de ? 'Video hochladen (mp4/webm/mov)' : 'Video yükle (mp4/webm/mov)' ?>
                    </label>
                    <input id="vid-<?= e($key) ?>-<?= $i ?>" type="file" name="video" accept="video/mp4,video/webm,video/quicktime"
                           class="mt-2 w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
                    <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
                      <?= e((string) ($vBox['hint'] ?? '')) ?>
                    </p>
                  </div>
                  <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Hochladen' : 'Yükle' ?>
                  </button>
                </form>

                <?php if ($vUrl !== '') : ?>
                  <div class="flex flex-wrap items-start gap-4 border border-sand-deep p-4">
                    <?php /* Vorschaukachel mit dem Standbild darueber – wenn keins da ist, sieht man einfach das Video. */ ?>
                    <div class="relative h-32 w-56 shrink-0 overflow-hidden bg-ink">
                      <?php if ($vLocal) : ?>
                        <video src="<?= e($vUrl) ?>" preload="metadata" muted class="h-full w-full object-cover"></video>
                      <?php endif; ?>
                      <?php if ($vPoster !== '') : ?>
                        <img src="<?= e($vPoster) ?>" alt="" loading="lazy"
                             class="pointer-events-none absolute inset-0 h-full w-full object-cover">
                      <?php endif; ?>
                      <span class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-cream/85 text-ink">
                          <svg viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true"><path d="M4 3l9 5-9 5V3z" fill="currentColor"/></svg>
                        </span>
                      </span>
                    </div>

                    <div class="min-w-0 flex-1 space-y-3">
                      <p class="break-all text-[0.78rem] leading-relaxed text-ink"><?= e($vUrl) ?></p>

                      <?php /*
                        „Kapak koyma" – Poster/Standbild fuer das Video. Vorher
                        rutschte einfach das erste Servicefoto in den Kasten
                        (Video::embedBox nimmt $poster von aussen). Jetzt kann
                        der Betrieb hier ein eigenes Standbild setzen.
                      */ ?>
                      <div class="flex flex-wrap items-center gap-3 border-t border-sand-deep pt-3">
                        <span class="text-[0.6rem] uppercase tracking-[0.18em] text-muted">
                          <?= $de ? 'Standbild' : 'Video kapağı' ?>
                        </span>

                        <?php if ($vPoster !== '') : ?>
                          <img src="<?= e($vPoster) ?>" alt="" loading="lazy"
                               class="h-10 w-16 border border-sand-deep object-cover">
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data" class="inline-flex items-center gap-2">
                          <?= $hidden('video-poster-upload', $key, $i) ?>
                          <input id="poster-<?= e($key) ?>-<?= $i ?>" type="file" name="poster" accept="image/*" class="hidden">
                          <label for="poster-<?= e($key) ?>-<?= $i ?>"
                                 class="cursor-pointer border border-ink px-3 py-1.5 text-[0.62rem] uppercase tracking-[0.14em] text-ink transition-colors hover:bg-ink hover:text-cream">
                            <?= $vPoster !== '' ? ($de ? 'Ersetzen' : 'Değiştir') : ($de ? 'Bild wählen' : 'Kapak yükle') ?>
                          </label>
                        </form>

                        <?php if ($vPoster !== '') : ?>
                          <form method="post" class="inline">
                            <?= $hidden('video-poster-remove', $key, $i) ?>
                            <button class="text-[0.62rem] uppercase tracking-[0.14em] text-muted underline-offset-4 hover:text-red-800 hover:underline">
                              <?= $de ? 'Entfernen' : 'Kaldır' ?>
                            </button>
                          </form>
                        <?php endif; ?>

                        <span class="w-full text-[0.7rem] leading-relaxed text-muted">
                          <?= e((string) ($vBox['posterHint'] ?? '')) ?>
                        </span>
                      </div>

                      <form method="post">
                        <?= $hidden('video-remove', $key, $i) ?>
                        <button data-confirm="<?= $de ? 'Dieses Video entfernen?' : 'Bu video kaldırılsın mı?' ?>"
                                class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                          <?= $de ? 'Video entfernen' : 'Videoyu kaldır' ?>
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($item['photos'])) : ?>
              <?php $photos = $item['photos']; ?>
              <div class="space-y-4">
                <form method="post" enctype="multipart/form-data"
                      class="flex flex-wrap items-end gap-4 border border-sand-deep p-5">
                  <?= $hidden('photos-add', $key, $i) ?>
                  <div class="min-w-[16rem] flex-1">
                    <label class="block text-[0.6rem] uppercase tracking-[0.18em] text-muted" for="up-<?= e($key) ?>-<?= $i ?>">
                      <?= $de ? 'Bilder hinzufügen' : 'Fotoğraf ekle' ?>
                    </label>
                    <input id="up-<?= e($key) ?>-<?= $i ?>" type="file" name="fotos[]" accept="image/*" multiple
                           class="mt-2 w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
                    <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
                      <?= e((string) ($photos['hint'] ?? '')) ?>
                    </p>
                  </div>
                  <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                    <?= $de ? 'Hochladen' : 'Yükle' ?>
                  </button>
                </form>

                <?php if ($photos['list'] !== []) : ?>
                  <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-7">
                    <?php foreach ($photos['list'] as $photo) : ?>
                      <div class="group/ph relative aspect-[3/4] overflow-hidden border border-sand-deep">
                        <img src="<?= e((string) $photo['src']) ?>" alt="" loading="lazy" class="h-full w-full object-cover">
                        <?php if (!empty($photo['upload'])) : ?>
                          <?php /* Titelbild ist das erste Bild - oben auf der Seite und in
                                   der Uebersicht. Deshalb hier der Griff dazu. */ ?>
                          <?php if ((int) $photo['index'] === 0) : ?>
                            <span class="absolute inset-x-0 top-0 bg-gold/90 py-1 text-center text-[0.55rem] uppercase tracking-[0.14em] text-cream"><?= $de ? 'Titelbild' : 'Kapak' ?></span>
                          <?php else : ?>
                            <form method="post" class="absolute inset-x-0 top-0">
                              <?= $hidden('photo-cover', $key, $i) ?>
                              <input type="hidden" name="foto" value="<?= (int) $photo['index'] ?>">
                              <button class="w-full bg-ink/70 py-1 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-90 transition-colors hover:bg-gold"><?= $de ? 'Als Titelbild' : 'Kapak yap' ?></button>
                            </form>
                          <?php endif; ?>
                          <form method="post" class="absolute inset-x-0 bottom-0">
                            <?= $hidden('photo-delete', $key, $i) ?>
                            <input type="hidden" name="foto" value="<?= (int) $photo['index'] ?>">
                            <button data-confirm="<?= $de ? 'Dieses Bild löschen?' : 'Bu fotoğraf silinsin mi?' ?>"
                                    class="w-full bg-ink/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-90 transition-opacity group-hover/ph:opacity-100">
                              <?= $de ? 'Löschen' : 'Sil' ?>
                            </button>
                          </form>
                        <?php else : ?>
                          <?php /*
                            Platzhalter sind jetzt „klicken zum Ersetzen": das
                            <label> zeigt auf den Datei-Dialog oben, ein Klick
                            oeffnet also denselben Auswaehler wie „Hochladen".
                            Der erste Upload verdraengt die Kachel, weil dann
                            oben ein eigenes Foto in der Liste steht.
                          */ ?>
                          <label for="up-<?= e($key) ?>-<?= $i ?>"
                                 class="absolute inset-0 flex cursor-pointer flex-col items-center justify-end bg-ink/0 transition-colors hover:bg-ink/40">
                            <span class="mb-1.5 w-full bg-gold/85 py-1 text-center text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover/ph:opacity-100">
                              <?= $de ? 'Ersetzen' : 'Değiştir' ?>
                            </span>
                          </label>
                          <span class="pointer-events-none absolute inset-x-0 top-0 bg-ink/55 py-1 text-center text-[0.55rem] uppercase tracking-[0.14em] text-cream">
                            <?= $de ? 'Platzhalter' : 'Temsili' ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <form method="post" class="space-y-8">
              <?= $hidden('save', $key, $i) ?>

              <?php foreach ($item['sections'] as $section) : ?>
                <div>
                  <?php if (($section['title'] ?? '') !== '') : ?>
                    <h4 class="text-[0.62rem] uppercase tracking-[0.18em] text-muted"><?= e((string) $section['title']) ?></h4>
                  <?php endif; ?>
                  <?php if (($section['hint'] ?? '') !== '') : ?>
                    <p class="mt-1.5 max-w-3xl text-[0.72rem] leading-relaxed text-muted"><?= e((string) $section['hint']) ?></p>
                  <?php endif; ?>
                  <div class="mt-4">
                    <?= Form::fields($section['fields'], $data, $section['grid'] ?? 'md:grid-cols-2', $originals ?? []) ?>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="flex flex-wrap items-center gap-5 border-t border-sand-deep pt-6">
                <button class="bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
                  <?= $de ? 'Speichern' : 'Kaydet' ?>
                </button>
                <?php if (($item['view'] ?? '') !== '') : ?>
                  <a href="<?= e((string) $item['view']) ?>" target="_blank" rel="noopener"
                     class="text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold">
                    <?= $de ? 'Seite ansehen' : 'Sayfayı gör' ?> ↗
                  </a>
                <?php endif; ?>
              </div>
            </form>

            <div class="flex flex-wrap items-center gap-3 border-t border-sand-deep pt-5">
              <form method="post">
                <?= $hidden('up', $key, $i) ?>
                <button <?= $i === 0 ? 'disabled' : '' ?>
                        class="border border-sand-deep px-3 py-2 text-[0.66rem] text-muted transition-colors hover:border-gold hover:text-gold disabled:opacity-30 disabled:hover:border-sand-deep disabled:hover:text-muted">
                  ↑ <?= $de ? 'nach oben' : 'yukarı' ?>
                </button>
              </form>
              <form method="post">
                <?= $hidden('down', $key, $i) ?>
                <button <?= $i === (int) $block['count'] - 1 ? 'disabled' : '' ?>
                        class="border border-sand-deep px-3 py-2 text-[0.66rem] text-muted transition-colors hover:border-gold hover:text-gold disabled:opacity-30 disabled:hover:border-sand-deep disabled:hover:text-muted">
                  ↓ <?= $de ? 'nach unten' : 'aşağı' ?>
                </button>
              </form>
              <form method="post" class="ml-auto">
                <?= $hidden('delete', $key, $i) ?>
                <button data-confirm="<?= e((string) $item['confirm']) ?>"
                        class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                  <?= e((string) $item['deleteLabel']) ?>
                </button>
              </form>
            </div>
          </div>
        </details>
      <?php endforeach; ?>

      <?php if ($block['items'] === []) : ?>
        <p class="border border-sand-deep p-5 text-sm text-muted">
          <?= $de ? 'Noch kein Eintrag.' : 'Henüz kayıt yok.' ?>
        </p>
      <?php endif; ?>

      <?php if (!empty($block['add'])) : ?>
        <form method="post" class="border border-sand-deep p-6">
          <?= $hidden('add', $key) ?>
          <h3 class="font-display text-lg text-ink"><?= e((string) $block['add']['title']) ?></h3>
          <?php if (($block['add']['hint'] ?? '') !== '') : ?>
            <p class="mt-2 max-w-3xl text-[0.78rem] leading-relaxed text-muted"><?= e((string) $block['add']['hint']) ?></p>
          <?php endif; ?>
          <div class="mt-6">
            <?= Form::fields($block['add']['fields'], [], $block['add']['grid'] ?? 'md:grid-cols-3') ?>
          </div>
          <button class="mt-7 bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= e((string) $block['add']['button']) ?>
          </button>
        </form>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
