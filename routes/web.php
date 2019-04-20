<?php

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
    Route::get('/dashboard', 'DashboardController@show')->name('dashboard');
    Route::namespace('Wrestlers')->group(function () {
        Route::get('/wrestlers/state/{state?}', 'WrestlersController@index')->name('wrestlers.index');
        Route::resource('wrestlers', 'WrestlersController')->except('index');
        Route::patch('/wrestlers/{wrestler}/restore', 'WrestlersController@restore')->name('wrestlers.restore');
        Route::post('/wrestlers/{wrestler}/retire', 'WrestlerRetirementsController@store')->name('wrestlers.retire');
        Route::delete('/wrestlers/{wrestler}/unretire', 'WrestlerRetirementsController@destroy')->name('wrestlers.unretire');
        Route::delete('/wrestlers/{wrestler}/reinstate', 'WrestlerSuspensionsController@destroy')->name('wrestlers.reinstate');
        Route::post('/wrestlers/{wrestler}/injure', 'WrestlerInjuriesController@store')->name('wrestlers.injure');
        Route::delete('/wrestlers/{wrestler}/recover', 'WrestlerInjuriesController@destroy')->name('wrestlers.recover');
        Route::post('/wrestlers/{wrestler}/deactivate', 'WrestlerActivationsController@destroy')->name('wrestlers.deactivate');
        Route::post('/wrestlers/{wrestler}/activate', 'WrestlerActivationsController@store')->name('wrestlers.activate');
        Route::post('/wrestlers/{wrestler}/suspend', 'WrestlerSuspensionsController@store')->name('wrestlers.suspend');
    });
    Route::namespace('TagTeams')->group(function () {
        Route::get('/tag-teams/state/{state?}', 'TagTeamsController@index')->name('tagteams.index');
        Route::resource('tagteams', 'TagTeamsController')->except('index');
        Route::patch('/tag-teams/{tagteam}/restore', 'TagTeamsController@restore')->name('tagteams.restore');
        Route::post('/tag-teams/{tagteam}/suspend', 'TagTeamSuspensionsController@store')->name('tagteams.suspend');
        Route::delete('/tag-teams/{tagteam}/reinstate', 'TagTeamSuspensionsController@destroy')->name('tagteams.reinstate');
        Route::post('/tag-teams/{tagteam}/deactivate', 'TagTeamActivationsController@destroy')->name('tagteams.deactivate');
        Route::post('/tag-teams/{tagteam}/activate', 'TagTeamActivationsController@store')->name('tagteams.activate');
        Route::post('/tag-teams/{tagteam}/retire', 'TagTeamRetirementsController@store')->name('tagteams.retire');
        Route::delete('/tag-teams/{tagteam}/unretire', 'TagTeamRetirementsController@destroy')->name('tagteams.unretire');
    });
    Route::namespace('Managers')->group(function () {
        Route::get('/managers/state/{state?}', 'ManagersController@index')->name('managers.index');
        Route::resource('managers', 'ManagersController')->except('index');
        Route::patch('/managers/{manager}/restore', 'ManagersController@restore')->name('managers.restore');
        Route::post('/managers/{manager}/retire', 'ManagerRetirementsController@store')->name('managers.retire');
        Route::delete('/managers/{manager}/unretire', 'ManagerRetirementsController@destroy')->name('managers.unretire');
        Route::post('/managers/{manager}/injure', 'ManagerInjuriesController@store')->name('managers.injure');
        Route::delete('/managers/{manager}/recover', 'ManagerInjuriesController@destroy')->name('managers.recover');
        Route::post('/managers/{manager}/deactivate', 'ManagerActivationsController@destroy')->name('managers.deactivate');
        Route::post('/managers/{manager}/activate', 'ManagerActivationsController@store')->name('managers.activate');
        Route::post('/managers/{manager}/suspend', 'ManagerSuspensionsController@store')->name('managers.suspend');
        Route::delete('/managers/{manager}/reinstate', 'ManagerSuspensionsController@destroy')->name('managers.reinstate');
    });
    Route::namespace('Referees')->group(function () {
        Route::resource('referees', 'RefereesController');
        Route::patch('/referees/{referee}/restore', 'RefereesController@restore')->name('referees.restore');
        Route::post('/referees/{referee}/retire', 'RefereeRetirementsController@store')->name('referees.retire');
        Route::delete('/referees/{referee}/unretire', 'RefereeRetirementsController@destroy')->name('referees.unretire');
        Route::post('/referees/{referee}/injure', 'RefereeInjuriesController@store')->name('referees.injure');
        Route::delete('/referees/{referee}/recover', 'RefereeInjuriesController@destroy')->name('referees.recover');
        Route::post('/referees/{referee}/deactivate', 'RefereeActivationsController@destroy')->name('referees.deactivate');
        Route::post('/referees/{referee}/activate', 'RefereeActivationsController@store')->name('referees.activate');
    });

    Route::namespace('Stables')->group(function () {
        Route::get('/stables/state/{state?}', 'StablesController@index')->name('stables.index');
        Route::resource('stables', 'StablesController')->except('index');
        Route::patch('/stables/{stable}/restore', 'StablesController@restore')->name('stables.restore');
        Route::post('/stables/{stable}/retire', 'StableRetirementsController@store')->name('stables.retire');
        Route::delete('/stables/{stable}/unretire', 'StableRetirementsController@destroy')->name('stables.unretire');
        Route::post('/stables/{stable}/deactivate', 'StableActivationsController@destroy')->name('stables.deactivate');
        Route::post('/stables/{stable}/activate', 'StableActivationsController@store')->name('stables.activate');
        Route::post('/stables/{stable}/suspend', 'StableSuspensionsController@store')->name('stables.suspend');
        Route::delete('/stables/{stable}/reinstate', 'StableSuspensionsController@destroy')->name('stables.reinstate');
    });

    Route::namespace('Venues')->group(function () {
        Route::resource('venues', 'VenuesController')->except('destroy');
    });

    Route::namespace('Titles')->group(function () {
        Route::get('/titles/state/{state?}', 'TitlesController@index')->name('titles.index');
        Route::resource('titles', 'TitlesController')->except('index');
        Route::patch('/titles/{title}/restore', 'TitlesController@restore')->name('titles.restore');
        Route::post('/titles/{title}/retire', 'TitleRetirementsController@store')->name('titles.retire');
        Route::delete('/titles/{title}/unretire', 'TitleRetirementsController@destroy')->name('titles.unretire');
        Route::post('/titles/{title}/deactivate', 'TitleActivationsController@destroy')->name('titles.deactivate');
        Route::post('/titles/{title}/activate', 'TitleActivationsController@store')->name('titles.activate');
    });

    Route::namespace('Events')->group(function () {
        Route::get('/events/state/{state?}', 'EventsController@index')->name('events.index');
        Route::resource('events', 'EventsController')->except('index');
        Route::post('/events/{event}/archive', 'ArchivedEventsController@store')->name('events.archive');
        Route::patch('/events/{event}/restore', 'EventsController@restore')->name('events.restore');
    });
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
