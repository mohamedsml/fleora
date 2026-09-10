#!/usr/bin/env bash
#
# Déploiement de Fleora sur Hostinger (hébergement mutualisé).
#
# Exécuté sur le SERVEUR, soit par GitHub Actions (workflow deploy.yml),
# soit à la main par SSH :
#     cd ~/domains/fleora.ca/fleora && ./deploy/deploy.sh
#
# Le script est idempotent : le relancer sans changement ne casse rien.

set -euo pipefail

# ── Repères ──────────────────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

BRANCHE="${DEPLOY_BRANCH:-main}"
# Hostinger expose deux PHP : celui du shell interactif (8.3) et celui des
# commandes non interactives comme SSH depuis GitHub Actions (8.2 par défaut).
# On cherche donc explicitement un PHP >= 8.3, requis par composer.json.
detecter_php() {
    [ -n "${PHP_BIN:-}" ] && { echo "$PHP_BIN"; return; }

    for candidat in \
        /opt/alt/php83/usr/bin/php \
        /usr/local/php83/bin/php \
        /opt/cpanel/ea-php83/root/usr/bin/php \
        php83 php8.3 php
    do
        if command -v "$candidat" >/dev/null 2>&1; then
            v="$("$candidat" -r 'echo PHP_MAJOR_VERSION*100+PHP_MINOR_VERSION;' 2>/dev/null || echo 0)"
            [ "$v" -ge 803 ] 2>/dev/null && { echo "$candidat"; return; }
        fi
    done

    echo "php"   # repli : le contrôle de version ci-dessous tranchera
}

PHP="$(detecter_php)"
COMPOSER="${COMPOSER_BIN:-composer}"

log()   { printf '\033[36m▸\033[0m %s\n' "$1"; }
ok()    { printf '\033[32m✓\033[0m %s\n' "$1"; }
fatal() { printf '\033[31m✗ %s\033[0m\n' "$1" >&2; exit 1; }

log "Déploiement dans $APP_DIR (branche $BRANCHE)"

# ── Version de PHP ───────────────────────────────────────────────────────
# Contrôlée AVANT le mode maintenance : un échec ici ne doit pas laisser le
# site à l'arrêt.
PHP_VERSION="$($PHP -r 'echo PHP_VERSION;' 2>/dev/null || echo inconnue)"
PHP_NUM="$($PHP -r 'echo PHP_MAJOR_VERSION*100+PHP_MINOR_VERSION;' 2>/dev/null || echo 0)"

if [ "$PHP_NUM" -lt 803 ] 2>/dev/null; then
    fatal "PHP $PHP_VERSION ($PHP) — la 8.3 minimum est requise.
       Hostinger sert une version différente en SSH non interactif.
       Chercher le bon binaire :  ls -d /opt/alt/php8*/usr/bin/php
       puis définir PHP_BIN dans le workflow (secret ou export)."
fi
ok "PHP $PHP_VERSION ($PHP)"

# Composer doit tourner avec le MÊME PHP qu'artisan. Sur Hostinger, l'exécutable
# `composer` utilise le PHP par défaut du système (8.2), pas celui du shell —
# il refuse alors d'installer des dépendances qui exigent 8.3.
#
# On ne devine pas la nature de l'exécutable (script, binaire, wrapper) : on
# vérifie la seule chose qui compte, la version de PHP que Composer voit.
if [ "$COMPOSER" = "composer" ]; then
    COMPOSER_PATH="$(command -v composer 2>/dev/null || true)"

    if [ -n "$COMPOSER_PATH" ]; then
        VU="$($COMPOSER_PATH --version 2>/dev/null | grep -oE 'PHP version [0-9]+\.[0-9]+' | grep -oE '[0-9]+\.[0-9]+' || echo '')"
        NUM="$(echo "${VU:-0}" | awk -F. '{print $1*100+$2}')"

        if [ "$NUM" -lt 803 ] 2>/dev/null; then
            # Composer tourne sur un PHP trop ancien : on l'exécute via le nôtre.
            log "Composer utilise PHP ${VU:-?} — bascule sur $PHP"
            COMPOSER="$PHP $COMPOSER_PATH"
        fi
    else
        # Pas de composer dans le PATH : le phar local est le repli habituel
        # sur mutualisé.
        for phar in composer.phar "$HOME/composer.phar" "$APP_DIR/composer.phar"; do
            [ -f "$phar" ] && { COMPOSER="$PHP $phar"; break; }
        done
    fi
