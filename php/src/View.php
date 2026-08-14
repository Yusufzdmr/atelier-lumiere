<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Schlichtes Templating mit PHP selbst: kein Twig, kein Build-Schritt.
 *
 * Regel im Haus: In Templates wird nur ausgegeben, nicht gerechnet. Alles,
 * was Logik ist, steht vorher im Controller.
 */
final class View
{
    /** @var array<string,mixed> Werte, die jedes Template kennt (Kopf-/Fußbereich) */
    private static array $shared = [];

    /** @param array<string,mixed> $values */
    public static function share(array $values): void
    {
        self::$shared = array_merge(self::$shared, $values);
    }

    /**
     * Seite mit Layout ausgeben.
     *
     * @param array<string,mixed> $data
     */
    public static function page(string $template, array $data = []): void
    {
        $content = self::capture($template, $data);
        $meta = $data['meta'] ?? [];
        // Der Adminbereich hat sein eigenes Grundgerüst.
        $layout = (string) ($data['layout'] ?? 'layout');

        echo self::capture($layout, array_merge($data, [
            'content' => $content,
            'meta'    => $meta,
        ]));
    }

    /**
     * Nur ein Teilstück rendern (Kopf, Karte, Formularblock …).
     *
     * @param array<string,mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    /**
     * Die eigenen Variablen heißen hier absichtlich __so: extract() arbeitet
     * mit EXTR_SKIP und überschreibt nichts, was es schon gibt. Hieße der
     * Parameter $data, käme ein Wert namens „data“ nie im Template an – er
     * würde stillschweigend übersprungen, und die Vorlage sähe stattdessen die
     * ganze Übergabeliste. Genau das ist einmal passiert: die Felder im
     * Adminbereich blieben leer, obwohl die Texte in der Datenbank standen.
     *
     * @param array<string,mixed> $__values
     */
    private static function capture(string $__template, array $__values): string
    {
        $__file = __DIR__ . '/../templates/' . $__template . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException('Template fehlt: ' . $__template);
        }

        extract(array_merge(self::$shared, $__values), EXTR_SKIP);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    }
}

/* ----------------------------- Kurzhelfer ----------------------------- */

/** Ausgabe maskieren – in Templates die einzige erlaubte Ausgabeform. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absätze eines mehrzeiligen Textes. @return list<string> */
function paragraphs(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    $parts = preg_split('/\n{2,}/', str_replace("\r\n", "\n", $text)) ?: [];
    return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
}

/** Zeilen eines Textfelds. @return list<string> */
function lines(?string $text): array
{
    if ($text === null) {
        return [];
    }
    $parts = explode("\n", str_replace("\r\n", "\n", $text));
    return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
}
