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
  var preview = form.querySelector('[data-preview]');
  if (!preview) return;

  function paint() {
    var werte = {};
    form.querySelectorAll('[data-live]').forEach(function (el) {
      werte[el.getAttribute('data-live')] = el.value.trim();
    });

    // couple_names und initials setzen sich aus zwei Feldern zusammen; der
    // Rest ist eins zu eins.
    var text = {
      couple_names: [werte.bride, werte.groom].filter(Boolean).join(' & '),
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
  }

  form.querySelectorAll('[data-live]').forEach(function (el) {
    el.addEventListener('input', paint);
  });
  paint();
})();
