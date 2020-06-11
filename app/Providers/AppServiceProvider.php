<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
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
            $column = $this->getGrammar()->wrap($column);
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $this->orderByRaw("$column IS NULL $direction, $column $direction");
        });

        Request::macro('validatedExcept', function ($keys) {
            $keys = is_array($keys) ? $keys : func_get_args();

            $results = $this->validated();

            Arr::forget($results, $keys);

            return $results;
        });
    }
}
