<?php

use App\Actions\Promotions\AddPromotionMemberAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it adds an active user to the selected promotion with the given role', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);

    app(AddPromotionMemberAction::class)->handle($promotion, $user, MembershipRole::Manager);

    $membership = $promotion->memberships()->where('user_id', $user->id)->firstOrFail();

    expect($membership->role)->toBe(MembershipRole::Manager)
        ->and($membership->status)->toBe(MembershipStatus::Active);
});

test('it leaves an existing membership unchanged when the user is added again', function (MembershipStatus $status) {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => $status,
    ]);

    app(AddPromotionMemberAction::class)->handle($promotion, $user, MembershipRole::Owner);

    $memberships = $promotion->memberships()->where('user_id', $user->id)->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->firstOrFail()->role)->toBe(MembershipRole::Member)
        ->and($memberships->firstOrFail()->status)->toBe($status);
})->with([
    'active membership' => MembershipStatus::Active,
    'suspended membership' => MembershipStatus::Suspended,
    'invited membership' => MembershipStatus::Invited,
]);
