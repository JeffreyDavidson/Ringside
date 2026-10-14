<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('sponsor directory and records are private to active owners and managers', function (MembershipRole $role, bool $allowed): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->for($promotion)->create(['name' => 'Harbor Hardware', 'contact_email' => 'private@example.test']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('sponsors.index'));
    $show = get(route('sponsors.show', $sponsor));

    // Assert
    if (! $allowed) {
        $response->assertForbidden();
        $show->assertForbidden();

        return;
    }
    $response
        ->assertOk()
        ->assertViewIs('sponsors.index')
        ->assertViewHas('promotionId', $promotion->id)
        ->assertSee('Harbor Hardware');
    $show
        ->assertOk()
        ->assertViewIs('sponsors.show')
        ->assertViewHas('sponsor', $sponsor)
        ->assertSee('private@example.test');

    expect(Sponsor::query()->count())->toBe(1);
})->with([[MembershipRole::Owner, true], [MembershipRole::Manager, true], [MembershipRole::Member, false]]);

test('guests and outsiders cannot reveal sponsor records', function (): void {
    // Arrange
    $sponsor = Sponsor::factory()->create(['notes' => 'PRIVATE-7Q']);

    // Act
    $response = get(route('sponsors.index'));

    // Assert
    $response->assertRedirect(route('login'));

    // Act
    $response = get(route('sponsors.show', $sponsor));

    // Assert
    $response->assertRedirect(route('login'));

    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('sponsors.show', $sponsor));

    // Assert
    $response
        ->assertNotFound()
        ->assertDontSee('PRIVATE-7Q');
});

test('an administrator without an active promotion must choose one before opening the directory', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('sponsors.index'));

    // Assert
    $response->assertForbidden();
});

test('a suspended or removed membership cannot recover private sponsor records', function (string $membership): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->for($promotion)->create(['notes' => 'PRIVATE-SUSPENDED-7Q']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $response = get(route('sponsors.show', $sponsor));

    // Assert
    $response->assertOk();
    if ($membership === 'suspended') {
        $promotion->users()->updateExistingPivot($user->id, ['status' => MembershipStatus::Suspended]);
    } else {
        $promotion->users()->detach($user);
    }

    // Act
    $response = get(route('sponsors.show', $sponsor));

    // Assert
    $response
        ->assertForbidden()
        ->assertDontSee('PRIVATE-SUSPENDED-7Q');

    expect(Sponsor::query()->withoutGlobalScopes()->findOrFail($sponsor->id)->version)->toBe(1);
})->with(['suspended', 'removed']);

test('missing sponsor records are not found', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('sponsors.show', 999999));

    // Assert
    $response->assertNotFound();
});
