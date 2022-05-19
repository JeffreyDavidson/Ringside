<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\RestoreController;
use App\Http\Controllers\Venues\VenuesController;
use Illuminate\Support\Facades\Route;

Route::resource('venues', VenuesController::class);
Route::patch('venues/{venue}/restore', RestoreController::class)->name('venues.restore');
