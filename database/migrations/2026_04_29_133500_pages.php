<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // При физическом удалении родителя дочерние страницы становятся корневыми.
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            // Статус публикации: draft, review, published.
            $table->string('status');
            // Ресурс ACL для проверки доступа к странице.
            $table->string('acl_resource')->nullable();

            // Позиция страницы среди соседних страниц.
            $table->unsignedInteger('sort_order')->default(0);
            // URL-сегмент страницы внутри родительского раздела.
            $table->string('slug');
            // Полный путь страницы внутри организации без домена.
            // Увеличенная длина оставляет запас для вложенных страниц.
            $table->string('path', 2048);
            // Глубина страницы в дереве, корневой уровень равен 0.
            $table->unsignedSmallInteger('depth')->default(0);

            // Название страницы для интерфейса и сортировки по алфавиту.
            $table->string('title');
            // Краткое описание или лид страницы.
            $table->text('excerpt')->nullable();
            // Основное содержимое страницы.
            $table->longText('content')->nullable();

            // Шаблон отображения страницы.
            $table->string('template')->nullable();
            // Заголовок страницы для SEO.
            $table->string('seo_title')->nullable();
            // Описание страницы для meta description.
            $table->text('meta_description')->nullable();
            // Запрещает индексацию страницы поисковыми системами.
            $table->boolean('noindex')->default(false);

            // Хеш текущего состояния страницы, влияющего на итоговый HTML.
            // В него стоит включать path, title, content, template, SEO-поля,
            // статус публикации и другие поля, от которых зависит генерация.
            $table->char('content_hash', 64)->nullable();
            // Хеш состояния страницы, по которому последний раз успешно
            // сгенерировали статический файл. Если он отличается от
            // content_hash, страницу нужно поставить в очередь генерации.
            $table->char('generated_hash', 64)->nullable();
            // Дата и время последней успешной генерации страницы.
            // Значение null означает, что страница ещё ни разу не генерировалась.
            $table->timestamp('generated_at')->nullable();
            // Явный маркер очереди генерации. Он нужен для случаев, когда
            // страницу надо пересобрать не из-за изменения собственных полей,
            // а из-за внешних зависимостей: меню, layout, родительского пути
            // или списка дочерних страниц.
            $table->boolean('needs_generation')->default(true);

            // Дата и время публикации страницы.
            $table->timestamp('published_at')->nullable();
            // Дата и время проверки страницы.
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Быстрый и уникальный доступ к странице по домену организации и полному URL-пути.
            // Основной сценарий: домен определил org_id, path определил конкретную страницу.
            $table->unique(['org_id', 'path']);
            // Выборка дочерних страниц внутри одного родителя с основной сортировкой сайта:
            // сначала ручной sort_order, затем алфавитный title, затем id для стабильного порядка.
            $table->index(['org_id', 'parent_id', 'sort_order', 'title', 'id']);
            // Поиск опубликованных страниц организации по статусу и дате публикации.
            // Полезно для sitemap, публичных списков, отложенной публикации и админских фильтров.
            $table->index(['org_id', 'status', 'published_at']);
            // Очередь инкрементальной генерации сайта: выбирает страницы нужного статуса,
            // которые помечены к пересборке, и позволяет сортировать их по времени прошлой генерации.
            $table->index(['org_id', 'status', 'needs_generation', 'generated_at']);
            // Проверки доступа по ACL-ресурсу без полного сканирования таблицы страниц.
            $table->index('acl_resource');
            // Служебный индекс для self-FK и прямых запросов по parent_id без фильтра org_id.
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
