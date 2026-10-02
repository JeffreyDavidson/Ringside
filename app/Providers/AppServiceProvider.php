<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EstablishPromotionContext;
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
use App\Policies\PromotionGate;
use App\Services\Promotions\PromotionContextService;
use App\Support\Auth\CaseInsensitiveEmailUserProvider;
use App\View\Composers\PromotionSwitcherComposer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->scoped(PromotionContextService::class);

        $this->registerLegacyRosterModelAliases();
        $this->registerCaseInsensitiveUserProvider();
    }

    private function registerCaseInsensitiveUserProvider(): void
    {
        Auth::provider('eloquent-email', fn (Application $app, array $config): CaseInsensitiveEmailUserProvider => new CaseInsensitiveEmailUserProvider(
            $app->make('hash'),
            $config['model'],
        ));
    }

    /** Merged migrations import the old model class names and must never be edited, so keep these aliases. */
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
        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
            EstablishPromotionContext::class,
        ]);

        Password::defaults(fn (): Password => Password::min(12));

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::before(fn (User $user, string $ability, array $arguments): ?bool => app(PromotionGate::class)->before($user, $ability, $arguments));

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

        View::composer([
            'components.topbar.profile',
            'components.sidebar.index',
            'components.layouts.partials.header',
        ], PromotionSwitcherComposer::class);
    }
}
