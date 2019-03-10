<?php

namespace App\Providers;

use App\Models\Stable;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Policies\StablePolicy;
use App\Policies\ManagerPolicy;
use App\Policies\RefereePolicy;
use App\Policies\TagTeamPolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::guessPolicyNamesUsing(function ($class) {
            return '\\App\\Policies\\'.class_basename($class).'Policy';
        });
    }
}
