import type { L } from "./i18n";

/* ------------------------------------------------------------------ */
/*  Leistungen                                                         */
/* ------------------------------------------------------------------ */

export type Service = {
  slug: string;
  title: L;
  short: L;
  body: L<string[]>;
  bullets: L<string[]>;
  seed: string;
  /** Beispielaufnahmen des Abschnitts. Leer = Demo-Bilder aus dem Bestand. */
  photos?: string[];
  /** Beispielfilm (YouTube, Vimeo oder Datei) */
  videoUrl?: string;
};

/**
 * Kapak: eigenes Titelbild vor eigenen Beispielbildern vor Demo-Motiv.
 * Ohne diese Reihenfolge liesse sich ein hochgeladenes Foto nie zur Karte
 * auf der Startseite machen, wenn oben noch die Demo-Kennung ("lum-service-…")
 * steht – das war die haeufigste Verwirrung im Panel.
 */
export function serviceCover(s: Service): string {
  if (/^(https?:|data:|\/)/.test(s.seed)) return s.seed;
  const first = (s.photos ?? []).find(Boolean);
  return first ?? s.seed;
}

export const services: Service[] = [
  {
    slug: "hochzeitsfotografie",
    seed: "lum-service-foto",
    title: { de: "Hochzeitsfotografie", tr: "Düğün Fotoğrafçılığı" },
    short: {
      de: "Dokumentarisch, ruhig, ohne stundenlanges Posieren. Wir halten fest, was ohnehin passiert.",
      tr: "Belgesel tarzda, sakin, saatlerce poz vermeden. Zaten yaşananı kayda alıyoruz.",
    },
    body: {
      de: [
        "Unsere Reportagen leben von echten Momenten: der Blick der Mutter beim Ankleiden, die zitternden Hände beim Ringtausch, der Vater, der sich beim ersten Tanz wegdreht. Dafür arbeiten wir leise, mit zwei Kameras und ohne den Tag zu unterbrechen.",
        "Ein Paarshooting von 30 bis 45 Minuten planen wir trotzdem ein – nicht für gestellte Posen, sondern für die zwei, drei ruhigen Bilder, die später über dem Sofa hängen.",
      ],
      tr: [
        "Çekimlerimiz gerçek anlardan besleniyor: gelin hazırlanırken annenin bakışı, yüzük takılırken titreyen eller, ilk dansta başını çeviren baba. Bunun için sessiz çalışıyor, iki gövde kullanıyor ve günü bölmüyoruz.",
        "Yine de 30–45 dakikalık bir çift çekimi planlıyoruz – kurgulu pozlar için değil, sonradan duvara asılacak o iki üç sakin kare için.",
      ],
    },
    bullets: {
      de: [
        "Zwei Fotografen bei Feiern ab 150 Gästen",
        "Vollformat-Technik, lichtstarke Festbrennweiten",
        "Farb- und Schwarzweiß-Bearbeitung von Hand",
        "Online-Galerie mit Download in voller Auflösung",
      ],
      tr: [
        "150 kişiden büyük düğünlerde iki fotoğrafçı",
        "Full frame ekipman, geniş diyaframlı sabit lensler",
        "Elde renk ve siyah-beyaz düzenleme",
        "Tam çözünürlük indirmeli online galeri",
      ],
    },
  },
  {
    slug: "hochzeitsvideo",
    seed: "lum-service-video",
    title: { de: "Hochzeitsfilm & Video", tr: "Düğün Filmi & Video" },
    short: {
      de: "Ein Film, den ihr wirklich anschaut – kurz, musikalisch, mit euren Stimmen.",
      tr: "Gerçekten izleyeceğiniz bir film – kısa, müzikli, kendi seslerinizle.",
    },
    body: {
      de: [
        "Der Highlight-Film dauert vier bis sechs Minuten. Er ist so geschnitten, dass er auch Gäste mitnimmt, die nicht dabei waren: die Rede des Vaters als Tonspur, der Einzug in Zeitlupe, das Lachen beim Anschneiden der Torte.",
        "Auf Wunsch gibt es zusätzlich den ungeschnittenen Mitschnitt von Trauung und Reden – für die Familie oft das Wichtigste.",
      ],
      tr: [
        "Highlight film dört ila altı dakika. Orada olmayan misafirleri de içine alacak şekilde kurgulanıyor: ses olarak babanın konuşması, ağır çekimde giriş, pasta kesiminde kahkaha.",
        "İsteğe bağlı olarak nikah ve konuşmaların kesilmemiş kaydı da veriliyor – aile için çoğu zaman en değerlisi bu.",
      ],
    },
    bullets: {
      de: [
        "Highlight-Film 4–6 Minuten in 4K",
        "Funkmikrofone für Reden und Zeremonie",
        "Gimbal & Slider, keine hektischen Schnitte",
        "Vertikale Version für Instagram inklusive",
      ],
      tr: [
        "4K'da 4–6 dakikalık highlight film",
        "Konuşma ve tören için telsiz mikrofon",
        "Gimbal ve slider, telaşlı kurgu yok",
        "Instagram için dikey versiyon dahil",
      ],
    },
  },
  {
    slug: "standesamt",
    seed: "lum-service-standesamt",
    title: { de: "Standesamt & Verlobung", tr: "Nikah & Nişan" },
    short: {
      de: "Kompakte Begleitung für kleine Feiern, Verlobungen und Henna-Abende.",
      tr: "Küçük kutlamalar, nişan ve kına geceleri için kompakt çekim.",
    },
    body: {
      de: [
        "Nicht jede Hochzeit dauert zwölf Stunden. Für standesamtliche Trauungen, Verlobungen und Henna-Abende gibt es kompakte Pakete ab zwei Stunden – mit derselben Technik und derselben Bildsprache wie bei den großen Feiern.",
      ],
      tr: [
        "Her düğün on iki saat sürmüyor. Resmi nikah, nişan ve kına geceleri için iki saatten başlayan kompakt paketler var – büyük düğünlerdeki aynı ekipman ve aynı görsel dille.",
      ],
    },
    bullets: {
      de: ["Ab 2 Stunden buchbar", "Ideal für Henna-Abend & Verlobung", "Lieferung innerhalb von 10 Tagen", "Aufstockung auf Tagespaket jederzeit möglich"],
      tr: ["2 saatten itibaren", "Kına gecesi ve nişan için ideal", "10 gün içinde teslim", "İstediğiniz zaman tam gün pakete yükseltme"],
    },
  },
  {
    slug: "after-wedding",
    seed: "lum-service-after",
    title: { de: "After-Wedding & Paarshooting", tr: "After-Wedding & Çift Çekimi" },
    short: {
      de: "Noch einmal ins Kleid – ohne Zeitdruck, an einem Ort, den ihr euch aussucht.",
      tr: "Gelinliği bir kez daha giyin – zaman baskısı olmadan, seçtiğiniz bir yerde.",
    },
    body: {
      de: [
        "Am Hochzeitstag ist selten Zeit für lange Portraits. Ein After-Wedding-Shooting löst das: zwei Stunden, ein Ort eurer Wahl – Weinberge, Schwarzwald, Bodensee oder eine Reise – und komplett ohne Zeitplan im Nacken.",
      ],
      tr: [
        "Düğün günü uzun portreler için nadiren zaman kalıyor. After-Wedding çekimi bunu çözüyor: iki saat, sizin seçtiğiniz bir yer – bağlar, Karaorman, Bodensee ya da bir seyahat – ve ensenizde program baskısı olmadan.",
      ],
    },
    bullets: {
      de: ["2 Stunden reine Shootingzeit", "Locationvorschläge inklusive", "Auch als Reise-Shooting buchbar", "40+ bearbeitete Bilder"],
      tr: ["2 saat net çekim", "Mekân önerileri dahil", "Seyahat çekimi olarak da mümkün", "40+ düzenlenmiş kare"],
    },
  },
];

