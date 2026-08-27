<?php
/**
 * Katalog der zweiten Fassung im Panel.
 *
 * Die Kachel ist keine nachgebaute Vorschau, sondern dieselbe Karte, die der
 * Gast sieht - derselbe Weg wie im oeffentlichen Katalog. Was hier gut
 * aussieht, sieht auch dort gut aus, und was hier kaputt ist, faellt hier auf.
 *
 * @var list<array<string,mixed>> $designs
 * @var array<string,list<array{kind:string,element:string,detail:string}>> $warnings
 * @var string $styles
 * @var array<string,string> $values
 * @var list<string> $kategorien
 * @var string $filter
 * @var list<array<string,mixed>> $themen
 * @var list<array{id:string,label:string,mp4:string,webm:string,poster:string,category:string}> $videos
 * @var string $csrf
 * @var string $locale
 */

use Atelier\Design;
use Atelier\I18n;
use function Atelier\e;

$tr    = $locale === 'tr';
$p     = static fn (string $to): string => I18n::path($to, $locale);
$label = 'text-[0.66rem] uppercase tracking-[0.16em] text-muted';

$ok     = (string) ($_GET['ok'] ?? '');
$fehler = (string) ($_GET['fehler'] ?? '');
$meldungen = [
    'kopiert'                => $tr ? 'Kopyalandı.' : 'Kopiert.',
    'uebernommen'            => $tr ? 'Temadan oluşturuldu.' : 'Aus dem Thema übernommen.',
    'uebernommen_ohne_kunst' => $tr
        ? 'Oluşturuldu — ama bu temanın çizilmiş sahnesi yok, köşeler taban tasarımdan kaldı: php bin/export-scene-art.php ile dışa aktar.'
        : 'Übernommen – aber dieses Thema hat keine exportierte Szene; die Ecken blieben die der Basis: php bin/export-scene-art.php.',
    'uebernommen_teilweise' => $tr
        ? 'Oluşturuldu — ama temanın sahnesi taban tasarımdan az parça içeriyor; artan köşeler tabandan kaldı.'
        : 'Übernommen – aber die Szene des Themas hat weniger Teile als die Basis Ecken; die übrigen blieben die der Basis.',
    'gespeichert' => $tr ? 'Kaydedildi.' : 'Gespeichert.',
    'aktiv'     => $tr ? 'Yayında.' : 'Veröffentlicht.',
    'inaktiv'   => $tr ? 'Yayından kaldırıldı.' : 'Aus der Veröffentlichung genommen.',
    'quelle'    => $tr ? 'Kaynak tasarım bulunamadı.' : 'Die Quellvorlage wurde nicht gefunden.',
    'thema'     => $tr ? 'Tema bulunamadı.' : 'Das Thema wurde nicht gefunden.',
    'basis'     => $tr ? 'Yerleşimi alınacak tasarım bulunamadı.' : 'Die Vorlage fuer die Anordnung wurde nicht gefunden.',
    'name'      => $tr ? 'Ad boş olamaz.' : 'Der Name darf nicht leer sein.',
    'belegt'    => $tr ? 'Bu adla bir tasarım zaten var.' : 'Unter diesem Namen gibt es schon eine Vorlage.',
    'csrf'      => $tr ? 'Oturum düştü, sayfayı tazele.' : 'Die Sitzung ist abgelaufen, bitte neu laden.',
    'geloescht' => $tr ? 'Tasarım silindi.' : 'Die Vorlage ist gelöscht.',
    'angelegt'  => $tr ? 'Boş tasarım oluşturuldu — düzenlemeye başlayabilirsin.'
                       : 'Leere Vorlage angelegt - jetzt kann sie gebaut werden.',
    'unbekannt' => $tr ? 'Tanınmayan işlem.' : 'Unbekannte Aktion.',
];
?>
<style><?= $styles ?></style>

