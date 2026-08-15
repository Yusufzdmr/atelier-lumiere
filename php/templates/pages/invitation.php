<?php
/**
 * Die fertige Einladung.
 *
 * Der Umschlag liegt über der Karte, bis jemand darauf tippt – erst dann
 * startet auch die Musik (Browser lassen Ton ohne Zutun nicht zu).
 *
 * @var string $locale
 * @var array<string,mixed> $invitation
 * @var array<string,mixed>|null $guest  persönlich adressierte Fassung
 * @var array<string,mixed> $theme
 * @var string $style
 * @var string $dateLong
 * @var string $weekday
 * @var list<array<string,mixed>> $rsvps
 * @var bool $sent
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Video;

$de = $locale === 'de';
$sections = (array) ($invitation['sections'] ?? []);
$events = (array) ($invitation['events'] ?? []);
$first = (array) ($events[0] ?? []);
$photos = (array) ($invitation['photos'] ?? []);
$bride = (string) ($invitation['bride'] ?? '');
$groom = (string) ($invitation['groom'] ?? '');
$initials = mb_strtoupper(mb_substr($bride, 0, 1) . mb_substr($groom, 0, 1));
// Persönliche Fassung: „Liebe Familie Müller“ steht über allem anderen.
$guestName = (string) (($guest ?? null)['name'] ?? '');

/**
 * Die Schmuckelemente eines Ortes ausgeben.
 *
 * Position, Groesse und Bewegung stehen im Stilblock des Themas; hier steht
 * nur das Bild mit seiner Klasse. Alternativtext bleibt leer: Blumen und
 * Rahmen sind Zierde, keine Information – ein Vorleseprogramm soll sie
 * ueberspringen.
 */
$decorations = static function (string $spot) use ($theme): string {
    $html = '';
    foreach ((array) ($theme['decorations'] ?? []) as $deco) {
        if (!is_array($deco) || (string) ($deco['src'] ?? '') === '' || ($deco['spot'] ?? 'card') !== $spot) {
            continue;
        }
        $html .= '<img src="' . e((string) $deco['src']) . '" alt="" aria-hidden="true" loading="lazy"'
            . ' class="t-deco-' . e((string) $deco['id']) . '">';
    }
    return $html;
};
$field = 'w-full border-b bg-transparent px-0 py-2.5 text-[0.95rem] outline-none';
$coming = 0;
foreach ($rsvps as $rsvp) {
    if (!empty($rsvp['coming'])) {
        $coming += (int) ($rsvp['count'] ?? 1);
    }
}
?>
<style><?= $style ?></style>

