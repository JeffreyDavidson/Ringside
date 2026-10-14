<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/talent-booking.php';

use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Promotions\Promotion;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('an event editor can open the checklist without creating agreements', function (): void {
    // Arrange
    $event = Event::factory()->create();
    actingAs(administrator());

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response->assertOk()->assertViewIs('events.talent-bookings')->assertViewHas('event', $event)->assertSee('Talent bookings')->assertSee('USD');
    expect(EventTalentBooking::query()->count())->toBe(0);
});

test('only owners and managers see private booking pages and their links', function (MembershipRole $role, bool $allowed): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.talent-bookings.show', $event));
    $eventResponse = get(route('events.show', $event));

    // Assert
    if ($allowed) {
        $response->assertOk()->assertViewIs('events.talent-bookings')->assertViewHas('event', $event);
        $eventResponse->assertSee(route('events.talent-bookings.show', $event));

        return;
    }
    $response->assertForbidden();
    $eventResponse->assertDontSee(route('events.talent-bookings.show', $event));
})->with([
    'owner' => [MembershipRole::Owner, true],
    'manager' => [MembershipRole::Manager, true],
    'member' => [MembershipRole::Member, false],
]);

test('guests and foreign promotions cannot read the booking checklist', function (): void {
    // Arrange
    $event = Event::factory()->for(Promotion::factory())->create();

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response->assertRedirect(route('login'));
    $other = Promotion::factory()->create();
    $user = basicUser();
    $other->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response->assertNotFound();
    expect(EventTalentBooking::query()->count())->toBe(0);
});

test('private terms never enter event pack print live or ordinary event responses or activity logs', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'LEAK-REASON-7Q'));

    // Act
    foreach (['events.show', 'events.pack.show', 'events.live-show.show'] as $name) {
        $response = get(route($name, $fixture['event']));

        // Assert
        $response->assertOk()->assertDontSee('LEAK-TRAVEL-7Q')->assertDontSee('LEAK-REASON-7Q')->assertDontSee('1,234.56');
    }

    // Act
    $response = get(route('events.pack.show', ['event' => $fixture['event'], 'print' => 1]));

    // Assert
    $response->assertOk()->assertDontSee('LEAK-TRAVEL-7Q')->assertDontSee('LEAK-REASON-7Q');
    expect(Activity::query()->get()->toJson())->not->toContain('LEAK-TRAVEL-7Q', 'LEAK-REASON-7Q');
});
