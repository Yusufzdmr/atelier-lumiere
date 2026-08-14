<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Preise der digitalen Einladung – dieselben Zahlen wie in lib/pricing.ts.
 *
 * `regular` ist der reguläre Preis, `now` der Einführungspreis; die Differenz
 * weist der Assistent als Ersparnis aus.
 *
 * Gerechnet wird immer hier. Was der Browser meldet, ist Anzeige – der Betrag,
 * der bei PayPal landet, entsteht ausschließlich auf dem Server.
 */
final class Pricing
{
    public const BASE = ['regular' => 99, 'now' => 79];
    public const SECOND_EVENT = ['regular' => 39, 'now' => 20];

    /** Aufpreis je zuschaltbarem Abschnitt. */
    public const SECTIONS = [
        'rsvp'      => ['regular' => 0, 'now' => 0],
        'location'  => ['regular' => 0, 'now' => 0],
        'countdown' => ['regular' => 0, 'now' => 0],
        'program'   => ['regular' => 0, 'now' => 0],
        'family'    => ['regular' => 0, 'now' => 0],
        'menu'      => ['regular' => 19, 'now' => 0],
        'music'     => ['regular' => 29, 'now' => 19],
        'video'     => ['regular' => 49, 'now' => 29],
    ];

    /** Abschnitte, die der Assistent anbietet (Reihenfolge = Anzeige). */
    public const SECTION_KEYS = ['rsvp', 'location', 'countdown', 'program', 'family', 'menu', 'music', 'video'];

    /**
     * Endbetrag. `free` kommt aus der Gutscheinprüfung – niemals aus dem
     * Browser.
     *
     * @param array<string,bool> $sections
     */
    public static function total(array $sections, bool $twoEvents, bool $free): int
    {
        if ($free) {
            return 0;
        }

        $sum = self::BASE['now'] + ($twoEvents ? self::SECOND_EVENT['now'] : 0);
        foreach (self::SECTIONS as $key => $price) {
            if (!empty($sections[$key])) {
                $sum += $price['now'];
            }
        }

        return $sum;
    }

    /**
     * Einzelposten für die Anzeige.
     *
     * @param array<string,bool> $sections
     * @param array<string,string> $labels
     * @return list<array{key:string,label:string,regular:int,now:int}>
     */
    public static function lines(array $sections, bool $twoEvents, array $labels, string $baseLabel, string $secondLabel): array
    {
        $lines = [['key' => 'base', 'label' => $baseLabel, 'regular' => self::BASE['regular'], 'now' => self::BASE['now']]];

        if ($twoEvents) {
            $lines[] = ['key' => 'second', 'label' => $secondLabel, 'regular' => self::SECOND_EVENT['regular'], 'now' => self::SECOND_EVENT['now']];
        }

        foreach (self::SECTIONS as $key => $price) {
            if (empty($sections[$key]) || $price['regular'] === 0) {
                continue;
            }
            $lines[] = ['key' => $key, 'label' => $labels[$key] ?? $key, 'regular' => $price['regular'], 'now' => $price['now']];
        }

        return $lines;
    }

    public static function euro(int $amount): string
    {
        return $amount === 0 ? '0 €' : $amount . ' €';
    }

    /** Standardbelegung der Abschnitte für einen neuen Entwurf. @return array<string,bool> */
    public static function defaultSections(): array
    {
        return [
            'countdown' => true,
            'program'   => true,
            'location'  => true,
            'menu'      => false,
            'family'    => false,
            'music'     => false,
            'video'     => false,
            'rsvp'      => true,
        ];
    }
}
