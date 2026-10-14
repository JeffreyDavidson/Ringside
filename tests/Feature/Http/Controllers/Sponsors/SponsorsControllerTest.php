<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('owners and managers can read the sponsor index', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create([
                'name' => 'Harbor Hardware',
                'contact_email' => 'private@example.test',
            ]);

        actingAsPromotionMember($promotion, $role);

        // Act
        $response = get(route('sponsors.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('sponsors.index')
            ->assertViewHas('promotionId', $promotion->id)
            ->assertSee('Harbor Hardware');

        expect(Sponsor::query()
            ->count())->toBe(1);
    })->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

    test('active members cannot read the sponsor index', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create([
                'name' => 'Harbor Hardware',
                'contact_email' => 'private@example.test',
            ]);

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $response = get(route('sponsors.index'));

        // Assert
        $response
            ->assertForbidden();

        expect(Sponsor::query()
            ->count())->toBe(1);
    });

    test('guests cannot read the sponsor index', function (): void {
        // Arrange
        $sponsor = Sponsor::factory()
            ->create(['notes' => 'PRIVATE-7Q']);

        // Act
        $response = get(route('sponsors.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect(Sponsor::query()
            ->count())->toBe(1);
    });

    test('an administrator without an active promotion must choose one before opening the directory', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('sponsors.index'));

        // Assert
        $response
            ->assertForbidden();
    });

});

describe('show', function (): void {
    test('owners and managers can read the sponsor show', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create([
                'name' => 'Harbor Hardware',
                'contact_email' => 'private@example.test',
            ]);

        actingAsPromotionMember($promotion, $role);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('sponsors.show')
            ->assertViewHas('sponsor', $sponsor)
            ->assertSee('private@example.test');

        expect(Sponsor::query()
            ->count())->toBe(1);
    })->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

    test('active members cannot read the sponsor show', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create([
                'name' => 'Harbor Hardware',
                'contact_email' => 'private@example.test',
            ]);

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertForbidden();

        expect(Sponsor::query()
            ->count())->toBe(1);
    });

    test('guests cannot read the sponsor show', function (): void {
        // Arrange
        $sponsor = Sponsor::factory()
            ->create(['notes' => 'PRIVATE-7Q']);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertRedirect(route('login'));

        expect(Sponsor::query()
            ->count())->toBe(1);
    });

    test('foreign sponsor records do not expose private notes', function (): void {
        // Arrange
        $sponsor = Sponsor::factory()
            ->create(['notes' => 'PRIVATE-7Q']);
        $promotion = Promotion::factory()
            ->create();

        actingAsPromotionMember($promotion, MembershipRole::Owner);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertNotFound()
            ->assertDontSee('PRIVATE-7Q');
    });

    test('a suspended membership revokes access to private sponsor records', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create(['notes' => 'PRIVATE-SUSPENDED-7Q']);

        $user = actingAsPromotionMember($promotion, MembershipRole::Owner);

        // An earlier request establishes access before the membership is revoked.
        $baselineResponse = get(route('sponsors.show', $sponsor));
        $baselineResponse
            ->assertOk();

        $promotion->users()
            ->updateExistingPivot($user->id, ['status' => MembershipStatus::Suspended]);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertForbidden()
            ->assertDontSee('PRIVATE-SUSPENDED-7Q');

        expect(Sponsor::query()
            ->withoutGlobalScopes()
            ->findOrFail($sponsor->id)->version)->toBe(1);
    });

    test('a removed membership revokes access to private sponsor records', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $sponsor = Sponsor::factory()
            ->for($promotion)
            ->create(['notes' => 'PRIVATE-SUSPENDED-7Q']);

        $user = actingAsPromotionMember($promotion, MembershipRole::Owner);

        // An earlier request establishes access before the membership is revoked.
        $baselineResponse = get(route('sponsors.show', $sponsor));
        $baselineResponse
            ->assertOk();

        $promotion->users()
            ->detach($user);

        // Act
        $response = get(route('sponsors.show', $sponsor));

        // Assert
        $response
            ->assertForbidden()
            ->assertDontSee('PRIVATE-SUSPENDED-7Q');

        expect(Sponsor::query()
            ->withoutGlobalScopes()
            ->findOrFail($sponsor->id)->version)->toBe(1);
    });

    test('missing sponsor records are not found', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();

        actingAsPromotionMember($promotion, MembershipRole::Owner);

        // Act
        $response = get(route('sponsors.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

});
