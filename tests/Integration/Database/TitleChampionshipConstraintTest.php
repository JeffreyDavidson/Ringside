<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a title has only one open reign', function () {
    // Arrange
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    // Act
    $secondOpenReign = fn () => DB::transaction(fn () => TitleChampionship::factory()->for($title)->create(['lost_at' => null]));

    // Assert
    expect($secondOpenReign)->toThrow(QueryException::class)
        ->and(TitleChampionship::query()->whereBelongsTo($title)->count())->toBe(1);
});

test('a title may have ended reigns and one open reign', function () {
    // Arrange
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->count(2)->create(['lost_at' => now()]);

    // Act
    TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    // Assert
    expect(TitleChampionship::query()->whereBelongsTo($title)->count())->toBe(3)
        ->and(TitleChampionship::query()->whereBelongsTo($title)->whereNull('lost_at')->count())->toBe(1);
});

test('a deleted open reign does not block a live open reign', function () {
    // Arrange
    $title = Title::factory()->create();
    TitleChampionship::factory()->for($title)->create(['lost_at' => null])->delete();

    // Act
    TitleChampionship::factory()->for($title)->create(['lost_at' => null]);

    // Assert
    expect(TitleChampionship::query()->whereBelongsTo($title)->count())->toBe(1)
        ->and(TitleChampionship::query()->whereBelongsTo($title)->withTrashed()->count())->toBe(2);
});

test('different titles may each have an open reign', function () {
    // Act
    TitleChampionship::factory()->count(2)->create(['lost_at' => null]);

    // Assert
    expect(TitleChampionship::query()->whereNull('lost_at')->count())->toBe(2);
});

test('the migration lists the titles with several open reigns before changing anything', function () {
    // Arrange
    $title = Title::factory()->create();
    DB::statement('DROP INDEX titles_championships_one_open_reign_unique');
    [$first, $second] = TitleChampionship::factory()->for($title)->count(2)->create(['lost_at' => null])->all();
    $migration = require database_path('migrations/2026_10_05_024420_enforce_single_open_reign_per_title.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "title {$title->id} has open reign ids {$first->id}, {$second->id}",
        );
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);
