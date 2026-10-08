<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction as EmployManager;
use App\Actions\Managers\ReleaseAction as ReleaseManager;
use App\Actions\Managers\RetireAction as RetireManager;
use App\Actions\Managers\UnretireAction as UnretireManager;
use App\Actions\Referees\EmployAction as EmployReferee;
use App\Actions\Referees\ReleaseAction as ReleaseReferee;
use App\Actions\Referees\RetireAction as RetireReferee;
use App\Actions\Referees\UnretireAction as UnretireReferee;
use App\Actions\Wrestlers\EmployAction as EmployWrestler;
use App\Actions\Wrestlers\ReleaseAction as ReleaseWrestler;
use App\Actions\Wrestlers\RetireAction as RetireWrestler;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestler;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

/*
 * Each case is [individual model class, lifecycle action class]. The wrestler,
 * manager and referee actions share the same rules, so one test body runs
 * against all three.
 */
dataset('individual employ actions', [
    'wrestler' => [Wrestler::class, EmployWrestler::class],
    'manager' => [Manager::class, EmployManager::class],
    'referee' => [Referee::class, EmployReferee::class],
]);

dataset('individual release actions', [
    'wrestler' => [Wrestler::class, ReleaseWrestler::class],
    'manager' => [Manager::class, ReleaseManager::class],
    'referee' => [Referee::class, ReleaseReferee::class],
]);

dataset('individual retire actions', [
    'wrestler' => [Wrestler::class, RetireWrestler::class],
    'manager' => [Manager::class, RetireManager::class],
    'referee' => [Referee::class, RetireReferee::class],
]);

dataset('individual unretire actions', [
    'wrestler' => [Wrestler::class, UnretireWrestler::class],
    'manager' => [Manager::class, UnretireManager::class],
    'referee' => [Referee::class, UnretireReferee::class],
]);

/*
 * Only the wrestler and manager UnretireAction take the $employImmediately
 * flag (TagTeams\UnretireCurrentMembersAction passes false); the referee
 * action always employs.
 */
dataset('individual unretire actions with optional employment', [
    'wrestler' => [Wrestler::class, UnretireWrestler::class],
    'manager' => [Manager::class, UnretireManager::class],
]);
