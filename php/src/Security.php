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
     * Passwörter, Gutscheincodes und Verwaltungsschlüssel.
     *
     * Gezählt wird in der Datenbank, nicht in der Sitzung. Das ist der ganze
     * Punkt: eine Bremse in der Sitzung löst sich, sobald jemand das Cookie
     * wegwirft – und genau das tut ein Skript, das Passwörter durchprobiert,
     * ohne es zu merken.
     *
     * Von der Absenderadresse wird nur ein Streuwert gespeichert. Für das
     * Zählen reicht er, und in der Datenbank steht dann keine IP-Adresse.
     */
    public static function throttle(string $key, int $limit, int $window): bool
    {
        $bucket = mb_substr($key . '|' . self::client(), 0, 190);

        try {
            // Ein Schritt: anlegen oder hochzählen. Ist das Fenster
            // abgelaufen, beginnt es von vorn.
            Db::run(
                'INSERT INTO throttle (bucket, hits, until) VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE
                   hits  = IF(until < NOW(), 1, hits + 1),
                   until = IF(until < NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), until)',
                [$bucket, $window, $window]
            );

            $row = Db::one('SELECT hits FROM throttle WHERE bucket = ?', [$bucket]);
            $hits = (int) ($row['hits'] ?? 0);

            // Abgelaufene Zeilen gelegentlich wegräumen, damit die Tabelle
            // nicht endlos wächst.
            if ($hits === 1 && random_int(1, 50) === 1) {
                Db::run('DELETE FROM throttle WHERE until < DATE_SUB(NOW(), INTERVAL 1 DAY)');
            }

            return $hits > $limit;
        } catch (\Throwable $e) {
            // Fehlt die Tabelle (ältere Installation), soll die Anmeldung
            // nicht unmöglich werden – dann greift wenigstens die Sitzung.
            error_log('[throttle] ' . $e->getMessage());
            return self::sessionThrottle($key, $limit, $window);
        }
    }

    /** Zweite Reihe: zählt innerhalb einer Sitzung, falls die Tabelle fehlt. */
    private static function sessionThrottle(string $key, int $limit, int $window): bool
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

    /**
     * Kennung des Absenders – gestreut, damit keine IP-Adresse gespeichert wird.
     *
     * Bewusst nur REMOTE_ADDR: X-Forwarded-For darf jeder in seine Anfrage
     * schreiben, und eine Bremse, die sich per Kopfzeile umgehen lässt, ist
     * keine.
     */
    private static function client(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return substr(hash('sha256', $ip . '|' . Config::str('admin_key', 'salz')), 0, 32);
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
