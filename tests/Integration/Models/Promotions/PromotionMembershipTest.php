<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Users\User;

it('resolves the promotion and the user of a membership', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $otherPromotion->users()->attach(User::factory()->create());
    $promotion->users()->attach($user);
    $membership = $promotion->memberships()->sole();

    $membershipPromotion = $membership->promotion()->first();
    $membershipUser = $membership->user()->first();

    expect($membershipPromotion?->is($promotion))->toBeTrue()
        ->and($membershipUser?->is($user))->toBeTrue();
});
