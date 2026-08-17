<?php
/**
 * Die Gästeliste des Paares.
 *
 * Kein Adminbereich: Hier sitzt jemand, der einmal im Leben eine Einladung
 * verschickt. Deshalb steht oben, was zu tun ist, und darunter genau drei
 * Dinge – Namen eintragen, Links kopieren, Vorschaubild tauschen.
 *
 * @var string $locale
 * @var array<string,mixed> $invitation
 * @var list<array<string,mixed>> $guests
 * @var list<array<string,mixed>> $rsvps   Zusagen und Absagen der Gäste
 * @var string $link       öffentlicher Link ohne Anrede
 * @var string $manageUrl  dieser Link hier
 * @var string $preview    Vorschaubild für WhatsApp
 * @var string $key
 * @var string $stand      Rückmeldung der letzten Aktion
 * @var string $csrf
 */

use function Atelier\e;
use Atelier\Dates;

$de = $locale === 'de';
$names = trim((string) ($invitation['bride'] ?? '') . ' & ' . (string) ($invitation['groom'] ?? ''), ' &');
$events = (array) ($invitation['events'] ?? []);
$date = (string) (((array) ($events[0] ?? []))['date'] ?? '');
$field = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.95rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';

// Rückmeldung der letzten Aktion in einen Satz übersetzen
$message = '';
if (str_starts_with($stand, 'plus')) {
    $count = (int) substr($stand, 4);
    $message = $de
        ? $count . ($count === 1 ? ' Name hinzugefügt.' : ' Namen hinzugefügt.')
        : $count . ($count === 1 ? ' name added.' : ' names added.');
} elseif ($stand === 'doppelt') {
    $message = $de ? 'Diese Namen standen schon auf der Liste.' : 'Those names were already on the list.';
} elseif ($stand === 'leer') {
    $message = $de ? 'Da war nichts zum Eintragen.' : 'There was nothing to add.';
} elseif ($stand === 'geloescht') {
    $message = $de ? 'Der Name ist entfernt. Sein Link führt jetzt auf die Einladung ohne Anrede.' : 'The name is gone. Its link now leads to the invitation without a greeting.';
} elseif ($stand === 'vorschau') {
    $message = $de ? 'Das Vorschaubild ist gespeichert.' : 'The preview picture is saved.';
}

/**
 * WhatsApp-Link mit fertigem Text – ein Antippen, und die Nachricht steht.
 *
 * Die Anrede kommt aus Guests::salutation(), damit in der Nachricht dasselbe
 * steht wie oben auf der Karte – und nicht „Liebe yilmaz“.
 *
 * @param array<string,mixed> $guest
 */
