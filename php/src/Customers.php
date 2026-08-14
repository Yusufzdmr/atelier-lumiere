<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Die Kundenakte: ein Kunde = ein Auftrag = eine Galerie.
 *
 * Der Code ist zugleich der Anmeldename der Galerie, das Passwort steht in
 * beiden Datensätzen – so bleibt die bestehende Galerie-Anmeldung unverändert
 * und der Adminbereich hat trotzdem eine Stelle, an der alles zusammenläuft:
 * Zugang, Auftragsdaten und der Gutschein für die digitale Einladung.
 */
final class Customers
{
    public static function code(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return array_map(
            [self::class, 'complete'],
            Db::jsonList('SELECT data FROM customers ORDER BY created_at DESC')
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(string $value): ?array
    {
        $row = Db::json('SELECT data FROM customers WHERE code = ?', [self::code($value)]);
        return $row === null ? null : self::complete($row);
    }

    /**
     * Ältere Datensätze robust machen: fehlende Felder auffüllen, damit die
     * Vorlagen nicht auf jeden Schlüssel prüfen müssen.
     *
     * @param array<string,mixed> $customer
     * @return array<string,mixed>
     */
    public static function complete(array $customer): array
    {
        $coupon = is_array($customer['coupon'] ?? null) ? $customer['coupon'] : [];

        return array_merge([
            'code'        => '',
            'password'    => '',
            'couple'      => '',
            'email'       => '',
            'phone'       => '',
            'date'        => '',
            'venue'       => '',
            'packageName' => '',
            'amount'      => '',
            'notes'       => '',
            'createdAt'   => '',
        ], $customer, [
            'status' => ($customer['status'] ?? 'active') === 'archived' ? 'archived' : 'active',
            'coupon' => [
                'code'    => (string) ($coupon['code'] ?? ''),
                'active'  => (bool) ($coupon['active'] ?? false),
                'once'    => (bool) ($coupon['once'] ?? true),
                'expires' => (string) ($coupon['expires'] ?? ''),
                'usedFor' => array_values(array_filter((array) ($coupon['usedFor'] ?? []), 'is_array')),
            ],
        ]);
    }

    /** @param array<string,mixed> $customer @return array<string,mixed> */
    public static function save(array $customer): array
    {
        $next = self::complete(array_merge($customer, ['code' => self::code((string) ($customer['code'] ?? ''))]));

        Db::run(
            'INSERT INTO customers (code, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [$next['code'], Db::encode($next)]
        );

        return $next;
    }

    /**
     * Einzelne Felder ändern. Passwort und Anzeigename gehören auch der
     * Galerie – wer sie hier ändert, ändert sie dort mit, sonst kommt das Paar
     * am nächsten Tag nicht mehr in seine Bilder.
     *
     * @param array<string,mixed> $patch
     * @return array<string,mixed>|null
     */
    public static function update(string $value, array $patch): ?array
    {
        $current = self::find($value);
        if ($current === null) {
            return null;
        }

        $next = self::save(array_merge($current, $patch, ['code' => $current['code']]));

        $galleryPatch = [];
        if (isset($patch['password']) && $patch['password'] !== $current['password']) {
            $galleryPatch['password'] = $next['password'];
        }
        if (isset($patch['couple']) && $patch['couple'] !== $current['couple']) {
            $galleryPatch['couple'] = $next['couple'];
        }
        if ($galleryPatch !== []) {
            Galleries::update($next['code'], $galleryPatch);
        }

        return $next;
    }

    /**
     * Kunde samt Galerie anlegen. Beides zusammen, weil ein Auftrag ohne
     * Galerie im Betrieb nie vorkommt.
     *
     * @param array<string,mixed> $input
     * @return array{ok:bool,code?:string,reason?:string}
     */
    public static function create(array $input): array
    {
        $couple = Security::clean($input['couple'] ?? '', 120);
        if ($couple === '') {
            return ['ok' => false, 'reason' => 'couple'];
        }

        $code = self::code(Security::clean($input['code'] ?? '', 64));
        if ($code === '') {
            $code = Invitations::slug($couple);
        }
        $code = Invitations::slug($code);
        if ($code === '') {
            return ['ok' => false, 'reason' => 'couple'];
        }

        // Ein vergebener Anmeldename würde die fremde Galerie überschreiben.
        if (self::find($code) !== null || Galleries::find($code) !== null) {
            return ['ok' => false, 'reason' => 'code'];
        }

        $password = Security::clean($input['password'] ?? '', 64);
        if ($password === '') {
            $password = self::randomPassword();
        }

        $coupon = mb_strtoupper(Security::clean($input['coupon'] ?? '', 40));
        if ($coupon === '') {
            $coupon = self::randomCoupon();
        }

        $date = self::date($input['date'] ?? '');
        $expires = self::date($input['expires'] ?? '');
        if ($expires === '' && $date !== '') {
            // Zwei Jahre Zugriff auf die eigenen Bilder – so lange lohnt sich
            // der Speicherplatz und so lange fragt niemand mehr nach.
            $expires = date('Y-m-d', strtotime($date . ' +2 years'));
        }

        $customer = self::save([
            'code'        => $code,
            'password'    => $password,
            'couple'      => $couple,
            'email'       => Security::clean($input['email'] ?? '', 160),
            'phone'       => Security::clean($input['phone'] ?? '', 60),
            'date'        => $date,
            'venue'       => Security::clean($input['venue'] ?? '', 160),
            'packageName' => Security::clean($input['packageName'] ?? '', 120),
            'amount'      => Security::clean($input['amount'] ?? '', 40),
            'notes'       => Security::clean($input['notes'] ?? '', 2000),
            'status'      => 'active',
            'createdAt'   => date('c'),
            'coupon'      => [
                'code'    => $coupon,
                'active'  => true,
                'once'    => !empty($input['couponOnce']),
                'expires' => self::date($input['couponExpires'] ?? ''),
                'usedFor' => [],
            ],
        ]);

        Galleries::save([
            'code'     => $code,
            'password' => $password,
            'couple'   => $couple,
            'date'     => $date,
            'venue'    => $customer['venue'],
            'expires'  => $expires,
            'cover'    => 'gal-' . $code . '-cover',
            'seeds'    => [],
            'uploads'  => [],
        ]);

        return ['ok' => true, 'code' => $code];
    }

    /**
     * Endgültig entfernen. Die Galerie und die hochgeladenen Bilder gehen
     * mit – das ist im Adminbereich bewusst ein eigener, bestätigter Knopf.
     */
    public static function delete(string $value): void
    {
        $code = self::code($value);
        Galleries::delete($code);
        Db::run('DELETE FROM customers WHERE code = ?', [$code]);
    }

    /* ------------------------------- Gutschein ------------------------------ */

    /** @param array<string,mixed> $patch */
    public static function saveCoupon(string $value, array $patch): void
    {
        $customer = self::find($value);
        if ($customer === null) {
            return;
        }
        self::update($customer['code'], ['coupon' => array_merge($customer['coupon'], $patch)]);
    }

    /** Wieder freigeben: der Code gilt erneut, die Historie wird gelöscht. */
    public static function resetCoupon(string $value): void
    {
        self::saveCoupon($value, ['usedFor' => [], 'active' => true]);
    }

    public static function randomCoupon(): string
    {
        // Ohne 0/O und 1/I – der Code wird am Telefon durchgegeben.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    public static function randomPassword(): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    /* --------------------------------- Helfer ------------------------------- */

    /** Datum aus einem Formular: entweder JJJJ-MM-TT oder leer. */
    public static function date(mixed $value): string
    {
        $date = Security::clean($value, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';
    }
}
