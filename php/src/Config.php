<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Konfiguration aus config.php – der Datei, die nicht ins Repository gehört.
 *
 * Absichtlich eine PHP-Datei und keine .env: auf einem Apache-Webspace kann
 * eine .env im falschen Verzeichnis ausgeliefert werden, eine PHP-Datei nie.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $values = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            http_response_code(500);
            exit('config.php fehlt. Bitte config.example.php kopieren und ausfüllen.');
        }
        /** @var array<string,mixed> $values */
        $values = require $file;
        self::$values = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $value = self::$values[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return (bool) (self::$values[$key] ?? $default);
    }

    /** Basis-URL ohne abschließenden Schrägstrich. */
    public static function url(): string
    {
        $configured = self::str('site_url');
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        // Der Host kommt aus der Anfrage und ist damit nichts, worauf man
        // bauen sollte: er landet in Einladungslinks und in E-Mails. Deshalb
        // nur Zeichen, die in einem Hostnamen vorkommen duerfen – und im
        // Betrieb gehoert site_url ohnehin in die config.php.
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '';
        if ($host === '') {
            $host = 'localhost';
        }

        return ($https ? 'https://' : 'http://') . $host;
    }

    public static function isDev(): bool
    {
        return self::bool('dev');
    }
}
