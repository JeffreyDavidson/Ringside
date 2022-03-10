<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;

class AppServiceProvider extends ServiceProvider
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
        Builder::macro('orderByNullsLast', function ($column, $direction = 'asc') {
            /** @var Builder $this */
            $column = $this->getGrammar()->wrap($column);
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $this->orderByRaw("{$column} IS NULL {$direction}, {$column} {$direction}");
        });

        Component::macro('notify', function ($message) {
            $this->dispatchBrowserEvent('notify', $message);
        });
    }
}
