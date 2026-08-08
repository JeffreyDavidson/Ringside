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
        Route::get('stables', [StablesController::class, 'index'])->can('viewList', Stable::class)->name('stables.index');
        Route::get('stables/{stable}', [StablesController::class, 'show'])->can('view', 'stable')->name('stables.show');
        Route::get('wrestlers', [WrestlersController::class, 'index'])->can('viewList', Wrestler::class)->name('wrestlers.index');
        Route::get('wrestlers/{wrestler}', [WrestlersController::class, 'show'])->can('view', 'wrestler')->name('wrestlers.show');
        Route::get('managers', [ManagersController::class, 'index'])->can('viewList', Manager::class)->name('managers.index');
        Route::get('managers/{manager}', [ManagersController::class, 'show'])->can('view', 'manager')->name('managers.show');
        Route::get('referees', [RefereesController::class, 'index'])->can('viewList', Referee::class)->name('referees.index');
        Route::get('referees/{referee}', [RefereesController::class, 'show'])->can('view', 'referee')->name('referees.show');
        Route::get('tag-teams', [TagTeamsController::class, 'index'])->can('viewList', TagTeam::class)->name('tag-teams.index');
        Route::get('tag-teams/{tagTeam}', [TagTeamsController::class, 'show'])->can('view', 'tagTeam')->name('tag-teams.show');
    });

    Route::get('titles', [TitlesController::class, 'index'])->can('viewList', Title::class)->name('titles.index');
    Route::get('titles/{title}', [TitlesController::class, 'show'])->can('view', 'title')->name('titles.show');

    Route::get('events/{event}/matches', [EventMatchesController::class, 'index'])->can('viewList', EventMatch::class)->name('events.matches.index');
    Route::get('events', [EventsController::class, 'index'])->can('viewList', Event::class)->name('events.index');
    Route::get('events/{event}', [EventsController::class, 'show'])->can('view', 'event')->name('events.show');

    Route::get('venues', [VenuesController::class, 'index'])->can('viewList', Venue::class)->name('venues.index');
    Route::get('venues/{venue}', [VenuesController::class, 'show'])->can('view', 'venue')->name('venues.show');

    Route::prefix('user-management')->group(function () {
        Route::get('users', [UsersController::class, 'index'])->can('viewList', User::class)->name('users.index');
        Route::get('users/{user}', [UsersController::class, 'show'])->can('view', 'user')->name('users.show');
    });
});
