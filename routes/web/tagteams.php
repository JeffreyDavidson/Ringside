<?php

use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Controllers\TagTeams\ReleaseController;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use Illuminate\Support\Facades\Route;

Route::resource('tag-teams', TagTeamsController::class);
Route::patch('tag-teams/{tag_team}/restore', RestoreController::class)->name('tag-teams.restore');
Route::patch('tag-teams/{tag_team}/suspend', SuspendController::class)->name('tag-teams.suspend');
Route::patch('tag-teams/{tag_team}/reinstate', ReinstateController::class)->name('tag-teams.reinstate');
Route::patch('tag-teams/{tag_team}/employ', EmployController::class)->name('tag-teams.employ');
Route::patch('tag-teams/{tag_team}/retire', RetireController::class)->name('tag-teams.retire');
Route::patch('tag-teams/{tag_team}/unretire', UnretireController::class)->name('tag-teams.unretire');
Route::patch('tag-teams/{tag_team}/release', ReleaseController::class)->name('tag-teams.release');
