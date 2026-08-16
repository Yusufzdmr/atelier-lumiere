<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Datumsangaben in beiden Sprachen.
 *
 * Bewusst ohne intl-Erweiterung: die ist auf Webspaces nicht garantiert, und
 * für zwei Sprachen mit je zwölf Monatsnamen lohnt keine Abhängigkeit.
 */
final class Dates
{
    private const MONTHS = [
        'de' => [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'tr' => [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
    ];

    private const WEEKDAYS = [
        'de' => [0 => 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
        'en' => [0 => 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'tr' => [0 => 'Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'],
    ];

    /** 14. August 2026 / 14 Ağustos 2026 */
    public static function long(string $iso, ?string $locale = null): string
    {
        $time = strtotime($iso);
        if ($time === false) {
            return $iso;
        }

        $locale = $locale ?? I18n::locale();
        $month = self::MONTHS[$locale][(int) date('n', $time)] ?? '';
        $day = (int) date('j', $time);
        $year = date('Y', $time);

        return $locale === 'de' ? "$day. $month $year" : "$day $month $year";
    }

    /** Kurzform für Listen: 14.08.2026 / 14.08.2026 */
    public static function short(string $iso): string
    {
        $time = strtotime($iso);
        return $time === false ? $iso : date('d.m.Y', $time);
    }

    public static function weekday(string $iso, ?string $locale = null): string
    {
        $time = strtotime($iso);
        if ($time === false) {
            return '';
        }
        $locale = $locale ?? I18n::locale();
        return self::WEEKDAYS[$locale][(int) date('w', $time)] ?? '';
    }

    public static function month(string $iso, ?string $locale = null): string
    {
        $time = strtotime($iso);
        if ($time === false) {
            return '';
        }
        $locale = $locale ?? I18n::locale();
        return self::MONTHS[$locale][(int) date('n', $time)] ?? '';
    }
}
