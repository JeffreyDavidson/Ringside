<?php

use App\Actions\Promotions\SwitchActivePromotionAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it stores an active promotion membership in the session', function () {
    $user = User::factory()->basicUser()->create();
    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    app(SwitchActivePromotionAction::class)->handle($user, $promotion->id, app('session.store'));

    expect(session('active_promotion_id'))->toBe($promotion->id);
});
