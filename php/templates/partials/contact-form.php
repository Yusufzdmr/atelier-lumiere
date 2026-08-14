<?php
/**
 * Anfrageformular.
 *
 * Ohne JavaScript nutzbar: Es ist ein normales Formular, das an die
 * Kontaktseite sendet und dort mit einer Bestätigung antwortet. Ein Skript
 * kann später davorgeschaltet werden, ohne das Markup zu ändern.
 *
 * @var string $locale
 * @var string $preset   Vorbelegung des Ortsfelds (Stadt- und Locationseiten)
 * @var string $csrf
 * @var array<string,string> $errors
 * @var array<string,string> $values
 */

use function Atelier\e;
use Atelier\I18n;

$preset = $preset ?? '';
$errors = $errors ?? [];
$values = $values ?? [];
$field = 'w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/60 focus:border-gold';
$label = 'block text-[0.68rem] uppercase tracking-[0.2em] text-muted';
$old = static fn (string $key): string => (string) ($values[$key] ?? '');
?>
<form method="post" action="<?= e(I18n::path('/kontakt', $locale)) ?>#anfrage" class="space-y-7">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <?php /* Falle für Spam-Roboter – für Menschen unsichtbar. */ ?>
  <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

  <div class="grid gap-7 sm:grid-cols-2">
    <div>
      <label class="<?= $label ?>" for="name"><?= e(I18n::t('contact.name')) ?> *</label>
      <input id="name" name="name" required value="<?= e($old('name')) ?>" class="<?= $field ?>">
    </div>
    <div>
      <label class="<?= $label ?>" for="email"><?= e(I18n::t('contact.email')) ?> *</label>
      <input id="email" name="email" type="email" required value="<?= e($old('email')) ?>" class="<?= $field ?>">
    </div>
    <div>
      <label class="<?= $label ?>" for="phone"><?= e(I18n::t('contact.phone')) ?></label>
      <input id="phone" name="phone" value="<?= e($old('phone')) ?>" class="<?= $field ?>">
    </div>
    <div>
      <label class="<?= $label ?>" for="date"><?= e(I18n::t('contact.date')) ?></label>
      <input id="date" name="date" type="date" value="<?= e($old('date')) ?>" class="<?= $field ?>">
    </div>
    <div>
      <label class="<?= $label ?>" for="location"><?= e(I18n::t('contact.location')) ?></label>
      <input id="location" name="location" value="<?= e($old('location') !== '' ? $old('location') : $preset) ?>" class="<?= $field ?>">
    </div>
    <div>
      <label class="<?= $label ?>" for="guests"><?= e(I18n::t('contact.guests')) ?></label>
      <input id="guests" name="guests" inputmode="numeric" value="<?= e($old('guests')) ?>" class="<?= $field ?>">
    </div>
  </div>

  <div>
    <label class="<?= $label ?>" for="service"><?= e(I18n::t('contact.service')) ?></label>
    <select id="service" name="service" class="<?= $field ?>">
      <?php
      $options = [
          'foto'        => $locale === 'de' ? 'Nur Fotografie' : 'Sadece fotoğraf',
          'video'       => $locale === 'de' ? 'Nur Film' : 'Sadece video',
          'beides'      => $locale === 'de' ? 'Foto & Film' : 'Foto & video',
          'standesamt'  => $locale === 'de' ? 'Standesamt / Verlobung' : 'Nikah / nişan',
      ];
      foreach ($options as $value => $text) :
      ?>
        <option value="<?= e($value) ?>" <?= $old('service') === $value ? 'selected' : '' ?>><?= e($text) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label class="<?= $label ?>" for="message"><?= e(I18n::t('contact.message')) ?> *</label>
    <textarea id="message" name="message" required rows="5" class="<?= $field ?> resize-none"><?= e($old('message')) ?></textarea>
  </div>

  <label class="flex cursor-pointer items-start gap-3 text-[0.82rem] leading-relaxed text-muted">
    <input type="checkbox" name="consent" required class="mt-1 h-4 w-4 shrink-0 accent-[#B08D57]">
    <span>
      <?= e(I18n::t('contact.consent')) ?>
      <a href="<?= e(I18n::path('/datenschutz', $locale)) ?>" class="text-gold underline-offset-4 hover:underline">*</a>
    </span>
  </label>

  <?php if ($errors !== []) : ?>
    <p class="text-sm text-red-700"><?= e(I18n::t('contact.error')) ?></p>
  <?php endif; ?>

  <button type="submit" class="w-full bg-ink px-8 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold sm:w-auto">
    <?= e(I18n::t('contact.submit')) ?>
  </button>
</form>
