<?php
/**
 * Der weitere Ring: München, Stuttgart, Friedrichshafen, Bregenz, St. Gallen.
 *
 * Hier ist die Entfernung kein Nebensatz mehr, sondern der Inhalt. Wer zwei
 * Stunden fährt, will vorher wissen, was das kostet und was es bringt – und
 * bei Bregenz und St. Gallen steht auch noch eine Grenze dazwischen.
 *
 * Eingespielt mit: php bin/cities.php
 */

return [

    /* -------------------------------- München ------------------------------- */
    [
        'slug'  => 'muenchen',
        'name'  => 'München',
        'kreis' => ['de' => 'Landeshauptstadt Bayern', 'tr' => 'Bavyera eyalet başkenti'],
        'drive' => ['de' => 'rund 1 Stunde 15', 'tr' => 'yaklaşık 1 saat 15'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in München – für Paare, die uns aus Schwaben mitnehmen, und für Feiern, die den Aufwand wert sind.',
            'tr' => 'Münih’te düğün fotoğrafçısı ve videografı – bizi Suabya’dan yanında götüren çiftler ve bu emeğe değen kutlamalar için.',
        ],
        'body' => [
            'de' => [
                'München ist nicht unsere Nachbarschaft, und wir tun nicht so. Wir kommen hierher, weil Paare uns mitnehmen, die wir aus der Region kennen, oder weil eine Familie zwischen Schwaben und der Landeshauptstadt verteilt ist. Das ist ein anderer Auftrag als eine Hochzeit zwanzig Minuten von zu Hause: Wir reisen am Vorabend an, wenn der Tag früh beginnt, und wir planen mit mehr Puffer, weil in München jede Fahrt zwischen zwei Orten länger dauert, als der Kartendienst sagt.',
                'Fotografisch ist die Stadt reich und streng zugleich. Der Schlosspark Nymphenburg und der Englische Garten sind großartig und öffentlich – mit allem, was dazugehört: Läufer im Hintergrund, andere Hochzeitsgesellschaften, und für gewerbliche Aufnahmen teils eigene Regeln. Die Arkaden am Odeonsplatz und die Isarauen sind unsere ruhigeren Gegenvorschläge. Was in München immer funktioniert, ist der frühe Morgen: um sieben gehört die Stadt für eine Stunde niemandem.',
            ],
            'tr' => [
                'Münih bizim mahallemiz değil ve öyleymiş gibi yapmıyoruz. Buraya, bölgeden tanıdığımız çiftler bizi yanında götürdüğü ya da bir aile Suabya ile başkent arasında dağıldığı için geliyoruz. Bu, evden yirmi dakikadaki bir düğünden başka bir iş: gün erken başlıyorsa bir akşam önceden geliyoruz ve daha geniş pay bırakıyoruz, çünkü Münih’te iki yer arası her yolculuk harita uygulamasının dediğinden uzun sürüyor.',
                'Fotoğraf açısından şehir hem zengin hem katı. Nymphenburg Sarayı parkı ve İngiliz Bahçesi muhteşem ve halka açık – beraberinde geleniyle: arka planda koşucular, başka düğün toplulukları ve ticari çekim için yer yer ayrı kurallar. Odeonsplatz revakları ve Isar kıyıları bizim daha sakin karşı önerilerimiz. Münih’te her zaman işleyen şey sabahın erken saati: yedide şehir bir saatliğine kimsenin değil.',
            ],
        ],
        'spots' => [
            ['name' => 'Schlosspark Nymphenburg', 'note' => [
                'de' => 'Weite Achsen und Wasser. Für gewerbliche Aufnahmen gelten eigene Regeln – vorher klären, nicht vor Ort diskutieren.',
                'tr' => 'Geniş akslar ve su. Ticari çekimin ayrı kuralları var – yerinde tartışmak yerine önceden halledin.',
            ]],
            ['name' => 'Odeonsplatz und Hofgarten', 'note' => [
                'de' => 'Arkaden als Regen- und Mittagsplan, Symmetrie für ruhige Bilder. Früh am Morgen fast leer.',
                'tr' => 'Yağmur ve öğlen planı olarak revaklar, sakin kareler için simetri. Sabah erken neredeyse boş.',
            ]],
            ['name' => 'Isarauen', 'note' => [
                'de' => 'Kies, Wasser und Bäume statt Architektur – der Gegenpol, wenn die Stadt zu viel wird.',
                'tr' => 'Mimari yerine çakıl, su ve ağaç – şehir fazla geldiğinde karşı kutup.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Was kostet die Anfahrt nach München?', 'tr' => 'Münih’e yol ücreti ne kadar?'],
             'a' => ['de' => 'Ab etwa 80 Kilometern kommt eine Anfahrtspauschale dazu; für München nennen wir sie im Angebot konkret, zusammen mit einer Übernachtung, falls der Tag vor acht Uhr beginnt oder nach Mitternacht endet. Nichts davon taucht später als Überraschung auf der Rechnung auf.',
                     'tr' => 'Yaklaşık 80 kilometreden sonra sabit bir yol ücreti ekleniyor; Münih için bunu teklifte açıkça yazıyoruz, gün sekizden önce başlıyor ya da gece yarısından sonra bitiyorsa konaklamayla birlikte. Hiçbiri sonradan faturada sürpriz olarak çıkmıyor.']],
            ['q' => ['de' => 'Lohnt es sich, uns von so weit zu buchen?', 'tr' => 'Bu kadar uzaktan bizi tutmak mantıklı mı?'],
             'a' => ['de' => 'Ehrlich: nur, wenn die Bilder zu euch passen und nicht, weil wir günstiger wären. München hat gute Fotografen. Paare buchen uns von hier aus meist, weil sie uns aus der Familie oder von einer anderen Hochzeit kennen – und das ist ein guter Grund.',
                     'tr' => 'Dürüst olalım: yalnızca fotoğraflar size uyuyorsa; daha ucuz olduğumuz için değil. Münih’in iyi fotoğrafçıları var. Buradan bizi tutan çiftler çoğunlukla aileden ya da başka bir düğünden tanıyor – ve bu iyi bir sebep.']],
        ],
        'venues'     => [],
        'neighbours' => ['augsburg', 'guenzburg', 'memmingen'],
    ],

    /* ------------------------------- Stuttgart ------------------------------ */
    [
        'slug'  => 'stuttgart',
        'name'  => 'Stuttgart',
        'kreis' => ['de' => 'Landeshauptstadt Baden-Württemberg', 'tr' => 'Baden-Württemberg eyalet başkenti'],
        'drive' => ['de' => 'rund 1 Stunde 30', 'tr' => 'yaklaşık 1 saat 30'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Stuttgart – die Richtung nach Westen, mit Weinbergen direkt über der Stadt.',
            'tr' => 'Stuttgart’ta düğün fotoğrafçısı ve videografı – batı yönü; şehrin hemen üstünde bağlar.',
        ],
        'body' => [
            'de' => [
                'Stuttgart ist von Krumbach aus die längste Fahrt nach Westen, und für viele deutsch-türkische Familien in unserem Umkreis die Stadt, in der die Verwandtschaft wohnt. Genau deshalb fahren wir sie: Wenn eine Hälfte der Familie aus Schwaben kommt und die andere aus dem Stuttgarter Raum, ist die Feier oft dort, und der Fotograf kommt mit.',
                'Die Stadt liegt in einem Kessel, und das ist fotografisch ihr größter Vorteil. Innerhalb von zehn Minuten ist man aus dem Zentrum in den Weinbergen, mit Blick über die Dächer – eine Kombination, die keine andere Stadt in unserem Gebiet so anbietet. Unten am Schlossplatz ist es großstädtisch und um die Mittagszeit hart im Licht; oben ist es still, und die Sonne geht über den Reben unter. Wer beides will, sollte zwei Stunden dafür einplanen und nicht vierzig Minuten.',
            ],
            'tr' => [
                'Stuttgart, Krumbach’tan batıya en uzun yolculuk ve çevremizdeki birçok Türk-Alman ailesi için akrabaların yaşadığı şehir. Tam bu yüzden gidiyoruz: ailenin bir yarısı Suabya’dan, diğeri Stuttgart bölgesinden geliyorsa kutlama çoğu zaman orada oluyor, fotoğrafçı da geliyor.',
                'Şehir bir çanağın içinde ve fotoğraf açısından en büyük avantajı bu. Merkezden on dakikada bağların içindesiniz, çatıların üzerine bakan bir manzarayla – bölgemizdeki başka hiçbir şehrin böyle sunmadığı bir birleşim. Aşağıda saray meydanı büyük şehir gibi ve öğlen ışığı sert; yukarısı sessiz ve güneş asmaların ardında batıyor. İkisini birden isteyen kırk dakika değil iki saat ayırmalı.',
            ],
        ],
        'spots' => [
            ['name' => 'Weinberge über der Stadt', 'note' => [
                'de' => 'Blick über die Dächer, goldene Stunde ohne Publikum. Steile Wege – Schuhe zum Wechseln mitnehmen.',
                'tr' => 'Çatıların üzerine manzara, seyircisiz altın saat. Dik yollar – değiştirmek için ayakkabı alın.',
            ]],
            ['name' => 'Schlossplatz und Altes Schloss', 'note' => [
                'de' => 'Großstädtisch und repräsentativ, mittags hart. Bei Regen tragen die Arkaden.',
                'tr' => 'Büyük şehir havası ve gösterişli, öğlen sert. Yağmurda revaklar iş görüyor.',
            ]],
            ['name' => 'Höhenpark Killesberg', 'note' => [
                'de' => 'Weiches Abendlicht und Weitblick, ruhiger als das Zentrum.',
                'tr' => 'Yumuşak akşam ışığı ve geniş manzara, merkezden sakin.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Ihr sitzt in Schwaben – warum Stuttgart?', 'tr' => 'Siz Suabya’dasınız – neden Stuttgart?'],
             'a' => ['de' => 'Weil Familien selten an einem Ort wohnen. Wir fahren die Strecke regelmäßig für Paare, deren Verwandtschaft im Stuttgarter Raum lebt, und kennen die Stadt gut genug, um nicht am Hochzeitstag nach einem Platz zu suchen.',
                     'tr' => 'Çünkü aileler nadiren tek bir yerde oturuyor. Bu yolu, akrabaları Stuttgart bölgesinde yaşayan çiftler için düzenli olarak yapıyoruz ve şehri düğün günü yer aramayacak kadar tanıyoruz.']],
            ['q' => ['de' => 'Kommt eine Übernachtung dazu?', 'tr' => 'Konaklama ekleniyor mu?'],
             'a' => ['de' => 'Wenn die Feier bis in die Nacht geht, ja – wir fahren nach zwölf Stunden Arbeit keine anderthalb Stunden mehr im Dunkeln zurück. Das steht vorher im Angebot, nicht hinterher auf der Rechnung.',
                     'tr' => 'Kutlama gece geç saate kadar sürüyorsa evet – on iki saatlik işten sonra karanlıkta bir buçuk saat yol yapmıyoruz. Bu sonradan faturada değil, önceden teklifte yazıyor.']],
        ],
        'venues'     => [],
        'neighbours' => ['ulm', 'muenchen', 'augsburg'],
    ],

    /* ---------------------------- Friedrichshafen --------------------------- */
    [
        'slug'  => 'friedrichshafen',
        'name'  => 'Friedrichshafen',
        'kreis' => ['de' => 'Bodenseekreis', 'tr' => 'Bodensee bölgesi'],
        'drive' => ['de' => 'rund 1 Stunde 40', 'tr' => 'yaklaşık 1 saat 40'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Friedrichshafen – Bodensee, offener Horizont und ein Licht, das vom Wasser zurückkommt.',
            'tr' => 'Friedrichshafen’de düğün fotoğrafçısı ve videografı – Bodensee, açık ufuk ve sudan geri gelen bir ışık.',
        ],
        'body' => [
            'de' => [
                'Am See ist das Licht anders, und das ist keine Floskel. Wasser wirft zurück, was von oben kommt, und füllt Schatten unter Augen und Kinn – das macht Portraits am Ufer weicher als in der Stadt. Dafür gibt es keinen Schutz: An einem klaren Julimittag ist die Promenade gnadenlos, und bei Wind kämpft jede Frisur. Wir planen Bodensee-Termine deshalb um die Tageszeit herum, nicht um den Ort.',
                'Friedrichshafen selbst ist nüchterner als die Postkartenorte am See, und wir mögen das. Die Uferpromenade ist lang und offen, der Blick geht bei gutem Wetter bis an die Schweizer Berge, und der Zeppelin über dem Wasser ist ein Motiv, das es sonst nirgends gibt – wenn er fliegt, warten wir kurz. Für Paare, die Weite wollen statt Kulisse, ist das der richtige Ort in dieser Richtung.',
            ],
            'tr' => [
                'Gölde ışık başka ve bu bir laf kalabalığı değil. Su, yukarıdan geleni geri yansıtıyor ve gözlerin, çenenin altındaki gölgeyi dolduruyor – bu, kıyıdaki portreleri şehirdekinden yumuşak yapıyor. Buna karşılık korunak yok: açık bir temmuz öğlesinde sahil acımasız, rüzgârda her saç mücadele veriyor. Bu yüzden Bodensee tarihlerini yere göre değil, günün saatine göre planlıyoruz.',
                'Friedrichshafen’in kendisi göldeki kartpostal kasabalarından daha sade ve bunu seviyoruz. Sahil uzun ve açık, hava iyiyse manzara İsviçre dağlarına kadar gidiyor, suyun üstündeki zeplin ise başka hiçbir yerde olmayan bir kare – uçuyorsa biraz bekliyoruz. Fon değil genişlik isteyen çiftler için bu yöndeki doğru yer burası.',
            ],
        ],
        'spots' => [
            ['name' => 'Uferpromenade und Hafen', 'note' => [
                'de' => 'Offener Horizont, weiches Licht vom Wasser. Mittags ungeschützt – lieber die letzten zwei Stunden vor Sonnenuntergang.',
                'tr' => 'Açık ufuk, sudan gelen yumuşak ışık. Öğlen korunaksız – gün batımından önceki son iki saat daha iyi.',
            ]],
            ['name' => 'Schlosskirche am See', 'note' => [
                'de' => 'Zwei Türme direkt am Wasser, gut aus der Ferne. Innen nur nach Absprache mit der Gemeinde.',
                'tr' => 'Su kenarında iki kule, uzaktan iyi duruyor. İçerisi yalnız cemaatle konuşulduktan sonra.',
            ]],
            ['name' => 'Uferpark Richtung Manzell', 'note' => [
                'de' => 'Bäume und Wiese als Windschutz, wenn es auf der Promenade zu böig wird.',
                'tr' => 'Sahil fazla rüzgârlıysa siper olarak ağaç ve çayır.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Was, wenn es am See stürmt?', 'tr' => 'Gölde fırtına çıkarsa ne olur?'],
             'a' => ['de' => 'Dann gehen wir vom offenen Ufer weg in den Uferpark oder in geschützte Innenhöfe. Wind ist am Bodensee kein Ausnahmefall, sondern eine Planungsgröße – wir schauen am Morgen auf die Vorhersage und verschieben eher die Uhrzeit als den Ort.',
                     'tr' => 'O zaman açık kıyıdan sahil parkına ya da korunaklı avlulara geçiyoruz. Bodensee’de rüzgâr istisna değil, planlama verisi – sabah tahmine bakıyor ve yeri değil saati kaydırıyoruz.']],
            ['q' => ['de' => 'Bleibt ihr über Nacht?', 'tr' => 'Geceyi orada geçiriyor musunuz?'],
             'a' => ['de' => 'Bei Terminen am Bodensee in der Regel ja. Die Rückfahrt dauert fast zwei Stunden, und die letzten Bilder des Abends sind oft die besten – die wollen wir nicht wegen der Uhrzeit auslassen.',
                     'tr' => 'Bodensee tarihlerinde genelde evet. Dönüş neredeyse iki saat ve akşamın son kareleri çoğu zaman en iyileri – onları saat yüzünden atlamak istemiyoruz.']],
        ],
        'venues'     => [],
        'neighbours' => ['memmingen', 'bregenz', 'st-gallen'],
    ],

    /* -------------------------------- Bregenz ------------------------------- */
    [
        'slug'  => 'bregenz',
        'name'  => 'Bregenz',
        'kreis' => ['de' => 'Vorarlberg, Österreich', 'tr' => 'Vorarlberg, Avusturya'],
        'drive' => ['de' => 'rund 2 Stunden', 'tr' => 'yaklaşık 2 saat'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in Bregenz – wo der Bodensee an den Berg stößt, mit Aussicht vom Pfänder über drei Länder.',
            'tr' => 'Bregenz’de düğün fotoğrafçısı ve videografı – Bodensee’nin dağa dayandığı yer; Pfänder’den üç ülkeye bakan manzara.',
        ],
        'body' => [
            'de' => [
                'Bregenz ist der einzige Ort in unserem Gebiet, an dem See und Berg direkt aneinanderstoßen. Vom Pfänder aus sieht man bei klarer Sicht über den Bodensee bis nach Deutschland und in die Schweiz – ein Bild, das kein anderer Ort auf dieser Liste hergibt. Der Weg nach oben geht mit der Seilbahn, und das ist Teil der Planung: Fahrzeiten, Betriebszeiten und eine Warteschlange an einem schönen Sonntag.',
                'Unten ist Bregenz zweigeteilt. Die Seepromenade mit der Seebühne ist offen und modern, die Oberstadt mit dem Martinsturm ist eng, alt und deutlich ruhiger. Wir kombinieren das gern: eine Stunde oben in den Gassen, eine am Wasser. Was Paare aus Deutschland bedenken sollten: Bregenz liegt in Österreich. Für uns heißt das eine Auslandsanfahrt und eine andere Rechnungsstellung – beides klären wir vorher schriftlich, damit am Ende keine Frage offen ist.',
            ],
            'tr' => [
                'Bregenz, bölgemizde göl ile dağın doğrudan buluştuğu tek yer. Pfänder’den hava açıkken Bodensee’nin ötesinde Almanya ve İsviçre görünüyor – bu listedeki başka hiçbir yerin vermediği bir kare. Yukarı çıkış teleferikle ve bu planın parçası: sefer saatleri, çalışma saatleri ve güzel bir pazar günü kuyruk.',
                'Aşağıda Bregenz ikiye ayrılıyor. Sahne’li göl sahili açık ve modern; Martinsturm’lu yukarı şehir dar, eski ve belirgin biçimde sakin. İkisini birleştirmeyi seviyoruz: bir saat yukarıda sokaklarda, bir saat su kenarında. Almanya’dan gelen çiftlerin bilmesi gereken: Bregenz Avusturya’da. Bizim için bu, yurt dışı yolculuğu ve farklı bir faturalandırma demek – ikisini de sonunda soru kalmasın diye önceden yazılı olarak netleştiriyoruz.',
            ],
        ],
        'spots' => [
            ['name' => 'Pfänder', 'note' => [
                'de' => 'Blick über den ganzen See. Seilbahnzeiten prüfen – bei Nebel im Tal ist oben oft Sonne, umgekehrt aber auch.',
                'tr' => 'Bütün göle bakan manzara. Teleferik saatlerine bakın – vadide sis varken yukarısı çoğu zaman güneşli, tersi de olabiliyor.',
            ]],
            ['name' => 'Seepromenade und Seebühne', 'note' => [
                'de' => 'Weite und Wasser, modern. Während der Festspiele im Sommer voll und teils gesperrt.',
                'tr' => 'Genişlik ve su, modern. Yaz festivali sırasında kalabalık ve yer yer kapalı.',
            ]],
            ['name' => 'Oberstadt mit Martinsturm', 'note' => [
                'de' => 'Enge Gassen, alter Putz, kaum Verkehr – der ruhige Gegenpol zum Ufer.',
                'tr' => 'Dar sokaklar, eski sıva, neredeyse trafik yok – kıyının sakin karşıtı.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Fahrt ihr für eine Hochzeit nach Österreich?', 'tr' => 'Bir düğün için Avusturya’ya gelir misiniz?'],
             'a' => ['de' => 'Ja. Anfahrt, Übernachtung und die steuerlichen Punkte einer Auslandsleistung stehen dafür im Angebot, bevor etwas unterschrieben wird. Wir machen das nicht oft, aber wir machen es sauber.',
                     'tr' => 'Evet. Yol, konaklama ve yurt dışı hizmetin vergisel tarafı, hiçbir şey imzalanmadan önce teklifte yazıyor. Bunu sık yapmıyoruz ama düzgün yapıyoruz.']],
            ['q' => ['de' => 'Lohnt sich der Pfänder am Hochzeitstag?', 'tr' => 'Düğün günü Pfänder’e çıkmak değer mi?'],
             'a' => ['de' => 'Nur mit Zeitpuffer und guter Sicht. Wir rechnen mit rund anderthalb Stunden für Auffahrt, Bilder und Rückweg. Bei Hochnebel entscheiden wir am Morgen dagegen – ein Berg ohne Aussicht ist nur ein kalter Umweg.',
                     'tr' => 'Yalnızca zaman payı ve açık hava varsa. Çıkış, çekim ve dönüş için yaklaşık bir buçuk saat hesaplıyoruz. Alçak sis varsa sabah vazgeçiyoruz – manzarasız bir dağ yalnızca soğuk bir sapma olur.']],
        ],
        'venues'     => [],
        'neighbours' => ['friedrichshafen', 'st-gallen', 'memmingen'],
    ],

    /* ------------------------------- St. Gallen ----------------------------- */
    [
        'slug'  => 'st-gallen',
        'name'  => 'St. Gallen',
        'kreis' => ['de' => 'Kanton St. Gallen, Schweiz', 'tr' => 'St. Gallen kantonu, İsviçre'],
        'drive' => ['de' => 'rund 2 Stunden 15', 'tr' => 'yaklaşık 2 saat 15'],
        'lead'  => [
            'de' => 'Hochzeitsfotograf und Videograf in St. Gallen – Stiftsbezirk, Erker und die weiteste Fahrt, die wir regelmäßig machen.',
            'tr' => 'St. Gallen’de düğün fotoğrafçısı ve videografı – manastır bölgesi, cumbalar ve düzenli yaptığımız en uzun yol.',
        ],
        'body' => [
            'de' => [
                'St. Gallen ist der äußerste Punkt unseres Gebiets, und wir nennen ihn trotzdem, weil die Fahrt sich für den Stiftsbezirk lohnt. Kathedrale und Stiftsbibliothek stehen auf der Welterbeliste, und der Platz davor hat eine Ruhe, die man in einer Stadt dieser Größe nicht erwartet. Fotografiert wird dort außen – die Bibliothek ist ein Buchmagazin, kein Ort für ein Brautkleid, und das respektieren wir.',
                'Die Altstadt lebt von den Erkern: über hundert bemalte und geschnitzte Vorbauten an den Häusern, die schmalen Gassen Tiefe geben. Bei bedecktem Himmel ist das ideal, in praller Sonne wird es fleckig – hier ist ein grauer Tag ausnahmsweise die bessere Nachricht. Für Paare aus Deutschland gilt dasselbe wie für Bregenz: andere Währung, Zollformalitäten für Ausrüstung, eigene Rechnungsstellung. Wir haben das geregelt, aber es gehört vorher besprochen und nicht am Hochzeitsmorgen.',
            ],
            'tr' => [
                'St. Gallen bölgemizin en uç noktası ve yine de anıyoruz, çünkü yolculuk manastır bölgesi için değiyor. Katedral ve manastır kütüphanesi dünya mirası listesinde; önündeki meydanda bu büyüklükte bir şehirde beklenmeyecek bir sessizlik var. Çekim orada dışarıda oluyor – kütüphane bir kitap deposu, gelinlik için bir mekân değil ve buna saygı duyuyoruz.',
                'Eski kent cumbalarla yaşıyor: evlerin üzerinde yüzden fazla boyalı ve oymalı çıkma, dar sokaklara derinlik veriyor. Kapalı havada bu ideal, tepedeki güneşte lekeli oluyor – burada gri bir gün istisnai olarak iyi haber. Almanya’dan gelen çiftler için Bregenz’le aynısı geçerli: başka para birimi, ekipman için gümrük işlemleri, ayrı faturalandırma. Bunu çözdük ama düğün sabahı değil, önceden konuşulması gerekiyor.',
            ],
        ],
        'spots' => [
            ['name' => 'Stiftsbezirk', 'note' => [
                'de' => 'Kathedrale und Klosterhof, Welterbe. Außenaufnahmen; innen gelten strenge Regeln.',
                'tr' => 'Katedral ve manastır avlusu, dünya mirası. Dış çekim; içeride katı kurallar var.',
            ]],
            ['name' => 'Altstadt mit den Erkern', 'note' => [
                'de' => 'Über hundert bemalte Vorbauten. Bei bedecktem Himmel am besten, harte Sonne macht Flecken.',
                'tr' => 'Yüzden fazla boyalı çıkma. En iyisi kapalı havada; sert güneş leke yapıyor.',
            ]],
            ['name' => 'Drei Weieren über der Stadt', 'note' => [
                'de' => 'Weiher und Blick über St. Gallen, zwanzig Minuten vom Zentrum. Am Wochenende belebt.',
                'tr' => 'Göletler ve şehre bakan manzara, merkezden yirmi dakika. Hafta sonu hareketli.',
            ]],
        ],
        'faq' => [
            ['q' => ['de' => 'Kann man in der Stiftsbibliothek fotografieren?', 'tr' => 'Manastır kütüphanesinde fotoğraf çekilir mi?'],
             'a' => ['de' => 'Nein, und das sollte man auch nicht wollen. Es ist ein Bibliotheksraum mit jahrhundertealten Beständen und entsprechendem Klima. Wir fotografieren im Klosterhof und am Platz davor – das gibt die Architektur genauso wieder.',
                     'tr' => 'Hayır ve istenmemeli de. Orası yüzyıllık koleksiyonları ve ona göre iklimi olan bir kütüphane salonu. Biz manastır avlusunda ve önündeki meydanda çekiyoruz – mimariyi o da aynı şekilde veriyor.']],
            ['q' => ['de' => 'Rechnet ihr in Euro oder Franken ab?', 'tr' => 'Euro mu Frank mı üzerinden faturalıyorsunuz?'],
             'a' => ['de' => 'In Euro, mit einem festen Betrag im Angebot – kein Kurs, der sich bis zur Hochzeit bewegt. Anfahrt und Übernachtung stehen getrennt daneben, damit ihr seht, wofür ihr zahlt.',
                     'tr' => 'Euro üzerinden ve teklifte sabit tutarla – düğüne kadar oynayan bir kur yok. Yol ve konaklama yanında ayrı yazıyor, neye ödediğinizi görün diye.']],
        ],
        'venues'     => [],
        'neighbours' => ['bregenz', 'friedrichshafen', 'memmingen'],
    ],
];
