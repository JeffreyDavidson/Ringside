<?php

use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;

test('it updates the role of a user in the selected promotion', function () {
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $user, MembershipRole::Owner);

    expect($promotion->memberships()->where('user_id', $user->id)->firstOrFail()->role)
        ->toBe(MembershipRole::Owner);
});

test('it locks the promotion row before the membership it changes', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $owners = User::factory()->count(2)->create(['status' => UserStatus::Active]);
    $owners->each(fn (User $owner) => $promotion->users()->attach($owner, [
        'role' => MembershipRole::Owner,
        'status' => MembershipStatus::Active,
    ]));

    // Act
    $statements = recordStatements(fn () => app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $owners->firstOrFail(), MembershipRole::Member));

    // Assert
    $lockedTables = collect($statements)
        ->filter(fn (array $statement): bool => $statement['locked'])
        ->map(fn (array $statement): string => str_contains($statement['sql'], 'from "promotions"') ? 'promotions' : 'other')
        ->values()
        ->all();

    expect(lockedRowIds($statements, 'promotions'))->toBe([$promotion->id])
        ->and($lockedTables[0] ?? null)->toBe('promotions');
});
