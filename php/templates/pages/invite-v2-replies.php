<?php
/**
 * Was die Gaeste geantwortet haben.
 *
 * Nur lesen. Loeschen, Aendern und Ausleiten sind Phase D - das hier ist eine
 * Liste, kein Panel. Wer sie oeffnet, hat einmal im Leben eine Einladung
 * verschickt und will eine einzige Sache wissen: wer kommt.
 *
 * Der haeufigste Zustand am ersten Tag ist der leere: die Liste muss ohne
 * eine einzige Antwort etwas Vernuenftiges zeigen, sonst waere das die Seite,
 * die genau dann bricht, wenn sie zum ersten Mal geoeffnet wird.
 *
 * @var string $locale
 * @var string $namen
 * @var list<array<string,mixed>> $antworten
 * @var int $kommen
 */

use function Atelier\e;
use Atelier\Dates;
use Atelier\I18n;
?>
<?php /*
   pt-32 wie im Schaufenster: der Kopf ist fixiert und rund 94 px hoch, diese
   Seite hat keinen Hero. Mit py-20 (80 px) lag die Ueberschrift unter dem Kopf.
*/ ?>
<section class="mx-auto max-w-3xl px-6 pb-24 pt-32">
  <h1 class="font-display text-3xl text-ink"><?= e(I18n::t('invitation2.repliesTitle')) ?></h1>
  <?php if ($namen !== '') : ?>
    <p class="mt-2 text-[0.95rem] text-muted"><?= e($namen) ?></p>
  <?php endif; ?>
  <p class="mt-4 max-w-xl text-sm text-muted"><?= e(I18n::t('invitation2.repliesLead')) ?></p>

  <?php /*
     Zwei Zahlen und nicht eine: "wie viele haben geantwortet" und "wie viele
     kommen" sind verschiedene Fragen. Eine Absage zaehlt als Antwort und
     nicht als Gast, und eine Zusage bringt mehrere Personen mit - eine
     einzige Zahl muesste sich fuer eine der beiden Fragen entscheiden.
  */ ?>
  <div class="mt-8 flex gap-12 border-y border-sand-deep py-5">
    <div>
      <div class="text-2xl text-ink"><?= (int) $kommen ?></div>
      <div class="text-[0.62rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invitation2.repliesGuests')) ?></div>
    </div>
    <div>
      <div class="text-2xl text-ink"><?= count($antworten) ?></div>
      <div class="text-[0.62rem] uppercase tracking-[0.2em] text-muted"><?= e(I18n::t('invitation2.repliesTotal')) ?></div>
    </div>
  </div>

  <?php if ($antworten === []) : ?>
    <p class="mt-8 text-sm text-muted"><?= e(I18n::t('invitation2.repliesEmpty')) ?></p>
  <?php else : ?>
    <ul class="mt-8 divide-y divide-sand-deep">
      <?php foreach ($antworten as $antwort) : ?>
        <?php $kommt = !empty($antwort['coming']); ?>
        <li class="py-4">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-[0.95rem] text-ink"><?= e((string) ($antwort['name'] ?? '')) ?></span>
            <span class="text-[0.66rem] uppercase tracking-[0.18em] <?= $kommt ? 'text-gold' : 'text-muted' ?>">
              <?= e(I18n::t($kommt ? 'invitation2.repliesYes' : 'invitation2.repliesNo')) ?>
            </span>
            <?php if ($kommt) : ?>
              <span class="text-[0.8rem] text-muted">
                <?= max(1, (int) ($antwort['count'] ?? 1)) ?> <?= e(I18n::t('invitation2.repliesCount')) ?>
              </span>
            <?php endif; ?>
          </div>
          <?php $notiz = trim((string) ($antwort['note'] ?? '')); ?>
          <?php if ($notiz !== '') : ?>
            <p class="mt-1 text-[0.9rem] text-ink"><?= e($notiz) ?></p>
          <?php endif; ?>
          <?php /*
             Das Datum steht an jeder Zeile, weil eine zweite Antwort die
             erste ersetzt: ohne es koennte das Paar nicht sehen, dass jemand
             seine Meinung geaendert hat. Nur der Tag - eine Uhrzeit auf die
             Minute waere eine Genauigkeit, die niemand braucht.
          */ ?>
          <?php $wann = (string) ($antwort['at'] ?? ''); ?>
          <?php if ($wann !== '') : ?>
            <p class="mt-1 text-[0.66rem] text-muted">
              <?= e(I18n::t('invitation2.repliesUpdated')) ?>: <?= e(Dates::short(substr($wann, 0, 10))) ?>
            </p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
