import type { L } from "./i18n";

export type City = {
  slug: string;
  name: string;
  kreis: L;
  drive: L;
  lead: L;
  body: L<string[]>;
  spots: { name: string; note: L }[];
  venues: string[];
  neighbours: string[];
  faq: { q: L; a: L }[];
};

export const cities: City[] = [
  {
    slug: "stuttgart",
    name: "Stuttgart",
    kreis: { de: "Stadtkreis Stuttgart", tr: "Stuttgart şehir merkezi" },
    drive: { de: "Atelier im Zentrum", tr: "Atölye şehir merkezinde" },
    lead: {
      de: "Hochzeitsfotograf und Videograf in Stuttgart – ruhig, dokumentarisch und mit einem Blick für die Zwischenmomente, die kein zweites Mal passieren.",
      tr: "Stuttgart'ta düğün fotoğrafçısı ve videografı – sakin, belgesel tarzda ve bir daha yaşanmayacak ara anları yakalayan bir bakışla.",
    },
    body: {
      de: [
        "Stuttgart ist unsere Heimat. Wir kennen das Licht am Killesberg um kurz nach sieben, die stillen Innenhöfe am Alten Schloss und die Stelle im Schlossgarten, an der im Juni um 18:30 Uhr die Sonne genau zwischen den Bäumen steht. Dieses Wissen spart am Hochzeitstag genau das, was am knappsten ist: Zeit.",
        "Ob standesamtliche Trauung im Stuttgarter Rathaus, freie Zeremonie auf der Solitude oder eine große Feier mit 400 Gästen in einer Eventhalle im Neckartal – wir begleiten den Tag unaufdringlich, arbeiten zu zweit (Foto und Video parallel) und liefern Bilder, die in zwanzig Jahren noch genauso wirken wie heute.",
      ],
      tr: [
        "Stuttgart bizim evimiz. Killesberg'de saat yediden hemen sonraki ışığı, Alte Schloss'un sessiz iç avlularını ve Schlossgarten'da haziranda 18:30'da güneşin tam ağaçların arasına düştüğü noktayı biliyoruz. Bu bilgi düğün günü en kıt olan şeyi kazandırır: zaman.",
        "İster Stuttgart Belediyesi'nde resmi nikah, ister Solitude'da serbest tören, ister Neckar vadisindeki bir salonda 400 kişilik büyük bir düğün olsun – günü rahatsız etmeden takip ediyor, iki kişi çalışıyoruz (foto ve video eş zamanlı) ve yirmi yıl sonra da bugünkü etkisini koruyan kareler teslim ediyoruz.",
      ],
    },
    spots: [
      { name: "Schlossplatz & Altes Schloss", note: { de: "Klassisch, urban, funktioniert auch bei Regen unter den Arkaden.", tr: "Klasik ve şehirli; yağmurda revak altında da çalışır." } },
      { name: "Höhenpark Killesberg", note: { de: "Weiches Abendlicht, Weitblick über den Kessel.", tr: "Yumuşak akşam ışığı, şehre hâkim manzara." } },
      { name: "Weinberge Stuttgart-Rotenberg", note: { de: "Goldene Stunde mit Blick über die Stadt.", tr: "Şehir manzarasıyla altın saat." } },
    ],
    venues: ["schloss-solitude", "villa-berg", "si-centrum-stuttgart"],
    neighbours: ["ludwigsburg", "esslingen", "boeblingen", "waiblingen"],
    faq: [
      {
        q: { de: "Wie viel kostet ein Hochzeitsfotograf in Stuttgart?", tr: "Stuttgart'ta düğün fotoğrafçısı ne kadar?" },
        a: {
          de: "Eine ganztägige Hochzeitsreportage in Stuttgart liegt bei uns zwischen 1.890 € und 3.490 €, je nach Stundenumfang und ob Foto und Video kombiniert werden. Standesamt-Pakete starten bei 690 €.",
          tr: "Stuttgart'ta tam gün düğün çekimi, saat sayısına ve foto+video birlikte olup olmamasına göre 1.890 € ile 3.490 € arasında. Sadece nikah paketleri 690 €'dan başlıyor.",
        },
      },
      {
        q: { de: "Kommt ihr auch für Foto und Video gleichzeitig?", tr: "Foto ve videoyu aynı anda çekiyor musunuz?" },
        a: {
          de: "Ja. Wir arbeiten als eingespieltes Team: eine Person für die Fotografie, eine für den Film. So entsteht kein Gedränge und beide Gewerke stimmen sich in Echtzeit ab.",
          tr: "Evet. Uyumlu bir ekip olarak çalışıyoruz: biri fotoğraf, biri video. Böylece kalabalık oluşmuyor ve iki ekip anlık olarak birbiriyle konuşuyor.",
        },
      },
    ],
  },
  {
    slug: "ludwigsburg",
    name: "Ludwigsburg",
    kreis: { de: "Landkreis Ludwigsburg", tr: "Ludwigsburg ilçesi" },
    drive: { de: "18 Minuten vom Atelier", tr: "Atölyeden 18 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Ludwigsburg – barocke Kulisse, klare Bildsprache, entspannter Ablauf.",
      tr: "Ludwigsburg'da düğün fotoğrafçısı – barok dekor, net bir görsel dil, rahat bir akış.",
    },
    body: {
      de: [
        "Kaum eine Stadt in der Region bietet auf so kleinem Raum so viele starke Hintergründe wie Ludwigsburg. Das Residenzschloss, das Blühende Barock, die Ostallee mit ihren Platanen – innerhalb von 20 Gehminuten entstehen Bilder, die nach drei verschiedenen Locations aussehen.",
        "Wir kennen die Genehmigungslage im Blühenden Barock und die ruhigen Ecken, an denen im Sommer keine Tagesgäste durchs Bild laufen. Für Paare, die im Standesamt Ludwigsburg heiraten und anschließend in einer Halle im Kreis feiern, planen wir den Tag so, dass zwischen Trauung und Empfang genug Luft für ein entspanntes Paar-Shooting bleibt.",
      ],
      tr: [
        "Bölgede bu kadar küçük bir alanda bu kadar güçlü arka plan sunan başka şehir yok. Residenzschloss, Blühendes Barock, çınarlı Ostallee – 20 dakikalık yürüyüş mesafesinde üç ayrı mekân gibi görünen kareler çıkıyor.",
        "Blühendes Barock'taki izin sürecini ve yazın ziyaretçilerin kadraja girmediği sakin köşeleri biliyoruz. Ludwigsburg'da nikah kıyıp ilçedeki bir salonda kutlayan çiftler için günü, nikah ile karşılama arasında rahat bir çift çekimine yetecek şekilde planlıyoruz.",
      ],
    },
    spots: [
      { name: "Residenzschloss Ludwigsburg", note: { de: "Barocke Symmetrie, ideal für ruhige Portraits.", tr: "Barok simetri, sakin portreler için ideal." } },
      { name: "Blühendes Barock", note: { de: "Genehmigung erforderlich – wir übernehmen die Abstimmung.", tr: "İzin gerekiyor – başvuruyu biz hallediyoruz." } },
      { name: "Seeschloss Monrepos", note: { de: "Wasser, weiches Licht, wenig Publikum am Abend.", tr: "Su, yumuşak ışık, akşamları sakin." } },
    ],
    venues: ["residenzschloss-ludwigsburg", "alte-kelter-fellbach"],
    neighbours: ["stuttgart", "waiblingen", "heilbronn", "boeblingen"],
    faq: [
      {
        q: { de: "Darf im Blühenden Barock fotografiert werden?", tr: "Blühendes Barock'ta fotoğraf çekilebiliyor mu?" },
        a: {
          de: "Für gewerbliche Hochzeitsshootings ist eine kostenpflichtige Genehmigung nötig. Wir kümmern uns auf Wunsch um die Anmeldung und planen den Zeitpuffer entsprechend ein.",
          tr: "Ticari düğün çekimleri için ücretli izin gerekiyor. İsterseniz başvuruyu biz yapıyor, zaman payını da ona göre planlıyoruz.",
        },
      },
      {
        q: { de: "Wie lange dauert ein Paarshooting in Ludwigsburg?", tr: "Ludwigsburg'da çift çekimi ne kadar sürer?" },
        a: {
          de: "45 bis 60 Minuten reichen völlig. In dieser Zeit schaffen wir zwei bis drei Kulissen, ohne dass ihr die Feier verpasst.",
          tr: "45–60 dakika fazlasıyla yeter. Bu sürede iki üç farklı dekor çekiyoruz ve düğünden kopmuyorsunuz.",
        },
      },
    ],
  },
  {
    slug: "esslingen",
    name: "Esslingen am Neckar",
    kreis: { de: "Landkreis Esslingen", tr: "Esslingen ilçesi" },
    drive: { de: "22 Minuten vom Atelier", tr: "Atölyeden 22 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Esslingen – mittelalterliche Gassen, Weinberge, warmes Abendlicht.",
      tr: "Esslingen'de düğün fotoğrafçısı – ortaçağ sokakları, bağlar, sıcak akşam ışığı.",
    },
    body: {
      de: [
        "Esslingen ist eine der fotogensten Städte Baden-Württembergs: Fachwerk, die Burgstaffel, der Blick über die Dächer von der Esslinger Burg. Wer hier heiratet, bekommt Bilder mit Charakter – ohne weite Wege.",
        "Besonders schön ist die Kombination aus Trauung im Alten Rathaus und Portraits in den Weinbergen oberhalb der Stadt. Der Aufstieg über die Burgstaffel dauert im Brautkleid etwa zehn Minuten – wir planen ihn bewusst früh ein, solange Frisur und Make-up frisch sind.",
      ],
      tr: [
        "Esslingen, Baden-Württemberg'in en fotojenik şehirlerinden biri: ahşap cepheler, Burgstaffel merdivenleri, kaleden çatıların üzerine bakan manzara. Burada evlenen çift, uzun yollara girmeden karakterli kareler alıyor.",
        "Özellikle Alte Rathaus'ta nikah ile şehrin üzerindeki bağlarda portre çekimini birleştirmek çok güzel oluyor. Merdivenlerden çıkış gelinlikle yaklaşık on dakika – bunu bilerek erken saate koyuyoruz, saç ve makyaj tazeyken.",
      ],
    },
    spots: [
      { name: "Esslinger Burg", note: { de: "Panorama über Altstadt und Neckartal.", tr: "Eski şehir ve Neckar vadisi panoraması." } },
      { name: "Altes Rathaus", note: { de: "Renaissance-Fassade, klassischer Trauungsort.", tr: "Rönesans cephesi, klasik nikah mekânı." } },
      { name: "Weinberge Schenkenberg", note: { de: "Rebzeilen im Gegenlicht, beste Zeit 1 h vor Sonnenuntergang.", tr: "Kontra ışıkta bağ sıraları; en iyi saat gün batımından 1 saat önce." } },
    ],
    venues: ["filderhalle-leinfelden", "schloss-hohenheim"],
    neighbours: ["stuttgart", "nuertingen", "waiblingen", "boeblingen"],
    faq: [
      {
        q: { de: "Fotografiert ihr auch am Wochenende in der Esslinger Altstadt?", tr: "Hafta sonu Esslingen eski şehirde de çekim yapıyor musunuz?" },
        a: {
          de: "Ja. Wir kennen die Gassen, die auch samstags ruhig sind, und arbeiten mit lichtstarken Objektiven, sodass wir keine Straße sperren müssen.",
          tr: "Evet. Cumartesileri de sakin kalan sokakları biliyoruz ve geniş diyaframlı lenslerle çalıştığımız için sokağı kapatmamıza gerek kalmıyor.",
        },
      },
    ],
  },
  {
    slug: "boeblingen",
    name: "Böblingen & Sindelfingen",
    kreis: { de: "Landkreis Böblingen", tr: "Böblingen ilçesi" },
    drive: { de: "25 Minuten vom Atelier", tr: "Atölyeden 25 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Böblingen und Sindelfingen – große Feiern, klare Reportage.",
      tr: "Böblingen ve Sindelfingen'de düğün fotoğrafçısı – büyük düğünler, net bir belgesel anlatım.",
    },
    body: {
      de: [
        "Im Kreis Böblingen finden viele der großen Hochzeiten der Region statt: moderne Eventhallen, Kapazitäten für 300 bis 800 Gäste, oft mit langem Programm bis in die Nacht. Genau dafür ist unsere Arbeitsweise gebaut – zwei Fotografen, entfesselte Blitzsetups für den Saal und ein Video-Team, das den Einzug filmisch begleitet.",
        "Für Portraits weichen wir gern an den Böblinger Oberen See oder in den Schönbuch aus: fünf Minuten Fahrt, komplett andere Bildwelt.",
      ],
      tr: [
        "Bölgenin büyük düğünlerinin çoğu Böblingen ilçesinde yapılıyor: modern salonlar, 300–800 kişilik kapasiteler, çoğu zaman gece geç saatlere uzayan programlar. Çalışma biçimimiz tam da buna göre kurulu – iki fotoğrafçı, salon için harici flaş sistemi ve gelin-damat girişini sinematik çeken bir video ekibi.",
        "Portreler için Böblingen'deki Oberer See'ye ya da Schönbuch ormanına geçiyoruz: beş dakikalık yol, tamamen başka bir görsel dünya.",
      ],
    },
    spots: [
      { name: "Oberer See Böblingen", note: { de: "Spiegelungen, ruhige Uferwege.", tr: "Yansımalar, sakin kıyı yolları." } },
      { name: "Schönbuchrand", note: { de: "Wald, weiches Streiflicht am Nachmittag.", tr: "Orman, öğleden sonra yumuşak yan ışık." } },
      { name: "Sindelfinger Altstadt", note: { de: "Fachwerk, kurze Wege zur Halle.", tr: "Ahşap cepheler, salona yakın mesafe." } },
    ],
    venues: ["si-centrum-stuttgart", "filderhalle-leinfelden"],
    neighbours: ["stuttgart", "esslingen", "pforzheim", "tuebingen"],
    faq: [
      {
        q: { de: "Könnt ihr Hochzeiten mit über 500 Gästen abdecken?", tr: "500 kişiden fazla düğünleri çekebiliyor musunuz?" },
        a: {
          de: "Ja. Ab 300 Gästen empfehlen wir das Team-Paket mit zwei Fotografen und zwei Videografen, damit Saaldekor, Empfang und Bühne parallel abgedeckt sind.",
          tr: "Evet. 300 kişiden itibaren iki fotoğrafçı ve iki videografın olduğu ekip paketini öneriyoruz; böylece salon süslemesi, karşılama ve sahne aynı anda kayıt altına alınıyor.",
        },
      },
    ],
  },
  {
    slug: "waiblingen",
    name: "Waiblingen & Rems-Murr",
    kreis: { de: "Rems-Murr-Kreis", tr: "Rems-Murr bölgesi" },
    drive: { de: "20 Minuten vom Atelier", tr: "Atölyeden 20 dakika" },
    lead: {
      de: "Hochzeitsfotograf im Rems-Murr-Kreis – Weinberge, Kelterhallen, entspannte Sommerhochzeiten.",
      tr: "Rems-Murr bölgesinde düğün fotoğrafçısı – bağlar, şaraphane salonları, rahat yaz düğünleri.",
    },
    body: {
      de: [
        "Der Rems-Murr-Kreis lebt vom Wein. Alte Keltern, Weingüter mit Innenhof und Rebhänge, die im Abendlicht golden werden – für Paare, die es warm und natürlich mögen, ist das die schönste Ecke der Region.",
        "Wir fahren regelmäßig nach Waiblingen, Fellbach, Winnenden und Schorndorf. Die Anfahrt innerhalb des Kreises ist bei uns immer inklusive.",
      ],
      tr: [
        "Rems-Murr bölgesi şarapla yaşar. Eski şaraphaneler, iç avlulu bağ evleri ve akşam ışığında altın rengine dönen bağ yamaçları – sıcak ve doğal bir dil isteyen çiftler için bölgenin en güzel köşesi.",
        "Waiblingen, Fellbach, Winnenden ve Schorndorf'a düzenli gidiyoruz. Bölge içi ulaşım tüm paketlere dahil.",
      ],
    },
    spots: [
      { name: "Altstadt Waiblingen", note: { de: "Stadtmauer und Fachwerk auf engem Raum.", tr: "Şehir surları ve ahşap cepheler yan yana." } },
      { name: "Rebhänge Fellbach", note: { de: "Kappelberg, Panorama Richtung Stuttgart.", tr: "Kappelberg, Stuttgart yönüne panorama." } },
      { name: "Remstal-Ufer", note: { de: "Schattige Bäume für Mittagshochzeiten im Hochsommer.", tr: "Yaz ortası öğle düğünleri için gölge ağaçlar." } },
    ],
    venues: ["alte-kelter-fellbach", "villa-berg"],
    neighbours: ["stuttgart", "ludwigsburg", "esslingen", "schwaebisch-gmuend"],
    faq: [
      {
        q: { de: "Berechnet ihr Anfahrt im Rems-Murr-Kreis?", tr: "Rems-Murr bölgesinde yol ücreti alıyor musunuz?" },
        a: {
          de: "Nein. Im Umkreis von 60 km um Stuttgart ist die Anfahrt in allen Paketen enthalten.",
          tr: "Hayır. Stuttgart çevresinde 60 km yarıçapındaki ulaşım tüm paketlere dahil.",
        },
      },
    ],
  },
  {
    slug: "heilbronn",
    name: "Heilbronn",
    kreis: { de: "Stadt- und Landkreis Heilbronn", tr: "Heilbronn şehir ve ilçesi" },
    drive: { de: "50 Minuten vom Atelier", tr: "Atölyeden 50 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Heilbronn – moderne Locations, große Familienfeiern, Weinlandschaft.",
      tr: "Heilbronn'da düğün fotoğrafçısı – modern mekânlar, büyük aile düğünleri, bağ manzaraları.",
    },
    body: {
      de: [
        "Heilbronn hat sich in den letzten Jahren stark verändert: das Neckarbogen-Quartier, die neue Uferpromenade und die Weinberge am Wartberg ergeben eine Mischung aus modern und klassisch, die es so kaum ein zweites Mal gibt.",
        "Wir begleiten in Heilbronn regelmäßig große Feiern mit türkischer, italienischer und deutscher Tradition – vom Nachmittagsempfang bis zum Konfetti-Finale um Mitternacht.",
      ],
      tr: [
        "Heilbronn son yıllarda çok değişti: Neckarbogen bölgesi, yeni nehir kıyısı ve Wartberg'deki bağlar, modernle klasiği eşine az rastlanır biçimde birleştiriyor.",
        "Heilbronn'da Türk, İtalyan ve Alman geleneğine göre yapılan büyük düğünleri düzenli çekiyoruz – öğleden sonraki karşılamadan gece yarısı konfeti finaline kadar.",
      ],
    },
    spots: [
      { name: "Wartberg", note: { de: "Aussichtsturm, Weinberge, Sonnenuntergang.", tr: "Seyir kulesi, bağlar, gün batımı." } },
      { name: "Neckarbogen", note: { de: "Klare moderne Architektur, viel Licht.", tr: "Net modern mimari, bol ışık." } },
      { name: "Kilianskirche", note: { de: "Historisches Zentrum, kurze Wege.", tr: "Tarihi merkez, kısa mesafeler." } },
    ],
    venues: ["residenzschloss-ludwigsburg", "si-centrum-stuttgart"],
    neighbours: ["ludwigsburg", "stuttgart", "pforzheim", "schwaebisch-gmuend"],
    faq: [
      {
        q: { de: "Fahrt ihr auch bis Heilbronn ohne Aufpreis?", tr: "Heilbronn'a ek ücretsiz geliyor musunuz?" },
        a: {
          de: "Ja, Heilbronn liegt innerhalb unseres 60-km-Radius. Erst darüber hinaus berechnen wir 0,40 € pro Kilometer.",
          tr: "Evet, Heilbronn 60 km yarıçapımızın içinde. Bunun ötesinde kilometre başına 0,40 € alıyoruz.",
        },
      },
    ],
  },
  {
    slug: "tuebingen",
    name: "Tübingen",
    kreis: { de: "Landkreis Tübingen", tr: "Tübingen ilçesi" },
    drive: { de: "45 Minuten vom Atelier", tr: "Atölyeden 45 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Tübingen – Neckarfront, Stocherkähne, romantisches Licht.",
      tr: "Tübingen'de düğün fotoğrafçısı – Neckar cephesi, sırıklı kayıklar, romantik ışık.",
    },
    body: {
      de: [
        "Die Tübinger Neckarfront ist eines der bekanntesten Motive Süddeutschlands – und im Sommer entsprechend gut besucht. Wir planen Portraits deshalb entweder früh am Morgen oder in der letzten Stunde vor Sonnenuntergang, wenn die Platanenallee fast leer ist.",
        "Für Paare, die es literarisch-romantisch mögen, ist ein Stocherkahn als Kulisse mit etwas Vorlauf buchbar und ergibt Bilder, die niemand sonst hat.",
      ],
      tr: [
        "Tübingen'in Neckar cephesi Güney Almanya'nın en bilinen manzaralarından biri – dolayısıyla yazın kalabalık. Bu yüzden portreleri ya sabah erken saate ya da gün batımından önceki son saate, çınar yolunun neredeyse boş olduğu zamana koyuyoruz.",
        "Edebi-romantik bir dil isteyen çiftler için sırıklı kayık, önceden ayarlanırsa başka kimsede olmayan kareler veriyor.",
      ],
    },
    spots: [
      { name: "Neckarfront & Platanenallee", note: { de: "Das Postkartenmotiv – am besten abends.", tr: "Kartpostal manzarası – en iyisi akşam." } },
      { name: "Schloss Hohentübingen", note: { de: "Innenhof, Blick über die Altstadt.", tr: "İç avlu, eski şehre bakan manzara." } },
      { name: "Marktplatz", note: { de: "Fachwerk und Rathausfassade.", tr: "Ahşap cepheler ve belediye binası." } },
    ],
    venues: ["schloss-hohenheim", "filderhalle-leinfelden"],
    neighbours: ["boeblingen", "nuertingen", "stuttgart", "esslingen"],
    faq: [
      {
        q: { de: "Wann ist die beste Uhrzeit für Fotos an der Neckarfront?", tr: "Neckar cephesinde en iyi çekim saati hangisi?" },
        a: {
          de: "Im Sommer zwischen 19:30 und 20:30 Uhr. Dann steht die Sonne tief über dem Fluss und die Altstadt liegt im warmen Gegenlicht.",
          tr: "Yazın 19:30–20:30 arası. O saatte güneş nehrin üzerinde alçalıyor ve eski şehir sıcak kontra ışıkta kalıyor.",
        },
      },
    ],
  },
  {
    slug: "nuertingen",
    name: "Nürtingen & Kirchheim",
    kreis: { de: "Landkreis Esslingen", tr: "Esslingen ilçesi" },
    drive: { de: "35 Minuten vom Atelier", tr: "Atölyeden 35 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Nürtingen, Kirchheim und am Albtrauf.",
      tr: "Nürtingen, Kirchheim ve Albtrauf bölgesinde düğün fotoğrafçısı.",
    },
    body: {
      de: [
        "Zwischen Neckar und Albtrauf liegen einige der ruhigsten Hochzeitslocations der Region – Höfe, Scheunen, kleine Weingüter. Wer eine familiäre Feier plant, findet hier Orte, die noch nicht auf jeder Pinnwand kleben.",
        "Der Blick auf die Burg Teck bei Sonnenuntergang gehört zu unseren liebsten Motiven im Kreis Esslingen.",
      ],
      tr: [
        "Neckar ile Albtrauf arasında bölgenin en sakin düğün mekânları var – çiftlik avluları, ahırlar, küçük bağ evleri. Samimi bir düğün planlayan çiftler burada henüz herkesin paylaşmadığı yerler buluyor.",
        "Gün batımında Burg Teck manzarası, Esslingen ilçesindeki en sevdiğimiz karelerden biri.",
      ],
    },
    spots: [
      { name: "Burg Teck", note: { de: "Weitblick über das Vorland, ideal zur blauen Stunde.", tr: "Ovaya hâkim manzara, mavi saat için ideal." } },
      { name: "Neckarauen Nürtingen", note: { de: "Wiesen und Uferbäume, sehr ruhig.", tr: "Çayırlar ve kıyı ağaçları, çok sakin." } },
      { name: "Kirchheimer Schlossgarten", note: { de: "Alter Baumbestand, gleichmäßiges Licht.", tr: "Yaşlı ağaçlar, dengeli ışık." } },
    ],
    venues: ["filderhalle-leinfelden", "schloss-hohenheim"],
    neighbours: ["esslingen", "tuebingen", "stuttgart", "boeblingen"],
    faq: [
      {
        q: { de: "Macht ihr auch kleine Hochzeiten mit 20 Gästen?", tr: "20 kişilik küçük düğünleri de çekiyor musunuz?" },
        a: {
          de: "Sehr gern. Für intime Feiern gibt es das Paket „Standesamt & Feier“ mit vier Stunden Begleitung.",
          tr: "Memnuniyetle. Samimi kutlamalar için dört saatlik „Nikah & Kutlama“ paketimiz var.",
        },
      },
    ],
  },
  {
    slug: "pforzheim",
    name: "Pforzheim",
    kreis: { de: "Stadtkreis Pforzheim", tr: "Pforzheim şehri" },
    drive: { de: "50 Minuten vom Atelier", tr: "Atölyeden 50 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Pforzheim und am Nordschwarzwald.",
      tr: "Pforzheim ve Kuzey Karaorman bölgesinde düğün fotoğrafçısı.",
    },
    body: {
      de: [
        "Pforzheim ist das Tor zum Nordschwarzwald – und damit die Adresse für Paare, die Wald, Nebel und Weite in ihren Bildern wollen. Zwanzig Autominuten vom Standesamt entfernt beginnt eine komplett andere Landschaft.",
        "Wir kombinieren hier gern eine städtische Trauung mit einem kurzen Ausflug in den Wald: zwei Bildwelten an einem Tag, ohne Stress im Ablauf.",
      ],
      tr: [
        "Pforzheim, Kuzey Karaorman'ın kapısı – yani karelerinde orman, sis ve derinlik isteyen çiftlerin adresi. Nikah dairesinden yirmi dakika uzakta bambaşka bir manzara başlıyor.",
        "Burada şehirdeki nikahı kısa bir orman çıkışıyla birleştirmeyi seviyoruz: bir günde iki ayrı görsel dünya, programı sıkıştırmadan.",
      ],
    },
    spots: [
      { name: "Wallberg-Panorama", note: { de: "Blick über die Stadt, gute Abendsonne.", tr: "Şehir manzarası, iyi akşam güneşi." } },
      { name: "Nordschwarzwald-Ränder", note: { de: "Nebelstimmung im Herbst, hohes Nadelholz.", tr: "Sonbaharda sis, yüksek çam ormanı." } },
      { name: "Enzauenpark", note: { de: "Weitläufige Wiesen, moderne Brücken.", tr: "Geniş çayırlar, modern köprüler." } },
    ],
    venues: ["residenzschloss-ludwigsburg", "schloss-solitude"],
    neighbours: ["boeblingen", "heilbronn", "stuttgart", "ludwigsburg"],
    faq: [
      {
        q: { de: "Ist die Anfahrt nach Pforzheim inklusive?", tr: "Pforzheim'a ulaşım dahil mi?" },
        a: {
          de: "Ja, Pforzheim liegt knapp innerhalb unseres 60-km-Radius und ist in allen Paketen ohne Aufpreis enthalten.",
          tr: "Evet, Pforzheim 60 km yarıçapımızın hemen içinde ve tüm paketlere ek ücretsiz dahil.",
        },
      },
    ],
  },
  {
    slug: "schwaebisch-gmuend",
    name: "Schwäbisch Gmünd",
    kreis: { de: "Ostalbkreis", tr: "Ostalb bölgesi" },
    drive: { de: "55 Minuten vom Atelier", tr: "Atölyeden 55 dakika" },
    lead: {
      de: "Hochzeitsfotograf in Schwäbisch Gmünd und im Ostalbkreis.",
      tr: "Schwäbisch Gmünd ve Ostalb bölgesinde düğün fotoğrafçısı.",
    },
    body: {
      de: [
        "Schwäbisch Gmünd verbindet staufische Altstadt mit den Höhen des Remstals. Der Zeitpuffer für die Anfahrt lohnt sich: Hier ist es an Sommersamstagen deutlich ruhiger als in Stuttgart, und die Locations sind oft familiengeführt.",
        "Für Paare aus dem Ostalbkreis bieten wir ein Vorgespräch per Video an, damit vor der Hochzeit keine unnötige Fahrt entsteht.",
      ],
      tr: [
        "Schwäbisch Gmünd, tarihi eski şehri Rems vadisinin yükseklikleriyle birleştiriyor. Yol için ayrılan zaman değiyor: yaz cumartesileri Stuttgart'a göre çok daha sakin ve mekânlar genelde aile işletmesi.",
        "Ostalb bölgesindeki çiftlere ön görüşmeyi video ile sunuyoruz, düğün öncesi gereksiz yol olmasın diye.",
      ],
    },
    spots: [
      { name: "Heilig-Kreuz-Münster", note: { de: "Gotische Kulisse mitten in der Altstadt.", tr: "Eski şehrin ortasında gotik dekor." } },
      { name: "Zeiselberg", note: { de: "Panorama über Gmünd, sehr schön zur blauen Stunde.", tr: "Gmünd panoraması, mavi saatte çok güzel." } },
      { name: "Remspark", note: { de: "Weite Wiesen aus der Landesgartenschau.", tr: "Bahçe fuarından kalan geniş çayırlar." } },
    ],
    venues: ["alte-kelter-fellbach", "villa-berg"],
    neighbours: ["waiblingen", "heilbronn", "stuttgart", "esslingen"],
    faq: [
      {
        q: { de: "Kommt ihr auch in den Ostalbkreis?", tr: "Ostalb bölgesine de geliyor musunuz?" },
        a: {
          de: "Ja. Ab 60 km berechnen wir 0,40 € pro Kilometer – für Schwäbisch Gmünd sind das je nach Location rund 25 €.",
          tr: "Evet. 60 km'den sonra kilometre başına 0,40 € alıyoruz – Schwäbisch Gmünd için mekâna göre yaklaşık 25 €.",
        },
      },
    ],
  },
];

export const cityBySlug = (slug: string) => cities.find((c) => c.slug === slug);
