CURRENT_UID := $(shell id -u)
CURRENT_GID := $(shell id -g)
DC := PROJECTPORTPREFIX=39 DEV_UID=$(CURRENT_UID) DEV_GID=$(CURRENT_GID) docker-compose -p gobelins -f provisioning/dev/docker-compose.yml

.PHONY: create
create:
	@$(DC) build --no-cache
	@$(DC) up -d
	sleep 5
	@$(DC) exec db psql --dbname=gobelins -U gobelins -f /mn.lab.database.dump/gobelins.dump
	@$(DC) exec -u www-data engine ./project/provisioning/scripts/post-create.bash

	provisioning/dev/db/mn.lab.database.dump/gobelins.dump

.PHONY: update-engine
update-engine:
	@$(DC) build --no-cache  engine
	@$(DC) up -d engine

.PHONY: import-db
import-db:
	@$(DC) exec db pg_restore --dbname=gobelins -U gobelins  --clean --no-acl --no-owner /mn.lab.database.dump

.PHONY: start
start:
	@$(DC) start

.PHONY: stop
stop:
	@$(DC) stop

.PHONY: logs
logs:
	@$(DC) logs -f --tail=100

.PHONY: enter
enter:
	@$(DC) exec -u www-data engine /bin/bash

.PHONY: clean
clean:
	@$(DC) down -v --remove-orphans

.PHONY: ps
ps:
	@echo $(DC)
	@$(DC) ps
