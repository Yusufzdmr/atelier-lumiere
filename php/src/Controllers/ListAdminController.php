<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Content;
use Atelier\Form;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Invitations;
use Atelier\Lists;
use Atelier\Media;
use Atelier\Security;
use Atelier\View;

/**
 * Die Listenreiter: Leistungen, Städte, Locations, Portfolio, Ratgeber.
 *
 * Alle fünf tun dasselbe – Einträge anlegen, ändern, verschieben, löschen –
 * und unterscheiden sich nur in den Feldern. Deshalb beschreibt jede Methode
 * nur ihre Liste, und handle() erledigt Formular, Speichern und Umleitung.
 */
final class ListAdminController
{
    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    private function de(): bool
    {
        return $this->locale === 'de';
    }

    /* ---------------------------- Leistungen & Ablauf ----------------------- */

    public function services(): void
    {
        $de = $this->de();

        $services = [
            'key'     => 'services',
            'label'   => $de ? 'Leistungen' : 'Hizmetler',
            'intro'   => $de
                ? 'Erscheinen auf der Leistungsseite und als Kacheln auf der Startseite.'
                : 'Hizmetler sayfasında ve ana sayfada kutucuk olarak görünür.',
            'heading' => fn (array $item): string => $this->pick($item, 'title', $de ? 'Leistung' : 'Hizmet'),
            'note'    => fn (array $item): string => '#' . (string) ($item['slug'] ?? ''),
            'view'    => fn (array $item): string => I18n::path('/leistungen', $this->locale) . '#' . (string) ($item['slug'] ?? ''),
            'delete'  => [
                'label'   => $de ? 'Diese Leistung löschen' : 'Bu hizmeti sil',
                'confirm' => $de ? 'Diese Leistung wirklich löschen?' : 'Bu hizmet silinsin mi?',
            ],
            'sections' => fn (int $i): array => [[
                'fields' => [
                    ['path' => "services.$i.title.de", 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                    ['path' => "services.$i.title.tr", 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                    ['path' => "services.$i.short.de", 'label' => $de ? 'Kurztext (DE)' : 'Kısa metin (DE)', 'type' => 'area', 'rows' => 2],
                    ['path' => "services.$i.short.tr", 'label' => $de ? 'Kurztext (TR)' : 'Kısa metin (TR)', 'type' => 'area', 'rows' => 2],
                    ['path' => "services.$i.body.de", 'label' => $de ? 'Fließtext (DE) – Leerzeile trennt Absätze' : 'Uzun metin (DE) – boş satır paragraf ayırır', 'type' => 'paras', 'rows' => 6, 'wide' => true, 'max' => 12000],
                    ['path' => "services.$i.body.tr", 'label' => $de ? 'Fließtext (TR)' : 'Uzun metin (TR)', 'type' => 'paras', 'rows' => 6, 'wide' => true, 'max' => 12000],
                    ['path' => "services.$i.bullets.de", 'label' => $de ? 'Enthalten (DE) – eine Zeile je Punkt' : 'İçerik (DE) – her satıra bir madde', 'type' => 'lines', 'rows' => 5],
                    ['path' => "services.$i.bullets.tr", 'label' => $de ? 'Enthalten (TR)' : 'İçerik (TR)', 'type' => 'lines', 'rows' => 5],
                    ['path' => "services.$i.slug", 'label' => $de ? 'Anker (URL-Teil)' : 'Bağlantı adı (URL)', 'hint' => $de ? 'Ändern macht alte Links auf diesen Abschnitt ungültig.' : 'Değiştirmek bu bölüme giden eski bağlantıları bozar.'],
                    ['path' => "services.$i.seed", 'label' => $de ? 'Bild-Kennung' : 'Görsel anahtarı', 'hint' => $de ? 'Platzhalter-Name oder eine Bildadresse.' : 'Temsili görsel adı ya da bir görsel adresi.'],
                ],
            ]],
            'add' => [
                'title'  => $de ? 'Neue Leistung' : 'Yeni hizmet',
                'button' => $de ? 'Leistung anlegen' : 'Hizmet oluştur',
                'fields' => [
                    ['path' => 'title_de', 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                    ['path' => 'title_tr', 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                    ['path' => 'slug', 'label' => $de ? 'Anker (leer = aus dem Titel)' : 'Bağlantı adı (boş = başlıktan)'],
                ],
                'make' => function (): ?array {
                    $titleDe = Security::clean($_POST['title_de'] ?? '', 120);
                    $titleTr = Security::clean($_POST['title_tr'] ?? '', 120);
                    if ($titleDe === '' && $titleTr === '') {
                        return null;
                    }
                    $title = Lists::l10n($titleDe, $titleTr);
                    $slug = Lists::freeSlug('services', Security::clean($_POST['slug'] ?? '', 60) ?: $title['de'], 'leistung');

                    return [
                        'slug'    => $slug,
                        'seed'    => 'lum-service-' . $slug,
                        'title'   => $title,
                        'short'   => ['de' => '', 'tr' => ''],
                        'body'    => ['de' => [], 'tr' => []],
                        'bullets' => ['de' => [], 'tr' => []],
                    ];
                },
            ],
        ];

        $process = [
            'key'     => 'process',
            'label'   => $de ? 'Ablauf in Schritten' : 'Süreç adımları',
            'intro'   => $de
                ? 'Der Weg von der Anfrage bis zur Galerie – erscheint unter den Leistungen.'
                : 'Talepten galeriye kadar olan yol – hizmetlerin altında görünür.',
            'heading' => fn (array $item): string => (string) ($item['step'] ?? '') . ' · ' . $this->pick($item, 'title', $de ? 'Schritt' : 'Adım'),
            'note'    => fn (array $item): string => '',
            'view'    => fn (array $item): string => '',
            'delete'  => [
                'label'   => $de ? 'Diesen Schritt löschen' : 'Bu adımı sil',
                'confirm' => $de ? 'Diesen Schritt wirklich löschen?' : 'Bu adım silinsin mi?',
            ],
            'sections' => fn (int $i): array => [[
                'fields' => [
                    ['path' => "process.$i.step", 'label' => $de ? 'Nummer' : 'Numara'],
                    ['path' => "process.$i.title.de", 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                    ['path' => "process.$i.title.tr", 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                    ['path' => "process.$i.text.de", 'label' => $de ? 'Text (DE)' : 'Metin (DE)', 'type' => 'area', 'rows' => 3, 'wide' => true],
                    ['path' => "process.$i.text.tr", 'label' => $de ? 'Text (TR)' : 'Metin (TR)', 'type' => 'area', 'rows' => 3, 'wide' => true],
                ],
            ]],
            'add' => [
                'title'  => $de ? 'Neuer Schritt' : 'Yeni adım',
                'button' => $de ? 'Schritt anlegen' : 'Adım oluştur',
                'fields' => [
                    ['path' => 'title_de', 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                    ['path' => 'title_tr', 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                ],
                'make' => function (): ?array {
                    $titleDe = Security::clean($_POST['title_de'] ?? '', 120);
                    $titleTr = Security::clean($_POST['title_tr'] ?? '', 120);
                    if ($titleDe === '' && $titleTr === '') {
                        return null;
                    }
                    // Nummer fortlaufend: 01, 02, 03 …
                    $step = str_pad((string) (count(Lists::all('process')) + 1), 2, '0', STR_PAD_LEFT);

                    return [
                        'step'  => $step,
                        'title' => Lists::l10n($titleDe, $titleTr),
                        'text'  => ['de' => '', 'tr' => ''],
                    ];
                },
            ],
        ];

        $this->handle('/leistungen', $de ? 'Leistungen & Ablauf' : 'Hizmetler & süreç', $de
            ? 'Was angeboten wird und wie die Zusammenarbeit abläuft.'
            : 'Neler sunuluyor ve iş birliği nasıl ilerliyor.', [$services, $process]);
    }

    /* --------------------------------- Städte -------------------------------- */

    public function cities(): void
    {
        $de = $this->de();

        $spec = [
            'key'     => 'cities',
            'heading' => fn (array $item): string => (string) ($item['name'] ?? ''),
            'note'    => fn (array $item): string => '/' . $this->locale . '/hochzeitsfotograf/' . (string) ($item['slug'] ?? ''),
            'view'    => fn (array $item): string => I18n::path('/hochzeitsfotograf/' . (string) ($item['slug'] ?? ''), $this->locale),
            'delete'  => [
                'label'   => $de ? 'Diese Stadtseite löschen' : 'Bu şehir sayfasını sil',
                'confirm' => $de ? 'Stadtseite wirklich löschen? Die Adresse ist danach nicht mehr erreichbar.' : 'Şehir sayfası silinsin mi? Adres artık açılmaz.',
            ],
            'sections' => fn (int $i): array => [
                [
                    'title'  => $de ? 'Kopf' : 'Üst bilgi',
                    'grid'   => 'md:grid-cols-3',
                    'fields' => [
                        ['path' => "cities.$i.name", 'label' => $de ? 'Name' : 'Ad'],
                        ['path' => "cities.$i.kreis.de", 'label' => $de ? 'Landkreis (DE)' : 'İlçe (DE)'],
                        ['path' => "cities.$i.kreis.tr", 'label' => $de ? 'Landkreis (TR)' : 'İlçe (TR)'],
                        ['path' => "cities.$i.drive.de", 'label' => $de ? 'Anfahrt (DE)' : 'Ulaşım (DE)'],
                        ['path' => "cities.$i.drive.tr", 'label' => $de ? 'Anfahrt (TR)' : 'Ulaşım (TR)'],
                    ],
                ],
                [
                    'title'  => $de ? 'Texte' : 'Metinler',
                    'hint'   => $de
                        ? 'Jede Stadt braucht eigene Sätze. Kopierte Stadtseiten wertet Google als Türseiten ab.'
                        : 'Her şehrin kendi cümleleri olmalı. Kopya şehir sayfalarını Google değersiz sayar.',
                    'fields' => [
                        ['path' => "cities.$i.lead.de", 'label' => $de ? 'Einleitung (DE)' : 'Giriş (DE)', 'type' => 'area', 'rows' => 3],
                        ['path' => "cities.$i.lead.tr", 'label' => $de ? 'Einleitung (TR)' : 'Giriş (TR)', 'type' => 'area', 'rows' => 3],
                        ['path' => "cities.$i.body.de", 'label' => $de ? 'Fließtext (DE) – Leerzeile trennt Absätze' : 'Uzun metin (DE) – boş satır paragraf ayırır', 'type' => 'paras', 'rows' => 8, 'wide' => true, 'max' => 16000],
                        ['path' => "cities.$i.body.tr", 'label' => $de ? 'Fließtext (TR)' : 'Uzun metin (TR)', 'type' => 'paras', 'rows' => 8, 'wide' => true, 'max' => 16000],
                    ],
                ],
                [
                    'title'  => $de ? 'Fotospots und Fragen' : 'Çekim noktaları ve sorular',
                    'hint'   => $de
                        ? 'Fotospots je Zeile: Name | Beschreibung. Fragen je Zeile: Frage | Antwort. Bearbeitet wird immer die gerade gewählte Sprache.'
                        : 'Çekim noktası her satır: Ad | Açıklama. Sorular her satır: Soru | Cevap. Her zaman seçili dil düzenlenir.',
                    'fields' => [
                        [
                            'path' => "cities.$i.spots", 'label' => $de ? 'Fotospots' : 'Çekim noktaları',
                            'type' => 'rows', 'rows' => 5, 'wide' => true, 'max' => 8000,
                            'cols' => [['key' => 'name'], ['key' => 'note', 'bilingual' => true]],
                        ],
                        [
                            'path' => "cities.$i.faq", 'label' => $de ? 'Häufige Fragen' : 'Sık sorulanlar',
                            'type' => 'rows', 'rows' => 5, 'wide' => true, 'max' => 8000,
                            'cols' => [['key' => 'q', 'bilingual' => true], ['key' => 'a', 'bilingual' => true]],
                        ],
                    ],
                ],
                [
                    'title'  => $de ? 'Verknüpfungen' : 'Bağlantılar',
                    'hint'   => $de
                        ? 'Je Zeile eine Adresse (der Teil hinter dem letzten Schrägstrich). Locations und Nachbarstädte werden auf der Seite verlinkt.'
                        : 'Her satıra bir adres (son eğik çizgiden sonraki kısım). Mekânlar ve komşu şehirler sayfada bağlanır.',
                    'fields' => [
                        ['path' => "cities.$i.venues", 'label' => $de ? 'Locations' : 'Mekânlar', 'type' => 'lines', 'rows' => 4],
                        ['path' => "cities.$i.neighbours", 'label' => $de ? 'Nachbarstädte' : 'Komşu şehirler', 'type' => 'lines', 'rows' => 4],
                    ],
                ],
            ],
            'add' => [
                'title'  => $de ? 'Neue Stadtseite' : 'Yeni şehir sayfası',
                'button' => $de ? 'Stadtseite anlegen' : 'Şehir sayfası oluştur',
                'hint'   => $de
                    ? 'Die Seite entsteht leer – die Texte kommen danach oben in der Liste dazu.'
                    : 'Sayfa boş oluşur – metinler sonra yukarıdaki listeden girilir.',
                'fields' => [
                    ['path' => 'name', 'label' => $de ? 'Name' : 'Ad'],
                    ['path' => 'slug', 'label' => $de ? 'Adresse (leer = aus dem Namen)' : 'Adres (boş = addan)'],
                    ['path' => 'drive_de', 'label' => $de ? 'Anfahrt (DE)' : 'Ulaşım (DE)'],
                ],
                'make' => function (): ?array {
                    $name = Security::clean($_POST['name'] ?? '', 80);
                    if ($name === '') {
                        return null;
                    }
                    $drive = Security::clean($_POST['drive_de'] ?? '', 80);

                    return [
                        'slug'       => Lists::freeSlug('cities', Security::clean($_POST['slug'] ?? '', 80) ?: $name, 'stadt'),
                        'name'       => $name,
                        'kreis'      => ['de' => '', 'tr' => ''],
                        'drive'      => Lists::l10n($drive, $drive),
                        'lead'       => ['de' => '', 'tr' => ''],
                        'body'       => ['de' => [], 'tr' => []],
                        'spots'      => [],
                        'faq'        => [],
                        'venues'     => [],
                        'neighbours' => [],
                    ];
                },
            ],
        ];

        $this->handle('/staedte', $de ? 'Städte' : 'Şehirler', $de
            ? 'Jede Stadt bekommt eine eigene Seite („Hochzeitsfotograf Stuttgart“). Die Texte müssen sich unterscheiden.'
            : 'Her şehir kendi sayfasını alır („Stuttgart düğün fotoğrafçısı“). Metinler birbirinden farklı olmalı.', [$spec]);
    }

    /* -------------------------------- Locations ------------------------------ */

    public function venues(): void
    {
        $de = $this->de();
        $cityOptions = $this->cityOptions();

        $spec = [
            'key'     => 'venues',
            'heading' => fn (array $item): string => (string) ($item['name'] ?? ''),
            'note'    => fn (array $item): string => (string) ($item['city'] ?? '') . ' · /' . (string) ($item['slug'] ?? ''),
            'view'    => fn (array $item): string => I18n::path('/hochzeitslocations/' . (string) ($item['slug'] ?? ''), $this->locale),
            'delete'  => [
                'label'   => $de ? 'Diese Location löschen' : 'Bu mekânı sil',
                'confirm' => $de ? 'Location wirklich löschen?' : 'Mekân silinsin mi?',
            ],
            'sections' => fn (int $i): array => [
                [
                    'title'  => $de ? 'Kopf' : 'Üst bilgi',
                    'grid'   => 'md:grid-cols-3',
                    'fields' => [
                        ['path' => "venues.$i.name", 'label' => $de ? 'Name' : 'Ad'],
                        ['path' => "venues.$i.city", 'label' => $de ? 'Ort (Anzeige)' : 'Şehir (görünen)'],
                        ['path' => "venues.$i.citySlug", 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                        ['path' => "venues.$i.address", 'label' => $de ? 'Anschrift' : 'Adres', 'wide' => true],
                        ['path' => "venues.$i.type.de", 'label' => $de ? 'Art (DE)' : 'Tür (DE)'],
                        ['path' => "venues.$i.type.tr", 'label' => $de ? 'Art (TR)' : 'Tür (TR)'],
                        ['path' => "venues.$i.capacity.de", 'label' => $de ? 'Kapazität (DE)' : 'Kapasite (DE)'],
                        ['path' => "venues.$i.capacity.tr", 'label' => $de ? 'Kapazität (TR)' : 'Kapasite (TR)'],
                    ],
                ],
                [
                    'title'  => $de ? 'Texte' : 'Metinler',
                    'fields' => [
                        ['path' => "venues.$i.lead.de", 'label' => $de ? 'Einleitung (DE)' : 'Giriş (DE)', 'type' => 'area', 'rows' => 3],
                        ['path' => "venues.$i.lead.tr", 'label' => $de ? 'Einleitung (TR)' : 'Giriş (TR)', 'type' => 'area', 'rows' => 3],
                        ['path' => "venues.$i.body.de", 'label' => $de ? 'Fließtext (DE)' : 'Uzun metin (DE)', 'type' => 'paras', 'rows' => 7, 'wide' => true, 'max' => 16000],
                        ['path' => "venues.$i.body.tr", 'label' => $de ? 'Fließtext (TR)' : 'Uzun metin (TR)', 'type' => 'paras', 'rows' => 7, 'wide' => true, 'max' => 16000],
                    ],
                ],
                [
                    'title'  => $de ? 'Licht, Regeln, Fotostellen' : 'Işık, kurallar, çekim yerleri',
                    'hint'   => $de
                        ? 'Das ist der Teil, den Paare wirklich suchen: wann das Licht steht und was die Location erlaubt.'
                        : 'Çiftlerin asıl aradığı kısım: ışığın ne zaman iyi olduğu ve mekânın neye izin verdiği.',
                    'fields' => [
                        ['path' => "venues.$i.light.de", 'label' => $de ? 'Licht (DE)' : 'Işık (DE)', 'type' => 'area', 'rows' => 3],
                        ['path' => "venues.$i.light.tr", 'label' => $de ? 'Licht (TR)' : 'Işık (TR)', 'type' => 'area', 'rows' => 3],
                        ['path' => "venues.$i.rules.de", 'label' => $de ? 'Regeln (DE) – eine Zeile je Punkt' : 'Kurallar (DE) – her satıra bir madde', 'type' => 'lines', 'rows' => 4],
                        ['path' => "venues.$i.rules.tr", 'label' => $de ? 'Regeln (TR)' : 'Kurallar (TR)', 'type' => 'lines', 'rows' => 4],
                        ['path' => "venues.$i.spots.de", 'label' => $de ? 'Fotostellen (DE)' : 'Çekim yerleri (DE)', 'type' => 'lines', 'rows' => 4],
                        ['path' => "venues.$i.spots.tr", 'label' => $de ? 'Fotostellen (TR)' : 'Çekim yerleri (TR)', 'type' => 'lines', 'rows' => 4],
                    ],
                ],
                [
                    'title'  => $de ? 'Zeitplan und Fragen' : 'Zaman planı ve sorular',
                    'hint'   => $de
                        ? 'Zeitplan je Zeile: Uhrzeit | was passiert. Fragen je Zeile: Frage | Antwort.'
                        : 'Zaman planı her satır: Saat | ne oluyor. Sorular her satır: Soru | Cevap.',
                    'fields' => [
                        [
                            'path' => "venues.$i.timing", 'label' => $de ? 'Zeitplan' : 'Zaman planı',
                            'type' => 'rows', 'rows' => 6, 'wide' => true, 'max' => 8000,
                            'cols' => [['key' => 'time'], ['key' => 'what', 'bilingual' => true]],
                        ],
                        [
                            'path' => "venues.$i.faq", 'label' => $de ? 'Häufige Fragen' : 'Sık sorulanlar',
                            'type' => 'rows', 'rows' => 5, 'wide' => true, 'max' => 8000,
                            'cols' => [['key' => 'q', 'bilingual' => true], ['key' => 'a', 'bilingual' => true]],
                        ],
                    ],
                ],
            ],
            'add' => [
                'title'  => $de ? 'Neue Location' : 'Yeni mekân',
                'button' => $de ? 'Location anlegen' : 'Mekân oluştur',
                'fields' => [
                    ['path' => 'name', 'label' => $de ? 'Name' : 'Ad'],
                    ['path' => 'slug', 'label' => $de ? 'Adresse (leer = aus dem Namen)' : 'Adres (boş = addan)'],
                    ['path' => 'citySlug', 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                    ['path' => 'city', 'label' => $de ? 'Ort (Anzeige)' : 'Şehir (görünen)'],
                    ['path' => 'address', 'label' => $de ? 'Anschrift' : 'Adres', 'wide' => true],
                ],
                'make' => function (): ?array {
                    $name = Security::clean($_POST['name'] ?? '', 120);
                    if ($name === '') {
                        return null;
                    }
                    $citySlug = Security::clean($_POST['citySlug'] ?? '', 80);
                    $city = Security::clean($_POST['city'] ?? '', 80);
                    if ($city === '') {
                        $found = Content::city($citySlug);
                        $city = (string) ($found['name'] ?? '');
                    }

                    return [
                        'slug'     => Lists::freeSlug('venues', Security::clean($_POST['slug'] ?? '', 120) ?: $name, 'location'),
                        'name'     => $name,
                        'city'     => $city,
                        'citySlug' => $citySlug,
                        'address'  => Security::clean($_POST['address'] ?? '', 200),
                        'type'     => ['de' => '', 'tr' => ''],
                        'capacity' => ['de' => '', 'tr' => ''],
                        'lead'     => ['de' => '', 'tr' => ''],
                        'body'     => ['de' => [], 'tr' => []],
                        'light'    => ['de' => '', 'tr' => ''],
                        'rules'    => ['de' => [], 'tr' => []],
                        'spots'    => ['de' => [], 'tr' => []],
                        'timing'   => [],
                        'faq'      => [],
                    ];
                },
            ],
        ];

        $this->handle('/locations', $de ? 'Locations' : 'Mekânlar', $de
            ? 'Eine Seite je Location. Licht, Regeln und Zeitplan sind das, wonach Paare wirklich suchen.'
            : 'Her mekân için bir sayfa. Işık, kurallar ve zaman planı çiftlerin asıl aradığı bilgilerdir.', [$spec]);
    }

    /* -------------------------------- Portfolio ------------------------------ */

    public function stories(): void
    {
        $de = $this->de();
        $cityOptions = $this->cityOptions();
        $venueOptions = $this->venueOptions();

        $spec = [
            'key'     => 'stories',
            'heading' => fn (array $item): string => (string) ($item['couple'] ?? ''),
            'note'    => function (array $item) use ($de): string {
                $uploads = count((array) ($item['uploads'] ?? []));
                return $this->pick($item, 'venue', '') . ' · '
                    . ($uploads > 0
                        ? $uploads . ' ' . ($de ? 'eigene Bilder' : 'kendi fotoğrafınız')
                        : ($de ? 'Platzhalter' : 'temsili görsel'));
            },
            'view'   => fn (array $item): string => I18n::path('/portfolio/' . (string) ($item['slug'] ?? ''), $this->locale),
            'delete' => [
                'label'   => $de ? 'Diese Reportage löschen' : 'Bu çekimi sil',
                'confirm' => $de ? 'Reportage samt hochgeladenen Bildern löschen?' : 'Çekim, yüklenen fotoğraflarla birlikte silinsin mi?',
            ],
            'photos' => fn (array $item): array => [
                'hint' => $de
                    ? 'Mehrere Bilder auf einmal möglich. Sobald eigene Bilder da sind, verschwinden die Platzhalter.'
                    : 'Aynı anda birden çok fotoğraf seçebilirsiniz. Kendi fotoğraflarınız yüklenince temsili görseller kaybolur.',
                'list' => $this->photoList($item),
            ],
            'sections' => fn (int $i): array => [
                [
                    'title'  => $de ? 'Eckdaten' : 'Künye',
                    'grid'   => 'md:grid-cols-4',
                    'fields' => [
                        ['path' => "stories.$i.couple", 'label' => $de ? 'Paar' : 'Çift'],
                        ['path' => "stories.$i.guests", 'label' => $de ? 'Gäste' : 'Kişi'],
                        ['path' => "stories.$i.citySlug", 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                        ['path' => "stories.$i.venueSlug", 'label' => 'Location', 'type' => 'select', 'options' => $venueOptions],
                        ['path' => "stories.$i.venue.de", 'label' => $de ? 'Location-Text (DE)' : 'Mekân yazısı (DE)'],
                        ['path' => "stories.$i.venue.tr", 'label' => $de ? 'Location-Text (TR)' : 'Mekân yazısı (TR)'],
                        ['path' => "stories.$i.month.de", 'label' => $de ? 'Monat (DE)' : 'Ay (DE)'],
                        ['path' => "stories.$i.month.tr", 'label' => $de ? 'Monat (TR)' : 'Ay (TR)'],
                        [
                            'path' => "stories.$i.videoUrl", 'label' => $de ? 'Hochzeitsfilm (YouTube / Vimeo)' : 'Düğün filmi (YouTube / Vimeo)', 'wide' => true,
                            'hint' => $de
                                ? 'Link einfügen – der Film erscheint oben in der Reportage. Leer lassen, wenn es keinen gibt.'
                                : 'Bağlantıyı yapıştırın – film hikâyenin başında görünür. Yoksa boş bırakın.',
                        ],
                    ],
                ],
                [
                    'title'  => $de ? 'Texte' : 'Metinler',
                    'fields' => [
                        ['path' => "stories.$i.intro.de", 'label' => $de ? 'Einleitung (DE)' : 'Giriş (DE)', 'type' => 'area', 'rows' => 3],
                        ['path' => "stories.$i.intro.tr", 'label' => $de ? 'Einleitung (TR)' : 'Giriş (TR)', 'type' => 'area', 'rows' => 3],
                        ['path' => "stories.$i.body.de", 'label' => $de ? 'Text (DE) – Leerzeile trennt Absätze' : 'Metin (DE) – boş satır paragraf ayırır', 'type' => 'paras', 'rows' => 6, 'wide' => true, 'max' => 16000],
                        ['path' => "stories.$i.body.tr", 'label' => $de ? 'Text (TR)' : 'Metin (TR)', 'type' => 'paras', 'rows' => 6, 'wide' => true, 'max' => 16000],
                        ['path' => "stories.$i.quote.de", 'label' => $de ? 'Zitat des Paares (DE)' : 'Çiftin yorumu (DE)', 'type' => 'area', 'rows' => 2],
                        ['path' => "stories.$i.quote.tr", 'label' => $de ? 'Zitat des Paares (TR)' : 'Çiftin yorumu (TR)', 'type' => 'area', 'rows' => 2],
                    ],
                ],
            ],
            'add' => [
                'title'  => $de ? 'Neue Reportage' : 'Yeni çekim',
                'button' => $de ? 'Reportage anlegen' : 'Çekim oluştur',
                'fields' => [
                    ['path' => 'couple', 'label' => $de ? 'Paar' : 'Çift'],
                    ['path' => 'slug', 'label' => $de ? 'Adresse (leer = aus dem Namen)' : 'Adres (boş = addan)'],
                    ['path' => 'citySlug', 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                    ['path' => 'venueSlug', 'label' => 'Location', 'type' => 'select', 'options' => $venueOptions],
                    ['path' => 'guests', 'label' => $de ? 'Gäste' : 'Kişi'],
                    ['path' => 'venue_de', 'label' => $de ? 'Location-Text' : 'Mekân yazısı'],
                ],
                'make' => function (): ?array {
                    $couple = Security::clean($_POST['couple'] ?? '', 120);
                    if ($couple === '') {
                        return null;
                    }
                    $slug = Lists::freeSlug('stories', Security::clean($_POST['slug'] ?? '', 120) ?: $couple, 'reportage');
                    $venueText = Security::clean($_POST['venue_de'] ?? '', 160);

                    return [
                        'slug'      => $slug,
                        'couple'    => $couple,
                        'guests'    => Security::clean($_POST['guests'] ?? '', 20),
                        'citySlug'  => Security::clean($_POST['citySlug'] ?? '', 80),
                        'venueSlug' => Security::clean($_POST['venueSlug'] ?? '', 120),
                        'venue'     => Lists::l10n($venueText, $venueText),
                        'month'     => ['de' => '', 'tr' => ''],
                        'intro'     => ['de' => '', 'tr' => ''],
                        'body'      => ['de' => [], 'tr' => []],
                        'quote'     => ['de' => '', 'tr' => ''],
                        // Bis eigene Bilder da sind, sechs Platzhalter – sonst
                        // wäre die neue Seite eine leere Fläche.
                        'seeds'     => array_map(static fn (int $n): string => 'story-' . $slug . '-' . $n, range(1, 6)),
                        'uploads'   => [],
                    ];
                },
            ],
        ];

        $this->handle('/portfolio', $de ? 'Portfolio' : 'Portfolyo', $de
            ? 'Jede Reportage ist eine eigene Seite und verlinkt zur Location und zur Stadtseite – das stärkt beide.'
            : 'Her çekim kendi sayfasıdır ve mekân ile şehir sayfasına bağlanır – ikisini birden güçlendirir.', [$spec]);
    }

    /* --------------------------------- Ratgeber ------------------------------ */

    public function posts(): void
    {
        $de = $this->de();
        $cityOptions = $this->cityOptions();
        $venueOptions = $this->venueOptions();

        $spec = [
            'key'     => 'posts',
            'heading' => fn (array $item): string => $this->pick($item, 'title', $de ? 'Beitrag' : 'Yazı'),
            'note'    => fn (array $item): string => (string) ($item['date'] ?? ''),
            'view'    => fn (array $item): string => I18n::path('/ratgeber/' . (string) ($item['slug'] ?? ''), $this->locale),
            'delete'  => [
                'label'   => $de ? 'Diesen Beitrag löschen' : 'Bu yazıyı sil',
                'confirm' => $de ? 'Beitrag wirklich löschen?' : 'Yazı silinsin mi?',
            ],
            'photos' => fn (array $item): array => [
                'hint' => $de
                    ? 'Das erste Bild ist das Titelbild des Beitrags, das zweite steht im Text.'
                    : 'İlk fotoğraf yazının kapağı, ikincisi metnin içinde görünür.',
                'list' => $this->photoList($item),
            ],
            'sections' => fn (int $i): array => [
                [
                    'title'  => $de ? 'Kopf' : 'Üst bilgi',
                    'grid'   => 'md:grid-cols-3',
                    'fields' => [
                        ['path' => "posts.$i.title.de", 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                        ['path' => "posts.$i.title.tr", 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                        ['path' => "posts.$i.date", 'label' => $de ? 'Datum (JJJJ-MM-TT)' : 'Tarih (YYYY-AA-GG)', 'hint' => $de ? 'Bestimmt die Reihenfolge im Ratgeber.' : 'Rehberdeki sırayı belirler.'],
                        ['path' => "posts.$i.citySlug", 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                        ['path' => "posts.$i.venueSlug", 'label' => 'Location', 'type' => 'select', 'options' => $venueOptions],
                        ['path' => "posts.$i.seed", 'label' => $de ? 'Bild-Kennung' : 'Görsel anahtarı', 'hint' => $de ? 'Gilt nur, solange kein eigenes Bild hochgeladen ist.' : 'Yalnızca kendi fotoğrafınız yokken geçerlidir.'],
                    ],
                ],
                [
                    'title'  => $de ? 'Texte' : 'Metinler',
                    'fields' => [
                        ['path' => "posts.$i.excerpt.de", 'label' => $de ? 'Anriss (DE)' : 'Özet (DE)', 'type' => 'area', 'rows' => 3],
                        ['path' => "posts.$i.excerpt.tr", 'label' => $de ? 'Anriss (TR)' : 'Özet (TR)', 'type' => 'area', 'rows' => 3],
                        ['path' => "posts.$i.body.de", 'label' => $de ? 'Beitrag (DE) – Leerzeile trennt Absätze' : 'Yazı (DE) – boş satır paragraf ayırır', 'type' => 'paras', 'rows' => 14, 'wide' => true, 'max' => 40000],
                        ['path' => "posts.$i.body.tr", 'label' => $de ? 'Beitrag (TR)' : 'Yazı (TR)', 'type' => 'paras', 'rows' => 14, 'wide' => true, 'max' => 40000],
                    ],
                ],
                [
                    'title'  => $de ? 'Häufige Fragen' : 'Sık sorulanlar',
                    'hint'   => $de ? 'Je Zeile: Frage | Antwort. Diese Fragen erscheinen auch bei Google.' : 'Her satır: Soru | Cevap. Bu sorular Google’da da görünür.',
                    'fields' => [[
                        'path' => "posts.$i.faq", 'label' => 'FAQ', 'type' => 'rows', 'rows' => 5, 'wide' => true, 'max' => 8000,
                        'cols' => [['key' => 'q', 'bilingual' => true], ['key' => 'a', 'bilingual' => true]],
                    ]],
                ],
            ],
            'add' => [
                'title'  => $de ? 'Neuer Beitrag' : 'Yeni yazı',
                'button' => $de ? 'Beitrag anlegen' : 'Yazı oluştur',
                'fields' => [
                    ['path' => 'title_de', 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)'],
                    ['path' => 'title_tr', 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                    ['path' => 'slug', 'label' => $de ? 'Adresse (leer = aus dem Titel)' : 'Adres (boş = başlıktan)'],
                    ['path' => 'date', 'label' => $de ? 'Datum (leer = heute)' : 'Tarih (boş = bugün)'],
                    ['path' => 'citySlug', 'label' => $de ? 'Stadtseite' : 'Şehir sayfası', 'type' => 'select', 'options' => $cityOptions],
                ],
                'make' => function (): ?array {
                    $titleDe = Security::clean($_POST['title_de'] ?? '', 160);
                    $titleTr = Security::clean($_POST['title_tr'] ?? '', 160);
                    if ($titleDe === '' && $titleTr === '') {
                        return null;
                    }
                    $title = Lists::l10n($titleDe, $titleTr);
                    $slug = Lists::freeSlug('posts', Security::clean($_POST['slug'] ?? '', 160) ?: $title['de'], 'beitrag');
                    $date = Security::clean($_POST['date'] ?? '', 10);

                    return [
                        'slug'      => $slug,
                        'title'     => $title,
                        'date'      => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d'),
                        'seed'      => 'lum-blog-' . $slug,
                        'citySlug'  => Security::clean($_POST['citySlug'] ?? '', 80),
                        'venueSlug' => '',
                        'excerpt'   => ['de' => '', 'tr' => ''],
                        'body'      => ['de' => [], 'tr' => []],
                        'faq'       => [],
                        'uploads'   => [],
                    ];
                },
            ],
        ];

        $this->handle('/ratgeber', $de ? 'Ratgeber' : 'Rehber', $de
            ? 'Beiträge, die Paare vor der Anfrage lesen. Sie bringen Besucher und verlinken auf Städte und Locations.'
            : 'Çiftlerin talep göndermeden önce okuduğu yazılar. Ziyaretçi getirir, şehir ve mekân sayfalarına bağlanır.', [$spec]);
    }

    /* --------------------------------- Gerüst -------------------------------- */

    /**
     * Formular anzeigen, Änderung ausführen, danach umleiten.
     *
     * @param list<array<string,mixed>> $specs eine oder mehrere Listen auf einer Seite
     */
    private function handle(string $tab, string $title, string $intro, array $specs): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();
            $this->apply($tab, $specs);
        }

        $blocks = [];
        foreach ($specs as $spec) {
            $items = Lists::all($spec['key']);
            $entries = [];

            foreach ($items as $i => $item) {
                $entries[] = [
                    'index'       => $i,
                    'heading'     => ($spec['heading'])($item),
                    'note'        => ($spec['note'])($item),
                    'view'        => ($spec['view'])($item),
                    'sections'    => ($spec['sections'])($i, $item),
                    'photos'      => isset($spec['photos']) ? ($spec['photos'])($item) : null,
                    'deleteLabel' => $spec['delete']['label'],
                    'confirm'     => $spec['delete']['confirm'],
                ];
            }

            $blocks[] = [
                'key'   => $spec['key'],
                'label' => $spec['label'] ?? '',
                'intro' => $spec['intro'] ?? '',
                'count' => count($items),
                'items' => $entries,
                'add'   => $spec['add'] ?? null,
            ];
        }

        View::page('admin/list', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . $tab),
            'current' => $tab,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'title'   => $title,
            'intro'   => $intro,
            'blocks'    => $blocks,
            'data'      => Content::all(),
            'originals' => Content::original(),
        ]);
    }

    /**
     * Die abgeschickte Änderung ausführen. Danach wird immer umgeleitet –
     * sonst legt ein Neuladen denselben Eintrag zweimal an.
     *
     * @param list<array<string,mixed>> $specs
     */
    private function apply(string $tab, array $specs): void
    {
        $key = Security::clean($_POST['liste'] ?? '', 40);
        $spec = null;
        foreach ($specs as $candidate) {
            if ($candidate['key'] === $key) {
                $spec = $candidate;
                break;
            }
        }

        if ($spec === null) {
            Admin::back($this->locale, $tab);
        }

        $was = Security::clean($_POST['was'] ?? '', 200);

        if ($was === 'add') {
            $item = isset($spec['add']['make']) ? ($spec['add']['make'])() : null;
            if ($item !== null) {
                Lists::add($key, $item);
            }
            Admin::back($this->locale, $tab);
        }

        $index = Lists::index($key, $_POST['index'] ?? '');
        if ($index === null) {
            Admin::back($this->locale, $tab);
        }

        // „zurücksetzen“ schickt das ganze Formular mit: erst alles Getippte
        // übernehmen, dann das eine Feld zurückholen.
        if (str_starts_with($was, 'reset:')) {
            $this->save($spec, $index);
            $this->reset($spec, $index, substr($was, 6));
            Admin::back($this->locale, $tab);
        }

        match ($was) {
            'save' => $this->save($spec, $index),
            'up'   => Lists::move($key, $index, -1),
            'down' => Lists::move($key, $index, 1),
            'delete' => $this->delete($key, $index),
            'photos-add' => Lists::addUploads($key, $index, Media::storeMany('fotos', $this->folder($key, $index), 40)),
            'photo-delete' => Lists::removeUpload($key, $index, (int) Security::clean($_POST['foto'] ?? '', 6)),
            default => null,
        };

        Admin::back($this->locale, $tab);
    }

    /** @param array<string,mixed> $spec */
    private function save(array $spec, int $index): void
    {
        $item = Lists::item($spec['key'], $index);
        if ($item === null) {
            return;
        }

        $fields = [];
        foreach (($spec['sections'])($index, $item) as $section) {
            foreach ($section['fields'] as $field) {
                $fields[] = $field;
            }
        }

        Content::mutate(static fn (array $content): array => Form::apply($content, $fields, $_POST));
    }

    /** Ein Feld dieses Eintrags auf den eingespielten Stand zurückholen. @param array<string,mixed> $spec */
    private function reset(array $spec, int $index, string $path): void
    {
        $item = Lists::item($spec['key'], $index);
        if ($item === null) {
            return;
        }

        // Nur Felder, die auf dieser Seite auch stehen – der Pfad kommt aus
        // einem Formular und ist damit nichts, worauf man bauen sollte.
        foreach (($spec['sections'])($index, $item) as $section) {
            foreach ($section['fields'] as $field) {
                if ((string) $field['path'] === $path) {
                    Content::resetField($path);
                    return;
                }
            }
        }
    }

    private function delete(string $key, int $index): void
    {
        $removed = Lists::remove($key, $index);
        if ($removed !== null) {
            Lists::deleteUploads($removed);
        }
    }

    /* --------------------------------- Helfer -------------------------------- */

    /** Ablageort der Bilder eines Eintrags – nach Slug, damit der Ordner sprechend bleibt. */
    private function folder(string $key, int $index): string
    {
        $item = Lists::item($key, $index) ?? [];
        $slug = Invitations::slug((string) ($item['slug'] ?? ''));
        return $key . '/' . ($slug !== '' ? $slug : (string) $index);
    }

    /** Zweisprachiges Feld eines Eintrags in der aktuellen Sprache. @param array<string,mixed> $item */
    private function pick(array $item, string $field, string $fallback): string
    {
        $value = I18n::pick($item[$field] ?? null, $this->locale);
        return $value !== '' ? $value : $fallback;
    }

    /**
     * Bilder eines Eintrags: erst die eigenen, danach die Platzhalter.
     *
     * @param array<string,mixed> $item
     * @return list<array{src:string,upload:bool,index:int}>
     */
    private function photoList(array $item): array
    {
        $photos = [];

        foreach (array_values(array_filter((array) ($item['uploads'] ?? []), 'is_string')) as $i => $url) {
            $photos[] = ['src' => $url, 'upload' => true, 'index' => $i];
        }

        // Platzhalter nur zeigen, solange es keine eigenen Bilder gibt –
        // auf der Seite ist es genauso.
        if ($photos === []) {
            foreach ((array) ($item['seeds'] ?? []) as $seed) {
                if (is_string($seed)) {
                    $photos[] = ['src' => Images::img($seed, 400, 520), 'upload' => false, 'index' => -1];
                }
            }
            if ($photos === [] && ($item['seed'] ?? '') !== '') {
                $photos[] = ['src' => Images::img((string) $item['seed'], 400, 520), 'upload' => false, 'index' => -1];
            }
        }

        return $photos;
    }

    /** @return array<string,string> */
    private function cityOptions(): array
    {
        $options = [];
        foreach (Content::list('cities') as $city) {
            $options[(string) ($city['slug'] ?? '')] = (string) ($city['name'] ?? '');
        }
        return $options;
    }

    /** @return array<string,string> */
    private function venueOptions(): array
    {
        $options = ['' => '—'];
        foreach (Content::list('venues') as $venue) {
            $options[(string) ($venue['slug'] ?? '')] = (string) ($venue['name'] ?? '');
        }
        return $options;
    }
}
