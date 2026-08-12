<?php

declare(strict_types=1);

use App\Models\Lifecycle\Suspension;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

test('a suspendable has only one open suspension period', function (string $modelClass) {
    /** @var Model $suspendable */
    $suspendable = $modelClass::factory()->create();

    Suspension::factory()->count(2)->for($suspendable, 'suspendable')->create(['ended_at' => now()]);
    Suspension::factory()->for($suspendable, 'suspendable')->create(['ended_at' => null]);

    expect(fn () => Suspension::factory()
        ->for($suspendable, 'suspendable')
        ->create(['ended_at' => null]))
        ->toThrow(QueryException::class);
})->with([Wrestler::class, Manager::class, Referee::class, TagTeam::class]);
