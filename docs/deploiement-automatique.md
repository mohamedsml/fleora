# Déploiement semi-automatique

Le déploiement se déclenche **d'un clic dans GitHub**, jamais automatiquement
sur un push. Vous poussez sur `main` quand vous voulez ; le site ne change que
lorsque vous le décidez.

```
git push origin main
        │
        │   (rien ne se passe — c'est voulu)
        ▼
GitHub → Actions → « Déployer sur Hostinger » → Run workflow
        │
        ├─ 1. Vérifications : Pint, 31 tests, assets présents
        │      ↳ si ça échoue, le serveur n'est PAS touché
        │
        └─ 2. SSH vers Hostinger → deploy/deploy.sh
               ↳ sauvegarde base · maintenance · git pull
                 composer · migrations · caches · contrôle de santé
```

Ce document couvre la **mise en place, à faire une seule fois**. Pour la
première installation manuelle du site sur le serveur, voir
[`deploiement.md`](deploiement.md) — à faire **avant** ce qui suit.

---

## Étape 1 — Créer le sous-domaine

hPanel → **Sites web → Sous-domaines** :

| Champ | Valeur |
|---|---|
| Domaine | `multiweb.ca` |
| Sous-domaine | `fleora` |

Le dossier `~/domains/fleora.multiweb.ca/` est créé automatiquement.

Activez ensuite le **SSL** : hPanel → Sécurité → SSL → installer le certificat
gratuit sur `fleora.multiweb.ca`.

---

## Étape 2 — Créer la base de données

hPanel → **Bases de données → Gestion des bases MySQL** :

- base : `uXXXXXX_fleora`
- utilisateur : `uXXXXXX_fleora` avec un **mot de passe long et unique**
- tous les privilèges sur cette base

Notez les quatre valeurs — elles vont dans le `.env` de l'étape 4.

---

## Étape 3 — Installer le site (une seule fois, à la main)

```bash
ssh -p 65002 uXXXXXX@votre-serveur.hostinger.com

cd ~/domains/fleora.multiweb.ca
git clone https://github.com/mohamedsml/fleora.git fleora
cd fleora

composer install --no-dev --optimize-autoloader
```

> **Si `composer` est introuvable**, utilisez le chemin complet fourni par
> Hostinger (souvent `/usr/local/bin/composer`) ou installez-le localement :
> `curl -sS https://getcomposer.org/installer | php` puis `php composer.phar`.

---

## Étape 4 — Configurer `.env`

```bash
cp .env.example .env
nano .env
```

```dotenv
APP_NAME=Fleora
APP_ENV=production
APP_DEBUG=false                          # ⚠️ JAMAIS true en production
APP_URL=https://fleora.multiweb.ca

APP_TIMEZONE=America/Toronto
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXX_fleora
DB_USERNAME=uXXXXXX_fleora
DB_PASSWORD=«mot de passe de l'étape 2»

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Puis :

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link     # rend les photos téléversées accessibles au web
php artisan optimize
```

Créez votre compte administrateur :

```bash
php artisan tinker --execute="
App\Models\User::create([
  'name' => 'Mohamed',
  'email' => 'votre@courriel.ca',
  'password' => bcrypt('«mot de passe long et unique»'),
]);"
```

---

## Étape 5 — Exposer `public/` au web

**L'étape la plus importante du document.**

Seul le dossier `public/` de Laravel doit être accessible par le web. Tout ce
qui est au-dessus — `.env` (mot de passe de la base), `app/`, `config/`,
`vendor/` — doit rester hors de portée.

> **Sur ce plan Hostinger, l'option « Racine du document » n'existe pas** :
> `Avancé` ne propose que l'accès SSH, PHP, DNS, cron et Git. La racine est
> figée sur `public_html`. On utilise donc un lien symbolique — vérifié
> fonctionnel sur ce compte (Apache suit les liens).

```bash
cd ~/domains/fleora.multiweb.ca

# Le default.php d'Hostinger n'a plus d'utilité — on le met de côté
mv public_html/default.php ~/default.php.bak 2>/dev/null

rmdir public_html && ln -s fleora/public public_html
ls -la          # → public_html -> fleora/public
```

Résultat : le web ne voit que `fleora/public`, exactement comme si la racine
avait été déplacée. **Aucune modification du code**, et `git pull` continue de
fonctionner normalement.

