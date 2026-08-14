import type { L } from "./i18n";

export type Venue = {
  slug: string;
  name: string;
  type: L;
  city: string;
  citySlug: string;
  address: string;
  capacity: L;
  lead: L;
  body: L<string[]>;
  light: L;
  timing: { time: string; what: L }[];
  spots: L<string[]>;
  rules: L<string[]>;
  faq: { q: L; a: L }[];
};

export const venues: Venue[] = [
  {
    slug: "schloss-solitude",
    name: "Schloss Solitude",
    type: { de: "Schloss", tr: "Saray" },
    city: "Stuttgart",
    citySlug: "stuttgart",
    address: "Solitude 1, 70197 Stuttgart",
    capacity: { de: "bis ca. 120 Gäste", tr: "yaklaşık 120 kişiye kadar" },
    lead: {
      de: "Hochzeitsfotograf für Schloss Solitude in Stuttgart – wir kennen den Ablauf, das Licht auf der Terrasse und die ruhigen Ecken, wenn Tagesbesucher unterwegs sind.",
      tr: "Stuttgart Schloss Solitude için düğün fotoğrafçısı – akışı, terastaki ışığı ve ziyaretçi yoğunken sakin kalan köşeleri biliyoruz.",
    },
    body: {
      de: [
        "Schloss Solitude gehört zu den schönsten Trauorten der Region: die weiße Rokoko-Fassade, die lange Sichtachse Richtung Ludwigsburg und eine Terrasse, die abends im warmen Gegenlicht liegt. Für die Fotografie heißt das vor allem eines – hier entstehen große, ruhige Bilder, wenn man den richtigen Zeitpunkt trifft.",
        "Weil das Schloss tagsüber öffentlich zugänglich ist, planen wir das Paarshooting bewusst in die Randzeiten. Zwischen 18:00 und 19:30 Uhr wird es merklich ruhiger, gleichzeitig steht die Sonne tief über der Sichtachse. Wir reservieren dafür rund 40 Minuten und sind rechtzeitig zurück, bevor der Empfang beginnt.",
        "Für die Trauung selbst arbeiten wir ohne Blitz und mit lichtstarken Festbrennweiten. Die Fenster im Weißen Saal geben genug Licht für saubere, natürliche Aufnahmen – auch im Winter.",
      ],
      tr: [
        "Schloss Solitude bölgenin en güzel nikah mekânlarından biri: beyaz rokoko cephe, Ludwigsburg yönüne uzanan görüş ekseni ve akşamları sıcak kontra ışıkta kalan teras. Fotoğraf açısından bu şu demek – doğru saati tutturursanız burada büyük, sakin kareler çıkıyor.",
        "Saray gündüz halka açık olduğu için çift çekimini bilerek sakin saatlere alıyoruz. 18:00–19:30 arası gözle görülür şekilde boşalıyor, aynı anda güneş de eksenin üzerinde alçalıyor. Buna yaklaşık 40 dakika ayırıyor ve karşılama başlamadan geri dönüyoruz.",
        "Nikahta flaş kullanmıyor, geniş diyaframlı sabit lenslerle çalışıyoruz. Beyaz Salon'un pencereleri kışın bile temiz ve doğal kareler için yeterli ışık veriyor.",
      ],
    },
    light: {
      de: "Beste Zeit für Portraits: 60–90 Minuten vor Sonnenuntergang auf der Südterrasse. Mittags hartes Licht – dann besser die Arkaden nutzen.",
      tr: "Portre için en iyi zaman: gün batımından 60–90 dakika önce güney terası. Öğle saatlerinde ışık sert – o zaman revakları kullanmak daha iyi.",
    },
    timing: [
      { time: "14:00", what: { de: "Getting Ready im Hotel, Anfahrt ca. 20 Min. aus der Innenstadt", tr: "Otelde hazırlık, merkezden yaklaşık 20 dk yol" } },
      { time: "15:30", what: { de: "Trauung im Weißen Saal – verfügbares Licht, kein Blitz", tr: "Beyaz Salon'da nikah – mevcut ışık, flaşsız" } },
      { time: "16:15", what: { de: "Gratulation & Gruppenbilder vor der Hauptfassade", tr: "Tebrikler ve ana cephede grup fotoğrafları" } },
      { time: "18:30", what: { de: "Paarshooting Sichtachse & Terrasse (40 Min.)", tr: "Görüş ekseni ve terasta çift çekimi (40 dk)" } },
      { time: "20:00", what: { de: "Feier – Blitz mit Bounce, Bühnenbereich mit zweiter Kamera", tr: "Düğün – sektirmeli flaş, sahne için ikinci kamera" } },
    ],
    spots: {
      de: [
        "Sichtachse Richtung Ludwigsburg – lange Perspektive, ideal für Weitwinkel",
        "Südterrasse mit Blick ins Tal – goldene Stunde",
        "Arkadengang – Schattenlicht auch bei Mittagssonne",
        "Kavaliersbauten – symmetrische Architekturbilder",
      ],
      tr: [
        "Ludwigsburg yönündeki eksen – uzun perspektif, geniş açı için ideal",
        "Vadiye bakan güney terası – altın saat",
        "Revaklı geçit – öğle güneşinde bile gölge ışığı",
        "Yan binalar – simetrik mimari kareler",
      ],
    },
    rules: {
      de: [
        "Fotogenehmigung für gewerbliche Shootings über die Schlossverwaltung",
        "Kein Blitz und keine Stative im Innenbereich während der Trauung",
        "Drohnenaufnahmen nicht gestattet – wir arbeiten mit Gimbal und Slider",
      ],
      tr: [
        "Ticari çekimler için saray idaresinden izin gerekiyor",
        "Nikah sırasında iç mekânda flaş ve tripod yok",
        "Drone yasak – gimbal ve slider ile çalışıyoruz",
      ],
    },
    faq: [
      {
        q: { de: "Habt ihr schon auf Schloss Solitude fotografiert?", tr: "Schloss Solitude'da daha önce çekim yaptınız mı?" },
        a: {
          de: "Ja, mehrfach. Wir kennen die Abläufe der Schlossverwaltung, die Wege für kurze Umbaupausen und die Stellen, an denen auch samstags keine Besuchergruppen im Bild stehen.",
          tr: "Evet, birçok kez. Saray idaresinin işleyişini, kısa aralarda kullanılan geçişleri ve cumartesileri bile ziyaretçi grubunun kadraja girmediği noktaları biliyoruz.",
        },
      },
      {
        q: { de: "Wie viel Zeit braucht ihr für das Paarshooting am Schloss?", tr: "Sarayda çift çekimi için ne kadar süre gerekiyor?" },
        a: {
          de: "40 Minuten reichen aus. Wenn ihr die Sichtachse und die Terrasse wollt, planen wir sie in die Stunde vor Sonnenuntergang.",
          tr: "40 dakika yeterli. Eksen ve terası istiyorsanız bunu gün batımından önceki saate planlıyoruz.",
        },
      },
    ],
  },
  {
    slug: "residenzschloss-ludwigsburg",
    name: "Residenzschloss Ludwigsburg",
    type: { de: "Schloss", tr: "Saray" },
    city: "Ludwigsburg",
    citySlug: "ludwigsburg",
    address: "Schlossstraße 30, 71634 Ludwigsburg",
    capacity: { de: "je nach Saal 50–250 Gäste", tr: "salona göre 50–250 kişi" },
    lead: {
      de: "Hochzeitsfotograf für das Residenzschloss Ludwigsburg – barocke Symmetrie, klare Bildsprache und ein Zeitplan, der zum Blühenden Barock passt.",
      tr: "Residenzschloss Ludwigsburg için düğün fotoğrafçısı – barok simetri, net görsel dil ve Blühendes Barock'a uyan bir zaman planı.",
    },
    body: {
      de: [
        "Das Residenzschloss ist eine der größten barocken Schlossanlagen Deutschlands – und für die Fotografie ein Geschenk: symmetrische Innenhöfe, lange Fluchten, immer eine Wand im Schatten. Selbst an einem grellen Julitag findet man hier innerhalb von fünf Gehminuten weiches Licht.",
        "Viele Paare kombinieren die Trauung im Schloss mit einem Shooting im Blühenden Barock. Das lohnt sich, braucht aber Planung: die Genehmigung muss vorab beantragt werden und der Weg vom Innenhof bis zu den Rosengärten dauert im Kleid gut zehn Minuten. Wir kalkulieren diesen Puffer von Anfang an mit ein.",
        "Für den Abend gilt: die beleuchtete Fassade ergibt zur blauen Stunde – etwa 20 Minuten nach Sonnenuntergang – Bilder, die tagsüber nicht möglich sind. Zwei Minuten Unterbrechung der Feier reichen dafür völlig.",
      ],
      tr: [
        "Residenzschloss Almanya'nın en büyük barok saray komplekslerinden biri – ve fotoğraf için tam bir hediye: simetrik iç avlular, uzun perspektifler, her saatte gölgede kalan bir duvar. Temmuzun en sert gününde bile beş dakika yürüyüşle yumuşak ışık bulunuyor.",
        "Birçok çift saraydaki nikahı Blühendes Barock çekimiyle birleştiriyor. Buna değer ama planlama ister: izin önceden alınmalı ve iç avludan gül bahçelerine yürüyüş gelinlikle on dakikayı buluyor. Bu payı baştan hesaba katıyoruz.",
        "Akşam için: aydınlatılmış cephe mavi saatte – gün batımından yaklaşık 20 dakika sonra – gündüz mümkün olmayan kareler veriyor. Bunun için düğüne iki dakika ara vermek yeterli.",
      ],
    },
    light: {
      de: "Innenhöfe liefern ganztags weiches Schattenlicht. Blaue Stunde an der Hauptfassade: ca. 20 Minuten nach Sonnenuntergang.",
      tr: "İç avlular gün boyu yumuşak gölge ışığı veriyor. Ana cephede mavi saat: gün batımından ~20 dakika sonra.",
    },
    timing: [
      { time: "11:00", what: { de: "Getting Ready, Detailaufnahmen von Ringen, Kleid und Papeterie", tr: "Hazırlık; yüzük, gelinlik ve davetiye detayları" } },
      { time: "13:00", what: { de: "Trauung – verfügbares Licht, zweite Kamera für Reaktionen der Gäste", tr: "Nikah – mevcut ışık, misafir tepkileri için ikinci kamera" } },
      { time: "14:00", what: { de: "Sektempfang im Innenhof, lockere Reportage", tr: "İç avluda karşılama, serbest belgesel çekim" } },
      { time: "15:30", what: { de: "Shooting Blühendes Barock (Genehmigung erforderlich)", tr: "Blühendes Barock çekimi (izin gerekli)" } },
      { time: "21:30", what: { de: "Blaue Stunde an der Hauptfassade – 10 Minuten", tr: "Ana cephede mavi saat – 10 dakika" } },
    ],
    spots: {
      de: [
        "Nördlicher Innenhof – symmetrisch, weiches Licht",
        "Treppenhaus mit Handlauf – klassisches Kleid-Detail",
        "Rosengarten im Blühenden Barock – nur mit Genehmigung",
        "Hauptfassade zur blauen Stunde – beleuchtet",
      ],
      tr: [
        "Kuzey iç avlu – simetrik, yumuşak ışık",
        "Trabzanlı merdiven – klasik gelinlik detayı",
        "Blühendes Barock gül bahçesi – sadece izinle",
        "Mavi saatte aydınlatılmış ana cephe",
      ],
    },
    rules: {
      de: [
        "Gewerbliche Fotografie im Blühenden Barock ist genehmigungs- und kostenpflichtig",
        "Im Museumsbereich ist Blitzlicht untersagt",
        "Fahrzeuge nur über die ausgewiesenen Zufahrten – Anfahrt großzügig planen",
      ],
      tr: [
        "Blühendes Barock'ta ticari çekim izinli ve ücretli",
        "Müze bölümünde flaş yasak",
        "Araçlar yalnızca belirlenen girişlerden – yol için bol zaman bırakın",
      ],
    },
    faq: [
      {
        q: { de: "Kümmert ihr euch um die Fotogenehmigung im Blühenden Barock?", tr: "Blühendes Barock çekim iznini siz mi alıyorsunuz?" },
        a: {
          de: "Auf Wunsch ja. Wir übernehmen die Anmeldung und stimmen den Zeitraum so ab, dass er in euren Ablauf passt. Die Gebühr wird direkt mit dem Betreiber abgerechnet.",
          tr: "İsterseniz evet. Başvuruyu biz yapıyor, saati programınıza uyacak şekilde ayarlıyoruz. Ücret doğrudan işletmeye ödeniyor.",
        },
      },
      {
        q: { de: "Lohnt sich ein Shooting zur blauen Stunde?", tr: "Mavi saat çekimi değer mi?" },
        a: {
          de: "Sehr. Die beleuchtete Fassade ergibt zwei bis drei Bilder, die man im Album später garantiert groß druckt – und es kostet euch nur zehn Minuten Feier.",
          tr: "Kesinlikle. Aydınlatılmış cephe, albümde büyük basacağınız iki üç kare veriyor – size sadece on dakikaya mal oluyor.",
        },
      },
    ],
  },
  {
    slug: "si-centrum-stuttgart",
    name: "SI-Centrum Stuttgart",
    type: { de: "Eventlocation & Hotel", tr: "Etkinlik mekânı & otel" },
    city: "Stuttgart",
    citySlug: "stuttgart",
    address: "Plieninger Straße 100, 70567 Stuttgart",
    capacity: { de: "bis ca. 800 Gäste", tr: "yaklaşık 800 kişiye kadar" },
    lead: {
      de: "Hochzeitsfotograf & Videograf im SI-Centrum Stuttgart – erfahren mit großen Feiern, Bühnenprogramm und schwierigem Kunstlicht.",
      tr: "SI-Centrum Stuttgart'ta düğün fotoğrafçısı ve videografı – büyük düğünler, sahne programı ve zor yapay ışıkta tecrübeli.",
    },
    body: {
      de: [
        "Große Säle sind fotografisch anspruchsvoll: viel Mischlicht, farbige LED-Spots, dunkle Decken, an denen sich kein Blitz sauber bouncen lässt. Wer hier gute Bilder will, braucht ein Team, das mit entfesseltem Licht arbeitet – nicht nur mit dem Blitz auf der Kamera.",
        "Wir bauen im SI-Centrum in der Regel zwei Blitzstative an den Saalrändern auf. Das ergibt saubere Trennung vom Hintergrund, natürliche Hauttöne und Bilder vom Einzug, die nicht flach wirken. Parallel filmt das Video-Team mit Gimbal – abgestimmt, damit sich Foto und Video nicht gegenseitig im Bild stehen.",
        "Weil Hotel, Säle und Parkhaus im selben Komplex liegen, sind die Wege kurz. Das Getting Ready findet meist im Hotelzimmer statt: Fenster nach Süden, gute Größe, wir arbeiten dort ausschließlich mit Tageslicht.",
      ],
      tr: [
        "Büyük salonlar fotoğraf açısından zorlu: karışık ışık, renkli LED spotlar, flaşın sektirilemediği koyu tavanlar. Burada iyi kare isteyen çift, sadece kamera üstü flaşla değil harici ışıkla çalışan bir ekibe ihtiyaç duyar.",
        "SI-Centrum'da genelde salonun iki kenarına flaş ayağı kuruyoruz. Bu, arka plandan temiz ayrışma, doğal ten tonları ve düz görünmeyen giriş kareleri veriyor. Aynı anda video ekibi gimbal ile çekiyor – foto ve video birbirinin kadrajına girmeyecek şekilde koordineli.",
        "Otel, salonlar ve otopark aynı kompleks içinde olduğu için mesafeler kısa. Hazırlık genelde otel odasında: güneye bakan pencere, geniş oda; orada sadece gün ışığıyla çalışıyoruz.",
      ],
    },
    light: {
      de: "Sehr dunkler Saal, farbiges Kunstlicht. Wir arbeiten mit zwei entfesselten Blitzen plus lichtstarken Objektiven (f/1.4–1.8).",
      tr: "Çok karanlık salon, renkli yapay ışık. İki harici flaş ve geniş diyaframlı lenslerle (f/1.4–1.8) çalışıyoruz.",
    },
    timing: [
      { time: "13:00", what: { de: "Getting Ready im Hotelzimmer – Tageslicht am Fenster", tr: "Otel odasında hazırlık – pencerede gün ışığı" } },
      { time: "16:00", what: { de: "First Look & Paarshooting draußen, bevor die Gäste eintreffen", tr: "İlk görüşme ve dışarıda çift çekimi, misafirler gelmeden" } },
      { time: "18:00", what: { de: "Empfang, Saaldekor und Details vor dem Einzug", tr: "Karşılama, salon süslemesi ve giriş öncesi detaylar" } },
      { time: "19:00", what: { de: "Einzug mit Bühnenlicht – zwei Fotografen, zwei Perspektiven", tr: "Sahne ışığında giriş – iki fotoğrafçı, iki açı" } },
      { time: "22:00", what: { de: "Party, Konfetti, offene Reportage bis Ende", tr: "Parti, konfeti, sona kadar serbest çekim" } },
    ],
    spots: {
      de: [
        "Hotelzimmer am Fenster – Getting Ready mit weichem Tageslicht",
        "Foyer mit Glasfront – Empfangsbilder ohne Blitz",
        "Bühne & Einzug – entfesseltes Blitzsetup",
        "Außenbereich Richtung Filderpark für 20 Minuten Paarzeit",
      ],
      tr: [
        "Otel odasında pencere kenarı – yumuşak gün ışığında hazırlık",
        "Cam cepheli fuaye – flaşsız karşılama kareleri",
        "Sahne ve giriş – harici flaş kurulumu",
        "20 dakikalık çift çekimi için Filderpark yönündeki dış alan",
      ],
    },
    rules: {
      de: [
        "Absprache mit der Haustechnik wegen Bühnenlicht und Nebelmaschine",
        "Stative im Gästebereich nur am Rand",
        "Zeitfenster für Dekoraufnahmen vor Einlass mit dem Veranstalter klären",
      ],
      tr: [
        "Sahne ışığı ve sis makinesi için teknik ekiple görüşme",
        "Misafir alanında tripodlar yalnızca kenarda",
        "Süsleme çekimi için giriş öncesi zaman aralığını organizatörle netleştirin",
      ],
    },
    faq: [
      {
        q: { de: "Werden Bilder im dunklen Saal körnig?", tr: "Karanlık salonda kareler grenli çıkar mı?" },
        a: {
          de: "Nicht, wenn richtig geleuchtet wird. Wir arbeiten mit zwei entfesselten Blitzen und Vollformatkameras, dadurch bleiben die Aufnahmen sauber und die Hauttöne natürlich.",
          tr: "Doğru ışıkla hayır. İki harici flaş ve full frame gövdelerle çalışıyoruz; kareler temiz, ten tonları doğal kalıyor.",
        },
      },
      {
        q: { de: "Kommt ihr auch für Hochzeiten mit 600 Gästen?", tr: "600 kişilik düğünlere de geliyor musunuz?" },
        a: {
          de: "Ja. Ab 300 Gästen empfehlen wir zwei Fotografen und zwei Videografen, damit Bühne, Tische und Empfang gleichzeitig abgedeckt sind.",
          tr: "Evet. 300 kişiden itibaren iki fotoğrafçı ve iki videograf öneriyoruz; sahne, masalar ve karşılama aynı anda çekiliyor.",
        },
      },
    ],
  },
  {
    slug: "alte-kelter-fellbach",
    name: "Alte Kelter Fellbach",
    type: { de: "Festhalle / Kelter", tr: "Düğün salonu / şaraphane" },
    city: "Fellbach",
    citySlug: "waiblingen",
    address: "Kelterweg 1, 70734 Fellbach",
    capacity: { de: "bis ca. 500 Gäste", tr: "yaklaşık 500 kişiye kadar" },
    lead: {
      de: "Hochzeitsfotograf für die Alte Kelter in Fellbach – warmes Holz, große Feiern und Weinberge fünf Minuten entfernt.",
      tr: "Fellbach Alte Kelter için düğün fotoğrafçısı – sıcak ahşap, büyük düğünler ve beş dakika ötede bağlar.",
    },
    body: {
      de: [
        "Die Alte Kelter ist eine der beliebtesten Hochzeitslocations im Rems-Murr-Kreis: massive Holzbalken, hohe Decken und ein Raumgefühl, das auch bei 400 Gästen nicht kalt wirkt. Fotografisch ist das Holz ein Vorteil – Blitzlicht bekommt hier eine warme, angenehme Färbung, statt wie in weißen Sälen flach zu wirken.",
        "Der größte Trumpf liegt aber draußen: die Rebhänge am Kappelberg sind in fünf Minuten zu Fuß erreichbar. Wer die goldene Stunde nutzt, bekommt Portraits, die aussehen wie aus der Toskana – ohne die Feier länger als eine halbe Stunde zu verlassen.",
        "Für den Ablauf empfehlen wir, den Einzug nicht zu spät zu legen. Wenn das Tageslicht durch die Hallenfenster noch mitspielt, entstehen deutlich lebendigere Bilder als bei reinem Kunstlicht.",
      ],
      tr: [
        "Alte Kelter, Rems-Murr bölgesinin en sevilen düğün mekânlarından biri: kalın ahşap kirişler, yüksek tavan ve 400 kişide bile soğuk durmayan bir atmosfer. Fotoğraf açısından ahşap avantaj – flaş ışığı beyaz salonlardaki gibi düz kalmıyor, sıcak bir renk alıyor.",
        "Ama asıl kozu dışarıda: Kappelberg bağları beş dakika yürüme mesafesinde. Altın saati kullanan çift, düğünden yarım saatten fazla ayrılmadan Toskana havasında portreler alıyor.",
        "Akış için önerimiz: girişi çok geç saate koymayın. Salon pencerelerinden gün ışığı hâlâ giriyorsa, sadece yapay ışığa göre çok daha canlı kareler çıkıyor.",
      ],
    },
    light: {
      de: "Warmes Holz reflektiert Blitzlicht angenehm. Tageslicht durch die Seitenfenster bis ca. eine Stunde vor Sonnenuntergang nutzbar.",
      tr: "Sıcak ahşap flaş ışığını hoş yansıtıyor. Yan pencerelerden gelen gün ışığı, gün batımından ~1 saat öncesine kadar kullanılabilir.",
    },
    timing: [
      { time: "15:00", what: { de: "Dekoaufnahmen im leeren Saal – vor dem Einlass", tr: "Boş salonda süsleme çekimi – misafir girişinden önce" } },
      { time: "17:00", what: { de: "Empfang draußen, Reportage der Begrüßung", tr: "Dışarıda karşılama, belgesel çekim" } },
      { time: "19:30", what: { de: "Paarshooting Rebhänge Kappelberg (30 Min.)", tr: "Kappelberg bağlarında çift çekimi (30 dk)" } },
      { time: "20:30", what: { de: "Einzug, Tanz, Programm mit entfesseltem Licht", tr: "Giriş, dans, harici ışıkla program" } },
    ],
    spots: {
      de: [
        "Rebhänge Kappelberg – goldene Stunde, 5 Gehminuten",
        "Holzbalkenhalle im Gegenlicht der Seitenfenster",
        "Innenhof / Vorplatz für Gruppenbilder",
        "Weinberg-Treppen für Aufnahmen von oben",
      ],
      tr: [
        "Kappelberg bağları – altın saat, 5 dakika yürüyüş",
        "Yan pencerelerin kontra ışığında ahşap kirişli salon",
        "Grup fotoğrafları için iç avlu / meydan",
        "Yukarıdan çekimler için bağ merdivenleri",
      ],
    },
    rules: {
      de: [
        "Dekoaufnahmen nur im Zeitfenster vor dem Gästeeinlass möglich",
        "Rauchmaschinen mit dem Hallenbetrieb abstimmen",
        "Nachtaufnahmen draußen benötigen ein zusätzliches Dauerlicht – bringen wir mit",
      ],
      tr: [
        "Süsleme çekimi yalnızca misafir girişinden önceki aralıkta mümkün",
        "Sis makinesi için salon yönetimiyle görüşün",
        "Dışarıda gece çekimi ek sürekli ışık gerektirir – biz getiriyoruz",
      ],
    },
    faq: [
      {
        q: { de: "Wie kommen wir in die Weinberge, ohne die Feier lange zu verlassen?", tr: "Düğünden uzun süre ayrılmadan bağlara nasıl çıkarız?" },
        a: {
          de: "Der Aufstieg dauert etwa fünf Minuten. Wir planen 30 Minuten insgesamt ein – die Gäste merken kaum, dass ihr weg wart.",
          tr: "Çıkış yaklaşık beş dakika. Toplam 30 dakika planlıyoruz – misafirler ayrıldığınızı neredeyse fark etmiyor.",
        },
      },
    ],
  },
  {
    slug: "schloss-hohenheim",
    name: "Schloss Hohenheim",
    type: { de: "Schloss", tr: "Saray" },
    city: "Stuttgart-Hohenheim",
    citySlug: "stuttgart",
    address: "Schloss Hohenheim, 70599 Stuttgart",
    capacity: { de: "bis ca. 150 Gäste", tr: "yaklaşık 150 kişiye kadar" },
    lead: {
      de: "Hochzeitsfotograf für Schloss Hohenheim – heller Klassizismus, ein weitläufiger Park und viel Grün für Portraits.",
      tr: "Schloss Hohenheim için düğün fotoğrafçısı – aydınlık neoklasik mimari, geniş bir park ve portreler için bol yeşil.",
    },
    body: {
      de: [
        "Schloss Hohenheim ist die ruhige Alternative zu den großen Schlossanlagen. Die helle Fassade wirft weiches Reflexlicht auf das Brautpaar, der Park liefert Schatten, wenn die Sonne zu hart steht, und der Exotische Garten gibt Bilder, die man nicht sofort einer Region zuordnet.",
        "Weil das Gelände zur Universität gehört, ist es an Wochenenden angenehm leer. Für Hochzeiten mit kleiner Gästezahl ist das ideal: keine Wartezeiten, keine Zuschauer, kurze Wege zwischen Trauort, Empfang und Portraits.",
      ],
      tr: [
        "Schloss Hohenheim, büyük saray komplekslerinin sakin alternatifi. Aydınlık cephe çifte yumuşak yansıma ışığı veriyor, park güneş sertleştiğinde gölge sunuyor ve Egzotik Bahçe, hangi bölgeye ait olduğu hemen anlaşılmayan kareler çıkarıyor.",
        "Alan üniversiteye ait olduğu için hafta sonları oldukça boş. Küçük davetli sayılı düğünler için ideal: bekleme yok, izleyici yok, nikah–karşılama–portre arası mesafeler kısa.",
      ],
    },
    light: {
      de: "Helle Fassade als natürlicher Reflektor. Park mit gleichmäßigem Schattenlicht auch mittags.",
      tr: "Aydınlık cephe doğal reflektör görevi görüyor. Park öğlen bile dengeli gölge ışığı veriyor.",
    },
    timing: [
      { time: "12:00", what: { de: "Trauung – ruhiger Rahmen, verfügbares Licht", tr: "Nikah – sakin ortam, mevcut ışık" } },
      { time: "13:00", what: { de: "Gratulation und Gruppenbilder vor der Fassade", tr: "Tebrikler ve cephe önünde grup fotoğrafları" } },
      { time: "14:30", what: { de: "Portraits im Park und im Exotischen Garten", tr: "Parkta ve Egzotik Bahçe'de portreler" } },
      { time: "17:00", what: { de: "Feier – Reportage bis zum ersten Tanz", tr: "Düğün – ilk dansa kadar belgesel çekim" } },
    ],
    spots: {
      de: [
        "Hauptfassade – heller Reflektor, ideal für Gruppenbilder",
        "Exotischer Garten – ungewöhnliches Grün",
        "Landesarboretum – alte Baumbestände, weiches Licht",
        "Innenhof – geschützt bei Regen",
      ],
      tr: [
        "Ana cephe – aydınlık reflektör, grup fotoğrafı için ideal",
        "Egzotik Bahçe – alışılmadık bir yeşil",
        "Arboretum – yaşlı ağaçlar, yumuşak ışık",
        "İç avlu – yağmurda korunaklı",
      ],
    },
    rules: {
      de: ["Universitätsgelände – Termine am Wochenende sind deutlich ruhiger", "Gewerbliche Aufnahmen im Park vorab anmelden"],
      tr: ["Üniversite alanı – hafta sonu randevuları çok daha sakin", "Parkta ticari çekim için önceden bildirim gerekli"],
    },
    faq: [
      {
        q: { de: "Ist Schloss Hohenheim auch bei Regen eine gute Wahl?", tr: "Yağmurlu havada Schloss Hohenheim iyi bir tercih mi?" },
        a: {
          de: "Ja. Innenhof und Arkaden bieten genügend geschützte Flächen, in denen wir auch bei Regen saubere Portraits machen können.",
          tr: "Evet. İç avlu ve revaklar, yağmurda da temiz portre çekebileceğimiz yeterli korunaklı alan sunuyor.",
        },
      },
    ],
  },
  {
    slug: "villa-berg",
    name: "Villa Berg & Umgebung",
    type: { de: "Villa / Park", tr: "Villa / park" },
    city: "Stuttgart-Ost",
    citySlug: "stuttgart",
    address: "Villa Berg, 70190 Stuttgart",
    capacity: { de: "kleine bis mittlere Feiern", tr: "küçük ve orta ölçekli kutlamalar" },
    lead: {
      de: "Hochzeitsfotograf rund um die Villa Berg in Stuttgart-Ost – urbaner Park, ruhige Wege, ideal für Paarshootings zwischen Standesamt und Feier.",
      tr: "Stuttgart-Ost'ta Villa Berg çevresinde düğün fotoğrafçısı – şehir içinde park, sakin yollar; nikah ile düğün arasındaki çift çekimi için ideal.",
    },
    body: {
      de: [
        "Der Park an der Villa Berg ist unser Favorit für kurze Shootings mitten in Stuttgart. Wer im Rathaus heiratet, ist in zehn Minuten hier – und steht plötzlich zwischen alten Bäumen statt in der Innenstadt.",
        "Für Paare mit engem Zeitplan bedeutet das: 30 Minuten reichen für eine komplette Portraitserie. Kein Umziehen, kein langes Fahren, keine Parkplatzsuche.",
      ],
      tr: [
        "Villa Berg parkı, Stuttgart'ın göbeğinde kısa çekimler için favorimiz. Belediyede nikah kıyan çift on dakikada burada – ve birden şehir merkezi yerine yaşlı ağaçların arasında.",
        "Programı sıkışık çiftler için bu şu demek: 30 dakika tam bir portre serisine yetiyor. Kıyafet değişimi yok, uzun yol yok, park yeri derdi yok.",
      ],
    },
    light: {
      de: "Alter Baumbestand streut das Licht. Auch mittags nutzbar – im Gegensatz zu offenen Plätzen in der Innenstadt.",
      tr: "Yaşlı ağaçlar ışığı yayıyor. Şehir merkezindeki açık meydanların aksine öğlen de kullanılabilir.",
    },
    timing: [
      { time: "10:30", what: { de: "Trauung im Standesamt Stuttgart", tr: "Stuttgart nikah dairesinde tören" } },
      { time: "11:30", what: { de: "Fahrt zum Park (10 Min.)", tr: "Parka geçiş (10 dk)" } },
      { time: "11:45", what: { de: "Paarshooting im Park – 30 Minuten", tr: "Parkta çift çekimi – 30 dakika" } },
      { time: "13:00", what: { de: "Weiterfahrt zur Feier", tr: "Düğün mekânına hareket" } },
    ],
    spots: {
      de: ["Alleen im Park – lange Fluchten", "Treppenanlagen – Höhe und Struktur im Bild", "Wiesenflächen – weiches Gegenlicht am Nachmittag"],
      tr: ["Park içindeki ağaçlı yollar – uzun perspektif", "Merdivenler – kadrajda kot farkı ve doku", "Çayırlar – öğleden sonra yumuşak kontra ışık"],
    },
    rules: {
      de: ["Öffentlicher Park – bitte Rücksicht auf Besucher nehmen", "Kein Aufbau von großen Lichtstativen auf den Hauptwegen"],
      tr: ["Halka açık park – ziyaretçilere dikkat", "Ana yollarda büyük ışık ayakları kurulmuyor"],
    },
    faq: [
      {
        q: { de: "Reichen 30 Minuten für ein Paarshooting wirklich?", tr: "Çift çekimi için 30 dakika gerçekten yeter mi?" },
        a: {
          de: "Ja, wenn die Location stimmt. Wir arbeiten mit klaren Ansagen statt endlosem Posieren – 30 Minuten ergeben in der Regel 40 bis 60 fertige Bilder.",
          tr: "Mekân doğruysa evet. Bitmeyen poz yerine net yönlendirmelerle çalışıyoruz – 30 dakika genelde 40–60 bitmiş kare veriyor.",
        },
      },
    ],
  },
  {
    slug: "filderhalle-leinfelden",
    name: "Filderhalle Leinfelden",
    type: { de: "Festhalle / Eventlocation", tr: "Düğün salonu / etkinlik mekânı" },
    city: "Leinfelden-Echterdingen",
    citySlug: "esslingen",
    address: "Bahnhofstraße 61, 70771 Leinfelden-Echterdingen",
    capacity: { de: "bis ca. 600 Gäste", tr: "yaklaşık 600 kişiye kadar" },
    lead: {
      de: "Hochzeitsfotograf & Videograf für die Filderhalle in Leinfelden-Echterdingen – große Feiern, klare Reportage, planbarer Ablauf.",
      tr: "Leinfelden-Echterdingen Filderhalle için düğün fotoğrafçısı ve videografı – büyük düğünler, net belgesel çekim, planlanabilir akış.",
    },
    body: {
      de: [
        "Die Filderhalle ist eine der meistgebuchten Hallen im Süden Stuttgarts – gute Autobahnanbindung, Parkplätze direkt am Haus und ein Saal, der sich flexibel bestuhlen lässt. Fotografisch ist sie ein klassischer Fall: neutraler Raum, viel Kunstlicht, das Bild entsteht durch Menschen und Licht, nicht durch die Architektur.",
        "Deshalb legen wir hier besonderen Wert auf die Dekoaufnahmen vor dem Einlass und auf ein sauberes Blitzsetup für Einzug und Bühne. Für Portraits fahren wir mit dem Paar zehn Minuten Richtung Felder – dort entsteht der Kontrast zur Halle.",
      ],
      tr: [
        "Filderhalle, Stuttgart'ın güneyinde en çok tercih edilen salonlardan biri – otoyola yakın, kapıda otopark ve esnek oturma düzeni. Fotoğraf açısından klasik bir durum: nötr mekân, bol yapay ışık; kareyi mimari değil insanlar ve ışık kuruyor.",
        "Bu yüzden burada giriş öncesi süsleme çekimine ve giriş/sahne için temiz bir flaş kurulumuna özellikle önem veriyoruz. Portreler için çiftle on dakika tarlalara doğru çıkıyoruz – salona kontrast orada oluşuyor.",
      ],
    },
    light: {
      de: "Neutrales Hallenlicht. Wir setzen zwei entfesselte Blitze und nutzen Tageslicht durch das Foyer, solange es geht.",
      tr: "Nötr salon ışığı. İki harici flaş kuruyor, mümkün olduğunca fuayedeki gün ışığını kullanıyoruz.",
    },
    timing: [
      { time: "16:00", what: { de: "Saal- und Dekoaufnahmen vor dem Einlass", tr: "Giriş öncesi salon ve süsleme çekimi" } },
      { time: "17:30", what: { de: "Empfang im Foyer – Tageslicht", tr: "Fuayede karşılama – gün ışığı" } },
      { time: "19:00", what: { de: "Paarshooting draußen, 20 Minuten", tr: "Dışarıda çift çekimi, 20 dakika" } },
      { time: "20:00", what: { de: "Einzug & Programm mit Blitzsetup", tr: "Giriş ve program, flaş kurulumuyla" } },
    ],
    spots: {
      de: [
        "Foyer mit Glasfront – natürliches Licht für Empfangsbilder",
        "Felder und Alleen Richtung Echterdingen – 10 Minuten Fahrt",
        "Haupteingang bei Nacht – Dauerlicht für Gruppenaufnahmen",
      ],
      tr: [
        "Cam cepheli fuaye – karşılama kareleri için doğal ışık",
        "Echterdingen yönündeki tarlalar ve ağaçlı yollar – 10 dakika",
        "Gece ana giriş – grup çekimleri için sürekli ışık",
      ],
    },
    rules: {
      de: ["Zeitfenster für Dekoaufnahmen mit dem Veranstalter fest einplanen", "Technik-Absprache wegen Bühnenlicht empfohlen"],
      tr: ["Süsleme çekimi için organizatörle net bir saat belirleyin", "Sahne ışığı için teknik ekiple görüşülmesi önerilir"],
    },
    faq: [
      {
        q: { de: "Wann sollten die Dekoaufnahmen entstehen?", tr: "Süsleme çekimleri ne zaman yapılmalı?" },
        a: {
          de: "Immer vor dem Gästeeinlass. Sobald die Halle voll ist, sind saubere Übersichtsbilder der Dekoration praktisch nicht mehr möglich.",
          tr: "Her zaman misafirler girmeden önce. Salon dolduktan sonra süslemenin temiz genel karesi pratikte mümkün olmuyor.",
        },
      },
    ],
  },
];

export const venueBySlug = (slug: string) => venues.find((v) => v.slug === slug);
export const venuesByCity = (citySlug: string) => venues.filter((v) => v.citySlug === citySlug);
