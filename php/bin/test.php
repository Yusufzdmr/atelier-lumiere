<?php
declare(strict_types=1);

/**
 * Testlaeufer ohne Abhaengigkeit.
 *
 *   php bin/test.php              alle Dateien in tests/
 *   php bin/test.php design_css   nur passende
 *
 * Warum nicht src/bootstrap.php: das laedt config.php, und die liegt nicht im
 * Repository (sie entsteht erst auf dem Server). Die reinen Funktionen brauchen
 * weder Konfiguration noch Datenbank, also laedt dieser Laeufer nur den
 * Autoloader und die Kurzhelfer.
 */

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atelier\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/../src/View.php'; // e()
mb_internal_encoding('UTF-8');

$failures = [];
$passed = 0;

function assert_same(mixed $expected, mixed $actual, string $label): void
{
    global $failures, $passed;
    if ($expected === $actual) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    erwartet: " . var_export($expected, true)
                         . "\n    bekommen: " . var_export($actual, true);
}

function assert_true(bool $ok, string $label): void
{
    global $failures, $passed;
    if ($ok) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    erwartet: true";
}

function assert_contains(string $haystack, string $needle, string $label): void
{
    global $failures, $passed;
    if (str_contains($haystack, $needle)) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    fehlt: " . $needle . "\n    in: " . $haystack;
}

function assert_not_contains(string $haystack, string $needle, string $label): void
{
    global $failures, $passed;
    if (!str_contains($haystack, $needle)) {
        $passed++;
        return;
    }
    $failures[] = $label . "\n    darf nicht vorkommen: " . $needle . "\n    in: " . $haystack;
}

/** Datenbanktests laufen nur, wenn eine Konfiguration da ist. */
function needs_db(): bool
{
    return is_file(__DIR__ . '/../config.php');
}

$filter = $argv[1] ?? '';
$files = glob(__DIR__ . '/../tests/*.php') ?: [];
sort($files);

foreach ($files as $file) {
    if ($filter !== '' && !str_contains(basename($file), $filter)) {
        continue;
    }
    echo '— ', basename($file), "\n";
    require $file;
}

echo "\n";
if ($failures === []) {
    echo $passed, " Prüfungen bestanden.\n";
    exit(0);
}

foreach ($failures as $failure) {
    echo "FEHLER: ", $failure, "\n\n";
}
echo count($failures), " von ", $passed + count($failures), " fehlgeschlagen.\n";
exit(1);
