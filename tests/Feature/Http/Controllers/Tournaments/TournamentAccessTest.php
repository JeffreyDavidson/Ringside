<?php

declare(strict_types=1);
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Tournaments\Tournament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('keeps foreign tournament bindings indistinguishable from missing records', function (): void {
    // Arrange
    $world = tournamentFixture();
    $other = Promotion::factory()->create();

    $user = basicUser();
    $other->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);

    // Act
    $response = get(route('tournaments.show', $world['tournament']));

    // Assert
    $response->assertNotFound();

    // Act
    $response = get(route('tournaments.show', PHP_INT_MAX));

    // Assert
    $response->assertNotFound();

    expect(Tournament::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('shows operation controls only to promotion owners and managers', function (MembershipRole $role, bool $editable): void {
    // Arrange
    $world = tournamentFixture();

    $user = basicUser();
    $world['promotion']->users()->attach($user, [
        'role' => $role,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);

    // Act
    $response = get(route('tournaments.show', $world['tournament']));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Autumn Cup');
    if ($editable) {
        $response->assertSee('Save draft');

        return;
    }
    $response
        ->assertDontSee('Save draft')
        ->assertDontSee('Cancel tournament');
})->with([
    'owner' => [MembershipRole::Owner, true],
    'manager' => [MembershipRole::Manager, true],
    'member' => [MembershipRole::Member, false],
]);

it('requires login for tournament pages', function (): void {
    // Arrange
    $world = tournamentFixture();

    // Act
    $response = get(route('tournaments.index'));

    // Assert
    $response->assertRedirect(route('login'));

    // Act
    $response = get(route('tournaments.show', $world['tournament']));

    // Assert
    $response->assertRedirect(route('login'));
});
