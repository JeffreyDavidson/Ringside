<?php

use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('titles', TitlesController::class);
Route::put('/titles/{title}/activate', ActivateController::class)->name('titles.activate');
Route::put('/titles/{title}/deactivate', DeactivateController::class)->name('titles.deactivate');
Route::put('/titles/{title}/restore', RestoreController::class)->name('titles.restore');
Route::put('/titles/{title}/retire', RetireController::class)->name('titles.retire');
Route::put('/titles/{title}/unretire', UnretireController::class)->name('titles.unretire');
