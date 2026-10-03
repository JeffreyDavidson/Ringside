<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\flushSession;
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

test('a remembered promotion the user can no longer use falls back to the first active promotion', function (?MembershipStatus $rememberedMembership) {
    // Arrange
    $user = basicUser();
    $rememberedPromotion = Promotion::factory()->create();
    $activePromotion = Promotion::factory()->create();
    attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);

    if ($rememberedMembership instanceof MembershipStatus) {
        attachPromotionMembership($user, $rememberedPromotion, $rememberedMembership);
    }

    actingAs($user);
    withSession(['active_promotion_id' => $rememberedPromotion->id]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertSuccessful();
    $response->assertSessionHas('active_promotion_id', $activePromotion->id);
    expect(resolve(PromotionContextService::class)->required()->id)->toBe($activePromotion->id);
})->with([
    'no membership' => [null],
    'suspended membership' => [MembershipStatus::Suspended],
    'invited membership' => [MembershipStatus::Invited],
]);

test('a remembered promotion that no longer exists falls back to the first active promotion', function () {
    // Arrange
    $user = basicUser();
    $activePromotion = Promotion::factory()->create();
    attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);
    actingAs($user);
    withSession(['active_promotion_id' => 999_999]);

    // Act
    $response = get(route('wrestlers.index'));

    // Assert
    $response->assertSuccessful();
    $response->assertSessionHas('active_promotion_id', $activePromotion->id);
});

test('users with no active promotion membership get the no-membership page', function () {
    // Arrange
    $user = basicUser();
    $suspendedPromotion = Promotion::factory()->create();
    attachPromotionMembership($user, $suspendedPromotion, MembershipStatus::Suspended);
    actingAs($user);
    withSession(['active_promotion_id' => $suspendedPromotion->id]);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertForbidden();
    $response->assertViewIs('promotions.no-membership');
});

test('the no-membership page offers a log out instead of promotion navigation that loops back to it', function () {
    // Arrange
    $user = basicUser();
    actingAs($user);

    // Act
    $response = get(route('dashboard'));

    // Assert
    $response->assertForbidden()
        ->assertSee(__('promotions.no_membership_title'))
        ->assertSee(__('promotions.no_membership_description'))
        ->assertSeeHtml(route('logout'))
        ->assertSee(__('auth-forms.log_out'))
        ->assertDontSeeHtml(route('wrestlers.index'))
        ->assertDontSeeHtml(route('events.index'))
        ->assertDontSeeHtml(route('dashboard'));
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

test('each request starts without the previous request promotion context', function () {
    // Arrange
    $member = basicUser();
    attachPromotionMembership($member, Promotion::factory()->create(), MembershipStatus::Active);
    $administrator = administrator();
    Route::middleware(['web', 'promotion.context'])->get('/promotion-context-probe', function () {
        $context = resolve(PromotionContextService::class);

        return response()->json([
            'enforced' => $context->isEnforced(),
            'promotion_id' => $context->current()?->id,
        ]);
    });
    actingAs($member)->get('/promotion-context-probe')->assertJson(['enforced' => true]);
    flushSession();

    // Act
    $response = actingAs($administrator)->get('/promotion-context-probe');

    // Assert
    $response->assertOk();
    $response->assertExactJson(['enforced' => false, 'promotion_id' => null]);
});
