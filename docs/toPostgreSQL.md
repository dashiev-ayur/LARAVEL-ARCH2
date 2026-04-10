# PostgreSQL в Docker (кратко)

Compose — файл `docker-compose.postgres.yml` рядом с проектом:

```yaml
services:
  postgres:
    image: postgres:16-alpine
    ports:
      - "5432:5432"
    environment:
      POSTGRES_USER: postgre
      POSTGRES_PASSWORD: postgre
      POSTGRES_DB: postgre
    volumes:
      - pgdata:/var/lib/postgresql/data
volumes:
  pgdata:
```

```bash
docker compose -f docker-compose.postgres.yml up -d
```

## Laravel (`.env`)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=postgre
DB_USERNAME=postgre
DB_PASSWORD=postgre
```

После смены драйвера: `php artisan migrate` (при необходимости `config:clear`). 
Сиды: `php artisan migrate --seed` (миграции + `DatabaseSeeder`) или только `php artisan db:seed`; конкретный класс — `php artisan db:seed --class=ИмяSeeder`.

## Управление

| Действие | Команда |
|----------|---------|
| Логи | `docker logs -f postgres` |
| Остановка | `docker stop postgres` |
| Запуск снова | `docker start postgres` |
| Консоль SQL | `docker exec -it postgres psql -U postgre -d postgre` |
| Удалить контейнер и данные (осторожно) | `docker rm -f postgres` и удалить volume, если использовали |

Образ `postgres:16-alpine` можно заменить на актуальный тег с [Docker Hub](https://hub.docker.com/_/postgres).
