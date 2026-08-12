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

test('it requires an explicit retirable owner', function () {
    expect(fn () => Retirement::factory()->create())
        ->toThrow(QueryException::class);
});

test('it creates a retirement for an explicit retirable owner', function (Closure $createRetirable) {
    /** @var Model $retirable */
    $retirable = $createRetirable();

    $retirement = Retirement::factory()
        ->for($retirable, 'retirable')
        ->create();

    expect($retirement->retirable)->toBeInstanceOf($retirable::class);
    expect($retirement->retirable->getKey())->toBe($retirable->getKey());
})->with([
    fn (): Wrestler => Wrestler::factory()->create(),
    fn (): Manager => Manager::factory()->create(),
    fn (): Referee => Referee::factory()->create(),
    fn (): TagTeam => TagTeam::factory()->create(),
    fn (): Stable => Stable::factory()->create(),
    fn (): Title => Title::factory()->create(),
]);

test('it can generate a retirement for a random supported owner', function () {
    $retirement = Retirement::factory()
        ->forRandomRetirable()
        ->create();

    expect([
        Wrestler::class,
        Manager::class,
        Referee::class,
        TagTeam::class,
        Stable::class,
        Title::class,
    ])->toContain($retirement->retirable::class);
});
