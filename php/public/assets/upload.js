/*
 * Fortschritt beim Hochladen.
 *
 * Der Betrieb hat Bilder ausgewaehlt, auf Hochladen gedrueckt, nichts gesehen,
 * das Fenster geschlossen - und der Upload war weg. Ein Formular sagt von sich
 * aus nicht, dass es arbeitet, und bei vierzig Handyfotos dauert das lange
 * genug, um wie ein Fehler auszusehen.
 *
 * Deshalb: dasselbe Formular, nur ueber XHR geschickt, damit es ueberhaupt
 * einen Fortschritt gibt. Geht das schief, uebernimmt der normale Weg - lieber
 * ohne Balken hochladen als gar nicht.
 */
(function () {
  "use strict";

  var tuerkisch = (document.documentElement.lang || "").indexOf("tr") === 0;
  var TEXT = {
    laeuft: tuerkisch ? "Yükleniyor" : "Wird hochgeladen",
    fertig: tuerkisch ? "Kaydediliyor …" : "Wird gespeichert …",
    warnung: tuerkisch
      ? "Yükleme sürüyor. Şimdi kapatırsanız fotoğraflar gitmez."
      : "Der Upload läuft noch. Wenn Sie jetzt schließen, kommen die Bilder nicht an."
  };

  function hatDatei(form) {
    var felder = form.querySelectorAll('input[type="file"]');
    for (var i = 0; i < felder.length; i++) {
      if (felder[i].files && felder[i].files.length > 0) return true;
    }
    return false;
  }

  function balkenBauen(form) {
    var box = document.createElement("div");
    box.className = "mt-3";
    box.innerHTML =
      '<div class="h-1.5 w-full overflow-hidden bg-sand-deep">' +
      '<div class="h-full w-0 bg-gold transition-all duration-150" data-balken></div>' +
      "</div>" +
      '<p class="mt-2 text-[0.72rem] uppercase tracking-[0.16em] text-muted" data-anzeige></p>';
    form.appendChild(box);
    return { balken: box.querySelector("[data-balken]"), anzeige: box.querySelector("[data-anzeige]") };
  }

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.uploadLaeuft) return;
    if (form.method.toLowerCase() !== "post" || !hatDatei(form)) return;
    if (!window.FormData || !window.XMLHttpRequest) return;

    var xhr = new XMLHttpRequest();
    if (!xhr.upload) return;

    event.preventDefault();
    form.dataset.uploadLaeuft = "1";

    // Der gedrueckte Knopf gehoert in die Daten: er sagt dem Server, welche
    // Aktion gemeint war ("was=photos-add"). Ohne ihn kaeme das Formular an,
    // aber niemand wuesste, was zu tun ist.
    var daten = new FormData(form);
    // event.submitter sagt, welcher Knopf gedrueckt wurde. Ohne ihn raet man
    // den ersten - und bei „zuruecksetzen" neben „speichern" raet man falsch.
    var knopf = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
    if (knopf && knopf.name) daten.append(knopf.name, knopf.value || "");
    if (knopf) {
      knopf.disabled = true;
      knopf.dataset.alt = knopf.textContent;
    }

    var teile = balkenBauen(form);
    var warnen = function (e) { e.preventDefault(); e.returnValue = TEXT.warnung; return TEXT.warnung; };
    window.addEventListener("beforeunload", warnen);

    function aufraeumen() {
      window.removeEventListener("beforeunload", warnen);
      delete form.dataset.uploadLaeuft;
      if (knopf) { knopf.disabled = false; knopf.textContent = knopf.dataset.alt || knopf.textContent; }
    }

    xhr.upload.addEventListener("progress", function (e) {
      if (!e.lengthComputable) return;
      var prozent = Math.round((e.loaded / e.total) * 100);
      teile.balken.style.width = prozent + "%";
      // Die letzten Prozent sind nicht das Ende: danach rechnet der Server die
      // Bilder klein. Deshalb ab 100 ein anderer Satz statt eines stehenden Balkens.
      teile.anzeige.textContent = prozent < 100 ? TEXT.laeuft + " … " + prozent + " %" : TEXT.fertig;
    });

    xhr.addEventListener("load", function () {
      window.removeEventListener("beforeunload", warnen);
      // Der Server antwortet mit einer Umleitung; ihr folgen wir selbst.
      window.location.href = xhr.responseURL || window.location.href;
    });

    xhr.addEventListener("error", function () {
      aufraeumen();
      teile.anzeige.textContent = tuerkisch ? "Yükleme başarısız — tekrar deneyin." : "Upload fehlgeschlagen – bitte erneut versuchen.";
    });

    xhr.addEventListener("abort", aufraeumen);

    xhr.open("POST", form.action || window.location.href, true);
    xhr.send(daten);
  });

  /*
   * „Ersetzen" auf einer Platzhalter-Kachel oeffnet den Datei-Dialog des
   * Upload-Formulars (per <label for=…>). Damit der Nutzer danach nicht noch
   * einmal auf „Hochladen" klicken muss, schicken wir das Formular gleich ab,
   * sobald ueberhaupt Dateien ausgewaehlt wurden. Der obige submit-Handler
   * kuemmert sich dann um Fortschritt und Fehlerfall.
   */
  document.addEventListener("change", function (event) {
    var eingang = event.target;
    if (!(eingang instanceof HTMLInputElement) || eingang.type !== "file") return;
    if (!eingang.files || eingang.files.length === 0) return;
    var form = eingang.form;
    if (!form || form.dataset.uploadLaeuft) return;
    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
    } else {
      form.submit();
    }
  });
})();
