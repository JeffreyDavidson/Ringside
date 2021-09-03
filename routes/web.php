<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
});

/************************************************************************
 * Roster
 ************************************************************************/
Route::middleware(['auth'])->prefix('roster')->group(function () {
    Route::group([], __DIR__.'/web/stables.php');
    Route::group([], __DIR__.'/web/wrestlers.php');
    Route::group([], __DIR__.'/web/managers.php');
    Route::group([], __DIR__.'/web/referees.php');
    Route::group([], __DIR__.'/web/tagteams.php');
});

/************************************************************************
 * Titles
 ************************************************************************/
Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/titles.php');
});

/************************************************************************
 * Events
 ************************************************************************/
Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/events.php');
});

/************************************************************************
 * Venues
 ************************************************************************/
Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__.'/web/venues.php');
});
