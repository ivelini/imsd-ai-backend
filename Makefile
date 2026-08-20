# Backend Makefile — делегирует в корневой Makefile
# Все Docker-команды теперь в ../Makefile

.PHONY: up stop down build bash root shell artisan fresh cache-clear optimize-clear \
        test lint lint-fix phpstan help \
        admin-dev admin-build admin-install

up:
	$(MAKE) -C .. up

stop:
	$(MAKE) -C .. stop

ps:
	$(MAKE) -C .. ps

down:
	$(MAKE) -C .. down

build:
	$(MAKE) -C .. build

bash:
	$(MAKE) -C .. bash

root:
	$(MAKE) -C .. root

shell:
	$(MAKE) -C .. shell s="$(s)"

artisan:
	$(MAKE) -C .. artisan c="$(c)"

fresh:
	$(MAKE) -C .. fresh

cache-clear:
	$(MAKE) -C .. cache-clear

optimize-clear:
	$(MAKE) -C .. optimize-clear

test:
	$(MAKE) -C .. test

lint:
	$(MAKE) -C .. lint

lint-fix:
	$(MAKE) -C .. lint-fix

phpstan:
	$(MAKE) -C .. phpstan

help:
	$(MAKE) -C .. help

admin-dev:
	$(MAKE) -C .. admin-dev

admin-build:
	$(MAKE) -C .. admin-build

admin-install:
	$(MAKE) -C .. admin-install
