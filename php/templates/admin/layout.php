<?php
/**
 * Grundgerüst des Adminbereichs – bewusst ohne Kopfzeile der Website.
 *
 * Die Reiter stehen in einer Seitenleiste, nach Abschnitten gruppiert. Auf
 * schmalen Geräten wird daraus eine Auswahl, die zugeklappt bleibt: dort ist
 * der Platz für die Arbeit da, nicht für eine Liste mit sechzehn Einträgen.
 *
 * @var string $content
 * @var array<string,mixed> $meta
 * @var string $locale
 * @var bool $nav       Reiter anzeigen (bei der Anmeldung nicht)
 * @var string $current aktueller Reiter
 */

use function Atelier\e;
use Atelier\Admin;
use Atelier\I18n;

$de = $locale === 'de';
$nav = $nav ?? true;
$current = $current ?? '';
$sections = $nav ? Admin::sidebar($locale, $current) : [];
$currentLabel = $nav ? Admin::currentLabel($locale, $current) : '';

/** Ein Eintrag der Seitenleiste. */
$link = static function (array $tab): string {
    $classes = $tab['active']
        ? 'border-gold bg-sand/50 text-ink'
        : 'border-transparent text-muted hover:border-sand-deep hover:text-ink';

    return '<a href="' . e($tab['href']) . '" class="block border-l-2 py-[0.4rem] pl-3.5 pr-2 text-[0.82rem] leading-snug transition-colors '
        . $classes . '"' . ($tab['active'] ? ' aria-current="page"' : '') . '>' . e($tab['label']) . '</a>';
};

$overviewTab = static function () use ($locale, $current, $link): string {
    return $link([
        'href'   => I18n::path('/admin', $locale),
        'label'  => $locale === 'de' ? 'Übersicht' : 'Genel bakış',
        'active' => $current === '',
    ]);
};
?>
<!doctype html>
<html lang="<?= e(I18n::htmlLang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e((string) ($meta['title'] ?? 'Admin')) ?> | Atelier Lumière</title>
  <link rel="icon" href="/assets/icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/style.css?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/style.css')) ?>">
