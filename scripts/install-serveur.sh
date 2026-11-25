#!/usr/bin/env bash
# Installation (ou mise a jour) standard du CMS CIDST sur un VPS Ubuntu 24.04 vierge.
# Logiciels 100 % libres : Nginx, PHP-FPM, MySQL, Certbot (Let's Encrypt).
#
# Usage, en root, apres avoir fait pointer le DNS du domaine sur le serveur :
#   DOMAINE=cidst.mg EMAIL_ADMIN=vous@exemple.mg bash scripts/install-serveur.sh
# Option : TAG=stable-2026-11-19-createsuperadmin (defaut : tag stable le plus recent).
# Relancer le script = mettre a jour vers le TAG demande. Si /tmp/cidst-build.tgz existe, il est
# installe comme public/build (c'est ce que fait scripts/deployer.ps1). [non verifie en reel]
set -euo pipefail

: "${DOMAINE:?Indiquez DOMAINE, ex. DOMAINE=cidst.mg}"
: "${EMAIL_ADMIN:?Indiquez EMAIL_ADMIN (alertes de renouvellement du certificat)}"
REPO="${REPO:-https://github.com/koaba/cms-cidst}"
APP_DIR=/var/www/cidst
DB_NAME=cidst
DB_USER=cidst
SECRETS=/root/cidst-install.txt

[ "$(id -u)" -eq 0 ] || { echo "Lancez ce script en root (sudo -i)."; exit 1; }
. /etc/os-release
[ "${VERSION_ID:-}" = "24.04" ] || { echo "Ubuntu 24.04 requis (trouve : ${PRETTY_NAME:-inconnu})."; exit 1; }

as_www() { sudo -u www-data env HOME=/var/www/.home COMPOSER_HOME=/var/www/.home/composer "$@"; }
artisan() { (cd "$APP_DIR" && as_www php artisan "$@"); }

echo "== 1/9 Paquets =="
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y nginx mysql-server git unzip composer ufw certbot python3-certbot-nginx poppler-utils \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-intl php8.3-bcmath

echo "== 2/9 Reglages PHP (images jointes, memoire) =="
for sapi in fpm cli; do
    cat > "/etc/php/8.3/$sapi/conf.d/99-cidst.ini" <<'INI'
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 20M
post_max_size = 25M
INI
done
systemctl restart php8.3-fpm

echo "== 3/9 Base de donnees =="
if [ -f "$SECRETS" ]; then
    DB_PASS="$(grep '^DB_PASSWORD=' "$SECRETS" | cut -d= -f2-)"
else
    DB_PASS="$(openssl rand -hex 16)"
    printf 'DB_DATABASE=%s\nDB_USERNAME=%s\nDB_PASSWORD=%s\n' "$DB_NAME" "$DB_USER" "$DB_PASS" > "$SECRETS"
    chmod 600 "$SECRETS"
fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "== 4/9 Code =="
mkdir -p /var/www/.home "$APP_DIR"
chown www-data:www-data /var/www/.home "$APP_DIR"
if [ ! -d "$APP_DIR/.git" ]; then
    as_www git clone "$REPO" "$APP_DIR"
fi
as_www git -C "$APP_DIR" fetch --tags --force origin
TAG="${TAG:-$(as_www git -C "$APP_DIR" tag -l 'stable-*' --sort=-creatordate | sed -n 1p)}"
[ -n "$TAG" ] || { echo "Aucun tag stable trouve : indiquez TAG=..."; exit 1; }
as_www git -C "$APP_DIR" checkout --force "$TAG"
echo "Version installee : $TAG"

# Build CSS/JS envoye par scripts/deployer.ps1 (public/build n'est pas dans Git)
if [ -f /tmp/cidst-build.tgz ]; then
    rm -rf "$APP_DIR/public/build"
    tar -xzf /tmp/cidst-build.tgz -C "$APP_DIR/public"
    chown -R www-data:www-data "$APP_DIR/public/build"
    rm -f /tmp/cidst-build.tgz
fi

echo "== 5/9 Dependances PHP =="
(cd "$APP_DIR" && as_www composer install --no-dev --optimize-autoloader --no-interaction)

echo "== 6/9 Fichier .env =="
if [ ! -f "$APP_DIR/.env" ]; then
    as_www cp "$APP_DIR/.env.production.example" "$APP_DIR/.env"
    sed -i \
        -e "s|^APP_URL=.*|APP_URL=https://$DOMAINE|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" \
        -e "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USER|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" \
        -e "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=contact@$DOMAINE|" \
        "$APP_DIR/.env"
    chmod 640 "$APP_DIR/.env"
fi
if grep -q '^APP_KEY=$' "$APP_DIR/.env"; then
    artisan key:generate --force
fi

echo "== 7/9 Base, liens et caches =="
artisan migrate --force
# CACHE_STORE=database : les reglages du site sont en cache sans fin ; apres une migration ils n'ont pas
# les nouvelles colonnes (passation #49, 4.3). Vider le cache a chaque installation ou mise a jour.
artisan cache:clear
artisan storage:link || true
artisan config:cache
artisan route:cache || echo "route:cache ignore (a verifier avec app:check-environment)."
artisan view:cache

echo "== 8/9 Nginx, cron, pare-feu =="
if [ ! -f /etc/nginx/sites-available/cidst ]; then
cat > /etc/nginx/sites-available/cidst <<'NGINX'
server {
    listen 80;
    server_name __DOMAINE__;
    root /var/www/cidst/public;
    index index.php;
    client_max_body_size 25M;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;

    # Fichiers Vite : le nom change a chaque build, cache longue duree sans risque.
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
NGINX
sed -i "s|__DOMAINE__|$DOMAINE|" /etc/nginx/sites-available/cidst
fi
ln -sf /etc/nginx/sites-available/cidst /etc/nginx/sites-enabled/cidst
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo '* * * * * www-data cd /var/www/cidst && php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/cidst
chmod 644 /etc/cron.d/cidst

# Port SSH reel (deployer.config.json accepte un port different de 22) : sinon le pare-feu coupe l'acces.
SSH_PORT="$(sshd -T 2>/dev/null | awk '/^port /{print $2; exit}')"
ufw allow "${SSH_PORT:-22}/tcp"
ufw allow 'Nginx Full'
ufw --force enable

echo "== 9/9 HTTPS (Let's Encrypt) =="
if [ ! -d "/etc/letsencrypt/live/$DOMAINE" ]; then
    certbot --nginx -d "$DOMAINE" --non-interactive --agree-tos -m "$EMAIL_ADMIN" --redirect \
        || echo "HTTPS non obtenu : verifiez que le DNS de $DOMAINE pointe sur ce serveur, puis relancez le script."
fi

echo
echo "== Termine. Reste a faire a la main =="
[ -d "$APP_DIR/public/build" ] || echo "- Envoyer public/build/ (npm run build en local) dans $APP_DIR/public/build/ puis : chown -R www-data:www-data $APP_DIR/public/build"
echo "- Courrier : renseigner MAIL_USERNAME et MAIL_PASSWORD dans $APP_DIR/.env, puis : sudo -u www-data php $APP_DIR/artisan config:cache"
echo "- Premier admin : cd $APP_DIR && sudo -u www-data php artisan app:create-super-admin vous@exemple.mg"
echo "- Controle : cd $APP_DIR && sudo -u www-data php artisan app:check-environment"
echo "- Toujours lancer artisan avec sudo -u www-data (sinon des fichiers de storage/ deviennent illisibles pour le site)."
echo "- Mot de passe de la base : $SECRETS (a conserver hors du serveur)."
