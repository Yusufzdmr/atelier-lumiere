#!/bin/bash
#
# Umzug auf die richtige Domain - in einem Durchgang und umkehrbar.
#
#   ./atelier-domain-umstellen.sh hochzeit-krumbach.de
#
# Was danach gilt:
#   - die neue Domain liefert die Seite aus (mit Zertifikat)
#   - 45-147-46-177.sslip.io und die nackte IP antworten mit 301 dorthin
#   - config.php traegt die neue Adresse (site_url)
#
# Warum 301 und nicht einfach abschalten: Google hat die Demo-Adresse
# indexiert. Ein 301 sagt "das ist umgezogen" und traegt weiter, was sich
# angesammelt hat. Ein 404 wirft es weg, und die neue Domain faengt bei null
# an - gegen ihre eigene, noch im Index stehende Kopie.
#
# gidonla.com wird nicht angefasst: sein Block traegt seinen eigenen Namen und
# steht in einer eigenen Datei. Diese hier legt nur einen Block dazu und
# aendert den der Demo.

set -euo pipefail

DOMAIN="${1:-}"
if [ -z "$DOMAIN" ]; then
    echo "Aufruf: $0 <domain>   (z. B. hochzeit-krumbach.de, ohne www und ohne https)" >&2
    exit 1
fi

# Erst die Wirklichkeit, dann die Konfiguration: zeigt die Domain noch nicht
# hierher, scheitert certbot mitten im Umbau und laesst die Seite halb um.
IP_HIER=$(curl -s -4 --max-time 10 https://api.ipify.org || echo '')
IP_DOMAIN=$(getent ahostsv4 "$DOMAIN" | awk 'NR==1{print $1}' || echo '')
echo "  dieser Server : ${IP_HIER:-unbekannt}"
echo "  $DOMAIN zeigt auf : ${IP_DOMAIN:-nichts}"
if [ -n "$IP_HIER" ] && [ "$IP_DOMAIN" != "$IP_HIER" ]; then
    echo >&2
    echo "ABBRUCH: die Domain zeigt noch nicht auf diesen Server." >&2
    echo "Erst den A-Record auf $IP_HIER setzen und warten, bis er sich ausgebreitet hat." >&2
    exit 1
fi

STAMP=$(date +%F-%H%M%S)
cp -p /etc/nginx/sites-available/atelier "/root/atelier-nginx-yedek-$STAMP"
cp -p /var/www/atelier/config.php "/root/atelier-config-yedek-$STAMP"
echo "  Sicherungen: /root/atelier-*-yedek-$STAMP"

# ---------------------------------------------------------------- 1. Domain
# Zuerst nur Port 80: certbot braucht ihn fuer seine Probe und schreibt den
# 443-Block danach selbst dazu.
cat > /etc/nginx/sites-available/atelier-domain <<NGINX
# Atelier Lumière - die richtige Adresse.
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;

    root /var/www/atelier/public;
    index index.php;
    client_max_body_size 120M;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Ersatz fuer public/uploads/.htaccess - nginx liest kein .htaccess.
    location ^~ /uploads/ {
        location ~ \.php\$ { return 403; }
    }

    location ~ /\. { deny all; }
}
NGINX

ln -sf /etc/nginx/sites-available/atelier-domain /etc/nginx/sites-enabled/atelier-domain
nginx -t
systemctl reload nginx
echo "  Domain-Block steht (noch ohne Zertifikat)."

# ------------------------------------------------------------ 2. Zertifikat
# Nur die neue Domain. Ein nacktes "certbot --nginx" fasst auch die
# Zertifikate von gidonla.com an.
certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos --redirect --keep-until-expiring
echo "  Zertifikat da."

# -------------------------------------------------------- 3. Demo umleiten
# Der bisherige Block bleibt als Datei erhalten (Sicherung oben), sein Inhalt
# wird zur Weiterleitung. Die Zertifikatszeilen der Demo bleiben stehen -
# ohne sie kann sie das https gar nicht erst annehmen, das sie umleiten soll.
cat > /etc/nginx/sites-available/atelier <<NGINX
# Die alte Demo-Adresse. Sie liefert nichts mehr aus, sie zeigt nur den Weg.
#
# 301 und nicht 404: was Google unter dieser Adresse gelernt hat, soll der
# neuen Domain zugutekommen statt weggeworfen zu werden.
server {
    listen 443 ssl;
    server_name 45.147.46.177 45-147-46-177.sslip.io;

    ssl_certificate /etc/letsencrypt/live/45-147-46-177.sslip.io/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/45-147-46-177.sslip.io/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    return 301 https://$DOMAIN\$request_uri;
}

server {
    listen 80;
    server_name 45.147.46.177 45-147-46-177.sslip.io;
    return 301 https://$DOMAIN\$request_uri;
}
NGINX

nginx -t
systemctl reload nginx
echo "  Demo-Adresse leitet weiter."

# ----------------------------------------------------------- 4. config.php
# Ohne diese Zeile stehen in Einladungslinks, OG-Bildern, E-Mails und
# PayPal-Rueckwegen weiter die alte Adresse - und die leitet dann im Kreis.
sed -i "s|^\(\s*.site_url.\s*=>\s*\).*,|\1'https://$DOMAIN',|" /var/www/atelier/config.php
php -l /var/www/atelier/config.php >/dev/null
grep -n "site_url" /var/www/atelier/config.php | sed 's/^/  /'
systemctl reload php8.3-fpm

echo
echo "Fertig. Zur Kontrolle:"
echo "  curl -sI https://45-147-46-177.sslip.io/de | head -2      # 301 auf $DOMAIN"
echo "  curl -s  https://$DOMAIN/robots.txt | tail -2             # Sitemap zeigt auf $DOMAIN"
echo "  curl -s  https://$DOMAIN/de | grep -o 'rel=\"canonical\"[^>]*'"
echo
echo "Danach in der Search Console die neue Domain anmelden und die Sitemap"
echo "einreichen - das beschleunigt den Umzug im Index."
