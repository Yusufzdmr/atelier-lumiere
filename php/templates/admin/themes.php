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

            <?php
            /*
             * Was der Betrieb hier einstellt, sieht er im Vorschaukästchen
             * daneben – klein, hell und in einer Schriftgröße, in der eine
             * knapp lesbare Farbe noch lesbar aussieht. Auf der Einladung
             * eines Gastes ist sie es dann nicht mehr. Deshalb wird gerechnet
             * und nicht geschaut.
             */
            $lesbarkeit = Themes::readability($theme, $locale);
            ?>
            <?php if ($lesbarkeit !== []) : ?>
              <div class="mt-6 border-l-2 border-[#9C4A3C] bg-[#9C4A3C]/5 px-4 py-3">
                <div class="text-[0.66rem] uppercase tracking-[0.18em] text-[#9C4A3C]">
                  <?= $de ? 'Schwer zu lesen' : 'Okunması zor' ?>
                </div>
                <ul class="mt-2 space-y-1 text-[0.82rem] leading-relaxed text-ink">
                  <?php foreach ($lesbarkeit as $warnung) : ?>
                    <li>
                      <strong><?= e($warnung['label']) ?></strong> —
                      <?= $de
                        ? 'Kontrast ' . number_format($warnung['ratio'], 1) . ':1, nötig sind ' . number_format($warnung['needed'], 1) . ':1.'
                        : 'Kontrast ' . number_format($warnung['ratio'], 1) . ':1, gereken ' . number_format($warnung['needed'], 1) . ':1.' ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <p class="mt-2 text-[0.74rem] leading-relaxed text-muted">
                  <?= $de
                    ? 'Entweder die Schriftfarbe dunkler oder den Untergrund heller. Das Vorschaubild rechts täuscht: dort ist die Schrift klein und der Bildschirm hell.'
                    : 'Ya yazı rengini koyulaştırın ya da altını açın. Sağdaki önizleme yanıltır: orada yazı küçük ve ekran parlak.' ?>
                </p>
              </div>
            <?php endif; ?>
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
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Bewegung' : 'Hareket' ?></div>
            <p class="mt-2 max-w-2xl text-[0.76rem] leading-relaxed text-muted">
              <?= $de
                  ? 'Vier Achsen, frei kombinierbar: wie die Karte hereinkommt, wie die Namen erscheinen, was im Hintergrund schwebt und wie die Abschnitte beim Scrollen kommen.'
                  : 'Dört ayrı eksen, istediğiniz gibi birleşir: kart nasıl gelsin, isimler nasıl belirsin, arkada ne uçuşsun ve kaydırınca bölümler nasıl gelsin.' ?>
            </p>
            <div class="mt-5 grid gap-7 md:grid-cols-3">
              <div class="md:col-span-3">
                <label class="<?= $label ?>"><?= $de ? 'Eröffnungsszene (läuft vor dem Kuvert)' : 'Açılış sahnesi (zarftan önce oynar)' ?></label>
                <select name="intro" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::INTROS as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['intro'] ?? 'none') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::introLabel($key, $de ? 'de' : 'tr')) ?>
                      <?php if (\Atelier\Themes::introDuration($key) > 0) : ?>
                        · <?= number_format(\Atelier\Themes::introDuration($key) / 1000, 1) ?> s
                      <?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Geschlossenes Kuvert wartet' : 'Kapalı zarf beklerken' ?></label>
                <select name="idle" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::IDLES as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['idle'] ?? 'breathe') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::idleLabel($key, $de ? 'de' : 'tr')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Karte kommt herein' : 'Kart nasıl gelsin' ?></label>
                <select name="animation" class="<?= $input ?>" data-theme-animation>
                  <?php foreach (Themes::ANIMATIONS as $option) : ?>
                    <option value="<?= e($option) ?>" <?= $theme['animation'] === $option ? 'selected' : '' ?>>
                      <?= e(Themes::animationLabel($option, $locale)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Namen' : 'İsimler' ?></label>
                <select name="nameAnimation" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::NAME_ANIMATIONS as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['nameAnimation'] ?? 'write') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::nameAnimationLabel($key, $de ? 'de' : 'tr')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Schwebende Teilchen' : 'Uçuşan parçacıklar' ?></label>
                <select name="particle" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::PARTICLES as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['particle'] ?? 'petal') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::particleLabel($key, $de ? 'de' : 'tr')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="<?= $label ?>"><?= $de ? 'Abschnitte beim Scrollen' : 'Kaydırınca bölümler' ?></label>
                <select name="reveal" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::REVEALS as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['reveal'] ?? 'up') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::revealLabel($key, $de ? 'de' : 'tr')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="md:col-span-3">
                <?php /* Die Listen sagen nur, wie etwas heisst. Der Knopf zeigt, was es tut –
                         mit der gerade gewaehlten Kombination, auch ungespeichert. */ ?>
                <a href="<?= e(\Atelier\I18n::sitePath('/designs/' . $id, $locale)) ?>"
                   data-theme-try="<?= e($id) ?>"
                   data-base="<?= e(\Atelier\I18n::sitePath('/designs/' . $id, $locale)) ?>"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.6"/>
                  </svg>
                  <?= $de ? 'Mit diesen Bewegungen ansehen' : 'Bu hareketlerle önizle' ?>
                </a>
                <p class="mt-2 text-[0.7rem] leading-relaxed text-muted">
                  <?= $de
                      ? 'Öffnet die vollständige Einladung in einem neuen Tab – mit der Auswahl von hier, auch wenn sie noch nicht gespeichert ist. Unten steht, welche Bewegung gerade läuft.'
                      : 'Davetiyenin tamamını yeni sekmede açar – buradaki seçimle, henüz kaydetmemiş olsanız bile. Altta hangi hareketin oynadığı yazar.' ?>
                </p>
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

          <!-- Schriften und Familie -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Schrift & Familie' : 'Yazı & aile' ?></div>
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Zwei Schriften liegen auf dem eigenen Server – mehr wären eine Verbindung zu Google bei jedem Aufruf. Die Familie fasst Varianten desselben Entwurfs zusammen (Ivory, Rose, Sage …); im Assistenten stehen sie dann beieinander.'
                  : 'İki yazı tipi kendi sunucumuzda duruyor – fazlası her açılışta Google’a bağlanmak demek. Aile, aynı tasarımın varyasyonlarını (Ivory, Rose, Sage …) bir arada tutar; sihirbazda yan yana görünürler.' ?>
            </p>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Familie' : 'Aile' ?></label>
                <input name="family" value="<?= e((string) $theme['family']) ?>" class="<?= $input ?>" placeholder="Élysée">
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Überschriften' : 'Başlıklar' ?></label>
                <select name="font_display" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::FONTS as $key => $font) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['fonts']['display'] ?? '') === $key ? 'selected' : '' ?>><?= e($font['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Fließtext' : 'Metin' ?></label>
                <select name="font_body" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::FONTS as $key => $font) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['fonts']['body'] ?? '') === $key ? 'selected' : '' ?>><?= e($font['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Namen (Kalligrafie)' : 'İsimler (kaligrafi)' ?></label>
                <select name="font_script" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::FONTS as $key => $font) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['fonts']['script'] ?? 'greatvibes') === $key ? 'selected' : '' ?>><?= e($font['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Hintergrundkunst' : 'Arka plan çizimi' ?></label>
                <select name="scene" class="<?= $input ?>">
                  <?php foreach (\Atelier\Themes::SCENES as $key) : ?>
                    <option value="<?= e($key) ?>" <?= ($theme['scene'] ?? 'botanical') === $key ? 'selected' : '' ?>>
                      <?= e(\Atelier\Themes::sceneLabel($key, $de ? 'de' : 'tr')) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="mt-1.5 text-[0.7rem] leading-relaxed text-muted">
                  <?= $de
                      ? 'Wird gezeichnet, nicht hochgeladen – nimmt die Farben dieses Themas an.'
                      : 'Yüklenmez, çizilir – bu temanın renklerini alır.' ?>
                </p>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="<?= $label ?>"><?= $de ? 'Größe %' : 'Boyut %' ?></label>
                  <input type="number" min="60" max="160" step="5" name="font_scale" value="<?= e((string) ($theme['fonts']['scale'] ?? '100')) ?>" class="<?= $input ?>">
                </div>
                <div>
                  <label class="<?= $label ?>"><?= $de ? 'Laufweite' : 'Harf aralığı' ?></label>
                  <input type="number" min="-30" max="80" step="5" name="font_tracking" value="<?= e((string) ($theme['fonts']['tracking'] ?? '0')) ?>" class="<?= $input ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- Schmuckelemente -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Schmuck' : 'Süslemeler' ?></div>
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Blume, Rahmen, Monogramm – als PNG, WebP oder SVG mit durchsichtigem Hintergrund. Alle Maße in Prozent, damit es auf dem Handy genauso sitzt wie am Bildschirm.'
                  : 'Çiçek, çerçeve, monogram – saydam zeminli PNG, WebP ya da SVG. Tüm ölçüler yüzde, telefonda da ekrandaki gibi otursun diye.' ?>
            </p>

            <?php $decorations = (array) $theme['decorations']; ?>
            <?php if ($decorations !== []) : ?>
              <div class="mt-4 space-y-3">
                <?php foreach ($decorations as $deco) : ?>
                  <?php $d = $deco['id']; ?>
                  <div class="grid gap-4 border border-sand-deep p-4 sm:grid-cols-[5rem_1fr]">
                    <div class="flex items-center justify-center border border-sand-deep bg-[repeating-conic-gradient(#EDE4D8_0_25%,#FAF7F2_0_50%)] bg-[length:12px_12px] p-2">
                      <img src="<?= e((string) $deco['src']) ?>" alt="" class="max-h-16 w-auto">
                    </div>
                    <div>
                      <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Ort' : 'Yer' ?></label>
                          <select name="deco_<?= e($d) ?>_spot" class="<?= $input ?>">
                            <?php foreach (\Atelier\Themes::SPOTS as $key => $caption) : ?>
                              <option value="<?= e($key) ?>" <?= $deco['spot'] === $key ? 'selected' : '' ?>><?= e($caption[$locale] ?? $caption['de']) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Links %' : 'Sol %' ?></label>
                          <input type="number" min="-50" max="150" name="deco_<?= e($d) ?>_x" value="<?= e((string) $deco['x']) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Oben %' : 'Üst %' ?></label>
                          <input type="number" min="-50" max="150" name="deco_<?= e($d) ?>_y" value="<?= e((string) $deco['y']) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Breite %' : 'Genişlik %' ?></label>
                          <input type="number" min="1" max="200" name="deco_<?= e($d) ?>_width" value="<?= e((string) $deco['width']) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Drehung °' : 'Dönüş °' ?></label>
                          <input type="number" min="-180" max="180" name="deco_<?= e($d) ?>_rotate" value="<?= e((string) $deco['rotate']) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Deckkraft %' : 'Opaklık %' ?></label>
                          <input type="number" min="0" max="100" step="5" name="deco_<?= e($d) ?>_opacity" value="<?= e((string) $deco['opacity']) ?>" class="<?= $input ?>">
                        </div>
                      </div>

                      <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Bewegung' : 'Hareket' ?></label>
                          <select name="deco_<?= e($d) ?>_move" class="<?= $input ?>">
                            <?php foreach (\Atelier\Themes::MOVES as $move) : ?>
                              <option value="<?= e($move) ?>" <?= $deco['move'] === $move ? 'selected' : '' ?>>
                                <?= e(\Atelier\Themes::moveLabel($move, $locale)) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Wartet (ms)' : 'Bekler (ms)' ?></label>
                          <input type="number" min="0" max="20000" step="100" name="deco_<?= e($d) ?>_delay" value="<?= e((string) $deco['delay']) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                          <label class="<?= $label ?>"><?= $de ? 'Dauer (ms)' : 'Süre (ms)' ?></label>
                          <input type="number" min="0" max="20000" step="100" name="deco_<?= e($d) ?>_duration" value="<?= e((string) $deco['duration']) ?>" class="<?= $input ?>">
                        </div>
                        <div class="flex items-end gap-4">
                          <label class="flex cursor-pointer items-center gap-2 pb-2 text-[0.78rem] text-ink">
                            <input type="checkbox" name="deco_<?= e($d) ?>_front" <?= $deco['front'] ? 'checked' : '' ?> class="h-4 w-4 accent-[#B08D57]">
                            <?= $de ? 'vor dem Text' : 'metnin önünde' ?>
                          </label>
                          <button name="was" value="deco-delete:<?= e($d) ?>"
                                  data-confirm="<?= $de ? 'Dieses Element entfernen?' : 'Bu öğe kaldırılsın mı?' ?>"
                                  class="ml-auto pb-2 text-[0.62rem] uppercase tracking-[0.14em] text-muted hover:text-red-700">
                            <?= $de ? 'Entfernen' : 'Kaldır' ?>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="mt-4 flex flex-wrap items-end gap-4 border border-dashed border-sand-deep p-4">
              <div class="min-w-[14rem] flex-1">
                <label class="<?= $label ?>"><?= $de ? 'Element hinzufügen' : 'Öğe ekle' ?></label>
                <input type="file" name="deco_neu" accept="image/png,image/webp,image/svg+xml,image/gif"
                       class="mt-2 w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
              </div>
              <button name="was" value="deco-add"
                      class="border border-ink px-6 py-3 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
                <?= $de ? 'Hochladen' : 'Yükle' ?>
              </button>
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

          <!-- Austausch -->
          <div class="border-t border-sand-deep pt-6">
            <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Sichern & übertragen' : 'Yedekle & aktar' ?></div>
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Fassung ' . (int) $theme['version'] . '. Verschickte Einladungen behalten die Fassung, mit der sie erstellt wurden – Änderungen hier gestalten keine fremde Feier um.'
                  : 'Sürüm ' . (int) $theme['version'] . '. Gönderilmiş davetiyeler oluşturuldukları sürümü korur – buradaki değişiklikler kimsenin düğününü değiştirmez.' ?>
            </p>
            <textarea name="austausch" rows="3" spellcheck="false" readonly
                      class="<?= $input ?> mt-3 resize-y font-mono text-[0.68rem]"><?= e((string) json_encode($theme, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></textarea>
            <p class="<?= $hint ?>"><?= $de ? 'Zum Übertragen kopieren und unten bei „Neues Thema“ einfügen.' : 'Aktarmak için kopyalayıp aşağıda „Yeni tema“ altına yapıştırın.' ?></p>
          </div>

          <div class="flex flex-wrap items-center gap-4 border-t border-sand-deep pt-6">
            <button class="bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
              <?= $de ? 'Speichern' : 'Kaydet' ?>
            </button>
            <button name="was" value="variant" class="border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-gold">
              <?= $de ? 'Variante anlegen' : 'Varyasyon oluştur' ?>
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
            <div class="flex items-center gap-1" data-theme-devices="<?= e($id) ?>">
              <?php foreach ([['w' => '', 'de' => 'Desktop', 'tr' => 'Masaüstü'], ['w' => '30rem', 'de' => 'Tablet', 'tr' => 'Tablet'], ['w' => '20rem', 'de' => 'Handy', 'tr' => 'Telefon']] as $device) : ?>
                <button type="button" data-theme-width="<?= e($device['w']) ?>"
                        class="border border-sand-deep px-2.5 py-1.5 text-[0.6rem] uppercase tracking-[0.12em] text-muted transition-colors hover:border-gold hover:text-gold">
                  <?= e($device[$locale] ?? $device['de']) ?>
                </button>
              <?php endforeach; ?>
            </div>
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
  
    <div class="mt-6">
      <label class="<?= $label ?>"><?= $de ? 'Oder ein gesichertes Thema einfügen' : 'Ya da yedeklenmiş bir temayı yapıştırın' ?></label>
      <textarea name="einfuegen" rows="3" spellcheck="false"
                class="<?= $input ?> mt-2 resize-y font-mono text-[0.68rem]"
                placeholder='{"id":"...","name":"..."}'></textarea>
      <p class="<?= $hint ?>">
        <?= $de
            ? 'Farben, Schriften und CSS kommen mit. Bilder nicht – die liegen auf der anderen Installation und müssen neu hochgeladen werden.'
            : 'Renkler, yazı tipleri ve CSS gelir. Görseller gelmez – onlar diğer kurulumda duruyor, yeniden yüklenmeli.' ?>
      </p>
    </div>
</form>
</div>
