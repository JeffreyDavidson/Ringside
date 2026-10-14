<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('only production managers can open the crew directory', function (MembershipRole $role, int $status): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('crew.contacts.index'));

    // Assert
    $response->assertStatus($status);
})->with([
    'owner' => [MembershipRole::Owner, 200],
    'manager' => [MembershipRole::Manager, 200],
    'member' => [MembershipRole::Member, 403],
]);
