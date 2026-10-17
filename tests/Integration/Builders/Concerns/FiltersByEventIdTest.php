<?php

declare(strict_types=1);

use App\Builders\Events\EventBudgetBuilder;
use App\Builders\Events\EventLiveRunBuilder;
use App\Builders\Events\EventPackBuilder;
use App\Builders\Events\EventPublicationBuilder;
use App\Builders\Events\EventSponsorCommitmentBuilder;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Events\EventPublication;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;

dataset('event-owned models', [
    'budget' => [EventBudget::class, EventBudgetBuilder::class],
    'live run' => [EventLiveRun::class, EventLiveRunBuilder::class],
    'pack' => [EventPack::class, EventPackBuilder::class],
    'publication' => [EventPublication::class, EventPublicationBuilder::class],
    'sponsor commitment' => [EventSponsorCommitment::class, EventSponsorCommitmentBuilder::class],
]);

test('event-owned records are limited to one event', function (string $model, string $builder): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $otherEvent = Event::factory()
        ->for($promotion)
        ->create();
    $other = $model::factory()
        ->create(['event_id' => $otherEvent->id]);
    $owned = $model::factory()
        ->create(['event_id' => $event->id]);

    // Act
    $query = $model::query();
    $records = $query->forEventId($event->id)
        ->get();

    // Assert
    expect($query instanceof $builder)->toBeTrue()
        ->and($records->modelKeys())->toBe([$owned->id])
        ->and($other->event_id)->not->toBe($owned->event_id);
})->with('event-owned models');
