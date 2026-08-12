<?php

declare(strict_types=1);

use App\Models\Lifecycle\Injury;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

test('it requires an explicit injurable owner', function () {
    expect(fn () => Injury::factory()->create())
        ->toThrow(QueryException::class);
});

test('it creates an injury for an explicit injurable owner', function (Closure $createInjurable) {
    /** @var Model $injurable */
    $injurable = $createInjurable();

    $injury = Injury::factory()
        ->for($injurable, 'injurable')
        ->create();

    expect($injury->injurable)->toBeInstanceOf($injurable::class);
    expect($injury->injurable->getKey())->toBe($injurable->getKey());
})->with([
    fn (): Wrestler => Wrestler::factory()->create(),
    fn (): Manager => Manager::factory()->create(),
    fn (): Referee => Referee::factory()->create(),
]);

test('it can generate an injury for a random supported owner', function () {
    $injury = Injury::factory()
        ->forRandomInjurable()
        ->create();

    expect([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ])->toContain($injury->injurable::class);
});
