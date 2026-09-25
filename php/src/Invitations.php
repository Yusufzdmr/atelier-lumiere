<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Adresstauglicher Name aus einem beliebigen Text.
 *
 * War einmal Teil der ersten Einladungsfassung (siehe Git-Historie,
 * 2026-09-25 vor diesem Commit) – inzwischen an Stellen im Kundenbereich
 * verwendet, die mit Einladungen nichts zu tun haben: Kundencode,
 * Listensortierung, OG-Bild. Ausserdem baut InvitationsV2::slug() (die
 * URLs aller lebenden Einladungen) direkt darauf auf. Deshalb bleibt die
 * Klasse, auch ohne die Einladung, die ihr den Namen gab.
 */
final class Invitations
{
    /** Adresstauglicher Name: Kleinbuchstaben, Ziffern, Bindestrich. */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'â' => 'a'];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
