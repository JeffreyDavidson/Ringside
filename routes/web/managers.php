<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\RetireController;
use App\Http\Controllers\Managers\RestoreController;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\UnretireController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Http\Controllers\Managers\ClearInjuryController;

Route::resource('managers', ManagersController::class);
Route::put('/managers/{manager}/restore', RestoreController::class)->name('managers.restore');
Route::put('/managers/{manager}/retire', RetireController::class)->name('managers.retire');
Route::put('/managers/{manager}/unretire', UnretireController::class)->name('managers.unretire');
Route::put('/managers/{manager}/injure', InjureController::class)->name('managers.injure');
Route::put('/managers/{manager}/clear-from-injury', ClearInjuryController::class)->name('managers.clear-from-injury');
Route::put('/managers/{manager}/employ', EmployController::class)->name('managers.employ');
Route::put('/managers/{manager}/suspend', SuspendController::class)->name('managers.suspend');
Route::put('/managers/{manager}/reinstate', ReinstateController::class)->name('managers.reinstate');
