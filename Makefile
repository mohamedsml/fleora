# Raccourcis de développement local.
# Docker ne sert qu'au développement : la production tourne sur PHP/MariaDB
# fournis par Hostinger, sans conteneur.

DC := docker compose -f docker/compose.yaml

.PHONY: help up down restart logs shell mysql migrate fresh test build ps env lien

help: ## Affiche cette aide
	@grep -E '^[a-z-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

# Compose lit automatiquement le .env placé à côté du fichier compose.
# On y écrit l'UID/GID de l'hôte plutôt que de les passer en préfixe de
# commande : `UID=...` échoue silencieusement, UID étant en lecture seule
# dans bash.
env:
	@printf 'HOST_UID=%s\nHOST_GID=%s\n' "$$(id -u)" "$$(id -g)" > docker/.env

up: env ## Démarre l'environnement (app + base)
	$(DC) up -d
	@$(MAKE) --no-print-directory lien
	@echo "→ http://localhost:8000    (admin : /admin)"

lien: ## (Re)crée le lien public/storage vers les médias
	@# Lien RELATIF : `artisan storage:link` depuis le conteneur écrirait
	@# /app/storage/..., un chemin qui n'existe pas sur la machine hôte.
	@rm -f public/storage
	@ln -s ../storage/app/public public/storage
	@echo "public/storage → $$(readlink public/storage)"

down: ## Arrête l'environnement
	$(DC) down

restart: ## Redémarre l'application
	$(DC) restart app

ps: ## Liste les conteneurs du projet
	$(DC) ps

logs: ## Suit les logs de l'application
	$(DC) logs -f app

shell: ## Ouvre un shell dans le conteneur applicatif
	$(DC) exec app bash

mysql: ## Ouvre un client MariaDB sur la base
	$(DC) exec db mariadb -uroot -pdev fleora

migrate: ## Applique les migrations
	$(DC) exec app php artisan migrate

fresh: ## Recrée la base et rejoue les seeds (DESTRUCTIF)
	$(DC) exec app php artisan migrate:fresh --seed

test: ## Lance la suite de tests
	$(DC) exec app php artisan test

build: env ## Reconstruit l'image PHP locale
	$(DC) build --no-cache
