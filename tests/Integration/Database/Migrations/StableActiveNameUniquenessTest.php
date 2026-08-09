<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('active stables must have unique names', function () {
    Stable::factory()->create(['name' => 'The Four Horsemen']);

    expect(fn () => Stable::factory()->create(['name' => 'The Four Horsemen']))
        ->toThrow(QueryException::class);
});

test('a deleted and active stable may share a name', function () {
    $deletedStable = Stable::factory()->create(['name' => 'The Four Horsemen']);
    $deletedStable->delete();

    $activeStable = Stable::factory()->create(['name' => 'The Four Horsemen']);

    expect($deletedStable->trashed())->toBeTrue()
        ->and($activeStable->exists)->toBeTrue();
});

test('the migration identifies existing duplicate active names', function () {
    DB::statement('DROP INDEX stables_active_name_unique');
    Stable::factory()->create(['name' => 'The Four Horsemen']);
    Stable::factory()->create(['name' => 'The Four Horsemen']);

    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_08_09_230854_enforce_unique_active_stable_names.php');

    expect(fn () => $migration->up())
        ->toThrow(
            RuntimeException::class,
            'Cannot enforce unique active stable names. Resolve duplicate active names first: The Four Horsemen'
        );
});