<div class="theme-<?= e((string) $theme['id']) ?> relative min-h-screen overflow-hidden" style="background: <?= e((string) $theme['bg']) ?>">
  <?= $decorations('page') ?>
  <?php /* Umschlag – verschwindet nach dem Antippen */ ?>
  <div class="fixed inset-0 z-50 flex items-center justify-center px-6 transition-opacity duration-700"
       data-envelope data-animation="<?= e((string) $theme['animation']) ?>"
       style="background: <?= e((string) $theme['bg']) ?>">
    <button type="button" data-envelope-open class="t-envelope relative flex h-56 w-full max-w-sm items-center justify-center border shadow-[0_30px_60px_-25px_rgba(0,0,0,.45)]"
            style="background: <?= e((string) $theme['envelope']) ?>; border-color: <?= e((string) $theme['envelopeEdge']) ?>"
            aria-label="<?= $de ? 'Einladung öffnen' : 'Davetiyeyi aç' ?>">
      <span class="t-seal flex h-16 w-16 items-center justify-center rounded-full font-display text-lg"
            style="background: <?= e((string) $theme['seal']) ?>; color: <?= e((string) $theme['sealText']) ?>"><?= e($initials) ?></span>
      <?= $decorations('envelope') ?>
      <span class="absolute bottom-5 text-[0.6rem] uppercase tracking-[0.28em]" style="color: <?= e((string) $theme['soft']) ?>">
        <?= $de ? 'Tippen zum Öffnen' : 'Açmak için dokunun' ?>
      </span>
    </button>
  </div>

  <div class="mx-auto max-w-2xl px-5 py-16 sm:py-24">
    <div class="t-card relative overflow-hidden px-6 py-14 text-center sm:px-12"
         style="background: <?= e((string) $theme['paper']) ?>; color: <?= e((string) $theme['fg']) ?>; border: 1px solid <?= e((string) $theme['paperEdge']) ?>">

      <?= $decorations('card') ?>

      <div class="relative">
        <?php if ($guestName !== '') : ?>
          <div class="mb-9">
            <div class="font-display text-xl font-light italic sm:text-2xl" style="color: <?= e((string) $theme['accent']) ?>">
              <?= $de ? 'Liebe' : 'Sayın' ?> <?= e($guestName) ?>,
            </div>
            <div class="mx-auto mt-6 h-px w-16" style="background: <?= e((string) $theme['accentSoft']) ?>"></div>
          </div>
        <?php endif; ?>

        <div class="text-[0.58rem] uppercase tracking-[0.34em]" style="color: <?= e((string) $theme['soft']) ?>">
          <?= e(I18n::t('invite.weMarry')) ?>
        </div>

        <h1 class="t-name font-display mt-6 flex flex-col leading-tight">
          <span class="text-4xl font-light sm:text-5xl"><?= e($bride) ?></span>
          <span class="my-1 text-2xl italic" style="color: <?= e((string) $theme['accent']) ?>">&amp;</span>
          <span class="text-4xl font-light sm:text-5xl"><?= e($groom) ?></span>
        </h1>

        <div class="mx-auto mt-8 h-px w-28" style="background: <?= e((string) $theme['accent']) ?>"></div>

        <?php foreach ($events as $i => $event) : ?>
          <div class="<?= $i > 0 ? 'mt-8 border-t pt-8' : 'mt-8' ?>" style="<?= $i > 0 ? 'border-color:' . e((string) $theme['paperEdge']) : '' ?>">
            <?php if (count($events) > 1 && ($event['name'] ?? '') !== '') : ?>
              <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e((string) $event['name']) ?></div>
            <?php endif; ?>
            <div class="t-date mt-2 text-[0.72rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['soft']) ?>">
              <?= e($i === 0 ? $weekday : '') ?>
            </div>
            <div class="font-display mt-1 text-2xl font-light"><?= e(\Atelier\Dates::long((string) ($event['date'] ?? ''), $locale)) ?></div>
            <?php if (($event['time'] ?? '') !== '') : ?>
              <div class="mt-1 text-[0.9rem]" style="color: <?= e((string) $theme['soft']) ?>"><?= e((string) $event['time']) ?></div>
            <?php endif; ?>
            <?php if (($event['venue'] ?? '') !== '') : ?>
              <div class="mt-3 text-[0.95rem]"><?= e((string) $event['venue']) ?></div>
            <?php endif; ?>
            <?php if (!empty($sections['location']) && ($event['address'] ?? '') !== '') : ?>
              <div class="text-[0.85rem]" style="color: <?= e((string) $theme['soft']) ?>"><?= e((string) $event['address']) ?></div>
              <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(rawurlencode((string) $event['address'])) ?>"
                 target="_blank" rel="noopener noreferrer"
                 class="mt-3 inline-block border px-5 py-2 text-[0.62rem] uppercase tracking-[0.2em]"
                 style="border-color: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['accent']) ?>">
                <?= e(I18n::t('contact.mapRoute')) ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php if (($invitation['message'] ?? '') !== '') : ?>
          <p class="mx-auto mt-10 max-w-md text-[0.98rem] leading-relaxed" style="color: <?= e((string) $theme['soft']) ?>">
            <?= e((string) $invitation['message']) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($sections['countdown']) && ($first['date'] ?? '') !== '') : ?>
          <div class="mt-10 flex justify-center gap-6" data-countdown="<?= e((string) $first['date'] . 'T' . ((string) ($first['time'] ?? '12:00'))) ?>">
            <?php foreach (['days' => 'countdownDays', 'hours' => 'countdownHours', 'minutes' => 'countdownMin', 'seconds' => 'countdownSec'] as $key => $dictKey) : ?>
              <div>
                <div class="font-display text-3xl font-light" data-<?= e($key) ?>>00</div>
                <div class="mt-1 text-[0.55rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.' . $dictKey)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['family']) && !empty($invitation['families'])) : ?>
          <div class="mt-10 text-[0.8rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>">
            <?= e((string) ($invitation['families']['bride'] ?? '')) ?> · <?= e((string) ($invitation['families']['groom'] ?? '')) ?>
          </div>
        <?php endif; ?>

        <?php if ($photos !== []) : ?>
          <div class="mt-12 grid grid-cols-2 gap-3">
            <?php foreach (array_slice($photos, 0, 4) as $i => $photo) : ?>
              <img src="<?= e((string) $photo) ?>" alt="<?= e($bride . ' & ' . $groom) ?>" loading="lazy" decoding="async"
                   class="h-full w-full object-cover" style="aspect-ratio: <?= $i % 3 === 0 ? '3/4' : '1/1' ?>">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['program']) && ($invitation['program'] ?? []) !== []) : ?>
          <div class="mt-12 text-left">
            <div class="text-center text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.programTitle')) ?></div>
            <ol class="mx-auto mt-6 max-w-sm space-y-4">
              <?php foreach ((array) $invitation['program'] as $item) : ?>
                <li class="flex gap-5">
                  <span class="w-14 shrink-0 text-[0.8rem]" style="color: <?= e((string) $theme['accent']) ?>"><?= e((string) ($item['time'] ?? '')) ?></span>
                  <span class="text-[0.92rem]"><?= e((string) ($item['title'] ?? '')) ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['menu']) && ($invitation['menu'] ?? []) !== []) : ?>
          <div class="mt-12">
            <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.menuTitle')) ?></div>
            <ul class="mt-5 space-y-2 text-[0.92rem]">
              <?php foreach ((array) $invitation['menu'] as $dish) : ?>
                <li><?= e((string) $dish) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (!empty($sections['video']) && Video::isSupported((string) ($invitation['videoUrl'] ?? ''))) : ?>
          <div class="mt-12"><?= Video::embedBox((string) $invitation['videoUrl'], $bride . ' & ' . $groom, (string) ($photos[0] ?? '')) ?></div>
        <?php endif; ?>

        <?php if (!empty($sections['rsvp'])) : ?>
          <div class="mt-14 border-t pt-10" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
            <div class="text-[0.6rem] uppercase tracking-[0.24em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e(I18n::t('invite.rsvpTitle')) ?></div>

            <?php if ($sent) : ?>
              <p class="mt-5 text-[0.95rem]"><?= e(I18n::t('invite.rsvpThanks')) ?></p>
            <?php else : ?>
              <form method="post" class="mx-auto mt-6 max-w-sm space-y-5 text-left">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpName')) ?> *</label>
                  <?php /* Wer über seinen persönlichen Link kommt, muss den eigenen Namen nicht abtippen. */ ?>
                  <input name="name" required value="<?= e($guestName) ?>" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <div class="flex gap-4">
                  <label class="flex flex-1 cursor-pointer items-center gap-2 border px-4 py-2.5 text-[0.85rem]" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
                    <input type="radio" name="coming" value="1" checked class="h-4 w-4"> <?= e(I18n::t('invite.rsvpComing')) ?>
                  </label>
                  <label class="flex flex-1 cursor-pointer items-center gap-2 border px-4 py-2.5 text-[0.85rem]" style="border-color: <?= e((string) $theme['paperEdge']) ?>">
                    <input type="radio" name="coming" value="0" class="h-4 w-4"> <?= e(I18n::t('invite.rsvpNotComing')) ?>
                  </label>
                </div>

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpCount')) ?></label>
                  <input type="number" name="count" value="1" min="1" max="20" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <div>
                  <label class="block text-[0.6rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['soft']) ?>"><?= e(I18n::t('invite.rsvpNote')) ?></label>
                  <input name="note" class="<?= $field ?>" style="border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['fg']) ?>">
                </div>

                <button class="w-full px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em]"
                        style="background: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['paper']) ?>">
                  <?= e(I18n::t('invite.rsvpSend')) ?>
                </button>
              </form>
            <?php endif; ?>

            <?php if ($coming > 0) : ?>
              <p class="mt-6 text-[0.75rem] uppercase tracking-[0.18em]" style="color: <?= e((string) $theme['soft']) ?>">
                <?= $coming ?> <?= $de ? 'Zusagen' : 'katılım' ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (($invitation['closing'] ?? '') !== '') : ?>
          <p class="mt-12 font-display text-lg italic"><?= e((string) $invitation['closing']) ?></p>
        <?php endif; ?>

        <?php if (($invitation['hashtag'] ?? '') !== '') : ?>
          <div class="mt-6 text-[0.72rem] uppercase tracking-[0.2em]" style="color: <?= e((string) $theme['accent']) ?>"><?= e((string) $invitation['hashtag']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($sections['music']) && ($invitation['musicUrl'] ?? '') !== '') : ?>
      <?php /* Ton startet erst nach dem Öffnen – Browser erlauben es nicht anders. */ ?>
      <audio data-music loop preload="none" src="<?= e((string) $invitation['musicUrl']) ?>"></audio>
      <button type="button" data-music-toggle
              class="fixed bottom-5 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full shadow-lg"
              style="background: <?= e((string) $theme['accent']) ?>; color: <?= e((string) $theme['paper']) ?>"
              aria-label="<?= $de ? 'Musik' : 'Müzik' ?>">♪</button>
    <?php endif; ?>
  </div>
</div>
