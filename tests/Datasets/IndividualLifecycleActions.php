<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction as EmployManager;
use App\Actions\Managers\ReleaseAction as ReleaseManager;
use App\Actions\Referees\EmployAction as EmployReferee;
use App\Actions\Referees\ReleaseAction as ReleaseReferee;
use App\Actions\Wrestlers\EmployAction as EmployWrestler;
use App\Actions\Wrestlers\ReleaseAction as ReleaseWrestler;
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
