<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     */
    public const HOME = '/dashboard';

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
        Builder::macro('orderByNullsLast', function (Expression|string $column, string $direction = 'asc') {
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

        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

    }
}
