# Laravel + React (Inertia)

## Первый запуск

Автоматическая установка зависимостей, `.env`, ключ приложения, миграции и сборка фронтенда:

```bash
composer run setup
```

Перед этим настройте подключение к БД в `.env` (или выполните `setup` после правки `.env` и при необходимости снова `php artisan migrate`).

## Режим разработки

Сервер приложения, Vite, воркер очереди и просмотр логов в одном терминале:

```bash
composer run dev
```

По умолчанию приложение: [http://127.0.0.1:8000](http://127.0.0.1:8000).

### Вручную (два терминала)

```bash
php artisan serve
```

```bash
npm run dev
```

## Очереди - запуск воркеров

```bash
php artisan queue:work --queue=orgs
```

## Сборка для продакшена

```bash
npm run build
```

При необходимости SSR:

```bash
npm run build:ssr
```

## Тесты

```bash
composer test
```

или

```bash
php artisan test
```
