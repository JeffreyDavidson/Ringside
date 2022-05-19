<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\ActivateController;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\Stables\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('stables', StablesController::class);
Route::patch('roster/stables/{stable}/restore', RestoreController::class)->name('stables.restore');
Route::patch('roster/stables/{stable}/retire', RetireController::class)->name('stables.retire');
Route::patch('roster/stables/{stable}/unretire', UnretireController::class)->name('stables.unretire');
Route::patch('roster/stables/{stable}/activate', ActivateController::class)->name('stables.activate');
Route::patch('roster/stables/{stable}/deactivate', DeactivateController::class)->name('stables.deactivate');
