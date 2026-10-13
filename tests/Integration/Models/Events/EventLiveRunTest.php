<?php

declare(strict_types=1);

use App\Models\Events\EventLiveRun;

require_once __DIR__.'/../../../Fixtures/live-show.php';

test('soft deletion retains the run and restoration reopens the same stored records', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $before = $fixture['run']->content;

    // Act
    $fixture['event']->delete();
    $fixture['event']->restore();

    // Assert
    expect($fixture['event']->liveRun()->firstOrFail()->content)->toBe($before)
        ->and($fixture['run']->event()->firstOrFail()->id)->toBe($fixture['event']->id);
});

test('event deletion cascades the live run and its identity fields cannot be mass assigned', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $model = EventLiveRun::factory()->make();

    // Act / Assert
    expect($model->getFillable())->toBeEmpty();
    $fixture['event']->forceDelete();
    expect(EventLiveRun::query()->whereKey($fixture['run']->id)->exists())->toBeFalse();
});
