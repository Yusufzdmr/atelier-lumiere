<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Orte bei Google nachschlagen – für den Adminbereich, nicht für Besucher.
 *
 * Eine Location von Hand einzutippen heißt: Schreibfehler in der Anschrift,
 * eine Postleitzahl daneben, und der Kartenlink führt ins Nachbardorf. Hier
 * wird stattdessen gesucht, auf der Karte geprüft und übernommen.
 *
 * Der Schlüssel bleibt auf dem Server. Auch die Karte kommt über die eigene
 * Adresse herein (Controller: karte()), damit er nicht im HTML des
 * Adminbereichs steht – ein Schlüssel in einer Seite ist ein Schlüssel, der
 * über einen geteilten Bildschirm abfließt.
 *
 * Was hier NICHT passiert: Bewertungstexte dauerhaft speichern. Die
 * Nutzungsbedingungen von Google erlauben das nicht, und die Texte gehören
 * ihren Verfassern. Sie werden live geholt, im Adminbereich gezeigt und
 * wieder vergessen.
 */
final class Places
{
    private const SEARCH = 'https://places.googleapis.com/v1/places:searchText';
    private const DETAILS = 'https://places.googleapis.com/v1/places/';
    private const STATIC_MAP = 'https://maps.googleapis.com/maps/api/staticmap';

    private const TIMEOUT = 8;

    /** Felder, die die Suche zurückgeben soll – jedes Feld kostet. */
    private const SEARCH_FIELDS = 'places.id,places.displayName,places.formattedAddress,places.location,'
        . 'places.rating,places.userRatingCount,places.primaryTypeDisplayName,places.googleMapsUri';

    private const DETAIL_FIELDS = 'id,displayName,formattedAddress,location,rating,userRatingCount,'
        . 'primaryTypeDisplayName,websiteUri,internationalPhoneNumber,googleMapsUri,reviews';

