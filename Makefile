# Backend Makefile — делегирует в корневой Makefile
# Все Docker-команды теперь в ../Makefile

.PHONY: up stop down bash artisan test lint lint-fix phpstan

up:
	$(MAKE) -C .. up

stop:
	$(MAKE) -C .. stop

down:
	$(MAKE) -C .. down

bash:
	$(MAKE) -C .. bash

artisan:
	$(MAKE) -C .. artisan c="$(c)"

test:
	$(MAKE) -C .. test

lint:
	$(MAKE) -C .. lint

lint-fix:
	$(MAKE) -C .. lint-fix

phpstan:
	$(MAKE) -C .. phpstan
