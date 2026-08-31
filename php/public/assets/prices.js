/*
 * Die mitlaufende Summe auf der Preisseite.
 *
 * "Bu ek islerde yani pakete gelecek ilave bolumu - tikladikca paket fiyati
 * oynamasi lazim."
 *
 * Das Skript rechnet nur die ANZEIGE. Was zaehlt, rechnet der Server noch
 * einmal (Packages::summary), wenn die Auswahl am Kontaktformular ankommt -
 * eine Zahl aus dem Browser ist eine Behauptung des Browsers.
 *
 * Gerechnet wird in Cent und mit ganzen Zahlen. Eine Summe aus Kommazahlen
 * kaeme irgendwann als 1889.9999999999998 heraus, und der Kunde saehe es.
 *
 * Ein Posten ohne data-cent hat keinen rechenbaren Preis ("auf Anfrage").
 * Er bleibt waehlbar und faellt aus der Summe - und die Seite sagt es, statt
 * schweigend zu wenig anzuzeigen.
 */
(function () {
  "use strict";

  var form = document.querySelector("[data-preisrechner]");
  if (!form) return;

  var zeile = form.querySelector("[data-preis-zeile]");
  var summe = form.querySelector("[data-preis-summe]");
  var leer = form.querySelector("[data-preis-leer]");
  var offen = form.querySelector("[data-preis-offen]");
  if (!zeile || !summe) return;

  /*
   * Dieselbe Schreibweise wie die Posten darueber: deutsche Tausenderpunkte,
   * volle Euro. Sie steht hier ein zweites Mal (Packages::money ist die
   * erste) - eine Zahl, die anders aussieht als die Zahlen daneben, liest
   * sich wie ein Fehler.
   */
  var schreibe = function (cent) {
    var euro = Math.floor(cent / 100);
    var rest = cent % 100;
    var text = String(euro).replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    if (rest !== 0) text += "," + (rest < 10 ? "0" + rest : String(rest));

    return text + " €";
  };

  var rechne = function () {
    var cent = 0;
    var gewaehlt = 0;
    var ohnePreis = false;

    form.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function (feld) {
      if (!feld.checked) return;
      gewaehlt += 1;

      var wert = feld.getAttribute("data-cent");
      if (wert === null) {
        ohnePreis = true;
        return;
      }

      cent += parseInt(wert, 10) || 0;
    });

    // Nichts gewaehlt: dann steht der Satz da, der sagt, was zu tun ist -
    // und keine Null. Eine Null ist ein Preis, und dieser Preis ist keiner.
    zeile.hidden = gewaehlt === 0;
    if (leer) leer.hidden = gewaehlt !== 0;
    if (offen) offen.hidden = !ohnePreis;

    summe.textContent = schreibe(cent);
  };

  form.addEventListener("change", rechne);
  // Beim Laden: der Browser stellt nach dem Zurueckgehen die Haken wieder her,
  // und dann stimmte die Summe von vorhin nicht mehr mit ihnen ueberein.
  rechne();
})();