/* ------------------------------------------------------------------ */
/*  Pakete / Preise                                                    */
/* ------------------------------------------------------------------ */

export type Pkg = {
  slug: string;
  name: L;
  price: string;
  hint: L;
  features: L<string[]>;
  featured?: boolean;
};

export const packages: Pkg[] = [
  {
    slug: "standesamt",
    name: { de: "Standesamt & Feier", tr: "Nikah & Kutlama" },
    price: "690 €",
    hint: { de: "4 Stunden Begleitung", tr: "4 saat çekim" },
    features: {
      de: [
        "4 Stunden Fotoreportage",
        "ca. 150 bearbeitete Bilder",
        "Private Online-Galerie, 12 Monate online",
        "Download in voller Auflösung",
        "Lieferung in 10 Werktagen",
      ],
      tr: [
        "4 saat fotoğraf çekimi",
        "yaklaşık 150 düzenlenmiş kare",
        "Özel online galeri, 12 ay erişim",
        "Tam çözünürlük indirme",
        "10 iş gününde teslim",
      ],
    },
  },
  {
    slug: "klassik",
    name: { de: "Der ganze Tag", tr: "Tüm Gün" },
    price: "1.890 €",
    hint: { de: "10 Stunden, Getting Ready bis Party", tr: "10 saat, hazırlıktan partiye" },
    featured: true,
    features: {
      de: [
        "10 Stunden Fotoreportage",
        "Zweiter Fotograf ab 150 Gästen",
        "400–600 bearbeitete Bilder",
        "Paarshooting zur goldenen Stunde",
        "Private Online-Galerie mit Foto-Auswahl fürs Album",
        "Digitale Hochzeitseinladung gratis",
        "Lieferung in 3 Wochen",
      ],
      tr: [
        "10 saat fotoğraf çekimi",
        "150 kişiden itibaren ikinci fotoğrafçı",
        "400–600 düzenlenmiş kare",
        "Altın saatte çift çekimi",
        "Albüm seçimi yapılabilen özel online galeri",
        "Dijital düğün davetiyesi ücretsiz",
        "3 haftada teslim",
      ],
    },
  },
  {
    slug: "foto-video",
    name: { de: "Foto & Film", tr: "Foto & Film" },
    price: "3.490 €",
    hint: { de: "Team aus 2 Personen, ganzer Tag", tr: "2 kişilik ekip, tüm gün" },
    features: {
      de: [
        "Alles aus „Der ganze Tag“",
        "Highlight-Film 4–6 Minuten in 4K",
        "Funkmikrofone für Trauung & Reden",
        "Vertikale Social-Version",
        "Ungeschnittener Mitschnitt der Trauung",
        "Digitale Hochzeitseinladung gratis",
        "Lieferung Film in 6 Wochen",
      ],
      tr: [
        "„Tüm Gün“ paketindeki her şey",
        "4K'da 4–6 dakikalık highlight film",
        "Nikah ve konuşmalar için telsiz mikrofon",
        "Sosyal medya için dikey versiyon",
        "Nikahın kesilmemiş kaydı",
        "Dijital düğün davetiyesi ücretsiz",
        "Film teslimi 6 hafta",
      ],
    },
  },
];

