<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;

test('it creates a scheduled event with its venue and preview', function (): void {
    $venue = Venue::factory()->create();
    $date = now()->addWeek()->setTime(19, 0);
    $data = new EventData('Summer Slam', $date, $venue, 'A major event.');

    $event = resolve(CreateAction::class)->handle($data);

    expect($event)
        ->toBeInstanceOf(Event::class)
        ->name->toBe('Summer Slam')
        ->preview->toBe('A major event.')
        ->venue_id->toBe($venue->id)
        ->and($event->date?->toDateTimeString())->toBe($date->toDateTimeString());
});

test('it assigns the active promotion when promotion context is enforced', function (): void {
    $promotion = Promotion::factory()->create();
    $context = app(PromotionContextService::class);

    $context->set($promotion);
    $context->enforce();

    $event = resolve(CreateAction::class)->handle(
        new EventData('Promotion Event', null, null, null),
    );

    expect($event->promotion_id)->toBe($promotion->id)
        ->and(Event::query()->findOrFail($event->id)->is($event))->toBeTrue();
});

test('it locks the venue row before checking the venue is free and creating the event', function (): void {
    // Arrange
    $venue = Venue::factory()->create();
    $data = new EventData('Locked Venue Event', now()->addWeek()->setTime(19, 0), $venue, null);

    // Act
    $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

    // Assert
    $venueLock = array_find_key($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "venues"'));
    $availabilityCheck = array_find_key($statements, fn (array $statement): bool => str_contains($statement['sql'], 'from "events"'));
    $insert = array_find_key($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "events"'));

    expect(lockedRowIds($statements, 'venues'))->toBe([$venue->id])
        ->and($venueLock)->toBeLessThan($availabilityCheck)
        ->and($availabilityCheck)->toBeLessThan($insert);
});
