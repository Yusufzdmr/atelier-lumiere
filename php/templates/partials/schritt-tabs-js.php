<?php
/**
 * Die Schritt-Reiter zu Schaltflaechen aufruesten - geteilt vom Assistenten
 * und vom Bearbeiten-Bildschirm.
 *
 * Eingebunden INNERHALB eines <script nonce="...">, das die aufrufende Seite
 * selbst oeffnet: der Nonce gehoert zur Antwort, nicht zu diesem Baustein.
 *
 * Der Baustein steht in einer eigenen Funktion und sucht sich sein Formular
 * selbst. Zwei Gruende: seine fruehen return-Zeilen duerfen nicht das ganze
 * umgebende Skript abbrechen, und die beiden Seiten nennen ihre Variable
 * verschieden (form / formular). So haengt er an nichts ausser dem DOM.
 */
?>
(function () {
  var form = document.querySelector('[data-wizard]');
  if (!form) return;

  var labels = Array.prototype.slice.call(form.querySelectorAll('[data-step-label]'));
  var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
  if (labels.length < 2 || steps.length < 2) return;

  /* Die von invite-v2.js angehaengten Knoepfe. Es haengt sie zuletzt an das
     Formular (form.appendChild(nav), siehe dort), Zurueck vor Weiter - also
     sind es die LETZTEN beiden, nicht die ersten. Das ist der Unterschied,
     der zaehlt: der Kommentar bei den Programmzeilen erwaegt einen
     Hinzufuegen-Knopf, und der stuende als type=button weiter oben im
     Dokument. Von vorne gezaehlt waere er dann "Zurueck". */
  var nav = Array.prototype.slice.call(form.querySelectorAll('button[type=button]')).slice(-2);
  if (nav.length < 2) return;
  var back = nav[0];
  var next = nav[1];

  function current() {
    for (var n = 0; n < steps.length; n++) {
      if (!steps[n].hidden) return n;
    }
    return 0;
  }

  labels.forEach(function (li, i) {
    /* Erst hier wird aus Text ein Knopf. Die Kinder wandern hinein, statt
       neu geschrieben zu werden: die Nummer (.wz-tab-num) und der Titel
       bleiben so genau die vom Server gesetzten und escapten Knoten. */
    var btn = document.createElement('button');
    btn.type = 'button';
    while (li.firstChild) btn.appendChild(li.firstChild);
    li.appendChild(btn);

    btn.addEventListener('click', function () {
      var guard = steps.length + 2;
      while (current() !== i && guard-- > 0) {
        var vor = current();
        (i > vor ? next : back).click();
        /* Bewegt sich nichts, hat Weiter abgelehnt - invite-v2.js prueft die
           Pflichtfelder des Schritts und kehrt ohne show() zurueck. Dann
           steht die Meldung des Browsers am Feld, und weiterzuklopfen
           brachte nur dieselbe Blase noch dreimal. */
        if (current() === vor) break;
      }
    });
  });
})();
