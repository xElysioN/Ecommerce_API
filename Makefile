DOCKER=docker
DOCKER_COMPOSE=$(DOCKER) compose $(DOCKER_PROFILE)
DOCKER_PHP_CONTAINER=php
DOCKER_ALL_PROFILES=--profile=debug
EXEC_PHP=$(DOCKER_COMPOSE) exec --user=www-data -T $(DOCKER_PHP_CONTAINER)
EXEC_PHP_WITH_TTY=$(DOCKER_COMPOSE) exec --user=www-data $(DOCKER_PHP_CONTAINER)
EXEC_REDIS=$(DOCKER_COMPOSE) exec --user=redis redis
EXEC_REDIS_CLI=$(EXEC_REDIS) redis-cli
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

redis-flushall: ## Remove all keys from all databases
	$(EXEC_REDIS_CLI) FLUSHALL
 
redis-cli: ## Connect to redis-cli in Redis container
	$(EXEC_REDIS_CLI)

redis-sh: ## Connect to Redis container
	$(EXEC_REDIS) sh

install: pull docker-compose.override.yaml start composer.json fixtures ## Install the project

debug-start: start ## Start all containers with debug profile

.PHONY: start stop down pull sh redis-flushall redis-cli redis-sh install 
.PHONY: debug-start

##
###----------------#
###    Doctrine    #
###----------------#
##

db-create: ## Creates the configured database.
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: ## Drops the configured database
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-validate: ## Validate the doctrine ORM mapping
	$(CONSOLE) doctrine:schema:validate

db-schema: ## Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata
	$(CONSOLE) doctrine:schema:update --force

db-diff: ## Creates a new migration based on database changes
	$(CONSOLE) make:migration

db-migrate: ## Execute a migration to a specified version or the latest available version.
	$(CONSOLE) doctrine:migrations:migrate --allow-no-migration --no-interaction --all-or-nothing

db-update: db-diff db-migrate ## Execute db-diff & db-migrate

db-fixtures: ## Load data fixtures to your database
	$(CONSOLE) hautelook:fixtures:load --no-interaction

fixtures: redis-flushall db-drop db-create db-migrate db-fixtures ## Reset database and load data fixtures to your database

.PHONY: db-create db-drop db-validate db-schema db-diff db-migrate db-update db-fixtures fixtures

##
###-------------#
###    Tests    #
###-------------#
##

test-unit: ## Run unit tests
	$(EXEC_PHP) bin/phpunit --no-extensions --testsuite unit

test-functional: fixtures ## Run functional tests
	$(EXEC_PHP) bin/phpunit --testsuite functional

test-smoke: fixtures ## Run smoke tests
	$(EXEC_PHP) bin/phpunit --no-extensions --testsuite smoke

test-debug: ## Run tests with debug group/tags
	$(EXEC_PHP) bin/phpunit -vvv --group debug

tests: test-smoke test-unit test-functional ## Execute all tests
tu: test-unit ## Alias for test-unit
tf: test-functional ## Alias for test-functional
ts: test-smoke ## Alias for test-smoke

.PHONY: test-unit tu test-functional tf test-debug test-smoke tests

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
	$(EXEC_PHP_WITH_TTY) ./vendor/bin/php-cs-fixer fix --diff --dry-run

fix-cs: ## Fix php code style
	$(EXEC_PHP_WITH_TTY) ./vendor/bin/php-cs-fixer fix

phpstan: ## Analyses source code
	$(EXEC_PHP_WITH_TTY) ./vendor/bin/phpstan analyse

qa: lint cs phpstan ## Run all Q&A tools

.PHONY: lint-config lint-container lint cs fix-cs phpstan qa

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

docker-compose.override.yaml: ## Create docker-compose.override.yaml
	cp docker-compose.override.yaml.dist docker-compose.override.yaml

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
