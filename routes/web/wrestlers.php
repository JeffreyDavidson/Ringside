<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Wrestlers\RecoverController;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\ActivateController;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\RestoreController;

Route::resource('wrestlers', WrestlersController::class);
Route::put('/wrestlers/{wrestler}/restore', RestoreController::class)->name('wrestlers.restore');
Route::put('/wrestlers/{wrestler}/activate', ActivateController::class)->name('wrestlers.activate');
Route::put('/wrestlers/{wrestler}/retire', RetireController::class)->name('wrestlers.retire');
Route::put('/wrestlers/{wrestler}/unretire', UnretireController::class)->name('wrestlers.unretire');
Route::put('/wrestlers/{wrestler}/suspend', SuspendController::class)->name('wrestlers.suspend');
Route::put('/wrestlers/{wrestler}/reinstate', ReinstateController::class)->name('wrestlers.reinstate');
Route::put('/wrestlers/{wrestler}/injure', InjureController::class)->name('wrestlers.injure');
Route::put('/wrestlers/{wrestler}/recover', RecoverController::class)->name('wrestlers.recover');
