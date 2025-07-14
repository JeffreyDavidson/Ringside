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
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::redirect('/', 'login');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('roster')->group(function () {
        Route::get('stables', StablesIndexController::class)->name('stables.index');
        Route::get('stables/{stable}', StablesShowController::class)->name('stables.show');
        Route::get('wrestlers', WrestlersIndexController::class)->name('wrestlers.index');
        Route::get('wrestlers/{wrestler}', WrestlersShowController::class)->name('wrestlers.show');
        Route::get('managers', ManagersIndexController::class)->name('managers.index');
        Route::get('managers/{manager}', ManagersShowController::class)->name('managers.show');
        Route::get('referees', RefereesIndexController::class)->name('referees.index');
        Route::get('referees/{referee}', RefereesShowController::class)->name('referees.show');
        Route::get('tag-teams', TagTeamsIndexController::class)->name('tag-teams.index');
        Route::get('tag-teams/{tagTeam}', TagTeamsShowController::class)->name('tag-teams.show');
    });

    Route::get('titles', TitlesIndexController::class)->name('titles.index');
    Route::get('titles/{title}', TitlesShowController::class)->name('titles.show');

    Route::get('events/{event}/matches', MatchesIndexController::class)->name('events.matches');
    Route::get('events', EventsIndexController::class)->name('events.index');
    Route::get('events/{event}', EventsShowController::class)->name('events.show');

    Route::get('venues', VenuesIndexController::class)->name('venues.index');
    Route::get('venues/{venue}', VenuesShowController::class)->name('venues.show');

    Route::prefix('user-management')->group(function () {
        Route::get('users', UsersIndexController::class)->name('users.index');
        Route::get('users/{user}', UsersShowController::class)->name('users.show');
    });

    Route::prefix('docs')->group(function () {
        Route::view('buttons', 'docs.buttons')->name('docs.buttons');
    });
});
