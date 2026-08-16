<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Dates;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Security;
use Atelier\View;

/**
 * Die Auswahl des Paares für den Albumhersteller.
 *
 * Kein Login, kein Galeriepasswort – ein geheimer, befristeter Link, den der
 * Fotograf weitergibt. Der Drucker sieht genau die ausgesuchten Bilder und
 * lädt sie in einem Zug als ZIP, in voller Auflösung, wo es sie gibt.
 *
 * Der Umweg über „herunterladen, hochladen, verschicken“ fällt damit weg, und
 * die Dateien verlassen den eigenen Server nicht auf dem Weg über einen
 * fremden Dienst.
 */
final class SelectionController
{
    public function show(array $params): void
    {
        $token = Security::clean($params['token'] ?? '', 40);

        // Raten kosten lassen; ein falscher Token sieht aus wie keiner.
        if (Security::throttle('auswahl', 60, 600)) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $gallery = Galleries::shareFind($token);
        if ($gallery === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $code = (string) $gallery['code'];
        $selection = Galleries::selection($code);
        $photos = Galleries::selectedPhotos($gallery, $selection);

        View::page('pages/selection', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/auswahl/' . $token),
            'meta'   => [
                'title'   => (string) ($gallery['couple'] ?? ''),
                'noindex' => true,
                'bare'    => true,
            ],
            'gallery'   => $gallery,
            'selection' => $selection,
            'photos'    => $photos,
            'token'     => $token,
            'dateLong'  => Dates::long((string) ($gallery['date'] ?? '')),
        ]);
    }

    /** Alles auf einmal – das ist der Grund, warum es die Seite gibt. */
    public function zip(array $params): void
    {
        $token = Security::clean($params['token'] ?? '', 40);

        if (Security::throttle('auswahl-zip', 20, 600)) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $gallery = Galleries::shareFind($token);
        if ($gallery === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $code = (string) $gallery['code'];
        $photos = Galleries::selectedPhotos($gallery, Galleries::selection($code));

        // Nur was wirklich auf der Platte liegt: Platzhalterbilder gehören
        // niemandem und haben im Album nichts zu suchen.
        $files = array_values(array_filter(
            $photos,
            static fn (array $p): bool => $p['original'] !== null
        ));

        if ($files === [] || !class_exists('ZipArchive')) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $zip = new \ZipArchive();
        $temp = tempnam(sys_get_temp_dir(), 'auswahl');
        if ($temp === false || $zip->open($temp, \ZipArchive::OVERWRITE) !== true) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        foreach ($files as $file) {
            // Durchnummeriert in der Reihenfolge, die das Paar gesehen hat –
            // so kann man am Telefon über „Nummer sieben“ reden.
            $name = sprintf('%03d-%s', $file['nr'], basename((string) $file['original']));
            $zip->addFile((string) $file['original'], $name);
        }

        $zip->close();

        $download = $code . '-auswahl.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $download . '"');
        header('Content-Length: ' . (string) filesize($temp));
        header('X-Content-Type-Options: nosniff');
        readfile($temp);
        @unlink($temp);
        exit;
    }
}
