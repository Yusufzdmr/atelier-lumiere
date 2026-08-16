<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Content;
use Atelier\Form;
use Atelier\I18n;
use Atelier\Marketing;
use Atelier\Security;
use Atelier\View;

/**
 * Die Inhaltsreiter des Adminbereichs.
 *
 * Jeder Reiter beschreibt nur seine Felder; Formular und Speichern erledigt
 * Form. Dadurch bleibt hier lesbar, WAS bearbeitet wird, statt wie das
 * Eingabefeld aussieht.
 */
final class ContentAdminController
{
    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    private function de(): bool
    {
        return $this->locale === 'de';
    }

    /* ---------------------------- Texte & Kontakt --------------------------- */

    public function texts(): void
    {
        $de = $this->de();

        $sections = [
            [
                'title'  => $de ? 'Startseite – Titelbereich' : 'Ana sayfa – üst bölüm',
                'hint'   => $de ? 'Zeilenumbruch in der Überschrift = neue Zeile auf der Seite.' : 'Başlıkta satır sonu = sayfada yeni satır.',
                'fields' => [
                    ['path' => 'hero.eyebrow.de', 'label' => 'Eyebrow (DE)'],
                    ['path' => 'hero.eyebrow.tr', 'label' => 'Eyebrow (TR)'],
                    ['path' => 'hero.title.de', 'label' => $de ? 'Überschrift (DE)' : 'Başlık (DE)', 'type' => 'area', 'rows' => 2],
                    ['path' => 'hero.title.tr', 'label' => $de ? 'Überschrift (TR)' : 'Başlık (TR)', 'type' => 'area', 'rows' => 2],
                    ['path' => 'hero.text.de', 'label' => $de ? 'Text (DE)' : 'Metin (DE)', 'type' => 'area', 'rows' => 3],
                    ['path' => 'hero.text.tr', 'label' => $de ? 'Text (TR)' : 'Metin (TR)', 'type' => 'area', 'rows' => 3],
                ],
            ],
            [
                'title'  => $de ? 'Zahlen auf der Startseite' : 'Ana sayfadaki sayılar',
                'grid'   => 'sm:grid-cols-2 lg:grid-cols-4',
                'fields' => [
                    ['path' => 'stats.weddings', 'label' => $de ? 'Hochzeiten' : 'Düğün'],
                    ['path' => 'stats.years', 'label' => $de ? 'Jahre Erfahrung' : 'Yıl tecrübe'],
                    ['path' => 'stats.delivery', 'label' => $de ? 'Wochen bis Galerie' : 'Galeriye kadar hafta'],
                    ['path' => 'stats.rating', 'label' => $de ? 'Bewertung' : 'Puan'],
                ],
            ],
            [
                'title'  => $de ? 'Kontaktdaten' : 'İletişim bilgileri',
                'fields' => [
                    ['path' => 'contact.phoneHuman', 'label' => $de ? 'Telefon (Anzeige)' : 'Telefon (görünen)'],
                    ['path' => 'contact.phone', 'label' => $de ? 'Telefon (Link)' : 'Telefon (link)'],
                    ['path' => 'contact.email', 'label' => 'E-Mail'],
                    ['path' => 'contact.instagram', 'label' => 'Instagram'],
                    ['path' => 'contact.street', 'label' => $de ? 'Straße' : 'Sokak'],
                    ['path' => 'contact.zip', 'label' => 'PLZ'],
                    ['path' => 'contact.city', 'label' => $de ? 'Ort' : 'Şehir'],
                    ['path' => 'contact.hours.de', 'label' => $de ? 'Zeiten (DE)' : 'Saatler (DE)'],
                    ['path' => 'contact.hours.tr', 'label' => $de ? 'Zeiten (TR)' : 'Saatler (TR)'],
                    [
                        'path'  => 'contact.mapsQuery',
                        'label' => $de ? 'Karte – abweichender Ort (optional)' : 'Harita – farklı konum (isteğe bağlı)',
                        'wide'  => true,
                        'hint'  => $de
                            ? 'Leer lassen: Die Karte nutzt die Anschrift oben. Sonst Adresse, Name der Location oder Koordinaten wie 48.3705,10.8875.'
                            : 'Boş bırakın: harita yukarıdaki adresi kullanır. Yoksa adres, mekân adı veya 48.3705,10.8875 gibi koordinat.',
                    ],
                ],
            ],
        ];

        $this->handle('/inhalte', $sections, $de ? 'Texte & Kontakt' : 'Metinler & iletişim', $de
            ? 'Diese Texte erscheinen auf der Startseite und im Footer – in beiden Sprachen.'
            : 'Bu metinler ana sayfada ve alt bilgide, iki dilde de görünür.', '/kontakt');
    }

