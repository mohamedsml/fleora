# Déploiement sur Hostinger (hébergement mutualisé hPanel)

Ce document décrit la mise en ligne de **fleora-admin** (Laravel + Filament).

> **N'utilisez PAS l'écran « Vérifiez les paramètres de compilation » d'Hostinger.**
> Cet outil déploie des sites *statiques* (Vite, React, Vue) : il exécute
> `npm run build` et publie des fichiers HTML/JS. Laravel est une application
> PHP qui a besoin d'un interpréteur PHP et d'une base de données — ce build
> produirait un site vide. La procédure ci-dessous la remplace.

---

## 1. Ce qu'il faut vérifier AVANT de commencer

Dans hPanel, confirmez ces trois points. Si l'un manque, le déploiement échouera.

| Élément | Valeur attendue | Où le voir dans hPanel |
|---|---|---|
| Version PHP | **8.3 ou plus** | Avancé → Configuration PHP |
| Extensions PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `bcmath`, `fileinfo`, `curl`, `zip`, `gd` (ou `imagick`), `intl` | Avancé → Configuration PHP → Extensions PHP |
| Accès SSH | Activé | Avancé → Accès SSH |

**Sans accès SSH**, la procédure reste faisable mais devient pénible : il faut
téléverser `vendor/` (≈ 151 Mo, 16 896 fichiers) par FTP et lancer les
migrations autrement. Activez-le si l'offre le permet — la section 6 couvre le
cas contraire.

---

## 2. Créer la base de données

hPanel → **Bases de données → Gestion des bases MySQL** → créer :

- une base, par exemple `uXXXXXX_fleora`
- un utilisateur avec un **mot de passe long et unique**
- tous les privilèges de cet utilisateur sur cette base

Notez les quatre valeurs : **hôte** (souvent `localhost`), **nom de la base**,
**utilisateur**, **mot de passe**. Elles vont dans le `.env` à l'étape 4.

---

## 3. Envoyer le code

### Avec SSH (recommandé)

```bash
ssh -p 65002 uXXXXXX@votre-serveur.hostinger.com

cd ~/domains/votredomaine.ca
git clone https://github.com/mohamedsml/fleora.git app
cd app/fleora-admin

composer install --no-dev --optimize-autoloader
```

`--no-dev` écarte les paquets de développement : moins de fichiers, et aucun
outil de débogage exposé en production.

### Sans SSH

Voir la section 6.

---

## 4. Configurer l'environnement

```bash
cp .env.example .env
nano .env
```

Renseignez au minimum :

```dotenv
APP_NAME=Fleora
APP_ENV=production
APP_DEBUG=false          # ⚠️ JAMAIS true en production : APP_DEBUG affiche
                         # le code source et les identifiants de la base
                         # sur la page d'erreur, visible par n'importe qui.
APP_URL=https://votredomaine.ca

APP_TIMEZONE=America/Toronto
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXX_fleora
DB_USERNAME=uXXXXXX_fleora
DB_PASSWORD=«le mot de passe de l'étape 2»

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Taxes : à passer à true seulement une fois inscrit aux fichiers TPS/TVQ
# (obligatoire au-delà de 30 000 $ de revenus sur 12 mois glissants).
FLEORA_TAXES_INSCRIT=false
```

Puis générez la clé d'application et préparez la base :

```bash
php artisan key:generate     # écrit APP_KEY dans .env
php artisan migrate --force  # --force : migre sans confirmation interactive
php artisan storage:link     # rend les images téléversées accessibles au web
```

> **La clé d'application chiffre les sessions et les cookies.** Ne la
> réutilisez jamais entre développement et production, et ne la versionnez pas.
> La changer après coup déconnecte tout le monde et rend illisibles les données
> déjà chiffrées.

Créez enfin votre compte administrateur :

```bash
php artisan tinker --execute="
App\Models\User::create([
  'name' => 'Mohamed',
  'email' => 'votre@courriel.ca',
  'password' => bcrypt('«un mot de passe long et unique»'),
]);"
```

---

## 5. Pointer le domaine sur `public/`

**C'est le point le plus important de tout ce document.**

La racine web doit pointer sur `fleora-admin/public/`, et **jamais** sur la
racine du projet. Tout ce qui est au-dessus de `public/` doit rester
inaccessible depuis le web : `.env` (mots de passe de la base), le code des
modèles, les dépendances. Une racine mal placée expose ces fichiers à
quiconque connaît leur URL.

Dans hPanel : **Sites web → Gérer → Avancé → Racine du document**, et
indiquez :

