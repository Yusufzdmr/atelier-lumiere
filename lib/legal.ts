/* ------------------------------------------------------------------ */
/*  Rechtstexte – Standardinhalte                                      */
/*                                                                     */
/*  Bewusst nur auf Deutsch: Impressum, Datenschutzerklärung und AGB   */
/*  richten sich nach deutschem Recht und gelten auch unter /tr.       */
/*                                                                     */
/*  Mini-Auszeichnung im Fließtext (siehe components/LegalBody.tsx):   */
/*    Leerzeile        -> neuer Absatz                                 */
/*    "- " am Zeilen-  -> Aufzählungspunkt                             */
/*      anfang                                                         */
/*    **fett**         -> Hervorhebung                                 */
/*    `code`           -> Monospace                                    */
/*    [Text](URL)      -> Link                                         */
/*    {{consent}}      -> Button "Cookie-Einstellungen ändern"         */
/*                                                                     */
/*  Platzhalter, die aus den Kontaktdaten im Admin gefüllt werden:     */
/*    {legalName} {owner} {street} {zip} {city} {email} {phone}        */
/* ------------------------------------------------------------------ */

export type LegalSection = { heading: string; body: string };

export type LegalPage = {
  title: string;
  sections: LegalSection[];
  /** Kleingedruckter Hinweis am Seitenende (leer = ausgeblendet) */
  note: string;
};

export type LegalKey = "impressum" | "datenschutz" | "agb";
export type LegalContent = Record<LegalKey, LegalPage>;

export const legalPageOrder: LegalKey[] = ["impressum", "datenschutz", "agb"];

