<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RetireController;
use App\Http\Controllers\Referees\RestoreController;
use App\Http\Controllers\Referees\SuspendController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\UnretireController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Http\Controllers\Referees\ClearInjuryController;

Route::resource('referees', RefereesController::class);
Route::put('/referees/{referee}/restore', RestoreController::class)->name('referees.restore');
Route::put('/referees/{referee}/retire', RetireController::class)->name('referees.retire');
Route::put('/referees/{referee}/unretire', UnretireController::class)->name('referees.unretire');
Route::put('/referees/{referee}/suspend', SuspendController::class)->name('referees.suspend');
Route::put('/referees/{referee}/reinstate', ReinstateController::class)->name('referees.reinstate');
Route::put('/referees/{referee}/injure', InjureController::class)->name('referees.injure');
Route::put('/referees/{referee}/clear-from-injury', ClearInjuryController::class)->name('referees.clear-from-injury');
Route::put('/referees/{referee}/employ', EmployController::class)->name('referees.employ');
