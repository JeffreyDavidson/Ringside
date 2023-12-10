<?php

declare(strict_types=1);

use App\Models\Referee;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', fn (Request $request) => $request->user());

Route::get('referees', function () {
    return Referee::all()->pluck('full_name', 'id')->toArray();
});

Route::get('titles', function () {
    return Title::all()->pluck('name', 'id')->toArray();
});
