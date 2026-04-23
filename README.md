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

## Веб-страницы

Маршруты Inertia и Fortify (не путать с префиксом `api/`).

### Публичные

- `/` — главная (`welcome`).
- `/login`, `/register` — вход и регистрация (регистрация зависит от настроек Fortify).
- `/forgot-password`, `/reset-password/{token}` — восстановление пароля.

### Аутентификация и безопасность

- `/email/verify` — напоминание подтвердить email; подтверждение по ссылке из письма: `/email/verify/{id}/{hash}`.
- `/two-factor-challenge` — второй шаг входа при включённой 2FA.
- `/user/confirm-password` — подтверждение пароля для чувствительных действий.
- Экраны настройки 2FA (QR, секрет, коды восстановления) — маршруты Fortify с префиксом `/user/...` (см. `php artisan route:list`).

### После входа

- `/{current_team}/dashboard` — дашборд; `{current_team}` — **slug** текущей команды пользователя, маршрут требует `auth`, `verified` и членство в команде.
- `/{current_team}/{current_org}/posts` — страница `Записи` (текущая заглушка под CRUD для `posts`), также требует `auth`, `verified` и членство в команде.
- `/invitations/{invitation}/accept` — принятие приглашения в команду (требуется `auth`).

### Настройки (`auth`; часть маршрутов — ещё и `verified`)

- `/settings` — редирект на профиль.
- `/settings/profile`, `/settings/security`, `/settings/appearance` — профиль, безопасность (в т.ч. смена пароля), внешний вид.
- `/settings/teams`, `/settings/teams/{team}` — список команд и карточка команды (для `{team}` действий по участникам и приглашениям нужны права члена этой команды).

### Документация API в браузере

- `/api/documentation` — Swagger UI (L5-Swagger).
- `/docs` — сырой OpenAPI JSON (артефакт в `storage/api-docs/`). При `L5_SWAGGER_GENERATE_ALWAYS=true` в `.env` JSON пересобирается при запросе к `/docs`.

Перегенерация спецификации из атрибутов OpenAPI в коде (обновляет `storage/api-docs/api-docs.json`):

```bash
php artisan l5-swagger:generate
```

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
