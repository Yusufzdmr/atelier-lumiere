<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Das Vorschaubild, das WhatsApp zeigt, wenn jemand den Einladungslink teilt.
 *
 * Ohne so ein Bild ist eine geteilte Einladung eine graue Zeile Text – und
 * genau in dem Moment entscheidet der Gast, ob er tippt. Gebraucht wird ein
 * Querformat von 1200x630; die Fotos einer Einladung sind Hochformat, also
 * wird beschnitten statt gestaucht.
 *
 * Namen und Datum stehen NICHT im Bild: dafür bräuchte GD eine TTF-Datei, und
 * im Projekt liegen nur WOFF2-Schriften. WhatsApp setzt Titel und
 * Beschreibung ohnehin als Text daneben – dort sind sie schärfer als jede
 * eingebrannte Schrift. Wer trotzdem eine gestaltete Karte will, lädt sie im
 * Verwaltungsbereich als eigenes Vorschaubild hoch.
 */
final class OgImage
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    /** Ordner der erzeugten Vorschaubilder. */
    private const FOLDER = 'og';

    /**
     * Adresse des Vorschaubilds – immer absolut, sonst zeigt WhatsApp nichts.
     *
     * @param array<string,mixed> $invitation
     */
    public static function url(array $invitation): string
    {
        // Ein selbst hochgeladenes Bild gilt unverändert: wer sich die Mühe
        // gemacht hat, will genau das sehen.
        $own = (string) ($invitation['ogImage'] ?? '');
        if ($own !== '') {
            return self::absolute($own);
        }

        $source = self::source($invitation);
        if ($source === null) {
            return '';
        }

        $slug = Invitations::slug((string) ($invitation['slug'] ?? ''));
        $name = $slug . '-' . substr(md5($source), 0, 8) . '.jpg';
        $path = Media::dir(self::FOLDER) . '/' . $name;

        if (!is_file($path) && !self::build($source, $invitation, $path)) {
            // Kein GD, kein lesbares Foto: lieber das Originalfoto als nichts.
            return self::absolute($source);
        }

        return self::absolute(Media::url(self::FOLDER . '/' . $name));
    }

    /** Erzeugte Vorschauen einer Einladung wegräumen. */
    public static function forget(string $slug): void
    {
        $slug = Invitations::slug($slug);
        foreach (glob(Media::dir(self::FOLDER) . '/' . $slug . '-*.jpg') ?: [] as $file) {
            @unlink($file);
        }
    }

    /* -------------------------------- Quelle -------------------------------- */

    /**
     * Woraus das Vorschaubild entsteht: erst die eigenen Fotos der Einladung,
     * dann der Hintergrund des Themas.
     *
     * @param array<string,mixed> $invitation
     */
    private static function source(array $invitation): ?string
    {
        foreach ((array) ($invitation['photos'] ?? []) as $photo) {
            if (is_string($photo) && $photo !== '') {
                return $photo;
            }
        }

        $theme = Themes::find((string) ($invitation['theme'] ?? ''));
        $background = (string) ($theme['image'] ?? '');

        return $background !== '' ? $background : null;
    }

    /** Aus einer öffentlichen Adresse den Pfad im Dateisystem machen. */
    private static function file(string $url): ?string
    {
        $prefix = '/' . trim(Config::str('upload_dir', 'uploads'), '/') . '/';
        if (!str_starts_with($url, $prefix) || str_contains($url, '..')) {
            return null;
        }

        $path = Media::dir() . '/' . substr($url, strlen($prefix));
        return is_file($path) ? $path : null;
    }

    private static function absolute(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : Config::url() . $url;
    }

    /* --------------------------------- Bauen -------------------------------- */

    /** @param array<string,mixed> $invitation */
    private static function build(string $source, array $invitation, string $target): bool
    {
        $file = self::file($source);
        if ($file === null || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($file);
        if ($info === false) {
            return false;
        }

        $photo = match ((string) ($info['mime'] ?? '')) {
            'image/jpeg' => @imagecreatefromjpeg($file),
            'image/png'  => @imagecreatefrompng($file),
            'image/webp' => @imagecreatefromwebp($file),
            'image/gif'  => @imagecreatefromgif($file),
            default      => false,
        };

        if ($photo === false) {
            return false;
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $theme = Themes::complete(Themes::find((string) ($invitation['theme'] ?? '')) ?? []);
        self::fill($canvas, (string) $theme['paper']);

        [$width, $height] = $info;
        $scale = max(self::WIDTH / $width, self::HEIGHT / $height);
        $cropWidth = (int) round(self::WIDTH / $scale);
        $cropHeight = (int) round(self::HEIGHT / $scale);

        // Waagerecht mittig, senkrecht ins obere Drittel: auf Hochformat-
        // aufnahmen stehen die Gesichter oben, nicht in der Mitte.
        $x = (int) round(($width - $cropWidth) / 2);
        $y = (int) round(($height - $cropHeight) * 0.3);

        imagecopyresampled($canvas, $photo, 0, 0, $x, $y, self::WIDTH, self::HEIGHT, $cropWidth, $cropHeight);
        imagedestroy($photo);

        self::vignette($canvas);
        self::frame($canvas, (string) $theme['accentSoft']);

        $ok = imagejpeg($canvas, $target, 82);
        imagedestroy($canvas);

        return $ok;
    }

    /** @param \GdImage $image */
    private static function fill($image, string $color): void
    {
        [$r, $g, $b] = self::rgb($color);
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, imagecolorallocate($image, $r, $g, $b));
    }

    /**
     * Ein sanfter Schatten von unten. Ohne ihn schwimmt ein helles Foto in der
     * weißen Vorschaukarte von WhatsApp, mit ihm bekommt es eine Kante.
     *
     * @param \GdImage $image
     */
    private static function vignette($image): void
    {
        $band = (int) (self::HEIGHT * 0.45);
        for ($i = 0; $i < $band; $i++) {
            $y = self::HEIGHT - $i - 1;
            // 0 bis 55 Prozent Deckkraft, unten am stärksten
            $alpha = (int) round(127 - (127 * 0.55) * ($i / $band));
            $black = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
            imageline($image, 0, $y, self::WIDTH, $y, $black);
        }
    }

    /** Schmale Linie im Akzentton – macht aus dem Foto eine Karte. @param \GdImage $image */
    private static function frame($image, string $color): void
    {
        [$r, $g, $b] = self::rgb($color);
        $line = imagecolorallocatealpha($image, $r, $g, $b, 40);
        $inset = 22;

        imagesetthickness($image, 2);
        imagerectangle($image, $inset, $inset, self::WIDTH - $inset, self::HEIGHT - $inset, $line);
        imagesetthickness($image, 1);
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $color): array
    {
        $hex = ltrim(trim($color), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return [250, 247, 242];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
