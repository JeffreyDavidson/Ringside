<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
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
use Livewire\Livewire;

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
        Livewire::addPersistentMiddleware([EnsureUserIsActive::class]);

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $subject = $arguments[0] ?? null;
            $context = app(PromotionContextService::class);

            if (! $user->role->isAdministrator()) {
                if ($subject instanceof Promotion) {
                    $membership = $subject->memberships()
                        ->where('user_id', $user->getKey())
                        ->where('status', MembershipStatus::Active)
                        ->first();

                    if ($membership === null) {
                        return false;
                    }

                    return match ($ability) {
                        'view' => true,
                        'manageMembers', 'update' => $membership->role === MembershipRole::Owner,
                        default => false,
                    };
                }

                $isPromotionOwnedClass = is_string($subject)
                    && class_exists($subject)
                    && ($subject === EventMatch::class
                        || in_array(BelongsToPromotion::class, class_uses_recursive($subject), true));

                $isPromotionOwnedModel = $subject instanceof Model
                    && (in_array(BelongsToPromotion::class, class_uses_recursive($subject), true)
                        || $subject instanceof EventMatch);

                if (! $isPromotionOwnedClass && ! $isPromotionOwnedModel) {
                    return null;
                }

                if (! $context->isEnforced()) {
                    return null;
                }

                $promotion = $context->current();

                if (! $promotion instanceof Promotion) {
                    return false;
                }

                if ($subject instanceof Model && ! $context->owns($subject)) {
                    return false;
                }

                $membership = $promotion->memberships()
                    ->where('user_id', $user->getKey())
                    ->where('status', MembershipStatus::Active)
                    ->first();

                return $membership?->role->allows($ability) ?? false;
            }

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

        View::composer([
            'components.topbar.profile',
            'components.sidebar',
            'components.sidebar.index',
            'components.layouts.partials.header',
        ], PromotionSwitcherComposer::class);

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
