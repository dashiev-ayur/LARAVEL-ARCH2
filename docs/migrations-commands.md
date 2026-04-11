# Команды Artisan для миграций (справка)

Все команды запускаются из корня проекта: `php artisan …`

## Создание файла миграции

| Команда | Назначение |
|--------|------------|
| `make:migration имя_миграции` | Новый класс в `database/migrations/` |
| `make:migration имя --create=таблица` | Заготовка под `Schema::create` |
| `make:migration имя --table=таблица` | Заготовка под `Schema::table` |

Путь по умолчанию можно переопределить: `--path=database/migrations/подпапка` (относительно корня проекта).

## Применение и откат

| Команда | Назначение |
|--------|------------|
| `migrate` | Выполнить все неприменённые миграции |
| `migrate --pretend` | Показать SQL без выполнения |
| `migrate --force` | Без подтверждения в `production` (осторожно) |
| `migrate --database=имя` | Другой connection из `config/database.php` |
| `migrate --path=путь/к/файлу_или_папке` | Только указанные миграции |
| `migrate:status` | Список миграций и факт применения |
| `migrate:rollback` | Откат последнего «пакета» миграций |
| `migrate:rollback --step=N` | Откатить N последних миграций |
| `migrate:reset` | Откатить все миграции (по одному `down`) |
| `migrate:refresh` | `reset` + снова `migrate` |
| `migrate:refresh --seed` | То же + `db:seed` |
| `migrate:fresh` | Удалить все таблицы и заново `migrate` |
| `migrate:fresh --seed` | То же + сиды |

`migrate:fresh` и `migrate:reset` разрушают данные в БД — только для dev/staging или когда это осознанно.

## Таблица учёта миграций

| Команда | Назначение |
|--------|------------|
| `migrate:install` | Создать таблицу `migrations`, если её ещё нет |

Обычно вызывается автоматически при первом `migrate`.

## Связка с сидами

Сиды не являются частью миграций, но часто идут вместе:

- После миграций: `migrate --seed` или `migrate:refresh --seed` / `migrate:fresh --seed`
- Отдельно: `db:seed`, `db:seed --class=ИмяSeeder`

## Полезные флаги (кратко)

- `--database=` — нестандартное подключение к БД.
- `--path=` — своя папка или один файл миграции.
- `--realpath` — вместе с `--path`: абсолютный путь к файлу/каталогу.
- `--force` — на production без интерактивного вопроса.

## Частые сценарии

1. **Локально поднять схему с нуля:** `php artisan migrate:fresh --seed`
2. **Проверить, что сгенерируется SQL:** `php artisan migrate --pretend`
3. **Откатить последнюю миграцию:** `php artisan migrate:rollback`
4. **Переиграть все миграции после правок в `down`/`up`:** `php artisan migrate:refresh`

Версии Laravel в проекте могут добавлять новые опции; актуальный список: `php artisan list migrate` и `php artisan migrate --help`.
