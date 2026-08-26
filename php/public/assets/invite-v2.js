/*
 * Der Assistent der zweiten Fassung: Schritte ein- und ausblenden, Vorschau
 * mitlaufen lassen.
 *
 * Ohne dieses Skript funktioniert das Formular vollstaendig - alle Schritte
 * stehen dann untereinander und ein Absenden reicht. Deshalb wird hier auch
 * nichts geprueft: welche Felder es gibt, hat der Server entschieden, bevor
 * die Seite ankam.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-wizard]');
  if (!form) return;

  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
  var labels = Array.prototype.slice.call(form.querySelectorAll('[data-step-label]'));
  if (steps.length < 2) return;   // Ein Schritt braucht keine Navigation.

  var at = 0;

  var nav = document.createElement('div');
  nav.className = 'mt-10 flex items-center justify-between gap-4';

  var back = document.createElement('button');
  back.type = 'button';
  back.className = 'border border-sand-deep px-6 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-muted';
  back.textContent = document.documentElement.lang === 'de' ? 'Zurück' : 'Back';

  var next = document.createElement('button');
  next.type = 'button';
  next.className = 'border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.16em] text-ink';
  next.textContent = document.documentElement.lang === 'de' ? 'Weiter' : 'Next';

  nav.appendChild(back);
  nav.appendChild(next);
  form.appendChild(nav);

  function show(i) {
    at = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach(function (el, n) { el.hidden = n !== at; });
    labels.forEach(function (el, n) {
      el.className = n === at
        ? 'text-ink'
        : 'text-muted';
    });
    back.hidden = at === 0;
    // Auf dem letzten Schritt steht der Absenden-Knopf im Formular selbst.
    next.hidden = at === steps.length - 1;
    window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
    ladeAbschnitte();
  }

  back.addEventListener('click', function () { show(at - 1); });
  next.addEventListener('click', function () {
    // Pflichtfelder des aktuellen Schrittes zuerst.
    var pflicht = steps[at].querySelectorAll('[required]');
    for (var i = 0; i < pflicht.length; i++) {
      if (!pflicht[i].reportValidity()) return;
    }
    show(at + 1);
  });

  show(0);

  /*
   * Vorschau: die gebundenen Felder heissen im Dokument anders als im
   * Formular. Diese Zuordnung ist die einzige Stelle, an der der Browser
   * etwas ueber bind-Namen wissen muss.
   */
  /*
   * Die Abschnitte holt der Server.
   *
   * Sie kennen die Datumsregel, den Kartenlink und die Frage, welcher
   * Abschnitt ueberhaupt gedruckt wird - all das noch einmal hier zu bauen
   * hiesse, DesignSections::html() ein zweites Mal zu schreiben, in einer
   * anderen Sprache. Zwei Quellen fuer dieselbe Antwort laufen auseinander.
   *
   * Nicht bei jedem Tastendruck: Abschnitte aendern sich nicht Buchstabe fuer
   * Buchstabe, und jede Anfrage waere Laerm. Beim Schrittwechsel und wenn ein
   * Feld verlassen wird, reicht.
   */
  var sections = document.querySelector('[data-sections]');
  var holt = false;

  function ladeAbschnitte() {
    if (!sections || holt) return;
    holt = true;

    var daten = new FormData(form);
    daten.set('was', 'preview');
    // Dateien gehoeren nicht in eine Vorschau - sie machen die Anfrage gross
    // und die Abschnitte zeigen ohnehin keine Bilder.
    form.querySelectorAll('input[type=file]').forEach(function (el) { daten.delete(el.name); });

    fetch(window.location.pathname, { method: 'POST', body: daten, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.text() : null; })
      .then(function (html) { if (html !== null) sections.innerHTML = html; })
      .catch(function () { /* Vorschau ist eine Zugabe; ihr Ausfall darf den Assistenten nicht stoeren. */ })
      .finally(function () { holt = false; });
  }

  // Nur change, nicht zusaetzlich focusout: bei einem geaenderten Feld feuern
  // beide, die zweite Anfrage faellt an der holt-Sperre aus, und dann fehlte
  // womoeglich gerade die letzte Aenderung. change kommt bei Textfeldern beim
  // Verlassen mit Aenderung, bei Haken und Listen sofort - genau die Momente,
  // in denen sich an den Abschnitten etwas aendern kann.
  form.addEventListener('change', ladeAbschnitte);

  var preview = form.querySelector('[data-preview]') || document.querySelector('[data-preview]');
  if (!preview) return;

  function paint() {
    var werte = {};
    form.querySelectorAll('[data-live]').forEach(function (el) {
      werte[el.getAttribute('data-live')] = el.value.trim();
    });

    // couple_names und initials setzen sich aus zwei Feldern zusammen; der
    // Rest ist eins zu eins.
    //
    // wedding_weekday fehlt hier absichtlich: der Wochentag wird serverseitig
    // aus dem Datum von Atelier\Dates::weekday() abgeleitet, in drei Sprachen.
    // Eine zweite Implementierung im Browser koennte von dem abweichen, was
    // auf der veroeffentlichten Einladung tatsaechlich steht. Die Vorschau
    // leert diesen Knoten deshalb unten nur, statt ihn zu spiegeln - die
    // Beispieldaten sagen sonst weiter "Sonntag", waehrend der Kunde laengst
    // einen Samstag eingetippt hat. Die veroeffentlichte Einladung bekommt
    // den echten Wochentag vom Server und hat immer recht.
    var text = {
      // Die Umbrueche um das Und wie in Design::bindValues() - die Vorschau
      // soll dasselbe zeigen wie die spaeter gedruckte Karte. Sichtbar werden
      // sie durch white-space:pre-line auf der gebundenen Ebene.
      couple_names: [werte.bride, werte.groom].filter(Boolean).join('\n&\n'),
      initials: (werte.bride || ' ').charAt(0) + (werte.groom || ' ').charAt(0),
      bride_name: werte.bride || '',
      groom_name: werte.groom || '',
      wedding_date: werte.date || '',
      wedding_time: werte.time || '',
      location_name: werte.venue || '',
      location_address: werte.address || '',
      invitation_text: werte.message || '',
      hashtag: werte.hashtag || ''
    };

    // Welches Element welches bind traegt, steht im Markup (data-bind, siehe
    // Design::html()) - nicht hier. Ein Design ohne wedding_time hat die
    // Zeile nicht, und dann findet querySelector nichts. Das ist richtig so.
    Object.keys(text).forEach(function (bind) {
      var ziel = preview.querySelector('[data-bind="' + bind + '"]');
      if (ziel) ziel.textContent = text[bind];
    });

    // wedding_weekday wird geleert, nicht gespiegelt (siehe oben): sonst
    // widerspraeche die Karte dem Datum, das der Kunde gerade eingetippt hat.
    var wochentag = preview.querySelector('[data-bind="wedding_weekday"]');
    if (wochentag) wochentag.textContent = '';
  }

  form.querySelectorAll('[data-live]').forEach(function (el) {
    el.addEventListener('input', paint);
  });
  paint();

  /* ---------------------------- Ortssuche ---------------------------- */

  /*
   * Die Adresse wird gesucht, nicht geraten.
   *
   * Vorher war das Adressfeld ein leeres Textfeld, und ob der Kartendienst
   * die Anschrift kennt, stellte sich erst auf der fertigen Einladung
   * heraus - dann naemlich, wenn keine Karte kam.
   *
   * Gefragt wird unser eigener Server (/v2/orte), nicht das Verzeichnis:
   * sonst saehe ein Fremder jeden Tastendruck des Paares.
   *
   * Alles hier ist Zugabe. Faellt das Skript aus, bleibt ein Textfeld, in
   * das man tippt wie bisher - und wer einen Ort eintraegt, den niemand
   * kennt, bekommt weiterhin eine Einladung, nur ohne Karte.
   */
  var suchfeld = form.querySelector('[data-ortsuche]');
  if (!suchfeld) return;

  var liste  = form.querySelector('[data-ortliste]');
  var notiz  = form.querySelector('[data-ortnotiz]');
  var karte  = form.querySelector('[data-ortkarte]');
  var saal   = form.querySelector('[data-live="venue"]');
  var sprache = (document.documentElement.getAttribute('lang') || 'de').slice(0, 2);

  // Die Adresse des Suchendpunkts steht im Pfad der Seite: /de/... oder
  // /en/... - fest verdrahtet waere sie in einer Sprache falsch.
  var sprachpfad = (location.pathname.match(/^\/[a-z]{2}(?=\/|$)/) || ['/de'])[0];
  var suchPfad   = sprachpfad + '/v2/orte';
  var kartePfad  = sprachpfad + '/v2/karte-vorschau.png';

  var wort = {
    suche:   sprache === 'en' ? 'Searching…'          : 'Suche…',
    nichts:  sprache === 'en'
      ? 'Not found. You can type the address anyway - then the invitation shows it without a map.'
      : 'Nicht gefunden. Ihr könnt die Adresse trotzdem eintragen – dann steht sie ohne Karte auf der Einladung.',
    fehler:  sprache === 'en' ? 'Search unavailable.' : 'Suche gerade nicht erreichbar.'
  };

  function sagen(text) {
    if (!notiz) return;
    notiz.textContent = text || '';
    notiz.hidden = !text;
  }

  function zumachen() {
    if (!liste) return;
    liste.textContent = '';
    liste.hidden = true;
  }

  function nehmen(ort) {
    suchfeld.value = ort.address || '';
    // Den Saalnamen nur setzen, wenn das Feld leer ist: wer ihn schon
    // getippt hat, hat sich etwas dabei gedacht ("Bei Oma im Garten").
    if (saal && !saal.value && ort.name) saal.value = ort.name;

    zumachen();
    sagen('');
    if (karte && ort.sig) {
      karte.src = kartePfad + '?lat=' + encodeURIComponent(ort.lat)
                + '&lng=' + encodeURIComponent(ort.lng) + '&s=' + encodeURIComponent(ort.sig);
      karte.hidden = false;
    }
    paint();
  }

  function zeigen(orte) {
    if (!liste) return;
    liste.textContent = '';

    orte.forEach(function (ort) {
      var li = document.createElement('li');
      var knopf = document.createElement('button');
      knopf.type = 'button';
      knopf.className = 'block w-full px-3 py-2 text-left text-sm hover:bg-sand';

      var oben = document.createElement('span');
      oben.className = 'block';
      oben.textContent = ort.name || ort.address;
      knopf.appendChild(oben);

      if (ort.name && ort.address) {
        var unten = document.createElement('span');
        unten.className = 'block text-[0.72rem] text-muted';
        unten.textContent = ort.address;
        knopf.appendChild(unten);
      }

      knopf.addEventListener('click', function () { nehmen(ort); });
      li.appendChild(knopf);
      liste.appendChild(li);
    });

    liste.hidden = orte.length === 0;
  }

  var wartet = null;
  var laeuft = null;

  suchfeld.addEventListener('input', function () {
    var q = suchfeld.value.trim();

    if (karte) karte.hidden = true;
    window.clearTimeout(wartet);

    if (q.length < 3) { zumachen(); sagen(''); return; }

    /*
     * Erst warten, dann fragen. Bei jedem Tastendruck zu fragen waere ein
     * Dutzend Anfragen fuer eine Adresse - an ein Verzeichnis, dessen
     * Regeln genau das nicht wollen, und der Server bremst uns ohnehin.
     */
    wartet = window.setTimeout(function () {
      sagen(wort.suche);

      // Die vorige Anfrage abbrechen: sonst ueberholt eine langsame
      // Antwort von vor drei Buchstaben die aktuelle.
      if (laeuft) laeuft.abort();
      laeuft = new AbortController();

      window.fetch(suchPfad + '?q=' + encodeURIComponent(q), { signal: laeuft.signal })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var orte = (d && d.places) || [];
          zeigen(orte);
          sagen(orte.length ? '' : wort.nichts);
        })
        .catch(function (e) {
          if (e && e.name === 'AbortError') return;
          zumachen();
          sagen(wort.fehler);
        });
    }, 450);
  });

  // Klick daneben macht die Liste zu - sonst steht sie ueber dem naechsten
  // Feld und faengt dessen Klicks ab.
  document.addEventListener('click', function (e) {
    if (!liste) return;
    if (e.target !== suchfeld && !liste.contains(e.target)) zumachen();
  });
})();
