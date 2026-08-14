<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Sitzung, CSRF-Schutz und einfache Ratenbegrenzung.
 *
 * Die Next.js-Fassung brauchte kein CSRF-Token, weil Server Actions das
 * mitbringen. Bei klassischen Formularen muss es explizit sein – sonst kann
 * eine fremde Seite im Namen eines angemeldeten Betreibers Formulare
 * abschicken.
 */
final class Security
{
    public static function session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !Config::isDev(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('al_session');
        session_start();
    }

    public static function csrf(): string
    {
        self::session();
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['csrf'];
    }

    public static function checkCsrf(?string $token): bool
    {
        self::session();
        $expected = $_SESSION['csrf'] ?? null;
        return is_string($expected) && is_string($token) && hash_equals($expected, $token);
    }

    /**
     * Höchstens $limit Versuche je $window Sekunden – gegen durchprobierte
     * Passwörter und Gutscheincodes.
     */
    public static function throttle(string $key, int $limit, int $window): bool
    {
        self::session();
        $now = time();
        $bucket = $_SESSION['throttle'][$key] ?? ['count' => 0, 'until' => $now + $window];

        if (($bucket['until'] ?? 0) < $now) {
            $bucket = ['count' => 0, 'until' => $now + $window];
        }

        $bucket['count']++;
        $_SESSION['throttle'][$key] = $bucket;

        return $bucket['count'] > $limit;
    }

    /** Eingaben kürzen und von Steuerzeichen befreien. */
    public static function clean(mixed $value, int $max = 200): string
    {
        $text = is_string($value) ? $value : '';
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';
        return mb_substr(trim($text), 0, $max);
    }

    /** Kopfzeilen-Einschleusung in E-Mails verhindern. */
    public static function singleLine(string $value): string
    {
        return trim(str_replace(["\n", "\r"], ' ', $value));
    }
}
