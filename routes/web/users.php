<?php

declare(strict_types=1);

use App\Http\Controllers\Users\UsersController;
use Illuminate\Support\Facades\Route;

Route::resource('users', UsersController::class)->only(['index']);
