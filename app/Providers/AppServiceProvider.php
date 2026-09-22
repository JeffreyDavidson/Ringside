<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Concerns\BelongsToPromotion;
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
use App\Services\Promotions\PromotionContextService;
use App\View\Composers\PromotionSwitcherComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
    #[\Override]
    public function register(): void
    {
        $this->app->scoped(PromotionContextService::class);

        $this->registerLegacyRosterModelAliases();

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
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! $user->role->isAdministrator()) {
                return null;
            }

            $subject = $arguments[0] ?? null;
            $context = app(PromotionContextService::class);

            $isPromotionOwned = $subject instanceof Model
                && (in_array(BelongsToPromotion::class, class_uses_recursive($subject), true)
                    || $subject instanceof EventMatch);

            return ! ($isPromotionOwned && $context->isEnforced() && ! $context->owns($subject));
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
            'user' => User::class,
        ]);

        Vite::macro('image', fn (string $asset) => Vite::asset("resources/media/{$asset}"));

        View::composer('components.topbar.profile', PromotionSwitcherComposer::class);

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
