<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Die festen Seitentexte editierbar machen.
 *
 * Überschriften wie „Was wir für euch tun“ standen bisher nur im Wörterbuch
 * (data/dict.php) und liessen sich nirgends ändern – der Betrieb konnte den
 * Vorspann der Startseite pflegen, aber nicht die Zeile darüber.
 *
 * Statt das Wörterbuch zu beschreiben (es wird aus der Next.js-Fassung neu
 * erzeugt und wäre danach wieder überschrieben) liegt hier eine Schicht
 * darüber: geändert wird nur, was wirklich geändert wurde, alles andere kommt
 * weiter aus dem Wörterbuch. Damit bleibt der Originaltext immer erhalten und
 * ein „zurücksetzen“ ist ein Löschen, kein Zurückschreiben.
 */
final class Texts
{
    /** Zweig in den Inhalten, unter dem die Änderungen liegen. */
    private const KEY = 'texts';

    /** @var array<string,array<string,string>>|null */
    private static ?array $cache = null;

    /** @var array<string,array<string,string>>|null */
    private static ?array $dict = null;

    /**
     * Die Gruppen des Wörterbuchs in der Reihenfolge, in der sie im
     * Adminbereich stehen – und wie sie dort heissen.
     *
     * @var array<string,array{de:string,tr:string}>
     */
    public const GROUPS = [
        'home'      => ['de' => 'Startseite', 'tr' => 'Ana sayfa'],
        'nav'       => ['de' => 'Menü & Navigation', 'tr' => 'Menü & gezinme'],
        'footer'    => ['de' => 'Fußbereich', 'tr' => 'Alt bilgi'],
        'services'  => ['de' => 'Leistungen', 'tr' => 'Hizmetler'],
        'prices'    => ['de' => 'Preise', 'tr' => 'Fiyatlar'],
        'portfolio' => ['de' => 'Portfolio', 'tr' => 'Portfolyo'],
        'city'      => ['de' => 'Stadtseiten', 'tr' => 'Şehir sayfaları'],
        'venue'     => ['de' => 'Locationseiten', 'tr' => 'Mekân sayfaları'],
        'blog'      => ['de' => 'Ratgeber', 'tr' => 'Rehber'],
        'about'     => ['de' => 'Über mich', 'tr' => 'Hakkımda'],
        'contact'   => ['de' => 'Kontakt & Formular', 'tr' => 'İletişim & form'],
        'gallery'   => ['de' => 'Kundengalerie', 'tr' => 'Müşteri galerisi'],
        'invite'    => ['de' => 'Einladungen', 'tr' => 'Davetiyeler'],
        'video'     => ['de' => 'Videos', 'tr' => 'Videolar'],
        'cookie'    => ['de' => 'Cookie-Hinweis', 'tr' => 'Çerez uyarısı'],
        'common'    => ['de' => 'Allgemeines', 'tr' => 'Genel'],
        'admin'     => ['de' => 'Adminbereich', 'tr' => 'Yönetim paneli'],
    ];

    /* -------------------------------- Lesen --------------------------------- */

    /**
     * Geänderte Texte: ['home.servicesTitle' => ['de' => '…', 'tr' => '…']].
     *
     * @return array<string,array<string,string>>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stored = Content::all()[self::KEY] ?? [];
        $texts = [];

        foreach (is_array($stored) ? $stored : [] as $key => $field) {
            if (!is_array($field)) {
                continue;
            }
            foreach ($field as $locale => $value) {
                if (is_string($value) && $value !== '' && I18n::isLocale((string) $locale)) {
                    $texts[(string) $key][(string) $locale] = $value;
                }
            }
        }

        self::$cache = $texts;
        return $texts;
    }

    /**
     * Der geänderte Text – oder null, wenn das Wörterbuch gilt.
     *
     * Wird bei jedem t() gefragt und muss deshalb billig sein: gelesen wird
     * einmal pro Aufruf, danach steht alles im Speicher.
     */
    public static function get(string $key, string $locale): ?string
    {
        return self::all()[$key][$locale] ?? null;
    }

    /** Der unveränderte Text aus dem Wörterbuch. */
    public static function original(string $key, string $locale): string
    {
        if (self::$dict === null) {
            /** @var array<string,array<string,mixed>> $raw */
            $raw = require __DIR__ . '/../data/dict.php';
            $flat = [];

            foreach ($raw as $lang => $groups) {
                foreach (is_array($groups) ? $groups : [] as $group => $entries) {
                    foreach (is_array($entries) ? $entries : [] as $name => $value) {
                        if (is_string($value)) {
                            $flat[$group . '.' . $name][(string) $lang] = $value;
                        }
                    }
                }
            }

            self::$dict = $flat;
        }

        return self::$dict[$key][$locale] ?? '';
    }

