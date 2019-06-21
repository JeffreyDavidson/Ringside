<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
