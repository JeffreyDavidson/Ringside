<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Matches\EventMatchesController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Controllers\Wrestlers\WrestlersController;
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
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('roster')->group(function () {
        Route::get('stables', [StablesController::class, 'index'])->can('viewAny', Stable::class)->name('stables.index');
        Route::get('stables/{stable}', [StablesController::class, 'show'])->can('view', 'stable')->name('stables.show');
        Route::get('wrestlers', [WrestlersController::class, 'index'])->can('viewAny', Wrestler::class)->name('wrestlers.index');
        Route::get('wrestlers/{wrestler}', [WrestlersController::class, 'show'])->can('view', 'wrestler')->name('wrestlers.show');
        Route::get('managers', [ManagersController::class, 'index'])->can('viewAny', Manager::class)->name('managers.index');
        Route::get('managers/{manager}', [ManagersController::class, 'show'])->can('view', 'manager')->name('managers.show');
        Route::get('referees', [RefereesController::class, 'index'])->can('viewAny', Referee::class)->name('referees.index');
        Route::get('referees/{referee}', [RefereesController::class, 'show'])->can('view', 'referee')->name('referees.show');
        Route::get('tag-teams', [TagTeamsController::class, 'index'])->can('viewAny', TagTeam::class)->name('tag-teams.index');
        Route::get('tag-teams/{tagTeam}', [TagTeamsController::class, 'show'])->can('view', 'tagTeam')->name('tag-teams.show');
    });

    Route::get('titles', [TitlesController::class, 'index'])->can('viewAny', Title::class)->name('titles.index');
    Route::get('titles/{title}', [TitlesController::class, 'show'])->can('view', 'title')->name('titles.show');

    Route::get('events/{event}/matches', [EventMatchesController::class, 'index'])->can('viewAny', EventMatch::class)->name('events.matches.index');
    Route::get('events', [EventsController::class, 'index'])->can('viewAny', Event::class)->name('events.index');
    Route::get('events/{event}', [EventsController::class, 'show'])->can('view', 'event')->name('events.show');

    Route::get('venues', [VenuesController::class, 'index'])->can('viewAny', Venue::class)->name('venues.index');
    Route::get('venues/{venue}', [VenuesController::class, 'show'])->can('view', 'venue')->name('venues.show');

    Route::prefix('user-management')->group(function () {
        Route::get('users', [UsersController::class, 'index'])->can('viewAny', User::class)->name('users.index');
        Route::get('users/{user}', [UsersController::class, 'show'])->can('view', 'user')->name('users.show');
    });
});
