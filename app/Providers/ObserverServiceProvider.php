<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Title;
use App\Observers\EventObserver;
use App\Observers\StableObserver;
use App\Observers\TagTeamObserver;
use App\Observers\TitleObserver;
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
        Stable::observe(StableObserver::class);
        TagTeam::observe(TagTeamObserver::class);
        Title::observe(TitleObserver::class);
    }
}
