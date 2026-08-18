<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Hochgeladene Bilder.
 *
 * Auf Vercel lag das in einem Objektspeicher, hier liegt es im Dateisystem
 * des Webspace. Verkleinert wird serverseitig mit GD: was aus einer Kamera
 * kommt, hat 6000 px – das will niemand ausliefern und der Speicherplatz
 * eines Webhosting-Pakets ist endlich.
 */
final class Media
{
    private const MAX_WIDTH = 1600;
    private const QUALITY = 82;

    /** Ordner im Dateisystem, in dem die Uploads liegen. */
    public static function dir(string $sub = ''): string
    {
        $base = dirname(__DIR__) . '/public/' . trim(Config::str('upload_dir', 'uploads'), '/');
        $path = $sub === '' ? $base : $base . '/' . trim($sub, '/');

        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    /** Öffentliche Adresse einer gespeicherten Datei. */
    public static function url(string $relative): string
    {
        return '/' . trim(Config::str('upload_dir', 'uploads'), '/') . '/' . ltrim($relative, '/');
    }

    /**
     * Datei aus $_FILES übernehmen: prüfen, verkleinern, speichern.
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     * @return string|null Öffentliche URL oder null bei Fehler
     */
    public static function store(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }

        // Auf den Dateinamen ist kein Verlass – der Inhalt entscheidet.
        $info = @getimagesize($tmp);
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = (string) ($info['mime'] ?? '');

        // Nicht jeder Webspace hat GD. Dann wird nicht verkleinert, sondern die
        // geprüfte Datei unverändert abgelegt – lieber ein großes Bild als eine
        // Fehlerseite beim Hochladen.
        if (!function_exists('imagecreatefromjpeg')) {
            return self::keep($tmp, $mime, $folder);
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            'image/gif'  => @imagecreatefromgif($tmp),
            default      => false,
        };

        if ($image === false) {
            return null;
        }

        // Seitenverhältnis behalten, nur verkleinern – nie vergrößern.
        if ($width > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * (self::MAX_WIDTH / $width));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $stem = bin2hex(random_bytes(8));
        $name = $stem . '.jpg';
        $relative = trim($folder, '/') . '/' . $name;
        $target = self::dir($folder) . '/' . $name;

        // Einheitlich JPEG: kleiner als PNG und überall darstellbar.
        $ok = imagejpeg($image, $target, self::QUALITY);
        imagedestroy($image);

        if (!$ok) {
            return null;
        }

        // Das Original daneben legen – siehe keepOriginal().
        self::keepOriginal($tmp, $mime, $folder, $stem);

        return self::url($relative);
    }

    /** Unterordner neben den verkleinerten Bildern. */
    public const ORIGINALS = 'original';

    /**
     * Das unveraenderte Bild neben dem verkleinerten aufheben.
     *
     * Die Galerie zeigt 1600 Pixel – richtig fuer den Browser, zu wenig fuer
     * den Albumdruck. Wenn das Paar seine Bilder ausgesucht hat, soll der
     * Drucker die vollen Dateien bekommen und nicht der Fotograf noch einmal
     * suchen, hochladen und schicken muessen.
     *
     * Gleicher Dateiname wie das verkleinerte Bild, nur in einem Unterordner:
     * so ist das Original aus der Adresse des kleinen ableitbar und die
     * gespeicherte Bildliste bleibt, was sie war.
     */
    private static function keepOriginal(string $tmp, string $mime, string $folder, string $stem): void
    {
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };

        if ($extension === null) {
            return;
        }

        $target = self::dir(trim($folder, '/') . '/' . self::ORIGINALS) . '/' . $stem . '.' . $extension;

