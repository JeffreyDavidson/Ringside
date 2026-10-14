<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

test('a user can switch to another active promotion', function () {
    // Arrange
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    [$firstPromotion, $secondPromotion] = Promotion::factory()->count(2)->create()->all();
    $firstPromotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);
    $secondPromotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    // Act
    $response = from(route('dashboard'))
        ->actingAs($user)
        ->post(route('promotions.switch'), ['promotion_id' => $secondPromotion->id]);

    // Assert
    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('promotions.switched'));

    expect(session('active_promotion_id'))->toBe($secondPromotion->id);
});

test('a user cannot switch to a promotion without an active membership', function () {
    // Arrange
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    [$promotion, $otherPromotion] = Promotion::factory()->count(2)->create()->all();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);

    // Act
    $response = post(route('promotions.switch'), ['promotion_id' => $otherPromotion->id]);

    // Assert
    $response
        ->assertForbidden();

    expect(session('active_promotion_id'))->toBeNull();
});

test('a guest cannot switch promotions', function (): void {
    // Act
    $response = post(route('promotions.switch'), ['promotion_id' => 1]);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionMissing('active_promotion_id');
});

test('invalid selection keeps the previous promotion', function (mixed $id): void {
    // Arrange
    $promotion = Promotion::factory()->create();

    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);
    withSession(['active_promotion_id' => $promotion->id]);

    // Act
    $response = from(route('dashboard'))->post(route('promotions.switch'), ['promotion_id' => $id]);

    // Assert
    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('promotion_id')
        ->assertSessionHas('active_promotion_id', $promotion->id);
})->with([
    'missing' => null,
    'noninteger' => 'invalid',
]);

test('a refused selection keeps the previous promotion', function (): void {
    // Arrange
    [$promotion, $other] = Promotion::factory()->count(2)->create()->all();
    $user = basicUser();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($user);
    withSession(['active_promotion_id' => $promotion->id]);

    // Act
    $response = post(route('promotions.switch'), ['promotion_id' => $other->id]);

    // Assert
    $response
        ->assertForbidden()
        ->assertSessionHas('active_promotion_id', $promotion->id);
});
