<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Schutzkopfzeilen für jede Antwort.
 *
 * Die .htaccess setzt schon einige davon – aber nur, wenn mod_headers läuft
 * und die Datei überhaupt gelesen wird. Beides ist auf einem fremden Webspace
 * keine Selbstverständlichkeit, und beim eingebauten Server von PHP gilt es
 * ohnehin nicht. Deshalb hier noch einmal, aus dem Programm heraus.
 *
 * Die Richtlinie ist eng, weil die Seite es hergibt: kein einziger
 * `onclick=`-Aufsatz im HTML, das Verhalten liegt als Datei vor, und die
 * einzige eingebettete Fremdquelle sind die Videodienste. Zwei eingebettete
 * Bloecke gibt es doch, und beide tragen die Einmalkennung – siehe nonce().
 */
final class Http
{
    private static string $nonce = '';

    /**
     * Einmalkennung dieser Antwort.
     *
     * Wer sie traegt, ist eine kurze und vollstaendige Liste – sie hier zu
     * fuehren ist kein Ordnungssinn, sondern Vorsicht: fiele der letzte
     * Traeger weg und mit ihm das `'nonce-…'` aus script-src, stuerbe der
     * andere Block ohne eine Zeile in der Konsole. Ein Skript ohne gueltige
     * Kennung fuehrt der Browser gar nicht erst aus.
     *
     * 1. Der JSON-LD-Datenblock fuer Suchmaschinen (templates/layout.php).
     * 2. Das Schrittwechsel-Skript des Bearbeiten-Bildschirms
     *    (templates/pages/invite-v2-edit.php) – es ruestet die Reiter zu
     *    Schaltflaechen auf und muss dort stehen, weil es die Reiter genau
     *    dieser einen Seite bedient.
     */
    public static function nonce(): string
    {
        if (self::$nonce === '') {
            self::$nonce = base64_encode(random_bytes(12));
        }
        return self::$nonce;
    }

    /** Wird als Erstes im Front-Controller aufgerufen. */
    public static function harden(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');
        // Nichts davon braucht die Seite; nicht gefragt zu werden ist besser,
        // als nein sagen zu müssen.
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), interest-cohort=()');

        // Nur wenn wirklich über HTTPS ausgeliefert wird – sonst sperrt man
        // sich eine Entwicklungsumgebung aus, die es noch nicht kann.
        if (!Config::isDev() && self::isHttps()) {
            header('Strict-Transport-Security: max-age=15768000; includeSubDomains');
        }

        header('Content-Security-Policy: ' . self::policy());
    }

    private static function isHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    private static function policy(): string
    {
        // Videos werden erst nach dem Antippen geladen (zwei Klicks), die
        // Karte auf der Kontaktseite genauso. Erlaubt sein müssen sie
        // trotzdem, sonst bleibt der Rahmen nach dem Klick leer.
        $frames = [
            'https://www.youtube-nocookie.com',
            'https://www.youtube.com',
            'https://player.vimeo.com',
            'https://www.google.com',
        ];

        // Die Messdienste kommen nur in die Richtlinie, wenn ueberhaupt eine
        // Kennung hinterlegt ist. Solange nichts gemessen wird, bleibt sie eng.
        $scripts = ["'self'", "'nonce-" . self::nonce() . "'"];
        $connects = ["'self'"];

        if (self::tracks()) {
            $scripts[] = 'https://www.googletagmanager.com';
            $scripts[] = 'https://connect.facebook.net';
            $connects[] = 'https://www.googletagmanager.com';
            $connects[] = 'https://www.google-analytics.com';
            $connects[] = 'https://*.google-analytics.com';
            $connects[] = 'https://*.analytics.google.com';
            $connects[] = 'https://connect.facebook.net';
            $connects[] = 'https://www.facebook.com';
            // Ads bestaetigt Abschluesse ueber einen unsichtbaren Rahmen.
            $frames[] = 'https://td.doubleclick.net';
            $frames[] = 'https://www.googletagmanager.com';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            // Skripte im Regelfall nur als Datei. Die zwei eingebetteten
            // Bloecke stehen in nonce() namentlich; beide tragen die
            // Einmalkennung, sonst laufen sie nicht.
            'script-src ' . implode(' ', $scripts),
            // Die Themen bringen ihre Farben als Stilblock mit; die gehen
            // vorher durch Themes::safeCss().
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            'connect-src ' . implode(' ', $connects),
            'frame-src ' . implode(' ', $frames),
            "media-src 'self' https:",
        ];

        return implode('; ', $directives);
    }

    /** Ist ueberhaupt eine Messkennung hinterlegt? */
    private static function tracks(): bool
    {
        try {
            $tracking = Integrations::publicTracking();
        } catch (\Throwable $e) {
            // Ohne Datenbank gibt es auch nichts zu messen.
            return false;
        }

        return ($tracking['gaId'] ?? '') !== ''
            || ($tracking['gtmId'] ?? '') !== ''
            || ($tracking['adsId'] ?? '') !== ''
            || ($tracking['metaPixelId'] ?? '') !== '';
    }
}