    /* --------------------------- Preise & Pakete ---------------------------- */

    public function packages(): void
    {
        $de = $this->de();
        $sections = [];

        foreach (Content::list('packages') as $i => $package) {
            $sections[] = [
                'title'  => (string) ($package['name'][$this->locale] ?? ($package['name']['de'] ?? 'Paket')),
                'fields' => [
                    ['path' => "packages.$i.name.de", 'label' => $de ? 'Name (DE)' : 'Ad (DE)'],
                    ['path' => "packages.$i.name.tr", 'label' => $de ? 'Name (TR)' : 'Ad (TR)'],
                    ['path' => "packages.$i.price", 'label' => $de ? 'Preis' : 'Fiyat'],
                    ['path' => "packages.$i.hint.de", 'label' => $de ? 'Zusatz (DE)' : 'Ek bilgi (DE)'],
                    ['path' => "packages.$i.hint.tr", 'label' => $de ? 'Zusatz (TR)' : 'Ek bilgi (TR)'],
                    ['path' => "packages.$i.featured", 'label' => $de ? 'Als beliebtestes Paket hervorheben' : 'En popüler paket olarak öne çıkar', 'type' => 'check'],
                    ['path' => "packages.$i.features.de", 'label' => $de ? 'Leistungen (DE) – eine je Zeile' : 'İçerik (DE) – her satıra bir madde', 'type' => 'lines', 'rows' => 6, 'wide' => true],
                    ['path' => "packages.$i.features.tr", 'label' => $de ? 'Leistungen (TR) – eine je Zeile' : 'İçerik (TR) – her satıra bir madde', 'type' => 'lines', 'rows' => 6, 'wide' => true],
                ],
            ];
        }

        $sections[] = [
            'title'  => $de ? 'Zusatzleistungen' : 'Ek hizmetler',
            'hint'   => $de ? 'Je Zeile: Name | Preis' : 'Her satır: Ad | Fiyat',
            'fields' => [[
                'path'  => 'addons',
                'label' => $de ? 'Zusatzleistungen' : 'Ek hizmetler',
                'type'  => 'rows',
                'rows'  => 8,
                'wide'  => true,
                'cols'  => [['key' => 'name', 'bilingual' => true], ['key' => 'price']],
            ]],
        ];

        $this->handle('/pakete', $sections, $de ? 'Preise & Pakete' : 'Fiyatlar & paketler', $de
            ? 'Die Pakete erscheinen auf der Preisseite und in der Seitenspalte der Stadtseiten.'
            : 'Paketler fiyat sayfasında ve şehir sayfalarının yan sütununda görünür.', '/preise');
    }

    /* -------------------------- Über mich & Stimmen ------------------------- */

    public function about(): void
    {
        $de = $this->de();

        $sections = [
            [
                'title'  => $de ? 'Über mich' : 'Hakkımda',
                'fields' => [
                    ['path' => 'about.name', 'label' => $de ? 'Name' : 'Ad'],
                    ['path' => 'about.lead.de', 'label' => $de ? 'Vorspann (DE)' : 'Giriş (DE)'],
                    ['path' => 'about.lead.tr', 'label' => $de ? 'Vorspann (TR)' : 'Giriş (TR)'],
                    ['path' => 'about.body.de', 'label' => $de ? 'Text (DE) – Leerzeile trennt Absätze' : 'Metin (DE) – boş satır paragraf ayırır', 'type' => 'paras', 'rows' => 8, 'wide' => true],
                    ['path' => 'about.body.tr', 'label' => $de ? 'Text (TR)' : 'Metin (TR)', 'type' => 'paras', 'rows' => 8, 'wide' => true],
                ],
            ],
            [
                'title'  => $de ? 'Arbeitsweise' : 'Çalışma şekli',
                'hint'   => $de ? 'Je Zeile: Titel | Text' : 'Her satır: Başlık | Metin',
                'fields' => [
                    ['path' => 'about.valuesTitle.de', 'label' => $de ? 'Überschrift (DE)' : 'Başlık (DE)'],
                    ['path' => 'about.valuesTitle.tr', 'label' => $de ? 'Überschrift (TR)' : 'Başlık (TR)'],
                    [
                        'path' => 'about.values', 'label' => $de ? 'Punkte' : 'Maddeler', 'type' => 'rows', 'rows' => 6, 'wide' => true,
                        'cols' => [['key' => 't', 'bilingual' => true], ['key' => 'd', 'bilingual' => true]],
                    ],
                ],
            ],
            [
                'title'  => $de ? 'Ausrüstung' : 'Ekipman',
                'fields' => [
                    ['path' => 'about.gearTitle.de', 'label' => $de ? 'Überschrift (DE)' : 'Başlık (DE)'],
                    ['path' => 'about.gearTitle.tr', 'label' => $de ? 'Überschrift (TR)' : 'Başlık (TR)'],
                    ['path' => 'about.gear.de', 'label' => $de ? 'Liste (DE)' : 'Liste (DE)', 'type' => 'lines', 'rows' => 6, 'wide' => true],
                    ['path' => 'about.gear.tr', 'label' => $de ? 'Liste (TR)' : 'Liste (TR)', 'type' => 'lines', 'rows' => 6, 'wide' => true],
                ],
            ],
            [
                'title'  => $de ? 'Kundenstimmen' : 'Müşteri yorumları',
                'hint'   => $de ? 'Je Zeile: Name | Ort | Text' : 'Her satır: Ad | Şehir | Metin',
                'fields' => [[
                    'path' => 'testimonials', 'label' => $de ? 'Stimmen' : 'Yorumlar', 'type' => 'rows', 'rows' => 6, 'wide' => true,
                    'cols' => [['key' => 'name'], ['key' => 'city', 'bilingual' => true], ['key' => 'text', 'bilingual' => true]],
                ]],
            ],
            [
                'title'  => $de ? 'Häufige Fragen' : 'Sık sorulanlar',
                'hint'   => $de ? 'Je Zeile: Frage | Antwort' : 'Her satır: Soru | Cevap',
                'fields' => [[
                    'path' => 'faq', 'label' => 'FAQ', 'type' => 'rows', 'rows' => 8, 'wide' => true,
                    'cols' => [['key' => 'q', 'bilingual' => true], ['key' => 'a', 'bilingual' => true]],
                ]],
            ],
        ];

        $this->handle('/ueber-mich', $sections, $de ? 'Über mich & Stimmen' : 'Hakkımda & yorumlar', $de
            ? 'Diese Inhalte erscheinen auf „Über mich“, auf der Startseite und bei den Preisen.'
            : 'Bu içerikler „Hakkımda“ sayfasında, ana sayfada ve fiyatlarda görünür.', '/ueber-mich');
    }

