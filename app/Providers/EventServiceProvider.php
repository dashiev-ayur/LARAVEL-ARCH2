<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrgUpdated;
use App\Listeners\LogOrgUpdated;
use App\Models\Org;
use App\Observers\OrgObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        OrgUpdated::class => [
            LogOrgUpdated::class,
        ],
    ];

    public function register(): void
    {
        // В проекте мы регистрируем события вручную через $listen (см. выше).
        // Поэтому отключаем автодискавери, чтобы listener-ы не регистрировались повторно
        // (иначе один и тот же обработчик может вызваться дважды на один event).
        ServiceProvider::disableEventDiscovery();

        // Оставляем стандартную логику регистрации слушателей из $listen.
        parent::register();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        parent::boot();

        Org::observe(OrgObserver::class);
    }
}
