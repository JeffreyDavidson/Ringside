<?php

declare(strict_types=1);

use App\Http\Controllers\Crew\CrewContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\EventBudgetController;
use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\EventLiveShowController;
use App\Http\Controllers\Events\EventPackController;
use App\Http\Controllers\Events\EventPosterController;
use App\Http\Controllers\Events\EventProductionSheetController;
use App\Http\Controllers\Events\EventPublicPageController;
use App\Http\Controllers\Events\EventSponsorController;
use App\Http\Controllers\Events\EventTalentBookingController;
use App\Http\Controllers\Events\PreviewEventPosterController;
use App\Http\Controllers\Events\PublicEventController;
use App\Http\Controllers\Events\ReleaseEventPublicationController;
use App\Http\Controllers\Events\UnpublishEventController;
use App\Http\Controllers\Managers\ManagerController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\Matches\EventMatchController;
use App\Http\Controllers\Promotions\AcceptPromotionInvitationController;
use App\Http\Controllers\Promotions\DeclinePromotionInvitationController;
use App\Http\Controllers\Promotions\PromotionController;
use App\Http\Controllers\Promotions\SwitchPromotionController;
use App\Http\Controllers\Referees\RefereeController;
use App\Http\Controllers\Roster\ShowRosterAvailabilityController;
use App\Http\Controllers\Sponsors\SponsorController;
use App\Http\Controllers\Stables\StableController;
use App\Http\Controllers\Storylines\StorylineController;
use App\Http\Controllers\TagTeams\TagTeamController;
use App\Http\Controllers\Titles\TitleController;
use App\Http\Controllers\Tournaments\TournamentController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Venues\VenueController;
use App\Http\Controllers\Wrestlers\WrestlerController;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Sponsors\Sponsor;
use App\Models\Storylines\Storyline;
use App\Models\Titles\Title;
use App\Models\Tournaments\Tournament;
use App\Models\Users\User;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', MarketingController::class)->name('home');
Route::get('shows/{publicId}/{slug}', PublicEventController::class)->whereUuid('publicId')->name('shows.show');
Route::get('shows/{publicId}/{slug}/results', PublicEventController::class)->whereUuid('publicId')->name('shows.results');
Route::get('shows/{publicId}/{slug}/poster/{asset}', EventPosterController::class)->whereUuid('publicId')->where('asset', '[a-f0-9-]{36}\\.png')->name('shows.poster');

