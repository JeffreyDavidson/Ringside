<?php

use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it updates the membership status without deleting the relationship', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    app(UpdatePromotionMemberStatusAction::class)->handle($promotion, $user, MembershipStatus::Suspended);

    expect($promotion->memberships()->count())->toBe(1)
        ->and($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->status)
        ->toBe(MembershipStatus::Suspended);
});
