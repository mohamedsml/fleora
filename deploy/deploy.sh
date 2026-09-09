#!/usr/bin/env bash
#
# Déploiement de Fleora sur Hostinger (hébergement mutualisé).
#
# Exécuté sur le SERVEUR, soit par GitHub Actions (workflow deploy.yml),
# soit à la main par SSH :
#     cd ~/domains/fleora.multiweb.ca/fleora && ./deploy/deploy.sh
#
# Le script est idempotent : le relancer sans changement ne casse rien.

set -euo pipefail

# ── Repères ──────────────────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

BRANCHE="${DEPLOY_BRANCH:-main}"
PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

log()   { printf '\033[36m▸\033[0m %s\n' "$1"; }
ok()    { printf '\033[32m✓\033[0m %s\n' "$1"; }
fatal() { printf '\033[31m✗ %s\033[0m\n' "$1" >&2; exit 1; }

log "Déploiement dans $APP_DIR (branche $BRANCHE)"

# ── Garde-fous ───────────────────────────────────────────────────────────
# Sans .env, artisan échouerait à mi-parcours en laissant le site cassé.
[ -f .env ] || fatal ".env absent. Première mise en ligne : suivre docs/deploiement.md."

grep -q '^APP_KEY=base64:' .env || fatal "APP_KEY vide. Lancer : php artisan key:generate"

if grep -qE '^APP_DEBUG=(true|1)' .env; then
    fatal "APP_DEBUG=true en production : la page d'erreur exposerait le code et les identifiants de la base."
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

# Quoi qu'il arrive ensuite, on ressort de maintenance.
trap '$PHP artisan up >/dev/null 2>&1 || true' EXIT

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
if [ "$AVANT" != "$APRES" ] || [ ! -d vendor ]; then
    log "Installation des dépendances…"
    $COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    ok "Dépendances à jour"
fi

# ── Base de données ──────────────────────────────────────────────────────
log "Migrations…"
$PHP artisan migrate --force --no-interaction
ok "Schéma à jour"

# Lien symbolique vers les médias téléversés (idempotent)
[ -L public/storage ] || $PHP artisan storage:link --no-interaction || true

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
