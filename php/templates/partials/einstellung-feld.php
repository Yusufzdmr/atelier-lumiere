<?php
/**
 * Ein Feld fuer eine Abschnittseinstellung - nach ihrer Art gebaut.
 *
 * Es steht als eigene Vorlage da, weil es an ZWEI Stellen gebraucht wird:
 * bei den Einstellungen, die jeder Abschnitt hat (Ausrichtung, Rahmen,
 * Luft), und bei denen, die nur einer Art gehoeren (der Kartenhaken des
 * Ortes, die YouTube-Adresse der Musik).
 *
 * Bis hierher konnte die erste Schleife nur Auswahllisten. Das ging, solange
 * dort nur Auswahllisten standen - der Rahmen bringt eine Datei mit
 * (frameSrc), und die waere als leere Liste erschienen: kein Fehler, kein
 * Hinweis, nur ein Feld, in das sich nichts eintragen laesst. Genau der
 * Fehler, der bei der Einbettung schon einmal gemacht wurde.
 *
 * Erwartet: $schluessel, $s (das Schema), $werte, $i, $sprache, $tr,
 * $label, $feld.
 *
 * @var string               $schluessel
 * @var array<string,mixed>  $s
 * @var array<string,mixed>  $werte
 * @var int                  $i
 */

use function Atelier\e;
?>
            <?php if ((string) $s['type'] === 'bool') : ?>
              <label class="flex items-center gap-2 text-[0.66rem] text-muted">
                <input type="checkbox" name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       <?= ($werte[$schluessel] ?? $s['default']) ? 'checked' : '' ?>>
                <?= e($s['label'][$sprache] ?? (string) $schluessel) ?></label>
            <?php elseif ((string) $s['type'] === 'src') : ?>
              <?php /*
                 Eine Datei aus dem eigenen Haus - und jetzt auch mit dem Weg
                 dorthin.

                 Hier stand lange nur ein Textfeld mit dem Hinweis "Hochladen
                 kommt spaeter": die Datei musste anderswo in /uploads landen
                 und ihr Pfad von Hand abgetippt werden. Fuer ein Blatt oder
                 einen Film gab es diesen Weg im Panel laengst (sec_bg_datei,
                 intro_datei) - nur diese Zeile hatte ihn nie bekommen, und
                 damit war die Tonspur einer Vorlage praktisch unerreichbar.

                 Das Pfadfeld bleibt darunter stehen: es zeigt, was gerade
                 gilt, und leert man es, ist die Datei weg. Ein Dateifeld
                 allein koennte nur ersetzen, nie entfernen.

                 accept richtet sich nach 'kind' im Katalog - dieselbe
                 Auskunft, mit der der Controller seine Pruefung waehlt.
              */ ?>
              <?php
                $art_ = (string) ($s['kind'] ?? '');
                $accept_ = match ($art_) {
                    'audio' => 'audio/mpeg,audio/mp4,audio/x-m4a,audio/ogg,audio/wav',
                    'video' => 'video/mp4,video/webm,video/quicktime',
                    default => 'image/png,image/jpeg,image/webp,image/svg+xml',
                };
                $wert_ = (string) ($werte[$schluessel] ?? $s['default']);
              ?>
              <label class="<?= $label ?>"><?= e($s['label'][$sprache] ?? (string) $schluessel) ?>
                <input type="file" class="<?= $feld ?>"
                       name="sec_setdatei_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       accept="<?= e($accept_) ?>"></label>

              <?php /*
                 Der Spieler gehoert zum FELD, nicht zum Wert.

                 Bisher stand er nur da, wenn schon etwas hinterlegt war - und
                 genau dann sucht ihn niemand. Wer gerade eine Datei waehlt,
                 will sie hoeren, bevor er speichert; das Skript haengt sie
                 ueber data-tonvorschau hinein. Ohne Datei und ohne Skript
                 bleibt er leer und stumm, und das ist keine Luege: es liegt
                 ja nichts an.
              */ ?>
              <?php if ($art_ === 'audio') : ?>
                <audio class="mt-2 w-full" controls preload="none"
                       data-tonvorschau="sec_setdatei_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       <?= $wert_ !== '' ? 'src="' . e($wert_) . '"' : '' ?>></audio>
              <?php endif; ?>

              <?php /*
                 Bei Ton darf auch eine fremde Adresse stehen.

                 Ein Lied liegt oft schon irgendwo, und es erst herunter- und
                 wieder hochzuladen ist ein Umweg ohne Gewinn. Nur https -
                 die Einladung laeuft ueber https, und ein Lied ueber http
                 wuerde der Browser als gemischten Inhalt verweigern: der Ton
                 bliebe stumm, ohne dass jemand sagen koennte, warum.
                 Design::safeAudio haelt diese Grenze.

                 Bild und Film bleiben beim eigenen Haus - sie gehoeren zur
                 Vorlage, und eine fremde Adresse waere ein Bild, das
                 verschwinden kann.
              */ ?>
              <label class="<?= $label ?> mt-2 block">
                <?= $art_ === 'audio'
                  ? ($tr ? 'ya da yol / https adresi (boş bırakınca kalkar)' : 'oder Pfad / https-Adresse (leer entfernt es)')
                  : ($tr ? 'ya da yol (boş bırakınca kalkar)' : 'oder Pfad (leer entfernt es)') ?>
                <input class="<?= $feld ?> font-mono text-[0.78rem]"
                       name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       value="<?= e($wert_) ?>"
                       placeholder="<?= $art_ === 'audio' ? 'https://… veya /uploads/designs/…' : '/uploads/designs/…' ?>"></label>
            <?php elseif ((string) $s['type'] === 'einbettung') : ?>
              <?php /*
                 Ein Textfeld, kein Dateifeld und keine Liste.

                 Die letzte Schublade unten baut eine Auswahl ueber
                 $s['options'] - eine Adresse hat keine, sie waere dort als
                 LEERE Liste erschienen: kein Fehler, kein Hinweis, nur ein
                 Feld, in das sich nichts eintragen laesst.

                 Das Beispiel steht als placeholder und nicht in der
                 Beschriftung: es sagt in einer Zeile, was hineingehoert, und
                 verschwindet, sobald jemand etwas schreibt.

                 Geprueft wird beim Speichern (Design::safeEinbettung). Wer
                 hier etwas anderes hineinschreibt, bekommt ein leeres Feld
                 zurueck - und der Abschnitt faellt weg, statt einen Knopf zu
                 zeigen, der nichts laden kann.
              */ ?>
              <label class="<?= $label ?>"><?= e($s['label'][$sprache] ?? (string) $schluessel) ?>
                <input class="<?= $feld ?> font-mono text-[0.78rem]"
                       name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>"
                       value="<?= e((string) ($werte[$schluessel] ?? $s['default'])) ?>"
                       placeholder="https://www.youtube.com/watch?v=…"></label>
            <?php else : ?>
              <label class="<?= $label ?>"><?= e($s['label'][$sprache] ?? (string) $schluessel) ?>
                <select class="<?= $feld ?>" name="sec_set_<?= e((string) $schluessel) ?>_<?= $i ?>">
                  <?php foreach ($s['options'] as $option) : ?>
                    <option value="<?= e((string) $option) ?>"
                      <?= (string) ($werte[$schluessel] ?? $s['default']) === (string) $option ? 'selected' : '' ?>>
                      <?= e((string) $option) ?></option>
                  <?php endforeach; ?>
                </select></label>
            <?php endif; ?>
