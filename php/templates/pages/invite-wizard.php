<?php
/**
 * Assistent für die digitale Einladung.
 *
 * Ein Formular, fünf Schritte. Das Skript blendet die Schritte ein und aus und
 * hält die Vorschau aktuell; ohne Skript stehen alle Schritte untereinander
 * und lassen sich genauso absenden.
 *
 * @var string $locale
 * @var list<array<string,mixed>> $themes
 * @var array<string,mixed> $campaign
 * @var array<string,bool> $sections
 * @var array<string,string> $values     Werte aus einem gespeicherten Entwurf
 * @var string $token
 * @var string $csrf
 * @var string $error
 * @var array<string,mixed>|null $done
 * @var string $draftLink
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Invitations;
use Atelier\Pricing;
use Atelier\Ui;

$de = $locale === 'de';
$p = static fn (string $to): string => I18n::path($to, $locale);
$field = 'w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/50 focus:border-gold';
$label = 'block text-[0.64rem] uppercase tracking-[0.2em] text-muted';
$old = static fn (string $key, string $fallback = ''): string => (string) ($values[$key] ?? $fallback);

$eventTypes = [
    'wedding'      => ['de' => 'Hochzeit', 'en' => 'Wedding'],
    'multi'        => ['de' => 'Mehrere Feiern (z. B. Henna + Hochzeit)', 'en' => 'Several celebrations (e.g. henna + wedding)'],
    'henna'        => ['de' => 'Henna-Abend', 'en' => 'Henna night'],
    'engagement'   => ['de' => 'Verlobung', 'en' => 'Engagement'],
    'circumcision' => ['de' => 'Beschneidungsfest', 'en' => 'Circumcision celebration'],
    'birthday'     => ['de' => 'Geburtstag', 'en' => 'Birthday'],
    'corporate'    => ['de' => 'Firmenfeier', 'en' => 'Company celebration'],
];

$sectionLabels = [
    'rsvp'      => ['de' => 'Zusagen (RSVP)', 'en' => 'Replies (RSVP)'],
    'location'  => ['de' => 'Ort & Karte', 'en' => 'Place & map'],
    'countdown' => ['de' => 'Countdown', 'en' => 'Countdown'],
    'program'   => ['de' => 'Programm', 'en' => 'Programme'],
    'family'    => ['de' => 'Familien', 'en' => 'Families'],
    'menu'      => ['de' => 'Menü', 'en' => 'Menu'],
    'music'     => ['de' => 'Musik', 'en' => 'Music'],
    'video'     => ['de' => 'Video-Intro', 'en' => 'Video intro'],
];

$steps = $de
    ? ['Anlass & Design', 'Eure Angaben', 'Feier', 'Abschnitte', 'Fotos & Link']
    : ['Occasion & design', 'Your details', 'Celebration', 'Sections', 'Photos & link'];
?>
<?= Ui::pageHero('invite-hero', I18n::t('invite.title'), I18n::t('nav.invitation'), I18n::t('invite.lead')) ?>

<?= Ui::sectionOpen() ?>
  <?= Ui::breadcrumbs([['name' => 'Home', 'href' => $p('')], ['name' => I18n::t('invite.title')]]) ?>

  <?php if ($done !== null) : ?>
    <!-- ---------------------------- Fertig ---------------------------- -->
    <div class="mx-auto max-w-2xl text-center">
      <div data-track-event="invite" data-track-value="<?= (int) $done['price'] ?>" hidden></div>
      <div class="eyebrow">✓</div>
      <h2 class="headline mt-3 text-3xl"><?= e(I18n::t('invite.doneTitle')) ?></h2>
      <p class="mt-4 text-sm text-muted"><?= e(I18n::t('invite.doneText')) ?></p>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <code class="flex-1 overflow-x-auto border border-sand-deep bg-sand/40 px-4 py-3.5 text-[0.8rem] text-ink"><?= e((string) $done['url']) ?></code>
        <button type="button" data-copy="<?= e((string) $done['url']) ?>"
                class="border border-ink px-6 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream">
          <?= e(I18n::t('invite.copy')) ?>
        </button>
      </div>

      <?php if ((int) $done['price'] > 0) : ?>
        <div class="mt-8 border border-gold/50 bg-sand/40 p-6 text-left">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <div class="text-[0.6rem] uppercase tracking-[0.24em] text-gold"><?= e(I18n::t('invite.payTitle')) ?></div>
              <div class="font-display mt-1 text-2xl font-light text-ink"><?= e(Pricing::euro((int) $done['price'])) ?></div>
            </div>
            <a href="<?= e($p('/einladung/' . $done['slug'] . '/zahlung')) ?>"
               class="rounded-full bg-[#ffc439] px-8 py-3.5 text-[0.8rem] font-medium text-[#003087] transition-opacity hover:opacity-90">
              PayPal · <?= e(I18n::t('invite.payNow')) ?>
            </a>
          </div>
          <p class="mt-4 text-[0.74rem] leading-relaxed text-muted"><?= e(I18n::t('invite.payNote')) ?></p>
        </div>
      <?php endif; ?>

      <?php /* Ohne Konto ist dieser Link der einzige Weg zurueck zur Gaesteliste. */ ?>
      <div class="mt-8 border border-sand-deep p-6 text-left">
        <div class="text-[0.6rem] uppercase tracking-[0.24em] text-gold">
          <?= $de ? 'Persönliche Einladungen' : 'Personal invitations' ?>
        </div>
        <p class="mt-3 text-[0.85rem] leading-relaxed text-muted">
          <?= $de
            ? 'Ihr könnt jede Familie einzeln ansprechen – „Liebe Familie Müller“ – und jeder bekommt seinen eigenen Link. Das geht hier, jetzt oder später:'
            : 'You can address each family by name – &bdquo;Dear Müller family&ldquo; – and each one gets its own link. Here, now or later:' ?>
        </p>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
          <code class="flex-1 overflow-x-auto border border-sand-deep bg-cream px-4 py-3 text-[0.76rem] text-ink"><?= e((string) $done['manage']) ?></code>
          <button type="button" data-copy="<?= e((string) $done['manage']) ?>"
                  class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink hover:bg-ink hover:text-cream">
            <?= e(I18n::t('invite.copy')) ?>
          </button>
        </div>
        <p class="mt-3 text-[0.74rem] leading-relaxed text-muted">
          <?= $de
            ? 'Diesen Link gut aufheben und nicht mit den Einladungen weitergeben – er gehört euch.'
            : 'Keep this link safe and do not pass it on with the invitations – it belongs to you.' ?>
        </p>

        <?php
        /*
         * Ohne diesen Link kommt niemand mehr an die Gästeliste. Deshalb steht
         * hier, ob er auch per Mail unterwegs ist – und wenn nicht, dass das
         * Fenster jetzt nicht einfach zugehen sollte.
         */
        ?>
        <?php if (!empty($done['mailed'])) : ?>
          <p class="mt-4 border border-gold/50 bg-sand/40 px-4 py-3 text-[0.8rem] leading-relaxed text-ink">
            <?= $de
              ? 'Beide Links sind auch an ' . e((string) $done['email']) . ' unterwegs.'
              : 'Both links are also on their way to ' . e((string) $done['email']) . '.' ?>
          </p>
        <?php else : ?>
          <p class="mt-4 border border-red-700/40 bg-red-50 px-4 py-3 text-[0.8rem] leading-relaxed text-red-800">
            <?= $de
              ? 'Achtung: Dieser Link ist nirgendwo sonst gespeichert. Kopiert ihn jetzt oder schickt ihn euch selbst – wenn ihr das Fenster schließt, ist er weg.'
              : 'Careful: this link is not stored anywhere else. Copy it now or send it to yourself – if you close the window, it is gone.' ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <?= Ui::btn((string) $done['manage'], $de ? 'Gästeliste öffnen' : 'Open guest list', 'solid') ?>
        <?= Ui::btn((string) $done['path'], I18n::t('invite.openInvite'), 'outline') ?>
        <?= Ui::btn($p('/einladung'), I18n::t('invite.createAnother'), 'outline') ?>
      </div>
    </div>

  <?php else : ?>
    <!-- ---------------------------- Assistent ---------------------------- -->
    <?php if ($draftLink !== '') : ?>
      <div class="mb-10 border border-gold/50 bg-sand/40 p-6">
        <p class="text-[0.85rem] text-ink"><?= e(I18n::t('invite.draftSaved')) ?></p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
          <code class="flex-1 overflow-x-auto border border-sand-deep bg-cream px-4 py-3 text-[0.78rem] text-ink"><?= e($draftLink) ?></code>
          <button type="button" data-copy="<?= e($draftLink) ?>"
                  class="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream">
            <?= e(I18n::t('invite.copy')) ?>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
      <p class="mb-8 border border-red-700/40 bg-red-50 px-5 py-3 text-sm text-red-700">
        <?= $error === 'fields'
            ? ($de ? 'Bitte Namen und mindestens ein Datum ausfüllen.' : 'Please fill in the names and at least one date.')
            : ($de ? 'Das hat nicht geklappt. Bitte die Seite neu laden und erneut versuchen.' : 'That did not work. Please reload the page and try again.') ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($campaign['active']) && (string) ($campaign['code'] ?? '') !== '') : ?>
      <div class="mb-10 border-l-2 border-gold bg-sand/40 p-6">
        <p class="text-[0.92rem] leading-relaxed text-ink"><?= e(I18n::t('invite.freeNote')) ?></p>
        <p class="mt-2 text-[0.82rem] text-muted">
          <?= $de ? 'Aktionscode: ' : 'Promotion code: ' ?><code class="text-gold"><?= e((string) $campaign['code']) ?></code>
        </p>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid gap-14 lg:grid-cols-[1.15fr_0.85fr]" data-wizard>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <input type="hidden" name="was" value="create" data-wizard-was>

      <div>
        <!-- Schrittanzeige -->
        <ol class="mb-10 flex flex-wrap gap-x-6 gap-y-2 border-b border-sand-deep pb-4 text-[0.62rem] uppercase tracking-[0.16em]" data-steps>
          <?php foreach ($steps as $i => $caption) : ?>
            <li data-step-label="<?= $i ?>" class="text-muted"><?= $i + 1 ?>. <?= e($caption) ?></li>
          <?php endforeach; ?>
        </ol>

        <!-- 1 – Anlass & Design -->
        <fieldset data-step="0" class="space-y-8">
          <div>
            <label class="<?= $label ?>" for="eventType"><?= $de ? 'Anlass' : 'Occasion' ?></label>
            <select id="eventType" name="eventType" class="<?= $field ?>" data-event-type>
              <?php foreach ($eventTypes as $key => $caption) : ?>
                <option value="<?= e($key) ?>" <?= $old('eventType', 'wedding') === $key ? 'selected' : '' ?>
                        data-line="<?= e(Invitations::occasionLine($key, $locale)) ?>"><?= e($caption[$locale]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <div class="<?= $label ?>"><?= e(I18n::t('invite.design')) ?></div>
            <p class="mt-2 text-[0.78rem] text-muted">
              <?= e(I18n::t('invite.designHint')) ?>
              <?php /* Die Kacheln hier sind klein. Wer erst schauen will, geht ins Schaufenster. */ ?>
              <a href="<?= e(I18n::path('/designs', $locale)) ?>" target="_blank" rel="noopener"
                 class="ml-1 whitespace-nowrap text-gold underline-offset-4 hover:underline">
                <?= $locale === 'de' ? 'Alle Designs ansehen ↗' : 'See all designs ↗' ?>
              </a>
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
              <?php foreach ($themes as $i => $theme) : ?>
                <?php $checked = $old('theme', (string) $themes[0]['id']) === (string) $theme['id']; ?>
                <label class="block cursor-pointer border p-4 transition-colors <?= $checked ? 'border-gold' : 'border-sand-deep hover:border-muted' ?>"
                       data-theme-option="<?= e((string) $theme['id']) ?>">
                  <input type="radio" name="theme" value="<?= e((string) $theme['id']) ?>" class="sr-only" <?= $checked ? 'checked' : '' ?>
                         data-theme-radio
                         data-colors='<?= e((string) json_encode([
                             'paper'  => $theme['paper'],
                             'fg'     => $theme['fg'],
                             'soft'   => $theme['soft'],
                             'accent' => $theme['accent'],
                             'bg'     => $theme['bg'],
                             'edge'   => $theme['paperEdge'],
                             'seal'   => $theme['seal'],
                             'sealText' => $theme['sealText'],
                             'image'  => $theme['image'],
                         ], JSON_UNESCAPED_UNICODE)) ?>'>
                  <span class="flex h-16 items-center justify-center border"
                        style="background: <?= e((string) $theme['paper']) ?>; border-color: <?= e((string) $theme['paperEdge']) ?>; color: <?= e((string) $theme['accent']) ?>">✦</span>
                  <span class="mt-3 block font-display text-lg text-ink"><?= e((string) $theme['name']) ?></span>
                  <span class="mt-1 block text-[0.72rem] text-muted"><?= e((string) ($theme['sub'][$locale] ?? '')) ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <?php
            /*
             * Die Bewegungen gehoeren dem Paar, nicht nur dem Betrieb.
             * Im Panel stellt der Betrieb ein, was ein Design mitbringt;
             * hier darf das Paar davon abweichen. Leer heisst „so wie das
             * Design es vorsieht" – niemand muss sich damit befassen.
             */
            $tl = $locale === 'de' ? 'de' : 'en';
            $axes = [
                ['anim_intro',    $de ? 'Eröffnungsszene' : 'Opening scene',        \Atelier\Themes::INTROS,          'introLabel'],
                ['anim_idle',     $de ? 'Geschlossenes Kuvert' : 'Closed envelope', \Atelier\Themes::IDLES,           'idleLabel'],
                ['anim_card',     $de ? 'Karte kommt herein' : 'The card arrives',  \Atelier\Themes::ANIMATIONS,      'animationLabel'],
                ['anim_name',     $de ? 'Eure Namen' : 'Your names',                \Atelier\Themes::NAME_ANIMATIONS, 'nameAnimationLabel'],
                ['anim_particle', $de ? 'Was schwebt' : 'What drifts',              \Atelier\Themes::PARTICLES,       'particleLabel'],
                ['anim_reveal',   $de ? 'Abschnitte' : 'The sections',              \Atelier\Themes::REVEALS,         'revealLabel'],
            ];
            ?>
            <div class="mt-10 border-t border-sand-deep pt-8">
              <div class="<?= $label ?>"><?= $de ? 'Bewegung' : 'Movement' ?></div>
              <p class="mt-2 max-w-xl text-[0.8rem] leading-relaxed text-muted">
                <?= $de
                    ? 'Jedes Design bringt seine eigene Bewegung mit. Wer mag, stellt sie hier selbst zusammen – und sieht sie sich vorher an.'
                    : 'Every design comes with its own movement. Change any of it here if you like — and watch it first.' ?>
              </p>

              <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($axes as [$name, $title, $options, $fn]) : ?>
                  <div>
                    <label class="<?= $label ?>" for="<?= e($name) ?>"><?= e($title) ?></label>
                    <select id="<?= e($name) ?>" name="<?= e($name) ?>" class="<?= $field ?>" data-anim="<?= e($name) ?>">
                      <option value=""><?= $de ? '— wie im Design —' : '— as the design has it —' ?></option>
                      <?php foreach ($options as $key) : ?>
                        <option value="<?= e($key) ?>" <?= $old($name) === $key ? 'selected' : '' ?>>
                          <?= e(\Atelier\Themes::$fn($key, $tl)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endforeach; ?>
              </div>

              <a href="<?= e(I18n::path('/designs', $locale)) ?>" target="_blank" rel="noopener" data-anim-preview
                 class="mt-6 inline-flex items-center gap-2 border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.6"/>
                </svg>
                <?= $de ? 'So ansehen' : 'Watch it' ?>
              </a>
            </div>
          </div>
        </fieldset>

        <!-- 2 – Angaben -->
        <fieldset data-step="1" class="space-y-8">
          <div class="grid gap-7 sm:grid-cols-2">
            <div>
              <label class="<?= $label ?>" for="bride"><?= e(I18n::t('invite.bride')) ?> *</label>
              <input id="bride" name="bride" required value="<?= e($old('bride')) ?>" class="<?= $field ?>" data-preview-bride>
            </div>
            <div>
              <label class="<?= $label ?>" for="groom"><?= e(I18n::t('invite.groom')) ?> *</label>
              <input id="groom" name="groom" required value="<?= e($old('groom')) ?>" class="<?= $field ?>" data-preview-groom>
            </div>
          </div>

          <div>
            <label class="<?= $label ?>" for="message"><?= e(I18n::t('invite.message')) ?></label>
            <textarea id="message" name="message" rows="3" class="<?= $field ?> resize-none"><?= e($old('message', $de ? 'Wir möchten diesen besonderen Tag mit euch feiern.' : 'We would love to celebrate this special day with you.')) ?></textarea>
          </div>

          <div class="grid gap-7 sm:grid-cols-2">
            <div>
              <label class="<?= $label ?>" for="closing"><?= $de ? 'Grußformel' : 'Closing line' ?></label>
              <input id="closing" name="closing" value="<?= e($old('closing')) ?>" class="<?= $field ?>">
            </div>
            <div>
              <label class="<?= $label ?>" for="hashtag">Hashtag</label>
              <input id="hashtag" name="hashtag" value="<?= e($old('hashtag')) ?>" class="<?= $field ?>" placeholder="#ayse-mehmet2026">
            </div>
          </div>

          <?php
          /*
           * Steht hier und nicht am Ende: wer den Assistenten abbricht, hat die
           * Adresse dann trotzdem schon dagelassen. Nach dem Erstellen geht der
           * Verwaltungslink dorthin – ohne ihn ist die Einladung bezahlt und
           * die Gästeliste unerreichbar, sobald jemand das Fenster schliesst.
           */
          ?>
          <div class="mt-7 border-t border-sand-deep pt-7">
            <label class="<?= $label ?>" for="email"><?= $de ? 'Eure E-Mail-Adresse' : 'Your e-mail address' ?></label>
            <input id="email" type="email" name="email" value="<?= e($old('email')) ?>" class="<?= $field ?>"
                   placeholder="<?= $de ? 'name@beispiel.de' : 'name@example.com' ?>" autocomplete="email">
            <p class="mt-2 text-[0.75rem] leading-relaxed text-muted">
              <?= $de
                ? 'Dorthin schicken wir eure beiden Links: die Einladung und die Seite, auf der ihr Gäste eintragt und die Zusagen seht. Ohne sie sind beide weg, sobald ihr dieses Fenster schließt. Wir schreiben euch sonst nicht.'
                : 'That is where we send your two links: the invitation, and the page where you enter guests and see the replies. Without an address both are gone the moment you close this window. We will not write to you for anything else.' ?>
            </p>
          </div>
        </fieldset>

        <!-- 3 – Feier(n) -->
        <fieldset data-step="2" class="space-y-10">
          <?php for ($i = 0; $i < 2; $i++) : ?>
            <div class="<?= $i === 1 ? 'border-t border-sand-deep pt-8' : '' ?>" <?= $i === 1 ? 'data-second-event' : '' ?>>
              <div class="eyebrow"><?= $i === 0 ? ($de ? 'Feier' : 'Celebration') : ($de ? 'Zweite Feier' : 'Second celebration') ?></div>

              <div class="mt-5 grid gap-7 sm:grid-cols-2">
                <div>
                  <label class="<?= $label ?>"><?= $de ? 'Bezeichnung' : 'Name' ?></label>
                  <input name="event<?= $i ?>_name" value="<?= e($old("event{$i}_name", $i === 0 ? ($de ? 'Hochzeit' : 'Wedding') : '')) ?>" class="<?= $field ?>">
                </div>
                <div>
                  <label class="<?= $label ?>"><?= e(I18n::t('invite.date')) ?> <?= $i === 0 ? '*' : '' ?></label>
                  <input type="date" name="event<?= $i ?>_date" value="<?= e($old("event{$i}_date")) ?>" class="<?= $field ?>" <?= $i === 0 ? 'data-preview-date' : '' ?>>
                </div>
                <div>
                  <label class="<?= $label ?>"><?= e(I18n::t('invite.time')) ?></label>
                  <input type="time" name="event<?= $i ?>_time" value="<?= e($old("event{$i}_time", $i === 0 ? '16:00' : '')) ?>" class="<?= $field ?>" <?= $i === 0 ? 'data-preview-time' : '' ?>>
                </div>
                <div>
                  <label class="<?= $label ?>"><?= e(I18n::t('invite.venue')) ?></label>
                  <input name="event<?= $i ?>_venue" value="<?= e($old("event{$i}_venue")) ?>" class="<?= $field ?>">
                </div>
                <div class="sm:col-span-2">
                  <label class="<?= $label ?>"><?= e(I18n::t('invite.address')) ?></label>
                  <input name="event<?= $i ?>_address" value="<?= e($old("event{$i}_address")) ?>" class="<?= $field ?>" placeholder="Straße 1, 89331 Krumbach">
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </fieldset>

        <!-- 4 – Abschnitte -->
        <fieldset data-step="3" class="space-y-8">
          <div class="grid gap-4 sm:grid-cols-2">
            <?php foreach (Pricing::SECTION_KEYS as $key) : ?>
              <?php
              $price = Pricing::SECTIONS[$key];
              $checked = $values === [] ? !empty($sections[$key]) : isset($values['section_' . $key]);
              ?>
              <label class="flex cursor-pointer items-start justify-between gap-4 border border-sand-deep p-4">
                <span class="flex items-start gap-3">
                  <input type="checkbox" name="section_<?= e($key) ?>" <?= $checked ? 'checked' : '' ?>
                         class="mt-1 h-4 w-4 accent-[#B08D57]" data-section="<?= e($key) ?>" data-price="<?= $price['now'] ?>">
                  <span class="text-[0.92rem] text-ink"><?= e($sectionLabels[$key][$locale]) ?></span>
                </span>
                <span class="shrink-0 text-[0.72rem] uppercase tracking-[0.14em] <?= $price['now'] === 0 ? 'text-gold' : 'text-muted' ?>">
                  <?= $price['now'] === 0 ? ($de ? 'inklusive' : 'included') : '+' . $price['now'] . ' €' ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="space-y-7 border-t border-sand-deep pt-8">
            <?php
            /*
             * Programm und Menue: das Textfeld bleibt stehen und bleibt das
             * Feld, das abgeschickt wird – ohne Skript tut die Seite also
             * weiter, was sie immer tat. assets/invite.js legt darueber die
             * Zeilen mit dem Plus-Knopf und schreibt sie hier hinein. Niemand
             * muss dann wissen, dass ein senkrechter Strich Uhrzeit und
             * Programmpunkt trennt.
             */
            ?>
            <div data-needs="program">
              <label class="<?= $label ?>"><?= $de ? 'Programm' : 'Programme' ?></label>
              <div data-repeater="program"
                   data-columns="time,text"
                   data-placeholder-time="16:00"
                   data-placeholder-text="<?= e($de ? 'Empfang' : 'Reception') ?>"
                   data-add="<?= e($de ? '+ Programmpunkt' : '+ Programme item') ?>"
                   data-remove="<?= e($de ? 'Entfernen' : 'Remove') ?>">
                <textarea name="program" rows="4" class="<?= $field ?> resize-y" placeholder="16:00 | <?= $de ? 'Empfang' : 'Reception' ?>"><?= e($old('program')) ?></textarea>
              </div>
            </div>

            <div data-needs="menu">
              <label class="<?= $label ?>"><?= $de ? 'Menü' : 'Menü' ?></label>
              <div data-repeater="menu"
                   data-columns="text"
                   data-placeholder-text="<?= e($de ? 'Vorspeise' : 'Starter') ?>"
                   data-add="<?= e($de ? '+ Gang' : '+ Course') ?>"
                   data-remove="<?= e($de ? 'Entfernen' : 'Remove') ?>">
                <textarea name="menu" rows="4" class="<?= $field ?> resize-y"><?= e($old('menu')) ?></textarea>
              </div>
            </div>

            <div class="grid gap-7 sm:grid-cols-2" data-needs="family">
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Familie der Braut' : 'Family of the bride' ?></label>
                <input name="familyBride" value="<?= e($old('familyBride')) ?>" class="<?= $field ?>">
              </div>
              <div>
                <label class="<?= $label ?>"><?= $de ? 'Familie des Bräutigams' : 'Family of the groom' ?></label>
                <input name="familyGroom" value="<?= e($old('familyGroom')) ?>" class="<?= $field ?>">
              </div>
            </div>

            <div data-needs="music">
              <label class="<?= $label ?>"><?= $de ? 'Musik' : 'Music' ?></label>
              <input type="file" name="musicFile" accept="audio/mpeg,audio/mp4,audio/ogg,audio/wav,.mp3,.m4a,.ogg,.wav"
                     class="mt-2 block w-full text-[0.85rem] text-ink file:mr-4 file:border file:border-ink file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
              <p class="mt-2 text-[0.75rem] leading-relaxed text-muted">
                <?= $de
                  ? 'MP3, M4A, OGG oder WAV, bis 12 MB. Ein YouTube- oder Spotify-Link funktioniert hier nicht: die Karte müsste dafür beim Öffnen bei einem Fremden laden, und das tut sie bewusst nicht.'
                  : 'MP3, M4A, OGG or WAV, up to 12 MB. A YouTube or Spotify link does not work here: the card would have to load from a stranger when it opens, and it deliberately does not.' ?>
              </p>
            </div>

            <div data-needs="video">
              <label class="<?= $label ?>"><?= $de ? 'Video-Intro (YouTube, Vimeo oder MP4)' : 'Video intro (YouTube, Vimeo or MP4)' ?></label>
              <input name="videoUrl" value="<?= e($old('videoUrl')) ?>" class="<?= $field ?>" placeholder="https://">
            </div>
          </div>
        </fieldset>

        <!-- 5 – Fotos & Link -->
        <fieldset data-step="4" class="space-y-8">
          <div>
            <label class="<?= $label ?>"><?= $de ? 'Fotos (bis zu vier)' : 'Photos (up to four)' ?></label>
            <input type="file" name="photos[]" accept="image/*" multiple class="mt-2 w-full text-[0.85rem] text-muted">
            <p class="mt-2 text-[0.74rem] text-muted">
              <?= $de ? 'Werden beim Hochladen verkleinert. Hochkant wirkt auf der Karte am besten.' : 'Scaled down as they are uploaded. Portrait format works best on the card.' ?>
            </p>
          </div>

          <?php /* Optional: schon hier Namen eintragen. Wer es ueberspringt,
                    kann es nach dem Erstellen jederzeit nachholen. */ ?>
          <div>
            <label class="<?= $label ?>" for="guests">
              <?= $de ? 'Persönliche Einladungen (optional)' : 'Personal invitations (optional)' ?>
            </label>
            <textarea id="guests" name="guests" rows="4" class="<?= $field ?> resize-y"
                      placeholder="<?= $de
                        ? 'Familie Müller&#10;Anna &amp; Thomas&#10;Familie Yılmaz'
                        : 'Müller family&#10;Anna &amp; Thomas&#10;Yılmaz family' ?>"><?= e($old('guests')) ?></textarea>
            <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
              <?= $de
                ? 'Eine Zeile je Person oder Familie. Jeder bekommt dieselbe Karte mit seiner eigenen Anrede und einem eigenen Link. Lässt sich später ergänzen.'
                : 'One line per person or family. Everyone gets the same card with their own greeting and their own link. More can be added later.' ?>
            </p>
          </div>

          <div>
            <label class="<?= $label ?>" for="slug"><?= e(I18n::t('invite.slug')) ?></label>
            <div class="flex items-center gap-1 border-b border-sand-deep py-3 text-[0.9rem] text-muted">
              <span class="shrink-0">/<?= e($locale) ?>/einladung/</span>
              <input id="slug" name="slug" value="<?= e($old('slug')) ?>" class="w-full bg-transparent text-ink outline-none" data-slug placeholder="ayse-mehmet">
            </div>
            <p class="mt-2 text-[0.72rem] text-muted"><?= e(I18n::t('invite.slugHint')) ?></p>
          </div>

          <div>
            <label class="<?= $label ?>" for="coupon"><?= e(I18n::t('invite.coupon')) ?></label>
            <input id="coupon" name="coupon" value="<?= e($old('coupon')) ?>" class="<?= $field ?>" data-coupon autocomplete="off">
            <p class="mt-2 text-[0.72rem] text-muted" data-coupon-note
               data-hint="<?= e(I18n::t('invite.couponHint')) ?>"
               data-checking="<?= e(I18n::t('invite.couponChecking')) ?>"
               data-ok="<?= e(I18n::t('invite.couponOk')) ?>"
               data-bad="<?= e(I18n::t('invite.couponBad')) ?>"
               data-used="<?= e(I18n::t('invite.couponUsed')) ?>"
               data-expired="<?= e(I18n::t('invite.couponExpired')) ?>"><?= e(I18n::t('invite.couponHint')) ?></p>
          </div>

          <div class="border border-sand-deep p-5">
            <div class="text-[0.64rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invite.draftTitle')) ?></div>
            <p class="mt-2 text-[0.78rem] leading-relaxed text-muted"><?= e(I18n::t('invite.draftHint')) ?></p>
            <button type="submit" name="was" value="draft" formnovalidate
                    class="mt-4 border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= e(I18n::t('invite.draftSave')) ?>
            </button>
          </div>
        </fieldset>

        <!-- Navigation -->
        <div class="mt-12 flex items-center gap-4 border-t border-sand-deep pt-7">
          <button type="button" data-back class="px-2 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-muted transition-colors hover:text-ink">
            ← <?= e(I18n::t('invite.back')) ?>
          </button>
          <button type="button" data-next class="ml-auto bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= e(I18n::t('invite.next')) ?> →
          </button>
          <button type="submit" data-submit class="ml-auto bg-gold px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-white transition-opacity hover:opacity-90">
            <span data-price-label><?= e(Pricing::euro(Pricing::total($sections, false, false))) ?></span> · <?= e(I18n::t('invite.create')) ?>
          </button>
        </div>
      </div>

      <!-- ----------------------------- Vorschau ----------------------------- -->
      <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="text-[0.64rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invite.preview')) ?></div>

        <div class="mt-4 overflow-hidden rounded-[2.2rem] border-[9px] border-ink shadow-[0_30px_70px_-30px_rgba(20,17,15,.55)]">
          <div class="flex min-h-[420px] flex-col items-center px-6 py-10 text-center" data-preview-stage
               style="background: <?= e((string) $themes[0]['bg']) ?>">
            <div class="t-card w-full px-5 py-8" data-preview-card
                 style="background: <?= e((string) $themes[0]['paper']) ?>; color: <?= e((string) $themes[0]['fg']) ?>; border: 1px solid <?= e((string) $themes[0]['paperEdge']) ?>">
              <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full text-[0.6rem]" data-preview-seal
                   style="background: <?= e((string) $themes[0]['seal']) ?>; color: <?= e((string) $themes[0]['sealText']) ?>">✦</div>

              <div class="mt-5 text-[0.5rem] uppercase tracking-[0.32em]" data-preview-soft data-preview-occasion
                   style="color: <?= e((string) $themes[0]['soft']) ?>"><?= e(Invitations::occasionLine($old('eventType', 'wedding'), $locale)) ?></div>

              <?php /* Dieselbe Kalligrafie wie auf der fertigen Einladung. Die Farbe
                       setzt das Skript beim Themenwechsel (data-preview-accent). */ ?>
              <div class="mt-4 flex flex-col leading-none">
                <span class="t-script text-4xl" data-preview-bride-out data-preview-accent
                      style="color: <?= e((string) $themes[0]['accent']) ?>">Ayşe</span>
                <span class="font-display my-1 text-lg italic" data-preview-accent
                      style="color: <?= e((string) $themes[0]['accent']) ?>">&amp;</span>
                <span class="t-script text-4xl" data-preview-groom-out data-preview-accent
                      style="color: <?= e((string) $themes[0]['accent']) ?>">Mehmet</span>
              </div>

              <div class="mx-auto mt-4 h-px w-24" data-preview-line style="background: <?= e((string) $themes[0]['accent']) ?>"></div>

              <div class="mt-4 text-[0.62rem] uppercase tracking-[0.2em]" data-preview-date-out data-preview-soft
                   style="color: <?= e((string) $themes[0]['soft']) ?>">—</div>
            </div>
          </div>
        </div>

        <div class="mt-6 border border-sand-deep p-5">
          <div class="flex items-baseline justify-between">
            <span class="text-[0.64rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invite.price')) ?></span>
            <span class="font-display text-2xl font-light text-ink" data-price><?= e(Pricing::euro(Pricing::total($sections, false, false))) ?></span>
          </div>
          <p class="mt-3 text-[0.72rem] leading-relaxed text-muted"><?= e(I18n::t('invite.payNote')) ?></p>
        </div>
      </aside>
    </form>
  <?php endif; ?>
<?= Ui::sectionClose() ?>
