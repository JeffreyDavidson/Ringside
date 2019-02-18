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
    Route::post('/wrestlers/{wrestler}/suspend', 'SuspensionsController@store')->name('wrestlers.suspend');
    Route::delete('/wrestlers/{wrestler}/reinstate', 'SuspensionsController@destroy')->name('wrestlers.reinstate');
    Route::post('/wrestlers/{wrestler}/injure', 'InjuriesController@store')->name('wrestlers.injure');
    Route::delete('/wrestlers/{wrestler}/recover', 'InjuriesController@destroy')->name('wrestlers.recover');
    Route::post('/wrestlers/{wrestler}/deactivate', 'ActivationsController@destroy')->name('wrestlers.deactivate');
    Route::post('/wrestlers/{wrestler}/activate', 'ActivationsController@store')->name('wrestlers.activate');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