export const legal: LegalContent = {
  impressum: {
    title: "Impressum",
    sections: [
      {
        heading: "Angaben gemäß § 5 DDG",
        body: "{legalName}\n{owner}\n{street}\n{zip} {city}",
      },
      {
        heading: "Kontakt",
        body: "Telefon: {phone}\nE-Mail: {email}",
      },
      {
        heading: "Umsatzsteuer-ID",
        body: "Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:\nDE 123 456 789",
      },
      {
        heading: "Berufsbezeichnung",
        body: "Fotograf (Bundesrepublik Deutschland)\nZuständige Kammer: Handwerkskammer Region Stuttgart",
      },
      {
        heading: "Redaktionell verantwortlich",
        body: "{owner}\n{street}, {zip} {city}",
      },
      {
        heading: "EU-Streitschlichtung",
        body: "Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: [ec.europa.eu/consumers/odr](https://ec.europa.eu/consumers/odr/). Unsere E-Mail-Adresse finden Sie oben im Impressum.",
      },
      {
        heading: "Verbraucherstreitbeilegung",
        body: "Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.",
      },
      {
        heading: "Bildnachweis",
        body: "Alle auf dieser Website gezeigten Aufnahmen sind Platzhalter der Demo-Version. In der Live-Version stammen sämtliche Bilder von {owner} und werden ausschließlich mit schriftlicher Einwilligung der abgebildeten Personen veröffentlicht.",
      },
      {
        heading: "Haftung für Inhalte und Links",
        body: "Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Für Inhalte externer Links ist stets der jeweilige Anbieter verantwortlich. Zum Zeitpunkt der Verlinkung waren keine Rechtsverstöße erkennbar.",
      },
    ],
    note: "**Hinweis:** Dieser Text ist eine Vorlage für die Demo-Version und ersetzt keine Rechtsberatung. Vor dem Livegang sind die Angaben durch die Betreiberin bzw. den Betreiber zu prüfen und zu ergänzen.",
  },

  datenschutz: {
    title: "Datenschutzerklärung",
    sections: [
      {
        heading: "1. Verantwortlicher",
        body: "{legalName}, {owner}, {street}, {zip} {city}\nE-Mail: {email} · Telefon: {phone}",
      },
      {
        heading: "2. Hosting und Server-Logfiles",
        body: "Diese Website wird bei einem Anbieter mit Serverstandort in der Europäischen Union betrieben. Beim Aufruf der Seite werden technisch notwendige Daten (IP-Adresse in gekürzter Form, Datum und Uhrzeit, aufgerufene Seite, Browsertyp) in Server-Logfiles verarbeitet. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse am sicheren und stabilen Betrieb). Die Logfiles werden nach spätestens 30 Tagen gelöscht. Mit dem Hoster besteht ein Auftragsverarbeitungsvertrag nach Art. 28 DSGVO.",
      },
      {
        heading: "3. Schriften und externe Inhalte",
        body: "Alle Schriftarten werden lokal von unserem eigenen Server geladen. Es besteht dabei **keine Verbindung zu Servern von Google**. Karteninhalte (Google Maps) werden nicht automatisch eingebunden – erst wenn Sie aktiv auf einen Kartenlink klicken, verlassen Sie diese Website.",
      },
      {
        heading: "4. Cookies und Einwilligung",
        body: "Technisch notwendige Speicherungen (z. B. Ihre Cookie-Entscheidung, der Zugang zur privaten Galerie) erfolgen auf Grundlage von § 25 Abs. 2 TDDDG. Statistik- und Marketing-Cookies werden ausschließlich nach Ihrer ausdrücklichen Einwilligung gesetzt (Art. 6 Abs. 1 lit. a DSGVO, § 25 Abs. 1 TDDDG). Ihre Einwilligung können Sie jederzeit mit Wirkung für die Zukunft widerrufen:\n\n{{consent}}",
      },
      {
        heading: "5. Kontaktformular und Anfragen",
        body: "Wenn Sie uns über das Formular, per E-Mail, Telefon oder WhatsApp kontaktieren, verarbeiten wir die von Ihnen angegebenen Daten zur Bearbeitung Ihrer Anfrage. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (vorvertragliche Maßnahmen) bzw. lit. f DSGVO. Anfragen ohne anschließenden Vertragsschluss löschen wir nach spätestens zwölf Monaten; bei Vertragsschluss gelten die handels- und steuerrechtlichen Aufbewahrungsfristen von bis zu zehn Jahren.",
      },
      {
        heading: "6. Private Kundengalerie",
        body: "Die Online-Galerie ist ausschließlich mit Zugangscode und Passwort erreichbar und über die Datei `robots.txt` sowie ein `noindex`-Meta-Tag von Suchmaschinen ausgeschlossen. Gespeichert werden der Zugriffszeitpunkt sowie die von Ihnen markierte Bildauswahl. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung). Galerien werden spätestens 24 Monate nach der Hochzeit gelöscht, sofern nichts anderes vereinbart wurde.",
      },
      {
        heading: "7. Digitale Hochzeitseinladung",
        body: "Beim Erstellen einer Einladung verarbeiten wir die von Ihnen eingegebenen Angaben (Namen, Datum, Ort, Text, optional ein Bild) zur Bereitstellung der Einladungsseite. Rückmeldungen Ihrer Gäste (Name, Zu- oder Absage, Personenzahl, optionale Nachricht) werden zur Weitergabe an Sie als Gastgeberin bzw. Gastgeber gespeichert. Verantwortlich für die Inhalte der jeweiligen Einladung sind die erstellenden Paare; wir handeln insoweit als Auftragsverarbeiter. Einladungsseiten sind nicht indexierbar und werden zwölf Monate nach dem Hochzeitsdatum gelöscht.",
      },
      {
        heading: "8. Reichweitenmessung",
        body: "Sofern Sie eingewilligt haben, nutzen wir Google Analytics 4 mit aktivierter IP-Anonymisierung. Anbieter ist Google Ireland Limited. Eine Übermittlung in Drittländer erfolgt auf Grundlage der EU-Standardvertragsklauseln und des EU-US Data Privacy Framework. Ohne Ihre Einwilligung wird kein Analytics-Skript geladen.",
      },
      {
        heading: "9. Ihre Rechte",
        body: "- Auskunft über die zu Ihnen gespeicherten Daten (Art. 15 DSGVO)\n- Berichtigung unrichtiger Daten (Art. 16 DSGVO)\n- Löschung (Art. 17 DSGVO) und Einschränkung der Verarbeitung (Art. 18 DSGVO)\n- Datenübertragbarkeit (Art. 20 DSGVO)\n- Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21 DSGVO)\n- Widerruf erteilter Einwilligungen mit Wirkung für die Zukunft (Art. 7 Abs. 3 DSGVO)\n- Beschwerde bei einer Aufsichtsbehörde (Art. 77 DSGVO)\n\nZuständige Aufsichtsbehörde: Der Landesbeauftragte für den Datenschutz und die Informationsfreiheit Baden-Württemberg, Lautenschlagerstraße 20, 70173 Stuttgart.",
      },
      {
        heading: "10. Bildrechte und Veröffentlichung",
        body: "Aufnahmen von Hochzeiten werden ausschließlich mit gesonderter schriftlicher Einwilligung der abgebildeten Personen veröffentlicht. Diese Einwilligung ist freiwillig, unabhängig vom Fotoauftrag und jederzeit mit Wirkung für die Zukunft widerrufbar.",
      },
    ],
    note: "**Hinweis:** Dieser Text ist eine Vorlage für die Demo-Version. Vor dem Livegang ist er an die tatsächlich eingesetzten Dienste anzupassen und juristisch zu prüfen.",
  },

  agb: {
    title: "Allgemeine Geschäftsbedingungen",
    sections: [
      {
        heading: "§ 1 Geltungsbereich",
        body: "Diese Bedingungen gelten für alle Foto- und Filmaufträge sowie für digitale Produkte (Online-Galerie, digitale Hochzeitseinladung) von {legalName}, {street}, {zip} {city}.",
      },
      {
        heading: "§ 2 Vertragsschluss und Reservierung",
        body: "Der Vertrag kommt mit schriftlicher Auftragsbestätigung zustande. Der Termin gilt erst mit Eingang der Anzahlung in Höhe von 30 % der Auftragssumme als reserviert. Die Restzahlung ist spätestens 14 Tage nach der Veranstaltung fällig.",
      },
      {
        heading: "§ 3 Leistungsumfang",
        body: "Der Umfang ergibt sich aus dem gebuchten Paket. Die Auswahl und Bearbeitung der Bilder erfolgt nach künstlerischem Ermessen der Fotografin bzw. des Fotografen. Ein Anspruch auf unbearbeitete Rohdaten besteht nicht.",
      },
      {
        heading: "§ 4 Liefer- und Bearbeitungszeiten",
        body: "- Vorschau mit rund 20 Bildern innerhalb von 48 Stunden\n- Vollständige Online-Galerie innerhalb von drei Wochen\n- Hochzeitsfilm innerhalb von sechs Wochen",
      },
      {
        heading: "§ 5 Nutzungsrechte",
        body: "Die Auftraggeber erhalten ein einfaches, zeitlich und räumlich unbeschränktes Nutzungsrecht für private Zwecke einschließlich der Veröffentlichung in sozialen Netzwerken. Eine kommerzielle Nutzung oder Weitergabe an Dritte zu gewerblichen Zwecken bedarf einer gesonderten Vereinbarung. Das Urheberrecht verbleibt bei der Urheberin bzw. dem Urheber.",
      },
      {
        heading: "§ 6 Veröffentlichung durch uns",
        body: "Eine Veröffentlichung der Aufnahmen zu Referenzzwecken erfolgt ausschließlich nach gesonderter schriftlicher Einwilligung der abgebildeten Personen. Die Einwilligung ist freiwillig und jederzeit widerrufbar.",
      },
      {
        heading: "§ 7 Rücktritt und Ausfall",
        body: "Bei Rücktritt der Auftraggeber bis 180 Tage vor dem Termin wird die Anzahlung einbehalten. Bei späterem Rücktritt werden 50 % der Auftragssumme fällig, ab 30 Tagen vor dem Termin 80 %. Bei Krankheit oder höherer Gewalt auf unserer Seite stellen wir eine gleichwertige Vertretung aus unserem Netzwerk; ist dies nicht möglich, werden bereits geleistete Zahlungen vollständig erstattet. Weitergehende Ansprüche sind ausgeschlossen.",
      },
      {
        heading: "§ 8 Online-Galerie",
        body: "Die private Galerie ist passwortgeschützt und steht für 24 Monate zur Verfügung. Die Weitergabe der Zugangsdaten an Familie und Gäste ist gestattet, eine öffentliche Verbreitung des Links nicht.",
      },
      {
        heading: "§ 9 Digitale Hochzeitseinladung",
        body: "Die digitale Einladung ist für Kundinnen und Kunden mit gebuchtem Foto- oder Filmpaket kostenfrei. Für alle übrigen Nutzer beträgt der Preis einmalig 79 € inkl. MwSt.; die Einladungsseite bleibt zwölf Monate nach dem Hochzeitsdatum erreichbar. Für die eingegebenen Inhalte sind die erstellenden Personen verantwortlich. Es besteht kein Anspruch auf ununterbrochene Verfügbarkeit; wir bemühen uns um eine Erreichbarkeit von 99 % im Jahresmittel.",
      },
      {
        heading: "§ 10 Widerrufsrecht",
        body: "Verbraucher haben bei Fernabsatzverträgen ein 14-tägiges Widerrufsrecht. Bei Dienstleistungen zu einem bestimmten Termin (Veranstaltungsfotografie, § 312g Abs. 2 Nr. 9 BGB) besteht kein Widerrufsrecht. Bei digitalen Inhalten erlischt das Widerrufsrecht mit ausdrücklicher Zustimmung zum vorzeitigen Beginn der Ausführung.",
      },
      {
        heading: "§ 11 Schlussbestimmungen",
        body: "Es gilt deutsches Recht. Sollten einzelne Bestimmungen unwirksam sein, bleibt die Wirksamkeit der übrigen Bestimmungen unberührt.",
      },
    ],
    note: "**Hinweis:** Vorlage für die Demo-Version. Vor dem Livegang anwaltlich prüfen lassen.",
  },
};
