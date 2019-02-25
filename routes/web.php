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

Route::middleware(['middleware' => 'auth'])->group(function() {
    Route::get('/wrestlers/create', 'WrestlersController@create')->name('wrestlers.create');
    Route::post('/wrestlers', 'WrestlersController@store')->name('wrestlers.store');
    Route::get('/wrestlers/state/{state?}', 'WrestlersController@index')->name('wrestlers.index');
    Route::get('/wrestlers/{wrestler}/edit', 'WrestlersController@edit')->name('wrestlers.edit');
    Route::patch('/wrestlers/{wrestler}', 'WrestlersController@update')->name('wrestlers.update');
    Route::delete('/wrestlers/{wrestler}', 'WrestlersController@destroy')->name('wrestlers.destroy');
    Route::get('/wrestlers/{wrestler}', 'WrestlersController@show')->name('wrestlers.show');
    Route::patch('/wrestlers/{wrestler}/restore', 'WrestlersController@restore')->name('wrestlers.restore');
    Route::post('/wrestlers/{wrestler}/retire', 'RetirementsController@store')->name('wrestlers.retire');
    Route::delete('/wrestlers/{wrestler}/unretire', 'RetirementsController@destroy')->name('wrestlers.unretire');
    Route::delete('/wrestlers/{wrestler}/reinstate', 'WrestlerSuspensionsController@destroy')->name('wrestlers.reinstate');
    Route::post('/wrestlers/{wrestler}/injure', 'InjuriesController@store')->name('wrestlers.injure');
    Route::delete('/wrestlers/{wrestler}/recover', 'InjuriesController@destroy')->name('wrestlers.recover');
    Route::post('/wrestlers/{wrestler}/deactivate', 'ActivationsController@destroy')->name('wrestlers.deactivate');
    Route::post('/wrestlers/{wrestler}/activate', 'ActivationsController@store')->name('wrestlers.activate');
    Route::post('/wrestlers/{wrestler}/suspend', 'WrestlerSuspensionsController@store')->name('wrestlers.suspend');
    Route::get('/tag-teams/create', 'TagTeamsController@create')->name('tagteams.create');
    Route::post('/tag-teams', 'TagTeamsController@store')->name('tagteams.store');
    Route::get('/tag-teams/{tagteam}/edit', 'TagTeamsController@edit')->name('tagteams.edit');
    Route::patch('/tag-teams/{tagteam}', 'TagTeamsController@update')->name('tagteams.update');
    Route::delete('/tag-teams/{tagteam}', 'TagTeamsController@destroy')->name('tagteams.destroy');
    Route::patch('/tag-teams/{tagteam}/restore', 'TagTeamsController@restore')->name('tagteams.restore');
    Route::post('/tag-teams/{tagteam}/suspend', 'TagTeamSuspensionsController@store')->name('tagteams.suspend');
    Route::delete('/tag-teams/{tagteam}/reinstate', 'TagTeamSuspensionsController@destroy')->name('tagteams.reinstate');
    Route::post('/tag-teams/{tagteam}/deactivate', 'TagTeamActivationsController@destroy')->name('tagteams.deactivate');
    Route::post('/tag-teams/{tagteam}/activate', 'TagTeamActivationsController@store')->name('tagteams.activate');
    Route::post('/tag-teams/{tagteam}/retire', 'TagTeamRetirementsController@store')->name('tagteams.retire');
    Route::delete('/tag-teams/{tagteam}/unretire', 'TagTeamRetirementsController@destroy')->name('tagteams.unretire');
    Route::get('/tag-teams/state/{state?}', 'TagTeamsController@index')->name('tagteams.index');
    Route::get('/tag-teams/{tagteam}', 'TagTeamsController@show')->name('tagteams.show');
    Route::get('/managers/create', 'ManagersController@create')->name('managers.create');
    Route::post('/managers', 'ManagersController@store')->name('managers.store');
    Route::get('/managers/{manager}/edit', 'ManagersController@edit')->name('managers.edit');
    Route::patch('/managers/{manager}', 'ManagersController@update')->name('managers.update');
    Route::delete('/managers/{manager}', 'ManagersController@destroy')->name('managers.destroy');
    Route::patch('/managers/{manager}/restore', 'ManagersController@restore')->name('managers.restore');
    Route::post('/managers/{manager}/retire', 'ManagerRetirementsController@store')->name('managers.retire');
    Route::delete('/managers/{manager}/unretire', 'ManagerRetirementsController@destroy')->name('managers.unretire');
    Route::post('/managers/{manager}/injure', 'ManagerInjuriesController@store')->name('managers.injure');
    Route::delete('/managers/{manager}/recover', 'ManagerInjuriesController@destroy')->name('managers.recover');
    Route::post('/managers/{manager}/deactivate', 'ManagerActivationsController@destroy')->name('managers.deactivate');
    Route::post('/managers/{manager}/activate', 'ManagerActivationsController@store')->name('managers.activate');
    Route::post('/managers/{manager}/suspend', 'ManagerSuspensionsController@store')->name('managers.suspend');
    Route::delete('/managers/{manager}/reinstate', 'ManagerSuspensionsController@destroy')->name('managers.reinstate');
    Route::get('/managers/state/{state?}', 'ManagersController@index')->name('managers.index');
    Route::get('/managers/{manager}', 'ManagersController@show')->name('managers.show');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
