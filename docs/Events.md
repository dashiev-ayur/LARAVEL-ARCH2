# Events & Listeners в проекте

Этот документ описывает, как в проекте устроены **события (Events)**, **слушатели (Listeners)** и как они связаны с **Eloquent Observer** на примере `OrgUpdated`.



php artisan event:list --event=OrgUpdated --json

## Термины (коротко)

- **Event (событие)**: объект “что произошло”, переносит данные.
- **Listener (слушатель)**: реакция на событие (побочные эффекты: логирование, уведомления, запуск job и т.п.).
- **Observer (наблюдатель модели)**: хук на жизненный цикл Eloquent-модели (`created/updated/deleted/...`), удобен для генерации событий на изменения модели.
- **EventServiceProvider**: место, где Laravel “узнаёт”, какие listener-ы слушают какие event-ы, и где можно регистрировать observers.

## Где что находится (в текущей реализации)

- **Провайдер**: `app/Providers/EventServiceProvider.php`
- **Подключение провайдера**: `bootstrap/providers.php`
- **Observer**: `app/Observers/OrgObserver.php`
- **Event**: `app/Events/OrgUpdated.php`
- **Listener-ы**: `app/Listeners/OrgUpdatedListener1.php`, `OrgUpdatedListener2.php`, `OrgUpdatedListener.php`
- **Job (очередь)**: `app/Jobs/OrgUpdatedProcessingJob.php`

## Почему это срабатывает (главная причина)

Потому что `EventServiceProvider` подключён в `bootstrap/providers.php`, а в самом провайдере:

1. зарегистрирован observer модели `Org`, значит Laravel будет вызывать методы observer-а на `updated`, `created`, и т.д.
2. объявлена карта `$listen`, значит Laravel будет вызывать listener при диспатче event-а.

## Последовательность (пошагово)

### 1) Загрузка провайдеров

Laravel загружает провайдеры из `bootstrap/providers.php`, в том числе `EventServiceProvider`.

### 2) Регистрация наблюдателя модели

В `EventServiceProvider::boot()` регистрируется observer:

- `Org::observe(OrgObserver::class);`

Это означает: когда Eloquent выполнит `UPDATE` по таблице `orgs` и модель действительно была изменена, будет вызван `OrgObserver::updated(Org $org)`.

### 3) Обновление модели

Где-то в приложении происходит обновление организации, например:

- `$org->update([...])`
- `$org->fill([...]); $org->save();`

Если update реально меняет атрибуты, Eloquent вызывает lifecycle-хук `updated`.

### 4) Observer диспатчит event

`OrgObserver::updated()`:

- берёт `$org->getChanges()` — изменённые поля и их новые значения
- отбрасывает шум (`updated_at`)
- собирает “старые” значения для этих же ключей через `$org->getOriginal($key)`
- диспатчит событие: `OrgUpdated::dispatch($org, $changes, $original)`

Идея: observer не делает “бизнес-эффектов”, он только формирует событие.

### 5) Listener обрабатывает event

В `EventServiceProvider::$listen` зарегистрировано несколько listener-ов на `OrgUpdated` (см. провайдер).

Поэтому после диспатча события Laravel вызывает их **по очереди** в одном запросе, например `OrgUpdatedListener1`, затем `OrgUpdatedListener2`, затем `OrgUpdatedListener` (который ставит в очередь job — см. раздел про Jobs ниже).

### Схема

```text
bootstrap/providers.php
  -> EventServiceProvider (загружен)
     -> boot(): Org::observe(OrgObserver::class)
     -> $listen: OrgUpdated => [Listener1, Listener2, OrgUpdatedListener]

Org::update()/save()
  -> Eloquent lifecycle: updated
    -> OrgObserver::updated()
      -> OrgUpdated::dispatch(...)
        -> Event dispatcher
          -> OrgUpdatedListener1::handle()
          -> OrgUpdatedListener2::handle()
          -> OrgUpdatedListener::handle()
            -> OrgUpdatedProcessingJob::dispatch()  -> таблица/Redis очереди `orgs`
              -> (отдельный процесс) queue:work -> Job::handle()
```

## Как добавить новое событие (Event)

1. Создайте класс события в `app/Events/`.
2. Сделайте его “контейнером данных”:
  - `declare(strict_types=1);`
  - `use Dispatchable, SerializesModels;`
  - передавайте необходимые данные через constructor property promotion
  - для массивов добавьте PHPDoc типов

