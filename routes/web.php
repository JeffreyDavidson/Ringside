<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';
Auth::loginUsingId(1);

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth'])->prefix('roster')->group(function () {
    Route::group([], __DIR__.'/web/stables.php');
    Route::group([], __DIR__.'/web/wrestlers.php');
    Route::group([], __DIR__.'/web/managers.php');
    Route::group([], __DIR__.'/web/referees.php');
    Route::group([], __DIR__.'/web/tagteams.php');
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
