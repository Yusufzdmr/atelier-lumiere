import type { L } from "./i18n";

/* ------------------------------------------------------------------ */
/*  Ratgeber – Standardbeiträge                                        */
/*                                                                     */
/*  Der Ratgeber ist der Teil des Local SEO, der laufend weiterwächst. */
/*  Jeder Beitrag verlinkt auf eine Stadt- und/oder Locationseite –    */
/*  daher stammt der eigentliche Nutzen, nicht aus dem Text allein.    */
/*  Im Admin unter "Ratgeber" vollständig bearbeitbar.                 */
/* ------------------------------------------------------------------ */

export type PostFaq = { q: L; a: L };

export type Post = {
  slug: string;
  title: L;
  /** Anrisstext für Übersicht, Meta-Description und Teaser */
  excerpt: L;
  /** Absätze; eine Zeile, die mit "## " beginnt, wird zur Zwischenüberschrift */
  body: L<string[]>;
  /** ISO-Datum, steuert die Sortierung */
  date: string;
  /** Bildkennung aus lib/photos.json */
  seed: string;
  /** Im Admin hochgeladene Bilder (ersetzen das Platzhalterbild) */
  uploads?: string[];
  /** Interne Verlinkung – der SEO-Hebel */
  citySlug?: string;
  venueSlug?: string;
  /** Erscheint als FAQ-Schema unter dem Beitrag */
  faq?: PostFaq[];
};

