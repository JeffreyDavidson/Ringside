<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\TagTeamSubscriber;
use App\Listeners\ManagerSubscriber;
use App\Listeners\WrestlerSubscriber;
use App\Models\Event;
use App\Models\Referee;
use App\Models\Stable;
use App\Models\Title;
use App\Observers\EventObserver;
use App\Observers\RefereeObserver;
use App\Observers\StableObserver;
use App\Observers\TitleObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        Event::class => [EventObserver::class],
        Referee::class => [RefereeObserver::class],
        Stable::class => [StableObserver::class],
        Title::class => [TitleObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        WrestlerSubscriber::class,
        TagTeamSubscriber::class,
        ManagerSubscriber::class,
    ];
}
