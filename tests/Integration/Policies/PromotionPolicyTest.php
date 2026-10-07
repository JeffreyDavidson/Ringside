<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Policies\PromotionPolicy;
use Illuminate\Support\Facades\Gate;

it('resolves the promotion policy for promotions', function () {
    $promotion = Promotion::factory()->create();

    $policy = Gate::getPolicyFor($promotion);

    expect($policy)->toBeInstanceOf(PromotionPolicy::class);
});

/*
 * PromotionGate::before() decides every ability on a Promotion instance for non-administrators from
 * their active membership, so PromotionPolicy is never consulted through the Gate. The policy itself
 * never grants anything: these tests call it directly to pin that contract.
 */
it('never grants an instance ability on its own', function (string $ability) {
    $promotion = Promotion::factory()->create();
    $owner = basicUser();
    $promotion->users()->attach($owner, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $policy = new PromotionPolicy;

    $allowed = $policy->{$ability}($owner, $promotion);

    expect($allowed)->toBeFalse();
})->with(['view', 'manageMembers', 'update', 'delete', 'restore']);

it('never grants a class ability on its own', function (string $ability) {
    $policy = new PromotionPolicy;

    $allowed = $policy->{$ability}(basicUser());

    expect($allowed)->toBeFalse();
})->with(['viewAny', 'create']);

it('denies class abilities on promotions to users without a promotion role', function (string $ability) {
    $basicUser = basicUser();

    $decision = Gate::forUser($basicUser)->inspect($ability, Promotion::class);

    expect($decision->allowed())->toBeFalse();
})->with(['viewAny', 'create']);

it('denies every ability on a promotion to a user who is not a member', function (string $ability) {
    $promotion = Promotion::factory()->create();
    $basicUser = basicUser();

    $decision = Gate::forUser($basicUser)->inspect($ability, $promotion);

    expect($decision->allowed())->toBeFalse();
})->with(['view', 'manageMembers', 'update', 'delete', 'restore']);

it('lets an active owner view, update and manage members but not delete or restore', function (string $ability, bool $expected) {
    $promotion = Promotion::factory()->create();
    $owner = basicUser();
    $promotion->users()->attach($owner, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    $allowed = Gate::forUser($owner)->allows($ability, $promotion);

    expect($allowed)->toBe($expected);
})->with([
    'view' => ['view', true],
    'manageMembers' => ['manageMembers', true],
    'update' => ['update', true],
    'delete' => ['delete', false],
    'restore' => ['restore', false],
]);