export const posts: Post[] = [
  {
    slug: "hochzeitsfotograf-finden-sieben-fragen",
    date: "2026-05-18",
    seed: "lum-blog-fragen",
    citySlug: "stuttgart",
    title: {
      de: "Hochzeitsfotograf finden: 7 Fragen, die ihr vor der Buchung stellen solltet",
      tr: "Düğün fotoğrafçısı seçerken sormanız gereken 7 soru",
    },
    excerpt: {
      de: "Der Preis allein sagt wenig. Diese sieben Fragen zeigen euch in zehn Minuten, ob jemand zu eurem Tag passt – und decken die Punkte auf, über die später am häufigsten gestritten wird.",
      tr: "Fiyat tek başına bir şey söylemez. Bu yedi soru, on dakikada size uygun olup olmadığını gösterir – ve sonradan en çok tartışma çıkan noktaları önceden ortaya koyar.",
    },
    body: {
      de: [
        "Die meisten Paare buchen ihren Hochzeitsfotografen nach zwei Kriterien: Bildsprache und Preis. Beides ist wichtig, aber beides sagt wenig darüber aus, wie der Tag tatsächlich ablaufen wird. Die folgenden Fragen kosten euch ein Gespräch und ersparen euch im Zweifel eine Enttäuschung.",
        "## 1. Wer fotografiert tatsächlich bei uns?",
        "Bei größeren Studios ist die Person im Erstgespräch nicht immer die Person am Hochzeitstag. Lasst euch den Namen geben und fragt nach einer vollständigen Hochzeit von genau dieser Person – nicht nach einer Best-of-Auswahl aus mehreren Aufträgen.",
        "## 2. Dürfen wir eine komplette Hochzeit sehen?",
        "Eine Galerie mit 30 Höhepunkten kann jeder zusammenstellen. Interessant ist, wie die zweihundert Bilder dazwischen aussehen: die Rede, die niemand geplant hat, der dunkle Saal um 22 Uhr, die Familienaufstellung mit 40 Personen.",
        "## 3. Was passiert bei Krankheit oder Ausfall?",
        "Ein seriöser Vertrag regelt das schriftlich: Ersatz aus einem Netzwerk innerhalb einer festen Frist oder vollständige Rückerstattung. Eine mündliche Zusage ist an dieser Stelle zu wenig.",
        "## 4. Wie lange dauert die Lieferung – und was steht im Vertrag?",
        "„Ein paar Wochen“ ist keine Frist. Fragt nach konkreten Zahlen für Vorschau, vollständige Galerie und Film, und danach, ob diese Fristen vertraglich zugesichert sind.",
        "## 5. Wie viele Bilder bekommen wir, und in welcher Form?",
        "Wichtig ist weniger die Anzahl als das Format: Bekommt ihr die Bilder in voller Auflösung zum Herunterladen? Gibt es eine Online-Galerie? Dürft ihr die Bilder privat frei nutzen?",
        "## 6. Wart ihr schon einmal in unserer Location?",
        "Wer die Location kennt, weiß, wo um 18 Uhr noch Licht steht und welche Wege wie lange dauern. Wer sie nicht kennt, sollte vorher hinfahren – fragt, ob das eingeplant ist.",
        "## 7. Was passiert mit unseren Bildern?",
        "Veröffentlichung auf Website und Social Media darf nur mit gesonderter, freiwilliger Einwilligung erfolgen. Diese Einwilligung muss unabhängig vom Fotoauftrag sein und jederzeit widerrufbar bleiben.",
        "Wenn eine Fotografin oder ein Fotograf auf alle sieben Fragen ruhig und konkret antwortet, habt ihr die wichtigste Information schon: Da arbeitet jemand, der seinen Ablauf kennt.",
      ],
      tr: [
        "Çiftlerin çoğu düğün fotoğrafçısını iki ölçüte göre seçiyor: görsel tarz ve fiyat. İkisi de önemli, ama ikisi de günün gerçekte nasıl geçeceği hakkında pek bir şey söylemiyor. Aşağıdaki sorular size bir görüşmeye mal olur, karşılığında olası bir hayal kırıklığını önler.",
        "## 1. Bizim düğünümüzde gerçekte kim çekecek?",
        "Büyük stüdyolarda ilk görüşmedeki kişi, düğün günü gelen kişi olmayabiliyor. İsmi net alın ve tam olarak o kişinin çektiği eksiksiz bir düğün isteyin – farklı işlerden derlenmiş bir seçki değil.",
        "## 2. Eksiksiz bir düğün görebilir miyiz?",
        "Otuz karelik bir seçkiyi herkes hazırlayabilir. Asıl mesele aradaki iki yüz karenin nasıl olduğu: kimsenin planlamadığı konuşma, gece 22'de karanlık salon, kırk kişilik aile fotoğrafı.",
        "## 3. Hastalık ya da aksilik durumunda ne oluyor?",
        "Ciddi bir sözleşme bunu yazılı düzenler: belirli bir süre içinde ağdan eşdeğer ekip ya da tam iade. Bu konuda sözlü güvence yeterli değil.",
        "## 4. Teslim ne kadar sürüyor – ve sözleşmede ne yazıyor?",
        "\"Birkaç hafta\" bir süre değildir. Ön izleme, tam galeri ve film için somut sayılar isteyin; bu sürelerin sözleşmeyle güvence altına alınıp alınmadığını sorun.",
        "## 5. Kaç kare alıyoruz ve hangi biçimde?",
        "Sayıdan çok biçim önemli: Kareleri tam çözünürlükte indirebiliyor musunuz? Online galeri var mı? Kareleri özel kullanımda serbestçe kullanabiliyor musunuz?",
        "## 6. Bizim mekânımızda daha önce çekim yaptınız mı?",
        "Mekânı bilen kişi, saat 18'de ışığın nerede durduğunu ve hangi geçişin ne kadar sürdüğünü bilir. Bilmeyen önceden gidip görmeli – bunun planlanıp planlanmadığını sorun.",
        "## 7. Karelerimize ne olacak?",
        "Web sitesinde ve sosyal medyada yayın, yalnızca ayrı ve gönüllü bir izinle yapılabilir. Bu izin fotoğraf işinden bağımsız olmalı ve her zaman geri alınabilmeli.",
        "Bir fotoğrafçı bu yedi sorunun hepsine sakin ve somut cevap veriyorsa, en önemli bilgiyi zaten almışsınız demektir: karşınızda akışını bilen biri var.",
      ],
    },
    faq: [
      {
        q: {
          de: "Wie früh sollten wir den Hochzeitsfotografen buchen?",
          tr: "Düğün fotoğrafçısını ne kadar önceden ayırtmalıyız?",
        },
        a: {
          de: "Für Samstage zwischen Mai und September sind zwölf bis achtzehn Monate üblich. Unter der Woche und in der Nebensaison reichen oft sechs Monate.",
          tr: "Mayıs–Eylül arası cumartesiler için on iki ila on sekiz ay normaldir. Hafta içi ve sezon dışında çoğu zaman altı ay yeterli oluyor.",
        },
      },
      {
        q: {
          de: "Ist ein zweiter Fotograf notwendig?",
          tr: "İkinci bir fotoğrafçı gerekli mi?",
        },
        a: {
          de: "Ab etwa 120 Gästen oder wenn sich Braut und Bräutigam getrennt vorbereiten, lohnt sich die zweite Kamera fast immer. Bei kleinen Feiern ist sie meist verzichtbar.",
          tr: "Yaklaşık 120 kişiden sonra ya da gelin ve damat ayrı hazırlanıyorsa ikinci kamera neredeyse her zaman işe yarıyor. Küçük kutlamalarda genelde gerekmiyor.",
        },
      },
    ],
  },

  {
    slug: "hochzeit-zeitplan-licht",
    date: "2026-06-24",
    seed: "lum-blog-zeitplan",
    citySlug: "ludwigsburg",
    title: {
      de: "Der Zeitplan, der wirklich funktioniert – und warum das Licht ihn bestimmt",
      tr: "Gerçekten işe yarayan düğün akışı – ve neden ışık belirler",
    },
    excerpt: {
      de: "Die schönsten Paarbilder entstehen in einem Zeitfenster von 40 Minuten. Wer den Tag darum herum plant, braucht keine Bilder nachzustellen. So sieht ein realistischer Ablauf aus.",
      tr: "En güzel çift kareleri 40 dakikalık bir aralıkta çıkıyor. Günü onun etrafına kuran çift, hiçbir kareyi yeniden kurgulamak zorunda kalmıyor. Gerçekçi bir akış şöyle görünür.",
    },
    body: {
      de: [
        "Fast jede Hochzeit, bei der am Ende Zeit fehlt, hat dasselbe Problem: Der Ablauf wurde nach den Wünschen der Location geplant, nicht nach dem Licht. Dabei ist die goldene Stunde der einzige Punkt im Tagesablauf, den niemand verschieben kann.",
        "## Wann ist die goldene Stunde?",
        "Sie beginnt etwa eine Stunde vor Sonnenuntergang und dauert je nach Jahreszeit 30 bis 50 Minuten. Im Juni in Baden-Württemberg heißt das ungefähr 20:15 Uhr, Ende September bereits 18:30 Uhr. Ein Unterschied von fast zwei Stunden – geplant wird der Tag aber oft mit demselben Schema.",
        "## Ein Ablauf, der aufgeht",
        "Getting Ready ab drei Stunden vor der Trauung, mit Puffer für die Anfahrt. Trauung. Danach Sektempfang und Gruppenbilder, solange alle Gäste noch beisammen sind – das dauert mit 40 Personen etwa 25 Minuten, nicht zehn.",
        "Der entscheidende Block: 30 bis 45 Minuten Paarshooting, gelegt auf die goldene Stunde. Das bedeutet, dass das Abendessen entweder davor endet oder danach beginnt. Beides funktioniert – nur mitten hinein sollte es nicht fallen.",
        "## Die drei häufigsten Fehler",
        "Erstens: Gruppenbilder erst nach dem Essen. Dann sind einige Gäste schon draußen, andere nicht mehr ganz bei der Sache. Zweitens: kein Puffer zwischen Trauung und Empfang – zehn Minuten Verspätung verschieben den ganzen Abend. Drittens: das Paarshooting auf die Mittagszeit legen, weil dann gerade Zeit ist. Hartes Mittagslicht ist die schwierigste Situation des Tages.",
        "## Was tun, wenn es regnet?",
        "Ein guter Plan hat für jeden Außenpunkt eine Innenalternative, die vorher angesehen wurde. Regen ist kein Problem, wenn er eingeplant ist – überdachte Eingänge, Fensterlicht und Schirme ergeben oft die ungewöhnlicheren Bilder.",
      ],
      tr: [
        "Sonunda zaman yetmeyen düğünlerin neredeyse hepsinde aynı sorun var: akış, mekânın istekleri üzerine kurulmuş, ışığın üzerine değil. Oysa altın saat, günün kimsenin erteleyemeyeceği tek noktası.",
        "## Altın saat ne zaman?",
        "Gün batımından yaklaşık bir saat önce başlar ve mevsime göre 30–50 dakika sürer. Baden-Württemberg'de haziranda bu aşağı yukarı 20:15, eylül sonunda ise 18:30 demek. Neredeyse iki saatlik fark – ama gün çoğu zaman aynı şablonla planlanıyor.",
        "## İşleyen bir akış",
        "Hazırlık, nikahtan üç saat önce başlar, yol için pay bırakılır. Nikah. Ardından karşılama ve grup fotoğrafları – henüz herkes bir aradayken. Kırk kişiyle bu on değil, yaklaşık 25 dakika sürer.",
        "Belirleyici blok: altın saate denk getirilmiş 30–45 dakikalık çift çekimi. Bu da akşam yemeğinin ya ondan önce bitmesi ya da sonra başlaması demek. İkisi de olur – yeter ki tam ortasına denk gelmesin.",
        "## En sık yapılan üç hata",
        "Birincisi: grup fotoğraflarını yemekten sonraya bırakmak. O saatte bir kısım misafir dışarıda, bir kısmının aklı başka yerde olur. İkincisi: nikah ile karşılama arasına pay koymamak – on dakikalık gecikme bütün akşamı kaydırır. Üçüncüsü: çift çekimini öğlene almak, çünkü \"o saatte boşluk var\". Sert öğle ışığı günün en zor koşuludur.",
        "## Yağmur yağarsa?",
        "İyi bir planda her açık hava noktasının, önceden görülmüş bir kapalı alternatifi vardır. Yağmur, planlandığı sürece sorun değildir – kapalı girişler, pencere ışığı ve şemsiyeler çoğu zaman daha sıra dışı kareler verir.",
      ],
    },
    faq: [
      {
        q: { de: "Wie lange dauert ein Paarshooting?", tr: "Çift çekimi ne kadar sürer?" },
        a: {
          de: "30 bis 45 Minuten reichen für die Bilder, die später wirklich verwendet werden. Längere Shootings werden für die meisten Paare anstrengend, ohne dass die Ergebnisse besser werden.",
          tr: "Sonradan gerçekten kullanılan kareler için 30–45 dakika yeterli. Daha uzun çekimler çoğu çift için yorucu oluyor ve sonuç daha iyi olmuyor.",
        },
      },
      {
        q: { de: "Sollen die Gruppenbilder vor oder nach dem Essen sein?", tr: "Grup fotoğrafları yemekten önce mi sonra mı?" },
        a: {
          de: "Vorher, direkt im Anschluss an den Sektempfang. Dann sind alle Gäste anwesend und die Aufstellung dauert halb so lang.",
          tr: "Önce, karşılamanın hemen ardından. O anda tüm misafirler orada olur ve dizilim yarı sürede tamamlanır.",
        },
      },
    ],
  },

  {
    slug: "deutsch-tuerkische-hochzeit-fotografieren",
    date: "2026-07-30",
    seed: "lum-blog-kina",
    citySlug: "stuttgart",
    title: {
      de: "Deutsch-türkische Hochzeit: Was beim Fotografieren wirklich zählt",
      tr: "Alman-Türk düğünü: Fotoğrafta gerçekten önemli olan ne?",
    },
    excerpt: {
      de: "Zwei Feiern, zwei Familien, oft zwei Sprachen. Wer den Ablauf einer Kına-Nacht und eines Nikah kennt, verpasst die Momente nicht, auf die es ankommt – hier die Punkte, die im Zeitplan stehen müssen.",
      tr: "İki kutlama, iki aile, çoğu zaman iki dil. Kına gecesinin ve nikahın akışını bilen, önemli anları kaçırmaz – akışta mutlaka yer alması gereken noktalar.",
    },
    body: {
      de: [
        "Eine deutsch-türkische Hochzeit ist selten ein Tag. Häufig sind es zwei oder drei: die Kına-Nacht, das Standesamt und die große Feier – manchmal über zwei Wochenenden verteilt, manchmal an aufeinanderfolgenden Abenden. Für die Fotografie heißt das vor allem: vorher klären, was wann stattfindet und wer welche Rolle hat.",
        "## Die Kına-Nacht ist kein Vorprogramm",
        "Der Henna-Abend ist emotional oft der intensivste Teil der ganzen Feier. Der Moment, in dem der Braut die Hand geschlossen wird, während die Schwiegermutter die Goldmünze hineinlegt, dauert vielleicht 40 Sekunden. Wer in dieser Zeit den Objektivwechsel macht, hat ihn verpasst.",
        "Praktisch bedeutet das: zwei Kameras, kein Wechsel während des Rituals, und vorher wissen, aus welcher Richtung die Braut hereingeführt wird. Das Licht ist meist warm und schwach – lichtstarke Festbrennweiten sind hier keine Spielerei, sondern Voraussetzung.",
        "## Standesamt und Nikah trennen",
        "Viele Paare haben eine standesamtliche Trauung und zusätzlich einen Nikah. Beide brauchen Zeit im Plan, auch wenn der eine Termin nur 20 Minuten dauert. Fragt vorher, ob im Trauzimmer fotografiert werden darf und wo genau ihr stehen dürft – die Regeln unterscheiden sich von Ort zu Ort.",
        "## Der Einzug und das Geldanstecken",
        "Bei der großen Feier sind zwei Programmpunkte gesetzt: der Einzug des Paares und das Anstecken von Gold und Geld. Letzteres kann bei großen Familien 45 Minuten dauern und ist für die Gäste einer der wichtigsten Momente – im Zeitplan wird er trotzdem regelmäßig vergessen.",
        "## Zweisprachigkeit ist Arbeitserleichterung",
        "Wenn eine Großmutter nur Türkisch spricht und der Trauzeuge nur Deutsch, entscheidet die Verständigung darüber, wie entspannt die Gruppenbilder werden. Ein Team, das beide Sprachen spricht, spart bei 40 Personen leicht zehn Minuten – und die Bilder sehen entsprechend gelöster aus.",
        "## Was in den Zeitplan gehört",
        "Kına-Ritual, Einzug, Nikah beziehungsweise Trauung, Goldanstecken, Eröffnungstanz, Halay. Für jeden dieser Punkte eine Uhrzeit und eine grobe Dauer – mehr braucht es nicht, damit der Tag ohne Kommandos abläuft.",
      ],
      tr: [
        "Alman-Türk düğünü nadiren tek bir gündür. Çoğu zaman iki ya da üç: kına gecesi, nikah dairesi ve büyük düğün – kimi zaman iki hafta sonuna yayılır, kimi zaman ardışık akşamlarda olur. Fotoğraf açısından bunun anlamı şu: neyin ne zaman olduğu ve kimin hangi rolde olduğu önceden netleşmeli.",
        "## Kına gecesi bir ön program değildir",
        "Kına, duygusal olarak çoğu zaman tüm kutlamanın en yoğun bölümüdür. Kaynananın altını avuca koyup gelinin elinin kapatıldığı an belki 40 saniye sürer. O sırada lens değiştiren, o anı kaçırmıştır.",
        "Pratikte bu şu demek: iki kamera, ritüel sırasında değişiklik yok ve gelinin hangi yönden getirileceğini önceden bilmek. Işık genelde sıcak ve zayıftır – geniş diyaframlı sabit lensler burada bir lüks değil, gereklilik.",
        "## Nikah dairesi ile dinî nikahı ayırın",
        "Birçok çiftin hem resmî nikahı hem de dinî nikahı oluyor. İkisi de planda yer kaplamalı, biri yalnızca 20 dakika sürse bile. Nikah salonunda fotoğraf çekilip çekilemeyeceğini ve tam olarak nerede durabileceğinizi önceden sorun – kurallar yere göre değişiyor.",
        "## Giriş ve takı takma",
        "Büyük düğünde iki program maddesi kesindir: çiftin girişi ve altın/para takma. İkincisi kalabalık ailelerde 45 dakikayı bulabilir ve misafirler için en önemli anlardan biridir – buna rağmen akış planında düzenli olarak unutulur.",
        "## İki dillilik işi kolaylaştırır",
        "Bir anneanne yalnızca Türkçe, sağdıç yalnızca Almanca konuşuyorsa, grup fotoğraflarının ne kadar rahat geçeceğini anlaşma belirler. İki dili de konuşan bir ekip, kırk kişilik dizilimde rahatlıkla on dakika kazandırır – kareler de o oranda rahat çıkar.",
        "## Akışta mutlaka olması gerekenler",
        "Kına ritüeli, giriş, nikah, takı takma, ilk dans, halay. Bu maddelerin her biri için bir saat ve kabaca bir süre – günün komut verilmeden akması için fazlası gerekmiyor.",
      ],
    },
    faq: [
      {
        q: {
          de: "Braucht man für Kına-Nacht und Hochzeit zwei getrennte Buchungen?",
          tr: "Kına gecesi ve düğün için iki ayrı anlaşma mı gerekiyor?",
        },
        a: {
          de: "Nein. Beide Termine lassen sich in einem Paket zusammenfassen; das ist in der Regel günstiger als zwei Einzelbuchungen und sorgt dafür, dass beide Abende dieselbe Bildsprache haben.",
          tr: "Hayır. İki tarih tek pakette birleştirilebilir; bu genelde iki ayrı anlaşmadan daha uygun oluyor ve iki akşamın da aynı görsel dilde olmasını sağlıyor.",
        },
      },
      {
        q: {
          de: "Wie lange dauert das Goldanstecken?",
          tr: "Takı takma ne kadar sürer?",
        },
        a: {
          de: "Bei 150 Gästen sind 30 bis 45 Minuten realistisch. Plant diesen Punkt fest ein, sonst verschiebt er den ganzen Abend.",
          tr: "150 misafirde 30–45 dakika gerçekçi. Bu maddeyi akışa sabit koyun, yoksa bütün akşamı kaydırıyor.",
        },
      },
    ],
  },
];

export const postBySlug = (slug: string) => posts.find((p) => p.slug === slug);
