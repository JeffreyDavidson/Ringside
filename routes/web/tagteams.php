<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\ActivateController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Controllers\TagTeams\ReinstateController;

Route::resource('tagteams', TagTeamsController::class);
Route::put('/tag-teams/{tagteam}/restore', RestoreController::class)->name('tagteams.restore');
Route::put('/tag-teams/{tagteam}/suspend', SuspendController::class)->name('tagteams.suspend');
Route::put('/tag-teams/{tagteam}/reinstate', ReinstateController::class)->name('tagteams.reinstate');
Route::put('/tag-teams/{tagteam}/activate', ActivateController::class)->name('tagteams.activate');
Route::put('/tag-teams/{tagteam}/retire', RetireController::class)->name('tagteams.retire');
Route::put('/tag-teams/{tagteam}/unretire', UnretireController::class)->name('tagteams.unretire');

