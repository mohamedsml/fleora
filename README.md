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

PHP 8.3 local n'a pas `pdo_mysql` : le développement passe par deux conteneurs
(application + base) qui reproduisent l'environnement de l'hébergeur. La
production, elle, n'utilise pas Docker — Hostinger fournit PHP et MariaDB.

```bash
cp .env.example .env

make up        # construit l'image, démarre app + base, attend que la base soit prête
make migrate   # applique le schéma
make test      # 31 tests

# → site   http://localhost:8000
# → admin  http://localhost:8000/admin
```

`make` sans argument liste toutes les cibles disponibles.

| Commande | Effet |
|---|---|
| `make up` / `make down` | Démarre / arrête l'environnement |
| `make logs` | Suit les logs applicatifs |
| `make shell` | Ouvre un shell dans le conteneur PHP |
| `make mysql` | Client MariaDB sur la base |
| `make migrate` | Applique les migrations |
| `make fresh` | Recrée la base + seeds (**destructif**) |
| `make test` | Suite de tests |
| `make build` | Reconstruit l'image PHP |

Tout est décrit dans [`docker/compose.yaml`](docker/compose.yaml) : la base
attend d'être `healthy` avant que l'application démarre, et les fichiers sont
écrits avec ton UID plutôt qu'en root.

## Tests

Les tests tournent sur **MariaDB**, pas sur SQLite : SQLite ignore
silencieusement `lockForUpdate` et tolère des types que MariaDB refuse — des
tests verts y masqueraient des bugs de production.

La base `fleora_test` est créée automatiquement au premier démarrage par
[`docker/init/01-test-db.sql`](docker/init/01-test-db.sql).

```bash
make test
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