Пример:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SomethingHappened
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $entityId,
        public array $context = [],
    ) {}
}
```

## Как добавить новый Listener

1. Создайте класс listener-а в `app/Listeners/`.
2. Реализуйте метод:

```php
public function handle(SomethingHappened $event): void
{
    // side effects
}
```

1. Зарегистрируйте связку в `app/Providers/EventServiceProvider.php` в `$listen`:

```php
protected $listen = [
    SomethingHappened::class => [
        DoSomethingListener::class,
    ],
];
```

Подсказка по стилю:

- **Observer** — тонкий, без побочных эффектов (только “сформировать и диспатчить событие”).
- **Listener** — место для побочных эффектов (логирование, notifications, постановка job в очередь).
- **Job** — отложенная задача, выполняется **воркером очереди** (другой процесс / позже в том же процессе при `sync`).

## Jobs (очередь): создание, запуск, связь с событиями

### Зачем Job, если есть Listener

- **Синхронный listener** выполняется в HTTP-запросе до отправки ответа (если не `ShouldQueue`). Долгая работа и ошибки влияют на ответ пользователю.
- **Job с `ShouldQueue`** кладётся в хранилище очереди; HTTP-запрос обычно **заканчивается раньше**, тяжёлая работа идёт в `queue:work`.

Частый паттерн: listener остаётся **лёгким** и только вызывает `SomeJob::dispatch(...)`, как в `OrgUpdatedListener`.

### Где лежат классы

- `app/Jobs/`

### Как создать Job

```bash
php artisan make:job ИмяКлассаJob --no-interaction
```

В проекте пример: `OrgUpdatedProcessingJob` — реализует `Illuminate\Contracts\Queue\ShouldQueue`, использует трейт `Illuminate\Foundation\Queue\Queueable` (в нём же `Dispatchable` и сериализация моделей для очереди).

Именованная очередь (например `orgs`) задаётся в конструкторе job:

```php
public function __construct(public Org $org)
{
    $this->onQueue('orgs');
}
```

### Как поставить Job в очередь из Listener

```php
use App\Jobs\OrgUpdatedProcessingJob;

public function handle(OrgUpdated $event): void
{
    OrgUpdatedProcessingJob::dispatch($event->org);
}
```

При `database` / `redis` и т.д. запись попадает в очередь; при `QUEUE_CONNECTION=sync` job выполняется **сразу** в том же запросе, без отдельного воркера.

### Как выполнить Job (воркер)

1. В `.env` задайте драйвер очереди, например `QUEUE_CONNECTION=database` или `redis`.
2. Для `database` нужна миграция таблицы `jobs` и при необходимости `failed_jobs` (`php artisan queue:table`, `queue:failed-table`).
3. Запустите воркер и укажите очередь **`orgs`** (или список очередей с приоритетом):

```bash
php artisan queue:work --queue=orgs,default
```

Пока процесс работает, он забирает job из очереди и вызывает `handle()`. **Один** воркер обрабатывает job **последовательно** (одна за другой). Несколько воркеров могут выполнять разные job **параллельно**.

### Отладка без очереди

Синхронное выполнение в текущем процессе (без записи в `jobs`):

```php
OrgUpdatedProcessingJob::dispatchSync($org);
```

Или временно `QUEUE_CONNECTION=sync` в `.env`.

### Успешные и неуспешные job (таблица `jobs`)

Для драйвера **`database`** строка в `jobs` — это **ожидающая** задача. После **успешного** `handle()` Laravel **удаляет** эту строку. История успешных выполнений в `jobs` не хранится.

При падении job с настроенным логированием неудач запись может оказаться в **`failed_jobs`** (`php artisan queue:retry` и т.д. — см. документацию Laravel по очередям).

### Таблица `job_batches`

Она используется для **`Bus::batch([...])`**: учёт пакета из многих job, колбэки по завершению всего пакета. К обычному `dispatch()` одной job **не относится**. Параллелизм в батче даёт не таблица, а то, что в очередь попадает много job и их подхватывают **несколько воркеров**.

### Ошибки в цепочке listener → job

Если **любой** синхронный listener **до** `dispatch` бросит исключение, следующие listener-ы не выполнятся, job может **не быть поставлен**. Исключение обычно приводит к **ошибочному HTTP-ответу**, а не к «тихому» успеху.

## Как добавить Eloquent Observer (и “подвязать” к модели)

1. Создайте observer, например `app/Observers/MyModelObserver.php`.
2. Реализуйте нужные методы lifecycle:
  - `created(MyModel $model)`
  - `updated(MyModel $model)`
  - `deleted(MyModel $model)`
  - и т.д.
3. Зарегистрируйте observer в `EventServiceProvider::boot()`:

```php
MyModel::observe(MyModelObserver::class);
```

Рекомендация:

- Если вы диспатчите события только при изменениях определённых полей — фильтруйте `$model->getChanges()` и выходите, если изменений нет.

## Как диспатчить событие без Eloquent Observer (вручную)

Иногда событие логичнее диспатчить **на уровне бизнес-операции** (use case / service / action), а не “на любое обновление модели”.

Есть 2 стандартных способа:

### Вариант A: `::dispatch()`

```php
use App\Events\SomethingHappened;

SomethingHappened::dispatch($entityId, ['source' => 'manual']);
```

### Вариант B: helper `event()`

```php
use App\Events\SomethingHappened;

event(new SomethingHappened($entityId, ['source' => 'manual']));
```

## Практические заметки (частые ошибки)

### 1) “Событие не сработало”

Проверить:

- подключён ли `EventServiceProvider` в `bootstrap/providers.php`
- зарегистрирован ли event в `$listen`
- зарегистрирован ли observer через `Model::observe()`
- действительно ли модель была изменена (Eloquent не вызывает `updated`, если вы сохранили без изменений)

### 2) Транзакции и “после коммита”

Если обновление модели происходит внутри транзакции, иногда важно запускать побочные эффекты **только после commit**.

Варианты (концептуально):

- диспатчить событие “после коммита” (after commit), чтобы listener не отрабатывал при rollback
- либо выносить диспатч в код, который гарантированно выполняется после успешного завершения транзакции

Если потребуется, можно расширить текущую реализацию под after-commit поведение (в зависимости от того, где именно обновляется `Org`).