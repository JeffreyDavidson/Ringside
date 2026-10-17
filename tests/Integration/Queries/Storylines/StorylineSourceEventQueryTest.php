<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Queries\Storylines\StorylineSourceEventQuery;

test('reads an event of the promotion with its promotion, pack and live run', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $query = resolve(StorylineSourceEventQuery::class);

    // Act
    $found = $query->get($promotion->id, $event->id);
    $statements = recordStatements(fn () => $query->get($promotion->id, $event->id));

    // Assert
    expect($found?->is($event))->toBeTrue()
        ->and($found?->relationLoaded('promotion'))->toBeTrue()
        ->and($found?->relationLoaded('pack'))->toBeTrue()
        ->and($found?->relationLoaded('liveRun'))->toBeTrue()
        ->and($statements)->toHaveCount(4);
});

test('keeps reading an event that was deleted', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $event->delete();

    // Act
    $found = resolve(StorylineSourceEventQuery::class)->get($promotion->id, $event->id);

    // Assert
    expect($found?->trashed())->toBeTrue();
});

test('reads nothing for an event of another promotion', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    // Act
    $found = resolve(StorylineSourceEventQuery::class)->get(Promotion::factory()->create()->id, $event->id);

    // Assert
    expect($found)->toBeNull();
});