export type AddOn = { name: L; price: string };

export const addons: AddOn[] = [
  { name: { de: "Zweiter Fotograf (ganzer Tag)", tr: "İkinci fotoğrafçı (tüm gün)" }, price: "+ 450 €" },
  { name: { de: "Henna-Abend / Verlobung (3 Std.)", tr: "Kına gecesi / nişan (3 saat)" }, price: "+ 490 €" },
  { name: { de: "After-Wedding-Shooting (2 Std.)", tr: "After-Wedding çekimi (2 saat)" }, price: "+ 390 €" },
  { name: { de: "Fotoalbum 30×30 cm, 40 Seiten", tr: "Fotoğraf albümü 30×30 cm, 40 sayfa" }, price: "+ 590 €" },
  { name: { de: "Digitale Einladung mit RSVP", tr: "RSVP'li dijital davetiye" }, price: "79 €" },
  { name: { de: "Anfahrt über 60 km", tr: "60 km üzeri yol" }, price: "0,40 €/km" },
];

/* ------------------------------------------------------------------ */
/*  Portfolio / Referenzen                                             */
/* ------------------------------------------------------------------ */

export type Story = {
  slug: string;
  couple: string;
  venue: L;
  citySlug: string;
  venueSlug?: string;
  month: L;
  guests: string;
  seeds: string[];
  /** Im Admin hochgeladene Bilder (ersetzen die Platzhalter) */
  uploads?: string[];
  intro: L;
  body: L<string[]>;
  quote: L;
  /** Hochzeitsfilm bei YouTube oder Vimeo – im Admin einzutragen */
  videoUrl?: string;
};

