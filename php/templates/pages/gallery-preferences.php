<?php
/**
 * Zweiter Reiter der Kundengalerie: Bearbeitungsstil und Fragebogen.
 *
 * Die Angaben landen bei der Anfrage, nicht erst beim Schnitt – damit sich
 * später keine Nacharbeit ergibt, die sich mit einer Frage vorher vermeiden
 * ließe.
 *
 * @var string $locale
 * @var array<string,mixed> $gallery
 * @var array<string,mixed>|null $preferences
 * @var bool $preferencesFilled
 * @var list<array<string,mixed>> $styles
 * @var list<array<string,mixed>> $questions
 * @var bool $saved
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\View;

$couple = (string) ($gallery['couple'] ?? '');
$code = (string) ($gallery['code'] ?? '');
$de = $locale === 'de';

$selectedStyle = $preferences['style'] ?? null;
$selectedStyle = $selectedStyle === null ? null : (int) $selectedStyle;
$answers = (array) ($preferences['answers'] ?? []);
?>
<?php /*
 * php/public/assets/style.css ist vorab kompiliert (kein JIT) - eine Klasse,
 * die dort nicht vorkommt, tut still gar nichts. `peer`/`peer-checked:*` kam
 * im ganzen Projekt noch nirgends vor, also von Hand statt geraten.
 */ ?>
<style>
  .pref-style-input { position: absolute; inset: 0; z-index: 1; width: 100%; height: 100%; margin: 0; cursor: pointer; opacity: 0; }
  .pref-style-card { transition: border-color .15s ease; }
  .pref-style-input:checked ~ .pref-style-card { border-color: var(--color-gold); }
  .pref-style-check { display: none; }
  .pref-style-input:checked ~ .pref-style-check { display: flex; }
</style>
<div class="pb-32">

  <div class="mx-auto max-w-5xl px-5 pt-32 sm:px-8 sm:pt-40">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <div class="eyebrow"><?= e(I18n::t('gallery.protected')) ?></div>
        <h1 class="headline mt-3 text-4xl sm:text-5xl"><?= e($couple) ?></h1>
      </div>
      <a href="<?= e(I18n::path('/galerie/abmelden', $locale)) ?>"
         class="border border-sand-deep px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
        <?= $de ? 'Abmelden' : 'Sign out' ?>
      </a>
    </div>

    <?= View::partial('partials/gallery-tabs', [
        'locale'            => $locale,
        'code'              => $code,
        'active'            => 'preferences',
        'preferencesFilled' => $preferencesFilled,
    ]) ?>

    <p class="mt-6 max-w-xl border-l-2 border-gold pl-4 text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Sagt uns vorher, wie eure Bilder wirken sollen – so entsteht das Album genau einmal, nicht zweimal.'
        : 'Tell us beforehand how your pictures should look – that way the album gets made once, not twice.' ?>
    </p>

    <?php if ($saved) : ?>
      <p class="mt-6 border border-gold/40 bg-gold/15 px-5 py-3 text-[0.85rem] text-ink">
        <?= $de ? 'Danke – eure Angaben sind gespeichert.' : 'Thank you – your answers are saved.' ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if ($styles === [] && $questions === []) : ?>
    <div class="mx-auto mt-12 max-w-5xl px-5 sm:px-8">
      <p class="border border-sand-deep px-5 py-4 text-[0.85rem] text-muted">
        <?= $de
          ? 'Dieser Bereich ist noch nicht eingerichtet. Meldet euch einfach direkt bei uns.'
          : 'This section is not set up yet. Just get in touch with us directly.' ?>
      </p>
    </div>
  <?php else : ?>
    <form method="post" class="mx-auto mt-12 max-w-5xl px-5 sm:px-8">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

      <?php if ($styles !== []) : ?>
        <div>
          <h2 class="text-[0.68rem] uppercase tracking-[0.2em] text-muted">
            <?= $de ? 'Bearbeitungsstil' : 'Editing style' ?>
          </h2>
          <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <?php foreach ($styles as $i => $style) : ?>
              <?php
              $uploads = array_values(array_filter((array) ($style['uploads'] ?? []), 'is_string'));
              $name = I18n::pick($style['name'] ?? null, $locale);
              $description = I18n::pick($style['description'] ?? null, $locale);
              ?>
              <label class="relative block cursor-pointer">
                <input type="radio" name="style" value="<?= $i ?>" class="pref-style-input"
                       <?= $selectedStyle === $i ? 'checked' : '' ?>>
                <div class="pref-style-card overflow-hidden border border-sand-deep">
                  <div class="aspect-[4/5] w-full bg-sand">
                    <?php if ($uploads !== []) : ?>
                      <img src="<?= e($uploads[0]) ?>" alt="<?= e($name) ?>" loading="lazy" decoding="async"
                           class="h-full w-full object-cover">
                    <?php endif; ?>
                  </div>
                  <div class="p-4">
                    <div class="text-[0.9rem] text-ink"><?= e($name) ?></div>
                    <?php if ($description !== '') : ?>
                      <p class="mt-1 text-[0.8rem] leading-relaxed text-muted"><?= e($description) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="pref-style-check pointer-events-none absolute right-3 top-2 h-6 w-6 items-center justify-center rounded-full bg-gold text-[0.7rem] text-cream">✓</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($questions !== []) : ?>
        <div class="mt-14 space-y-8">
          <h2 class="text-[0.68rem] uppercase tracking-[0.2em] text-muted">
            <?= $de ? 'Ein paar Fragen zu Aufnahme und Schnitt' : 'A few questions about the shoot and the edit' ?>
          </h2>

          <?php foreach ($questions as $i => $question) : ?>
            <?php
            $label = I18n::pick($question['question'] ?? null, $locale);
            $type = (string) ($question['type'] ?? 'text');
            $answer = (string) ($answers[$i] ?? '');
            ?>
            <div class="border-t border-sand-deep pt-6">
              <label class="block text-[0.85rem] text-ink"><?= e($label) ?></label>

              <?php if ($type === 'choice') : ?>
                <?php $choices = I18n::pickList($question['choices'] ?? null, $locale); ?>
                <div class="mt-3 space-y-3">
                  <?php foreach ($choices as $choice) : ?>
                    <label class="flex cursor-pointer items-center gap-3 text-[0.88rem] text-ink">
                      <input type="radio" name="answer[<?= $i ?>]" value="<?= e($choice) ?>"
                             <?= $answer === $choice ? 'checked' : '' ?>
                             class="h-4 w-4 border-sand-deep text-gold">
                      <?= e($choice) ?>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php else : ?>
                <textarea name="answer[<?= $i ?>]" rows="3"
                          class="mt-3 w-full border-b border-sand-deep bg-transparent px-0 py-2 text-[0.9rem] text-ink outline-none focus:border-gold"><?= e($answer) ?></textarea>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <button type="submit" class="mt-12 bg-ink px-8 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Angaben speichern' : 'Save answers' ?>
      </button>
    </form>
  <?php endif; ?>
</div>