fi

# Vérification finale : mieux vaut échouer ici, avant la maintenance.
COMPOSER_PHP="$($COMPOSER --version 2>/dev/null | grep -oE 'PHP version [0-9]+\.[0-9]+' || echo '')"
[ -n "$COMPOSER_PHP" ] && ok "Composer sur ${COMPOSER_PHP#PHP version }"

# ── Garde-fous ───────────────────────────────────────────────────────────
# Sans .env, artisan échouerait à mi-parcours en laissant le site cassé.
[ -f .env ] || fatal ".env absent. Première mise en ligne : suivre docs/deploiement.md."

grep -q '^APP_KEY=base64:' .env || fatal "APP_KEY vide. Lancer : php artisan key:generate"

if grep -qE '^APP_DEBUG=(true|1)' .env; then
    fatal "APP_DEBUG=true en production : la page d'erreur exposerait le code et les identifiants de la base."
fi

# Marqueurs de .env.example laissés en place : sans ça, l'échec surviendrait
# en pleine migration, après le passage en maintenance.
if grep -q 'A_REMPLACER' .env; then
    fatal ".env contient encore des marqueurs A_REMPLACER — renseigner les identifiants de la base (hPanel → Bases de données)."
fi

# « db » est le nom du service Docker Compose en développement ; il n'existe
# pas sur le serveur.
if grep -qE '^DB_HOST=db$' .env; then
    fatal "DB_HOST=db est la valeur de développement local. En production : DB_HOST=localhost"
fi

# Connexion automatique à l'admin : réservée au développement. Activée ici,
# elle ouvrirait le back-office — demandes clientes, devis, factures — sans
# authentification.
if grep -qiE '^FLEORA_AUTO_LOGIN=(true|1)' .env; then
    fatal "FLEORA_AUTO_LOGIN est activé : l'administration serait accessible sans mot de passe. Retirer cette ligne du .env du serveur."
fi

# ── Sauvegarde de la base avant migration ────────────────────────────────
# Une migration qui échoue à mi-chemin laisse le schéma dans un état
# intermédiaire. Le dump permet de revenir en arrière.
if [ "${SKIP_BACKUP:-0}" != "1" ]; then
    BACKUP_DIR="$HOME/backups/fleora"
    mkdir -p "$BACKUP_DIR"
    STAMP="$(date +%Y-%m-%d_%H%M%S)"

    DB_NAME="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'"'')"
    DB_USER="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'"'')"
    DB_PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'"'')"

    if command -v mysqldump >/dev/null 2>&1 && [ -n "$DB_NAME" ]; then
        log "Sauvegarde de la base…"
        mysqldump --no-tablespaces -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            2>/dev/null | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz" \
            && ok "Sauvegarde : $BACKUP_DIR/db-$STAMP.sql.gz" \
            || log "Sauvegarde impossible — le déploiement continue."
        # Rotation : 14 jours
        find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime +14 -delete 2>/dev/null || true
    fi
fi

# ── Mode maintenance ─────────────────────────────────────────────────────
# Évite qu'un visiteur tombe sur un état intermédiaire pendant la migration.
# Le `|| true` couvre le cas où l'app est déjà en maintenance.
log "Passage en maintenance…"
$PHP artisan down --render="errors::503" --retry=60 2>/dev/null || true

# Quoi qu'il arrive ensuite, on ressort de maintenance. Si artisan lui-même
# est cassé (mauvaise version de PHP, dépendances absentes), on retire le
# verrou à la main : le site doit revenir en ligne même quand artisan échoue.
sortir_maintenance() {
    $PHP artisan up >/dev/null 2>&1 || rm -f storage/framework/maintenance.php
}
trap sortir_maintenance EXIT

# ── Récupération du code ─────────────────────────────────────────────────
log "Récupération de la branche $BRANCHE…"
git fetch --quiet origin "$BRANCHE"

