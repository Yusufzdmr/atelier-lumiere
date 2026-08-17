<?php
declare(strict_types=1);

namespace Atelier;

use function Atelier\e;

/**
 * Formularfelder aus einer Beschreibung erzeugen – und wieder einlesen.
 *
 * Der Adminbereich besteht zum größten Teil aus denselben paar Feldarten auf
 * verschachtelten Inhalten. Statt zwölf ähnliche Vorlagen zu pflegen, steht
 * hier eine Feldbeschreibung („welcher Pfad, welche Art, welche Beschriftung“)
 * und daraus entsteht das Formular – und beim Speichern der umgekehrte Weg.
 *
 * Feldarten:
 *   text    einzeilig
 *   area    mehrzeilig, unverändert gespeichert
 *   paras   mehrzeilig, Leerzeile trennt Absätze  -> Liste
 *   lines   mehrzeilig, jede Zeile ein Eintrag    -> Liste
 *   pairs   je Zeile „A | B“                      -> Liste aus zwei Feldern
 *   check   Kästchen
 *   number  Zahl
 *   select  Auswahl
 */
final class Form
{
    private const INPUT = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
    private const LABEL = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';
    /** Englische Beschriftungen in derselben Farbe wie ihr Streifen. */
    private const LABEL_EN = 'block text-[0.6rem] uppercase tracking-[0.18em] text-[#9C4A3C]';

    /** Punktpfad -> Formularname (Punkte sind in Namen unpraktisch). */
    public static function key(string $path): string
    {
        return str_replace('.', '__', $path);
    }

