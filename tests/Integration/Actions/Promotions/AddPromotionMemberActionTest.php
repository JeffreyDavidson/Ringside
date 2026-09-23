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
