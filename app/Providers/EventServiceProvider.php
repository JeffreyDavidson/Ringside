<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Event;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use App\Observers\EventObserver;
use App\Observers\ManagerObserver;
use App\Observers\RefereeObserver;
use App\Observers\StableObserver;
use App\Observers\TagTeamObserver;
use App\Observers\TitleObserver;
use App\Observers\WrestlerObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
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
        Manager::class => [ManagerObserver::class],
        Referee::class => [RefereeObserver::class],
        Stable::class => [StableObserver::class],
        TagTeam::class => [TagTeamObserver::class],
        Title::class => [TitleObserver::class],
        Wrestler::class => [WrestlerObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
