<?php

declare(strict_types=1);

use App\Models\Lifecycle\Employment;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

test('an employable has only one open employment period', function (string $modelClass) {
    /** @var Model $employable */
    $employable = $modelClass::factory()->create();

    Employment::factory()->count(2)->for($employable, 'employable')->create(['ended_at' => now()]);
    Employment::factory()->for($employable, 'employable')->create(['ended_at' => null]);

    expect(fn () => Employment::factory()
        ->for($employable, 'employable')
        ->create(['ended_at' => null]))
        ->toThrow(QueryException::class);
})->with([Wrestler::class, Manager::class, Referee::class, TagTeam::class]);
