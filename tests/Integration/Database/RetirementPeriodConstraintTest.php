<?php

declare(strict_types=1);

use App\Models\Lifecycle\Retirement;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

test('a retirable has only one open retirement period', function (string $modelClass) {
    /** @var Model $retirable */
    $retirable = $modelClass::factory()->create();

    Retirement::factory()->count(2)->for($retirable, 'retirable')->create(['ended_at' => now()]);
    Retirement::factory()->for($retirable, 'retirable')->create(['ended_at' => null]);

    expect(fn () => Retirement::factory()
        ->for($retirable, 'retirable')
        ->create(['ended_at' => null]))
        ->toThrow(QueryException::class);
})->with([Wrestler::class, Manager::class, Referee::class, TagTeam::class, Stable::class, Title::class]);
