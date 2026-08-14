<?php
/**
 * Grundgerüst des Adminbereichs – bewusst ohne Kopfzeile der Website.
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
<body class="min-h-screen bg-cream antialiased">
  <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-sand-deep pb-5">
      <div>
        <div class="eyebrow">Atelier Lumière</div>
        <h1 class="font-display mt-1 text-2xl font-light text-ink"><?= $de ? 'Verwaltung' : 'Yönetim' ?></h1>
      </div>

      <?php if ($nav) : ?>
        <div class="flex items-center gap-4">
          <a href="<?= e(I18n::path('', $locale)) ?>" class="text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold">
            <?= $de ? 'Zur Website' : 'Siteye dön' ?> ↗
          </a>
          <a href="<?= e(I18n::path('/admin/abmelden', $locale)) ?>"
             class="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
            <?= $de ? 'Abmelden' : 'Çıkış' ?>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($nav) : ?>
      <nav class="mt-6 flex flex-wrap gap-1 border-b border-sand-deep">
        <?php foreach (Admin::TABS as $tab) : ?>
          <?php $active = $current === $tab['href']; ?>
          <a href="<?= e(I18n::path('/admin' . $tab['href'], $locale)) ?>"
             class="border-b-2 px-4 py-3 text-[0.72rem] uppercase tracking-[0.16em] transition-colors <?= $active ? 'border-gold text-ink' : 'border-transparent text-muted hover:border-gold hover:text-ink' ?>">
            <?= e($de ? $tab['de'] : $tab['tr']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if (isset($_GET['gespeichert'])) : ?>
      <div class="mt-6 border border-gold/50 bg-sand/40 px-5 py-3 text-[0.85rem] text-ink">
        <?= $de ? 'Gespeichert.' : 'Kaydedildi.' ?>
      </div>
    <?php endif; ?>

    <div class="py-10"><?= $content ?></div>
  </div>

  <script src="/assets/admin.js?v=<?= e((string) @filemtime(__DIR__ . '/../../public/assets/admin.js')) ?>" defer></script>
</body>
</html>
