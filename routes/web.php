<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\IndexController as EventsIndexController;
use App\Http\Controllers\Events\ShowController as EventsShowController;
use App\Http\Controllers\Managers\IndexController as ManagersIndexController;
use App\Http\Controllers\Managers\ShowController as ManagersShowController;
use App\Http\Controllers\Matches\IndexController as MatchesIndexController;
use App\Http\Controllers\Referees\IndexController as RefereesIndexController;
use App\Http\Controllers\Referees\ShowController as RefereesShowController;
use App\Http\Controllers\Stables\IndexController as StablesIndexController;
use App\Http\Controllers\Stables\ShowController as StablesShowController;
use App\Http\Controllers\TagTeams\IndexController as TagTeamsIndexController;
use App\Http\Controllers\TagTeams\ShowController as TagTeamsShowController;
use App\Http\Controllers\Titles\IndexController as TitlesIndexController;
use App\Http\Controllers\Titles\ShowController as TitlesShowController;
use App\Http\Controllers\Users\IndexController as UsersIndexController;
use App\Http\Controllers\Users\ShowController as UsersShowController;
use App\Http\Controllers\Venues\IndexController as VenuesIndexController;
use App\Http\Controllers\Venues\ShowController as VenuesShowController;
use App\Http\Controllers\Wrestlers\IndexController as WrestlersIndexController;
use App\Http\Controllers\Wrestlers\ShowController as WrestlersShowController;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Managers\Manager;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('roster')->group(function () {
        Route::get('stables', StablesIndexController::class)->can('viewList', Stable::class)->name('stables.index');
        Route::get('stables/{stable}', StablesShowController::class)->can('view', 'stable')->name('stables.show');
        Route::get('wrestlers', WrestlersIndexController::class)->can('viewList', Wrestler::class)->name('wrestlers.index');
        Route::get('wrestlers/{wrestler}', WrestlersShowController::class)->can('view', 'wrestler')->name('wrestlers.show');
        Route::get('managers', ManagersIndexController::class)->can('viewList', Manager::class)->name('managers.index');
        Route::get('managers/{manager}', ManagersShowController::class)->can('view', 'manager')->name('managers.show');
        Route::get('referees', RefereesIndexController::class)->can('viewList', Referee::class)->name('referees.index');
        Route::get('referees/{referee}', RefereesShowController::class)->can('view', 'referee')->name('referees.show');
        Route::get('tag-teams', TagTeamsIndexController::class)->can('viewList', TagTeam::class)->name('tag-teams.index');
        Route::get('tag-teams/{tagTeam}', TagTeamsShowController::class)->can('view', 'tagTeam')->name('tag-teams.show');
    });

    Route::get('titles', TitlesIndexController::class)->can('viewList', Title::class)->name('titles.index');
    Route::get('titles/{title}', TitlesShowController::class)->can('view', 'title')->name('titles.show');

    Route::get('events/{event}/matches', MatchesIndexController::class)->can('viewList', EventMatch::class)->name('events.matches');
    Route::get('events', EventsIndexController::class)->can('viewList', Event::class)->name('events.index');
    Route::get('events/{event}', EventsShowController::class)->can('view', 'event')->name('events.show');

    Route::get('venues', VenuesIndexController::class)->can('viewList', Venue::class)->name('venues.index');
    Route::get('venues/{venue}', VenuesShowController::class)->can('view', 'venue')->name('venues.show');

    Route::prefix('user-management')->group(function () {
        Route::get('users', UsersIndexController::class)->can('viewList', User::class)->name('users.index');
        Route::get('users/{user}', UsersShowController::class)->can('view', 'user')->name('users.show');
    });
});
