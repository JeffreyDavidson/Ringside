<?php
// Auth::loginUsingId(1);
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\UnretireController;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\Wrestlers\RecoverController;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Controllers\Managers\InjureController as ManagerInjureController;
use App\Http\Controllers\Managers\RetireController as ManagerRetireController;
use App\Http\Controllers\Referees\InjureController as RefereeInjureController;
use App\Http\Controllers\Referees\RetireController as RefereeRetireController;
use App\Http\Controllers\TagTeams\RetireController as TagTeamRetireController;
use App\Http\Controllers\Managers\RecoverController as ManagerRecoverController;
use App\Http\Controllers\Managers\RestoreController as ManagerRestoreController;
use App\Http\Controllers\Managers\SuspendController as ManagerSuspendController;
use App\Http\Controllers\Referees\RecoverController as RefereeRecoverController;
use App\Http\Controllers\Referees\RestoreController as RefereeRestoreController;
use App\Http\Controllers\TagTeams\RestoreController as TagTeamRestoreController;
use App\Http\Controllers\TagTeams\SuspendController as TagTeamSuspendController;
use App\Http\Controllers\Wrestlers\RetireController as WrestlerRetireController;
use App\Http\Controllers\Managers\ActivateController as ManagerActivateController;
use App\Http\Controllers\Managers\UnretireController as ManagerUnretireController;
use App\Http\Controllers\Referees\ActivateController as RefereeActivateController;
use App\Http\Controllers\Referees\UnretireController as RefereeUnretireController;
use App\Http\Controllers\TagTeams\ActivateController as TagTeamActivateController;
use App\Http\Controllers\TagTeams\UnretireController as TagTeamUnretireController;
use App\Http\Controllers\Wrestlers\RestoreController as WrestlerRestoreController;
use App\Http\Controllers\Managers\ReinstateController as ManagerReinstateController;
use App\Http\Controllers\TagTeams\ReinstateController as TagTeamReinstateController;
use App\Http\Controllers\Wrestlers\ActivateController as WrestlerActivateController;
use App\Http\Controllers\Wrestlers\UnretireController as WrestlerUnretireController;

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
        Route::resource('wrestlers', WrestlersController::class);
        Route::put('/wrestlers/{wrestler}/restore', WrestlerRestoreController::class)->name('wrestlers.restore');
        Route::put('/wrestlers/{wrestler}/activate', WrestlerActivateController::class)->name('wrestlers.activate');
        Route::put('/wrestlers/{wrestler}/retire', WrestlerRetireController::class)->name('wrestlers.retire');
        Route::put('/wrestlers/{wrestler}/unretire', WrestlerUnretireController::class)->name('wrestlers.unretire');
        Route::put('/wrestlers/{wrestler}/suspend', SuspendController::class)->name('wrestlers.suspend');
        Route::put('/wrestlers/{wrestler}/reinstate', ReinstateController::class)->name('wrestlers.reinstate');
        Route::put('/wrestlers/{wrestler}/injure', InjureController::class)->name('wrestlers.injure');
        Route::put('/wrestlers/{wrestler}/recover', RecoverController::class)->name('wrestlers.recover');
        Route::resource('tagteams', TagTeamsController::class);
        Route::put('/tag-teams/{tagteam}/restore', TagTeamRestoreController::class)->name('tagteams.restore');
        Route::put('/tag-teams/{tagteam}/suspend', TagTeamSuspendController::class)->name('tagteams.suspend');
        Route::put('/tag-teams/{tagteam}/reinstate', TagTeamReinstateController::class)->name('tagteams.reinstate');
        Route::put('/tag-teams/{tagteam}/activate', TagTeamActivateController::class)->name('tagteams.activate');
        Route::put('/tag-teams/{tagteam}/retire', TagTeamRetireController::class)->name('tagteams.retire');
        Route::put('/tag-teams/{tagteam}/unretire', TagTeamUnretireController::class)->name('tagteams.unretire');
        Route::resource('managers', ManagersController::class);
        Route::put('/managers/{manager}/restore', ManagerRestoreController::class)->name('managers.restore');
        Route::put('/managers/{manager}/retire', ManagerRetireController::class)->name('managers.retire');
        Route::put('/managers/{manager}/unretire', ManagerUnretireController::class)->name('managers.unretire');
        Route::put('/managers/{manager}/injure', ManagerInjureController::class)->name('managers.injure');
        Route::put('/managers/{manager}/recover', ManagerRecoverController::class)->name('managers.recover');
        Route::put('/managers/{manager}/activate', ManagerActivateController::class)->name('managers.activate');
        Route::put('/managers/{manager}/suspend', ManagerSuspendController::class)->name('managers.suspend');
        Route::put('/managers/{manager}/reinstate', ManagerReinstateController::class)->name('managers.reinstate');
        Route::resource('referees', RefereesController::class);
        Route::put('/referees/{referee}/restore', RefereeRestoreController::class)->name('referees.restore');
        Route::put('/referees/{referee}/retire', RefereeRetireController::class)->name('referees.retire');
        Route::put('/referees/{referee}/unretire', RefereeUnretireController::class)->name('referees.unretire');
        Route::put('/referees/{referee}/injure', RefereeInjureController::class)->name('referees.injure');
        Route::put('/referees/{referee}/recover', RefereeRecoverController::class)->name('referees.recover');
        Route::put('/referees/{referee}/activate', RefereeActivateController::class)->name('referees.activate');
    });

    Route::resource('venues', VenuesController::class)->except('destroy');

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

/************************************************************************
 * Only Roster
 ************************************************************************/
Route::middleware(['auth'])->group(function () {
    Route::group([], __DIR__ . '/web/stables.php');
});
