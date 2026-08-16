<?php

declare(strict_types=1);

use App\Models\Lifecycle\Employment;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

test('it creates employment for an explicit employable owner', function (Closure $createEmployable) {
    /** @var Model $employable */
    $employable = $createEmployable();

    $employment = Employment::factory()
        ->for($employable, 'employable')
        ->create();

    expect($employment->employable)->toBeInstanceOf($employable::class);
    expect($employment->employable->getKey())->toBe($employable->getKey());
})->with([
    fn (): Wrestler => Wrestler::factory()->create(),
    fn (): Manager => Manager::factory()->create(),
    fn (): Referee => Referee::factory()->create(),
    fn (): TagTeam => TagTeam::factory()->create(),
]);

test('it can generate an employment for a random supported owner', function () {
    $employment = Employment::factory()
        ->forRandomEmployable()
        ->create();

    expect([
        Wrestler::class,
        Manager::class,
        Referee::class,
        TagTeam::class,
    ])->toContain($employment->employable::class);
});
