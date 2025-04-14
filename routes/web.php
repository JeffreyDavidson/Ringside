<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventMatchesController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\ManagersController;
use App\Http\Controllers\RefereesController;
use App\Http\Controllers\StablesController;
use App\Http\Controllers\TagTeamsController;
use App\Http\Controllers\TitlesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VenuesController;
use App\Http\Controllers\WrestlersController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::redirect('/', 'login');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('roster')->group(function () {
        Route::resource('stables', StablesController::class)->only(['index', 'show']);
        Route::resource('wrestlers', WrestlersController::class)->only(['index', 'show']);
        Route::resource('managers', ManagersController::class)->only(['index', 'show']);
        Route::resource('referees', RefereesController::class)->only(['index', 'show']);
        Route::resource('tag-teams', TagTeamsController::class)->only(['index', 'show']);
    });

    Route::resource('titles', TitlesController::class)->only(['index', 'show']);

    Route::get('events/{event}/matches', [EventMatchesController::class, 'index'])->name('events.matches');
    Route::resource('events', EventsController::class)->only(['index', 'show']);

    Route::resource('venues', VenuesController::class)->only(['index', 'show']);

    Route::prefix('user-management')->group(function () {
        Route::resource('users', UsersController::class)->only(['index', 'show']);
    });

    Route::prefix('docs')->group(function () {
        Route::view('buttons', 'docs.buttons')->name('docs.buttons');
    });
});