    /** Wert an einem Punktpfad lesen. @param array<string,mixed> $data */
    public static function get(array $data, string $path): mixed
    {
        $node = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return null;
            }
            $node = $node[$part];
        }
        return $node;
    }

    /** Wert an einem Punktpfad setzen. @param array<string,mixed> $data */
    public static function set(array &$data, string $path, mixed $value): void
    {
        $node = &$data;
        $parts = explode('.', $path);

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $node[$part] = $value;
                return;
            }
            if (!isset($node[$part]) || !is_array($node[$part])) {
                $node[$part] = [];
            }
            $node = &$node[$part];
        }
    }

    /* ------------------------------ Darstellung ----------------------------- */

    /**
     * Ein Feld ausgeben.
     *
     * @param array{path:string,label:string,type?:string,rows?:int,hint?:string,options?:array<string,string>,max?:int} $field
     * @param array<string,mixed> $data
     */
    public static function field(array $field, array $data, array $originals = []): string
    {
        $type = $field['type'] ?? 'text';
        $name = self::key($field['path']);
        $value = self::get($data, $field['path']);

        /*
         * Ein Kästchen, dessen Feld es in den Daten noch gar nicht gibt, wäre
         * sonst leer – und stünde damit auf „aus“, obwohl der Code die
         * fehlende Angabe als „an“ liest. Beim ersten Speichern hätte sich der
         * Schalter dann von selbst umgelegt.
         */
        if ($type === 'check' && $value === null) {
            $value = (bool) ($field['default'] ?? false);
        }
        $label = '<label class="' . (str_ends_with((string) $field['path'], '.en') ? self::LABEL_EN : self::LABEL) . '" for="' . e($name) . '">' . e($field['label']) . '</label>';
        $hint = isset($field['hint'])
            ? '<p class="mt-2 text-[0.72rem] leading-relaxed text-muted">' . e($field['hint']) . '</p>'
            : '';

        $control = match ($type) {
            'area' => self::textarea($name, is_string($value) ? $value : '', $field['rows'] ?? 4),
            'paras' => self::textarea($name, is_array($value) ? implode("\n\n", array_map('strval', $value)) : (string) $value, $field['rows'] ?? 6),
            'lines' => self::textarea($name, is_array($value) ? implode("\n", array_map('strval', $value)) : (string) $value, $field['rows'] ?? 4),
            'pairs', 'rows' => self::textarea($name, self::rowsToText(is_array($value) ? $value : [], $field), $field['rows'] ?? 5),
            'check' => '<label class="mt-1 flex cursor-pointer items-center gap-3 text-[0.85rem] text-ink">'
                . '<input type="checkbox" name="' . e($name) . '" ' . ($value ? 'checked' : '')
                . ' class="h-4 w-4 accent-[#B08D57]">' . e($field['label']) . '</label>',
            'number' => '<input id="' . e($name) . '" type="number" name="' . e($name) . '" value="' . e((string) $value) . '" class="' . self::INPUT . '">',
            'select' => self::select($name, (string) $value, $field['options'] ?? []),
            default => '<input id="' . e($name) . '" name="' . e($name) . '" value="' . e((string) $value) . '" class="' . self::INPUT . '">',
        };

        /*
         * Die englischen Felder farblich absetzen.
         *
         * Deutsch und Englisch stehen paarweise untereinander und sehen sonst
         * gleich aus – man tippt den deutschen Satz ins englische Feld, merkt
         * es beim Speichern nicht, und auf der Seite steht es doppelt. Ein
         * schmaler Streifen an der Seite genügt, um sie auseinanderzuhalten.
         */
        $englisch = str_ends_with((string) $field['path'], '.en');

        /*
         * Steht das Feld in einem Sprachpaar, trägt die Spalte darüber schon
         * „DE“ oder „EN“. Dann hier keinen zweiten Hinweis: kein roter Streifen
         * und keine eigene Beschriftung, sonst steht dasselbe dreimal.
         */
        if (!empty($field['paired'])) {
            return '<div>' . $control . self::originalNote($field, $value, $originals) . '</div>';
        }

        $rahmen = $englisch ? ' class="border-l-2 border-[#9C4A3C]/40 pl-4"' : '';

        // Beim Kästchen steckt die Beschriftung schon im Steuerelement.
        return '<div' . $rahmen . '>' . ($type === 'check' ? '' : $label) . $control . $hint
            . self::originalNote($field, $value, $originals) . '</div>';
    }

    /**
     * Was ursprünglich in diesem Feld stand – mit einem Knopf dorthin zurück.
     *
     * Erscheint nur, wenn es wirklich abweicht: bei dreihundert Feldern wäre
     * ein Hinweis unter jedem einzelnen kein Hinweis mehr, sondern Grundrauschen.
     *
     * @param array<string,mixed> $field
     * @param array<string,mixed> $originals
     */
    private static function originalNote(array $field, mixed $value, array $originals): string
    {
        if ($originals === [] || ($field['type'] ?? 'text') === 'check') {
            return '';
        }

        $original = self::get($originals, (string) $field['path']);
        if ($original === null) {
            return '';
        }

        $before = self::display($original, $field);
        if ($before === '' || $before === self::display($value, $field)) {
            return '';
        }

        $de = I18n::isDe();
        $short = mb_strlen($before) > 220 ? mb_substr($before, 0, 220) . ' …' : $before;

        return '<div class="mt-2.5 flex flex-wrap items-start gap-x-3 gap-y-1 border-l-2 border-sand-deep pl-3">'
            . '<p class="min-w-0 flex-1 whitespace-pre-line text-[0.72rem] leading-relaxed text-muted">'
            . '<span class="uppercase tracking-[0.14em]">' . ($de ? 'Vorher' : 'Öncesi') . ':</span> '
            . e($short) . '</p>'
            . '<button name="was" value="reset:' . e((string) $field['path']) . '"'
            . ' class="shrink-0 text-[0.62rem] uppercase tracking-[0.14em] text-muted underline-offset-4 hover:text-gold hover:underline">'
            . ($de ? 'zurücksetzen' : 'geri al') . '</button>'
            . '</div>';
    }

    /** Einen gespeicherten Wert so darstellen, wie er im Feld stünde. @param array<string,mixed> $field */
    private static function display(mixed $value, array $field): string
    {
        return match ($field['type'] ?? 'text') {
            'paras' => is_array($value) ? implode("\n\n", array_map('strval', $value)) : (string) $value,
            'lines' => is_array($value) ? implode("\n", array_map('strval', $value)) : (string) $value,
            'pairs', 'rows' => self::rowsToText(is_array($value) ? $value : [], $field),
            default => is_array($value) ? '' : (string) $value,
        };
    }

    /**
     * Alle Felder eines Formulars ausgeben.
     *
     * @param list<array<string,mixed>> $fields
     * @param array<string,mixed> $data
     */
    public static function fields(array $fields, array $data, string $grid = 'md:grid-cols-2', array $originals = []): string
    {
        $html = '<div class="grid gap-7 ' . e($grid) . '">';

        $anzahl = count($fields);
        for ($i = 0; $i < $anzahl; $i++) {
            $field = $fields[$i];
            $partner = $fields[$i + 1] ?? null;

            // Deutsch und Englisch desselben Feldes stehen im Formular
            // hintereinander. Erkannt wird das am Pfad, nicht an der Reihenfolge.
            if ($partner !== null && self::isPair((string) $field['path'], (string) $partner['path'])) {
                $html .= self::pair($field, $partner, $data, $originals);
                $i++;
                continue;
            }

            $span = !empty($field['wide']) ? ' class="md:col-span-2"' : '';
            $html .= '<div' . $span . '>' . self::field($field, $data, $originals) . '</div>';
        }

        return $html . '</div>';
    }

    /** Zwei Pfade, die sich nur in der Sprache unterscheiden. */
    private static function isPair(string $a, string $b): bool
    {
        return str_ends_with($a, '.de')
            && str_ends_with($b, '.en')
            && substr($a, 0, -3) === substr($b, 0, -3);
    }

    /**
     * Ein Sprachpaar als eine Zeile mit zwei Spalten.
     *
     * Vorher standen die beiden Sprachen untereinander und sahen gleich aus;
     * unterschieden hat sie ein schmaler roter Streifen am englischen Feld.
     * Wer schnell etwas ändert, übersieht den. Nebeneinander mit „DE“ und „EN“
     * darüber muss man nicht mehr hinsehen, um es zu wissen.
     *
     * @param array<string,mixed> $de
     * @param array<string,mixed> $en
     * @param array<string,mixed> $data
     * @param array<string,mixed> $originals
     */
    private static function pair(array $de, array $en, array $data, array $originals): string
    {
        /*
         * „Einleitung (DE)“ und „Einleitung (EN)“ tragen denselben Namen. Das
         * Sprachkürzel steht nicht immer am Ende – „Fließtext (DE) – Leerzeile
         * trennt Absätze“ hat es in der Mitte, deshalb wird es überall entfernt
         * und nicht nur hinten.
         */
        $label = (string) preg_replace('/\s*\((?:DE|EN)\)/u', '', (string) ($de['label'] ?? ''));
        $label = trim((string) preg_replace('/\s{2,}/u', ' ', $label));
        $hint = isset($de['hint'])
            ? '<p class="mt-2 text-[0.72rem] leading-relaxed text-muted">' . e((string) $de['hint']) . '</p>'
            : '';

        $spalte = static fn (string $text, string $farbe): string =>
            '<div class="mb-2 text-[0.6rem] uppercase tracking-[0.18em] ' . $farbe . '">' . e($text) . '</div>';

        $de['paired'] = true;
        $en['paired'] = true;

        return '<div class="col-span-full">'
            . '<div class="' . self::LABEL . '">' . e($label) . '</div>'
            . '<div class="mt-3 grid gap-x-6 gap-y-5 md:grid-cols-2">'
            . '<div>' . $spalte('Deutsch', 'text-muted') . self::field($de, $data, $originals) . '</div>'
            . '<div class="md:border-l md:border-[#9C4A3C]/25 md:pl-6">'
            . $spalte('English', 'text-[#9C4A3C]') . self::field($en, $data, $originals) . '</div>'
            . '</div>' . $hint . '</div>';
    }

    private static function textarea(string $name, string $value, int $rows): string
    {
        return '<textarea id="' . e($name) . '" name="' . e($name) . '" rows="' . $rows . '" class="'
            . self::INPUT . ' resize-y">' . e($value) . '</textarea>';
    }

    /** @param array<string,string> $options */
    private static function select(string $name, string $value, array $options): string
    {
        $html = '<select id="' . e($name) . '" name="' . e($name) . '" class="' . self::INPUT . '">';
        foreach ($options as $key => $caption) {
            $html .= '<option value="' . e((string) $key) . '"' . ($value === (string) $key ? ' selected' : '') . '>'
                . e($caption) . '</option>';
        }
        return $html . '</select>';
    }

    /**
     * Zeilen fuer das Textfeld: je Datensatz eine Zeile, Spalten mit | getrennt.
     *
     * @param list<array<string,mixed>> $rows
     * @param array<string,mixed> $field
     */
    private static function rowsToText(array $rows, array $field): string
    {
        $cols = self::columns($field);
        $out = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cells = [];
            foreach ($cols as $col) {
                $value = $row[$col['key']] ?? '';
                $cells[] = is_array($value) ? I18n::pick($value) : (string) $value;
            }
            $out[] = implode(' | ', $cells);
        }

        return implode("\n", $out);
    }

    /**
     * Spaltenbeschreibung eines Zeilenfelds. „pairs“ bleibt als Kurzform
     * erhalten: zwei Spalten, beide zweisprachig.
     *
     * @param array<string,mixed> $field
     * @return list<array{key:string,bilingual:bool}>
     */
    private static function columns(array $field): array
    {
        if (isset($field['cols']) && is_array($field['cols'])) {
            $cols = [];
            foreach ($field['cols'] as $col) {
                $cols[] = [
                    'key'       => (string) ($col['key'] ?? 'a'),
                    'bilingual' => (bool) ($col['bilingual'] ?? false),
                ];
            }
            return $cols;
        }

        return [
            ['key' => (string) ($field['a'] ?? 'q'), 'bilingual' => (bool) ($field['bilingual'] ?? true)],
            ['key' => (string) ($field['b'] ?? 'a'), 'bilingual' => (bool) ($field['bilingual'] ?? true)],
        ];
    }

    /* -------------------------------- Einlesen ------------------------------- */

    /**
     * Formularwerte zurück in die Inhalte schreiben.
     *
     * @param array<string,mixed> $data
     * @param list<array<string,mixed>> $fields
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    public static function apply(array $data, array $fields, array $post): array
    {
        foreach ($fields as $field) {
            $path = (string) $field['path'];
            $name = self::key($path);
            $type = $field['type'] ?? 'text';
            $max = (int) ($field['max'] ?? 4000);

            if ($type === 'check') {
                self::set($data, $path, isset($post[$name]));
                continue;
            }

            if (!array_key_exists($name, $post)) {
                continue;
            }

            $raw = Security::clean($post[$name], $max);

            $value = match ($type) {
                'paras'  => self::splitParagraphs($raw),
                'lines'  => self::splitLines($raw),
                'pairs', 'rows' => self::splitRows($raw, $field, self::get($data, $path)),
                'number' => (string) (int) $raw,
                default  => $raw,
            };

            self::set($data, $path, $value);
        }

        return $data;
    }

    /** @return list<string> */
    public static function splitParagraphs(string $text): array
    {
        $parts = preg_split('/\n{2,}/', $text) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    }

    /** @return list<string> */
    public static function splitLines(string $text): array
    {
        $parts = explode("\n", $text);
        return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    }

    /**
     * Zeilen einlesen. Zweisprachige Spalten behalten die andere Sprache:
     * bearbeitet wird immer nur die gerade gewaehlte.
     *
     * @param array<string,mixed> $field
     * @return list<array<string,mixed>>
     */
    public static function splitRows(string $text, array $field, mixed $previous): array
    {
        $cols = self::columns($field);
        $locale = I18n::locale();
        $old = is_array($previous) ? array_values($previous) : [];
        $out = [];

        foreach (self::splitLines($text) as $i => $line) {
            $cells = array_map('trim', explode('|', $line, count($cols)));
            $before = is_array($old[$i] ?? null) ? $old[$i] : [];
            $row = $before;

            foreach ($cols as $index => $col) {
                $cell = $cells[$index] ?? '';

                if (!$col['bilingual']) {
                    $row[$col['key']] = $cell;
                    continue;
                }

                $field_ = is_array($before[$col['key']] ?? null)
                    ? $before[$col['key']]
                    : ['de' => '', 'tr' => ''];
                $field_[$locale] = $cell;
                $row[$col['key']] = $field_;
            }

            $out[] = $row;
        }

        return $out;
    }
}