**Vérifiez immédiatement après :**

```bash
curl -I https://fleora.multiweb.ca/.env      # doit répondre 403 ou 404, JAMAIS 200
curl -I https://fleora.multiweb.ca           # doit répondre 200
```

> Si `.env` répond 200, arrêtez tout : le mot de passe de votre base est
> public. Vérifiez que `public_html` est bien un lien (`ls -la`), et non un
> dossier contenant le projet.

<details>
<summary>Si les liens symboliques étaient refusés (autre hébergeur)</summary>

Placer le projet dans `public_html/fleora/` et créer
`public_html/.htaccess` :

```apache
RewriteEngine On
RewriteRule ^(.*)$ /fleora/public/$1 [L]

# L'application est sous la racine web : il faut bloquer explicitement
RedirectMatch 404 ^/fleora/(?!public/).*$
```

Moins net que le lien symbolique — l'application reste techniquement
accessible et dépend de règles de blocage. À n'utiliser qu'en dernier recours,
avec la vérification `curl` ci-dessus.
</details>

---

## Étape 6 — Clé SSH pour GitHub

GitHub a besoin de sa **propre clé**, distincte de celle que vous utilisez.
Ainsi vous pouvez la révoquer sans perdre votre accès.

**Sur votre machine :**

```bash
ssh-keygen -t ed25519 -C "github-actions-fleora" -f ~/.ssh/fleora_deploy -N ""
```

Deux fichiers sont créés :
- `~/.ssh/fleora_deploy.pub` → à installer sur le serveur
- `~/.ssh/fleora_deploy` → à copier dans GitHub (**ne jamais commiter**)

**Autoriser la clé sur Hostinger** — deux méthodes :

*Via hPanel* : Avancé → Accès SSH → Clés SSH → coller le contenu de
`fleora_deploy.pub`.

*Via SSH* :

```bash
ssh -p 65002 uXXXXXX@votre-serveur.hostinger.com \
  "mkdir -p ~/.ssh && chmod 700 ~/.ssh && \
   cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys" \
  < ~/.ssh/fleora_deploy.pub
```

**Vérifier que la clé fonctionne :**

```bash
ssh -i ~/.ssh/fleora_deploy -p 65002 uXXXXXX@votre-serveur.hostinger.com "echo OK"
```

Si `OK` s'affiche sans demander de mot de passe, c'est bon.

---

## Étape 7 — Secrets GitHub

GitHub → dépôt **fleora** → Settings → Secrets and variables → Actions →
**New repository secret**. Cinq secrets :

| Nom | Valeur | Où la trouver |
|---|---|---|
| `HOSTINGER_HOST` | `votre-serveur.hostinger.com` | hPanel → Accès SSH |
| `HOSTINGER_USER` | `uXXXXXX` | hPanel → Accès SSH |
| `HOSTINGER_PORT` | `65002` | hPanel → Accès SSH (rarement 22) |
| `HOSTINGER_SSH_KEY` | contenu de `~/.ssh/fleora_deploy` | clé **privée**, en entier |
| `HOSTINGER_PATH` | `domains/fleora.multiweb.ca/fleora` | chemin relatif au home |

> Pour `HOSTINGER_SSH_KEY`, copiez le fichier **entier**, y compris
> `-----BEGIN OPENSSH PRIVATE KEY-----` et la ligne de fin :
> ```bash
> cat ~/.ssh/fleora_deploy
> ```

---

## Étape 8 — Protéger l'environnement *(recommandé)*

GitHub → Settings → **Environments** → `Hostinger Fleora` → cocher
**Required reviewers** et vous ajouter.

Effet : chaque déploiement demande une approbation explicite dans l'interface.
Un second garde-fou en plus de la confirmation écrite.