AVANT="$(git rev-parse HEAD)"
# --ff-only : refuse de fusionner. Si le serveur a divergé (modification
# locale d'urgence), on veut le savoir plutôt que créer un merge silencieux.
git merge --ff-only "origin/$BRANCHE" || fatal "Le dépôt local a divergé de origin/$BRANCHE. Résoudre à la main."
APRES="$(git rev-parse HEAD)"

if [ "$AVANT" = "$APRES" ]; then
    log "Aucun nouveau commit."
else
    ok "$(git rev-list --count "$AVANT..$APRES") commit(s) — $(git log -1 --format='%h %s')"
fi

# ── Dépendances PHP ──────────────────────────────────────────────────────
# --no-dev : ni PHPUnit ni outils de débogage en production.
#
# On compare composer.lock à l'empreinte du dernier install réussi, plutôt que
# de se fier au seul écart de commits : un `git pull` lancé à la main avant ce
# script laisse AVANT == APRES, Composer est sauté, et l'application référence
# une classe absente de vendor/ — erreur 500 sur tout le site.
EMPREINTE=".composer-lock-hash"
LOCK_ACTUEL="$(md5sum composer.lock 2>/dev/null | cut -d' ' -f1)"
LOCK_INSTALLE="$(cat "$EMPREINTE" 2>/dev/null || echo '')"

if [ ! -d vendor ] || [ "$LOCK_ACTUEL" != "$LOCK_INSTALLE" ]; then
    log "Installation des dépendances…"
    $COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    echo "$LOCK_ACTUEL" > "$EMPREINTE"
    ok "Dépendances à jour"
else
    log "Dépendances inchangées."
fi

# ── Base de données ──────────────────────────────────────────────────────
log "Migrations…"
$PHP artisan migrate --force --no-interaction
ok "Schéma à jour"

# Lien symbolique vers les médias téléversés.
# `-e` plutôt que `-L` : un lien peut exister tout en pointant nulle part
# (restauration de sauvegarde, copie de fichiers entre environnements). Les
# photos disparaîtraient du site sans qu'aucune erreur ne le signale.
if [ ! -e public/storage ]; then
    rm -f public/storage
    $PHP artisan storage:link --no-interaction || true
fi

# ── Caches ───────────────────────────────────────────────────────────────
# optimize:clear d'abord : un cache de config obsolète pointerait sur les
# anciennes valeurs du .env.
log "Reconstruction des caches…"
$PHP artisan optimize:clear --no-interaction
$PHP artisan optimize --no-interaction
$PHP artisan event:cache --no-interaction 2>/dev/null || true
ok "Caches reconstruits"

# ── Vérification des assets ──────────────────────────────────────────────
# Le serveur n'a pas Node : les assets sont compilés en local et versionnés.
# Un manifest absent = pages sans CSS.
if [ ! -f public/build/manifest.json ]; then
    fatal "public/build/manifest.json absent. Lancer 'npm run build' en local, puis commiter public/build/."
fi

# ── Sortie de maintenance ────────────────────────────────────────────────
trap - EXIT
$PHP artisan up
ok "Site en ligne"

# ── Contrôle de santé ────────────────────────────────────────────────────
if [ -n "${HEALTHCHECK_URL:-}" ]; then
    log "Vérification de $HEALTHCHECK_URL…"
    sleep 2
    CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$HEALTHCHECK_URL" || echo 000)"
    [ "$CODE" = "200" ] && ok "HTTP $CODE" || fatal "HTTP $CODE — vérifier storage/logs/laravel.log"

    # Le .env porte le mot de passe de la base. Sur mutualisé, la racine web
    # est un lien vers public/ ; si ce lien était remplacé par un dossier
    # (restauration, erreur de manipulation), tout le projet deviendrait
    # public. On le vérifie à chaque déploiement plutôt qu'une seule fois.
    ENV_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "${HEALTHCHECK_URL%/}/.env" || echo 000)"
    if [ "$ENV_CODE" = "200" ]; then
        fatal ".env est accessible publiquement à ${HEALTHCHECK_URL%/}/.env — CHANGER LES IDENTIFIANTS DE LA BASE puis corriger la racine web."
    fi
    ok ".env non accessible (HTTP $ENV_CODE)"
fi

ok "Déploiement terminé : $(git log -1 --format='%h %s')"