export const stories: Story[] = [
  {
    slug: "elif-marco-schloss-solitude",
    couple: "Elif & Marco",
    venue: { de: "Schloss Solitude, Stuttgart", tr: "Schloss Solitude, Stuttgart" },
    citySlug: "stuttgart",
    venueSlug: "schloss-solitude",
    month: { de: "Juni", tr: "Haziran" },
    guests: "120",
    seeds: ["story-elif-1", "story-elif-2", "story-elif-3", "story-elif-4", "story-elif-5", "story-elif-6"],
    intro: {
      de: "Eine deutsch-türkische Hochzeit auf Schloss Solitude – mit Sonnenuntergang auf der Sichtachse und Feier bis halb zwei.",
      tr: "Schloss Solitude'da Türk-Alman düğünü – eksende gün batımı ve gece bir buçuğa kadar kutlama.",
    },
    body: {
      de: [
        "Elif und Marco wollten zwei Traditionen in einen Tag bringen, ohne dass sich eine davon wie ein Programmpunkt anfühlt. Der Morgen gehörte der Familie, der Nachmittag der Trauung im Weißen Saal, der Abend dem Fest.",
        "Für uns war das ein Tag mit vielen kleinen Übergängen – und genau die machen so eine Reportage aus. Das schönste Bild entstand nicht beim Shooting, sondern als Elifs Großmutter ihr kurz vor dem Einzug die Hand hielt.",
      ],
      tr: [
        "Elif ve Marco iki geleneği tek güne sığdırmak istiyordu, ama hiçbiri program maddesi gibi durmasın diye. Sabah aileye, öğleden sonra Beyaz Salon'daki nikaha, akşam da düğüne ayrıldı.",
        "Bizim için bu, çok sayıda küçük geçişin olduğu bir gündü – ve böyle bir çekimi asıl bunlar oluşturur. En güzel kare çekim sırasında değil, girişten hemen önce Elif'in babaannesi elini tuttuğunda çıktı.",
      ],
    },
    quote: {
      de: "Wir haben euch den ganzen Tag kaum gesehen – und trotzdem ist alles auf den Bildern.",
      tr: "Sizi gün boyu neredeyse hiç görmedik – ama her şey karelerde var.",
    },
  },
  {
    slug: "sarah-daniel-alte-kelter",
    couple: "Sarah & Daniel",
    venue: { de: "Alte Kelter, Fellbach", tr: "Alte Kelter, Fellbach" },
    citySlug: "waiblingen",
    venueSlug: "alte-kelter-fellbach",
    month: { de: "September", tr: "Eylül" },
    guests: "260",
    seeds: ["story-sarah-1", "story-sarah-2", "story-sarah-3", "story-sarah-4", "story-sarah-5", "story-sarah-6"],
    intro: {
      de: "Weinberge, warmes Holz und eine Feier, die um 19:30 Uhr für 30 Minuten in den Kappelberg ausgewichen ist.",
      tr: "Bağlar, sıcak ahşap ve 19:30'da otuz dakikalığına Kappelberg'e çıkan bir düğün.",
    },
    body: {
      de: [
        "Bei 260 Gästen bleibt selten Zeit für Portraits. Wir haben deshalb im Vorgespräch ein festes Fenster vereinbart: 19:30 Uhr, 30 Minuten, Rebhänge. Der Rest des Abends gehörte der Reportage.",
        "Die Bilder aus dieser halben Stunde sind die, die heute im Wohnzimmer hängen – und trotzdem hat niemand gemerkt, dass das Brautpaar kurz weg war.",
      ],
      tr: [
        "260 kişide portreye nadiren zaman kalır. Bu yüzden ön görüşmede sabit bir aralık belirledik: 19:30, 30 dakika, bağlar. Akşamın kalanı belgesel çekime kaldı.",
        "Bugün oturma odasında asılı olan kareler o yarım saatten – yine de kimse çiftin kısa süre ayrıldığını fark etmedi.",
      ],
    },
    quote: {
      de: "Die halbe Stunde in den Weinbergen war die beste Entscheidung des Tages.",
      tr: "Bağlardaki o yarım saat günün en iyi kararıydı.",
    },
  },
  {
    slug: "aylin-emre-si-centrum",
    couple: "Aylin & Emre",
    venue: { de: "SI-Centrum, Stuttgart", tr: "SI-Centrum, Stuttgart" },
    citySlug: "stuttgart",
    venueSlug: "si-centrum-stuttgart",
    month: { de: "August", tr: "Ağustos" },
    guests: "600",
    seeds: ["story-aylin-1", "story-aylin-2", "story-aylin-3", "story-aylin-4", "story-aylin-5", "story-aylin-6"],
    intro: {
      de: "600 Gäste, Bühnenprogramm, Konfetti – eine große Feier, die trotzdem ruhige Bilder zulässt.",
      tr: "600 kişi, sahne programı, konfeti – buna rağmen sakin kareler veren büyük bir düğün.",
    },
    body: {
      de: [
        "Große Hallen sind eine Lichtfrage. Wir haben zwei Blitzstative gesetzt, das Video-Team hat mit Gimbal gearbeitet, und der Einzug wurde aus zwei Perspektiven gleichzeitig aufgenommen.",
        "Für das Paarshooting sind wir um 16:00 Uhr raus, bevor die Gäste kamen – 25 Minuten, die den Unterschied gemacht haben.",
      ],
      tr: [
        "Büyük salonlar bir ışık meselesidir. İki flaş ayağı kurduk, video ekibi gimbal ile çalıştı ve giriş aynı anda iki açıdan kaydedildi.",
        "Çift çekimi için misafirler gelmeden 16:00'da dışarı çıktık – farkı yaratan 25 dakika.",
      ],
    },
    quote: {
      de: "Wir hatten Angst, dass die Bilder im dunklen Saal nichts werden. Das Gegenteil ist passiert.",
      tr: "Karanlık salonda kareler olmaz diye korkuyorduk. Tam tersi oldu.",
    },
  },
  {
    slug: "lena-jonas-residenzschloss",
    couple: "Lena & Jonas",
    venue: { de: "Residenzschloss, Ludwigsburg", tr: "Residenzschloss, Ludwigsburg" },
    citySlug: "ludwigsburg",
    venueSlug: "residenzschloss-ludwigsburg",
    month: { de: "Mai", tr: "Mayıs" },
    guests: "90",
    seeds: ["story-lena-1", "story-lena-2", "story-lena-3", "story-lena-4", "story-lena-5", "story-lena-6"],
    intro: {
      de: "Barocke Innenhöfe, ein Shooting im Blühenden Barock und zehn Minuten blaue Stunde an der Fassade.",
      tr: "Barok iç avlular, Blühendes Barock'ta çekim ve cephede on dakikalık mavi saat.",
    },
    body: {
      de: [
        "Lena und Jonas hatten eine klare Vorstellung: wenig Inszenierung, viel Familie. Wir haben den Tag entsprechend leicht gehalten und nur zwei feste Blöcke gesetzt – das Shooting im Park und die blaue Stunde.",
        "Die Genehmigung für das Blühende Barock haben wir vier Wochen vorher beantragt. Das ist der Punkt, an dem Erfahrung Zeit spart.",
      ],
      tr: [
        "Lena ve Jonas'ın net bir isteği vardı: az kurgu, çok aile. Günü buna göre hafif tuttuk ve sadece iki sabit blok koyduk – parktaki çekim ve mavi saat.",
        "Blühendes Barock iznini dört hafta önceden aldık. Tecrübenin zaman kazandırdığı nokta tam burası.",
      ],
    },
    quote: {
      de: "Ihr habt den Zeitplan gerettet, bevor wir überhaupt gemerkt haben, dass es einen gibt.",
      tr: "Bir program olduğunu biz fark etmeden siz o programı kurtardınız.",
    },
  },
];

