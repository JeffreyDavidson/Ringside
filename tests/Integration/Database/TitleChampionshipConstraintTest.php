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
