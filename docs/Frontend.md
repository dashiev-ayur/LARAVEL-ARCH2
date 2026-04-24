# Как насчет FSD

## Слои

pages - состоит только из композиции виджетов, тут должно быть минимум html (только для расположения виджетов), и другой необходимый минимум
widgets - Крупные UI блоки (хедер, сайдбар, списки)
features - Бизнес-функции (авторизация, поиск, фильтры)
entities - Бизнес-сущности (пользователь, запись)
shared - Переиспользуемый код (UI-кит, утилиты, inertiajs)

Верхние слои импортируют только из нижних;

## Этап 0. Правила (зафиксировано)

### Смысл слоёв (договор)

- **shared** — примитивы UI, утилиты, обёртки над Inertia/React без домена (не знают про «организацию», «запись» и сценарии).
- **entities** — куски UI и типы, привязанные к **одной** бизнес-сущности (карточка, аватар+имя, ячейка «статус»), **без** полноценного пользовательского сценария.
- **features** — законченные действия/флоу: фильтр списка, смена org, шаг 2FA — здесь уместны `usePage`/`router`, сценарная разметка.
- **widgets** — крупные композиции: шапка, сайдбар, зона «таблица+тулбар» из нескольких features/entities.
- **pages** — Inertia-страница: `Head`, разметка маршрута, **композиция** widgets/features, типы пропов с бэка; **минимум** собственной логики (колонки таблицы и т.п. — ниже по слоям).

### Публичные API слайса

- Слайс (папка) отдаёт наружу **явный вход**: предпочтительно `index.ts` (реэкспорт), либо согласованные файлы; внутренние файлы не импортируем «сквозь» чужой слайс.
- **Не** подтягиваем из слайса всё подряд — только то, что объявлено публичным, чтобы границы не размылись.

### Inertia и данные

- Данные с сервера — `**usePage()`** (и при необходимости **узкие хуки** `useXxxPage` над `usePage` для одной страницы). Отдельный React Context **под копию тех же `props` не вводим** — источник правды остаётся Inertia; контекст допустим для локального UI-состояния поддерева, не для дублирования пропов.
- **Справочники, подписи, enum-значения**, которые ведёт Laravel (например типы записей) — **на фронте не дублируем**; приходят в пропах Inertia. В TS — `string`, `Record<…>`, `readonly string[]` и т.д., **без** зеркального `enum` в JS, пока нет жёсткой клиентской валидации.
- **Wayfinder** (`@/routes`, `@/actions`) — единый способ путей к экшенам и маршрутам, без сырых строк URL в фичах.

### Импорты

- **Верхние** слои **импортируют только из нижних** (и при необходимости из shared на любом уровне по правилам FSD); нижние не тянут `pages` / `widgets` / `features` выше по смыслу.
- `shared` **не** зависит от `entities` / `features` / `widgets` / `pages`.

## Этап 1. Алиасы (выполнено)

- **`tsconfig.json`**: `paths` для корня и вложенных путей слоёв — `@/shared`, `@/shared/*`, `@/entities`, `@/entities/*`, `@/features`, `@/features/*`, `@/widgets`, `@/widgets/*`, плюс по-прежнему `@/*` → `resources/js/*` (старые импорты вида `@/components/...` не трогались).
- **`vite.config.ts`**: `resolve.alias` (массив) — впереди привязки к каталогам FSD, затем `@` → `resources/js`, чтобы резолв совпадал с TypeScript.
- **Каталоги**: `resources/js/shared`, `entities`, `features`, `widgets` с **`index.ts`**-заглушками (публичный API слоёв по мере миграции).
- **Использование**: `import '…' from '@/shared'`, `from '@/shared/…'`, и аналогично для остальных слоёв.

## Этап: shared (выполнено)

- **`shared/lib`** — утилиты, в т.ч. `cn` и `toUrl` (`utils.ts`).
- **`shared/ui`** — shadcn-примитивы; таблица — `shared/ui/table` (публичный вход как `@/shared/ui/table`).
- **`shared/hooks`** (нейтральные): `use-clipboard`, `use-mobile`, `use-initials`, `use-appearance`, `use-current-url`, `use-mobile-navigation`, `use-flash-toast`.
- **Вне `shared`**: `use-two-factor-auth` остаётся в `resources/js/hooks` до отдельной фичи; кнопка «новая запись» — `features/post` (`ButtonNewPost`, импорт `from '@/features/post'`). Алиасы shadcn в `components.json` указывают на `shared/ui` и `shared/lib`.

## Этап: entities (выполнено)

- **Слайсы** — `resources/js/entities/{user,team,org,post}/`, публичный вход `index.ts` (и опционально корневой `resources/js/entities/index.ts`).
- **Модель** — `model/types.ts`: контракты, согласованные с DTO Inertia из `@/types` (без дублирования полей); для строки списка постов — `PostListRow` (отдельного DTO в `types/` нет, определён в слайсе).
- **UI** — `UserInfo` (user); ячейки таблицы поста — `PostTitleSlugCell`, `PostStatusCell` (post). Смена org/team через switcher’ы в `components/`; типы `OrgEntity` / `TeamEntity` импортируют из `@/entities/org` и `@/entities/team`.

## Поэтапный план перехода (чтобы ничего не сломать)

1. **Правила** — согласовать смыслы слоёв, entrypoints слайсов, тонкие `pages` + `usePage` без дублирования бэка.
2. **Алиасы** — ввести `@/shared`, `@/entities`, `@/features`, `@/widgets` (Vite/TS), старые пути оставить до миграции.
3. `**shared` первым** — UI-кит, `lib`, общие UI-хуки/типы; граница: `shared` не тянет верхние слои; после переноса — сборка.
4. `**entities` (выполнено)** — слайсы `user`, `team`, `org`, `post` под [`resources/js/entities/`](resources/js/entities/); в каждом слайсе `model/types` (контракт сущности, согласован с `@/types` через `Pick` / `type` = DTO); UI: `UserInfo` (`@/entities/user`), `PostTitleSlugCell` / `PostStatusCell` + `PostListRow` (`@/entities/post`); `team`/`org` — публичные типы + `TeamEntity` / `OrgEntity` для потребителей. Импорты только вниз по слоям; сценарные switcher’ы остаются в `components/`.
5. `**features**` (пилот) — `resources/js/features/post/`: сценарий «список записей» — `PostTypeFilter` (Inertia `Link` + `byType.url` из `@/routes/posts`), `ButtonNewPost` (опционально `href` через Inertia, иначе заглушка), `PostsListToolbar`, хук `usePostsListPage` и типы `PostsListPageProps`; страница `pages/posts/index` только композиция + таблица.
6. `**widgets**` — крупные блоки (сайдбар, шапка) по одному файлу, композиция `features` + `entities` + `shared`.
7. `**pages**` — убрать толстую разметку/логику, оставить композицию + пропы Inertia; проверка роутов и wayfinder.
8. **Закрепление** — (опц.) линт границ импорта, обновить README/док, регрессия e2e/PHP feature-тестов Inertia.

