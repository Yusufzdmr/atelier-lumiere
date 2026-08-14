<?php
/**
 * Zugangsdaten fremder Dienste.
 *
 * Geheimnisse stehen nur maskiert im Feld: ein leeres Feld heißt
 * „unverändert lassen“. Sonst müsste man ein Passwort abtippen, um eine
 * Kleinigkeit daneben zu ändern.
 *
 * @var string $locale
 * @var array<string,mixed> $settings
 * @var string $csrf
 * @var array{ok:bool,mode:string,message:string}|null $test
 */

use function Atelier\e;
use Atelier\I18n;
use Atelier\Integrations;

$de = $locale === 'de';
$input = 'w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold';
$label = 'block text-[0.6rem] uppercase tracking-[0.18em] text-muted';
$hint = 'mt-2 text-[0.72rem] leading-relaxed text-muted';

$paypal = $settings['paypal'];
$google = $settings['google'];
$meta = $settings['meta'];
$extras = $settings['extras'];

$chip = static function (bool $on, string $name) : string {
    $classes = $on ? 'border-gold text-gold' : 'border-sand-deep text-muted';
    return '<span class="border px-3 py-1.5 text-[0.62rem] uppercase tracking-[0.14em] ' . $classes . '">'
        . \Atelier\e($name) . ' ' . ($on ? '✓' : '—') . '</span>';
};
?>
<div class="space-y-12">
  <div>
    <h2 class="font-display text-xl text-ink"><?= $de ? 'Integrationen' : 'Entegrasyonlar' ?></h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
      <?= $de
          ? 'Zugangsdaten fremder Dienste – hier eingetragen, ohne dass jemand an den Code muss. Was hier steht, hat Vorrang vor der Serverkonfiguration; bleibt ein Feld leer, gilt weiter der dort hinterlegte Wert.'
          : 'Diğer servislerin erişim bilgileri – koda dokunmadan buradan girilir. Buradaki değer sunucu ayarından önce gelir; alan boşsa sunucudaki değer geçerli kalır.' ?>
    </p>
    <p class="<?= $hint ?>">
      <?= $de
          ? 'Geheimnisse werden nur verdeckt angezeigt. Ein leeres Feld bedeutet: unverändert lassen.'
          : 'Gizli değerler maskeli gösterilir. Boş alan „değiştirme“ demektir.' ?>
    </p>

    <div class="mt-6 flex flex-wrap gap-2">
      <?= $chip($paypal['clientId'] !== '' && $paypal['clientSecret'] !== '', 'PayPal') ?>
      <?= $chip($google['gaId'] !== '', 'Analytics') ?>
      <?= $chip($google['gtmId'] !== '', 'Tag Manager') ?>
      <?= $chip($google['adsId'] !== '', 'Google Ads') ?>
      <?= $chip($meta['pixelId'] !== '', 'Meta Pixel') ?>
      <?= $chip($google['searchConsole'] !== '', 'Search Console') ?>
    </div>
  </div>

  <form method="post" class="space-y-10">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="was" value="settings">

    <!-- ------------------------------ PayPal ------------------------------ -->
    <section class="border border-sand-deep p-6">
      <h3 class="font-display text-xl text-ink">PayPal</h3>
      <p class="<?= $hint ?>">
        <?= $de
            ? 'Aus dem PayPal-Entwicklerkonto: Apps & Credentials → Live (oder Sandbox) → App auswählen. Gebraucht werden nur Client-ID und Secret, niemals das Kontopasswort.'
            : 'PayPal geliştirici hesabından: Apps & Credentials → Live (veya Sandbox) → uygulamayı seçin. Yalnızca Client ID ve Secret gerekir, hesap parolası asla.' ?>
      </p>

      <div class="mt-6 grid gap-7 md:grid-cols-2">
        <div>
          <label class="<?= $label ?>" for="paypal_client_id">Client ID</label>
          <input id="paypal_client_id" name="paypal_client_id" class="<?= $input ?>"
                 placeholder="<?= e(Integrations::mask($paypal['clientId']) ?: 'AeA1QIZX…') ?>">
        </div>
        <div>
          <label class="<?= $label ?>" for="paypal_secret">Secret</label>
          <input id="paypal_secret" name="paypal_secret" type="password" autocomplete="new-password" class="<?= $input ?>"
                 placeholder="<?= e(Integrations::mask($paypal['clientSecret']) ?: 'EGnHDxD_qRp…') ?>">
        </div>
        <div>
          <label class="<?= $label ?>" for="paypal_mode"><?= $de ? 'Modus' : 'Mod' ?></label>
          <select id="paypal_mode" name="paypal_mode" class="<?= $input ?>">
            <option value="sandbox" <?= $paypal['mode'] === 'sandbox' ? 'selected' : '' ?>>Sandbox (<?= $de ? 'Test' : 'test' ?>)</option>
            <option value="live" <?= $paypal['mode'] === 'live' ? 'selected' : '' ?>>Live (<?= $de ? 'echtes Geld' : 'gerçek ödeme' ?>)</option>
          </select>
          <p class="<?= $hint ?>">
            <?= $de
                ? 'Sandbox-Schlüssel funktionieren nur im Sandbox-Modus – und umgekehrt.'
                : 'Sandbox anahtarları yalnızca sandbox modunda çalışır – tersi de geçerli.' ?>
          </p>
        </div>
      </div>
    </section>

    <!-- ------------------------------ Google ------------------------------ -->
    <section class="border border-sand-deep p-6">
      <h3 class="font-display text-xl text-ink">Google</h3>
      <p class="<?= $hint ?>">
        <?= $de
            ? 'Analytics und Ads starten erst nach der Einwilligung im Cookie-Banner: Statistik schaltet Analytics frei, Marketing schaltet Ads und den Pixel frei. Ohne Einwilligung wird nicht einmal das Skript geladen.'
            : 'Analytics ve Ads yalnızca çerez bandındaki onaydan sonra çalışır: istatistik onayı Analytics’i, pazarlama onayı Ads ve pikseli açar. Onay yoksa script bile yüklenmez.' ?>
      </p>

      <div class="mt-6 grid gap-7 md:grid-cols-3">
        <div>
          <label class="<?= $label ?>" for="ga_id">Analytics 4 (G-…)</label>
          <input id="ga_id" name="ga_id" value="<?= e($google['gaId']) ?>" class="<?= $input ?>" placeholder="G-XXXXXXXXXX">
        </div>
        <div>
          <label class="<?= $label ?>" for="gtm_id">Tag Manager (GTM-…)</label>
          <input id="gtm_id" name="gtm_id" value="<?= e($google['gtmId']) ?>" class="<?= $input ?>" placeholder="GTM-XXXXXXX">
        </div>
        <div>
          <label class="<?= $label ?>" for="ads_id">Ads Conversion-ID (AW-…)</label>
          <input id="ads_id" name="ads_id" value="<?= e($google['adsId']) ?>" class="<?= $input ?>" placeholder="AW-123456789">
        </div>
      </div>

      <div class="mt-8 border-t border-sand-deep pt-6">
        <div class="text-[0.66rem] uppercase tracking-[0.18em] text-muted"><?= $de ? 'Conversion-Label' : 'Dönüşüm etiketleri' ?></div>
        <p class="<?= $hint ?>">
          <?= $de
              ? 'In Google Ads steht bei jeder Conversion ein Wert wie AW-123456789/AbC-D_efG. Hier gehört nur der Teil nach dem Schrägstrich hinein.'
              : 'Google Ads’te her dönüşümde AW-123456789/AbC-D_efG gibi bir değer olur. Buraya yalnızca eğik çizgiden sonraki kısım yazılır.' ?>
        </p>

        <div class="mt-5 grid gap-7 md:grid-cols-3">
          <div>
            <label class="<?= $label ?>" for="ads_label_contact"><?= $de ? 'Anfrage über das Formular' : 'Formdan gelen talep' ?></label>
            <input id="ads_label_contact" name="ads_label_contact" value="<?= e($google['adsLabels']['contact']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="ads_label_invite"><?= $de ? 'Einladung erstellt' : 'Davetiye oluşturuldu' ?></label>
            <input id="ads_label_invite" name="ads_label_invite" value="<?= e($google['adsLabels']['invite']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>" for="ads_label_phone"><?= $de ? 'Klick auf die Telefonnummer' : 'Telefon numarasına tıklama' ?></label>
            <input id="ads_label_phone" name="ads_label_phone" value="<?= e($google['adsLabels']['phone']) ?>" class="<?= $input ?>">
          </div>
        </div>

        <div class="mt-7 grid gap-7 md:grid-cols-3">
          <div>
            <label class="<?= $label ?>" for="ads_lead_value"><?= $de ? 'Wert einer Anfrage' : 'Bir talebin değeri' ?></label>
            <input id="ads_lead_value" name="ads_lead_value" value="<?= e($google['leadValue']) ?>" class="<?= $input ?>" placeholder="150">
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Optional. Hilft Google beim Optimieren: Was ist eine Anfrage im Schnitt wert?'
                  : 'İsteğe bağlı. Google’ın optimizasyonuna yardım eder: bir talep ortalama ne değerinde?' ?>
            </p>
          </div>
          <div>
            <label class="<?= $label ?>" for="ads_currency"><?= $de ? 'Währung' : 'Para birimi' ?></label>
            <input id="ads_currency" name="ads_currency" value="<?= e($google['currency']) ?>" class="<?= $input ?>" placeholder="EUR">
          </div>
        </div>
      </div>

      <div class="mt-8 border-t border-sand-deep pt-6">
        <div class="grid gap-7 md:grid-cols-2">
          <div>
            <label class="<?= $label ?>" for="gsc">Search Console</label>
            <input id="gsc" name="gsc" value="<?= e($google['searchConsole']) ?>" class="<?= $input ?>" placeholder="google-site-verification=…">
            <p class="<?= $hint ?>">
              <?= $de
                  ? 'Nur der content-Wert des Metatags aus der Search Console („HTML-Tag“-Methode).'
                  : 'Search Console’un verdiği meta etiketindeki content değeri („HTML etiketi“ yöntemi).' ?>
            </p>
          </div>
          <div>
            <label class="<?= $label ?>" for="bing">Bing Webmaster Tools</label>
            <input id="bing" name="bing" value="<?= e($google['bing']) ?>" class="<?= $input ?>" placeholder="msvalidate.01">
          </div>
        </div>

        <label class="mt-6 flex cursor-pointer items-start gap-3 text-[0.82rem] leading-relaxed text-ink">
          <input type="checkbox" name="consent_mode" <?= $google['consentMode'] ? 'checked' : '' ?> class="mt-1 h-4 w-4 shrink-0 accent-[#B08D57]">
          <span>
            <?= $de ? 'Google Consent Mode v2 verwenden' : 'Google Consent Mode v2 kullan' ?>
            <span class="mt-1 block text-[0.74rem] text-muted">
              <?= $de
                  ? 'Empfohlen und für Werbung in der EU vorausgesetzt: Vor der Einwilligung steht alles auf „abgelehnt“, danach wird auf die tatsächliche Auswahl aktualisiert.'
                  : 'Önerilir ve AB’de reklam için gereklidir: onaydan önce her şey „reddedildi“ olur, onaydan sonra gerçek seçime güncellenir.' ?>
            </span>
          </span>
        </label>
      </div>
    </section>

    <!-- ------------------------------- Meta ------------------------------- -->
    <section class="border border-sand-deep p-6">
      <h3 class="font-display text-xl text-ink">Meta (Facebook &amp; Instagram)</h3>
      <div class="mt-6 max-w-md">
        <label class="<?= $label ?>" for="meta_pixel">Pixel-ID</label>
        <input id="meta_pixel" name="meta_pixel" value="<?= e($meta['pixelId']) ?>" class="<?= $input ?>" placeholder="123456789012345">
        <p class="<?= $hint ?>"><?= $de ? 'Wird nur nach Marketing-Einwilligung geladen.' : 'Yalnızca pazarlama onayından sonra yüklenir.' ?></p>
      </div>
    </section>

    <div class="flex flex-wrap items-center gap-6">
      <button class="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        <?= $de ? 'Speichern' : 'Kaydet' ?>
      </button>
      <?php if (($settings['updatedAt'] ?? '') !== '') : ?>
        <span class="text-[0.72rem] text-muted">
          <?= $de ? 'Zuletzt geändert' : 'Son değişiklik' ?>: <?= e(date('d.m.Y H:i', strtotime((string) $settings['updatedAt']) ?: time())) ?>
        </span>
      <?php endif; ?>
    </div>
  </form>

  <!-- --------------------------- Verbindungstest --------------------------- -->
  <section class="border border-sand-deep p-6">
    <h3 class="font-display text-xl text-ink"><?= $de ? 'PayPal-Verbindung prüfen' : 'PayPal bağlantısını kontrol et' ?></h3>
    <p class="<?= $hint ?>">
      <?= $de
          ? 'Fragt bei PayPal ein Zugriffstoken an. Es wird keine Zahlung ausgelöst und kein Betrag bewegt.'
          : 'PayPal’dan yalnızca bir erişim anahtarı ister. Ödeme başlatmaz, para hareketi olmaz.' ?>
    </p>

    <form method="post" class="mt-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="paypal-test">
      <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
        <?= $de ? 'Verbindung testen' : 'Bağlantıyı test et' ?>
      </button>
    </form>

    <?php if ($test !== null) : ?>
      <?php
      $messages = [
          'ok'       => $de ? 'Verbindung steht (' . ($test['mode'] === 'live' ? 'Live' : 'Sandbox') . ').' : 'Bağlantı kuruldu (' . ($test['mode'] === 'live' ? 'Live' : 'Sandbox') . ').',
          'missing'  => $de ? 'Es fehlen Client-ID oder Secret.' : 'Client ID veya Secret eksik.',
          'rejected' => $de ? 'PayPal hat die Zugangsdaten abgelehnt. Passen ID, Secret und Modus zusammen?' : 'PayPal bilgileri reddetti. ID, Secret ve mod uyumlu mu?',
      ];
      ?>
      <p class="mt-4 text-[0.82rem] <?= $test['ok'] ? 'text-gold' : 'text-red-700' ?>">
        <?= e($messages[$test['message']] ?? ($de ? 'PayPal war nicht erreichbar.' : 'PayPal’a ulaşılamadı.')) ?>
      </p>
    <?php endif; ?>
  </section>

  <!-- ---------------------------- Weitere Keys ---------------------------- -->
  <section class="space-y-6">
    <div>
      <h3 class="font-display text-xl text-ink"><?= $de ? 'Weitere Schlüssel' : 'Diğer anahtarlar' ?></h3>
      <p class="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
        <?= $de
            ? 'Platz für alles, was später dazukommt – E-Mail-Versand, WhatsApp, Buchhaltung. Der technische Name ist der Schlüssel, unter dem der Code den Wert liest; er darf hier schon stehen, bevor die Anbindung gebaut ist.'
            : 'Sonradan eklenecek her şey için yer – e-posta gönderimi, WhatsApp, muhasebe. Teknik ad, kodun değeri okuduğu anahtardır; entegrasyon yazılmadan önce de burada durabilir.' ?>
      </p>
    </div>

    <?php foreach ($extras as $extra) : ?>
      <form method="post" class="border border-sand-deep p-6">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="was" value="extra-save">
        <input type="hidden" name="id" value="<?= e((string) $extra['id']) ?>">

        <div class="grid gap-7 md:grid-cols-[1fr_1fr_1.2fr]">
          <div>
            <label class="<?= $label ?>"><?= $de ? 'Anzeigename' : 'Görünen ad' ?></label>
            <input name="label" value="<?= e((string) $extra['label']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="<?= $label ?>"><?= $de ? 'Technischer Name' : 'Teknik ad' ?></label>
            <input readonly value="<?= e((string) $extra['name']) ?>" class="<?= $input ?> font-mono text-muted">
          </div>
          <div>
            <label class="<?= $label ?>"><?= $de ? 'Wert' : 'Değer' ?></label>
            <input name="value" type="<?= !empty($extra['secret']) ? 'password' : 'text' ?>" autocomplete="new-password" class="<?= $input ?>"
                   placeholder="<?= e(!empty($extra['secret']) ? Integrations::mask((string) $extra['value']) : (string) $extra['value']) ?>">
          </div>
        </div>

        <div class="mt-6 grid gap-7 md:grid-cols-[1fr_auto] md:items-end">
          <div>
            <label class="<?= $label ?>"><?= $de ? 'Notiz' : 'Not' ?></label>
            <input name="note" value="<?= e((string) $extra['note']) ?>" class="<?= $input ?>">
          </div>
          <div class="flex gap-3">
            <button class="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
              <?= $de ? 'Speichern' : 'Kaydet' ?>
            </button>
            <button name="was" value="extra-delete"
                    class="px-4 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted hover:text-red-700">
              <?= $de ? 'Löschen' : 'Sil' ?>
            </button>
          </div>
        </div>
      </form>
    <?php endforeach; ?>

    <form method="post" class="border border-dashed border-sand-deep p-6">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="was" value="extra-add">

      <div class="grid gap-7 md:grid-cols-[1fr_1fr_1.2fr]">
        <div>
          <label class="<?= $label ?>"><?= $de ? 'Anzeigename' : 'Görünen ad' ?></label>
          <input name="label" class="<?= $input ?>" placeholder="Brevo API-Key">
        </div>
        <div>
          <label class="<?= $label ?>"><?= $de ? 'Technischer Name' : 'Teknik ad' ?></label>
          <input name="name" required class="<?= $input ?> font-mono" placeholder="BREVO_API_KEY">
        </div>
        <div>
          <label class="<?= $label ?>"><?= $de ? 'Wert' : 'Değer' ?></label>
          <input name="value" class="<?= $input ?>">
        </div>
      </div>

      <div class="mt-6 grid gap-7 md:grid-cols-[1fr_auto] md:items-end">
        <div>
          <label class="<?= $label ?>"><?= $de ? 'Notiz' : 'Not' ?></label>
          <input name="note" class="<?= $input ?>" placeholder="<?= $de ? 'wofür ist der Schlüssel?' : 'anahtar ne için?' ?>">
        </div>
        <div class="flex items-center gap-6">
          <label class="flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
            <input type="checkbox" name="secret" checked class="h-4 w-4 accent-[#B08D57]">
            <?= $de ? 'Geheim' : 'Gizli' ?>
          </label>
          <button class="bg-ink px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            <?= $de ? 'Hinzufügen' : 'Ekle' ?>
          </button>
        </div>
      </div>
    </form>
  </section>
</div>
