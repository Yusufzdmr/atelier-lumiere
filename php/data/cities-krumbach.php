<?php
/**
 * Die Stadtseiten rund um Krumbach.
 *
 * Jede Seite muss etwas sagen, das nur für diese Stadt gilt – sonst sind es
 * zehn Türseiten mit ausgetauschtem Ortsnamen, und Google behandelt sie auch
 * so. Deshalb steht hier nirgends „malerische Kulisse“, sondern: wo das Licht
 * wann steht, was bei Regen trägt, wie lang die Wege sind.
 *
 * Eingespielt mit: php bin/cities.php
 *
 * ACHTUNG, vor dem Livegang zu prüfen:
 *   - Fahrzeiten sind gerundete Schätzungen ab Krumbach
 *   - Standesämter, Öffnungszeiten und Gebühren sind bewusst NICHT genannt;
 *     sie ändern sich und gehören, wenn überhaupt, aus erster Hand hierher
 *   - Für Bregenz und St. Gallen: Auslandsaufträge steuerlich klären
 */

return [

    /* ------------------------------- Günzburg ------------------------------- */
    [
        'slug'  => 'guenzburg',
        'name'  => 'Günzburg',
        'kreis' => ['de' => 'Landkreis Günzburg', 'tr' => 'Günzburg ilçesi'],
        'drive' => ['de' => 'rund 25 Minuten', 'tr' => 'yaklaşık 25 dakika'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Günzburg – die nächste Stadt von Krumbach aus, und die mit der geschlossensten Altstadt der Region.',
            'tr' => 'Günzburg’da düğün fotoğrafçısı ve videografı – Krumbach’a en yakın şehir ve bölgenin en bütün kalmış eski kenti.',
        ],
        'body' => [
            'de' => [
                'Günzburg ist für uns Nachbarschaft. Wir sind in einer knappen halben Stunde da, und wir kennen die Stadt nicht von einer Ortsbesichtigung, sondern von Hochzeiten zu jeder Jahreszeit. Das merkt man an Kleinigkeiten: dass der Marktplatz zum Schloss hin ansteigt und Gruppenbilder deshalb von unten besser aussehen als von oben. Dass die Frauenkirche von Dominikus Zimmermann innen ein Licht hat, mit dem man ohne Blitz arbeiten kann, wenn man weiß, wo man steht.',
                'Die Altstadt ist kompakt genug, dass Trauung, Sektempfang und Paarshooting ohne Umziehen der ganzen Gesellschaft funktionieren. Das ist mehr wert, als es klingt: Jede Fahrt zwischen zwei Orten kostet vierzig Minuten Stimmung. Wer am Nachmittag heiratet, hat vom Schlossplatz aus bis in den frühen Abend weiches Licht; danach wird es in den Gassen schnell dunkel, und wir gehen an die Günz oder in Richtung Stadtpark.',
            ],
            'tr' => [
                'Günzburg bizim için komşu demek. Yarım saatten kısa sürede oradayız ve şehri bir keşif gezisinden değil, her mevsimden düğünlerden tanıyoruz. Bu, küçük şeylerde belli oluyor: meydanın saraya doğru yükseldiğini, bu yüzden toplu fotoğrafların aşağıdan daha iyi durduğunu bilmek gibi. Ya da Dominikus Zimmermann’ın Frauenkirche’sinin içinde, nerede duracağınızı bilirseniz flaşsız çalışılabilecek bir ışık olduğunu.',
                'Eski kent, nikâh, kokteyl ve çift çekimi bütün topluluğu taşımadan yapılabilecek kadar derli toplu. Bu kulağa geldiğinden daha değerli: iki yer arasındaki her yolculuk kırk dakikalık keyif götürüyor. Öğleden sonra evlenenler saray meydanından akşamın ilk saatlerine kadar yumuşak ışık buluyor; sonrasında dar sokaklar hızla kararıyor, biz de Günz kıyısına ya da şehir parkına doğru geçiyoruz.',
            ],
        ],
        'spots' => [
            ['name' => 'Marktplatz und Schlossplatz', 'note' => [
                'de' => 'Der Aufstieg zum Schloss gibt Tiefe. Vormittags liegt die Westseite im Schatten – dann lieber die Häuserzeile gegenüber.',
                'tr' => 'Saraya çıkan eğim derinlik veriyor. Sabahları batı yüzü gölgede – o saatte karşıdaki sıra evler daha iyi.',
            ]],
            ['name' => 'Frauenkirche', 'note' => [
                'de' => 'Heller Rokoko-Innenraum, der ohne zusätzliches Licht auskommt. Fotografieren nur nach Absprache mit der Pfarrei.',
                'tr' => 'Ek ışık gerektirmeyen aydınlık rokoko iç mekân. Çekim yalnızca kilise idaresiyle konuşulduktan sonra.',
            ]],
            ['name' => 'Günzufer und Auwald', 'note' => [
                'de' => 'Grün und ruhig, fünf Gehminuten aus der Altstadt. Die letzte Stunde vor Sonnenuntergang gehört hierher.',
                'tr' => 'Yeşil ve sakin, eski kentten beş dakika yürüyüş. Gün batımından önceki son saat buraya ait.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Kommt ihr auch für eine kleine Trauung ohne Feier?', 'tr' => 'Kutlamasız küçük bir nikâh için de gelir misiniz?'],
             'a' => ['de' => 'Ja. Für Günzburg gibt es dafür eine kurze Begleitung von zwei Stunden – Trauung, Gratulation, ein Rundgang durch die Altstadt. Anfahrt berechnen wir im Landkreis Günzburg nicht.',
                     'tr' => 'Evet. Günzburg için iki saatlik kısa bir eşlik var – nikâh, tebrikler, eski kentte bir tur. Günzburg ilçesi içinde yol ücreti almıyoruz.']],
            ['q' => ['de' => 'Lohnt sich Legoland als Kulisse?', 'tr' => 'Legoland fon olarak işe yarar mı?'],
             'a' => ['de' => 'Für Hochzeitsbilder eher nicht – es ist ein Freizeitpark mit eigenen Regeln und vielen Menschen im Bild. Wenn Familie mit Kindern angereist ist, planen wir es lieber als eigenen Programmpunkt am Folgetag ein.',
                     'tr' => 'Düğün fotoğrafı için pek değil – kendi kuralları ve karede çok insan olan bir eğlence parkı. Çocuklu aile geldiyse onu ertesi güne ayrı bir program olarak koymayı tercih ediyoruz.']],
        ],
        'venues'     => [],
        'neighbours' => ['ulm', 'neu-ulm', 'augsburg', 'memmingen'],
    ],

    /* --------------------------------- Ulm ---------------------------------- */
    [
        'slug'  => 'ulm',
        'name'  => 'Ulm',
        'kreis' => ['de' => 'Stadtkreis Ulm, Baden-Württemberg', 'tr' => 'Ulm şehri, Baden-Württemberg'],
        'drive' => ['de' => 'rund 40 Minuten', 'tr' => 'yaklaşık 40 dakika'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Ulm – Münster, Fischerviertel und Donau auf engem Raum, mit dem schwierigsten und schönsten Licht der Region.',
            'tr' => 'Ulm’da düğün fotoğrafçısı ve videografı – Münster, Fischerviertel ve Tuna dar bir alanda; bölgenin hem en zor hem en güzel ışığıyla.',
        ],
        'body' => [
            'de' => [
                'Ulm ist die Stadt in unserem Umkreis, in der man am meisten falsch machen kann. Der Münsterplatz ist weit und hell, und um die Mittagszeit steht die Sonne so, dass Gesichter unter den Augen dunkel werden. Wir legen Paarbilder deshalb selten dorthin, sondern in das Fischerviertel: schmale Gassen, Fachwerk, die Blau in mehreren Armen, und Schatten, der weich ist statt hart. Wer trotzdem das Münster im Bild haben will, bekommt es von der Herdbrücke oder von der Donaupromenade aus – mit Abstand, und ohne dass der Turm oben abgeschnitten ist.',
                'Das zweite Thema in Ulm ist Publikum. Die Altstadt ist an Samstagnachmittagen voll, und ein Brautpaar bleibt dort nicht unbemerkt. Manche Paare genießen das, andere werden still davon. Wir sprechen das vorher an und planen entsprechend: entweder früh am Morgen, wenn die Gassen leer sind, oder in den Randlagen an der Donau, wo man zu zweit sein kann, ohne aus der Stadt zu fahren.',
            ],
            'tr' => [
                'Ulm, çevremizde en çok hata yapılabilecek şehir. Münster meydanı geniş ve parlak; öğle saatlerinde güneş öyle bir açıda ki gözlerin altı kararıyor. Bu yüzden çift fotoğraflarını oraya nadiren koyuyoruz, Fischerviertel’i tercih ediyoruz: dar sokaklar, ahşap cumbalar, birkaç kola ayrılan Blau deresi ve sert değil yumuşak gölge. Yine de karede Münster isteyenler onu Herdbrücke’den ya da Tuna kıyısından alıyor – mesafeyle ve kulenin tepesi kesilmeden.',
                'Ulm’un ikinci konusu kalabalık. Eski kent cumartesi öğleden sonraları dolu ve gelin damat orada fark edilmeden kalmıyor. Bazı çiftler bundan keyif alıyor, bazıları sesini kesiyor. Bunu önceden konuşup ona göre planlıyoruz: ya sokaklar boşken sabah erken, ya da şehirden çıkmadan baş başa kalınabilen Tuna kıyısındaki kenar noktalarda.',
            ],
        ],
        'spots' => [
            ['name' => 'Fischerviertel', 'note' => [
                'de' => 'Enge Gassen an der Blau, weiches Streiflicht bis in den Nachmittag. Kopfsteinpflaster – hohe Absätze vorher bedenken.',
                'tr' => 'Blau kıyısında dar sokaklar, öğleden sonraya kadar yumuşak yan ışık. Arnavut kaldırımı – yüksek topuğu önceden düşünün.',
            ]],
            ['name' => 'Donaupromenade und Metzgerturm', 'note' => [
                'de' => 'Weite und Wasser, das Münster im Hintergrund. Am späten Abend das beste Licht der Stadt.',
                'tr' => 'Genişlik ve su, arkada Münster. Akşamın geç saatinde şehrin en iyi ışığı.',
            ]],
            ['name' => 'Münsterplatz', 'note' => [
                'de' => 'Groß und repräsentativ, aber hart im Mittagslicht und selten leer. Für Gruppenbilder besser am frühen Vormittag.',
                'tr' => 'Büyük ve gösterişli ama öğle ışığında sert ve nadiren boş. Toplu fotoğraf için sabahın erken saati daha iyi.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Braucht man in Ulm eine Genehmigung zum Fotografieren?', 'tr' => 'Ulm’da fotoğraf için izin gerekiyor mu?'],
             'a' => ['de' => 'Für ein Paar mit Fotograf im öffentlichen Raum in aller Regel nicht. Anders wird es bei Aufbauten, Drohne oder größeren Gruppen, die einen Weg blockieren – das klären wir vorher mit der Stadt, damit am Hochzeitstag niemand diskutiert.',
                     'tr' => 'Kamusal alanda fotoğrafçıyla bir çift için kural olarak gerekmiyor. Ekipman kurulumu, drone ya da yolu kapatacak büyük gruplar başka konu – onu düğün günü kimse tartışmasın diye önceden belediyeyle hallediyoruz.']],
            ['q' => ['de' => 'Ulm oder Neu-Ulm – wo sollen wir fotografieren?', 'tr' => 'Ulm mu Neu-Ulm mu – nerede çekilelim?'],
             'a' => ['de' => 'Die Donau trennt beide, die Brücke braucht vier Minuten zu Fuß. Wir nutzen oft beides: die Altstadtseite für Enge und Fachwerk, die Neu-Ulmer Seite für Weite und den Blick zurück aufs Münster.',
                     'tr' => 'İkisini Tuna ayırıyor, köprü yürüyerek dört dakika. Çoğu zaman ikisini birden kullanıyoruz: dar sokak ve ahşap için eski kent tarafı, genişlik ve Münster’e dönük manzara için Neu-Ulm tarafı.']],
        ],
        'venues'     => [],
        'neighbours' => ['neu-ulm', 'guenzburg', 'memmingen', 'augsburg'],
    ],

    /* ------------------------------- Neu-Ulm -------------------------------- */
    [
        'slug'  => 'neu-ulm',
        'name'  => 'Neu-Ulm',
        'kreis' => ['de' => 'Landkreis Neu-Ulm, Bayern', 'tr' => 'Neu-Ulm ilçesi, Bavyera'],
        'drive' => ['de' => 'rund 40 Minuten', 'tr' => 'yaklaşık 40 dakika'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Neu-Ulm – die ruhige Seite der Donau, mit Platz für Gruppenbilder und dem Münster als Hintergrund.',
            'tr' => 'Neu-Ulm’da düğün fotoğrafçısı ve videografı – Tuna’nın sakin yakası; toplu fotoğraf için yer ve arka planda Münster.',
        ],
        'body' => [
            'de' => [
                'Neu-Ulm wird unterschätzt, weil die berühmte Stadt gegenüber liegt. Fotografisch ist genau das der Vorteil: Von hier aus hat man das Ulmer Münster im Bild, ohne im Gedränge davor zu stehen. Die Donaupromenade ist breit, das Ufer ist offen, und eine Hochzeitsgesellschaft von fünfzig Personen findet Platz, ohne dass jemand auf der Straße steht.',
                'Der Glacis-Park ist der zweite Grund, warum wir gern hierherkommen. Alter Baumbestand, gepflegte Wege, im Sommer Schatten und im Herbst Farbe – und er ist groß genug, dass zwei Hochzeiten am selben Tag sich nicht begegnen. Für Paare, die es unaufgeregt mögen, ist Neu-Ulm oft die bessere Wahl als die Altstadt gegenüber, und die Bilder sagen trotzdem eindeutig, wo sie entstanden sind.',
            ],
            'tr' => [
                'Neu-Ulm hafife alınıyor, çünkü ünlü şehir tam karşıda. Fotoğraf açısından asıl avantaj bu: buradan Ulm Münster’i karede oluyor, önündeki kalabalığın içinde durmadan. Tuna kıyısı geniş, sahil açık; elli kişilik bir düğün topluluğu kimse yola taşmadan yerleşiyor.',
                'Glacis Parkı buraya gelmeyi sevmemizin ikinci sebebi. Yaşlı ağaçlar, bakımlı yollar, yazın gölge, sonbaharda renk – ve aynı gün iki düğünün karşılaşmayacağı kadar büyük. Sakin seven çiftler için Neu-Ulm çoğu zaman karşıdaki eski kentten daha iyi bir tercih; fotoğraflar yine de nerede çekildiğini net söylüyor.',
            ],
        ],
        'spots' => [
            ['name' => 'Donauufer mit Blick aufs Münster', 'note' => [
                'de' => 'Die Postkarte, ohne das Gedränge der anderen Seite. Am schönsten in der Stunde vor Sonnenuntergang.',
                'tr' => 'Kartpostal manzarası, karşı yakanın kalabalığı olmadan. En güzeli gün batımından önceki saat.',
            ]],
            ['name' => 'Glacis-Park', 'note' => [
                'de' => 'Alter Baumbestand, weicher Schatten auch mittags. Bei Regen tragen die Wege besser als Wiesenwege.',
                'tr' => 'Yaşlı ağaçlar, öğlen bile yumuşak gölge. Yağmurda yollar çayır patikalarından daha iyi taşıyor.',
            ]],
            ['name' => 'Edwin-Scharff-Haus und Umfeld', 'note' => [
                'de' => 'Klare Architektur direkt am Wasser – gute Gegenposition zum Fachwerk auf der Ulmer Seite.',
                'tr' => 'Su kenarında yalın mimari – Ulm tarafındaki ahşap dokuya iyi bir karşıtlık.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Wir feiern in Ulm, aber die Trauung ist in Neu-Ulm. Geht das?', 'tr' => 'Kutlama Ulm’da, nikâh Neu-Ulm’da. Olur mu?'],
             'a' => ['de' => 'Das ist der Normalfall und kostet kaum Zeit – über die Brücke sind es wenige Minuten. Wir planen den Wechsel so, dass er in die Fahrt der Gesellschaft fällt und nicht extra Programm wird.',
                     'tr' => 'Bu olağan durum ve neredeyse hiç zaman almıyor – köprüden birkaç dakika. Geçişi topluluğun zaten yapacağı yolculuğa denk getiriyoruz, ayrı bir program olmasın diye.']],
            ['q' => ['de' => 'Gibt es Platz für ein Gruppenbild mit allen Gästen?', 'tr' => 'Bütün misafirlerle toplu fotoğraf için yer var mı?'],
             'a' => ['de' => 'Ja, und das ist hier leichter als in den meisten Altstädten. Am Donauufer und im Glacis-Park stehen achtzig Personen, ohne dass wir jemanden auf eine Mauer stellen müssen.',
                     'tr' => 'Evet, üstelik çoğu eski kentten daha kolay. Tuna kıyısında ve Glacis Parkı’nda seksen kişi, kimseyi duvara çıkarmadan yerleşiyor.']],
        ],
        'venues'     => [],
        'neighbours' => ['ulm', 'guenzburg', 'memmingen', 'augsburg'],
    ],

    /* ------------------------------- Memmingen ------------------------------ */
    [
        'slug'  => 'memmingen',
        'name'  => 'Memmingen',
        'kreis' => ['de' => 'Kreisfreie Stadt, Tor zum Allgäu', 'tr' => 'Bağımsız şehir, Allgäu’ya açılan kapı'],
        'drive' => ['de' => 'rund 40 Minuten', 'tr' => 'yaklaşık 40 dakika'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Memmingen – eine Altstadt, die auch bei Regen funktioniert, und der kürzeste Weg ins Allgäu.',
            'tr' => 'Memmingen’de düğün fotoğrafçısı ve videografı – yağmurda da işleyen bir eski kent ve Allgäu’ya en kısa yol.',
        ],
        'body' => [
            'de' => [
                'Memmingen hat etwas, das im Sommer niemanden interessiert und im November alles rettet: Arkaden. Am Steuerhaus und rund um den Marktplatz kann man trocken stehen und trotzdem draußen sein. Wir haben hier Hochzeiten fotografiert, an denen es durchgehend geregnet hat, und die Bilder sehen nicht nach Notlösung aus. Für ein Datum im Spätherbst oder Vorfrühling ist das ein echtes Argument.',
                'Der zweite Grund ist die Geschlossenheit. Der Marktplatz mit Rathaus und Steuerhaus, das Siebendächerhaus an der Stadtbach-Ecke, die Gassen dazwischen – das liegt alles in Gehweite, und es wirkt aus jedem Winkel wie eine Kulisse, die jemand aufgebaut hat. Wer von Memmingen ins Allgäu weiterfährt, hat außerdem nach zwanzig Minuten Wiesen und Berge im Rücken, wenn es das Paar in die Landschaft zieht.',
            ],
            'tr' => [
                'Memmingen’de yazın kimsenin umursamadığı, kasımda ise her şeyi kurtaran bir şey var: revaklar. Steuerhaus’ta ve meydanın çevresinde kuru durup yine de dışarıda olabiliyorsunuz. Burada baştan sona yağmur yağan düğünler çektik ve fotoğraflar mecburiyetten çekilmiş gibi durmuyor. Geç sonbahar ya da erken ilkbahar tarihi için bu gerçek bir gerekçe.',
                'İkinci sebep bütünlük. Belediye ve Steuerhaus’lu meydan, dere köşesindeki Siebendächerhaus, aradaki sokaklar – hepsi yürüme mesafesinde ve her açıdan biri özellikle kurmuş gibi duruyor. Memmingen’den Allgäu’ya devam edenler, çift kırsala çekiliyorsa yirmi dakika sonra arkalarında çayır ve dağ buluyor.',
            ],
        ],
        'spots' => [
            ['name' => 'Marktplatz mit Steuerhaus', 'note' => [
                'de' => 'Die Arkaden sind der Regenplan. Auch bei Sonne guter Schatten, wenn der Platz zu hell wird.',
                'tr' => 'Revaklar yağmur planı. Güneşte de, meydan fazla parlarken iyi bir gölge.',
            ]],
            ['name' => 'Siebendächerhaus am Stadtbach', 'note' => [
                'de' => 'Unverwechselbar und klein – gut für zwei Personen, nicht für die ganze Gesellschaft.',
                'tr' => 'Ayırt edici ama küçük – iki kişilik, bütün topluluk için değil.',
            ]],
            ['name' => 'Stadtmauer und Westertor', 'note' => [
                'de' => 'Ruhiger als der Marktplatz, mit Grün davor. Am späten Nachmittag steht die Sonne günstig.',
                'tr' => 'Meydandan sakin, önünde yeşillik. Öğleden sonranın geç saatinde güneş uygun açıda.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Was, wenn es an unserem Termin regnet?', 'tr' => 'Tarihimizde yağmur yağarsa ne olacak?'],
             'a' => ['de' => 'In Memmingen ist das die Stadt mit dem besten Plan B im Umkreis. Wir gehen unter die Arkaden am Marktplatz, arbeiten mit den Torbögen und dem nassen Pflaster – Regen wird dann Teil der Bilder statt ihr Gegner.',
                     'tr' => 'Memmingen bu konuda çevredeki en iyi B planına sahip şehir. Meydandaki revakların altına geçiyor, kemerlerle ve ıslak taşla çalışıyoruz – yağmur o zaman fotoğrafların düşmanı değil parçası oluyor.']],
            ['q' => ['de' => 'Fahrt ihr von Memmingen aus weiter ins Allgäu?', 'tr' => 'Memmingen’den Allgäu’ya devam eder misiniz?'],
             'a' => ['de' => 'Gern. Für Paare, die Berge im Bild haben möchten, planen wir ein Zeitfenster von rund zwei Stunden ein – Hin- und Rückweg mitgerechnet. Das lohnt sich nur bei gutem Wetter, und wir entscheiden es am Morgen des Hochzeitstags gemeinsam.',
                     'tr' => 'Memnuniyetle. Karede dağ isteyen çiftler için gidiş dönüş dahil yaklaşık iki saatlik bir pencere ayırıyoruz. Bu yalnızca hava iyiyse değer; kararı düğün sabahı birlikte veriyoruz.']],
        ],
        'venues'     => [],
        'neighbours' => ['ulm', 'neu-ulm', 'guenzburg', 'friedrichshafen'],
    ],

    /* -------------------------------- Augsburg ------------------------------ */
    [
        'slug'  => 'augsburg',
        'name'  => 'Augsburg',
        'kreis' => ['de' => 'Kreisfreie Stadt, Bayerisch-Schwaben', 'tr' => 'Bağımsız şehir, Bavyera-Suabya'],
        'drive' => ['de' => 'rund 50 Minuten', 'tr' => 'yaklaşık 50 dakika'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Augsburg – Renaissance im großen Maßstab, und die einzige Stadt der Region, in der Wasser Weltkulturerbe ist.',
            'tr' => 'Augsburg’da düğün fotoğrafçısı ve videografı – büyük ölçekte Rönesans ve bölgede suyu dünya mirası olan tek şehir.',
        ],
        'body' => [
            'de' => [
                'Augsburg ist die repräsentativste Stadt in unserem Umkreis. Die Maximilianstraße ist breit wie ein Platz, das Rathaus mit dem Goldenen Saal ist keine Kulisse, sondern ein Bauwerk, das im Bild Gewicht hat, und die Fuggerei ist eine Straße für sich – die älteste bestehende Sozialsiedlung der Welt, mit Backsteinfassaden und Ruhe mitten in der Stadt. Wer Bilder möchte, die nach Anlass aussehen, findet hier mehr davon als irgendwo sonst zwischen Ulm und München.',
                'Diese Größe hat eine Kehrseite: Augsburg braucht Zeit. Zwischen Perlachturm, Fuggerei und den Wasserläufen im Lechviertel liegen Wege, die man nicht in zwanzig Minuten macht, und die Innenstadt ist auf breite Straßen gebaut, nicht auf kurze. Wir planen für Augsburg deshalb ein größeres Fenster ein oder legen uns vorher auf zwei Orte fest, statt vier halb zu sehen. Für die Wasserläufe gilt: schmale Kanäle, historische Brunnen, und am Vormittag deutlich weniger Menschen als nachmittags.',
            ],
            'tr' => [
                'Augsburg çevremizdeki en gösterişli şehir. Maximilianstraße bir meydan kadar geniş; Altın Salon’lu belediye binası bir fon değil, karede ağırlığı olan bir yapı; Fuggerei ise kendi başına bir sokak – dünyanın ayakta kalmış en eski sosyal konut yerleşimi, tuğla cepheler ve şehrin ortasında sessizlik. Anlamına yakışan fotoğraflar isteyen, Ulm ile Münih arasında bunun en fazlasını burada bulur.',
                'Bu büyüklüğün bir de öbür yüzü var: Augsburg zaman istiyor. Perlach Kulesi, Fuggerei ve Lech mahallesindeki su yolları arasında yirmi dakikada alınmayacak mesafeler var; şehir merkezi kısa yollar üzerine değil geniş caddeler üzerine kurulmuş. Bu yüzden Augsburg için ya daha geniş bir pencere ayırıyoruz ya da dördünü yarım görmektense önceden iki yere karar veriyoruz. Su yolları için şunu bilin: dar kanallar, tarihî çeşmeler ve sabahları öğleden sonraya göre belirgin biçimde az insan.',
            ],
        ],
        'spots' => [
            ['name' => 'Fuggerei', 'note' => [
                'de' => 'Backstein, Grün, kaum Durchgangsverkehr. Zutritt und Fotografieren nur nach Anmeldung – wir kümmern uns darum.',
                'tr' => 'Tuğla, yeşil, neredeyse hiç geçiş trafiği yok. Giriş ve çekim yalnız randevuyla – onu biz hallediyoruz.',
            ]],
            ['name' => 'Rathausplatz und Perlachturm', 'note' => [
                'de' => 'Große Fläche für große Gesellschaften. Mittags hart – dann in die Arkaden der Maximilianstraße ausweichen.',
                'tr' => 'Kalabalık topluluklar için geniş alan. Öğlen sert – o saatte Maximilianstraße revaklarına geçmek gerekiyor.',
            ]],
            ['name' => 'Lechviertel und Wasserläufe', 'note' => [
                'de' => 'Schmale Kanäle und alte Handwerkerhäuser, ruhiger als das Zentrum. Vormittags am leersten.',
                'tr' => 'Dar kanallar ve eski zanaatkâr evleri, merkezden sakin. En boş hâli sabah.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Kann man in der Fuggerei heiraten oder fotografieren?', 'tr' => 'Fuggerei’de evlenilir ya da fotoğraf çekilir mi?'],
             'a' => ['de' => 'Fotografieren ist nach Anmeldung möglich, es ist aber ein bewohntes Quartier und kein Fotostudio – wir arbeiten dort leise und ohne Aufbauten. Die Anmeldung übernehmen wir rechtzeitig vor dem Termin.',
                     'tr' => 'Çekim randevuyla mümkün ama orası bir stüdyo değil, insanların yaşadığı bir mahalle – sessiz ve kurulum yapmadan çalışıyoruz. Randevuyu tarihten önce biz alıyoruz.']],
            ['q' => ['de' => 'Berechnet ihr für Augsburg Anfahrt?', 'tr' => 'Augsburg için yol ücreti alıyor musunuz?'],
             'a' => ['de' => 'Nein. Augsburg liegt in dem Umkreis, den wir ohne Anfahrtskosten abdecken. Erst ab etwa 80 Kilometern kommt eine Pauschale dazu, und die steht dann im Angebot, bevor irgendetwas unterschrieben wird.',
                     'tr' => 'Hayır. Augsburg yol ücreti almadığımız çevrede kalıyor. Yaklaşık 80 kilometreden sonra sabit bir tutar ekleniyor ve o da hiçbir şey imzalanmadan önce teklifte yazıyor.']],
        ],
        'venues'     => [],
        'neighbours' => ['guenzburg', 'ulm', 'muenchen', 'neu-ulm'],
    ],
];