<div class="space-y-8">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $tr ? 'Tasarımlar (v2)' : 'Designs (v2)' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $tr
        ? 'Her kart, müşterinin göreceği kartın kendisi. Düzenle dediğinde renkleri, yazıları ve metinleri değiştirirsin; yerleşim bu fazda sabit.'
        : 'Jede Kachel ist die Karte, die der Gast sieht. „Bearbeiten" ändert Farben, Schriften und Texte; die Anordnung bleibt in dieser Phase fest.' ?>
    </p>
  </div>

  <?php if ($ok !== '' && isset($meldungen[$ok])) : ?>
    <p class="border-l-2 border-gold px-4 py-3 text-sm text-ink"><?= e($meldungen[$ok]) ?></p>
  <?php endif; ?>
  <?php if ($fehler !== '' && isset($meldungen[$fehler])) : ?>
    <p class="border-l-2 border-red-700 px-4 py-3 text-sm text-red-700"><?= e($meldungen[$fehler]) ?></p>
  <?php endif; ?>

  <?php /*
     Von vorn anfangen.

     Bis heute ging beides nur von etwas Bestehendem aus - aus einem Thema
     ueber die Anordnung einer vorhandenen Vorlage, oder als Kopie. Das hatte
     seinen Grund: der Editor konnte keine Textebene anlegen, eine leere Karte
     waere also ein Blatt gewesen, auf das man nichts schreiben kann.

     Seit der Editor Text- und Formebenen anlegt und jeder Kasten sich ziehen
     laesst, ist der Grund weg.
  */ ?>
  <details class="border border-sand-deep">
    <summary class="cursor-pointer p-5 <?= $label ?>">
      <?= $tr ? 'Boş tasarımla sıfırdan başla' : 'Leere Vorlage - von vorn anfangen' ?>
    </summary>
    <form method="post" class="flex flex-wrap items-end gap-4 border-t border-sand-deep p-5">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="leer">
      <label class="<?= $label ?>">
        <?= $tr ? 'Ad' : 'Name' ?>
        <input name="neuer_name" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
      </label>
      <button class="bg-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
        <?= $tr ? 'Oluştur' : 'Anlegen' ?>
      </button>
      <p class="w-full text-[0.78rem] leading-relaxed text-muted">
        <?= $tr
          ? 'Katmansız, taslak olarak başlar. Düzenleyicide "Yeni katman" ile yazı, görsel ve şekil ekler, yerlerini kartın üstünde sürükleyerek verirsin.'
          : 'Ohne Ebenen, als Entwurf. Im Editor legst du unter „Neue Ebene" Text, Bild und Form an und gibst ihnen den Platz mit der Hand auf der Karte.' ?>
      </p>
    </form>
  </details>

  <details class="border border-sand-deep">
    <summary class="cursor-pointer p-5 <?= $label ?>">
      <?= $tr ? 'Temadan yeni tasarım (yerleşim bir tasarımdan, renkler temadan)' : 'Neues Design aus einem Thema (Anordnung von einer Vorlage, Farben vom Thema)' ?>
    </summary>
    <form method="post" class="flex flex-wrap items-end gap-4 border-t border-sand-deep p-5">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="temadan">
      <label class="<?= $label ?>">
        <?= $tr ? 'Tema' : 'Thema' ?>
        <select name="thema" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
          <?php foreach ($themen as $t) : ?>
            <option value="<?= e((string) $t['id']) ?>"><?= e((string) $t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="<?= $label ?>">
        <?= $tr ? 'Yerleşim' : 'Anordnung von' ?>
        <select name="basis" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
          <?php foreach ($designs as $d) : ?>
            <option value="<?= e((string) $d['id']) ?>"><?= e($d['name']['de']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="<?= $label ?>">
        <?= $tr ? 'Yeni ad' : 'Neuer Name' ?>
        <input name="neuer_name" class="mt-1 block border border-sand-deep bg-transparent px-3 py-2 text-sm text-ink">
      </label>
      <button class="bg-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
        <?= $tr ? 'Oluştur' : 'Anlegen' ?>
      </button>
    </form>
  </details>

  <?php if ($kategorien !== []) : ?>
    <div class="flex flex-wrap items-center gap-4 <?= $label ?>">
      <a href="<?= e($p('/admin/designs')) ?>" class="<?= $filter === '' ? 'text-gold' : 'hover:text-ink' ?>">
        <?= $tr ? 'Hepsi' : 'Alle' ?>
      </a>
      <?php foreach ($kategorien as $k) : ?>
        <a href="<?= e($p('/admin/designs') . '?kategorie=' . rawurlencode($k)) ?>"
           class="<?= $filter === $k ? 'text-gold' : 'hover:text-ink' ?>"><?= e($k) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($designs === []) : ?>
    <p class="text-sm text-muted"><?= $tr ? 'Bu süzgeçle tasarım yok.' : 'Kein Design mit diesem Filter.' ?></p>
  <?php endif; ?>

  <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($designs as $design) : ?>
      <?php
      $id  = (string) $design['id'];
      $ms  = $warnings[$id] ?? [];
      $akt = (string) $design['status'] === 'active';
      ?>
      <div class="border <?= $akt ? 'border-gold' : 'border-sand-deep' ?>">
        <div class="d-<?= e($id) ?> relative overflow-hidden"
             style="aspect-ratio: <?= e(str_replace(':', ' / ', (string) $design['canvas']['ratio'])) ?>;
                    background: var(--d-bg, #EFE7DC);">
          <?= Design::html($design, $values, $locale) ?>
        </div>

        <div class="space-y-3 p-5">
          <div class="flex items-baseline justify-between gap-3">
            <span class="font-display text-lg text-ink"><?= e($design['name']['de']) ?></span>
            <span class="text-[0.62rem] uppercase tracking-[0.16em] <?= $akt ? 'text-gold' : 'text-muted' ?>">
              <?= e((string) $design['status']) ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-x-4 gap-y-1 <?= $label ?>">
            <span><?= e((string) $design['category']) ?></span>
            <span><?= $tr ? 'sürüm' : 'Fassung' ?> <?= (int) $design['version'] ?></span>
            <span><?= count($design['layers']) ?> <?= $tr ? 'katman' : 'Ebenen' ?></span>
            <span class="<?= $ms === [] ? '' : 'text-gold' ?>">
              <?= $ms === [] ? ($tr ? 'uyarı yok' : 'keine Hinweise') : count($ms) . ($tr ? ' uyarı' : ' Hinweise') ?>
            </span>
          </div>

          <div class="flex flex-wrap gap-2 pt-1 text-[0.62rem] uppercase tracking-[0.16em]">
            <a href="<?= e($p('/admin/designs/' . $design['slug'])) ?>"
               class="border border-ink px-3 py-2 text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $tr ? 'Düzenle' : 'Bearbeiten' ?>
            </a>
            <a href="<?= e(I18n::path('/v2/designs/' . $design['slug'], 'de')) ?>" target="_blank"
               class="border border-sand-deep px-3 py-2 text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Önizle' : 'Ansehen' ?>
            </a>
            <?php /*
               Der kurze Weg zur Einladung. Im Schaufenster steht er an jeder
               Karte, im Panel fehlte er - und wer eine Vorlage gerade gebaut
               hat, will sie sofort ausprobieren, ohne den Umweg ueber die
               oeffentliche Seite.

               Dieselbe Adresse wie dort: der Assistent nimmt die Vorlage aus
               ?design= und prueft sie selbst. Kein zweiter Einstieg, keine
               zweite Pruefung.
            */ ?>
            <a href="<?= e(I18n::path('/v2/einladung', 'de') . '?design=' . rawurlencode((string) $design['slug'])) ?>"
               target="_blank"
               class="border border-gold px-3 py-2 text-gold transition-colors hover:bg-gold hover:text-cream">
              <?= $tr ? 'Davetiye oluştur' : 'Einladung anlegen' ?>
            </a>
          </div>

          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="was" value="durum">
            <input type="hidden" name="quelle" value="<?= e($id) ?>">
            <button class="border border-sand-deep px-3 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
              <?= $akt ? ($tr ? 'Pasife al' : 'Deaktivieren') : ($tr ? 'Aktife al' : 'Aktivieren') ?>
            </button>
          </form>

          <?php if ((string) ($_GET['frage'] ?? '') === 'aktivieren' && (string) ($_GET['id'] ?? '') === $id) : ?>
            <form method="post" class="space-y-2 border-l-2 border-gold p-3">
              <p class="text-sm text-ink">
                <?= $tr
                  ? 'Bu tasarımda ' . (int) ($_GET['n'] ?? 0) . ' uyarı var. Yine de yayınlansın mı?'
                  : 'Diese Vorlage hat ' . (int) ($_GET['n'] ?? 0) . ' Hinweise. Trotzdem veröffentlichen?' ?>
              </p>
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="was" value="durum">
              <input type="hidden" name="quelle" value="<?= e($id) ?>">
              <input type="hidden" name="bestaetigt" value="1">
              <button class="bg-ink px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
                <?= $tr ? 'Uyarılarla yayınla' : 'Mit Hinweisen veröffentlichen' ?>
              </button>
            </form>
          <?php endif; ?>

          <form method="post" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="was" value="kopyala">
            <input type="hidden" name="quelle" value="<?= e($id) ?>">
            <input name="neuer_name" placeholder="<?= $tr ? 'kopyanın adı' : 'Name der Kopie' ?>"
                   class="w-56 border border-sand-deep bg-transparent px-2 py-2 text-[0.7rem] text-ink">
            <button class="border border-sand-deep px-3 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Kopyala' : 'Kopieren' ?>
            </button>
          </form>

          <?php /*
             Wegnehmen, in zwei Schritten.

             Der erste Klick fragt nur - und die Frage steht dann hier, an
             derselben Kachel, mit der Zahl der Einladungen daran. Dasselbe
             Muster wie beim Veroeffentlichen mit Hinweisen: keine
             Browserabfrage, sondern eine Zeile, die man lesen kann.

             Der Knopf traegt keine Warnfarbe. Er steht am Ende der Reihe,
             unauffaellig - wer ihn sucht, findet ihn; wer nicht, stolpert
             nicht darueber.
          */ ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="was" value="loeschen">
            <input type="hidden" name="quelle" value="<?= e($id) ?>">
            <button class="border border-sand-deep px-3 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-ink">
              <?= $tr ? 'Sil' : 'Löschen' ?>
            </button>
          </form>

          <?php if ((string) ($_GET['frage'] ?? '') === 'loeschen' && (string) ($_GET['id'] ?? '') === $id) : ?>
            <?php $anzahl = (int) ($_GET['n'] ?? 0); ?>
            <form method="post" class="space-y-2 border-l-2 border-gold p-3">
              <p class="text-sm text-ink">
                <?php if ($anzahl > 0) : ?>
                  <?= $tr
                    ? $anzahl . ' davetiye bu tasarımı kullanıyor. Silinirse çalışmaya devam ederler (her biri kendi dondurulmuş kopyasını taşır), ama bir daha yeni sürüme geçirilemezler. Yine de silinsin mi?'
                    : $anzahl . ' Einladungen hängen an dieser Vorlage. Sie laufen weiter (jede trägt ihre eingefrorene Kopie), lassen sich danach aber nicht mehr auffrischen. Trotzdem löschen?' ?>
                <?php else : ?>
                  <?= $tr
                    ? 'Bu tasarımı kullanan davetiye yok. Silinsin mi? Geri alınamaz.'
                    : 'Keine Einladung hängt an dieser Vorlage. Wirklich löschen? Das lässt sich nicht zurücknehmen.' ?>
                <?php endif; ?>
              </p>
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="was" value="loeschen">
              <input type="hidden" name="quelle" value="<?= e($id) ?>">
              <input type="hidden" name="bestaetigt" value="1">
              <button class="bg-ink px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-cream transition-colors hover:bg-gold">
                <?= $tr ? 'Evet, sil' : 'Ja, löschen' ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php /*
   Die Filmbibliothek. Sie gehoert hierher und nicht in einen eigenen Reiter:
   sie ist Zubehoer der Vorlagen, und ein siebzehnter Reiter fuer eine Liste
   mit einer Handvoll Eintraegen waere genau die Ueberdetaillierung, ueber die
   sich der Kunde beschwert hat.
*/ ?>
<section class="mt-16 border-t border-sand-deep pt-10">
  <h3 class="font-display text-lg text-ink"><?= $tr ? 'Video kitaplığı' : 'Filmbibliothek' ?></h3>
  <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
    <?= $tr
      ? 'Buradaki videolar, izni açık olan video katmanlarında çifte seçenek olarak çıkar. Çift kendi videosunu yükleyemez — bulamaz.'
      : 'Diese Filme bietet der Assistent dem Paar an, wenn die Videoebene das Recht dazu traegt. Eigene Dateien kann das Paar nicht laden - es findet keine.' ?>
  </p>

  <form method="post" enctype="multipart/form-data" class="mt-6 space-y-6">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <?php foreach ($videos as $i => $film) : ?>
      <div class="flex flex-wrap items-start gap-5 border-t border-sand-deep pt-5 first:border-0 first:pt-0">
        <input type="hidden" name="vid_id_<?= (int) $i ?>" value="<?= e($film['id']) ?>">
        <input type="hidden" name="vid_mp4_<?= (int) $i ?>" value="<?= e($film['mp4']) ?>">
        <input type="hidden" name="vid_webm_<?= (int) $i ?>" value="<?= e($film['webm']) ?>">
        <input type="hidden" name="vid_poster_<?= (int) $i ?>" value="<?= e($film['poster']) ?>">

        <video src="<?= e($film['mp4']) ?>" muted preload="metadata"
               <?= $film['poster'] !== '' ? 'poster="' . e($film['poster']) . '"' : '' ?>
               class="h-20 w-14 shrink-0 bg-ink object-cover"></video>

        <label class="min-w-[14rem] flex-1 text-[0.66rem] uppercase tracking-[0.16em] text-muted">
          <?= $tr ? 'Adı' : 'Name' ?>
          <input name="vid_label_<?= (int) $i ?>" value="<?= e($film['label']) ?>" maxlength="80"
                 class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink"></label>

        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">
          <?= $tr ? 'Kategori' : 'Kategorie' ?>
          <select name="vid_cat_<?= (int) $i ?>"
                  class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink">
            <option value="">—</option>
            <?php foreach (Design::CATEGORIES as $k) : ?>
              <option value="<?= e($k) ?>" <?= $film['category'] === $k ? 'selected' : '' ?>><?= e($k) ?></option>
            <?php endforeach; ?>
          </select></label>

        <button name="was" value="video-loeschen-<?= e($film['id']) ?>"
                class="self-end pb-2 text-[0.66rem] uppercase tracking-[0.16em] text-muted hover:text-red-700"
                data-confirm="<?= $tr ? 'Bu video silinsin mi?' : 'Diesen Film entfernen?' ?>">
          <?= $tr ? 'Sil' : 'Entfernen' ?>
        </button>
      </div>
    <?php endforeach; ?>

    <div class="border-t border-sand-deep pt-5">
      <div class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Yeni video' : 'Neuer Film' ?></div>
      <div class="mt-3 grid gap-4 sm:grid-cols-2">
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Adı' : 'Name' ?>
          <input name="vid_neu_label" maxlength="80"
                 class="mt-1 w-full border border-sand-deep bg-cream px-3 py-2 text-sm normal-case tracking-normal text-ink"></label>
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted">mp4 / webm
          <input type="file" name="vid_neu_datei" accept="video/mp4,video/webm,video/quicktime"
                 class="mt-1 w-full text-[0.8rem] text-muted"></label>
        <label class="text-[0.66rem] uppercase tracking-[0.16em] text-muted"><?= $tr ? 'Kapak görseli' : 'Standbild' ?>
          <input type="file" name="vid_neu_poster" accept="image/png,image/jpeg,image/webp"
                 class="mt-1 w-full text-[0.8rem] text-muted"></label>
      </div>
    </div>

    <button name="was" value="videos-kaydet"
            class="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
      <?= $tr ? 'Kitaplığı kaydet' : 'Bibliothek speichern' ?>
    </button>
  </form>
</section>
