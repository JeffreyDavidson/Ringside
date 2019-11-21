<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
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
        Blade::component('components.filter', 'filter');
        Blade::component('components.flatpickr', 'flatpickr');
        Blade::component('components.statusSelect', 'statusSelect');
        Blade::component('components.buttons.employ', 'employbutton');
        Blade::component('components.buttons.delete', 'deletebutton');
        Blade::component('components.buttons.edit', 'editbutton');
        Blade::component('components.buttons.injure', 'injurebutton');
        Blade::component('components.buttons.recover', 'recoverbutton');
        Blade::component('components.buttons.reinstate', 'reinstatebutton');
        Blade::component('components.buttons.retire', 'retirebutton');
        Blade::component('components.buttons.suspend', 'suspendbutton');
        Blade::component('components.buttons.unretire', 'unretirebutton');
        Blade::component('components.buttons.view', 'viewbutton');
    }
}