> ⚠️ **Le nom de l'environnement doit correspondre exactement.** Le workflow
> déclare `environment: name: Hostinger Fleora` ; un secret rangé dans un
> environnement au nom différent est **invisible** pour le job, qui échoue avec
> « secrets manquants » alors que les secrets existent bel et bien. Si vous
> renommez l'environnement, mettez à jour `.github/workflows/deploy.yml`.
>
> Les secrets peuvent aussi être placés en **Repository secrets** (visibles par
> tous les workflows du dépôt, sans condition d'environnement) — mais les
> secrets d'environnement sont préférables : ils permettent l'approbation
> obligatoire ci-dessus.

---

## Déployer

1. GitHub → onglet **Actions**
2. « Déployer sur Hostinger » dans la colonne de gauche
3. **Run workflow** → taper `deployer` → lancer

Le workflow vérifie d'abord (style, 31 tests, assets). **Si une vérification
échoue, le serveur n'est pas touché.**

---

## Avant chaque déploiement

**Si vous avez modifié du CSS ou du JS :**

```bash
npm run build
git add public/build && git commit -m "Recompile les assets"
git push
```

Le serveur n'a pas Node : les assets sont compilés en local et versionnés.
Laravel ne lit que `public/build/manifest.json` — il ignore que vos sources ont
changé. Le workflow refuse de déployer si le manifest est absent, mais il ne
peut pas savoir s'il est **périmé**.

---

## Ce que fait `deploy/deploy.sh` sur le serveur

| Étape | Détail |
|---|---|
| Garde-fous | `.env` présent · `APP_KEY` renseignée · `APP_DEBUG` à `false` |
| Sauvegarde | `mysqldump` compressé dans `~/backups/fleora/`, rotation 14 jours |
| Maintenance | `artisan down` — les visiteurs voient une page d'attente |
| Code | `git merge --ff-only` — refuse de fusionner si le serveur a divergé |
| Dépendances | `composer install --no-dev` (seulement si le code a changé) |
| Base | `migrate --force` |
| Caches | `optimize:clear` puis `optimize` |
| Assets | échec si `manifest.json` manque |
| Retour en ligne | `artisan up`, même en cas d'erreur (`trap`) |
| Santé | `curl` sur le site — échec si autre chose que 200 |

---

## En cas de problème

**Le workflow échoue aux vérifications** — rien n'a touché le serveur.
Corrigez en local, poussez, relancez.

**Le workflow échoue à la connexion SSH** — vérifiez les cinq secrets, surtout
le port (`65002`) et la clé privée copiée en entier.

**Le site affiche une erreur 500 après déploiement :**

```bash
ssh -p 65002 uXXXXXX@votre-serveur.hostinger.com
cd ~/domains/fleora.multiweb.ca/fleora
tail -50 storage/logs/laravel.log
```

**Revenir au commit précédent :**

```bash
cd ~/domains/fleora.multiweb.ca/fleora
git log --oneline -5
git reset --hard <commit-précédent>
./deploy/deploy.sh
```

**Restaurer la base :**

```bash
ls -lt ~/backups/fleora/
gunzip < ~/backups/fleora/db-AAAA-MM-JJ_HHMMSS.sql.gz | \
  mysql -u uXXXXXX_fleora -p uXXXXXX_fleora
```

> Une sauvegarde jamais restaurée n'est pas une sauvegarde. Faites l'essai une
> fois, sur une base jetable, pendant que tout va bien.

**Le site reste bloqué en maintenance :**

```bash
php artisan up
```

---

## Cron du planificateur

Une seule fois, hPanel → Avancé → **Tâches cron**.

**Choisir « Personnalisé », pas « PHP ».** Le mode PHP impose `/usr/bin/php`
(version 8.2) et n'accepte qu'un chemin de fichier, pas une commande Artisan.

Commande :

```
cd /home/u663068008/domains/fleora.multiweb.ca/fleora && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Fréquence : `*` dans les cinq champs (chaque minute). Laravel décide ensuite
lui-même quelles tâches exécuter.

> ⚠️ **Deux pièges, tous deux vérifiés sur ce serveur.**
>
> **Le chemin de PHP.** `php` tout court donne **8.2.33** en contexte non
> interactif (cron, SSH automatisé), alors que le shell interactif donne
> 8.3.33. Le projet exige 8.3 : il faut le chemin complet
> `/opt/alt/php83/usr/bin/php`. C'est la même cause qui a fait échouer les
> premiers déploiements GitHub Actions.
>
> **Le chemin du projet.** Écrire `/home/u663068008/domains/...` en absolu :
> `~` n'est pas toujours interprété par cron.

Le planificateur portera les sauvegardes quotidiennes, les purges Loi 25 et les
rappels d'événements approchants. Tant qu'aucune tâche n'est déclarée dans
`routes/console.php`, il ne fait rien — c'est normal.

Vérifier ce qui est planifié :

```bash
php artisan schedule:list
```