    public static function key(): string
    {
        $stored = trim((string) (Integrations::all()['google']['mapsKey'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        // Wer den Schlüssel lieber in der freien Liste führt, kann das auch.
        $extra = Integrations::value('GOOGLE_MAPS_KEY');
        return $extra !== '' ? $extra : trim(Config::str('maps_key'));
    }

    public static function configured(): bool
    {
        return self::key() !== '';
    }

    /* -------------------------------- Suchen -------------------------------- */

    /**
     * Orte zu einem Suchtext. Der Ort der Stadt hilft der Trefferqualität:
     * „Schloss“ gibt es oft, „Schloss in Krumbach“ meist einmal.
     *
     * @return array{ok:bool,error:string,places:list<array<string,mixed>>}
     */
    public static function search(string $query, string $near = ''): array
    {
        $query = trim($query . ($near !== '' ? ' ' . $near : ''));

        if ($query === '') {
            return ['ok' => false, 'error' => 'leer', 'places' => []];
        }
        if (!self::configured()) {
            return ['ok' => false, 'error' => 'kein-schluessel', 'places' => []];
        }
        // Ein Suchfeld, das jemand gedrückt hält, kostet Geld.
        if (Security::throttle('places-suche', 40, 600)) {
            return ['ok' => false, 'error' => 'zu-oft', 'places' => []];
        }

        $answer = self::post(self::SEARCH, [
            'textQuery'    => $query,
            'languageCode' => I18n::locale() === 'tr' ? 'tr' : 'de',
            'regionCode'   => 'DE',
            'maxResultCount' => 6,
        ], self::SEARCH_FIELDS);

        if ($answer === null) {
            return ['ok' => false, 'error' => 'nicht-erreichbar', 'places' => []];
        }
        if (isset($answer['error'])) {
            error_log('[places] ' . json_encode($answer['error']));
            return ['ok' => false, 'error' => 'abgelehnt', 'places' => []];
        }

        $places = [];
        foreach ((array) ($answer['places'] ?? []) as $place) {
            $places[] = self::shape(is_array($place) ? $place : []);
        }

        return ['ok' => true, 'error' => $places === [] ? 'nichts-gefunden' : '', 'places' => $places];
    }

    /**
     * Ein Ort mit allem, was für die Location-Seite brauchbar ist – samt der
     * (höchstens fünf) Bewertungen, die Google mitgibt.
     *
     * @return array<string,mixed>|null
     */
    public static function details(string $placeId): ?array
    {
        $placeId = preg_replace('/[^A-Za-z0-9_\-]/', '', $placeId) ?? '';
        if ($placeId === '' || !self::configured()) {
            return null;
        }

        $answer = self::get(self::DETAILS . $placeId, self::DETAIL_FIELDS);
        if ($answer === null || isset($answer['error'])) {
            if (is_array($answer)) {
                error_log('[places] ' . json_encode($answer['error']));
            }
            return null;
        }

        $place = self::shape($answer);
        $place['website'] = (string) ($answer['websiteUri'] ?? '');
        $place['phone'] = (string) ($answer['internationalPhoneNumber'] ?? '');
        $place['reviews'] = self::reviews($answer);

        return $place;
    }

    /**
     * Die Bewertungen, aufsteigend nach Brauchbarkeit gefiltert.
     *
     * Gesucht sind Sätze, aus denen jemand etwas über die Location lernt –
     * nicht „Top!“ mit fünf Sternen. Deshalb: mindestens vier Sterne und ein
     * Text, der mehr als eine Floskel ist.
     *
     * @param array<string,mixed> $answer
     * @return list<array<string,mixed>>
     */
    private static function reviews(array $answer): array
    {
        $reviews = [];

        foreach ((array) ($answer['reviews'] ?? []) as $review) {
            if (!is_array($review)) {
                continue;
            }

            $text = trim((string) ($review['text']['text'] ?? ($review['originalText']['text'] ?? '')));
            $rating = (int) ($review['rating'] ?? 0);

            if ($rating < 4 || mb_strlen($text) < 80) {
                continue;
            }

            $reviews[] = [
                'text'   => $text,
                'rating' => $rating,
                'author' => (string) ($review['authorAttribution']['displayName'] ?? ''),
                'link'   => (string) ($review['authorAttribution']['uri'] ?? ''),
                'when'   => (string) ($review['relativePublishTimeDescription'] ?? ''),
            ];
        }

        // Die längeren zuerst: sie sagen in der Regel mehr über den Ort.
        usort($reviews, static fn (array $a, array $b): int => mb_strlen($b['text']) <=> mb_strlen($a['text']));

        return $reviews;
    }

    /* --------------------------------- Karte -------------------------------- */

    /** Kartenbild als Rohdaten – der Controller reicht es weiter. */
    public static function staticMap(float $lat, float $lng, int $width = 640, int $height = 320): ?string
    {
        if (!self::configured()) {
            return null;
        }

        $url = self::STATIC_MAP . '?' . http_build_query([
            'center'  => $lat . ',' . $lng,
            'zoom'    => 16,
            'size'    => $width . 'x' . $height,
            'scale'   => 2,
            'maptype' => 'roadmap',
            'markers' => 'color:0xB08D57|' . $lat . ',' . $lng,
            'key'     => self::key(),
        ]);

        $image = self::fetch($url, []);
        return $image === null || $image === '' ? null : $image;
    }

    /* -------------------------------- Technik ------------------------------- */

    /** @param array<string,mixed> $place @return array<string,mixed> */
    private static function shape(array $place): array
    {
        return [
            'placeId' => (string) ($place['id'] ?? ''),
            'name'    => (string) ($place['displayName']['text'] ?? ''),
            'address' => (string) ($place['formattedAddress'] ?? ''),
            'lat'     => (float) ($place['location']['latitude'] ?? 0),
            'lng'     => (float) ($place['location']['longitude'] ?? 0),
            'rating'  => (float) ($place['rating'] ?? 0),
            'votes'   => (int) ($place['userRatingCount'] ?? 0),
            'kind'    => (string) ($place['primaryTypeDisplayName']['text'] ?? ''),
            'mapsUrl' => (string) ($place['googleMapsUri'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $body @return array<string,mixed>|null */
    private static function post(string $url, array $body, string $fields): ?array
    {
        $raw = self::fetch($url, [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . self::key(),
            'X-Goog-FieldMask: ' . $fields,
        ], Db::encode($body));

        if ($raw === null) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<string,mixed>|null */
    private static function get(string $url, string $fields): ?array
    {
        $raw = self::fetch($url, [
            'X-Goog-Api-Key: ' . self::key(),
            'X-Goog-FieldMask: ' . $fields,
        ]);

        if ($raw === null) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /** @param list<string> $headers */
    private static function fetch(string $url, array $headers, ?string $body = null): ?string
    {
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $answer = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($answer === false || $error !== '') {
            error_log('[places] ' . ($error !== '' ? $error : 'keine Antwort'));
            return null;
        }
        if ($status >= 400) {
            error_log('[places] HTTP ' . $status . ' ' . mb_substr((string) $answer, 0, 300));
            // Der Fehlertext von Google ist JSON und wird oben ausgewertet.
            return is_string($answer) ? $answer : null;
        }

        return (string) $answer;
    }
}
