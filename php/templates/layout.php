<?php
/**
 * Grundgerüst jeder Seite.
 *
 * @var string $content  fertig gerendertes Seiteninhalt-HTML
 * @var array<string,mixed> $meta
 * @var string $locale
 * @var string $path
 */

use function Atelier\e;
use Atelier\Config;
use Atelier\I18n;

$title = (string) ($meta['title'] ?? 'Atelier Lumière');
$description = (string) ($meta['description'] ?? '');
$canonical = (string) ($meta['canonical'] ?? Config::url() . $path);
$image = (string) ($meta['image'] ?? '');
$noindex = (bool) ($meta['noindex'] ?? false);
$jsonLd = $meta['jsonLd'] ?? [];

// hreflang: derselbe Pfad in der anderen Sprache
$bare = preg_replace('#^/(de|tr)#', '', $path) ?? '';
?>
<!doctype html>
<html lang="<?= e(I18n::htmlLang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#FAF7F2">

  <title><?= e($title) ?></title>
  <?php if ($description !== '') : ?>
    <meta name="description" content="<?= e($description) ?>">
  <?php endif; ?>
  <?php if ($noindex) : ?>
    <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>

  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="alternate" hreflang="de-DE" href="<?= e(Config::url() . '/de' . $bare) ?>">
  <link rel="alternate" hreflang="tr-TR" href="<?= e(Config::url() . '/tr' . $bare) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= e(Config::url() . '/de' . $bare) ?>">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Atelier Lumière">
  <meta property="og:locale" content="<?= e(I18n::ogLocale()) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <?php if ($image !== '') : ?>
    <meta property="og:image" content="<?= e($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
  <?php endif; ?>

  <link rel="icon" href="/assets/icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/style.css?v=<?= e((string) @filemtime(__DIR__ . '/../public/assets/style.css')) ?>">

  <?php foreach ((array) $jsonLd as $block) : ?>
    <?php /* Kleiner-als maskieren, damit kein </script> im Datenblock stecken kann. */ ?>
    <script type="application/ld+json"><?= str_replace('<', chr(92) . 'u003c', (string) json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></script>
  <?php endforeach; ?>
</head>
<body class="min-h-screen antialiased">
  <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:bg-ink focus:px-4 focus:py-2 focus:text-cream">Skip</a>

  <?= \Atelier\View::partial('partials/header', ['locale' => $locale, 'path' => $path]) ?>

  <main id="main"><?= $content ?></main>

  <?= \Atelier\View::partial('partials/footer', ['locale' => $locale]) ?>

  <script src="/assets/app.js?v=<?= e((string) @filemtime(__DIR__ . '/../public/assets/app.js')) ?>" defer></script>

  <?php /* Seiten mit eigenem Verhalten (Galerie, Assistent) laden ihr Skript zusätzlich. */ ?>
  <?php foreach ((array) ($meta['scripts'] ?? []) as $script) : ?>
    <script src="<?= e($script) ?>?v=<?= e((string) @filemtime(__DIR__ . '/../public' . $script)) ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
