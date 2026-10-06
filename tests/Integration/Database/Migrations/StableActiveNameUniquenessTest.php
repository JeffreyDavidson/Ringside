<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use Illuminate\Database\QueryException;

test('active stables must have unique names', function () {
    Stable::factory()->create(['name' => 'The Four Horsemen']);

    expect(fn () => Stable::factory()->create(['name' => 'The Four Horsemen']))
        ->toThrow(QueryException::class);
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_UNOWNED_STABLE_NAMES);

test('a deleted and active stable may share a name', function () {
    $deletedStable = Stable::factory()->create(['name' => 'The Four Horsemen']);
    $deletedStable->delete();

    $activeStable = Stable::factory()->create(['name' => 'The Four Horsemen']);

    expect($deletedStable->trashed())->toBeTrue()
        ->and($activeStable->exists)->toBeTrue();
});
