<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Albumist;
use Atelier\Dates;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Security;
use Atelier\View;

/**
 * Der Bereich des Albumherstellers: hazır Galerien sehen, ZIP laden.
 *
 * Ersetzt den alten Link-Weg (SelectionController) – gleicher ZIP-Aufbau,
 * aber mit einer eigenen, dauerhaften Anmeldung statt eines befristeten,
 * geteilten Tokens.
 */
final class AlbumistController
{
    public function __construct(private readonly string $locale)
    {
        Albumist::requireLogin($this->locale);
    }

    /* --------------------------------- Liste -------------------------------- */

    public function index(): void
    {
        $rows = [];
        foreach (Galleries::all() as $gallery) {
            $code = (string) ($gallery['code'] ?? '');
            $selection = Galleries::selection($code);
            if (!Galleries::isReady($selection)) {
                continue;
            }

            $rows[] = [
                'gallery' => $gallery,
                'cover'   => Galleries::coverPhoto($gallery, $selection),
                'count'   => count((array) ($selection['picks'] ?? [])),
            ];
        }

        View::page('pages/albumist-list', [
            'locale' => $this->locale,
            'path'   => I18n::path('/albumcu', $this->locale),
            'meta'   => ['title' => 'Albümcü', 'noindex' => true, 'bare' => true],
            'rows'   => $rows,
        ]);
    }

    /* --------------------------------- Akte --------------------------------- */

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $code = Security::clean($params['code'] ?? '', 64);
        $gallery = Galleries::find($code);
        $selection = $gallery === null ? null : Galleries::selection($code);

        if ($gallery === null || !Galleries::isReady($selection)) {
            (new PageController())->notFound($this->locale);
            return;
        }

        View::page('pages/albumist-show', [
            'locale'    => $this->locale,
            'path'      => I18n::path('/albumcu/' . $code, $this->locale),
            'meta'      => ['title' => (string) ($gallery['couple'] ?? ''), 'noindex' => true, 'bare' => true],
            'gallery'   => $gallery,
            'selection' => $selection,
            'photos'    => Galleries::selectedPhotos($gallery, $selection),
            'cover'     => Galleries::coverPhoto($gallery, $selection),
            'code'      => $code,
            'dateLong'  => Dates::long((string) ($gallery['date'] ?? ''), $this->locale),
        ]);
    }

    /** Alles auf einmal – der Grund, warum es diese Seite gibt. @param array<string,string> $params */
    public function zip(array $params): void
    {
        $code = Security::clean($params['code'] ?? '', 64);
        $gallery = Galleries::find($code);
        $selection = $gallery === null ? null : Galleries::selection($code);

        if ($gallery === null || !Galleries::isReady($selection)) {
            (new PageController())->notFound($this->locale);
            return;
        }

        $photos = Galleries::selectedPhotos($gallery, $selection);

        // Nur was wirklich auf der Platte liegt: Platzhalterbilder gehören
        // niemandem und haben im Album nichts zu suchen.
        $files = array_values(array_filter(
            $photos,
            static fn (array $p): bool => $p['original'] !== null
        ));

        if ($files === [] || !class_exists('ZipArchive')) {
            (new PageController())->notFound($this->locale);
            return;
        }

        $zip = new \ZipArchive();
        $temp = tempnam(sys_get_temp_dir(), 'albumcu');
        if ($temp === false || $zip->open($temp, \ZipArchive::OVERWRITE) !== true) {
            (new PageController())->notFound($this->locale);
            return;
        }

        foreach ($files as $file) {
            // Durchnummeriert in der Reihenfolge, die das Paar gesehen hat.
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

    /* --------------------------------- Login --------------------------------- */

    public function logout(): void
    {
        Albumist::logout();
        header('Location: ' . I18n::path('/albumcu', $this->locale), true, 303);
        exit;
    }
}
