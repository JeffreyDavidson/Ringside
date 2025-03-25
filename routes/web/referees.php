<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Http\Controllers\Referees\ReleaseController;
use App\Http\Controllers\Referees\RestoreController;
use App\Http\Controllers\Referees\RetireController;
use App\Http\Controllers\Referees\SuspendController;
use App\Http\Controllers\Referees\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('referees', RefereesController::class)->only(['index', 'show']);
Route::patch('referees/{referee}/restore', RestoreController::class)->name('referees.restore');
Route::patch('referees/{referee}/retire', RetireController::class)->name('referees.retire');
Route::patch('referees/{referee}/release', ReleaseController::class)->name('referees.release');
Route::patch('referees/{referee}/unretire', UnretireController::class)->name('referees.unretire');
Route::patch('referees/{referee}/suspend', SuspendController::class)->name('referees.suspend');
Route::patch('referees/{referee}/reinstate', ReinstateController::class)->name('referees.reinstate');
Route::patch('referees/{referee}/injure', InjureController::class)->name('referees.injure');
Route::patch('referees/{referee}/clear-from-injury', ClearInjuryController::class)->name('referees.clear-from-injury');
Route::patch('referees/{referee}/employ', EmployController::class)->name('referees.employ');
