<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the live show page opens without preparing or changing a show', function (): void {
    // Arrange
    $event = Event::factory()->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.live')
        ->assertViewHas('event', $event)
        ->assertSee('Live show')
        ->assertSee('Save and review');

    expect(EventLiveRun::query()->count())->toBe(0);
});

test('guests and other promotions cannot open a live show', function (): void {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response->assertRedirect(route('login'));

    // Arrange
    $promotion = Promotion::factory()->create();

    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Manager,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response->assertNotFound();

    expect(EventLiveRun::query()->count())->toBe(0);
});

test('an active member receives the live view for their own event', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();

    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.live')
        ->assertViewHas('event', $event);

    expect(EventLiveRun::query()->count())->toBe(0);
});
