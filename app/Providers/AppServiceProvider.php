<?php

namespace App\Providers;

use Spatie\BladeX\Facades\BladeX;
use Illuminate\Support\ServiceProvider;
use App\Http\Requests\CustomDataTablesRequest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('datatables.request', function () {
            return new CustomDataTablesRequest;
        });

        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        BladeX::component('components.*');
    }
}
