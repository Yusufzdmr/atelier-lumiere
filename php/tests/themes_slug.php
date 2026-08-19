<?php
declare(strict_types=1);

use Atelier\Themes;

/*
 * Themes::slug() macht aus einem Namen eine Kennung. Sie steht in der
 * Adresszeile und im Datensatz, also darf sie Buchstaben nicht verlieren.
 *
 * Aufgefallen in Faz 2 an der Schwesterfunktion Design::key(): strtolower()
 * arbeitet byteweise. Aus "Élysée" bleibt das grosse É stehen, und die Zeile
 * darunter wirft es als Nicht-ASCII weg - die Kennung hiess "lysee".
 */

assert_same('elysee', Themes::slug('Élysée'), 'slug: grosses É ueberlebt');
assert_same('elysee-nacht', Themes::slug('Élysée Nacht'), 'slug: Akzent im Namen');
assert_same('safak-isik', Themes::slug('Şafak Işık'), 'slug: tuerkische Grossbuchstaben');
assert_same('gruen-weiss', Themes::slug('Grün & Weiß'), 'slug: Umlaut und scharfes S');
assert_same('cigdem', Themes::slug('Çiğdem'), 'slug: Ç und ğ');

// Kleingeschrieben ging es vorher schon - das darf nicht kaputtgehen.
assert_same('elysee', Themes::slug('élysée'), 'slug: klein geschrieben bleibt');
assert_same('noir', Themes::slug('  Noir  '), 'slug: Leerzeichen aussen fallen weg');
assert_same('thema', Themes::slug('###'), 'slug: ohne Buchstaben bleibt der Rueckfall');
