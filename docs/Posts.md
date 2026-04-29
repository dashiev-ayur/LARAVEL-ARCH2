# Записи для организаций

Универсальные типы записей: `page`, `news`, `article`, `product`.

## Структура таблиц

> Базовый вариант: записи и категории принадлежат организации через `org_id`.
> Если позже понадобится привязка к команде, лучше добавлять отдельную логику владения, а не дублировать `team_id` + `org_id` во всех таблицах.

### `posts`

Таблица записей.

| Поле | Тип | Null | Описание |
|---|---|---|---|
| `id` | `bigint unsigned` | Нет | Первичный ключ |
| `org_id` | `bigint unsigned` | Нет | Владелец записи (организация) |
| `author_id` | `bigint unsigned` | Да | Автор записи |
| `parent_id` | `bigint unsigned` | Да | Родитель для древовидной структуры |
| `type` | `string` | Нет | Тип записи: `news`, `article`, `page`, `product` |
| `status` | `string` | Нет | Статус: `draft`, `scheduled`, `published`, `archived` |
| `acl_resource` | `string` | Да | ACL-ресурс для ограничения доступа (например: `admin`, `news.admin`) |
| `slug` | `string` | Нет | ЧПУ-идентификатор |
| `title` | `string` | Нет | Заголовок |
| `excerpt` | `text` | Да | Краткое описание |
| `content` | `longText` | Да | Контент записи |
| `published_at` | `timestamp` | Да | Дата публикации |
| `created_at` | `timestamp` | Нет | Время создания |
| `updated_at` | `timestamp` | Нет | Время обновления |
| `deleted_at` | `timestamp` | Да | Soft delete |

Индексы и ограничения:

- `unique (org_id, type, slug)`
- `index (org_id, type, status, published_at)`
- `index (acl_resource)`
- `index (parent_id)`
- `foreign key (org_id) -> orgs.id`
- `foreign key (author_id) -> users.id`
- `foreign key (parent_id) -> posts.id`

### `categories`

Таблица категорий для типов записей.

| Поле | Тип | Null | Описание |
|---|---|---|---|
| `id` | `bigint unsigned` | Нет | Первичный ключ |
| `org_id` | `bigint unsigned` | Нет | Владелец категории (организация) |
| `parent_id` | `bigint unsigned` | Да | Родительская категория (дерево) |
| `type` | `string` | Нет | Тип записей, для которых используется категория |
| `acl_resource` | `string` | Да | ACL-ресурс для ограничения доступа к категории |
| `slug` | `string` | Нет | ЧПУ-идентификатор |
| `title` | `string` | Нет | Название категории |
| `created_at` | `timestamp` | Нет | Время создания |
| `updated_at` | `timestamp` | Нет | Время обновления |

Индексы и ограничения:

- `unique (org_id, type, slug)`
- `index (acl_resource)`
- `index (parent_id)`
- `foreign key (org_id) -> orgs.id`
- `foreign key (parent_id) -> categories.id`

### `category_post`

Таблица связей `many-to-many` для записей и категорий.

Одна категория содержит много записей, и одна запись может входить в несколько категорий.

| Поле | Тип | Null | Описание |
|---|---|---|---|
| `post_id` | `bigint unsigned` | Нет | ID записи |
| `category_id` | `bigint unsigned` | Нет | ID категории |
| `position` | `integer` | Да | Порядок внутри категории (опционально) |

Индексы и ограничения:

- `primary key (post_id, category_id)` или `unique (post_id, category_id)`
- `foreign key (post_id) -> posts.id on delete cascade`
- `foreign key (category_id) -> categories.id on delete cascade`

## Примечания

- Для MVP дерево через `parent_id` обычно достаточно.
- Если позже потребуется быстрый вывод полной ветки, можно рассмотреть `path` или отдельную структуру дерева (например, closure table).
- Правило ACL для `posts` и `categories`: если `acl_resource = null`, объект доступен всем в рамках базовой бизнес-логики; если значение задано, доступ разрешается только при успешной проверке ACL-права на этот ресурс.


## UI

Для управления записями создана отдельная страница `/{current_team}/{current_org}/posts/{type?}`.

- Сверху таблицы кнопки на `/{current_team}/{current_org}/posts/[type]`, где `type` — тип записи (`page`, `news`, `article`, `product`).
- Таблица всегда фильтруется по `type`, который берётся из URL.
- Для таблицы используется `@tanstack/react-table` и общие компоненты таблиц из `resources/js/shared/ui/table`.
- Список записей поддерживает серверную пагинацию, поиск, фильтры по колонкам и сортировку.
- На странице редактирования записи справа отображается блок категорий. Категории выводятся деревом с учётом вложенности, связь с записью меняется чекбоксом и автоматически сохраняется через `PATCH /{current_team}/{current_org}/posts/{post}/categories` после debounce-задержки.
