# Pages in Inertia (React)

Проект использует Laravel + Inertia + React.  
Ниже короткая инструкция, как создать простую страницу и правильно выбрать для нее layout.

## 1. Создание простой страницы

1. Создайте React-файл в `resources/js/pages`, например `about.tsx`.
2. Добавьте маршрут в `routes/web.php`, который возвращает `inertia(...)`.
3. Передайте с сервера только нужные props.

Пример страницы `resources/js/pages/about.tsx`:

```tsx
import { Head } from '@inertiajs/react';

type AboutProps = {
    message: string;
};

export default function About({ message }: AboutProps) {
    return (
        <div className="p-10">
            <Head title="О проекте" />
            <h1 className="text-2xl font-semibold">О проекте</h1>
            <p className="mt-2 text-sm text-muted-foreground">{message}</p>
        </div>
    );
}
```

Пример маршрута в `routes/web.php`:

```php
Route::get('/about', function () {
    return inertia('about', [
        'message' => 'Тут будет информация о проекте!',
    ]);
});
```

Важно: строка в `inertia('about')` должна соответствовать пути `resources/js/pages/about.tsx`.

## 2. Как работает layout в этом проекте

Выбор layout централизован в `resources/js/app.tsx` через:

`createInertiaApp({ layout: (name) => { ... } })`

Текущая идея:
- `welcome` -> без layout
- `auth/*` -> `AuthLayout`
- `settings/*` и `teams/*` -> `[AppLayout, SettingsLayout]`
- остальное -> `AppLayout`

Именно из-за этого публичная страница может случайно получить приватный layout.

## 3. Как создать новый layout

1. Создайте файл в `resources/js/layouts`, например `public-layout.tsx`.
2. Экспортируйте компонент, принимающий `children`.
3. Подключите его в `resources/js/app.tsx` и добавьте условие в `layout(name)`.

Пример `resources/js/layouts/public-layout.tsx`:

```tsx
export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b px-6 py-4">Public header</header>
            <main className="mx-auto max-w-5xl p-6">{children}</main>
        </div>
    );
}
```

Подключение в `resources/js/app.tsx`:

```tsx
import PublicLayout from '@/layouts/public-layout';

// ...
case name === 'about':
    return PublicLayout;
```

## 4. Как отключить layout для конкретной страницы

Если для страницы layout не нужен:

```tsx
case name === 'about':
    return null;
```

Это полностью выключит layout для `/about`.

## 5. Частая проблема: пустой экран на публичной странице

Причина обычно в том, что страница рендерится через `AppLayout`, а внутри есть обращения к `auth.user` без проверки на `null`.

Решения:
- использовать `PublicLayout` для публичных страниц;
- или вернуть `null` (без layout);
- или сделать `AppLayout` безопасным для гостя (`auth.user ? ... : ...`).

## 6. Рекомендации по структуре

- Публичные страницы удобно хранить в `resources/js/pages/public/*`.
- Тогда layout можно назначать одним правилом:

```tsx
case name.startsWith('public/'):
    return PublicLayout;
```

- Для заголовка страницы всегда используйте `<Head title="..." />`.

## 7. Быстрый чеклист перед коммитом

- Страница создана в `resources/js/pages/...`
- Маршрут добавлен в `routes/web.php`
- Props передаются с сервера
- Проверен правильный layout в `resources/js/app.tsx`
- Проверено поведение для гостя и авторизованного пользователя
