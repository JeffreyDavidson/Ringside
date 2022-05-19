<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Controllers\Wrestlers\RestoreController;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use Illuminate\Support\Facades\Route;

Route::resource('wrestlers', WrestlersController::class);
Route::patch('wrestlers/{wrestler}/restore', RestoreController::class)->name('wrestlers.restore');
Route::patch('wrestlers/{wrestler}/employ', EmployController::class)->name('wrestlers.employ');
Route::patch('wrestlers/{wrestler}/release', ReleaseController::class)->name('wrestlers.release');
Route::patch('wrestlers/{wrestler}/retire', RetireController::class)->name('wrestlers.retire');
Route::patch('wrestlers/{wrestler}/unretire', UnretireController::class)->name('wrestlers.unretire');
Route::patch('wrestlers/{wrestler}/suspend', SuspendController::class)->name('wrestlers.suspend');
Route::patch('wrestlers/{wrestler}/reinstate', ReinstateController::class)->name('wrestlers.reinstate');
Route::patch('wrestlers/{wrestler}/injure', InjureController::class)->name('wrestlers.injure');
Route::patch('wrestlers/{wrestler}/clear-from-injury', ClearInjuryController::class)
    ->name('wrestlers.clear-from-injury');
