<?php

declare(strict_types=1);

use App\Actions\Managers\ClearFromInjuryAction as ClearManagerFromInjury;
use App\Actions\Managers\InjureAction as InjureManager;
use App\Actions\Referees\ClearFromInjuryAction as ClearRefereeFromInjury;
use App\Actions\Referees\InjureAction as InjureReferee;
use App\Actions\Wrestlers\ClearFromInjuryAction as ClearWrestlerFromInjury;
use App\Actions\Wrestlers\InjureAction as InjureWrestler;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

/*
 * Each case is [individual model class, injury action class]. The wrestler,
 * manager and referee actions share the same rules, so one test body runs
 * against all three.
 */
dataset('individual injure actions', [
    'wrestler' => [Wrestler::class, InjureWrestler::class],
    'manager' => [Manager::class, InjureManager::class],
    'referee' => [Referee::class, InjureReferee::class],
]);

dataset('individual clear from injury actions', [
    'wrestler' => [Wrestler::class, ClearWrestlerFromInjury::class],
    'manager' => [Manager::class, ClearManagerFromInjury::class],
    'referee' => [Referee::class, ClearRefereeFromInjury::class],
]);
