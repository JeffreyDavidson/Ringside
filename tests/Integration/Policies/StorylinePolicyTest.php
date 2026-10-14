<?php

declare(strict_types=1);
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;

test('creative abilities use their stricter policy within the active promotion', function (MembershipRole $role, MembershipStatus $status, bool $allowed) {
    $promotion = Promotion::factory()->create();
    $story = Storyline::factory()->for($promotion)->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $gate = Gate::forUser($user);
    expect($gate->allows('viewAny', Storyline::class))->toBe($allowed)
        ->and($gate->allows('create', Storyline::class))->toBe($allowed)
        ->and($gate->allows('view', $story))->toBe($allowed)
        ->and($gate->allows('update', $story))->toBe($allowed)
        ->and($gate->allows('delete', $story))->toBeFalse();
})->with([
    'owner' => [MembershipRole::Owner, MembershipStatus::Active, true],
    'manager' => [MembershipRole::Manager, MembershipStatus::Active, true],
    'member' => [MembershipRole::Member, MembershipStatus::Active, false],
    'suspended' => [MembershipRole::Owner, MembershipStatus::Suspended, false],
]);
test('creative policies fail closed without enforced context and preserve the administrator ownership guard', function () {
    $promotion = Promotion::factory()->create();
    $foreign = Promotion::factory()->create();
    $story = Storyline::factory()->for($foreign)->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    $context = resolve(PromotionContextService::class);
    expect(Gate::forUser($user)->allows('viewAny', Storyline::class))->toBeFalse();
    $context->set($promotion);
    $context->enforce();
    expect(Gate::forUser($user)->allows('view', $story))->toBeFalse()
        ->and(Gate::forUser(administrator())->allows('view', $story))->toBeFalse();
});