    /* ------------------------------ Rechtstexte ----------------------------- */

    public function legal(): void
    {
        $de = $this->de();
        $legal = Content::get('legal');
        $sections = [];

        foreach (['impressum' => 'Impressum', 'datenschutz' => 'Datenschutz', 'agb' => 'AGB'] as $key => $caption) {
            $fields = [
                ['path' => "legal.$key.title", 'label' => $de ? 'Seitentitel' : 'Sayfa başlığı', 'wide' => true],
            ];

            foreach ((array) ($legal[$key]['sections'] ?? []) as $i => $section) {
                $fields[] = ['path' => "legal.$key.sections.$i.heading", 'label' => ($de ? 'Abschnitt' : 'Bölüm') . ' ' . ($i + 1), 'wide' => true];
                $fields[] = ['path' => "legal.$key.sections.$i.body", 'label' => $de ? 'Text' : 'Metin', 'type' => 'area', 'rows' => 6, 'wide' => true, 'max' => 8000];
            }

            $fields[] = ['path' => "legal.$key.note", 'label' => $de ? 'Hinweis am Seitenende' : 'Sayfa sonu notu', 'type' => 'area', 'rows' => 3, 'wide' => true];

            $sections[] = [
                'title'  => $caption,
                'hint'   => $de
                    ? 'Platzhalter {street} {zip} {city} {email} {phone} {legalName} {owner} werden aus den Kontaktdaten gefüllt. {{consent}} setzt den Knopf für die Cookie-Einstellungen.'
                    : '{street} {zip} {city} {email} {phone} {legalName} {owner} yer tutucuları iletişim bilgilerinden dolar. {{consent}} çerez ayarları düğmesini koyar.',
                'fields' => $fields,
                'grid'   => 'md:grid-cols-1',
            ];
        }

        $this->handle('/rechtliches', $sections, $de ? 'Rechtstexte' : 'Yasal metinler', $de
            ? 'Vor der Veröffentlichung sollte ein Anwalt darüberschauen – die Vorlagen sind ein Ausgangspunkt, keine Rechtsberatung.'
            : 'Yayından önce bir avukatın görmesi gerekir – şablonlar başlangıç noktasıdır, hukuki danışmanlık değildir.', '/impressum');
    }

    /* -------------------------------- SEO ----------------------------------- */

