<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Controllers\TagTeams\ReinstateController;

Route::resource('tag-teams', TagTeamsController::class);
Route::put('/tag-teams/{tag_team}/restore', RestoreController::class)->name('tag-teams.restore');
Route::put('/tag-teams/{tag_team}/suspend', SuspendController::class)->name('tag-teams.suspend');
Route::put('/tag-teams/{tag_team}/reinstate', ReinstateController::class)->name('tag-teams.reinstate');
Route::put('/tag-teams/{tag_team}/employ', EmployController::class)->name('tag-teams.employ');
Route::put('/tag-teams/{tag_team}/retire', RetireController::class)->name('tag-teams.retire');
Route::put('/tag-teams/{tag_team}/unretire', UnretireController::class)->name('tag-teams.unretire');

