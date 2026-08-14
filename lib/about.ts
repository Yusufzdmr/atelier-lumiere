import type { L } from "./i18n";

/* ------------------------------------------------------------------ */
/*  "Über mich" – Standardinhalte                                      */
/*  Im Admin unter "Über mich & Stimmen" bearbeitbar.                  */
/* ------------------------------------------------------------------ */

export type AboutValue = { t: L; d: L };

export type AboutContent = {
  /** Name in der Überschrift – in beiden Sprachen identisch */
  name: string;
  lead: L;
  body: L<string[]>;
  valuesTitle: L;
  values: AboutValue[];
  gearTitle: L;
  gear: L<string[]>;
};

export const about: AboutContent = {
  name: "Julian Roth",
  lead: {
    de: "Fotograf und Filmemacher aus Stuttgart. Seit 2016 Hochzeiten – vorher zehn Jahre Reportage.",
    tr: "Stuttgart'tan fotoğrafçı ve film yapımcısı. 2016'dan beri düğün – öncesinde on yıl foto muhabirliği.",
  },
  body: {
    de: [
      "Ich bin über den Journalismus zur Hochzeitsfotografie gekommen. Zehn Jahre lang habe ich für Zeitungen und Magazine fotografiert – Menschen, die nicht wussten, dass sie fotografiert werden, und Situationen, die man nicht wiederholen kann. Genau diese Haltung habe ich mitgenommen.",
      "Eine Hochzeit ist kein Fotoshooting mit Feier drumherum. Sie ist ein Tag, an dem hunderte kleine Dinge passieren, die niemand geplant hat: der Bruder, der beim Einzug heimlich Tränen wegwischt. Die Großmutter, die zum ersten Mal seit Jahren tanzt. Die Braut, die zwei Minuten allein im Nebenzimmer steht.",
      "Deshalb arbeite ich leise. Ich stelle keine Szenen nach, ich unterbreche keine Reden für ein besseres Foto und ich rufe niemanden zurück, weil das Licht gerade schöner ist. Was ich mache: den Tag vorher so planen, dass die schönen Momente überhaupt Platz haben.",
      "Bei größeren Hochzeiten bin ich nicht allein. Mein Team besteht aus zwei festen Kolleginnen für Zweitkamera und Film – wir arbeiten seit vier Jahren zusammen und brauchen keine Absprachen mehr während der Feier.",
    ],
    tr: [
      "Düğün fotoğrafçılığına gazetecilikten geldim. On yıl boyunca gazeteler ve dergiler için çektim – fotoğraflandığını bilmeyen insanlar ve tekrar edilemeyecek anlar. Bu yaklaşımı olduğu gibi getirdim.",
      "Düğün, etrafında kutlama olan bir fotoğraf çekimi değildir. Kimsenin planlamadığı yüzlerce küçük şeyin yaşandığı bir gündür: girişte gizlice gözyaşını silen abi. Yıllar sonra ilk kez dans eden anneanne. Yan odada iki dakika tek başına duran gelin.",
      "Bu yüzden sessiz çalışıyorum. Sahne yeniden kurdurmuyorum, daha iyi bir kare için konuşmaları bölmüyorum ve ışık güzelleşti diye kimseyi geri çağırmıyorum. Yaptığım şey: günü önceden öyle planlamak ki o güzel anlara yer kalsın.",
      "Büyük düğünlerde yalnız değilim. Ekibimde ikinci kamera ve film için iki sabit meslektaşım var – dört yıldır birlikte çalışıyoruz, düğün sırasında artık konuşmamıza bile gerek kalmıyor.",
    ],
  },
  valuesTitle: { de: "Wie ich arbeite", tr: "Nasıl çalışıyorum" },
  values: [
    {
      t: { de: "Leise statt laut", tr: "Sessiz, gürültüsüz" },
      d: {
        de: "Keine Kommandos, keine Unterbrechungen. Ihr merkt uns kaum – das sieht man später auf den Bildern.",
        tr: "Komut yok, kesinti yok. Bizi neredeyse fark etmiyorsunuz – bu sonradan karelerde görülüyor.",
      },
    },
    {
      t: { de: "Ehrlich statt geschönt", tr: "Dürüst, süslemesiz" },
      d: {
        de: "Ich sage vorher, was zeitlich geht und was nicht. Lieber ein realistischer Plan als ein enttäuschter Abend.",
        tr: "Zaman olarak neyin mümkün olduğunu önceden söylüyorum. Hayal kırıklığı yerine gerçekçi bir plan.",
      },
    },
    {
      t: { de: "Pünktlich statt irgendwann", tr: "Zamanında" },
      d: {
        de: "Vorschau in 48 Stunden, Galerie in drei Wochen, Film in sechs. Vertraglich zugesichert.",
        tr: "48 saatte ön izleme, üç haftada galeri, altı haftada film. Sözleşmeyle garanti.",
      },
    },
    {
      t: { de: "Zweisprachig", tr: "İki dilli" },
      d: {
        de: "Deutsch und Türkisch – bei Familien, in denen beide Sprachen gesprochen werden, macht das den Tag deutlich entspannter.",
        tr: "Almanca ve Türkçe – iki dilin konuşulduğu ailelerde bu, günü ciddi şekilde rahatlatıyor.",
      },
    },
  ],
  gearTitle: { de: "Technik", tr: "Ekipman" },
  gear: {
    de: [
      "Zwei Vollformat-Gehäuse pro Person, jeweils mit Doppel-Kartenslot",
      "Festbrennweiten 35 / 50 / 85 mm, f/1.4",
      "Entfesselte Blitze für dunkle Säle",
      "Funkmikrofone für Trauung und Reden",
      "Doppelte Sicherung noch am Hochzeitsabend, Archiv für 24 Monate",
    ],
    tr: [
      "Kişi başı iki full frame gövde, her biri çift kart yuvalı",
      "35 / 50 / 85 mm f/1.4 sabit lensler",
      "Karanlık salonlar için harici flaşlar",
      "Nikah ve konuşmalar için telsiz mikrofonlar",
      "Düğün akşamı çift yedekleme, 24 ay arşiv",
    ],
  },
};
