<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Zugangsdaten fremder Dienste – im Adminbereich pflegbar, ohne dass jemand
 * an die Dateien auf dem Webspace muss.
 *
 * Eigene Tabelle, nicht in site_content: die Inhalte liest jede öffentliche
 * Seite, Geheimnisse haben dort nichts zu suchen. Ins Browser-Bundle geht
 * ausschließlich, was `publicTracking()` zurückgibt.
 *
 * Rangfolge: Was im Admin steht, gewinnt. Ist das Feld leer, greift der Wert
 * aus config.php – so lässt sich ein Schlüssel auch außerhalb der Datenbank
 * setzen, etwa während eines Umzugs.
 */
final class Integrations
{
    /** @var array<string,mixed>|null */
    private static ?array $cache = null;

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'paypal' => [
                'clientId'     => '',
                'clientSecret' => '',
                'mode'         => 'sandbox',
            ],
            'google' => [
                'gaId'          => '',
                'gtmId'         => '',
                'adsId'         => '',
                'adsLabels'     => ['contact' => '', 'invite' => '', 'phone' => ''],
                'leadValue'     => '',
                'currency'      => 'EUR',
                'searchConsole' => '',
                'bing'          => '',
                'consentMode'   => true,
                // Nur fuer den Adminbereich: Orte suchen und Karte zeigen.
                // Geht nie an den Browser.
                'mapsKey'       => '',
            ],
            'meta'      => ['pixelId' => ''],
            'admin'     => ['passwordHash' => ''],
            'extras'    => [],
            'updatedAt' => '',
        ];
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stored = Db::json('SELECT data FROM integrations WHERE id = 1') ?? [];
        $defaults = self::defaults();

        // Fehlende Felder älterer Stände auffüllen, damit ein neues Feld die
        // Oberfläche nicht mit Warnungen überzieht.
        $merged = $defaults;
        foreach (['paypal', 'google', 'meta', 'admin'] as $group) {
            if (isset($stored[$group]) && is_array($stored[$group])) {
                $merged[$group] = array_merge($defaults[$group], $stored[$group]);
            }
        }
        if (isset($stored['google']['adsLabels']) && is_array($stored['google']['adsLabels'])) {
            $merged['google']['adsLabels'] = array_merge($defaults['google']['adsLabels'], $stored['google']['adsLabels']);
        }
        $merged['extras'] = is_array($stored['extras'] ?? null) ? array_values($stored['extras']) : [];
        $merged['updatedAt'] = (string) ($stored['updatedAt'] ?? '');

        self::$cache = $merged;
        return $merged;
    }

    /** @param array<string,mixed> $settings */
    public static function save(array $settings): void
    {
        $settings['updatedAt'] = date('c');
        Db::run(
            'INSERT INTO integrations (id, data) VALUES (1, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
            [Db::encode($settings)]
        );
        self::$cache = $settings;
    }

    /** Admin-Wert, sonst config.php, sonst leer. */
    private static function pick(string $value, string $configKey): string
    {
        $value = trim($value);
        return $value !== '' ? $value : trim(Config::str($configKey));
    }

    /* -------------------------------- PayPal -------------------------------- */

    /** @return array{clientId:string,clientSecret:string,mode:string,configured:bool} */
    public static function paypal(): array
    {
        $paypal = self::all()['paypal'];
        $clientId = self::pick((string) $paypal['clientId'], 'paypal_client_id');
        $clientSecret = self::pick((string) $paypal['clientSecret'], 'paypal_client_secret');

        $mode = ((string) $paypal['clientId'] !== '' || (string) $paypal['clientSecret'] !== '')
            ? (string) $paypal['mode']
            : (Config::str('paypal_mode') === 'live' ? 'live' : 'sandbox');

        return [
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'mode'         => $mode === 'live' ? 'live' : 'sandbox',
            'configured'   => $clientId !== '' && $clientSecret !== '',
        ];
    }

    /* ------------------------------- Messung -------------------------------- */

    /** Nur das, was der Browser braucht – niemals ein Geheimnis. @return array<string,mixed> */
    public static function publicTracking(): array
    {
        $google = self::all()['google'];
        $meta = self::all()['meta'];

        return [
            'gaId'        => self::pick((string) $google['gaId'], 'ga_id'),
            'gtmId'       => trim((string) $google['gtmId']),
            'adsId'       => trim((string) $google['adsId']),
            'adsLabels'   => [
                'contact' => trim((string) $google['adsLabels']['contact']),
                'invite'  => trim((string) $google['adsLabels']['invite']),
                'phone'   => trim((string) $google['adsLabels']['phone']),
            ],
            'leadValue'   => trim((string) $google['leadValue']),
            'currency'    => trim((string) $google['currency']) ?: 'EUR',
            'metaPixelId' => trim((string) $meta['pixelId']),
            'consentMode' => (bool) $google['consentMode'],
        ];
    }

    /** @return array{google:string,bing:string} */
    public static function verification(): array
    {
        $google = self::all()['google'];
        return [
            'google' => trim((string) $google['searchConsole']),
            'bing'   => trim((string) $google['bing']),
        ];
    }

    /**
     * Zusatzschlüssel im Code lesen: Integrations::value('BREVO_API_KEY').
     * Fällt auf den gleichnamigen Eintrag in config.php zurück.
     */
    public static function value(string $name): string
    {
        foreach (self::all()['extras'] as $extra) {
            if (strcasecmp(trim((string) ($extra['name'] ?? '')), $name) === 0) {
                $value = trim((string) ($extra['value'] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return trim(Config::str(strtolower($name)));
    }

    /* -------------------------------- Admin -------------------------------- */

    /** Panelden belirlenmiş parola hash'i. Boşsa config bootstrap devreye girer. */
    public static function adminPasswordHash(): string
    {
        $admin = self::all()['admin'] ?? [];
        return trim((string) ($admin['passwordHash'] ?? ''));
    }

    /** Yeni hash'i JSON'a yaz. `$hash` = `password_hash(..., PASSWORD_DEFAULT)` sonucu. */
    public static function saveAdminPasswordHash(string $hash): void
    {
        $settings = self::all();
        $settings['admin']['passwordHash'] = $hash;
        self::save($settings);
    }

    /** Geheimnisse im Formular nur andeuten: die letzten vier Zeichen genügen. */
    public static function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (mb_strlen($value) <= 4) {
            return '••••';
        }
        return str_repeat('•', min(16, mb_strlen($value) - 4)) . mb_substr($value, -4);
    }
}
