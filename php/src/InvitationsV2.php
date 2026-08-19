<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Einladungen der zweiten Fassung.
 *
 * Getrennt von Invitations, weil die alte Tabelle das alte Schema traegt und
 * unangetastet bleibt. Das Schema hier steht seit Phase 1 und aendert sich
 * nicht: was noch fehlt, kommt in data hinein - das ist JSON.
 */
final class InvitationsV2
{
    /** Dieselbe Buchstabentabelle wie die alte Fassung - nicht zwei Wahrheiten. */
    public static function slug(string $value): string
    {
        return Invitations::slug($value);
    }

    /**
     * Frei in BEIDEN Tabellen.
     *
     * Das v2 in der Adresse faellt eines Tages weg. Dann muss
     * /einladung/{slug} genau eine Einladung treffen - und eine bereits
     * verschickte Adresse laesst sich nicht umbenennen.
     */
    public static function slugAvailable(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }
        if (Db::one('SELECT slug FROM invitations_v2 WHERE slug = ?', [$slug]) !== null) {
            return false;
        }
        return Db::one('SELECT slug FROM invitations WHERE slug = ?', [$slug]) === null;
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<string,mixed> $data
     */
    public static function create(string $slug, string $designId, array $snapshot, array $data): void
    {
        Db::run(
            'INSERT INTO invitations_v2 (slug, design_id, design_snapshot, data) VALUES (?, ?, ?, ?)',
            [$slug, $designId, Db::encode($snapshot), Db::encode($data)]
        );
    }

    /**
     * @return array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string}|null
     */
    public static function find(string $slug): ?array
    {
        $row = Db::one('SELECT * FROM invitations_v2 WHERE slug = ?', [self::slug($slug)]);
        if ($row === null) {
            return null;
        }

        return [
            'slug'            => (string) $row['slug'],
            'design_id'       => (string) $row['design_id'],
            'design_snapshot' => (array) json_decode((string) $row['design_snapshot'], true),
            'data'            => (array) json_decode((string) $row['data'], true),
            'created_at'      => (string) $row['created_at'],
        ];
    }
}
