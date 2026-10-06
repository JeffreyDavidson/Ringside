<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\QueryException;

test('stables of different promotions may share an active name', function () {
    [$first, $second] = Promotion::factory()->count(2)->create()->all();

    $firstStable = Stable::factory()->for($first, 'promotion')->create(['name' => 'The Four Horsemen']);
    $secondStable = Stable::factory()->for($second, 'promotion')->create(['name' => 'The Four Horsemen']);

    expect($firstStable->exists)->toBeTrue()
        ->and($secondStable->exists)->toBeTrue();
});

test('active stables of one promotion must have unique names', function () {
    $promotion = Promotion::factory()->create();
    Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen']);

    expect(fn () => Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen']))
        ->toThrow(QueryException::class);
});

test('active unowned stables must have unique names', function () {
    Stable::factory()->create(['name' => 'The Four Horsemen']);

    expect(fn () => Stable::factory()->create(['name' => 'The Four Horsemen']))
        ->toThrow(QueryException::class);
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_UNOWNED_STABLE_NAMES);

test('a deleted and an active stable of one promotion may share a name', function () {
    $promotion = Promotion::factory()->create();
    Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen'])->delete();

    $active = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Four Horsemen']);

    expect($active->exists)->toBeTrue();
});
