DOCKER=docker
DOCKER_COMPOSE=$(DOCKER) compose $(DOCKER_PROFILE)
DOCKER_PHP_CONTAINER=php
DOCKER_ALL_PROFILES=--profile=debug
EXEC_PHP=$(DOCKER_COMPOSE) exec --user=www-data -T $(DOCKER_PHP_CONTAINER)
EXEC_PHP_WITH_TTY=$(DOCKER_COMPOSE) exec --user=www-data $(DOCKER_PHP_CONTAINER)
CONSOLE=$(EXEC_PHP) bin/console
COMPOSER=$(EXEC_PHP) composer
DOCKERIZE=$(EXEC_PHP) dockerize

test-%: EXEC_PHP=$(DOCKER_COMPOSE) exec --env APP_ENV=test --user=www-data -T $(DOCKER_PHP_CONTAINER)
debug-%: DOCKER_PROFILE=--profile=debug

##
###--------------#
###    Docker    #
###--------------#
##

start: ## Start all containers
	$(DOCKER_COMPOSE) up -d
	$(DOCKERIZE) -wait tcp://database:3306 -timeout 60s

stop: ## Stop all containers
	$(DOCKER_COMPOSE) $(DOCKER_ALL_PROFILES) stop

down: ## Stops containers and removes containers, networks, volumes, and images created by `up`.
	$(DOCKER_COMPOSE) $(DOCKER_ALL_PROFILES) down --volumes

pull: ## Pull service images
	$(DOCKER_COMPOSE) pull

sh: ## Connect to php container
	$(EXEC_PHP_WITH_TTY) sh

install: pull docker-compose.override.yaml start composer.json ## Install the project

debug-start: start ## Start all containers with debug profile

.PHONY: start stop down sh install pull
.PHONY: debug-start

##
###-----------------#
###    Q&A tools    #
###-----------------#
##

lint-config: ## Lint yaml for config directory
	$(CONSOLE) lint:yaml config

lint-container: ## Ensures that arguments injected into services match type declarations
	$(CONSOLE) lint:container

lint: lint-config lint-container ## Lint twig and yaml files

cs: ## Check php code style
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix --diff --dry-run

fix-cs: ## Fix php code style
	$(EXEC_PHP) ./vendor/bin/php-cs-fixer fix

qa: lint cs ## Run all Q&A tools

.PHONY: lint-config lint-container lint cs fix-cs qa

##
###----------------#
###    Composer    #
###----------------#
##

composer-validate: ## Validates a composer.json and composer.lock.
	$(COMPOSER) validate

composer-update: ## Upgrades your dependencies to the latest version according to composer.json, and updates the composer.lock file
	$(COMPOSER) update $(package)

composer-require: ## Adds required packages to your composer.json and installs them
	$(COMPOSER) require $(package)

.PHONY: composer-validate composer-update composer-require

##
###----------------------------#
###    Rules based on files    #
###----------------------------#
##

vendor:	composer.lock ## Install dependencies
	$(COMPOSER) install

docker-compose.override.yml: ## Create docker-compose.override.yml
	cp docker-compose.override.yml.dist docker-compose.override.yml

##
###-------------#
###    Utils    #
###-------------#
##

cc:	## Clear cache
	$(CONSOLE) cache:clear

cc-rm: ## Clear cache by rm -rf
	rm -rf var/cache

.PHONY: cc cc-rm

##
###---------------------#
###    Help & Others    #
###---------------------#
##

.DEFAULT_GOAL := help

help: ## Display help messages from parent Makefile
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

.PHONY: help
