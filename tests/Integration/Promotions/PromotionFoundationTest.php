<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContext;
use Illuminate\Database\QueryException;

test('a promotion can have global users with scoped membership data', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();

    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    $membership = $promotion->memberships()->sole();

    expect($user->promotions()->sole()->is($promotion))->toBeTrue()
        ->and($membership)->toBeInstanceOf(PromotionMembership::class)
        ->and($membership->role)->toBe(MembershipRole::Owner)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($promotion->hasActiveMember($user))->toBeTrue()
        ->and($promotion->hasMemberWithRole($user, MembershipRole::Owner))->toBeTrue();
});

test('a user cannot have duplicate membership in the same promotion', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();

    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member->value,
        'status' => MembershipStatus::Active->value,
    ]);

    expect(fn () => $promotion->users()->attach($user, [
        'role' => MembershipRole::Member->value,
        'status' => MembershipStatus::Active->value,
    ]))->toThrow(QueryException::class);
});

test('promotion context is explicitly established and required', function () {
    $context = app(PromotionContext::class);
    $promotion = Promotion::factory()->create();

    expect($context->current())->toBeNull()
        ->and(fn () => $context->required())->toThrow(LogicException::class);

    $context->set($promotion);

    expect($context->current())->toBe($promotion)
        ->and($context->required())->toBe($promotion);

    $context->clear();

    expect($context->current())->toBeNull();
});
