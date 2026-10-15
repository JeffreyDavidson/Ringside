<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Livewire\Crew\Components\Directory;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('owners and managers can open the crew directory', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('crew.contacts.index'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('crew.index')
        ->assertSeeLivewire(Directory::class);
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

test('active members cannot open the crew directory', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('crew.contacts.index'));

    // Assert
    $response
        ->assertForbidden();
});

test('guests cannot open crew.contacts.index', function (): void {
    // Arrange

    // Act
    $response = get(route('crew.contacts.index'));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('users without memberships cannot open the crew directory', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('crew.contacts.index'));

    // Assert
    $response
        ->assertForbidden()
        ->assertViewIs('promotions.no-membership');
});