    public function seo(): void
    {
        $de = $this->de();
        $sections = [];

        foreach (Marketing::pages() as $page) {
            $key = $page['key'];
            $sections[] = [
                'title'  => (string) ($page['label'][$this->locale] ?? $page['label']['de']),
                'hint'   => ($de ? 'Adresse: ' : 'Adres: ') . '/' . $this->locale . ($page['path'] === '/' ? '' : $page['path']),
                'fields' => [
                    ['path' => "marketing.pages.$key.title.de", 'label' => $de ? 'Titel (DE)' : 'Başlık (DE)', 'hint' => $de ? 'Etwa 60 Zeichen.' : 'Yaklaşık 60 karakter.'],
                    ['path' => "marketing.pages.$key.title.tr", 'label' => $de ? 'Titel (TR)' : 'Başlık (TR)'],
                    ['path' => "marketing.pages.$key.description.de", 'label' => $de ? 'Beschreibung (DE)' : 'Açıklama (DE)', 'type' => 'area', 'rows' => 3, 'hint' => $de ? 'Etwa 160 Zeichen.' : 'Yaklaşık 160 karakter.'],
                    ['path' => "marketing.pages.$key.description.tr", 'label' => $de ? 'Beschreibung (TR)' : 'Açıklama (TR)', 'type' => 'area', 'rows' => 3],
                    ['path' => "marketing.pages.$key.noindex", 'label' => $de ? 'Nicht in Google aufnehmen' : 'Google’a girmesin', 'type' => 'check', 'wide' => true],
                ],
            ];
        }

        $sections[] = [
            'title'  => $de ? 'Titel-Vorlagen' : 'Başlık şablonları',
            'hint'   => $de
                ? 'Für die Seiten, die es viele Male gibt. {name} wird zum Stadt- oder Locationnamen, {title} zum Beitragstitel, {couple} und {venue} zur Reportage.'
                : 'Çok sayıda örneği olan sayfalar için. {name} şehir/mekân adı, {title} yazı başlığı, {couple} ve {venue} çekim bilgisi olur.',
            'fields' => [
                ['path' => 'marketing.templates.city.de', 'label' => $de ? 'Städte (DE)' : 'Şehirler (DE)'],
                ['path' => 'marketing.templates.city.tr', 'label' => $de ? 'Städte (TR)' : 'Şehirler (TR)'],
                ['path' => 'marketing.templates.venue.de', 'label' => 'Locations (DE)'],
                ['path' => 'marketing.templates.venue.tr', 'label' => 'Locations (TR)'],
                ['path' => 'marketing.templates.post.de', 'label' => $de ? 'Ratgeber (DE)' : 'Rehber (DE)'],
                ['path' => 'marketing.templates.post.tr', 'label' => $de ? 'Ratgeber (TR)' : 'Rehber (TR)'],
                ['path' => 'marketing.templates.story.de', 'label' => 'Portfolio (DE)'],
                ['path' => 'marketing.templates.story.tr', 'label' => 'Portfolio (TR)'],
            ],
        ];

        $this->handle('/seo', $sections, $de ? 'SEO & Meta' : 'SEO & meta', $de
            ? 'Das ist der Text, den Google in der Trefferliste zeigt. Bleibt ein Feld leer, nimmt die Seite ihren eingebauten Text – es geht also nie etwas verloren.'
            : 'Bu, Google’ın sonuç listesinde gösterdiği metindir. Alan boşsa sayfa kendi hazır metnini kullanır – hiçbir zaman boşa düşmez.');
    }

    /* -------------------------------- Gerüst -------------------------------- */

    /**
     * Formular anzeigen und beim Absenden speichern.
     *
     * @param list<array<string,mixed>> $sections
     */
    private function handle(string $tab, array $sections, string $title, string $intro, string $view = ''): void
    {
        $fields = [];
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                $fields[] = $field;
            }
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            // Erst alles Getippte übernehmen, dann erst zurücksetzen: sonst
            // verlöre der „zurücksetzen“-Knopf die übrigen Änderungen im
            // Formular, weil er es mit abschickt.
            Content::mutate(static fn (array $content): array => Form::apply($content, $fields, $_POST));

            $was = Security::clean($_POST['was'] ?? '', 200);
            if (str_starts_with($was, 'reset:')) {
                $path = substr($was, 6);
                // Nur Felder, die auf dieser Seite auch stehen.
                foreach ($fields as $field) {
                    if ((string) $field['path'] === $path) {
                        Content::resetField($path);
                        break;
                    }
                }
            }

            Admin::back($this->locale, $tab);
        }

        View::page('admin/content', [
            'layout'   => 'admin/layout',
            'locale'   => $this->locale,
            'path'     => I18n::path('/admin' . $tab),
            'current'  => $tab,
            'meta'     => ['title' => 'Admin', 'noindex' => true],
            'csrf'     => Security::csrf(),
            'title'    => $title,
            'intro'    => $intro,
            'sections'  => $sections,
            'data'      => Content::all(),
            'originals' => Content::original(),
            'reset'     => '',
            // Neben dem Speichern der Weg zur Seite, die dieser Reiter fuellt.
            'view'      => $view === '' ? '' : I18n::path($view, $this->locale),
        ]);
    }
}
