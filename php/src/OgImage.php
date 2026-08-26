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

        $theme = Themes::complete(Themes::find((string) ($invitation['theme'] ?? '')) ?? []);

        return self::erzeugen(
            (string) ($invitation['slug'] ?? ''),
            $source,
            (string) $theme['paper'],
            (string) $theme['accentSoft']
        );
    }

    /**
     * Dasselbe fuer die zweite Fassung.
     *
     * Sie hat kein Thema, sondern ein Dokument: die Farben stehen in seiner
     * Palette, und das Foto liegt in einer seiner Ebenen. Deshalb nimmt dieser
     * Weg beides fertig entgegen, statt es sich aus einer Datenform zu holen,
     * die es hier zweimal gaebe.
     *
     * Der Kern darunter ist derselbe - Zuschnitt, Vignette, Rahmen, Cache.
     * Eine zweite Bilderzeugung waere eine zweite Stelle, an der der Zuschnitt
     * eines Tages anders ist als in der ersten Fassung.
     */
    public static function forDocument(string $slug, string $source, string $papier, string $rahmen): string
    {
        return $source === '' ? '' : self::erzeugen($slug, $source, $papier, $rahmen);
    }

    /**
     * Bauen, ablegen, Adresse zurueckgeben - fuer beide Fassungen.
     *
     * Der Dateiname traegt den Streuwert der Quelle: taucht das Paar sein
     * Foto aus, entsteht ein neuer Name und WhatsApp holt das Bild wirklich
     * neu. Unter demselben Namen bliebe die alte Vorschau in jedem Cache der
     * Welt stehen - und die Einladung zeigte monatelang das falsche Foto.
     */
    private static function erzeugen(string $slug, string $source, string $papier, string $rahmen): string
    {
        /*
         * Zuerst die Frage, ob wir diese Datei ueberhaupt kennen.
         *
         * Sie stand frueher erst in build(), und wenn die fehlschlug, ging
         * die ROHE Quelle als Rueckfallwert hinaus - "lieber das Originalfoto
         * als nichts". Der Satz stimmt, der Weg nicht: er hat den
         * Rueckfallwert ungeprueft durchgereicht. Ein Pfad, der auf einen
         * fremden Server zeigt, stuende damit als og:image auf der Einladung
         * und meldete jedem Gast, der sie in WhatsApp oeffnet, einen Besuch
         * dort. In der ersten Fassung kam die Quelle immer aus den eigenen
         * Daten, es fiel also nie auf - ein Loch bleibt es trotzdem.
         *
         * Jetzt wird einmal vorn geprueft. Danach ist der Rueckfallwert
         * derselbe wie vorher, aber er ist eine Datei aus dem eigenen Haus.
         */
        if (self::file($source) === null) {
            return '';
        }

        $slug = Invitations::slug($slug);
        $name = $slug . '-' . substr(md5($source), 0, 8) . '.jpg';
        $path = Media::dir(self::FOLDER) . '/' . $name;

        if (!is_file($path) && !self::build($source, $papier, $rahmen, $path)) {
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

    /**
     * Aus einer oeffentlichen Adresse den Pfad im Dateisystem machen.
     *
     * Zwei Orte, nicht einer: die Fotos des Paares liegen in /uploads, die
     * Bilder einer Vorlage in /assets. Bis heute stand hier nur der erste -
     * und eine Einladung, deren Karte ein Vorlagenfoto zeigt, bekam deshalb
     * gar kein gebautes Vorschaubild. Sie fiel auf das Originalfoto zurueck,
     * waehrend die Seite daneben og:image:width 1200 und height 630
     * behauptete. Ein Hochformat mit dieser Angabe schneidet WhatsApp
     * irgendwo durch.
     *
     * Gepruefte Grenze und kein Praefixvergleich: realpath() loest ".." und
     * Symlinks auf, und danach muss der Pfad noch immer unter public/ liegen.
     * str_contains('..') haette "%2e%2e" oder einen Symlink nicht gesehen.
     */
    private static function file(string $url): ?string
    {
        $upload = '/' . trim(Config::str('upload_dir', 'uploads'), '/') . '/';
        if (!str_starts_with($url, $upload) && !str_starts_with($url, '/assets/')) {
            return null;
        }

        $wurzel = realpath(dirname(__DIR__) . '/public');
        $pfad   = realpath(dirname(__DIR__) . '/public' . $url);

        if ($wurzel === false || $pfad === false || !is_file($pfad)) {
            return null;
        }

        return str_starts_with($pfad, $wurzel . DIRECTORY_SEPARATOR) ? $pfad : null;
    }

    private static function absolute(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : Config::url() . $url;
    }

    /* --------------------------------- Bauen -------------------------------- */

    /**
     * Zwei Farben und kein Thema.
     *
     * Frueher holte sich build() das Thema selbst aus der Einladung. Die
     * zweite Fassung hat keines - sie hat ein Dokument mit einer Palette -,
     * und ein zweiter Zweig hier waere eine zweite Stelle gewesen, an der
     * jemand den Zuschnitt aendern kann. Gebraucht wurden ohnehin nur zwei
     * Werte: der Grund und die Rahmenlinie.
     */
    private static function build(string $source, string $papier, string $rahmen, string $target): bool
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
        self::fill($canvas, $papier);

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
        self::frame($canvas, $rahmen);

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
        /*
         * Von oben nach unten dunkler - und der Verlauf lief verkehrt herum.
         *
         * In GD heisst alpha 127 "durchsichtig" und 0 "deckend". Die alte
         * Zeile begann am untersten Bildrand ($i = 0) bei 127, also
         * durchsichtig, und wurde nach OBEN hin dunkler. Am oberen Rand des
         * Bandes sprang die Deckkraft dann von 0 auf 55 Prozent: eine harte
         * waagerechte Kante quer durch das Bild, genau bei 55 Prozent Hoehe.
         * Auf einem Foto sah das aus wie ein Druckfehler, auf hellem Papier
         * wie zwei aufeinandergeklebte Bilder.
         *
         * Jetzt ist es, was der Satz immer behauptet hat: unten am
         * staerksten, nach oben auslaufend - und am Anfang des Bandes
         * beruehrungslos, also ohne Kante.
         */
        $band = (int) (self::HEIGHT * 0.45);
        for ($i = 0; $i < $band; $i++) {
            $y = self::HEIGHT - $i - 1;
            $alpha = (int) round(127 - (127 * 0.55) * (($band - $i) / $band));
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
