<?php
/**
 * Themen der digitalen Einladung.
 *
 * Links die Einstellungen, rechts eine echte Vorschau der Karte – wer eine
 * Farbe ändert, sieht sofort, was das Paar später sehen wird.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $themes
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Themes;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';
$hint = 'mt-2 text-[0.72rem] leading-relaxed text-muted';
?>
<div class="space-y-10">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Themen der Einladung' : 'Davetiye temaları' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
          ? 'Jedes Thema ist ein fertiges Design: Farben, Hintergrundbild und Öffnungsanimation. Das Paar wählt im Assistenten nur noch aus – bearbeitet wird hier.'
          : 'Her tema hazır bir tasarımdır: renkler, arka plan görseli ve açılış animasyonu. Çift sihirbazda yalnızca seçer – düzenleme burada yapılır.' ?>
    </p>
    <p class="<?= $hint ?>">
      <?= $de
          ? 'Hintergründe lassen sich als Bild hochladen (z. B. aus Canva), und wer eine eigene Animation hat, fügt sie unter „Eigenes CSS“ ein.'
          : 'Arka planlar görsel olarak yüklenebilir (örneğin Canva’dan); kendi animasyonunuz varsa „Kendi CSS’iniz“ alanına yapıştırın.' ?>
    </p>
  </div>

  <?php foreach ($themes as $index => $theme) : ?>
    <?php $id = (string) $theme['id']; ?>
    <details class="border border-sand-deep" <?= $index === 0 ? 'open' : '' ?>>
      <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-4 p-6">
        <span class="flex items-center gap-4">
          <span class="flex h-10 w-10 items-center justify-center rounded-full border"
                style="background: <?= e((string) $theme['paper']) ?>; border-color: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['accent']) ?>">✦</span>
          <span>
            <span class="font-display text-lg text-ink"><?= e((string) $theme['name']) ?></span>
            <span class="ml-3 text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= e($id) ?></span>
          </span>
        </span>
        <span class="text-[0.68rem] uppercase tracking-[0.14em] text-muted">
          <?= e(Themes::animationLabel((string) $theme['animation'], $locale)) ?>
          <?php if ((string) $theme['image'] !== '') : ?>
            <span class="ml-3 text-gold"><?= $de ? 'mit Bild' : 'görselli' ?></span>
          <?php endif; ?>
        </span>
      </summary>

      <div class="grid gap-10 border-t border-sand-deep p-6 lg:grid-cols-[1.15fr_0.85fr]">
        <!-- ------------------------- Einstellungen ------------------------- -->
        <form method="post" enctype="multipart/form-data" class="space-y-8" data-theme-form data-theme-id="<?= e($id) ?>">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="was" value="save">
          <input type="hidden" name="id" value="<?= e($id) ?>">

          <div class="grid gap-7 md:grid-cols-2">
            <div>
              <label class="<?= $label ?>"><?= $de ? 'Name' : 'Ad' ?></label>
              <input name="name" value="<?= e((string) $theme['name']) ?>" class="<?= $input ?>" data-theme-name>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Kurztext (DE)' : 'Kısa metin (DE)' ?></label>
                <input name="sub_de" value="<?= e((string) ($theme['sub']['de'] ?? '')) ?>" class="<?= $input ?>">
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Kurztext (TR)' : 'Kısa metin (TR)' ?></label>
                <input name="sub_tr" value="<?= e((string) ($theme['sub']['tr'] ?? '')) ?>" class="<?= $input ?>">
              </div>
            </div>
          </div>

          <!-- Farben -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Farben' : 'Renkler' ?></div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              <?php foreach (Themes::COLORS as $key => $caption) : ?>
                <?php
                $value = (string) $theme[$key];
                // Farbwähler versteht nur #rrggbb – bei rgba() bleibt das Textfeld führend.
                $isHex = preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
                ?>
                <div>
                  <label class="<?= $label ?>"><?= e($caption[$locale] ?? $caption['de']) ?></label>
                  <div class="mt-1 flex items-center gap-2">
                    <input type="color" value="<?= e($isHex ? $value : '#B08D57') ?>"
                           class="h-9 w-10 shrink-0 cursor-pointer border border-sand-deep bg-transparent p-0"
                           data-color-picker="<?= e($key) ?>" <?= $isHex ? '' : 'title="rgba – bitte im Textfeld ändern"' ?>>
                    <input name="<?= e($key) ?>" value="<?= e($value) ?>" class="<?= $input ?> font-mono text-[0.8rem]"
                           data-theme-field="<?= e($key) ?>">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Bilder -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Hintergrundbilder' : 'Arka plan görselleri' ?></div>
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'JPG oder PNG, wird beim Hochladen auf 1600 px verkleinert. Bestes Format für die Karte: hochkant, etwa 1200 × 1600.'
                  : 'JPG veya PNG; yüklerken 1600 px’e küçültülür. Kart için en iyi ölçü dikey, yaklaşık 1200 × 1600.' ?>
            </p>

            <div class="mt-5 grid gap-7 md:grid-cols-2">
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Karte' : 'Kart' ?></label>
                <?php if ((string) $theme['image'] !== '') : ?>
                  <div class="mt-2 flex items-center gap-3">
                    <img src="<?= e((string) $theme['image']) ?>" alt="" class="h-16 w-12 border border-sand-deep object-cover">
                    <button name="was" value="image-delete" class="text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-red-700"
                            data-confirm="<?= $de ? 'Bild entfernen?' : 'Görsel kaldırılsın mı?' ?>">
                      <?= $de ? 'Entfernen' : 'Kaldır' ?>
                    </button>
                  </div>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="mt-2 w-full text-[0.8rem] text-muted">
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Kuvert' : 'Zarf' ?></label>
                <?php if ((string) $theme['envelopeImage'] !== '') : ?>
                  <div class="mt-2 flex items-center gap-3">
                    <img src="<?= e((string) $theme['envelopeImage']) ?>" alt="" class="h-16 w-24 border border-sand-deep object-cover">
                    <button name="was" value="envelope-delete" class="text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-red-700"
                            data-confirm="<?= $de ? 'Bild entfernen?' : 'Görsel kaldırılsın mı?' ?>">
                      <?= $de ? 'Entfernen' : 'Kaldır' ?>
                    </button>
                  </div>
                <?php endif; ?>
                <input type="file" name="envelopeImage" accept="image/*" class="mt-2 w-full text-[0.8rem] text-muted">
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Darstellung' : 'Yerleşim' ?></label>
                <select name="imageMode" class="<?= $input ?>">
                  <option value="cover" <?= $theme['imageMode'] === 'cover' ? 'selected' : '' ?>><?= $de ? 'Fläche füllen' : 'Alanı kapla' ?></option>
                  <option value="repeat" <?= $theme['imageMode'] === 'repeat' ? 'selected' : '' ?>><?= $de ? 'Kacheln (Muster)' : 'Döşe (desen)' ?></option>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Deckkraft (%)' : 'Opaklık (%)' ?></label>
                <input type="number" min="0" max="100" name="imageOpacity" value="<?= e((string) $theme['imageOpacity']) ?>" class="<?= $input ?>">
              </div>
            </div>
          </div>

          <!-- Animation -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Öffnungsanimation' : 'Açılış animasyonu' ?></div>
            <div class="mt-5 grid gap-7 md:grid-cols-3">
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Art' : 'Tür' ?></label>
                <select name="animation" class="<?= $input ?>" data-theme-animation>
                  <?php foreach (Themes::ANIMATIONS as $option) : ?>
                    <option value="<?= e($option) ?>" <?= $theme['animation'] === $option ? 'selected' : '' ?>>
                      <?= e(Themes::animationLabel($option, $locale)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Dauer (ms)' : 'Süre (ms)' ?></label>
                <input type="number" min="0" max="8000" step="50" name="animationSpeed" value="<?= e((string) $theme['animationSpeed']) ?>" class="<?= $input ?>" data-theme-speed>
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Verzögerung (ms)' : 'Gecikme (ms)' ?></label>
                <input type="number" min="0" max="8000" step="50" name="animationDelay" value="<?= e((string) $theme['animationDelay']) ?>" class="<?= $input ?>" data-theme-delay>
              </div>
            </div>
          </div>

          <!-- Eigenes CSS -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Eigenes CSS (fortgeschritten)' : 'Kendi CSS’iniz (gelişmiş)' ?></div>
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Wird nur auf dieses Thema angewendet – die Selektoren werden automatisch auf .theme-' . $id . ' eingegrenzt. @keyframes bleiben unverändert. Verfügbare Klassen: .t-card (Karte), .t-envelope (Kuvert), .t-seal (Siegel), .t-name (Namen), .t-date (Datum).'
                  : 'Yalnızca bu temaya uygulanır – seçiciler otomatik olarak .theme-' . $id . ' altına alınır. @keyframes olduğu gibi kalır. Kullanılabilir sınıflar: .t-card (kart), .t-envelope (zarf), .t-seal (mühür), .t-name (isimler), .t-date (tarih).' ?>
            </p>
            <textarea name="css" rows="8" spellcheck="false"
                      class="<?= $input ?> mt-3 resize-y font-mono text-[0.78rem] leading-relaxed"
                      placeholder=".t-card { animation: meinEffekt 1.2s ease-out both; }&#10;@keyframes meinEffekt { from { opacity: 0; transform: scale(.96); } }"><?= e((string) $theme['css']) ?></textarea>
          </div>

          <div class="flex flex-wrap items-center gap-4 border-t border-sand-deep pt-6">
            <button class="bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
              <?= $de ? 'Speichern' : 'Kaydet' ?>
            </button>
            <button name="was" value="duplicate" class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $de ? 'Kopie anlegen' : 'Kopyasını oluştur' ?>
            </button>
            <button name="was" value="delete" class="px-4 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted hover:text-red-700"
                    data-confirm="<?= $de ? 'Dieses Thema wirklich löschen?' : 'Bu tema silinsin mi?' ?>">
              <?= $de ? 'Löschen' : 'Sil' ?>
            </button>
          </div>
        </form>

        <!-- ---------------------------- Vorschau ---------------------------- -->
        <div class="lg:sticky lg:top-6 lg:self-start">
          <div class="flex items-center justify-between">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Vorschau' : 'Önizleme' ?></div>
            <button type="button" data-theme-play="<?= e($id) ?>"
                    class="border border-ink px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $de ? 'Animation abspielen' : 'Animasyonu oynat' ?>
            </button>
          </div>

          <div class="mt-4 flex items-center justify-center p-6" data-theme-preview="<?= e($id) ?>"
               style="background: <?= e((string) $theme['bg']) ?>">
            <div class="t-card relative w-full max-w-[16rem] overflow-hidden px-6 py-10 text-center"
                 style="background: <?= e((string) $theme['paper']) ?>; color: <?= e((string) $theme['fg']) ?>; border: 1px solid <?= e((string) $theme['paperEdge']) ?>;
                        <?= (string) $theme['image'] !== '' ? 'background-image:url(' . e((string) $theme['image']) . ');background-size:' . ($theme['imageMode'] === 'repeat' ? 'auto' : 'cover') . ';background-position:center;' : '' ?>">
              <div class="t-seal mx-auto flex h-9 w-9 items-center justify-center rounded-full text-[0.7rem]"
                   style="background: <?= e((string) $theme['seal']) ?>; color: <?= e((string) $theme['sealText']) ?>">A&amp;M</div>

              <div class="mt-6 text-[0.5rem] uppercase tracking-[0.3em]" style="color: <?= e((string) $theme['soft']) ?>">
                <?= $de ? 'Wir heiraten' : 'Evleniyoruz' ?>
              </div>

              <div class="t-name font-display mt-3 flex flex-col leading-tight">
                <span class="text-2xl font-light">Ayşe</span>
                <span class="my-0.5 text-base italic" style="color: <?= e((string) $theme['accent']) ?>">&amp;</span>
                <span class="text-2xl font-light">Mehmet</span>
              </div>

              <div class="mx-auto mt-4 h-px w-20" style="background: <?= e((string) $theme['accent']) ?>"></div>

              <div class="t-date mt-4 text-[0.62rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>">
                12.09.2026 · 16:00
              </div>

              <div class="mt-6 inline-block border px-4 py-2 text-[0.55rem] uppercase tracking-[0.2em]"
                   style="border-color: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['accent']) ?>">
                RSVP
              </div>
            </div>
          </div>

          <div class="mt-3 flex items-center gap-3">
            <span class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $de ? 'Kuvert' : 'Zarf' ?></span>
            <div class="t-envelope h-10 flex-1 border"
                 style="background: <?= e((string) $theme['envelope']) ?>; border-color: <?= e((string) $theme['envelopeEdge']) ?>;
                        <?= (string) $theme['envelopeImage'] !== '' ? 'background-image:url(' . e((string) $theme['envelopeImage']) . ');background-size:cover;' : '' ?>"></div>
          </div>
        </div>
      </div>
    </details>
  <?php endforeach; ?>

  <!-- ----------------------------- Neues Thema ----------------------------- -->
  <form method="post" class="border border-dashed border-sand-deep p-6">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="was" value="add">

    <h3 class="font-display text-lg text-ink"><?= $de ? 'Neues Thema' : 'Yeni tema' ?></h3>
    <p class="<?= $hint ?>">
      <?= $de
          ? 'Startet mit den Farben von Élysée; Bild und Animation kommen danach.'
          : 'Élysée renkleriyle başlar; görsel ve animasyon sonra eklenir.' ?>
    </p>

    <div class="mt-5 flex flex-wrap items-end gap-5">
      <div class="min-w-[16rem] flex-1">
        <label class="<?= $label ?>"><?= $de ? 'Name' : 'Ad' ?></label>
        <input name="name" required class="<?= $input ?>" placeholder="<?= $de ? 'z. B. Marmor' : 'örn. Mermer' ?>">
      </div>
      <button class="bg-ink px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Anlegen' : 'Oluştur' ?>
      </button>
    </div>
  </form>
</div>
