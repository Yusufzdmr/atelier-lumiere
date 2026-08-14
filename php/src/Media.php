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

        $name = bin2hex(random_bytes(8)) . '.jpg';
        $relative = trim($folder, '/') . '/' . $name;
        $target = self::dir($folder) . '/' . $name;

        // Einheitlich JPEG: kleiner als PNG und überall darstellbar.
        $ok = imagejpeg($image, $target, self::QUALITY);
        imagedestroy($image);

        return $ok ? self::url($relative) : null;
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
    }
}
