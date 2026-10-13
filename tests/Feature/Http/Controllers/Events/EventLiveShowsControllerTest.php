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
    $response->assertOk()->assertSee('Live show')->assertSee('Save and review');
    expect(EventLiveRun::query()->count())->toBe(0);
});

test('guests and other promotions cannot open a live show', function (): void {
    // Arrange
    $event = Event::factory()->create();

    // Act / Assert
    get(route('events.live-show.show', $event))->assertRedirect(route('login'));
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.live-show.show', $event))->assertNotFound();
    expect(EventLiveRun::query()->count())->toBe(0);
});
