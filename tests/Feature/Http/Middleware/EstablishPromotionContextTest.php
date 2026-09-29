<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withSession;

function attachPromotionMembership(User $user, Promotion $promotion, MembershipStatus $status): void
{
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => $status->value,
    ]);
}

test('unauthenticated requests are rejected with a 401', function () {
    // Arrange
    Route::middleware(['web', 'promotion.context'])->get('/promotion-context-guest', fn () => response()->json([
        'promotion_id' => resolve(PromotionContextService::class)->required()->id,
    ]));

    // Act
    $response = get('/promotion-context-guest');

    // Assert
    $response->assertUnauthorized();
});

test('a remembered promotion the user cannot access is rejected with a 403', function (?MembershipStatus $otherMembership) {
    // Arrange
    $user = basicUser();
    $memberPromotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    attachPromotionMembership($user, $memberPromotion, MembershipStatus::Active);

    if ($otherMembership instanceof MembershipStatus) {
        attachPromotionMembership($user, $otherPromotion, $otherMembership);
    }

    actingAs($user);
    withSession(['active_promotion_id' => $otherPromotion->id]);

    // Act
    $response = get(route('wrestlers.index'));

    // Assert
    $response->assertForbidden();
})->with([
    'no membership' => [null],
    'suspended membership' => [MembershipStatus::Suspended],
    'invited membership' => [MembershipStatus::Invited],
]);

test('a remembered promotion that no longer exists is rejected with a 403', function () {
    // Arrange
    $user = basicUser();
    attachPromotionMembership($user, Promotion::factory()->create(), MembershipStatus::Active);
    actingAs($user);
    withSession(['active_promotion_id' => 999_999]);

    // Act
    $response = get(route('wrestlers.index'));

    // Assert
    $response->assertForbidden();
});

test('users with no active promotion membership are rejected with a 403', function () {
    // Arrange
    $user = basicUser();
    attachPromotionMembership($user, Promotion::factory()->create(), MembershipStatus::Suspended);
    actingAs($user);

    // Act
    $response = get(route('wrestlers.index'));

    // Assert
    $response->assertForbidden();
});

test('the first active promotion is used when none has been selected', function () {
    // Arrange
    $user = basicUser();
    $firstPromotion = Promotion::factory()->create();
    attachPromotionMembership($user, Promotion::factory()->create(), MembershipStatus::Suspended);
    attachPromotionMembership($user, $firstPromotion, MembershipStatus::Active);
    actingAs($user);

    // Act
    $response = get(route('wrestlers.index'));

    // Assert
    $response->assertSuccessful();
    $response->assertSessionHas('active_promotion_id', $firstPromotion->id);
});
