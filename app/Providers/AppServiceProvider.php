<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\EnhancedTestMakeCommand;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     */
    public const string HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace Laravel's default make:test command with our enhanced version
        $this->app->singleton('command.test.make', function ($app) {
            return new EnhancedTestMakeCommand($app['files']);
        });
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
            $direction = mb_strtolower($direction) === 'asc' ? 'asc' : 'desc';

            return $builder->orderByRaw("{$column} IS NULL {$direction}, {$column} {$direction}");
        });

        /** @param array<string> $parameters */
        Validator::replacer('ends_with', static function (string $message, string $attribute, string $rule, array $parameters): string {
            /** @var string $values */
            $values = array_pop($parameters);

            if (count($parameters) !== 0) {
                $values = implode(', ', $parameters).' or '.$values;
            }

            return str_replace(':values', $values, $message);
        });

        Relation::morphMap([
            'wrestler' => Wrestler::class,
            'manager' => Manager::class,
            'title' => Title::class,
            'tagTeam' => TagTeam::class,
            'referee' => Referee::class,
            'stable' => Stable::class,
        ]);

        Vite::macro('image', fn (string $asset) => Vite::asset("resources/images/{$asset}"));

        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            /** @var User|null $user */
            $user = $request->user();

            return Limit::perMinute(60)->by($user?->id ?: $request->ip());
        });
    }
}
