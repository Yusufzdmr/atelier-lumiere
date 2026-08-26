<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Ein Kartenbild zu einer Adresse - fertig gerendert, von unserem Server.
 *
 * Warum ein Bild und keine eingebettete Karte: eine Google- oder Leaflet-Karte
 * im iframe schickt den Browser des Gastes zu einem Fremden, und zwar in dem
 * Moment, in dem die Einladung aufgeht - vor jeder Einwilligung. Die ganze
 * Seite ist darauf gebaut, dass ohne Zustimmung NULL Anfragen nach draussen
 * gehen (siehe templates/partials/consent.php); eine Karte waere das erste
 * Leck gewesen, und ausgerechnet auf der Seite, die am haeufigsten
 * weitergeschickt wird.
 *
 * Also holt der Server das Bild, legt es ab und liefert es selbst aus. Der
 * Gast sieht die Karte, sein Browser spricht nur mit uns, und ein Klick
 * darauf fuehrt - dann bewusst - zur Routenplanung.
 *
 * Zwei Quellen, in dieser Reihenfolge:
 *
 *   1. Google Static Maps, wenn ein maps_key in der config.php steht. Schoener
 *      und genauer, und der Schluessel ist ohnehin schon fuer die Mekân-Suche
 *      im Panel da.
 *   2. OpenStreetMap, sonst. Kein Schluessel, keine Rechnung - dafuer werden
 *      die Kacheln hier selbst zusammengesetzt. Auf dem heutigen Server ist
 *      kein maps_key gesetzt, das ist also der Normalfall.
 *
 * Alles wird zwischengespeichert: eine Einladung wird hundertmal geoeffnet,
 * die Adresse aendert sich dabei nie. Ohne Cache waeren das hundert Anfragen
 * an Nominatim fuer dieselbe Strasse - deren Nutzungsregeln erlauben das zu
 * Recht nicht.
 */
final class StaticMap
{
    /**
     * Die Adresse der Beispieleinladung.
     *
     * Sie steht hier, weil zwei Stellen sie brauchen und eine dritte sie
     * pruefen muss: das Schaufenster (DesignController), die Vorschau im
     * Assistenten (InviteV2Controller) und der Endpunkt, der zu genau dieser
     * einen Adresse ein Bild ausliefert. Als drei Zeichenketten in drei
     * Dateien war es bisher schon eine zu viel; mit der Pruefung waeren es
     * vier gewesen, und die vierte haette irgendwann nicht mehr gepasst -
     * dann faende die Vorschau kein Bild und niemand wuesste warum.
     */
    public const DEMO_ADDRESS = 'Schlossstraße 1, 89312 Günzburg';

    /** Sekunden, die eine einzelne Anfrage nach draussen dauern darf. */
    private const TIMEOUT = 8;

    /** Kachelgroesse bei OSM. Steht so in jedem Kachelschema. */
    private const TILE = 256;

    /**
     * Zoomstufe fuer das OSM-Bild.
     *
     * 15 zeigt den Strassenzug mit den Nachbarstrassen: nah genug, um das
     * Haus zu finden, weit genug, um zu wissen, wo in der Stadt man landet.
     * 16 waere schon fast nur noch der Hof.
     */
    private const ZOOM = 15;

    /**
     * Kachelserver.
     *
     * Die Nutzungsregeln von OpenStreetMap verlangen einen aussagekraeftigen
     * User-Agent und keine Massenabfragen. Beides ist erfuellt: ein Bild je
     * Adresse, danach aus dem Cache.
     */
    private const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

    private const NOMINATIM = 'https://nominatim.openstreetmap.org/search';

    /**
     * Wer da anfragt. Ohne diese Zeile antwortet Nominatim mit 403, und das
     * zu Recht - anonyme Skripte sind genau das Problem, das die Regel meint.
     */
    private const AGENT = 'AtelierLumiere/1.0 (Hochzeitseinladung; +https://atelier-lumiere.de)';

    /* ------------------------------- Aussen -------------------------------- */

    /**
     * Das Kartenbild zu einer Adresse. Rohdaten (PNG), oder null.
     *
     * Null heisst immer dasselbe: hier gibt es kein Bild zu zeigen. Der
     * Aufrufer druckt dann die Adresse ohne Karte - eine Einladung ohne
     * Kartenbild ist vollstaendig, eine mit kaputtem Bild nicht.
     */
    public static function forAddress(string $address, int $width = 640, int $height = 480): ?string
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        // Breite und Hoehe stehen im Schluessel: dasselbe Haus in zwei
        // Groessen sind zwei Bilder, und das zweite darf nicht das erste
        // ueberschreiben.
        $datei = self::cacheDir() . '/' . sha1($address . '|' . $width . 'x' . $height) . '.png';

