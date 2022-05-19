<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Http\Controllers\Managers\ReleaseController;
use App\Http\Controllers\Managers\RestoreController;
use App\Http\Controllers\Managers\RetireController;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Controllers\Managers\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('managers', ManagersController::class);
Route::patch('managers/{manager}/restore', RestoreController::class)->name('managers.restore');
Route::patch('managers/{manager}/retire', RetireController::class)->name('managers.retire');
Route::patch('managers/{manager}/unretire', UnretireController::class)->name('managers.unretire');
Route::patch('managers/{manager}/injure', InjureController::class)->name('managers.injure');
Route::patch('managers/{manager}/clear-from-injury', ClearInjuryController::class)->name('managers.clear-from-injury');
Route::patch('managers/{manager}/employ', EmployController::class)->name('managers.employ');
Route::patch('managers/{manager}/suspend', SuspendController::class)->name('managers.suspend');
Route::patch('managers/{manager}/reinstate', ReinstateController::class)->name('managers.reinstate');
Route::patch('managers/{manager}/release', ReleaseController::class)->name('managers.release');
