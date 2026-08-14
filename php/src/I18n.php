<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Zweisprachigkeit wie in der Next.js-Fassung: /de und /tr, dieselben Texte.
 *
 * Die Wörterbücher liegen in data/dict.php und werden aus lib/dict.ts erzeugt
 * (scripts/export-dict.mjs) – so bleiben beide Fassungen wortgleich, statt
 * dass jemand hunderte Sätze abtippt.
 */
final class I18n
{
    public const LOCALES = ['de', 'tr'];
    public const DEFAULT = 'de';

    private static string $locale = self::DEFAULT;
    /** @var array<string,array<string,mixed>> */
    private static array $dict = [];

    public static function isLocale(string $value): bool
    {
        return in_array($value, self::LOCALES, true);
    }

    public static function set(string $locale): void
    {
        self::$locale = self::isLocale($locale) ? $locale : self::DEFAULT;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function isDe(): bool
    {
        return self::$locale === 'de';
    }

    public static function htmlLang(): string
    {
        return self::$locale === 'tr' ? 'tr-TR' : 'de-DE';
    }

    public static function ogLocale(): string
    {
        return self::$locale === 'tr' ? 'tr_TR' : 'de_DE';
    }

    /**
     * Text aus dem Wörterbuch: t('nav.prices'). Fehlt der Schlüssel, kommt der
     * Schlüssel selbst zurück – sichtbar, aber ohne Absturz.
     */
    public static function t(string $path, ?string $locale = null): string
    {
        $value = self::raw($path, $locale);
        return is_string($value) ? $value : $path;
    }

    /** Wie t(), aber auch für Listen und verschachtelte Blöcke. */
    public static function raw(string $path, ?string $locale = null): mixed
    {
        $locale = $locale ?? self::$locale;

        // Im Adminbereich geänderte Seitentexte haben Vorrang. Das Wörterbuch
        // bleibt unangetastet und ist damit weiter der Originalwortlaut.
        $override = Texts::get($path, $locale);
        if ($override !== null) {
            return $override;
        }

        if (self::$dict === []) {
            /** @var array<string,array<string,mixed>> $dict */
            $dict = require __DIR__ . '/../data/dict.php';
            self::$dict = $dict;
        }

        $node = self::$dict[$locale] ?? [];
        foreach (explode('.', $path) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return null;
            }
            $node = $node[$part];
        }

        return $node;
    }

    /**
     * Zweisprachiges Feld aus den Inhalten: ['de' => …, 'tr' => …].
     *
     * @param array<string,mixed>|string|null $field
     */
    public static function pick(array|string|null $field, ?string $locale = null): string
    {
        if (is_string($field)) {
            return $field;
        }
        if ($field === null) {
            return '';
        }
        $locale = $locale ?? self::$locale;
        $value = $field[$locale] ?? $field[self::DEFAULT] ?? '';
        return is_string($value) ? $value : '';
    }

    /**
     * Liste aus einem zweisprachigen Feld.
     *
     * @param array<string,mixed>|null $field
     * @return list<string>
     */
    public static function pickList(?array $field, ?string $locale = null): array
    {
        $locale = $locale ?? self::$locale;
        $value = $field[$locale] ?? $field[self::DEFAULT] ?? [];
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /** Pfad mit Sprachpräfix: path('/preise') → /de/preise */
    public static function path(string $path = '', ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;
        $path = $path === '/' ? '' : $path;
        return '/' . $locale . $path;
    }
}
