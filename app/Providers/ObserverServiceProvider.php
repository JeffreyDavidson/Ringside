<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Title;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use App\Observers\EventObserver;
use App\Observers\TitleObserver;
use App\Observers\ManagerObserver;
use App\Observers\RefereeObserver;
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
        Wrestler::observe(WrestlerObserver::class);
        Referee::observe(RefereeObserver::class);
        Manager::observe(ManagerObserver::class);
        Event::observe(EventObserver::class);
        Title::observe(TitleObserver::class);
    }
}
