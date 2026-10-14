<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view for administrator', function () {
    // Arrange
    $event = Event::factory()->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.matches.index', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('matches.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertViewHas('event', $event);
});

test('a user without membership cannot view event matches', function () {
    // Arrange
    $event = Event::factory()->create();

    actingAs(basicUser());

    // Act
    $response = get(route('events.matches.index', $event));

    // Assert
    $response
        ->assertForbidden();
});

test('guest cannot view event matches', function () {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.matches.index', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('promotion member can view matches of their promotion event', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->create();

    $member = basicUser();
    $promotion->users()->attach($member, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($member);

    // Act
    $response = get(route('events.matches.index', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('matches.index')
        ->assertViewHas('event', $event);
});

test('promotion member cannot view matches of another promotion event', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherEvent = Event::factory()->for(Promotion::factory(), 'promotion')->create();

    $member = basicUser();
    $promotion->users()->attach($member, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($member);

    // Act
    $response = get(route('events.matches.index', $otherEvent));

    // Assert
    $response
        ->assertNotFound();
});

test('missing or deleted events have no match index', function (bool $deleted): void {
    // Arrange
    $event = Event::factory()->create();
    $id = $deleted ? $event->id : 999999;
    if ($deleted) {
        $event->delete();
    }

    actingAs(administrator());

    // Act
    $response = get(route('events.matches.index', $id));

    // Assert
    $response->assertNotFound();
})->with([
    'missing' => false,
    'deleted' => true,
]);
