<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\followingRedirects;
use function Pest\Laravel\get;

describe('pending invitations in the sidebar', function () {
    test('an administrator without a membership sees a pending invitation in the sidebar', function () {
        // Arrange
        $admin = administrator();
        $promotion = Promotion::factory()->create(['name' => 'Sidebar Wrestling']);
        PromotionInvitation::factory()->for($promotion)->forEmail($admin->email)->withRole(MembershipRole::Manager)->create();
        actingAs($admin);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertSuccessful()
            ->assertSeeHtml('data-test="pending-invitations"')
            ->assertSee('Sidebar Wrestling')
            ->assertSeeHtml(route('promotions.invitation.accept', $promotion))
            ->assertSeeHtml(route('promotions.invitation.decline', $promotion));
    });

    test('an administrator is told that accepting scopes them to the promotion, a regular user is not', function (callable $makeUser, int $status, bool $seesNote): void {
        // Arrange
        $user = $makeUser();
        $promotion = Promotion::factory()->create();
        PromotionInvitation::factory()->for($promotion)->forEmail($user->email)->withRole(MembershipRole::Manager)->create();
        actingAs($user);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response->assertStatus($status)->assertSeeHtml('data-test="pending-invitations"');
        expect(str_contains((string) $response->getContent(), 'data-test="invitation-admin-note"'))->toBe($seesNote);
    })->with([
        'administrator' => [administrator(...), 200, true],
        'regular user' => [basicUser(...), 403, false],
    ]);

    test('an administrator accepting the invitation then gets the promotion in the switcher', function () {
        // Arrange
        $admin = administrator();
        $promotion = Promotion::factory()->create(['name' => 'Accepted Wrestling']);
        PromotionInvitation::factory()->for($promotion)->forEmail($admin->email)->withRole(MembershipRole::Manager)->create();
        actingAs($admin);

        // Act
        $response = followingRedirects()->post(route('promotions.invitation.accept', $promotion));

        // Assert
        $response
            ->assertSuccessful()
            ->assertSee('Accepted Wrestling')
            ->assertSeeHtml('id="promotion-menu"')
            ->assertDontSeeHtml('data-test="pending-invitations"');
        expect(promotionHasActiveMember($promotion, $admin))->toBeTrue();
    });

    test('an administrator without invitations sees no invitations block', function () {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertSuccessful()
            ->assertDontSeeHtml('data-test="pending-invitations"');
    });

    test('a member with an active promotion sees the invitation only once, inside the switcher', function () {
        // Arrange
        $user = basicUser();
        $own = Promotion::factory()->create();
        $inviting = Promotion::factory()->create(['name' => 'Inviting Wrestling']);
        $own->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        PromotionInvitation::factory()->for($inviting)->forEmail($user->email)->withRole(MembershipRole::Manager)->create();
        actingAs($user);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertSuccessful()
            ->assertSeeHtml('data-test="invitation-indicator"')
            ->assertDontSeeHtml('data-test="invitation-collapsed-toggle"');
        expect(substr_count((string) $response->getContent(), 'data-test="pending-invitations"'))->toBe(1);
    });
});
