<?php

declare(strict_types=1);

use App\Models\Lifecycle\Suspension;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

test('it creates a suspension for an explicit suspendable owner', function (Closure $createSuspendable) {
    /** @var Model $suspendable */
    $suspendable = $createSuspendable();

    $suspension = Suspension::factory()
        ->for($suspendable, 'suspendable')
        ->create();

    expect($suspension->suspendable)->toBeInstanceOf($suspendable::class);
    expect($suspension->suspendable->getKey())->toBe($suspendable->getKey());
})->with([
    fn (): Wrestler => Wrestler::factory()->create(),
    fn (): Manager => Manager::factory()->create(),
    fn (): Referee => Referee::factory()->create(),
    fn (): TagTeam => TagTeam::factory()->create(),
]);

test('it can generate a suspension for a random supported owner', function () {
    $suspension = Suspension::factory()
        ->forRandomSuspendable()
        ->create();

    expect([
        Wrestler::class,
        Manager::class,
        Referee::class,
        TagTeam::class,
    ])->toContain($suspension->suspendable::class);
});
