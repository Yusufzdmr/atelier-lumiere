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
 * `onclick=`-Aufsatz im HTML, alle Skripte liegen als Datei vor, und die
 * einzige eingebettete Fremdquelle sind die Videodienste.
 */
final class Http
{
    private static string $nonce = '';

    /** Einmalkennung dieser Antwort – nur der JSON-LD-Block trägt sie. */
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

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            // Skripte nur als Datei – der einzige Block im HTML ist der
            // Datenblock für Suchmaschinen, und der trägt die Einmalkennung.
            "script-src 'self' 'nonce-" . self::nonce() . "'",
            // Die Themen bringen ihre Farben als Stilblock mit; die gehen
            // vorher durch Themes::safeCss().
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            'frame-src ' . implode(' ', $frames),
            "media-src 'self' https:",
        ];

        return implode('; ', $directives);
    }
}
