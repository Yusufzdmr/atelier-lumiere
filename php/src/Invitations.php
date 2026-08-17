<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Digitale Einladungen: anlegen, lesen, Zusagen, Entwürfe und Gutscheine.
 *
 * Der Gutschein ist der Punkt, an dem Geld hängt: geprüft wird ausschließlich
 * hier, und verbraucht wird er erst, wenn die Einladung wirklich angelegt ist.
 */
final class Invitations
{
    public const EVENT_TYPES = ['wedding', 'multi', 'henna', 'engagement', 'circumcision', 'birthday', 'corporate'];

    /* ------------------------------ Einladungen ----------------------------- */

    /** @return array<string,mixed>|null */
    public static function find(string $slug): ?array
    {
        return Db::json('SELECT data FROM invitations WHERE slug = ?', [self::slug($slug)]);
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Db::jsonList('SELECT data FROM invitations ORDER BY created_at DESC');
    }

    /** @param array<string,mixed> $invitation */
    public static function create(array $invitation): void
    {
        Db::run(
            'INSERT INTO invitations (slug, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [(string) $invitation['slug'], Db::encode($invitation)]
        );
    }

    /** @param array<string,mixed> $patch */
    public static function update(string $slug, array $patch): void
    {
        $invitation = self::find($slug);
        if ($invitation === null) {
            return;
        }
        Db::run('UPDATE invitations SET data = ? WHERE slug = ?', [Db::encode(array_merge($invitation, $patch)), self::slug($slug)]);
    }

