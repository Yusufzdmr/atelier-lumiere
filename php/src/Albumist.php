<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Anmeldung des Albumherstellers.
 *
 * Ein gemeinsamer Zugang aus config.php, kein eigenes Benutzerkonto pro
 * Firma – genau wie beim Adminbereich (Admin::login). Eigene Sitzung,
 * eigenes Sitzungsfeld: ein Admin-Login öffnet hier nichts, und umgekehrt.
 */
final class Albumist
{
    /** Nach so langer Untaetigkeit ist Schluss. */
    private const IDLE = 4 * 3600;

    /** Und spaetestens dann in jedem Fall, auch bei Betrieb. */
    private const LIFETIME = 12 * 3600;

    public static function isLoggedIn(): bool
    {
        Security::session();
        if (empty($_SESSION['albumist'])) {
            return false;
        }

        $now = time();
        $seen = (int) ($_SESSION['albumistSeen'] ?? 0);
        $since = (int) ($_SESSION['albumistSince'] ?? 0);

        if (($now - $seen) > self::IDLE || ($now - $since) > self::LIFETIME) {
            self::logout();
            return false;
        }

        $_SESSION['albumistSeen'] = $now;
        return true;
    }

    /** @return bool true bei erfolgreicher Anmeldung */
    public static function login(string $user, string $password): bool
    {
        if (Security::throttle('albumist-login', 8, 900)) {
            return false;
        }

        $expectedUser = Config::str('albumist_user', '');
        $expectedKey = Config::str('albumist_key', '');
        $user = trim($user);
        $password = trim($password);

        if ($expectedUser === '' || $expectedKey === '' || $user === '' || $password === '') {
            return false;
        }

        if (!hash_equals($expectedUser, $user)) {
            return false;
        }

        // Steht in der config.php ein Hash (password_hash), wird er geprueft;
        // sonst der Klartext, zeitkonstant – gleiche Regel wie Admin::login.
        $ok = str_starts_with($expectedKey, '$2y$') || str_starts_with($expectedKey, '$argon2')
            ? password_verify($password, $expectedKey)
            : hash_equals($expectedKey, $password);

        if (!$ok) {
            return false;
        }

        Security::session();
        session_regenerate_id(true);
        $_SESSION['albumist'] = true;
        $_SESSION['albumistSince'] = time();
        $_SESSION['albumistSeen'] = time();

        return true;
    }

    public static function logout(): void
    {
        Security::session();
        unset($_SESSION['albumist'], $_SESSION['albumistSince'], $_SESSION['albumistSeen']);
        session_regenerate_id(true);
    }

    /**
     * Wird von AlbumistController als Erstes aufgerufen. Ohne Anmeldung wird
     * das Anmeldeformular gezeigt und die Ausführung beendet.
     */
    public static function requireLogin(string $locale): void
    {
        if (self::isLoggedIn()) {
            return;
        }

        $error = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['password'])) {
            if (Security::checkCsrf($_POST['csrf'] ?? null) && self::login((string) ($_POST['user'] ?? ''), (string) $_POST['password'])) {
                $back = (string) ($_SERVER['REQUEST_URI'] ?? '');
                if ($back === '' || !str_starts_with($back, '/') || str_starts_with($back, '//')) {
                    $back = I18n::path('/albumcu', $locale);
                }
                header('Location: ' . $back, true, 303);
                exit;
            }
            $error = true;
        }

        View::page('pages/albumist-login', [
            'locale' => $locale,
            'path'   => I18n::path('/albumcu', $locale),
            'meta'   => ['title' => 'Albümcü', 'noindex' => true, 'bare' => true],
            'error'  => $error,
            'csrf'   => Security::csrf(),
        ]);
        exit;
    }
}