export const storyBySlug = (slug: string) => stories.find((s) => s.slug === slug);

/* ------------------------------------------------------------------ */
/*  Ablauf & Stimmen                                                   */
/* ------------------------------------------------------------------ */

export type ProcessStep = { step: string; title: L; text: L };

export const processSteps: ProcessStep[] = [
  {
    step: "01",
    title: { de: "Kennenlernen", tr: "Tanışma" },
    text: {
      de: "Ein Gespräch per Video oder im Atelier. Ihr erzählt vom Tag, wir sagen ehrlich, was zeitlich realistisch ist.",
      tr: "Video ile ya da atölyede bir görüşme. Siz günü anlatıyorsunuz, biz zaman açısından neyin gerçekçi olduğunu açıkça söylüyoruz.",
    },
  },
  {
    step: "02",
    title: { de: "Zeitplan", tr: "Zaman planı" },
    text: {
      de: "Vier Wochen vorher erstellen wir gemeinsam einen Ablaufplan – inklusive Lichtzeiten und Puffer für die Location.",
      tr: "Dört hafta önce birlikte bir akış planı çıkarıyoruz – ışık saatleri ve mekân için zaman payı dahil.",
    },
  },
  {
    step: "03",
    title: { de: "Der Tag", tr: "Düğün günü" },
    text: {
      de: "Wir sind da, bevor es losgeht, und bleiben, bis die Party läuft. Ohne Kommandos, ohne Unterbrechung.",
      tr: "Başlamadan önce oradayız ve parti oturana kadar kalıyoruz. Komut yok, kesinti yok.",
    },
  },
  {
    step: "04",
    title: { de: "Galerie & Auswahl", tr: "Galeri & seçim" },
    text: {
      de: "Nach drei Wochen bekommt ihr eure private Galerie. Dort markiert ihr per Herz die Bilder fürs Album – wir sehen die Auswahl direkt.",
      tr: "Üç hafta sonra özel galeriniz hazır. Albüm için kareleri kalp ile işaretliyorsunuz – seçiminizi anında görüyoruz.",
    },
  },
];

