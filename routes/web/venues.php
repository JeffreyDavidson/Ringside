<?php

use App\Http\Controllers\Venues\VenuesController;
use Illuminate\Support\Facades\Route;

Route::resource('venues', VenuesController::class)->except('destroy');