```
domains/votredomaine.ca/app/fleora-admin/public
```

### Si hPanel ne permet pas de changer la racine

Certaines offres imposent `public_html`. Deux solutions, par ordre de
préférence :

**a) Lien symbolique** (propre, si SSH est disponible) :

```bash
rm -rf ~/public_html
ln -s ~/domains/votredomaine.ca/app/fleora-admin/public ~/public_html
```

**b) `.htaccess` de redirection** à la racine de `public_html` :

```apache
RewriteEngine On
RewriteRule ^(.*)$ app/fleora-admin/public/$1 [L]
```

Dans le cas (b), placez aussi ce `.htaccess` **à la racine du projet** pour
bloquer l'accès direct aux dossiers sensibles :

```apache
RewriteEngine On
RewriteRule ^(\.env|composer\.(json|lock)|artisan) - [F,L]
RewriteRule ^(app|bootstrap|config|database|resources|routes|storage|tests|vendor)/ - [F,L]
```

**Vérifiez ensuite** que `https://votredomaine.ca/.env` renvoie bien une
erreur 403 ou 404, et non le contenu du fichier. Si le fichier s'affiche,
arrêtez tout : vos identifiants de base de données sont exposés, changez-les
et corrigez la configuration avant d'aller plus loin.

---

## 6. Sans accès SSH

Le blocage est `vendor/` : 16 896 fichiers, que Composer génère normalement
sur le serveur.

1. **En local**, produisez le dossier de production :

   ```bash
   cd fleora-admin
   composer install --no-dev --optimize-autoloader
   ```

2. **Compressez** `fleora-admin/` en `.zip` — un seul fichier à transférer
   plutôt que 17 000.

3. hPanel → **Fichiers → Gestionnaire de fichiers** → téléversez le `.zip`
   puis **extrayez-le sur le serveur**. Un envoi FTP fichier par fichier
   prendrait des heures et échoue souvent en cours de route.

4. Créez `.env` directement dans le gestionnaire de fichiers (étape 4). Pour
   `APP_KEY`, générez-la en local avec `php artisan key:generate --show` et
   recopiez la valeur.

5. Pour les migrations, sans ligne de commande, deux options :
   - importer un dump SQL via **phpMyAdmin** (produit en local avec
     `mysqldump`) ;
   - ou activer temporairement une route protégée qui lance `migrate` — à
     supprimer immédiatement après.

> **Vérifiez le quota d'inodes** avant de téléverser : le nombre de fichiers
> est limité sur les offres mutualisées, et `vendor/` en consomme près de
> 17 000 à lui seul. hPanel affiche ce quota dans la section des statistiques.

---

## 7. Optimisations de production

À lancer après chaque déploiement :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

> Ces commandes figent la configuration en cache. **Après toute modification
> du `.env`, relancez-les** — sinon vos changements resteront sans effet, ce
> qui est une source classique d'heures perdues à déboguer.

Pour annuler : `php artisan optimize:clear`.

---

## 8. Mises à jour ultérieures

```bash
cd ~/domains/votredomaine.ca/app
git pull origin main
cd fleora-admin
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 9. Vérifications avant de considérer le site en ligne

- [ ] `https://votredomaine.ca/.env` renvoie 403 ou 404 — **jamais** son contenu
- [ ] `APP_DEBUG=false` dans le `.env` de production
- [ ] HTTPS actif (hPanel → SSL), et le site force la redirection vers HTTPS
- [ ] Le mot de passe administrateur est long, unique, et différent de celui de développement
- [ ] Une sauvegarde automatique de la base est configurée dans hPanel
- [ ] `php artisan migrate:status` montre toutes les migrations exécutées

---

## Rappel : ce qui reste à construire

Ce document déploie le **back-office** (`/admin`). Le **site public** n'existe
pas encore — l'accueil affiche la page Laravel par défaut. La stack du site
public devra être choisie en tenant compte d'une contrainte : un hébergement
mutualisé **n'exécute pas Node.js en continu**, ce qui exclut un Next.js en
rendu serveur. Trois options resteront ouvertes :

1. **Blade + Livewire** dans le même projet Laravel — aucun serveur
   supplémentaire, un seul déploiement, excellent pour le SEO ;
2. **Next.js en export statique** consommant l'API Laravel — à régénérer à
   chaque modification de contenu ;
3. **Next.js sur Vercel** (offre gratuite) pointant vers l'API Laravel
   hébergée ici — deux hébergements à gérer.

L'option 1 est la plus cohérente avec un mutualisé et avec le fait que vous
soyez seul à maintenir le projet.
