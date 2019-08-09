<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;

Route::resource('events', EventsController::class);
Route::put('/events/{event}/restore', RestoreController::class)->name('events.restore');
