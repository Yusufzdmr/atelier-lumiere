<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\I18n;
use Atelier\Security;
use Atelier\Texts;
use Atelier\View;

/**
 * Der Reiter für die festen Seitentexte.
 *
 * Das sind die Zeilen, die nicht zu einem Inhalt gehören, sondern zur Seite
 * selbst: „Was wir für euch tun“, „Alle Reportagen ansehen“, die Beschriftung
 * eines Formularfeldes. Sie standen bisher nur im Wörterbuch.
 *
 * Bewusst eine Gruppe je Seitenaufruf: dreihundert Texte auf einmal wären
 * weder zu überblicken noch abzuschicken.
 */
final class TextAdminController
{
    private const TAB = '/texte';

    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    public function index(): void
    {
        $de = $this->locale === 'de';
        $group = Security::clean($_GET['gruppe'] ?? '', 40);
        if (!Texts::isGroup($group)) {
            $group = array_key_first(Texts::GROUPS);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            $was = Security::clean($_POST['was'] ?? '', 200);

            // Erst alles Getippte übernehmen, dann das eine Feld zurückholen –
            // der Knopf schickt schliesslich das ganze Formular mit.
            Texts::saveGroup($group, $_POST);

            if (str_starts_with($was, 'reset:')) {
                [$key, $one] = array_pad(explode('|', substr($was, 6), 2), 2, null);
                if (str_starts_with((string) $key, $group . '.')) {
                    Texts::reset((string) $key, I18n::isLocale((string) $one) ? (string) $one : null);
                }
            }

            header('Location: ' . I18n::path('/admin' . self::TAB, $this->locale)
                . '?gruppe=' . rawurlencode($group) . '&gespeichert=ok', true, 303);
            exit;
        }

        // Für die Gruppenleiste: wo wurde schon etwas geändert?
        $groups = [];
        foreach (Texts::GROUPS as $key => $caption) {
            $groups[] = [
                'key'     => $key,
                'label'   => $caption[$this->locale] ?? $caption['de'],
                'changed' => Texts::changedIn($key),
                'active'  => $key === $group,
            ];
        }

        View::page('admin/texts', [
            'layout'   => 'admin/layout',
            'locale'   => $this->locale,
            'path'     => I18n::path('/admin' . self::TAB),
            'current'  => self::TAB,
            'meta'     => ['title' => 'Admin', 'noindex' => true],
            'csrf'     => Security::csrf(),
            'groups'   => $groups,
            'group'    => $group,
            'caption'  => Texts::GROUPS[$group][$this->locale] ?? Texts::GROUPS[$group]['de'],
            'entries'  => Texts::group($group),
            'title'    => $de ? 'Seitentexte' : 'Sayfa metinleri',
            'intro'    => $de
                ? 'Überschriften, Knöpfe und Beschriftungen, die auf jeder Seite gleich stehen. Wer ein Feld leert, bekommt den ursprünglichen Text zurück – verloren geht hier nichts.'
                : 'Her sayfada aynı duran başlıklar, düğmeler ve etiketler. Bir alanı boşaltırsanız ilk metin geri gelir – burada hiçbir şey kaybolmaz.',
        ]);
    }
}
