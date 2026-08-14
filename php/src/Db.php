<?php
declare(strict_types=1);

namespace Atelier;

use PDO;
use PDOException;

/**
 * MariaDB-Zugriff über PDO.
 *
 * Die Daten liegen als JSON-Dokument je Datensatz – wie in der Next.js-Fassung.
 * Deshalb gibt es hier nur eine Handvoll Grundoperationen; die Fachlogik sitzt
 * in den Klassen darüber (Content, Customers, Invitations …).
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::str('db_host', 'localhost'),
            (int) Config::get('db_port', 3306),
            Config::str('db_name')
        );

        try {
            self::$pdo = new PDO($dsn, Config::str('db_user'), Config::str('db_pass'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Ohne Datenbank läuft nichts – aber der Fehler gehört ins Log,
            // nicht auf die Seite eines Besuchers.
            error_log('[db] ' . $e->getMessage());
            http_response_code(500);
            exit(Config::isDev() ? 'DB: ' . $e->getMessage() : 'Die Seite ist gerade nicht erreichbar.');
        }

        return self::$pdo;
    }

    /** @param array<string|int,mixed> $params */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Erste Zeile oder null.
     *
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int,mixed> $params
     * @return list<array<string,mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * JSON-Spalte lesen und dekodieren.
     *
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function json(string $sql, array $params = []): ?array
    {
        $row = self::one($sql, $params);
        if ($row === null || !isset($row['data'])) {
            return null;
        }
        $decoded = json_decode((string) $row['data'], true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Alle JSON-Dokumente einer Abfrage.
     *
     * @param array<string|int,mixed> $params
     * @return list<array<string,mixed>>
     */
    public static function jsonList(string $sql, array $params = []): array
    {
        $out = [];
        foreach (self::all($sql, $params) as $row) {
            $decoded = json_decode((string) ($row['data'] ?? ''), true);
            if (is_array($decoded)) {
                $out[] = $decoded;
            }
        }
        return $out;
    }

    /** Kodiert so, dass Umlaute und türkische Zeichen lesbar in der Datenbank stehen. */
    public static function encode(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
