<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as LaravelTelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->register(LaravelTelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('orderByNullsLast', function ($column, $direction = 'asc') {
            /** @var Builder $this */
            $column = $this->getGrammar()->wrap($column);
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $this->orderByRaw("{$column} IS NULL {$direction}, {$column} {$direction}");
        });
    }
}
