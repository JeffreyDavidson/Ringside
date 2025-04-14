<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Users\UsersController;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::redirect('/', 'login');

Route::view('/test', 'test');

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth'])->prefix('roster')->group(function () {
    Route::resource('stables', StablesController::class)->only(['index', 'show']);
    Route::resource('wrestlers', WrestlersController::class)->only(['index', 'show']);
    Route::resource('managers', ManagersController::class)->only(['index', 'show']);
    Route::resource('referees', RefereesController::class)->only(['index', 'show']);
    Route::resource('tag-teams', TagTeamsController::class)->only(['index', 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('titles', TitlesController::class)->only(['index', 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('events/{event}/matches', [EventMatchesController::class, 'index'])->name('events.matches');
    Route::resource('events', EventsController::class)->only(['index', 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('venues', VenuesController::class)->only(['index', 'show']);
});

Route::middleware(['auth'])->prefix('user-management')->group(function () {
    Route::resource('users', UsersController::class)->only(['index', 'show']);
});

Route::prefix('docs')->group(function () {
    Route::view('buttons', 'docs.buttons')->name('docs.buttons');
});
