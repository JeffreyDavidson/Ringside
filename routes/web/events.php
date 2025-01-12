<?php

declare(strict_types=1);

use App\Http\Controllers\Events\EventsController;
use App\Http\Controllers\Events\RestoreController;
use Illuminate\Support\Facades\Route;

Route::resource('events', EventsController::class)->only(['index', 'show']);
Route::patch('events/{event}/restore', RestoreController::class)->name('events.restore');
