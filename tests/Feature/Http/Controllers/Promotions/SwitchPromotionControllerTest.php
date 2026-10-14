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
    $user = User::factory()
        ->basicUser()
        ->create(['status' => UserStatus::Active]);
    [$firstPromotion, $secondPromotion] = Promotion::factory()
        ->count(2)
        ->create()
        ->all();
    $firstPromotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);
    $secondPromotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);
    withSession(['active_promotion_id' => $firstPromotion->id]);

    $requestData = ['promotion_id' => $secondPromotion->id];

    // Act
    $response = from(route('dashboard'))
        ->post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', __('promotions.switched'));

    expect(session('active_promotion_id'))->toBe($secondPromotion->id);
});

test('a user cannot switch to a promotion without an active membership', function () {
    // Arrange
    $user = User::factory()
        ->basicUser()
        ->create(['status' => UserStatus::Active]);
    [$promotion, $otherPromotion] = Promotion::factory()
        ->count(2)
        ->create()
        ->all();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    $requestData = ['promotion_id' => $otherPromotion->id];

    // Act
    $response = post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertForbidden();

    expect(session('active_promotion_id'))->toBeNull();
});

test('a guest cannot switch promotions', function (): void {
    $requestData = ['promotion_id' => 1];

    // Act
    $response = post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertRedirect(route('login'))
        ->assertSessionMissing('active_promotion_id');
});

test('invalid selection keeps the previous promotion', function (mixed $id): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();

    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);
    withSession(['active_promotion_id' => $promotion->id]);

    $requestData = ['promotion_id' => $id];

    // Act
    $response = from(route('dashboard'))->post(route('promotions.switch'), $requestData);

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
    [$promotion, $other] = Promotion::factory()
        ->count(2)
        ->create()
        ->all();
    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);
    withSession(['active_promotion_id' => $promotion->id]);

    $requestData = ['promotion_id' => $other->id];

    // Act
    $response = post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertForbidden()
        ->assertSessionHas('active_promotion_id', $promotion->id);
});

test('a missing selection is refused without revealing promotion existence', function (): void {
    // Arrange
    actingAs(basicUser());
    $requestData = ['promotion_id' => 999999];

    // Act
    $response = post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertForbidden()
        ->assertSessionMissing('active_promotion_id');
});

test('an account without memberships cannot select an existing promotion', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    actingAs(basicUser());
    $requestData = ['promotion_id' => $promotion->id];

    // Act
    $response = post(route('promotions.switch'), $requestData);

    // Assert
    $response
        ->assertForbidden()
        ->assertSessionMissing('active_promotion_id');
});
