<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\EnhancedTestMakeCommand;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->registerLegacyRosterModelAliases();

        // Replace Laravel's default make:test command with our enhanced version
        $this->app->singleton('command.test.make', function (Application $app) {
            return new EnhancedTestMakeCommand($app['files']);
        });
    }

    private function registerLegacyRosterModelAliases(): void
    {
        $aliases = [
            'App\\Models\\Managers\\Manager' => Manager::class,
            'App\\Models\\Referees\\Referee' => Referee::class,
            'App\\Models\\Stables\\Stable' => Stable::class,
            'App\\Models\\TagTeams\\TagTeam' => TagTeam::class,
            'App\\Models\\Wrestlers\\Wrestler' => Wrestler::class,
        ];

        foreach ($aliases as $legacyClass => $rosterClass) {
            if (! class_exists($legacyClass, false)) {
                class_alias($rosterClass, $legacyClass);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @param array<string> $parameters */
        Validator::replacer('ends_with', static function (string $message, string $attribute, string $rule, array $parameters): string {
            /** @var string $values */
            $values = array_pop($parameters);

            if (count($parameters) !== 0) {
                $values = implode(', ', $parameters).' or '.$values;
            }

            return str_replace(':values', $values, $message);
        });

        Relation::enforceMorphMap([
            'wrestler' => Wrestler::class,
            'manager' => Manager::class,
            'match' => EventMatch::class,
            'title' => Title::class,
            'tag_team' => TagTeam::class,
            'referee' => Referee::class,
            'stable' => Stable::class,
            'event' => Event::class,
            'venue' => Venue::class,
        ]);

        Vite::macro('image', fn (string $asset) => Vite::asset("resources/media/{$asset}"));

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