</head>
<body class="min-h-screen bg-cream antialiased"
      data-toast-ok="<?= $de ? 'Gespeichert.' : 'Kaydedildi.' ?>"
      data-toast-deleted="<?= $de ? 'Gelöscht.' : 'Silindi.' ?>">

  <?php /* ------------------------------- Kopf -------------------------------- */ ?>
  <header class="sticky top-0 z-30 border-b border-sand-deep bg-cream/95 backdrop-blur">
    <div class="mx-auto flex max-w-[92rem] flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
      <div class="flex items-baseline gap-4">
        <span class="font-display text-lg font-light text-ink">Atelier Lumière</span>
        <span class="hidden text-[0.62rem] uppercase tracking-[0.2em] text-muted sm:inline">
          <?= $de ? 'Verwaltung' : 'Yönetim' ?>
        </span>
      </div>

      <?php if ($nav) : ?>
        <div class="flex items-center gap-4">
          <?php
          /*
           * Die Sprache des Adminbereichs, nicht die der Website. Die Website
           * spricht Deutsch und Englisch, hier sitzt der Betrieb – und der
           * arbeitet lieber auf Türkisch. Der Umschalter bleibt auf demselben
           * Reiter stehen.
           */
          $hier = $current === '' ? '/admin' : '/admin' . $current;
          ?>
          <div class="flex items-center gap-1">
            <?php foreach (I18n::ADMIN_LOCALES as $l) : ?>
              <a href="<?= e(I18n::path($hier, $l)) ?>"
                 class="px-1.5 py-1 text-[0.64rem] uppercase tracking-[0.16em] transition-colors <?= $l === $locale ? 'text-gold' : 'text-muted hover:text-ink' ?>"><?= e(strtoupper($l)) ?></a>
            <?php endforeach; ?>
          </div>

          <a href="<?= e(I18n::path('', I18n::DEFAULT)) ?>" target="_blank" rel="noopener"
             class="text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold">
            <?= $de ? 'Zur Website' : 'Siteye dön' ?> ↗
          </a>
          <a href="<?= e(I18n::path('/admin/abmelden', $locale)) ?>"
             class="border border-ink px-4 py-2 text-[0.64rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= $de ? 'Abmelden' : 'Çıkış' ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <div class="mx-auto max-w-[92rem] px-5 sm:px-8">
    <?php if (!$nav) : ?>
      <div class="py-16"><?= $content ?></div>
    <?php else : ?>

      <?php /* ------------------------ Schmale Ansicht ------------------------- */ ?>
      <details class="group border-b border-sand-deep py-3 lg:hidden">
        <summary class="flex cursor-pointer items-center justify-between gap-4 py-1">
          <span>
            <span class="block text-[0.58rem] uppercase tracking-[0.2em] text-muted"><?= $de ? 'Bereich' : 'Bölüm' ?></span>
            <span class="font-display text-lg text-ink"><?= e($currentLabel) ?></span>
          </span>
          <span class="text-[0.66rem] uppercase tracking-[0.16em] text-gold group-open:hidden"><?= $de ? 'Wechseln' : 'Değiştir' ?></span>
          <span class="hidden text-[0.66rem] uppercase tracking-[0.16em] text-muted group-open:inline"><?= $de ? 'Schließen' : 'Kapat' ?></span>
        </summary>

        <?php /*
          Mehrspalten-Layout statt Grid: bei Grid werden Zeilen ausgeglichen,
          also blieb unter „Bereich" (ein Eintrag) eine grosse Luecke stehen,
          waehrend „Site" (dreizehn Eintraege) daneben durchlief. Mit CSS
          columns fliessen die Abschnitte natuerlich; `break-inside-avoid`
          haelt einen Abschnitt zusammen.
        */ ?>
        <nav class="mt-4 pb-3 sm:columns-2 sm:gap-x-8">
          <?php /* Overview + sık kullanılanlar önce */ ?>
          <div class="mb-6 break-inside-avoid">
            <div class="mb-2 text-[0.58rem] uppercase tracking-[0.2em] text-muted">
              <?= $de ? 'Häufig' : 'Sık kullanılan' ?>
            </div>
            <?= $overviewTab() ?>
            <?php foreach ($sections['pinned'] as $tab) : ?>
              <?= $link($tab) ?>
            <?php endforeach; ?>
          </div>

          <?php foreach ($sections['more'] as $section) : ?>
            <div class="mb-6 break-inside-avoid">
              <?php if ($section['label'] !== '') : ?>
                <div class="mb-2 text-[0.58rem] uppercase tracking-[0.2em] text-muted"><?= e($section['label']) ?></div>
              <?php endif; ?>
              <?php foreach ($section['tabs'] as $tab) : ?>
                <?= $link($tab) ?>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </nav>
      </details>

      <div class="lg:grid lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-12">

        <?php /* ------------------------ Seitenleiste ------------------------- */ ?>
        <?php /* Rollbalken nur, wenn das Fenster wirklich zu niedrig ist. */ ?>
        <nav class="hidden lg:sticky lg:top-[3.9rem] lg:block lg:max-h-[calc(100vh-5rem)] lg:overflow-y-auto lg:py-7">
          <?php /* Overview (sabit üst) */ ?>
          <div class="mb-4">
            <?= $overviewTab() ?>
          </div>

          <?php /* Sık kullanılanlar */ ?>
          <div class="mb-5">
            <div class="mb-1.5 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted">
              <?= $de ? 'Häufig' : 'Sık kullanılan' ?>
            </div>
            <?php foreach ($sections['pinned'] as $tab) : ?>
              <?= $link($tab) ?>
            <?php endforeach; ?>
          </div>

          <?php /* Daha fazla — açılır, localStorage'da durum kalır */ ?>
          <details id="admin-more" class="group">
            <summary class="mb-3 flex cursor-pointer items-center gap-2 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted hover:text-ink">
              <span><?= $de ? 'Mehr' : 'Daha fazla' ?></span>
              <span class="text-gold transition-transform group-open:rotate-90">›</span>
            </summary>
            <?php foreach ($sections['more'] as $section) : ?>
              <div class="mb-5">
                <?php if ($section['label'] !== '') : ?>
                  <div class="mb-1.5 pl-3.5 text-[0.58rem] uppercase tracking-[0.2em] text-muted"><?= e($section['label']) ?></div>
                <?php endif; ?>
                <?php foreach ($section['tabs'] as $tab) : ?>
                  <?= $link($tab) ?>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </details>
        </nav>

        <?php /* --------------------------- Inhalt ---------------------------- */ ?>
        <main class="min-w-0 py-8 lg:py-10">
          <?php $warning = Admin::passwordWarning($locale); ?>
          <?php if ($warning !== '') : ?>
            <div class="mb-8 border-l-2 border-red-700 bg-red-50 px-5 py-3 text-[0.86rem] leading-relaxed text-red-800">
              <?= e($warning) ?>
            </div>
          <?php endif; ?>

          <?= $content ?>
        </main>
      </div>
    <?php endif; ?>
  </div>

  <div id="toast-host" aria-live="polite" class="pointer-events-none fixed bottom-6 right-6 z-50 flex flex-col gap-2"></div>

  <script src="/assets/admin.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/admin.js')) ?>" defer></script>
  <script src="/assets/upload.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/upload.js')) ?>" defer></script>
</body>
</html>
