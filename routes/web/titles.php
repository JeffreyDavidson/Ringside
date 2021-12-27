<?php

use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('titles', TitlesController::class);
Route::patch('titles/{title}/activate', ActivateController::class)->name('titles.activate');
Route::patch('titles/{title}/deactivate', DeactivateController::class)->name('titles.deactivate');
Route::patch('titles/{title}/restore', RestoreController::class)->name('titles.restore');
Route::patch('titles/{title}/retire', RetireController::class)->name('titles.retire');
Route::patch('titles/{title}/unretire', UnretireController::class)->name('titles.unretire');
