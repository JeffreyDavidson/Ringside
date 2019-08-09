<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Venues\VenuesController;

Route::resource('venues', VenuesController::class)->except('destroy');