export type Testimonial = { name: string; city: L; text: L };

export const testimonials: Testimonial[] = [
  {
    name: "Elif & Marco",
    city: { de: "Stuttgart", tr: "Stuttgart" },
    text: {
      de: "Wir haben euch den ganzen Tag kaum bemerkt – und dann waren da 600 Bilder, auf denen alles drauf ist, was uns wichtig war.",
      tr: "Gün boyu sizi neredeyse fark etmedik – sonra bizim için önemli olan her şeyin olduğu 600 kare çıktı.",
    },
  },
  {
    name: "Sarah & Daniel",
    city: { de: "Fellbach", tr: "Fellbach" },
    text: {
      de: "Die Online-Galerie war für unsere Familien perfekt. Meine Mutter in Izmir hat die Bilder am selben Abend gesehen.",
      tr: "Online galeri ailelerimiz için mükemmeldi. Annem İzmir'de kareleri aynı akşam gördü.",
    },
  },
  {
    name: "Aylin & Emre",
    city: { de: "Ludwigsburg", tr: "Ludwigsburg" },
    text: {
      de: "Der Film hat meinen Vater zum Weinen gebracht. Mehr muss man dazu nicht sagen.",
      tr: "Film babamı ağlattı. Daha fazla söze gerek yok.",
    },
  },
];

export type FaqItem = { q: L; a: L };

export const faqGeneral: FaqItem[] = [
  {
    q: { de: "Wie schnell bekommen wir die Bilder?", tr: "Kareleri ne kadar sürede alıyoruz?" },
    a: {
      de: "Eine Vorschau mit 20 Bildern gibt es innerhalb von 48 Stunden. Die vollständige Galerie folgt nach etwa drei Wochen, der Film nach sechs Wochen.",
      tr: "48 saat içinde 20 karelik bir ön izleme gönderiyoruz. Tam galeri yaklaşık üç hafta, film ise altı hafta sonra hazır oluyor.",
    },
  },
  {
    q: { de: "Wie funktioniert die Bildauswahl fürs Album?", tr: "Albüm için fotoğraf seçimi nasıl oluyor?" },
    a: {
      de: "In eurer privaten Galerie markiert ihr die Favoriten mit einem Herz und sendet die Auswahl mit einem Klick ab. Wir sehen sie sofort im Admin-Bereich – kein Excel, keine Dateinamen-Listen.",
      tr: "Özel galerinizde beğendiklerinizi kalp ile işaretleyip tek tıkla gönderiyorsunuz. Biz seçimi yönetim panelinde anında görüyoruz – Excel yok, dosya adı listesi yok.",
    },
  },
  {
    q: { de: "Ist die digitale Einladung wirklich kostenlos?", tr: "Dijital davetiye gerçekten ücretsiz mi?" },
    a: {
      de: "Für Paare, die ein Foto- oder Filmpaket gebucht haben, ja. Alle anderen können die Einladung für 79 € einmalig erstellen.",
      tr: "Foto veya film paketi alan çiftler için evet. Diğerleri davetiyeyi tek seferlik 79 €'ya oluşturabiliyor.",
    },
  },
  {
    q: { de: "Wie sind die Daten geschützt?", tr: "Veriler nasıl korunuyor?" },
    a: {
      de: "Alle Galerien liegen passwortgeschützt auf Servern in der EU. Bilder werden nur mit eurer Freigabe veröffentlicht, Analytics läuft nur nach Einwilligung.",
      tr: "Tüm galeriler AB'deki sunucularda parola korumalı tutuluyor. Kareler yalnızca sizin onayınızla yayınlanıyor, analiz araçları yalnızca izinle çalışıyor.",
    },
  },
  {
    q: { de: "Was passiert bei Krankheit?", tr: "Hastalık durumunda ne oluyor?" },
    a: {
      de: "Wir sind in einem Netzwerk mit sechs Kolleginnen und Kollegen aus der Region. Im Notfall steht innerhalb von 24 Stunden ein gleichwertiger Ersatz bereit – vertraglich zugesichert.",
      tr: "Bölgedeki altı meslektaşımızla bir ağ içindeyiz. Acil durumda 24 saat içinde eşdeğer bir ekip devreye giriyor – sözleşmeyle güvence altında.",
    },
  },
];
