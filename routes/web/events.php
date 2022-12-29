<?php

declare(strict_types=1);

use App\Http\Controllers\Events\DeletedEventsController;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
use Illuminate\Support\Facades\Route;

Route::get('events/deleted', [DeletedEventsController::class, 'index'])->name('events.deleted');
Route::resource('events', EventsController::class);
Route::patch('events/{event}/restore', RestoreController::class)->name('events.restore');
Route::resource('events.matches', EventMatchesController::class);
