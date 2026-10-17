<?php

declare(strict_types=1);

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Events\TalentBookingContextService;

/**
 * The context is stored inside confirmed agreements, so its keys and their order are part of the contract.
 *
 * @return array{event: Event, venue: Venue}
 */
function talentContextWorld(): array
{
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/New_York']);
    $venue = Venue::factory()
        ->create(['name' => 'Harbor Arena', 'street_address' => '1 Dock Road', 'city' => 'Portland', 'state' => 'ME', 'zipcode' => '04101']);
    $event = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Harbor Showcase', 'date' => '2026-10-17 19:00:00', 'venue_id' => $venue->id]);

    return ['event' => $event, 'venue' => $venue];
}

describe('talent booking context service', function (): void {
    it('describes the event details an agreement depends on', function (): void {
        // Arrange
        ['event' => $event, 'venue' => $venue] = talentContextWorld();

        // Act
        $context = resolve(TalentBookingContextService::class)->get($event);

        // Assert
        expect($context)->toBe([
            'event_name' => 'Harbor Showcase',
            'event_date' => '2026-10-17T19:00:00+00:00',
            'timezone' => 'America/New_York',
            'venue_id' => $venue->id,
            'venue_name' => 'Harbor Arena',
            'venue_address' => ['1 Dock Road', 'Portland', 'ME', '04101'],
            'venue_deleted' => false,
            'deletion_version' => 0,
        ]);
    });

    it('keeps the name and address of a venue that was deleted and says so', function (): void {
        // Arrange
        ['event' => $event, 'venue' => $venue] = talentContextWorld();
        $venue->delete();

        // Act
        $context = resolve(TalentBookingContextService::class)->get($event->refresh());

        // Assert
        expect($context['venue_deleted'])->toBeTrue()
            ->and($context['venue_name'])->toBe('Harbor Arena')
            ->and($context['venue_address'])->toBe(['1 Dock Road', 'Portland', 'ME', '04101']);
    });

    it('has no venue details for an event without a venue', function (): void {
        // Arrange
        ['event' => $event] = talentContextWorld();
        $event->update(['venue_id' => null]);

        // Act
        $context = resolve(TalentBookingContextService::class)->get($event->refresh());

        // Assert
        expect($context['venue_id'])->toBeNull()
            ->and($context['venue_name'])->toBeNull()
            ->and($context['venue_address'])->toBeNull()
            ->and($context['venue_deleted'])->toBeFalse();
    });

    it('changes the deletion version when the event is deleted and restored', function (): void {
        // Arrange
        ['event' => $event] = talentContextWorld();
        $service = resolve(TalentBookingContextService::class);
        resolve(DeletionStateManager::class)->delete($event, now()->subDay());
        resolve(DeletionStateManager::class)->restore($event, now());
        $latest = $event->lifecycleTransitions()->latest('id')->firstOrFail();

        // Act
        $context = $service->get($event);

        // Assert
        expect($context['deletion_version'])->toBe($latest->id);
    });

    it('reads a fixed number of statements', function (): void {
        // Arrange
        ['event' => $event] = talentContextWorld();
        $service = resolve(TalentBookingContextService::class);

        // Act
        $statements = recordStatements(fn () => $service->get($event));

        // Assert
        expect($statements)->toHaveCount(3);
    });
});
