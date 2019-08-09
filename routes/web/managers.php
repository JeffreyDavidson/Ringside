<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\RetireController;
use App\Http\Controllers\Managers\RecoverController;
use App\Http\Controllers\Managers\RestoreController;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Controllers\Managers\ActivateController;
use App\Http\Controllers\Managers\UnretireController;
use App\Http\Controllers\Managers\ReinstateController;

Route::resource('managers', ManagersController::class);
Route::put('/managers/{manager}/restore', RestoreController::class)->name('managers.restore');
Route::put('/managers/{manager}/retire', RetireController::class)->name('managers.retire');
Route::put('/managers/{manager}/unretire', UnretireController::class)->name('managers.unretire');
Route::put('/managers/{manager}/injure', InjureController::class)->name('managers.injure');
Route::put('/managers/{manager}/recover', RecoverController::class)->name('managers.recover');
Route::put('/managers/{manager}/activate', ActivateController::class)->name('managers.activate');
Route::put('/managers/{manager}/suspend', SuspendController::class)->name('managers.suspend');
Route::put('/managers/{manager}/reinstate', ReinstateController::class)->name('managers.reinstate');
