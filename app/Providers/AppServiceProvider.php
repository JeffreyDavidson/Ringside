<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('orderByNullsLast', function ($column, $direction = 'asc') {
            /** @var Builder $builder */
            $builder = $this;
            $column = $builder->getGrammar()->wrap($column);
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $builder->orderByRaw("{$column} IS NULL {$direction}, {$column} {$direction}");
        });

        Relation::morphMap([
            'wrestler' => \App\Models\Wrestler::class,
            'manager' => \App\Models\Manager::class,
            'title' => \App\Models\Title::class,
            'tagteam' => \App\Models\TagTeam::class,
            'referee' => \App\Models\Referee::class,
            'stable' => \App\Models\Stable::class,
        ]);
    }
}