$whatsapp = static function (string $url, array $guest = []) use ($de, $names, $date, $locale): string {
    $who = $guest !== [] ? \Atelier\Guests::salutation($guest, $locale) : '';
    $greeting = $who !== '' ? $who . ",\n\n" : '';
    $text = $greeting
        . ($de
            ? 'wir laden euch herzlich zu unserer Hochzeit ein.'
            : 'we would love to invite you to our wedding.')
        . ($date !== '' ? "\n" . Dates::long($date) : '')
        . "\n\n" . $url . "\n\n" . $names;

    return 'https://wa.me/?text=' . rawurlencode($text);
};
?>
<div class="mx-auto max-w-3xl px-5 py-14 sm:py-20">

  <div class="eyebrow"><?= $de ? 'Eure Einladung' : 'Your invitation' ?></div>
  <h1 class="font-display mt-2 text-3xl font-light text-ink sm:text-4xl"><?= e($names) ?></h1>
  <p class="mt-4 max-w-xl text-sm leading-relaxed text-muted">
    <?= $de
      ? 'Hier tragt ihr ein, wen ihr persönlich ansprechen wollt. Die Einladung bleibt dieselbe – nur die Anrede oben auf der Karte ändert sich, und jeder bekommt seinen eigenen Link.'
      : 'Here you enter who you want to address personally. The invitation stays the same – only the greeting at the top of the card changes, and everyone gets their own link.' ?>
  </p>

  <?php if ($message !== '') : ?>
    <p class="mt-6 border border-gold/50 bg-sand/40 px-5 py-3 text-[0.9rem] text-ink"><?= e($message) ?></p>
  <?php endif; ?>

  <?php /* --------------------------- Rückmeldungen --------------------------- */ ?>
  <?php
  /*
   * Antworten sind nach Datum absteigend sortiert. Wer zweimal antwortet, weil
   * sich etwas geaendert hat, soll nicht zweimal in der Liste stehen – die
   * erste gefundene Antwort je Gast ist die neueste und gilt.
   */
  $answers = [];
  $answered = [];
  foreach ($rsvps as $rsvp) {
      $token = (string) ($rsvp['guest'] ?? '');
      if ($token !== '') {
          if (isset($answered[$token])) {
              continue;
          }
          $answered[$token] = true;
      }
      $answers[] = $rsvp;
  }

  $yes = array_values(array_filter($answers, static fn (array $r): bool => !empty($r['coming'])));
  $no = array_values(array_filter($answers, static fn (array $r): bool => empty($r['coming'])));
  // Nicht die Zahl der Antworten, sondern die der Personen: eine Zusage kann
  // fuer eine ganze Familie gelten, und danach wird der Saal bestuhlt.
  $heads = array_sum(array_map(static fn (array $r): int => max(1, (int) ($r['count'] ?? 1)), $yes));

  // Eingetragen, aber noch keine Antwort. Das ist die Liste, an der man
  // tatsaechlich arbeitet – die anderen sind erledigt.
  $pending = array_values(array_filter(
      $guests,
      static fn (array $g): bool => !isset($answered[(string) ($g['token'] ?? '')])
  ));
  ?>
  <section class="mt-12 border border-sand-deep p-6">
    <h2 class="font-display text-lg text-ink"><?= $de ? 'Wer kommt' : 'Who is coming' ?></h2>

    <?php if ($rsvps === []) : ?>
      <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
        <?= $de
          ? 'Noch keine Rückmeldung. Sobald jemand auf der Einladung antwortet, steht es hier – die Seite zeigt immer den aktuellen Stand.'
          : 'No replies yet. As soon as somebody answers on the invitation it appears here – the page always shows the current state.' ?>
      </p>
    <?php else : ?>
      <div class="mt-5 flex flex-wrap gap-8">
        <div>
          <div class="font-display text-3xl font-light text-ink"><?= (int) $heads ?></div>
          <div class="mt-1 text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Personen' : 'People' ?></div>
        </div>
        <div>
          <div class="font-display text-3xl font-light text-ink"><?= count($yes) ?></div>
          <div class="mt-1 text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Zusagen' : 'Coming' ?></div>
        </div>
        <div>
          <div class="font-display text-3xl font-light text-muted"><?= count($no) ?></div>
          <div class="mt-1 text-[0.6rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Absagen' : 'Not coming' ?></div>
        </div>
      </div>

      <ul class="mt-7 divide-y divide-sand-deep/60">
        <?php foreach ($answers as $rsvp) : ?>
          <?php $coming = !empty($rsvp['coming']); ?>
          <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1 py-3">
            <span class="text-[0.7rem] <?= $coming ? 'text-gold' : 'text-muted' ?>"><?= $coming ? '●' : '○' ?></span>
            <span class="text-[0.95rem] text-ink"><?= e((string) ($rsvp['name'] ?? '')) ?></span>

            <?php if ($coming && (int) ($rsvp['count'] ?? 1) > 1) : ?>
              <span class="text-[0.78rem] text-muted">· <?= (int) $rsvp['count'] ?> <?= $de ? 'Personen' : 'people' ?></span>
            <?php endif; ?>

            <span class="ml-auto text-[0.7rem] text-muted"><?= e(Dates::short((string) ($rsvp['at'] ?? ''))) ?></span>

            <?php if ((string) ($rsvp['note'] ?? '') !== '') : ?>
              <p class="w-full pl-6 text-[0.82rem] leading-relaxed text-muted">
                &bdquo;<?= e((string) $rsvp['note']) ?>&ldquo;
              </p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($pending !== []) : ?>
      <div class="mt-9 border-t border-sand-deep/60 pt-7">
        <h3 class="text-[0.66rem] uppercase tracking-[0.18em] text-muted">
          <?= $de ? 'Warten noch auf Antwort' : 'Still waiting for a reply' ?>
          <span class="text-gold">(<?= count($pending) ?>)</span>
        </h3>
        <p class="mt-2 text-[0.8rem] leading-relaxed text-muted">
          <?= $de
            ? 'Diese Namen stehen auf eurer Liste, haben aber über ihren eigenen Link noch nicht geantwortet.'
            : 'These names are on your list but have not yet answered through their own link.' ?>
        </p>

        <ul class="mt-5 space-y-2.5">
          <?php foreach ($pending as $guest) : ?>
            <li class="flex flex-wrap items-center gap-3">
              <span class="text-[0.95rem] text-ink"><?= e((string) ($guest['name'] ?? '')) ?></span>
              <a href="<?= e($whatsapp((string) ($guest["url"] ?? ""), $guest)) ?>"
                 target="_blank" rel="noopener"
                 class="ml-auto whitespace-nowrap border border-gold px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-gold transition-colors hover:bg-gold hover:text-white">
                <?= $de ? 'Erinnern' : 'Remind' ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </section>

  <?php /* ------------------------- Allgemeiner Link ------------------------- */ ?>
  <section class="mt-12 border border-sand-deep p-6">
    <h2 class="font-display text-lg text-ink"><?= $de ? 'Der allgemeine Link' : 'The general link' ?></h2>
    <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
      <?= $de
        ? 'Ohne Anrede – für eine Gruppe oder wenn es schnell gehen muss.'
        : 'Without a greeting – for a group, or when it has to be quick.' ?>
    </p>
    <div class="mt-4 flex flex-wrap items-center gap-3">
      <code class="min-w-0 flex-1 break-all border border-sand-deep bg-cream px-4 py-3 text-[0.8rem] text-ink"><?= e($link) ?></code>
      <button type="button" data-copy="<?= e($link) ?>"
              class="border border-ink px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= $de ? 'Kopieren' : 'Copy' ?>
      </button>
      <a href="<?= e($whatsapp($link)) ?>" target="_blank" rel="noopener"
         class="border border-gold px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-gold transition-colors hover:bg-gold hover:text-white">
        WhatsApp
      </a>
    </div>
  </section>

  <?php /* --------------------------- Namen eintragen ------------------------ */ ?>
  <section class="mt-8 border border-sand-deep p-6">
    <h2 class="font-display text-lg text-ink"><?= $de ? 'Namen eintragen' : 'Enter names' ?></h2>
    <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
      <?= $de
        ? 'Eine Zeile je Person oder Familie. Ihr könnt eine Liste auch einfach hineinkopieren – aus einer Tabelle, aus WhatsApp, wie sie gerade da ist.'
        : 'One line per person or family. You can also simply paste a list in – from a spreadsheet, from WhatsApp, however it happens to be.' ?>
    </p>

    <form method="post" enctype="multipart/form-data" class="mt-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="schluessel" value="<?= e($key) ?>">
      <input type="hidden" name="was" value="namen">

      <?php
      /*
       * Die Anrede steht oben auf der Karte, und „Liebe yilmaz“ hat dort schon
       * einmal gestanden. Eine Familie wird anders angesprochen als eine
       * Person, und im Deutschen ein Mann anders als eine Frau – das kann nur
       * sagen, wer die Gäste kennt. Es gilt für alle Namen dieses Durchgangs;
       * für gemischte Listen trägt man sie in zwei Durchgängen ein.
       */
      $kinds = [
          'family' => [$de ? 'Familie' : 'Family',    $de ? 'Liebe Familie Yılmaz' : 'Dear Yılmaz family'],
          'male'   => [$de ? 'Herr' : 'Mr',           $de ? 'Lieber Yusuf' : 'Dear Yusuf'],
          'female' => [$de ? 'Frau' : 'Ms',           $de ? 'Liebe Ayşe' : 'Dear Ayşe'],
      ];
      ?>
      <div class="mb-6">
        <span class="<?= $label ?>"><?= $de ? 'Anrede für diese Namen' : 'Greeting for these names' ?></span>
        <div class="mt-3 grid gap-2 sm:grid-cols-3">
          <?php foreach ($kinds as $value => [$title, $example]) : ?>
            <label class="cursor-pointer border border-sand-deep px-4 py-3 transition-colors hover:border-muted has-[:checked]:border-gold">
              <input type="radio" name="art" value="<?= e($value) ?>" class="sr-only" <?= $value === 'family' ? 'checked' : '' ?>>
              <span class="block text-[0.85rem] text-ink"><?= e($title) ?></span>
              <span class="mt-1 block text-[0.72rem] italic text-muted"><?= e($example) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <label class="<?= $label ?>" for="namen"><?= $de ? 'Namen' : 'Names' ?></label>
      <textarea id="namen" name="namen" rows="7" class="<?= $field ?> resize-y"
                placeholder="<?= $de
                  ? 'Familie Müller&#10;Familie Oxford&#10;Anna &amp; Thomas&#10;Familie Yılmaz'
                  : 'Müller family&#10;Oxford family&#10;Anna &amp; Thomas&#10;Yılmaz family' ?>"></textarea>

      <div class="mt-6">
        <label class="<?= $label ?>" for="liste"><?= $de ? 'Oder eine Datei (.txt oder .csv)' : 'Or a file (.txt or .csv)' ?></label>
        <input id="liste" type="file" name="liste" accept=".txt,.csv,text/plain,text/csv"
               class="mt-2 w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
        <p class="mt-2 text-[0.72rem] leading-relaxed text-muted">
          <?= $de
            ? 'Aus Excel: „Speichern unter“ → CSV. Die erste Spalte wird genommen.'
            : 'From Excel: &bdquo;Save as&ldquo; → CSV. The first column is taken.' ?>
        </p>
      </div>

      <button class="mt-7 bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Einladungen erstellen' : 'Create invitations' ?>
      </button>

      <?php /* Hier stehen Namen von Leuten, die diese Seite nie gesehen haben. */ ?>
      <p class="mt-4 text-[0.75rem] leading-relaxed text-muted">
        <?= e(\Atelier\I18n::t('invite.guestsDataNote')) ?>
      </p>
    </form>
  </section>

  <?php /* ----------------------------- Die Liste ---------------------------- */ ?>
  <section class="mt-8">
    <h2 class="font-display text-lg text-ink">
      <?= $de ? 'Persönliche Einladungen' : 'Personal invitations' ?>
      <span class="ml-2 text-[0.8rem] text-muted">(<?= count($guests) ?>)</span>
    </h2>

    <?php if ($guests === []) : ?>
      <p class="mt-4 border border-sand-deep p-5 text-sm text-muted">
        <?= $de
          ? 'Noch keine Namen. Sobald ihr oben welche eintragt, stehen hier die Links.'
          : 'No names yet. The moment you enter some above, the links appear here.' ?>
      </p>
    <?php else : ?>
      <div class="mt-4 space-y-3">
        <?php foreach ($guests as $guest) : ?>
          <div class="border border-sand-deep p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
              <span class="font-display text-lg text-ink"><?= e((string) $guest['name']) ?></span>
              <form method="post" class="shrink-0">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="schluessel" value="<?= e($key) ?>">
                <input type="hidden" name="was" value="loeschen">
                <input type="hidden" name="token" value="<?= e((string) $guest['token']) ?>">
                <button data-confirm="<?= $de ? 'Diesen Namen entfernen?' : 'Remove this name?' ?>"
                        class="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                  <?= $de ? 'Entfernen' : 'Remove' ?>
                </button>
              </form>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3">
              <code class="min-w-0 flex-1 break-all border border-sand-deep bg-cream px-4 py-2.5 text-[0.78rem] text-ink"><?= e((string) $guest['url']) ?></code>
              <button type="button" data-copy="<?= e((string) $guest['url']) ?>"
                      class="border border-ink px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream">
                <?= $de ? 'Kopieren' : 'Copy' ?>
              </button>
              <a href="<?= e($whatsapp((string) $guest["url"], $guest)) ?>" target="_blank" rel="noopener"
                 class="border border-gold px-4 py-2.5 text-[0.64rem] uppercase tracking-[0.16em] text-gold transition-colors hover:bg-gold hover:text-white">
                WhatsApp
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php /* ---------------------------- Vorschaubild -------------------------- */ ?>
  <section class="mt-12 border border-sand-deep p-6">
    <h2 class="font-display text-lg text-ink"><?= $de ? 'Das Bild in WhatsApp' : 'The picture in WhatsApp' ?></h2>
    <p class="mt-2 text-[0.82rem] leading-relaxed text-muted">
      <?= $de
        ? 'So sieht die Vorschau aus, wenn ihr den Link verschickt. Wir nehmen dafür euer erstes Foto. Wenn ihr lieber ein eigenes Bild möchtet, ladet es hier hoch – quer, etwa 1200 × 630 Punkte.'
        : 'This is what the preview looks like when you send the link. We use your first photo for it. If you would rather have a picture of your own, upload it here – landscape, about 1200 × 630 pixels.' ?>
    </p>

    <div class="mt-5 max-w-md border border-sand-deep bg-cream">
      <?php if ($preview !== '') : ?>
        <img src="<?= e($preview) ?>" alt="" class="block w-full">
      <?php endif; ?>
      <div class="border-t border-sand-deep px-4 py-3">
        <div class="text-[0.86rem] font-medium text-ink"><?= e($names) ?> – <?= e(\Atelier\Invitations::kindLabel((string) ($invitation['eventType'] ?? ''), $locale)) ?></div>
        <?php if ($date !== '') : ?>
          <div class="mt-0.5 text-[0.78rem] text-muted"><?= e(Dates::long($date, $locale)) ?></div>
        <?php endif; ?>
        <div class="mt-1 text-[0.7rem] uppercase tracking-[0.14em] text-muted"><?= e(parse_url($link, PHP_URL_HOST) ?: '') ?></div>
      </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="mt-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="schluessel" value="<?= e($key) ?>">
      <input type="hidden" name="was" value="vorschau">

      <input type="file" name="bild" accept="image/*"
             class="w-full text-[0.8rem] text-muted file:mr-4 file:border file:border-sand-deep file:bg-transparent file:px-4 file:py-2 file:text-[0.66rem] file:uppercase file:tracking-[0.16em] file:text-ink">
      <div class="mt-5 flex flex-wrap items-center gap-4">
        <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
          <?= $de ? 'Bild speichern' : 'Save picture' ?>
        </button>
        <?php if ((string) ($invitation['ogImage'] ?? '') !== '') : ?>
          <button name="entfernen" value="1"
                  class="text-[0.66rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-gold hover:underline">
            <?= $de ? 'Wieder unser Foto nehmen' : 'Use our photo again' ?>
          </button>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <?php /* -------------------------- Diesen Link merken ---------------------- */ ?>
  <section class="mt-12 border-t border-sand-deep pt-8">
    <h2 class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Diese Seite wiederfinden' : 'Finding this page again' ?></h2>
    <p class="mt-3 max-w-xl text-[0.82rem] leading-relaxed text-muted">
      <?= $de
        ? 'Speichert euch diesen Link – nur damit kommt ihr wieder hierher. Gebt ihn nicht mit den Einladungen weiter.'
        : 'Save this link – it is the only way back here. Do not pass it on with the invitations.' ?>
    </p>
    <div class="mt-4 flex flex-wrap items-center gap-3">
      <code class="min-w-0 flex-1 break-all border border-sand-deep bg-cream px-4 py-3 text-[0.76rem] text-muted"><?= e($manageUrl) ?></code>
      <button type="button" data-copy="<?= e($manageUrl) ?>"
              class="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
        <?= $de ? 'Kopieren' : 'Copy' ?>
      </button>
    </div>
  </section>
</div>