        if (is_file($datei) && filesize($datei) > 0) {
            $inhalt = @file_get_contents($datei);
            if (is_string($inhalt) && $inhalt !== '') {
                return $inhalt;
            }
        }

        $bild = self::render($address, $width, $height);
        if ($bild === null) {
            return null;
        }

        // Erst schreiben, wenn etwas da ist: eine leere Datei im Cache waere
        // ein dauerhaft kaputtes Bild, das sich nie wieder repariert.
        @file_put_contents($datei, $bild);

        return $bild;
    }

    /**
     * Orte zu einem Suchwort - fuer den Assistenten.
     *
     * Warum es das gibt: das Paar tippte seine Adresse in ein leeres Feld,
     * und ob der Kartendienst sie kennt, stellte sich erst auf der fertigen
     * Einladung heraus - dann naemlich, wenn keine Karte kam. Kein Hinweis,
     * keine zweite Chance, und die Einladung war schon verschickt.
     *
     * Hier sucht dasselbe Verzeichnis, das spaeter auch die Karte zeichnet.
     * Was in dieser Liste steht, hat also garantiert eine Karte - das Paar
     * waehlt statt zu raten.
     *
     * Die Anfrage geht ueber UNSEREN Server, nicht aus dem Browser: sonst
     * saehe das Verzeichnis jeden Gast und jeden Tastendruck, und die Seite
     * ist darauf gebaut, dass ohne Einwilligung nichts nach draussen geht.
     *
     * @return list<array{name:string,address:string,lat:float,lng:float}>
     */
    public static function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        // Zwei Zeichen sind kein Ort. Sie waeren aber eine Anfrage - und zwar
        // bei jedem Tastendruck, an ein Verzeichnis, dessen Regeln genau das
        // nicht wollen.
        if (mb_strlen($query) < 3) {
            return [];
        }

        $limit = max(1, min(8, $limit));

        /*
         * Der bessere Weg zuerst, wenn er offensteht.
         *
         * Google kennt Saele, Hotels und Gasthaeuser beim Namen; das freie
         * Verzeichnis kennt vor allem Strassen und Orte. "Stadthalle
         * Guenzburg" findet der eine und der andere nicht - und ein Paar
         * sucht seinen Festsaal beim Namen, nicht bei der Hausnummer.
         *
         * Ungebremst darf das hier nicht laufen (der Aufrufer bremst), und
         * gespeichert wird es auch nicht: Google erlaubt das Ablegen seiner
         * Ergebnisse nicht. Deshalb steht dieser Zweig VOR dem Cache.
         */
        if (Places::configured()) {
            $treffer = Places::search($query);
            if ($treffer !== []) {
                $aus = [];
                foreach (array_slice($treffer, 0, $limit) as $ort) {
                    $aus[] = self::treffer(
                        (string) ($ort['name'] ?? ''),
                        (string) ($ort['address'] ?? ''),
                        (float) ($ort['lat'] ?? 0),
                        (float) ($ort['lng'] ?? 0)
                    );
                }
                return $aus;
            }
        }

        $datei = self::cacheDir() . '/such-' . sha1(mb_strtolower($query) . '|' . $limit) . '.json';

        if (is_file($datei)) {
            $roh = json_decode((string) @file_get_contents($datei), true);
            if (is_array($roh)) {
                return $roh;
            }
        }

        $antwort = self::fetch(self::NOMINATIM . '?' . http_build_query([
            'q'              => $query,
            'format'         => 'jsonv2',
            'limit'          => $limit,
            // Der Name des Saals getrennt von der Anschrift: auf der
            // Einladung stehen sie in zwei Zeilen, und sie hier wieder
            // auseinanderzuschneiden hiesse raten.
            'addressdetails' => 1,
        ]));

        if ($antwort === null) {
            return [];
        }

        $daten = json_decode($antwort, true);
        if (!is_array($daten)) {
            return [];
        }

        $out = [];
        foreach ($daten as $treffer) {
            if (!is_array($treffer) || !isset($treffer['lat'], $treffer['lon'])) {
                continue;
            }

            $ganz = (string) ($treffer['display_name'] ?? '');
            if ($ganz === '') {
                continue;
            }

            /*
             * display_name ist eine Kette von Kommas: "Schloss Hohenstein,
             * Schlossstrasse 1, Guenzburg, Bayern, 89312, Deutschland". Das
             * erste Glied ist der Name, wenn der Ort einen hat (ein Saal, ein
             * Hotel) - sonst ist es die Hausnummer und damit Teil der
             * Anschrift. Woran man das erkennt: Nominatim gibt den Namen
             * separat, wenn es einen gibt.
             */
            $name = trim((string) ($treffer['name'] ?? ''));
            $teile = array_map('trim', explode(',', $ganz));

            if ($name !== '' && ($teile[0] ?? '') === $name) {
                array_shift($teile);
            } else {
                $name = '';
            }

            // Land und Bundesland weglassen waere Willkuer - eine Einladung
            // nach Bregenz braucht "Oesterreich". Gekuerzt wird nur, was
            // ohnehin doppelt steht.
            $adresse = implode(', ', array_values(array_unique(array_filter($teile))));

            $out[] = self::treffer($name, $adresse, (float) $treffer['lat'], (float) $treffer['lon']);
        }

        // Auch eine leere Antwort wird gemerkt: "diesen Ort kennt das
        // Verzeichnis nicht" ist ein Ergebnis, und es zweimal zu erfragen
        // aendert es nicht.
        @file_put_contents($datei, json_encode($out, JSON_UNESCAPED_UNICODE));

        return $out;
    }

    /**
     * Ein Treffer, wie ihn der Assistent bekommt.
     *
     * Die Unterschrift reist mit, weil das Vorschaubild sie verlangt: sie
     * sagt "diese Koordinate kam von uns". Ohne sie waere der Endpunkt fuer
     * das Bild ein Dienst, der jedem Vorbeikommenden zu beliebigen
     * Koordinaten eine Karte rendert und sie auf unserer Platte ablegt -
     * 190 KB je Anfrage, und die Platte ist irgendwann voll.
     *
     * @return array{name:string,address:string,lat:float,lng:float,sig:string}
     */
    private static function treffer(string $name, string $adresse, float $lat, float $lng): array
    {
        return [
            'name'    => $name,
            'address' => $adresse,
            'lat'     => $lat,
            'lng'     => $lng,
            'sig'     => self::sign($lat, $lng),
        ];
    }

    /**
     * Die Unterschrift einer Koordinate.
     *
     * Dieselbe Bauart wie Security::client(): gehasht mit dem admin_key als
     * Salz, das Geheimnis selbst verlaesst den Server nie. Gerundet wird auf
     * fuenf Stellen (gut einen Meter), damit die Zahl in der Adresse und die
     * Zahl beim Pruefen dieselbe ist - eine Gleitkommazahl, die einmal durch
     * einen String gegangen ist, ist es sonst nicht mehr.
     */
    public static function sign(float $lat, float $lng): string
    {
        return substr(hash('sha256', self::punkt($lat, $lng) . '|karte|' . Config::str('admin_key', 'salz')), 0, 16);
    }

    /** Die kanonische Schreibweise einer Koordinate - einmal, fuer beide Seiten. */
    private static function punkt(float $lat, float $lng): string
    {
        return number_format($lat, 5, '.', '') . ',' . number_format($lng, 5, '.', '');
    }

    /** Stimmt die Unterschrift zu dieser Koordinate? */
    public static function verify(float $lat, float $lng, string $sig): bool
    {
        return hash_equals(self::sign($lat, $lng), $sig);
    }

    /**
     * Das Kartenbild zu einer Koordinate - fuer die Vorschau im Assistenten.
     *
     * Getrennt von forAddress(): dort ist die Anschrift der Schluessel und
     * das Geokodieren der erste Schritt. Hier ist der Punkt schon bekannt,
     * weil er gerade aus der Suche kam - noch einmal zu fragen waere eine
     * Anfrage an ein fremdes Verzeichnis fuer eine Auskunft, die wir haben.
     */
    public static function forPoint(float $lat, float $lng, int $width = 480, int $height = 360): ?string
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
            return null;
        }

        $datei = self::cacheDir() . '/p-' . sha1(self::punkt($lat, $lng) . '|' . $width . 'x' . $height) . '.png';

        if (is_file($datei) && filesize($datei) > 0) {
            $inhalt = @file_get_contents($datei);
            if (is_string($inhalt) && $inhalt !== '') {
                return $inhalt;
            }
        }

        $bild = Places::configured()
            ? (Places::staticMap($lat, $lng, $width, $height) ?: self::fromTiles($lat, $lng, $width, $height))
            : self::fromTiles($lat, $lng, $width, $height);

        if ($bild === null || $bild === '') {
            return null;
        }

        @file_put_contents($datei, $bild);

        return $bild;
    }

    /* ------------------------------- Innen --------------------------------- */

    /**
     * Der Ablageort - ausserhalb von public/, und zwar mit Absicht.
     *
     * Der naheliegende Platz waere public/uploads/ gewesen: dort ist der
     * Server ohnehin schreibberechtigt. Aber neben dem Bild liegt die
     * Koordinate des Festsaals in einer .json, und die .htaccess des
     * Uploadordners haelt nur Programme ab, keine Datenfiles - die Datei
     * waere unter ihrer Adresse abrufbar gewesen. data/ liegt neben public/
     * und nicht darin; der Webserver kommt dort gar nicht erst hin.
     */
    private static function cacheDir(): string
    {
        $pfad = dirname(__DIR__) . '/data/cache/karten';
        if (!is_dir($pfad)) {
            @mkdir($pfad, 0755, true);
        }

        return $pfad;
    }

    private static function render(string $address, int $width, int $height): ?string
    {
        $punkt = self::geocode($address);
        if ($punkt === null) {
            return null;
        }

        [$lat, $lng] = $punkt;

        // Der bezahlte Weg zuerst, wenn er offensteht: Places bringt seinen
        // eigenen Schluessel und seine eigene Pruefung mit.
        if (Places::configured()) {
            $bild = Places::staticMap($lat, $lng, $width, $height);
            if ($bild !== null && $bild !== '') {
                return $bild;
            }
        }

        return self::fromTiles($lat, $lng, $width, $height);
    }

    /**
     * Adresse -> Koordinate, einmal und dann aus dem Cache.
     *
     * Die Koordinate liegt getrennt vom Bild, weil sie laenger haelt: wer die
     * Bildgroesse aendert, soll nicht ein zweites Mal geokodieren.
     *
     * @return array{0:float,1:float}|null
     */
    private static function geocode(string $address): ?array
    {
        $datei = self::cacheDir() . '/geo-' . sha1($address) . '.json';

        if (is_file($datei)) {
            $roh = json_decode((string) @file_get_contents($datei), true);
            if (is_array($roh) && isset($roh['lat'], $roh['lng'])) {
                return [(float) $roh['lat'], (float) $roh['lng']];
            }
        }

        $antwort = self::fetch(self::NOMINATIM . '?' . http_build_query([
            'q'      => $address,
            'format' => 'jsonv2',
            'limit'  => 1,
        ]));

        if ($antwort === null) {
            return null;
        }

        $daten = json_decode($antwort, true);
        if (!is_array($daten) || !isset($daten[0]['lat'], $daten[0]['lon'])) {
            return null;
        }

        $lat = (float) $daten[0]['lat'];
        $lng = (float) $daten[0]['lon'];

        // Der Nullpunkt im Atlantik ist die uebliche Gestalt eines
        // Fehlschlags, nicht die eines Festsaals.
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        @file_put_contents($datei, json_encode(['lat' => $lat, 'lng' => $lng]));

        return [$lat, $lng];
    }

    /**
     * Das Bild aus OSM-Kacheln zusammensetzen.
     *
     * Gerechnet wird im Kachelschema (Web Mercator): der Punkt faellt auf
     * einen Bruchteil einer Kachel, und das fertige Bild wird so
     * ausgeschnitten, dass genau dieser Bruchteil in der Mitte liegt. Ohne
     * die Nachkommastellen saesse der Saal irgendwo im Bild statt im
     * Fadenkreuz.
     */
    private static function fromTiles(float $lat, float $lng, int $width, int $height): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $n = 2 ** self::ZOOM;
        $x = ($lng + 180) / 360 * $n;
        $y = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * $n;

        // Wie viele Kacheln links und rechts der mittleren gebraucht werden.
        // +1 statt genau: der Rand darf ueberstehen, ein fehlendes Stueck
        // waere ein weisser Streifen im Bild.
        $spalten = (int) ceil($width / self::TILE) + 1;
        $zeilen  = (int) ceil($height / self::TILE) + 1;

        $x0 = (int) floor($x) - intdiv($spalten, 2);
        $y0 = (int) floor($y) - intdiv($zeilen, 2);

        $tafel = imagecreatetruecolor($spalten * self::TILE, $zeilen * self::TILE);
        if ($tafel === false) {
            return null;
        }
        imagefilledrectangle($tafel, 0, 0, imagesx($tafel), imagesy($tafel), imagecolorallocate($tafel, 0xE8, 0xE4, 0xDC));

        $geladen = 0;
        for ($sx = 0; $sx < $spalten; $sx++) {
            for ($sy = 0; $sy < $zeilen; $sy++) {
                $tx = $x0 + $sx;
                $ty = $y0 + $sy;

                // Ausserhalb des Kachelfeldes gibt es nichts zu holen; oben
                // und unten endet die Welt, links und rechts laeuft sie um.
                if ($ty < 0 || $ty >= $n) {
                    continue;
                }
                $tx = (($tx % $n) + $n) % $n;

                $roh = self::fetch(strtr(self::TILES, [
                    '{z}' => (string) self::ZOOM,
                    '{x}' => (string) $tx,
                    '{y}' => (string) $ty,
                ]));
                if ($roh === null || $roh === '') {
                    continue;
                }

                $kachel = @imagecreatefromstring($roh);
                if ($kachel === false) {
                    continue;
                }

                imagecopy($tafel, $kachel, $sx * self::TILE, $sy * self::TILE, 0, 0, self::TILE, self::TILE);
                imagedestroy($kachel);
                $geladen++;
            }
        }

        // Kein einziges Stueck Karte: lieber gar kein Bild als ein grauer
        // Kasten, der aussieht wie ein Fehler.
        if ($geladen === 0) {
            imagedestroy($tafel);
            return null;
        }

        // Wo der Punkt auf der zusammengesetzten Tafel liegt.
        $px = ($x - $x0) * self::TILE;
        $py = ($y - $y0) * self::TILE;

        $bild = imagecreatetruecolor($width, $height);
        if ($bild === false) {
            imagedestroy($tafel);
            return null;
        }

        imagecopy(
            $bild,
            $tafel,
            0,
            0,
            (int) round($px - $width / 2),
            (int) round($py - $height / 2),
            $width,
            $height
        );
        imagedestroy($tafel);

        self::marker($bild, intdiv($width, 2), intdiv($height, 2));

        ob_start();
        imagepng($bild, null, 8);
        $aus = (string) ob_get_clean();
        imagedestroy($bild);

        return $aus === '' ? null : $aus;
    }

    /**
     * Die Nadel: ein Tropfen in Messing, die Hausfarbe der Seite.
     *
     * Von Hand gezeichnet und nicht als Bilddatei mitgeliefert, weil eine
     * Datei mehr eine Datei mehr ist, die auf dem Webspace fehlen kann - und
     * ohne sie stuende die Karte ohne Markierung da.
     */
    private static function marker(\GdImage $bild, int $x, int $y): void
    {
        $messing = imagecolorallocate($bild, 0xB0, 0x8D, 0x57);
        $weiss   = imagecolorallocate($bild, 0xFF, 0xFF, 0xFF);

        // Der Schatten sitzt auf dem Boden, der Kopf darueber: so liest sich
        // die Nadel als stehend und nicht als aufgeklebter Punkt.
        imagefilledellipse($bild, $x, $y + 12, 14, 5, imagecolorallocatealpha($bild, 0, 0, 0, 90));

        imagefilledellipse($bild, $x, $y - 4, 22, 22, $weiss);
        imagefilledellipse($bild, $x, $y - 4, 18, 18, $messing);
        imagefilledellipse($bild, $x, $y - 4, 7, 7, $weiss);

        // Die Spitze, aus drei Punkten.
        imagefilledpolygon($bild, [$x - 7, $y + 2, $x + 7, $y + 2, $x, $y + 13], $messing);
    }

    /** Eine Anfrage nach draussen. Null heisst: kein brauchbares Ergebnis. */
    private static function fetch(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => self::AGENT,
            CURLOPT_HTTPHEADER     => ['Accept-Language: de, en'],
        ]);

        $antwort = curl_exec($curl);
        $status  = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $fehler  = curl_error($curl);
        curl_close($curl);

        if ($antwort === false || $fehler !== '' || $status >= 400) {
            error_log('[karte] ' . ($fehler !== '' ? $fehler : 'HTTP ' . $status) . ' ' . $url);
            return null;
        }

        return (string) $antwort;
    }
}
