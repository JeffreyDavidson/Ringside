<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::redirect('/', 'login');

Route::view('/test', 'test');

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth'])->prefix('roster')->group(function () {
    Route::group([], __DIR__.'/web/stables.php');
    Route::resource('wrestlers', WrestlersController::class)->only(['index', 'show']);
    Route::group([], __DIR__.'/web/managers.php');
    Route::group([], __DIR__.'/web/referees.php');
    Route::resource('tag-teams', TagTeamsController::class)->only(['index', 'show']);
});

Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/titles.php');
});

Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/events.php');
});

Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/venues.php');
});

Route::middleware(['auth'])->prefix('user-management')->group(function () {
    Route::group([], __DIR__.'/web/users.php');
});

Route::prefix('docs')->group(function () {
    Route::view('buttons', 'docs.buttons')->name('docs.buttons');
});
