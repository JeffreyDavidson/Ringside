<?php

declare(strict_types=1);

use App\Actions\Venues\DeleteAction;
use App\Enums\Promotions\MembershipRole;
use App\Exceptions\Venues\CannotBeDeletedException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Carbon;

test('it soft deletes a venue through a locked record', function () {
    $venue = Venue::factory()->create();
    $staleVenue = clone $venue;

    $venue->refresh();
    resolve(DeleteAction::class)->handle($staleVenue);

    expect(Venue::query()->find($venue->getKey()))->toBeNull()
        ->and(Venue::withTrashed()->find($venue->getKey()))->not->toBeNull();
});

test('it refuses to delete a venue with upcoming events, naming the venue and the count', function (int $count, string $expected): void {
    $venue = Venue::factory()->create(['name' => 'Garden Arena']);
    Event::factory()->count($count)->sequence(fn ($sequence): array => ['date' => now()->addDays($sequence->index + 1)])->create(['venue_id' => $venue->id]);

    $act = fn () => resolve(DeleteAction::class)->handle($venue);

    expect($act)->toThrow(CannotBeDeletedException::class, $expected)
        ->and(Venue::query()->find($venue->id))->not->toBeNull();
})->with([
    'one event' => [1, "Venue 'Garden Arena' has 1 upcoming event and cannot be deleted. Move it to another venue or delete it first."],
    'three events' => [3, "Venue 'Garden Arena' has 3 upcoming events and cannot be deleted. Move them to another venue or delete them first."],
]);

test('it counts upcoming events of other promotions but never names them', function (): void {
    $venue = Venue::factory()->create(['name' => 'Garden Arena']);
    $own = Promotion::factory()->create();
    $other = Promotion::factory()->create();
    Event::factory()->for($own, 'promotion')->create(['venue_id' => $venue->id, 'date' => now()->addDays(2)]);
    Event::factory()->for($other, 'promotion')->create(['venue_id' => $venue->id, 'date' => now()->addDays(3), 'name' => 'Secret Rival Show']);
    actingAsPromotionMember($own, MembershipRole::Owner);
    $message = null;

    try {
        resolve(DeleteAction::class)->handle($venue);
    } catch (CannotBeDeletedException $exception) {
        $message = $exception->getMessage();
    }

    expect($message)->toContain('2 upcoming events')
        ->not->toContain('Secret Rival Show')
        ->and(Venue::query()->find($venue->id))->not->toBeNull();
});

test('it deletes a venue whose events are past, deleted or undated', function (): void {
    $venue = Venue::factory()->create();
    Event::factory()->create(['venue_id' => $venue->id, 'date' => now()->subDay()]);
    Event::factory()->create(['venue_id' => $venue->id, 'date' => now()->addDays(5)])->delete();
    Event::factory()->unscheduled()->create(['venue_id' => $venue->id]);

    resolve(DeleteAction::class)->handle($venue);

    expect(Venue::query()->find($venue->id))->toBeNull();
});

test('it treats an event earlier today in the venue time zone as upcoming', function (): void {
    // 03:00 UTC is 8pm the previous day in Los Angeles, so 18:00 UTC the day before is still "today" there.
    $this->travelTo(Carbon::parse('2026-10-09 03:00:00', 'UTC'));
    $venue = Venue::factory()->create(['timezone' => 'America/Los_Angeles']);
    Event::factory()->create(['venue_id' => $venue->id, 'date' => Carbon::parse('2026-10-08 18:00:00', 'UTC')]);

    $act = fn () => resolve(DeleteAction::class)->handle($venue);

    expect($act)->toThrow(CannotBeDeletedException::class);
});

test('it does not treat an event on the previous venue day as upcoming', function (): void {
    $this->travelTo(Carbon::parse('2026-10-09 03:00:00', 'UTC'));
    $venue = Venue::factory()->create(['timezone' => 'America/Los_Angeles']);
    Event::factory()->create(['venue_id' => $venue->id, 'date' => Carbon::parse('2026-10-08 06:00:00', 'UTC')]);

    resolve(DeleteAction::class)->handle($venue);

    expect(Venue::query()->find($venue->id))->toBeNull();
});
