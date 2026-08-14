<?php
/**
 * Systemcheck vor dem Livegang.
 *
 * @var string $locale
 * @var list<array{key:string,state:string,title:string,detail:string,hint:string}> $checks
 * @var array{ok:int,warn:int,fail:int} $tally
 */

use function Atelier\e;
use Atelier\Preflight;

$de = $locale === 'de';

$look = [
    Preflight::OK   => ['border-gold/50', 'text-gold', '✓'],
    Preflight::WARN => ['border-sand-deep', 'text-muted', '!'],
    Preflight::FAIL => ['border-red-700/50', 'text-red-700', '×'],
];
?>
<div class="space-y-8">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Vor dem Livegang' : 'Yayına almadan önce' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
        ? 'Auf dem eigenen Rechner ist alles grün – die Fragen stellen sich erst auf dem Webspace. Diese Seite prüft dort, was wirklich zählt. Rot muss weg, Gelb sollte man gelesen haben.'
        : 'Kendi bilgisayarında her şey yeşildir – asıl sorular sunucuda çıkar. Bu sayfa orada gerçekten önemli olanı kontrol eder. Kırmızı kalmamalı, sarıyı okumuş olmak gerekir.' ?>
    </p>
  </div>

  <div class="flex flex-wrap gap-6 border-y border-sand-deep py-4 text-[0.72rem] uppercase tracking-[0.16em]">
    <span class="text-gold"><?= $tally['ok'] ?> <?= $de ? 'in Ordnung' : 'uygun' ?></span>
    <span class="text-muted"><?= $tally['warn'] ?> <?= $de ? 'Hinweis' : 'uyarı' ?></span>
    <span class="<?= $tally['fail'] > 0 ? 'text-red-700' : 'text-muted' ?>">
      <?= $tally['fail'] ?> <?= $de ? 'offen' : 'açık' ?>
    </span>
  </div>

  <div class="space-y-px bg-sand-deep">
    <?php foreach ($checks as $check) : ?>
      <?php [$border, $colour, $sign] = $look[$check['state']]; ?>
      <div class="border-l-2 <?= $border ?> bg-cream p-5">
        <div class="flex flex-wrap items-baseline gap-3">
          <span class="<?= $colour ?> text-[0.9rem]"><?= $sign ?></span>
          <span class="font-display text-lg text-ink"><?= e($check['title']) ?></span>
          <span class="text-[0.82rem] text-muted"><?= e($check['detail']) ?></span>
        </div>
        <?php if ($check['hint'] !== '') : ?>
          <p class="mt-2 max-w-3xl text-[0.8rem] leading-relaxed text-muted"><?= e($check['hint']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="border border-sand-deep p-6">
    <h3 class="font-display text-lg text-ink"><?= $de ? 'Danach von Hand prüfen' : 'Sonrasında elle kontrol' ?></h3>
    <p class="mt-2 text-[0.8rem] leading-relaxed text-muted">
      <?= $de
        ? 'Das hier kann kein Programm für euch feststellen – es braucht einen Menschen, der klickt:'
        : 'Bunları program sizin için anlayamaz – tıklayan bir insan gerekiyor:' ?>
    </p>
    <ul class="mt-4 space-y-2 text-[0.85rem] leading-relaxed text-ink">
      <?php foreach ([
        ['de' => 'Kontaktformular abschicken – kommt die E-Mail an? Auch im Spam nachsehen.', 'tr' => 'İletişim formunu gönderin – e-posta geliyor mu? Spam’e de bakın.'],
        ['de' => 'Eine Galerie öffnen und ein Bild hochladen.', 'tr' => 'Bir galeri açıp fotoğraf yükleyin.'],
        ['de' => 'Eine Einladung erstellen und den Link in WhatsApp einfügen – erscheint die Vorschaukarte?', 'tr' => 'Bir davetiye oluşturup bağlantıyı WhatsApp’a yapıştırın – önizleme kartı çıkıyor mu?'],
        ['de' => 'PayPal mit einem echten Kleinbetrag testen, bevor der erste Kunde zahlt.', 'tr' => 'İlk müşteri ödemeden önce PayPal’ı gerçek küçük bir tutarla deneyin.'],
        ['de' => 'Beide Sprachen durchklicken, auch auf dem Handy.', 'tr' => 'İki dili de telefonda dahil tıklayarak gezin.'],
        ['de' => 'Cookie-Hinweis: ablehnen, neu laden – bleibt er weg?', 'tr' => 'Çerez uyarısı: reddedin, sayfayı yenileyin – geri gelmiyor mu?'],
        ['de' => 'Eine erfundene Adresse aufrufen – kommt die eigene 404-Seite?', 'tr' => 'Var olmayan bir adres açın – kendi 404 sayfanız mı geliyor?'],
      ] as $item) : ?>
        <li class="flex gap-3">
          <span class="text-muted">·</span>
          <span><?= e($item[$locale] ?? $item['de']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
