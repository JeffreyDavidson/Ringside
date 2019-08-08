<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\UnretireController;
use App\Http\Controllers\Stables\ActivateController;

Route::resource('stables', StablesController::class);
Route::get('/roster/stables', [StablesController::class, 'index'])->name('roster.stables.index');
Route::get('/roster/stables/create', [StablesController::class, 'create'])->name('roster.stables.create');
Route::post('/roster/stables', [StablesController::class, 'store'])->name('roster.stables.store');
Route::get('/roster/stables/{stable}', [StablesController::class, 'show'])->name('roster.stables.show');
Route::get('/roster/stables/{stable}/edit', [StablesController::class, 'edit'])->name('roster.stables.edit');
Route::put('/roster/stables/{stable}', [StablesController::class, 'update'])->name('roster.stables.update');
Route::delete('/roster/stables/{stable}/destroy', [StablesController::class, 'destroy'])->name('roster.stables.destroy');
Route::put('/roster/stables/{stable}/restore', RestoreController::class)->name('roster.stables.restore');
Route::put('/roster/stables/{stable}/retire', RetireController::class)->name('roster.stables.retire');
Route::put('/roster/stables/{stable}/unretire', UnretireController::class)->name('roster.stables.unretire');
Route::put('/roster/stables/{stable}/activate', ActivateController::class)->name('roster.stables.activate');
