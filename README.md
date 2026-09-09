# Fleora

Site vitrine et back-office pour **Fleora** — boîtes décoratives personnalisées
pour événements (mariages, baby showers, baptêmes, anniversaires).
Région de Montréal, Laval et Rive-Nord.

**Stack** : Laravel 13 · Livewire 3 · Filament 4 · Tailwind 4 · Alpine 3 · MariaDB

| | URL |
|---|---|
| Site public | `/` |
| Back-office | `/admin` |

---

## Démarrer

PHP 8.3 local n'a pas `pdo_mysql` : le développement passe par un conteneur qui
reproduit l'environnement de l'hébergeur. La production, elle, n'utilise pas
Docker.

```bash
# 1. Image de dev (une seule fois)
docker build -f docker/Dockerfile.dev -t fleora-php:dev .
docker network create fleora-dev

# 2. Base de données
docker run -d --name fleora-mariadb-dev --network fleora-dev \
  -e MARIADB_ROOT_PASSWORD=dev -e MARIADB_DATABASE=fleora \
  -p 3307:3306 mariadb:11.8

# 3. Dépendances et configuration
docker run --rm -v "$PWD:/app" -w /app fleora-php:dev composer install
cp .env.example .env   # puis renseigner DB_* et lancer key:generate
npm install && npm run build

# 4. Schéma et données de démonstration
docker run --rm --network fleora-dev -v "$PWD:/app" -w /app fleora-php:dev \
  sh -c "php artisan migrate --force && php artisan db:seed --class=DemoSeeder --force"

# 5. Serveur → http://localhost:8000
docker run -d --name fleora-serve --network fleora-dev -p 8000:8000 \
  -v "$PWD:/app" -w /app fleora-php:dev \
  php artisan serve --host=0.0.0.0 --port=8000
```

## Tests

Les tests tournent sur **MariaDB**, pas sur SQLite : SQLite ignore
silencieusement `lockForUpdate` et tolère des types que MariaDB refuse — des
tests verts y masqueraient des bugs de production.

```bash
# Créer la base de test (une seule fois)
docker exec fleora-mariadb-dev mariadb -uroot -pdev \
  -e "CREATE DATABASE IF NOT EXISTS fleora_test;"

docker run --rm --network fleora-dev -v "$PWD:/app" -w /app \
  fleora-php:dev php artisan test
```

## Assets

`public/build/` est **versionné** : l'hébergement mutualisé n'exécute pas
Node.js, les assets sont compilés en local et déployés avec le code.

```bash
npm run dev      # développement
npm run build    # AVANT tout commit touchant au CSS, au JS ou aux vues
```

Oublier `npm run build` fait servir les anciens styles sans erreur visible.

---

## Documentation

- [docs/structure.md](docs/structure.md) — organisation des dossiers, conventions, où ajouter quoi
- [docs/deploiement.md](docs/deploiement.md) — mise en ligne sur Hostinger

## Notes métier

- **L'argent est stocké en cents, en entiers.** Jamais de flottant.
- **TPS 5 % / TVQ 9,975 %** modélisées ; `FLEORA_TAXES_INSCRIT` reste à `false`
  tant que l'entreprise n'est pas inscrite aux fichiers de taxes (obligatoire
  au-delà de 30 000 $ de revenus sur 12 mois glissants).
- **Loi 25 (Québec)** : les demandes de soumission contiennent des
  renseignements personnels. Consentement horodaté, conservation limitée à
  24 mois après le dernier contact, droit d'accès et de suppression sous 30 jours.