    public static function delete(string $slug): void
    {
        $slug = self::slug($slug);
        $invitation = self::find($slug);

        foreach ((array) ($invitation['photos'] ?? []) as $url) {
            Media::delete((string) $url);
        }
        if (($invitation['ogImage'] ?? '') !== '') {
            Media::delete((string) $invitation['ogImage']);
        }
        OgImage::forget($slug);

        Db::run('DELETE FROM invitations WHERE slug = ?', [$slug]);
        Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);
        Guests::deleteAll($slug);
    }

    public static function slugAvailable(string $slug): bool
    {
        return self::find($slug) === null;
    }

    /** Adresstauglicher Name: Kleinbuchstaben, Ziffern, Bindestrich. */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'ı' => 'i', 'İ' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'â' => 'a'];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    /**
     * Wie der Anlass heißt. Steht in der WhatsApp-Vorschau hinter den Namen –
     * „Ayşe & Mehmet – Hochzeitseinladung“ liest sich anders als ein nackter
     * Link.
     */
    public static function kindLabel(string $eventType, string $locale): string
    {
        $labels = [
            'wedding'      => ['de' => 'Hochzeitseinladung', 'en' => 'Wedding Invitation'],
            'multi'        => ['de' => 'Einladung', 'en' => 'Invitation'],
            'henna'        => ['de' => 'Einladung zum Henna-Abend', 'en' => 'Henna Night Invitation'],
            'engagement'   => ['de' => 'Verlobungseinladung', 'en' => 'Engagement Invitation'],
            'circumcision' => ['de' => 'Einladung zum Beschneidungsfest', 'en' => 'Circumcision Celebration Invitation'],
            'birthday'     => ['de' => 'Geburtstagseinladung', 'en' => 'Birthday Invitation'],
            'corporate'    => ['de' => 'Einladung', 'en' => 'Invitation'],
        ];

        $label = $labels[$eventType] ?? $labels['wedding'];

        return $label[$locale] ?? $label['de'];
    }

    /* -------------------------------- Thema --------------------------------- */

    /**
     * Das Thema, mit dem diese Einladung gestaltet wurde.
     *
     * Entscheidend ist die Reihenfolge: zuerst die Kopie, die beim Erstellen
     * mitgeschrieben wurde, und erst wenn es keine gibt das heutige Thema.
     *
     * Der Grund steht in jedem Postfach: Eine Einladung ist verschickt. Wer
     * sie oeffnet, soll die Karte sehen, die das Paar herumgeschickt hat –
     * nicht die, die dabei herauskaeme, wenn der Betrieb heute an den Farben
     * dreht. Ein Thema zu aendern darf nie eine fremde Feier umgestalten.
     *
     * @param array<string,mixed> $invitation
     * @return array<string,mixed>
     */
    public static function theme(array $invitation): array
    {
        $snapshot = $invitation['themeSnapshot'] ?? null;
        if (is_array($snapshot) && ($snapshot['id'] ?? '') !== '') {
            return Themes::complete($snapshot);
        }

        $live = Themes::find((string) ($invitation['theme'] ?? ''));
        return Themes::complete($live ?? (Themes::all()[0] ?? []));
    }

    /** Haengt diese Einladung an einer aelteren Fassung ihres Themas? */
    public static function themeOutdated(array $invitation): bool
    {
        $snapshot = $invitation['themeSnapshot'] ?? null;
        if (!is_array($snapshot)) {
            return false;
        }

        $live = Themes::find((string) ($snapshot['id'] ?? ''));
        return $live !== null && (int) $live['version'] > (int) ($snapshot['version'] ?? 1);
    }

    /** Eine Einladung auf den heutigen Stand ihres Themas heben. */
    public static function refreshTheme(string $slug): void
    {
        $invitation = self::find($slug);
        if ($invitation === null) {
            return;
        }

        $live = Themes::find((string) ($invitation['theme'] ?? ''));
        if ($live !== null) {
            self::update($slug, ['themeSnapshot' => $live]);
        }
    }

    /* ---------------------------- Verwaltungslink --------------------------- */

    /**
     * Das Paar hat kein Konto – es soll auch keins anlegen müssen. Statt einer
     * Anmeldung bekommt es beim Erstellen einen geheimen Link, unter dem es
     * später Gäste nachtragen kann. Ältere Einladungen bekommen den
     * Schlüssel beim ersten Aufruf.
     *
     * @param array<string,mixed> $invitation
     */
    public static function manageKey(array $invitation): string
    {
        $key = (string) ($invitation['manageKey'] ?? '');
        if ($key !== '') {
            return $key;
        }

        $key = bin2hex(random_bytes(16));
        self::update((string) ($invitation['slug'] ?? ''), ['manageKey' => $key]);

        return $key;
    }

    /** @param array<string,mixed> $invitation */
    public static function checkManageKey(array $invitation, string $key): bool
    {
        $expected = (string) ($invitation['manageKey'] ?? '');
        return $expected !== '' && $key !== '' && hash_equals($expected, $key);
    }

    /** @param array<string,mixed> $invitation */
    public static function manageUrl(array $invitation, ?string $locale = null): string
    {
        return Config::url()
            . I18n::sitePath('/einladung/' . (string) ($invitation['slug'] ?? '') . '/verwalten', $locale)
            . '?schluessel=' . self::manageKey($invitation);
    }

    /* --------------------------------- RSVP --------------------------------- */

    /** @param array<string,mixed> $rsvp */
    public static function addRsvp(string $slug, array $rsvp): void
    {
        Db::run('INSERT INTO rsvps (slug, data) VALUES (?, ?)', [self::slug($slug), Db::encode($rsvp)]);
    }

    /** @return list<array<string,mixed>> */
    public static function rsvps(string $slug = ''): array
    {
        if ($slug === '') {
            return Db::jsonList('SELECT data FROM rsvps ORDER BY at DESC');
        }
        return Db::jsonList('SELECT data FROM rsvps WHERE slug = ? ORDER BY at DESC', [self::slug($slug)]);
    }

    /* ------------------------------- Entwürfe ------------------------------- */

    public static function saveDraft(string $token, string $label, mixed $data): void
    {
        $draft = ['token' => $token, 'label' => $label, 'data' => $data, 'updatedAt' => date('c')];
        Db::run(
            'INSERT INTO invite_drafts (token, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = CURRENT_TIMESTAMP',
            [$token, Db::encode($draft)]
        );
        // Liegengelassene Entwürfe räumen, sonst wächst die Tabelle endlos.
        Db::run('DELETE FROM invite_drafts WHERE updated_at < (NOW() - INTERVAL 120 DAY)');
    }

    /** @return array<string,mixed>|null */
    public static function draft(string $token): ?array
    {
        return Db::json('SELECT data FROM invite_drafts WHERE token = ?', [$token]);
    }

    /** @return list<array<string,mixed>> */
    public static function drafts(): array
    {
        return Db::jsonList('SELECT data FROM invite_drafts ORDER BY updated_at DESC LIMIT 200');
    }

    public static function deleteDraft(string $token): void
    {
        Db::run('DELETE FROM invite_drafts WHERE token = ?', [$token]);
    }

    /* ------------------------------ Gutscheine ------------------------------ */

    /**
     * Code prüfen. Die allgemeine Aktion kommt aus den Inhalten, die
     * persönlichen Codes aus der Kundenakte.
     *
     * @return array{ok:bool,reason?:string,kind?:string,customer?:string}
     */
    public static function checkCoupon(string $input): array
    {
        $value = mb_strtolower(trim($input));
        if ($value === '') {
            return ['ok' => false, 'reason' => 'empty'];
        }

        $campaign = Content::get('campaign');
        if (!empty($campaign['active']) && mb_strtolower(trim((string) ($campaign['code'] ?? ''))) === $value) {
            return ['ok' => true, 'kind' => 'campaign'];
        }

        $customer = self::customerByCoupon($value);
        if ($customer === null) {
            return ['ok' => false, 'reason' => 'unknown'];
        }

        $coupon = (array) ($customer['coupon'] ?? []);

        if (($customer['status'] ?? 'active') === 'archived') {
            return ['ok' => false, 'reason' => 'archived'];
        }
        if (empty($coupon['active'])) {
            return ['ok' => false, 'reason' => 'inactive'];
        }
        if (($coupon['expires'] ?? '') !== '' && (string) $coupon['expires'] < date('Y-m-d')) {
            return ['ok' => false, 'reason' => 'expired'];
        }
        if (!empty($coupon['once']) && ((array) ($coupon['usedFor'] ?? [])) !== []) {
            return ['ok' => false, 'reason' => 'used'];
        }

        return ['ok' => true, 'kind' => 'customer', 'customer' => (string) $customer['code']];
    }

    /** Code als eingelöst vermerken – erst nach dem Anlegen der Einladung. */
    public static function redeemCoupon(string $input, string $slug): void
    {
        $customer = self::customerByCoupon(mb_strtolower(trim($input)));
        if ($customer === null) {
            return;
        }

        $coupon = (array) ($customer['coupon'] ?? []);
        $used = (array) ($coupon['usedFor'] ?? []);
        $used[] = ['slug' => $slug, 'at' => date('c')];
        $coupon['usedFor'] = array_slice($used, -20);
        $customer['coupon'] = $coupon;

        Db::run('UPDATE customers SET data = ? WHERE code = ?', [Db::encode($customer), (string) $customer['code']]);
    }

    /** @return array<string,mixed>|null */
    private static function customerByCoupon(string $value): ?array
    {
        foreach (Db::jsonList('SELECT data FROM customers') as $customer) {
            $code = mb_strtolower(trim((string) ($customer['coupon']['code'] ?? '')));
            if ($code !== '' && $code === $value) {
                return $customer;
            }
        }
        return null;
    }
}