        // move_uploaded_file waere hier falsch: die Datei wird anschliessend
        // noch als verkleinerte Fassung gebraucht.
        @copy($tmp, $target);
    }

    /**
     * Zu einem verkleinerten Bild das Original finden.
     *
     * @return string|null Pfad auf der Platte, oder null wenn es keines gibt
     *                     (Bilder von vor dieser Änderung, oder Platzhalter)
     */
    public static function originalPath(string $url): ?string
    {
        $prefix = '/' . trim(Config::str('upload_dir', 'uploads'), '/') . '/';
        if (!str_starts_with($url, $prefix)) {
            return null;
        }

        $relative = substr($url, strlen($prefix));
        if (str_contains($relative, '..')) {
            return null;
        }

        $folder = dirname($relative);
        $stem = pathinfo($relative, PATHINFO_FILENAME);
        $base = self::dir() . '/' . $folder . '/' . self::ORIGINALS . '/' . $stem;

        foreach (['jpg', 'png', 'webp', 'gif'] as $extension) {
            if (is_file($base . '.' . $extension)) {
                return $base . '.' . $extension;
            }
        }

        return null;
    }

    /**
     * Rückfall ohne GD: die geprüfte Datei unverändert übernehmen.
     *
     * Die Endung kommt aus dem erkannten Typ, nicht aus dem Dateinamen – so
     * landet nichts Ausführbares im Upload-Ordner.
     */
    private static function keep(string $tmp, string $mime, string $folder): ?string
    {
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };

        if ($extension === null) {
            return null;
        }

        $name = bin2hex(random_bytes(8)) . '.' . $extension;
        $target = self::dir($folder) . '/' . $name;

        return move_uploaded_file($tmp, $target)
            ? self::url(trim($folder, '/') . '/' . $name)
            : null;
    }

    /** Groesser als ein Lied braucht eine Einladung nicht. */
    private const MAX_AUDIO = 12 * 1024 * 1024;

    /**
     * Hintergrundmusik der Einladung.
     *
     * Frueher stand hier ein Feld fuer eine Adresse, und wer – wie jeder – den
     * YouTube-Link seines Lieds hineinkopierte, bekam ein <audio>-Element mit
     * einer HTML-Seite darin. Das kann nicht klingen. Ein Video-Einbett-Player
     * waere der falsche Ausweg: er laedt beim Oeffnen der Karte bei einem
     * Fremden, und genau das soll auf dieser Seite ohne Einwilligung nie
     * passieren. Also liegt die Datei bei uns.
     *
     * Wie bei Bildern entscheidet der Inhalt, nicht der Dateiname, und die
     * Endung vergeben wir selbst.
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     */
    public static function storeAudio(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || (int) ($file['size'] ?? 0) > self::MAX_AUDIO) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $tmp) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        // mp4/m4a meldet je nach Server audio/x-m4a oder audio/mp4.
        $extension = match ($mime) {
            'audio/mpeg', 'audio/mp3'   => 'mp3',
            'audio/mp4', 'audio/x-m4a'  => 'm4a',
            'audio/ogg', 'application/ogg' => 'ogg',
            'audio/wav', 'audio/x-wav'  => 'wav',
            default                     => null,
        };

        if ($extension === null) {
            return null;
        }

        $name = bin2hex(random_bytes(8)) . '.' . $extension;
        $target = self::dir($folder) . '/' . $name;

        return move_uploaded_file($tmp, $target)
            ? self::url(trim($folder, '/') . '/' . $name)
            : null;
    }

    /** Kurzer Beispielfilm einer Leistung (mp4/webm/mov). 100 MB Deckel. */
    private const MAX_VIDEO = 100 * 1024 * 1024;

    /**
     * Video übernehmen: Endung nach Inhalt, nicht nach dem Dateinamen.
     *
     * Wir transkodieren nicht – das kann ein Webhosting nicht in Ruhe. Wer
     * grosse Kameradateien laedt, sieht sie so wie sie sind; im Panel steht
     * daher der Hinweis „vorher komprimieren".
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     */
    public static function storeVideo(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || (int) ($file['size'] ?? 0) > self::MAX_VIDEO) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $tmp) : '';
        if ($finfo !== false) {
            finfo_close($finfo);
        }

        $extension = match ($mime) {
            'video/mp4', 'video/x-m4v'    => 'mp4',
            'video/webm'                  => 'webm',
            'video/quicktime', 'video/mov' => 'mov',
            default                        => null,
        };

        if ($extension === null) {
            return null;
        }

        $name = bin2hex(random_bytes(8)) . '.' . $extension;
        $target = self::dir($folder) . '/' . $name;

        return move_uploaded_file($tmp, $target)
            ? self::url(trim($folder, '/') . '/' . $name)
            : null;
    }

    /**
     * Schmuckelemente: Blume, Rahmen, Monogramm.
     *
     * Anders als bei Fotos darf hier nichts nach JPEG umgewandelt werden – ein
     * Rahmen ohne durchsichtigen Hintergrund ist kein Rahmen, sondern ein
     * weisses Rechteck. Ausgegeben wird deshalb WebP mit Alphakanal (kleiner
     * als PNG bei gleicher Qualitaet) oder, wo GD das nicht kann, PNG.
     *
     * SVG wird durchgereicht: es wird ausschliesslich in einem <img> gezeigt,
     * und dort fuehren Browser kein Skript aus. Zusaetzlich raeumt
     * cleanSvg() auf, und der Upload-Ordner erlaubt per .htaccess ohnehin
     * nichts Ausfuehrbares.
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int} $file
     */
    public static function storeGraphic(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || filesize($tmp) > 6 * 1024 * 1024) {
            return null;
        }

        $raw = (string) file_get_contents($tmp);

        // SVG erkennt getimagesize() nicht – es ist Text, kein Rasterbild.
        if (preg_match('/^\s*(<\?xml|<svg)/i', $raw) === 1) {
            $clean = self::cleanSvg($raw);
            if ($clean === null) {
                return null;
            }
            $name = bin2hex(random_bytes(8)) . '.svg';
            $target = self::dir($folder) . '/' . $name;
            return file_put_contents($target, $clean) === false
                ? null
                : self::url(trim($folder, '/') . '/' . $name);
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = (string) ($info['mime'] ?? '');

        if (!function_exists('imagecreatefrompng')) {
            return self::keep($tmp, $mime, $folder);
        }

        $image = match ($mime) {
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            'image/gif'  => @imagecreatefromgif($tmp),
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            default      => false,
        };

        if ($image === false) {
            return null;
        }

        // Schmuck braucht keine Fotoaufloesung.
        $max = 1400;
        if ($width > $max) {
            $newWidth = $max;
            $newHeight = (int) round($height * ($max / $width));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $webp = function_exists('imagewebp');
        $name = bin2hex(random_bytes(8)) . ($webp ? '.webp' : '.png');
        $target = self::dir($folder) . '/' . $name;

        $ok = $webp ? imagewebp($image, $target, 88) : imagepng($image, $target, 6);
        imagedestroy($image);

        return $ok ? self::url(trim($folder, '/') . '/' . $name) : null;
    }

    /**
     * SVG entschaerfen.
     *
     * Im <img> laeuft ohnehin kein Skript; das hier ist die zweite Reihe fuer
     * den Fall, dass die Datei doch einmal direkt aufgerufen wird.
     */
    private static function cleanSvg(string $svg): ?string
    {
        if (mb_strlen($svg) > 400 * 1024 || !str_contains(strtolower($svg), '<svg')) {
            return null;
        }

        $svg = preg_replace('#<script\b.*?</script>#is', '', $svg) ?? '';
        $svg = preg_replace('#<foreignObject\b.*?</foreignObject>#is', '', $svg) ?? '';
        $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? '';
        $svg = preg_replace('/(href|xlink:href)\s*=\s*("|\')\s*(javascript|data):[^"\']*("|\')/i', '', $svg) ?? '';
        $svg = preg_replace('#<!ENTITY.*?>#is', '', $svg) ?? '';

        return trim($svg) === '' ? null : $svg;
    }

    /**
     * Mehrere Dateien eines Formularfelds (multiple).
     *
     * @return list<string>
     */
    public static function storeMany(string $field, string $folder, int $max = 60): array
    {
        $files = $_FILES[$field] ?? null;
        if (!is_array($files) || !isset($files['name'])) {
            return [];
        }

        $urls = [];
        $names = (array) $files['name'];

        foreach (array_keys($names) as $i) {
            if (count($urls) >= $max) {
                break;
            }
            $url = self::store([
                'name'     => (string) ($files['name'][$i] ?? ''),
                'type'     => (string) ($files['type'][$i] ?? ''),
                'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                'error'    => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size'     => (int) ($files['size'][$i] ?? 0),
            ], $folder);

            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /** Datei löschen – nur innerhalb des Upload-Ordners. */
    public static function delete(string $url): void
    {
        $prefix = '/' . trim(Config::str('upload_dir', 'uploads'), '/') . '/';
        if (!str_starts_with($url, $prefix)) {
            return;
        }

        $relative = substr($url, strlen($prefix));
        // Kein Ausbrechen aus dem Ordner
        if (str_contains($relative, '..')) {
            return;
        }

        $path = self::dir() . '/' . $relative;
        if (is_file($path)) {
            @unlink($path);
        }

        // Sonst bleibt das Original als Karteileiche liegen – und Originale
        // sind das Vielfache dessen, was das verkleinerte Bild wiegt.
        $original = self::originalPath($url);
        if ($original !== null) {
            @unlink($original);
        }
    }
}
