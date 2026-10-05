<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a title has only one open reign', function () {
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    expect(fn () => DB::transaction(fn () => TitleChampionship::factory()->for($title)->create(['lost_at' => null])))
        ->toThrow(QueryException::class);
});

test('a title may have ended reigns and one open reign', function () {
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->count(2)->create(['lost_at' => now()]);

    $open = TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    expect($open->exists)->toBeTrue();
});

test('a deleted open reign does not block a live open reign', function () {
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->create(['lost_at' => null])->delete();

    $open = TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    expect($open->exists)->toBeTrue();
});

test('different titles may each have an open reign', function () {
    TitleChampionship::factory()->count(2)->create(['lost_at' => null]);

    expect(TitleChampionship::query()->count())->toBe(2);
});

test('the migration lists the titles with several open reigns before changing anything', function () {
    $title = Title::factory()->create();
    DB::statement('DROP INDEX titles_championships_one_open_reign_unique');
    [$first, $second] = TitleChampionship::factory()->for($title)->count(2)->create(['lost_at' => null])->all();
    $migration = require database_path('migrations/2026_10_05_024420_enforce_single_open_reign_per_title.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "title {$title->id} has open reign ids {$first->id}, {$second->id}",
        );
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);
