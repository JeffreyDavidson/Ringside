<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use App\Observers\EventObserver;
use App\Observers\ManagerObserver;
use App\Observers\RefereeObserver;
use App\Observers\TagTeamObserver;
use App\Observers\TitleObserver;
use App\Observers\WrestlerObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Event::observe(EventObserver::class);
        Manager::observe(ManagerObserver::class);
        Referee::observe(RefereeObserver::class);
        TagTeam::observe(TagTeamObserver::class);
        Title::observe(TitleObserver::class);
        Wrestler::observe(WrestlerObserver::class);
    }
}
