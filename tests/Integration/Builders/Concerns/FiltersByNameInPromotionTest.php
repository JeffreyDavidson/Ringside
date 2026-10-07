<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;

dataset('promotion scoped models', [
    'tag team' => TagTeam::class,
    'stable' => Stable::class,
    'title' => Title::class,
]);

test('whereNameConflictsWith finds other records with the same name and promotion', function (string $model) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = $model::factory()->create(['name' => 'Shared Name', 'promotion_id' => $promotion->id]);
    $record->delete();
    $conflicting = $model::factory()->create(['name' => 'Shared Name', 'promotion_id' => $promotion->id]);
    $model::factory()->create(['name' => 'Shared Name', 'promotion_id' => Promotion::factory()->create()->id]);
    $model::factory()->create(['name' => 'Another Name', 'promotion_id' => $promotion->id]);

    // Act
    $conflicts = $model::withTrashed()->whereNameConflictsWith($record)->pluck('id');

    // Assert
    expect($conflicts->all())->toBe([$conflicting->id]);
})->with('promotion scoped models');

test('whereNameInPromotion matches the name within the promotion only', function (string $model) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = $model::factory()->create(['name' => 'Shared Name', 'promotion_id' => $promotion->id]);
    $model::factory()->create(['name' => 'Shared Name', 'promotion_id' => Promotion::factory()->create()->id]);

    // Act
    $inPromotion = $model::query()->whereNameInPromotion('Shared Name', $promotion->id)->pluck('id');
    $withoutPromotion = $model::query()->whereNameInPromotion('Shared Name', null)->pluck('id');

    // Assert
    expect($inPromotion->all())->toBe([$record->id])
        ->and($withoutPromotion->all())->toBe([]);
})->with('promotion scoped models');
