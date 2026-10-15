<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/talent-booking.php';

use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Promotions\Promotion;
use Illuminate\View\View;
use PHPUnit\Framework\Assert;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('an event editor can open the checklist without creating agreements', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.talent-bookings')
        ->assertViewHas('event', $event)
        ->assertSee('Talent bookings')
        ->assertSee('USD');

    expect(EventTalentBooking::query()
        ->count())->toBe(0);
});

test('owners and managers receive their private booking checklist', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.talent-bookings')
        ->assertViewHas('event', $event);
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

test('owners and managers see their private booking link', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('events.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee(route('events.talent-bookings.show', $event));
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

test('read-only members cannot read the booking checklist', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertForbidden();
});

test('read-only members cannot see the private booking link', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('events.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertDontSee(route('events.talent-bookings.show', $event));
});

test('guests cannot read the booking checklist', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect(EventTalentBooking::query()
        ->count())->toBe(0);
});

test('foreign promotions cannot read the booking checklist', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $other = Promotion::factory()
        ->create();

    actingAsPromotionMember($other, MembershipRole::Manager);

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertNotFound();

    expect(EventTalentBooking::query()
        ->count())->toBe(0);
});

test('ordinary event responses omit private talent terms and cancellation reasons', function (string $routeName, bool $print, string $view): void {
    // Arrange
    $fixture = talentBookingFixture();
    $event = $fixture['event'];
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'LEAK-REASON-7Q'));

    // Act
    $response = get(route($routeName, ['event' => $event, 'print' => $print]));

    // Assert
    $response
        ->assertOk()
        ->assertViewHas('event', $event)
        ->assertDontSee('LEAK-TRAVEL-7Q')
        ->assertDontSee('LEAK-REASON-7Q')
        ->assertDontSee('1,234.56');

    Assert::assertInstanceOf(View::class, $response->original);
    expect($response->original->name())->toBe($view);
})->with(['event' => ['events.show', false, 'events.show'], 'pack' => ['events.pack.show', false, 'events.pack'], 'print' => ['events.pack.show', true, 'events.pack-print'], 'live' => ['events.live-show.show', false, 'events.live']]);

test('opening the booking checklist cannot put private terms in activity logs', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $event = $fixture['event'];
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'LEAK-REASON-7Q'));

    // Act
    $response = get(route('events.talent-bookings.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.talent-bookings')
        ->assertViewHas('event', $event);

    expect(Activity::query()
        ->get()
        ->toJson())->not->toContain('LEAK-TRAVEL-7Q', 'LEAK-REASON-7Q');
});
