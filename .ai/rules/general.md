---
paths:
  - '**'
---

# General

## Команды PHP — только через Docker
PHP на хосте 8.3.9, а composer.lock требует >= 8.4.1: любые php-команды на хосте падают (platform check). Запуск artisan/phpstan/pint/test — только через `docker compose exec backend-app php ...` (см. Makefile в корне). MCP-сервер laravel-boost в .mcp.json — тоже через docker, не через хост-PHP.
