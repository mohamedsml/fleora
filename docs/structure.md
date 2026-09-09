# Organisation du projet

Fleora est **une seule application Laravel** : le site public et le back-office
partagent la même base de données, les mêmes modèles et le même déploiement.
La séparation se fait par URL, pas par projet.

| | URL | Où vit le code |
|---|---|---|
| Site public (front office) | `/` | `routes/web.php`, `resources/views/` |
| Back-office (Filament) | `/admin` | `app/Filament/` |

Le préfixe `/admin` est défini dans `app/Providers/Filament/AdminPanelProvider.php`.

---

## Arborescence

Laravel est à la racine — c'est la convention du framework, et ce qui permet à
`php artisan`, `composer` et `npm` de fonctionner sans changer de dossier. Les
fichiers qui ne sont pas l'application vivent dans trois dossiers dédiés.

```
Fleora/
│
├── app/
│   ├── Models/              Modèles Eloquent — PARTAGÉS front + back
│   ├── Enums/               Statuts métier (devis, commande, production…)
│   ├── Support/             Règles métier pures : Taxes, Numerotation
│   ├── Filament/            BACK-OFFICE : écrans d'administration
│   ├── Livewire/            FRONT : composants interactifs (formulaires)
│   └── Providers/
│
├── resources/
│   ├── views/
│   │   ├── pages/           FRONT : une vue par page publique
│   │   ├── components/
│   │   │   ├── site/        FRONT : en-tête, pied, cartes
│   │   │   └── ui/          FRONT : boutons, badges — réutilisables
│   │   └── livewire/        FRONT : vues des composants Livewire
│   ├── css/app.css          Design system (palette, typographie)
│   └── js/app.js            Alpine + animations au défilement
│
├── routes/web.php           FRONT : les routes publiques
├── database/migrations/     Schéma — PARTAGÉ
├── database/seeders/        Données de démonstration
├── lang/                    Traductions FR / EN
├── public/
│   └── build/               Assets compilés — VERSIONNÉS (voir plus bas)
├── tests/Feature/           Tests
│
├── docs/                    ← Documentation du projet
│   ├── structure.md         Ce fichier
│   └── deploiement.md       Mise en ligne sur Hostinger
│
├── docker/                  ← Environnement de développement local
│   └── Dockerfile.dev       Reproduit le PHP de l'hébergeur
│
└── deploy/                  ← Scripts de mise en ligne (à venir)
```

---

## Où ajouter quoi

**Une nouvelle page publique** : une vue dans `resources/views/pages/`, une
route dans `routes/web.php`. Si la page a de l'interactivité (formulaire,
filtres en direct), un composant Livewire dans `app/Livewire/` avec sa vue dans
`resources/views/livewire/`.

**Un nouvel écran d'administration** :

```bash
php artisan make:filament-resource NomDuModele --generate
```

La commande crée le dossier complet sous `app/Filament/Resources/`. Passez
ensuite les libellés en français et regroupez l'écran dans le menu via
`$navigationGroup`.

**Une nouvelle table** : `php artisan make:migration`, puis le modèle dans
`app/Models/`. Toute règle de calcul (argent, taxes, numérotation) va dans
`app/Support/` — pas dans le modèle, pour rester testable sans base de données.

**Un composant visuel réutilisé** : `resources/views/components/ui/` s'il est
générique (bouton, badge), `components/site/` s'il est propre à ce site
(en-tête, carte de création).

---

## Conventions du projet

**Nommage en français** pour tout ce qui est métier : colonnes de base
(`titre_fr`, `date_evenement`), méthodes (`fourchettePrix()`), variables. Le
framework reste en anglais (`app/Models`, `routes`, `public`). C'est cohérent
avec un projet dont le client, le contenu et la documentation sont francophones.

**L'argent en cents, en entiers.** Jamais de flottant : un float sur de
l'argent produit des écarts d'arrondi qui finissent par des factures fausses.
La conversion en dollars n'a lieu qu'à l'affichage.

**Bilinguisme par colonnes `_fr` / `_en`**, avec repli automatique sur le
français via le trait `HasTranslations`. Pas de table de traductions : pas de
jointure à chaque lecture, et deux onglets simples dans Filament.

**Les tests tournent sur MariaDB**, pas sur SQLite en mémoire (voir
`phpunit.xml`). SQLite ignore silencieusement `lockForUpdate` et tolère des
types que MariaDB refuse : des tests verts sur SQLite peuvent masquer un bug
qui casse en production.

---

## Les assets compilés

`public/build/` est **volontairement versionné**. L'hébergement mutualisé
n'exécute pas Node.js : les assets sont compilés en local et déployés avec le
code.

```bash
npm run dev      # développement, rechargement à chaud
npm run build    # AVANT chaque commit touchant au CSS, au JS ou aux vues
```

> Oublier `npm run build` fait servir les anciens styles **sans aucune erreur
> visible**. Laravel ne lit que `public/build/manifest.json` ; il ignore que
> vos sources ont changé.

---

## Développement local

PHP 8.3 est installé sur la machine mais sans `pdo_mysql`. Un conteneur
reproduit l'environnement de l'hébergeur — la production, elle, n'utilise pas
Docker.

```bash
# Construire l'image de dev (une seule fois)
docker build -f docker/Dockerfile.dev -t fleora-php:dev .

# Base de données
docker run -d --name fleora-mariadb-dev --network fleora-dev \
  -e MARIADB_ROOT_PASSWORD=dev -e MARIADB_DATABASE=fleora \
  -p 3307:3306 mariadb:11.8

# Serveur de développement → http://localhost:8000
docker run -d --name fleora-serve --network fleora-dev -p 8000:8000 \
  -v "$PWD:/app" -w /app fleora-php:dev \
  php artisan serve --host=0.0.0.0 --port=8000

# Tests (base fleora_test, à créer une fois)
docker run --rm --network fleora-dev -v "$PWD:/app" -w /app \
  fleora-php:dev php artisan test
```

Le back-office est sur `http://localhost:8000/admin`.

---

## Pourquoi cette stack

**Laravel + Livewire + Tailwind + Alpine**, sans framework JavaScript séparé.

Le rendu est fait côté serveur par Blade, ce qui donne l'avantage de
référencement recherché pour les requêtes locales. Une vue lit directement
`Creation::publie()->vedette()` : aucune API à écrire, à sécuriser, à versionner
ni à maintenir en cohérence.

Un front Next.js séparé aurait imposé, sur cet hébergement, soit un export
statique à régénérer à chaque modification de contenu — annulant l'intérêt du
back-office — soit un hébergement Vercel distinct, avec deux déploiements et de
la latence sur chaque appel. Le gain de référencement, lui, était déjà acquis.

Ce choix se révisera si un besoin réel apparaît : une interface à état complexe
(configurateur visuel en temps réel), une équipe front distincte, ou une
application mobile partageant la même API. Aucun n'est présent aujourd'hui.