    /**
     * Alle Schlüssel einer Gruppe mit Original und aktuellem Stand.
     *
     * @return list<array{key:string,original:array<string,string>,current:array<string,string>,changed:bool}>
     */
    public static function group(string $group): array
    {
        /** @var array<string,array<string,mixed>> $raw */
        $raw = require __DIR__ . '/../data/dict.php';
        $entries = $raw[I18n::DEFAULT][$group] ?? [];
        $out = [];

        foreach (is_array($entries) ? $entries : [] as $name => $value) {
            if (!is_string($value)) {
                continue;
            }

            $key = $group . '.' . $name;
            $original = [];
            $current = [];
            $changed = false;

            foreach (I18n::LOCALES as $locale) {
                $original[$locale] = self::original($key, $locale);
                $override = self::get($key, $locale);
                $current[$locale] = $override ?? $original[$locale];
                $changed = $changed || ($override !== null && $override !== $original[$locale]);
            }

            $out[] = ['key' => $key, 'original' => $original, 'current' => $current, 'changed' => $changed];
        }

        return $out;
    }

    public static function isGroup(string $group): bool
    {
        return array_key_exists($group, self::GROUPS);
    }

    /** Wie viele Texte einer Gruppe geändert wurden – für den Reiter. */
    public static function changedIn(string $group): int
    {
        $count = 0;
        foreach (array_keys(self::all()) as $key) {
            if (str_starts_with((string) $key, $group . '.')) {
                $count++;
            }
        }
        return $count;
    }

    /* ------------------------------ Schreiben -------------------------------- */

    /**
     * Eine Gruppe speichern.
     *
     * Gespeichert wird nur, was vom Original abweicht. Wer einen Text von Hand
     * wieder auf den Originalwortlaut bringt, hat damit auch zurückgesetzt –
     * das ist genau das, was jemand erwartet, der es so tippt.
     *
     * @param array<string,string> $post Formularwerte, Schlüssel wie im Formular
     */
    public static function saveGroup(string $group, array $post): void
    {
        $changes = [];

        foreach (self::group($group) as $entry) {
            $key = $entry['key'];

            foreach (I18n::LOCALES as $locale) {
                $name = self::field($key, $locale);
                if (!array_key_exists($name, $post)) {
                    continue;
                }

                $value = Security::clean($post[$name], 4000);
                // Leer heisst „nimm wieder den Originaltext“ – niemand will
                // eine Überschrift wirklich löschen.
                $changes[$key][$locale] = ($value === '' || $value === $entry['original'][$locale]) ? null : $value;
            }
        }

        self::apply($changes);
    }

    /** Einen Text auf das Wörterbuch zurücksetzen. Ohne Sprache: beide. */
    public static function reset(string $key, ?string $locale = null): void
    {
        $changes = [];
        foreach ($locale === null ? I18n::LOCALES : [$locale] as $one) {
            $changes[$key][$one] = null;
        }
        self::apply($changes);
    }

    /**
     * Änderungen einarbeiten. null entfernt einen Eintrag.
     *
     * @param array<string,array<string,string|null>> $changes
     */
    private static function apply(array $changes): void
    {
        Content::mutate(static function (array $content) use ($changes): array {
            $texts = is_array($content[self::KEY] ?? null) ? $content[self::KEY] : [];

            foreach ($changes as $key => $field) {
                foreach ($field as $locale => $value) {
                    if ($value === null) {
                        unset($texts[$key][$locale]);
                    } else {
                        $texts[$key][$locale] = $value;
                    }
                }

                // Keine leeren Hüllen stehen lassen – sonst wächst das
                // Dokument mit jedem Zurücksetzen.
                if (($texts[$key] ?? null) === []) {
                    unset($texts[$key]);
                }
            }

            $content[self::KEY] = $texts;
            return $content;
        });

        self::$cache = null;
    }

    /* -------------------------------- Helfer --------------------------------- */

    /** Formularname eines Feldes – Punkte sind in Namen unpraktisch. */
    public static function field(string $key, string $locale): string
    {
        return 't__' . str_replace('.', '__', $key) . '__' . $locale;
    }
}
