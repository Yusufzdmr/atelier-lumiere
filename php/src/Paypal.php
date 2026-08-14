<?php
declare(strict_types=1);

namespace Atelier;

/**
 * PayPal-Anbindung (Orders v2) über cURL.
 *
 * Zugangsdaten kommen aus dem Adminbereich („Integrationen“). Bezahlt wird
 * per Weiterleitung, nicht über ein eingebettetes SDK – so kommt ohne
 * Einwilligung kein fremdes Skript in die Seite.
 *
 * Der Betrag wird immer hier berechnet. Was der Browser meldet, entscheidet
 * über keinen Preis.
 */
final class Paypal
{
    private static function host(string $mode): string
    {
        return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    public static function isConfigured(): bool
    {
        return Integrations::paypal()['configured'];
    }

    /**
     * @param array<string,mixed> $body
     * @return array{status:int,json:array<string,mixed>}
     */
    private static function request(string $method, string $url, array $headers, ?string $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('[paypal] ' . $error);
            return ['status' => 0, 'json' => []];
        }

        $json = json_decode((string) $response, true);
        return ['status' => $status, 'json' => is_array($json) ? $json : []];
    }

    /** @return array{token:string,host:string,mode:string}|null */
    private static function auth(): ?array
    {
        $cfg = Integrations::paypal();
        if (!$cfg['configured']) {
            return null;
        }

        $host = self::host($cfg['mode']);
        $result = self::request(
            'POST',
            $host . '/v1/oauth2/token',
            [
                'Authorization: Basic ' . base64_encode($cfg['clientId'] . ':' . $cfg['clientSecret']),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials'
        );

        $token = (string) ($result['json']['access_token'] ?? '');
        return $token === '' ? null : ['token' => $token, 'host' => $host, 'mode' => $cfg['mode']];
    }

    /**
     * Zugangsdaten prüfen, ohne eine Zahlung auszulösen – für den Testknopf
     * im Adminbereich.
     *
     * @return array{ok:bool,mode:string,message:string}
     */
    public static function test(): array
    {
        $cfg = Integrations::paypal();
        if (!$cfg['configured']) {
            return ['ok' => false, 'mode' => $cfg['mode'], 'message' => 'missing'];
        }

        $auth = self::auth();
        return $auth === null
            ? ['ok' => false, 'mode' => $cfg['mode'], 'message' => 'rejected']
            : ['ok' => true, 'mode' => $cfg['mode'], 'message' => 'ok'];
    }

    /**
     * Bestellung anlegen.
     *
     * @return array{id:string,approveUrl:string}|null
     */
    public static function createOrder(float $amount, string $slug, string $description, string $returnUrl, string $cancelUrl): ?array
    {
        $auth = self::auth();
        if ($auth === null) {
            return null;
        }

        $result = self::request(
            'POST',
            $auth['host'] . '/v2/checkout/orders',
            ['Authorization: Bearer ' . $auth['token'], 'Content-Type: application/json'],
            (string) json_encode([
                'intent'         => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $slug,
                    'description'  => mb_substr($description, 0, 127),
                    'amount'       => ['currency_code' => 'EUR', 'value' => number_format($amount, 2, '.', '')],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name'  => 'Atelier Lumière',
                            'locale'      => 'de-DE',
                            'user_action' => 'PAY_NOW',
                            'return_url'  => $returnUrl,
                            'cancel_url'  => $cancelUrl,
                        ],
                    ],
                ],
            ])
        );

        $id = (string) ($result['json']['id'] ?? '');
        if ($id === '') {
            return null;
        }

        $approve = '';
        foreach ((array) ($result['json']['links'] ?? []) as $link) {
            if (in_array((string) ($link['rel'] ?? ''), ['payer-action', 'approve'], true)) {
                $approve = (string) ($link['href'] ?? '');
                break;
            }
        }

        return ['id' => $id, 'approveUrl' => $approve];
    }

    /** Zahlung einziehen, nachdem der Gast bei PayPal bestätigt hat. */
    public static function captureOrder(string $orderId): bool
    {
        $auth = self::auth();
        if ($auth === null) {
            return false;
        }

        $result = self::request(
            'POST',
            $auth['host'] . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
            ['Authorization: Bearer ' . $auth['token'], 'Content-Type: application/json'],
            '{}'
        );

        return ($result['json']['status'] ?? '') === 'COMPLETED';
    }
}
