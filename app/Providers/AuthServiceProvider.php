<?php

namespace App\Providers;

use App\Manager;
use App\TagTeam;
use App\Wrestler;
use App\Retirement;
use App\Policies\ManagerPolicy;
use App\Policies\TagTeamPolicy;
use App\Policies\WrestlerPolicy;
use App\Policies\RetirementPolicy;
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
        Wrestler::class => WrestlerPolicy::class,
        TagTeam::class => TagTeamPolicy::class,
        Manager::class => ManagerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
