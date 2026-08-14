<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Welche Seiten im Adminbereich einen eigenen Titel und Beschreibungstext
 * bekommen – dieselbe Liste, die auch die Sitemap benutzt.
 *
 * Wer eine neue feste Seite anlegt, trägt sie hier ein; Formular, Metadaten
 * und Sitemap lesen alle dieselbe Stelle.
 */
final class Marketing
{
    /** @return list<array{key:string,path:string,label:array<string,string>}> */
    public static function pages(): array
    {
        return [
            ['key' => 'home', 'path' => '/', 'label' => ['de' => 'Startseite', 'tr' => 'Ana sayfa']],
            ['key' => 'leistungen', 'path' => '/leistungen', 'label' => ['de' => 'Leistungen', 'tr' => 'Hizmetler']],
            ['key' => 'preise', 'path' => '/preise', 'label' => ['de' => 'Preise', 'tr' => 'Fiyatlar']],
            ['key' => 'portfolio', 'path' => '/portfolio', 'label' => ['de' => 'Portfolio', 'tr' => 'Portfolyo']],
            ['key' => 'hochzeitslocations', 'path' => '/hochzeitslocations', 'label' => ['de' => 'Locations (Übersicht)', 'tr' => 'Mekânlar (liste)']],
            ['key' => 'regionen', 'path' => '/regionen', 'label' => ['de' => 'Regionen', 'tr' => 'Bölgeler']],
            ['key' => 'ratgeber', 'path' => '/ratgeber', 'label' => ['de' => 'Ratgeber (Übersicht)', 'tr' => 'Rehber (liste)']],
            ['key' => 'ueber-mich', 'path' => '/ueber-mich', 'label' => ['de' => 'Über mich', 'tr' => 'Hakkımda']],
            ['key' => 'kontakt', 'path' => '/kontakt', 'label' => ['de' => 'Kontakt', 'tr' => 'İletişim']],
            ['key' => 'einladung', 'path' => '/einladung', 'label' => ['de' => 'Digitale Einladung', 'tr' => 'Dijital davetiye']],
            ['key' => 'galerie', 'path' => '/galerie', 'label' => ['de' => 'Kundengalerie (Login)', 'tr' => 'Müşteri galerisi (giriş)']],
            ['key' => 'impressum', 'path' => '/impressum', 'label' => ['de' => 'Impressum', 'tr' => 'Impressum']],
            ['key' => 'datenschutz', 'path' => '/datenschutz', 'label' => ['de' => 'Datenschutz', 'tr' => 'Gizlilik']],
            ['key' => 'agb', 'path' => '/agb', 'label' => ['de' => 'AGB', 'tr' => 'AGB']],
        ];
    }
}
