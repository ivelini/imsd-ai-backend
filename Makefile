DOCKER_COMPOSE = docker compose
EXEC            = $(DOCKER_COMPOSE) exec app
ARTISAN         = $(EXEC) php artisan
PINT            = $(EXEC) ./vendor/bin/pint
PHPSTAN         = $(EXEC) ./vendor/bin/phpstan

.PHONY: up stop down build bash shell root cache-clear \
        lint lint-fix test phpstan docs

# --- Запуск / остановка ---

## Запустить все контейнеры (в фоне)
up:
	$(DOCKER_COMPOSE) up -d

## Остановить все контейнеры
stop:
	$(DOCKER_COMPOSE) stop

## Остановить и удалить контейнеры, сети, volumes
down:
	$(DOCKER_COMPOSE) down

## Пересобрать образы
build:
	$(DOCKER_COMPOSE) build

# --- Вход в контейнеры ---

## Bash в app-контейнере (php-fpm)
bash:
	$(EXEC) bash

## Bash в app-контейнере от root
root:
	$(DOCKER_COMPOSE) exec -u root app bash

## Bash внутри любого сервиса: make shell s=db
shell:
	$(DOCKER_COMPOSE) exec $(s) bash

# --- Laravel ---

## Сбросить весь кеш Laravel
cache-clear:
	$(ARTISAN) optimize:clear

## Запустить artisan-команду: make artisan c="migrate --seed"
artisan:
	$(ARTISAN) $(c)

# --- Качество кода ---

## Проверить стиль кода (Pint)
lint:
	$(PINT) --test

## Исправить стиль кода (Pint)
lint-fix:
	$(PINT)

## Статический анализ (PHPStan)
phpstan:
	$(PHPSTAN) analyse

## Запустить тесты
test:
	$(EXEC) php artisan config:clear --ansi
	$(EXEC) php artisan test

# --- Документация ---

## Сгенерировать документацию API (Scribe) + Postman + OpenAPI
docs:
	$(ARTISAN) scribe:generate
