# Variables
COMPOSE_FILE ?= dev.yaml
UTILS_FILE ?= utils.yaml

# Phony Targets
.PHONY: run stop build up down restart logs status test clean prune migrate help migrate-update

# Load .env file
ifneq (,$(wildcard ./.env))
    include .env
    export
endif

# Targets
serve: up

stop: down

build:
	docker compose -f $(COMPOSE_FILE) build

up:
	docker compose -f $(COMPOSE_FILE) up -d

down:
	docker compose -f $(COMPOSE_FILE) down

restart:
	docker compose -f $(COMPOSE_FILE) restart

logs:
	docker compose -f $(COMPOSE_FILE) logs -f

status:
	docker compose -f $(COMPOSE_FILE) ps

test:
	docker compose -f $(UTILS_FILE) run --rm tests

composer-install:
	docker compose -f $(UTILS_FILE) run --rm composer install

composer-update:
	docker compose -f $(UTILS_FILE) run --rm composer update

clean:
	docker compose -f $(COMPOSE_FILE) down -v
	docker system prune -f
	docker volume prune -f

migrate:
	docker exec -i mysql mariadb -u"root" -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < ./sql/init.sql
	
help:
	@echo "Available commands:"
	@echo "  make serve    - Start the environment (default: dev)"
	@echo "  make stop     - Stop the environment"
	@echo "  make build    - Build the Docker images"
	@echo "  make up       - Start the environment in detached mode"
	@echo "  make down     - Stop the environment"
	@echo "  make restart  - Restart the environment"
	@echo "  make logs     - Follow logs for the environment"
	@echo "  make status   - Show status of containers"
	@echo "  make test     - Run tests inside the container"
	@echo "  make clean    - Remove containers, volumes and Docker resources"
	@echo "  make composer-install - Install composer dependencies"
	@echo "  make composer-update  - Update composer dependencies"
	@echo "  make migrate  - Initialize the database"
	@echo "  make migrate-update - Run database migrations"
	@echo "You can specify the compose file with COMPOSE_FILE=<file>"
	@echo "You can specify the utils compose file with UTILS_FILE=<file>"


