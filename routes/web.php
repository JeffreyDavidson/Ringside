<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\UnretireController;

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

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::prefix('roster')->group(function () {
        Route::resource('wrestlers', 'WrestlersController');
        Route::patch('/wrestlers/{wrestler}/restore', [WrestlersController::class, 'restore'])->name('wrestlers.restore');
        Route::post('/wrestlers/{wrestler}/retire', [WrestlerRetirementsController::class, 'store'])->name('wrestlers.retire');
        Route::delete('/wrestlers/{wrestler}/unretire', [WrestlerRetirementsController::class, 'destroy'])->name('wrestlers.unretire');
        Route::delete('/wrestlers/{wrestler}/reinstate', [WrestlerSuspensionsController::class, 'destroy'])->name('wrestlers.reinstate');
        Route::post('/wrestlers/{wrestler}/injure', [WrestlerInjuriesController::class, 'store'])->name('wrestlers.injure');
        Route::delete('/wrestlers/{wrestler}/recover', [WrestlerInjuriesController::class, 'destroy'])->name('wrestlers.recover');
        Route::post('/wrestlers/{wrestler}/deactivate', [WrestlerActivationsController::class, 'destroy'])->name('wrestlers.deactivate');
        Route::post('/wrestlers/{wrestler}/activate', [WrestlerActivationsController::class, 'store'])->name('wrestlers.activate');
        Route::post('/wrestlers/{wrestler}/suspend', [WrestlerSuspensionsController::class, 'store'])->name('wrestlers.suspend');
        Route::resource('tagteams', 'TagTeamsController');
        Route::patch('/tag-teams/{tagteam}/restore', [TagTeamsController::class, 'restore'])->name('tagteams.restore');
        Route::post('/tag-teams/{tagteam}/suspend', [TagTeamSuspensionsController::class, 'store'])->name('tagteams.suspend');
        Route::delete('/tag-teams/{tagteam}/reinstate', [TagTeamSuspensionsController::class, 'destroy'])->name('tagteams.reinstate');
        Route::post('/tag-teams/{tagteam}/deactivate', [TagTeamActivationsController::class, 'destroy'])->name('tagteams.deactivate');
        Route::post('/tag-teams/{tagteam}/activate', [TagTeamActivationsController::class, 'store'])->name('tagteams.activate');
        Route::post('/tag-teams/{tagteam}/retire', [TagTeamRetirementsController::class, 'store'])->name('tagteams.retire');
        Route::delete('/tag-teams/{tagteam}/unretire', [TagTeamRetirementsController::class, 'destroy'])->name('tagteams.unretire');
        Route::resource('managers', 'ManagersController');
        Route::patch('/managers/{manager}/restore', [ManagersController::class, 'restore'])->name('managers.restore');
        Route::post('/managers/{manager}/retire', [ManagerRetirementsController::class, 'store'])->name('managers.retire');
        Route::delete('/managers/{manager}/unretire', [ManagerRetirementsController::class, 'destroy'])->name('managers.unretire');
        Route::post('/managers/{manager}/injure', [ManagerInjuriesController::class, 'store'])->name('managers.injure');
        Route::delete('/managers/{manager}/recover', [ManagerInjuriesController::class, 'destroy'])->name('managers.recover');
        Route::post('/managers/{manager}/deactivate', [ManagerActivationsController::class, 'destroy'])->name('managers.deactivate');
        Route::post('/managers/{manager}/activate', [ManagerActivationsController::class, 'store'])->name('managers.activate');
        Route::post('/managers/{manager}/suspend', [ManagerSuspensionsController::class, 'store'])->name('managers.suspend');
        Route::delete('/managers/{manager}/reinstate', [ManagerSuspensionsController::class, 'destroy'])->name('managers.reinstate');
        Route::resource('referees', 'RefereesController');
        Route::patch('/referees/{referee}/restore', [RefereesController::class, 'restore'])->name('referees.restore');
        Route::post('/referees/{referee}/retire', [RefereeRetirementsController::class, 'store'])->name('referees.retire');
        Route::delete('/referees/{referee}/unretire', [RefereeRetirementsController::class, 'destroy'])->name('referees.unretire');
        Route::post('/referees/{referee}/injure', [RefereeInjuriesController::class, 'store'])->name('referees.injure');
        Route::delete('/referees/{referee}/recover', [RefereeInjuriesController::class, 'destroy'])->name('referees.recover');
        Route::post('/referees/{referee}/deactivate', [RefereeActivationsController::class, 'destroy'])->name('referees.deactivate');
        Route::post('/referees/{referee}/activate', [RefereeActivationsController::class, 'store'])->name('referees.activate');
        Route::resource('stables', StablesController::class);
        Route::patch('/stables/{stable}/restore', [StablesController::class, 'restore'])->name('stables.restore');
        Route::post('/stables/{stable}/retire', [StableRetirementsController::class, 'store'])->name('stables.retire');
        Route::delete('/stables/{stable}/unretire', [StableRetirementsController::class, 'destroy'])->name('stables.unretire');
        Route::post('/stables/{stable}/deactivate', [StableActivationsController::class, 'destroy'])->name('stables.deactivate');
        Route::post('/stables/{stable}/activate', [StableActivationsController::class, 'store'])->name('stables.activate');
        Route::post('/stables/{stable}/suspend', [StableSuspensionsController::class, 'store'])->name('stables.suspend');
        Route::delete('/stables/{stable}/reinstate', [StableSuspensionsController::class, 'destroy'])->name('stables.reinstate');
    });

    Route::resource('venues', 'VenuesController')->except('destroy');

    Route::resource('titles', TitlesController::class);
    Route::put('/titles/{title}/restore', RestoreController::class)->name('titles.restore');
    Route::put('/titles/{title}/retire', RetireController::class)->name('titles.retire');
    Route::put('/titles/{title}/unretire', UnretireController::class)->name('titles.unretire');
    Route::put('/titles/{title}/activate', ActivateController::class)->name('titles.activate');

    Route::resource('events', 'EventsController');
    Route::post('/events/{event}/archive', [ArchivedEventsController::class, 'store'])->name('events.archive');
    Route::patch('/events/{event}/restore', [EventsController::class, 'restore'])->name('events.restore');
});

Auth::routes();
