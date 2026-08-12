<?php

declare(strict_types=1);

use App\Models\Lifecycle\Employment;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
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