Route::middleware('auth')->group(function () {
    Route::post('promotions/switch', SwitchPromotionController::class)->name('promotions.switch');
    // Outside promotion.context on purpose: a user with no active membership must still be able to reach them.
    Route::post('promotions/{promotion}/invitation/accept', AcceptPromotionInvitationController::class)
        ->missing(fn () => redirect()->route('dashboard')->with('error', __('promotions.invitation_unavailable')))
        ->name('promotions.invitation.accept');
    Route::post('promotions/{promotion}/invitation/decline', DeclinePromotionInvitationController::class)
        ->missing(fn () => redirect()->route('dashboard')->with('error', __('promotions.invitation_unavailable')))
        ->name('promotions.invitation.decline');

    Route::middleware('promotion.context')->group(function () {
        Route::get('promotions', [PromotionController::class, 'index'])
            ->can('viewAny', Promotion::class)
            ->name('promotions.index');
        Route::get('promotions/{promotion}', [PromotionController::class, 'show'])
            ->can('view', 'promotion')
            ->name('promotions.show');

        Route::get('tournaments', [TournamentController::class, 'index'])->can('viewAny', Tournament::class)->name('tournaments.index');
        Route::get('tournaments/{tournament}', [TournamentController::class, 'show'])->can('view', 'tournament')->name('tournaments.show');
        Route::get('storylines', [StorylineController::class, 'index'])->can('viewAny', Storyline::class)->name('storylines.index');
        Route::get('storylines/{storyline}', [StorylineController::class, 'show'])->can('view', 'storyline')->name('storylines.show');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('roster')->group(function () {
            Route::get('availability', ShowRosterAvailabilityController::class)->can('viewAny', Wrestler::class)->name('availability.index');
            Route::get('stables', [StableController::class, 'index'])->can('viewAny', Stable::class)->name('stables.index');
            Route::get('stables/{stable}', [StableController::class, 'show'])->can('view', 'stable')->name('stables.show');
            Route::get('wrestlers', [WrestlerController::class, 'index'])->can('viewAny', Wrestler::class)->name('wrestlers.index');
            Route::get('wrestlers/{wrestler}', [WrestlerController::class, 'show'])->can('view', 'wrestler')->name('wrestlers.show');
            Route::get('managers', [ManagerController::class, 'index'])->can('viewAny', Manager::class)->name('managers.index');
            Route::get('managers/{manager}', [ManagerController::class, 'show'])->can('view', 'manager')->name('managers.show');
            Route::get('referees', [RefereeController::class, 'index'])->can('viewAny', Referee::class)->name('referees.index');
            Route::get('referees/{referee}', [RefereeController::class, 'show'])->can('view', 'referee')->name('referees.show');
            Route::get('tag-teams', [TagTeamController::class, 'index'])->can('viewAny', TagTeam::class)->name('tag-teams.index');
            Route::get('tag-teams/{tagTeam}', [TagTeamController::class, 'show'])->can('view', 'tagTeam')->name('tag-teams.show');
        });

        Route::get('titles', [TitleController::class, 'index'])->can('viewAny', Title::class)->name('titles.index');
        Route::get('titles/{title}', [TitleController::class, 'show'])->can('view', 'title')->name('titles.show');

        Route::get('events/{event}/matches', [EventMatchController::class, 'index'])->can('viewAny', EventMatch::class)->name('events.matches.index');
        Route::get('sponsors', [SponsorController::class, 'index'])->can('manageSponsors', Sponsor::class)->name('sponsors.index');
        Route::get('sponsors/{sponsor}', [SponsorController::class, 'show'])->can('manageSponsors', 'sponsor')->name('sponsors.show');
        Route::get('production-crew', [CrewContactController::class, 'index'])->can('viewAny', CrewContact::class)->name('crew.contacts.index');
        Route::get('events/{event}/public-pages', [EventPublicPageController::class, 'edit'])->can('preparePublication', 'event')->name('events.public-pages.edit');
        Route::put('events/{event}/public-pages', [EventPublicPageController::class, 'update'])->can('preparePublication', 'event')->name('events.public-pages.update');
        Route::get('events/{event}/public-pages/preview', [EventPublicPageController::class, 'show'])->can('preparePublication', 'event')->name('events.public-pages.show');
        Route::get('events/{event}/public-pages/poster', PreviewEventPosterController::class)->can('uploadPublicationPoster', 'event')->name('events.public-pages.poster');
        Route::post('events/{event}/public-pages/publish', ReleaseEventPublicationController::class)->can('publishPublication', 'event')->name('events.public-pages.publish');
        Route::delete('events/{event}/public-pages/publish', UnpublishEventController::class)->can('unpublishPublication', 'event')->name('events.public-pages.unpublish');
        Route::get('events', [EventController::class, 'index'])->can('viewAny', Event::class)->name('events.index');
        Route::put('events/{event}/pack', [EventPackController::class, 'update'])->can('update', 'event')->name('events.pack.update');
        Route::get('events/{event}/pack', [EventPackController::class, 'show'])->can('view', 'event')->name('events.pack.show');
        Route::get('events/{event}/production-sheet', [EventProductionSheetController::class, 'show'])->can('viewProduction', 'event')->name('events.production-sheet.show');
        Route::get('events/{event}/live-show', [EventLiveShowController::class, 'show'])->can('view', 'event')->name('events.live-show.show');
        Route::get('events/{event}/budget', [EventBudgetController::class, 'show'])->can('viewBudget', 'event')->name('events.budget.show');
        Route::get('events/{event}/talent-bookings', [EventTalentBookingController::class, 'show'])->can('update', 'event')->name('events.talent-bookings.show');
        Route::get('events/{event}/sponsors', [EventSponsorController::class, 'show'])->can('manageSponsors', 'event')->withTrashed()->name('events.sponsors.show');
        Route::get('events/{event}', [EventController::class, 'show'])->can('view', 'event')->name('events.show');

        Route::get('venues', [VenueController::class, 'index'])->can('viewAny', Venue::class)->name('venues.index');
        Route::get('venues/{venue}', [VenueController::class, 'show'])->can('view', 'venue')->name('venues.show');

        Route::prefix('user-management')->group(function () {
            Route::get('users', [UserController::class, 'index'])->can('viewAny', User::class)->name('users.index');
            Route::get('users/{user}', [UserController::class, 'show'])->can('view', 'user')->name('users.show');
        });
    });
});
