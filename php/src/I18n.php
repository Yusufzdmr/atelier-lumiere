<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Zwei Sprachsätze: /de und /en für die Website, /de und /tr für den
 * Adminbereich. Wer die Seite besucht, und wer sie pflegt, sind nicht
 * dieselben Leute und brauchen nicht dieselben Sprachen.
 *
 * Die Wörterbücher liegen in data/dict.php.
 */
final class I18n
{
    /**
     * Die Sprachen der öffentlichen Seite.
     *
     * Türkisch stand hier von Anfang an, weil der Betrieb türkisch-deutsche
     * Hochzeiten fotografiert. Die Gäste einer Website sind aber nicht die
     * Gäste einer Hochzeit: gesucht wird auf Deutsch, und wer nicht deutsch
     * liest, liest eher englisch als türkisch.
     */
    public const LOCALES = ['de', 'en'];
    public const DEFAULT = 'de';

    /**
     * Die Sprachen des Adminbereichs – ein anderer Satz, andere Leute.
     *
     * Hier sitzt nicht der Besucher, sondern der Betrieb, und der ist
     * türkischsprachig. Deshalb bleibt Türkisch, obwohl es die Website nicht
     * mehr spricht, und Englisch fehlt, weil es hier niemand braucht.
     */
    public const ADMIN_LOCALES = ['de', 'tr'];

    public static function isAdminLocale(string $value): bool
    {
        return in_array($value, self::ADMIN_LOCALES, true);
    }

    private static string $locale = self::DEFAULT;
    /** @var array<string,array<string,mixed>> */
    private static array $dict = [];

    public static function isLocale(string $value): bool
    {
        return in_array($value, self::LOCALES, true);
    }

    public static function set(string $locale): void
    {
        // Auch die Adminsprachen zulassen: sonst faellt /tr/admin auf Deutsch
        // zurueck, sobald irgendetwas dort das Woerterbuch fragt.
        self::$locale = self::isLocale($locale) || self::isAdminLocale($locale)
            ? $locale
            : self::DEFAULT;
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
        return self::$locale === 'en' ? 'en' : 'de-DE';
    }

    public static function ogLocale(): string
    {
        return self::$locale === 'en' ? 'en_GB' : 'de_DE';
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

        /*
         * Erst in der gefragten Sprache, dann auf Deutsch. Ohne diesen Rückweg
         * stand auf einer noch nicht übersetzten Seite nicht der deutsche Satz,
         * sondern der Schlüssel selbst – "nav.prices" mitten im Menü. Lieber
         * eine Zeile in der falschen Sprache als sichtbare Technik.
         */
        $node = self::find($locale, $path);
        if ($node === null && $locale !== self::DEFAULT) {
            $node = self::find(self::DEFAULT, $path);
        }

        return $node;
    }

    private static function find(string $locale, string $path): mixed
    {
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
     * Mehrsprachiges Feld aus den Inhalten: ['de' => …, 'en' => …].
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
     * Liste aus einem mehrsprachigen Feld.
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

    /**
     * Die Sprache, in der es die Website wirklich gibt.
     *
     * Die beiden Sätze überschneiden sich nur in Deutsch: der Betrieb pflegt
     * auf Türkisch, die Website spricht Deutsch und Englisch. Türkisch ist
     * deshalb keine Adresse, sondern eine Arbeitssprache.
     */
    public static function siteLocale(?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;

        return self::isLocale($locale) ? $locale : self::DEFAULT;
    }

    /**
     * Pfad zu einer Seite der Website – auch wenn er im Adminbereich entsteht.
     *
     * Wer den Adminbereich auf Türkisch bedient, bekam mit path() Adressen wie
     * /tr/designs, und die Website hat kein /tr. Jeder „Seite ansehen"-Verweis
     * lief in die eigene 404-Seite. Überall, wo aus der Verwaltung heraus auf
     * die öffentliche Seite gezeigt wird, gehört diese Methode hin und nicht
     * path() – auch bei Adressen, die in einer E-Mail landen.
     */
    public static function sitePath(string $path = '', ?string $locale = null): string
    {
        return self::path($path, self::siteLocale($locale));
    }
}
