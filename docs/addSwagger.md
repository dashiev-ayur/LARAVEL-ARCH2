# Подключение Swagger

Ниже — пошаговое подключение интерактивной документации OpenAPI (Swagger UI) в Laravel через пакет [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger). Для Laravel 13 используйте актуальную ветку пакета (11.x): она рассчитана на `laravel/framework ^13`, PHP 8.2+ и `zircote/swagger-php` 6.x.

1. Установите зависимость из корня проекта:

   ```bash
   composer require darkaonline/l5-swagger
   ```

2. Опубликуйте конфигурацию и шаблоны пакета (начиная с L5-Swagger 6 это стандартный способ):

   ```bash
   php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
   ```

   После публикации появится `config/l5-swagger.php` и при необходимости представления в `resources/views/vendor/l5-swagger`.

3. Укажите в `config/l5-swagger.php`, какие каталоги и файлы сканирует генератор (ключи вроде `annotations` / `scanOptions`), чтобы OpenAPI-описания подхватывались из ваших контроллеров или отдельных PHP-файлов с атрибутами/аннотациями swagger-php. По умолчанию часто сканируются `app` и связанные пути — сверьтесь с опубликованным конфигом после шага 2.

4. При необходимости задайте базовый URL API для спецификации через переменные окружения (имена смотрите в опубликованном `config/l5-swagger.php` и `.env.example`, если вы их добавите). Часто используют константу вида `L5_SWAGGER_CONST_HOST` или аналог из конфига, чтобы в UI корректно подставлялся хост (локально и на стендах).

5. Сгенерируйте JSON/YAML спецификацию и статику UI:

   ```bash
   php artisan l5-swagger:generate
   ```

   Повторяйте команду после изменения атрибутов или аннотаций в коде. Для разработки можно включить автогенерацию при каждом запросе (в `.env`): `L5_SWAGGER_GENERATE_ALWAYS=true` — **не включайте это в продакшене** без понимания нагрузки и кэширования.

6. Запустите приложение (`php artisan serve`, Sail и т.д.) и откройте интерфейс Swagger UI по маршруту из конфигурации (типичный путь по умолчанию — `/api/documentation`; точное значение — в `config/l5-swagger.php`, ключ `routes` / `docs`).

7. Ограничьте доступ к документации в production: оставьте UI только за авторизацией, базовой авторизацией веб-сервера или отключите маршруты документации на публичных окружениях, если спецификация не должна быть открыта.

8. Для `swagger-php` 6.x в PHP предпочтительны **атрибуты** (`OpenApi\Attributes\...`) вместо устаревших DocBlock-аннотаций; синтаксис и примеры — в [документации zircote/swagger-php](https://zircote.github.io/swagger-php/) и примерах репозитория L5-Swagger.

Если `composer require` сообщает о несовместимости версий, обновите ограничения фреймворка в `composer.json` или установите версию L5-Swagger, явно совместимую с вашей веткой Laravel (см. [Installation & Configuration](https://github.com/DarkaOnLine/L5-Swagger/wiki/Installation-&-Configuration) на GitHub).
