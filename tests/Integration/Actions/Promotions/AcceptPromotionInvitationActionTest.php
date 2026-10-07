<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travel;

test('it creates an active membership with the invited role and deletes the invitation', function (MembershipRole $role) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'invitee@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('invitee@example.test')->withRole($role)->create();
    $otherInvitation = PromotionInvitation::factory()->for($otherPromotion)->forEmail('invitee@example.test')->create();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    $membership = $promotion->memberships()->where('user_id', $user->id)->sole();

    expect($accepted)->toBe($role)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->role)->toBe($role)
        ->and(promotionHasActiveMember($promotion, $user))->toBeTrue()
        ->and($promotion->invitations()->count())->toBe(0)
        ->and(promotionHasActiveMember($otherPromotion, $user))->toBeFalse()
        ->and($otherInvitation->fresh())->not->toBeNull();
})->with([
    'owner' => MembershipRole::Owner,
    'manager' => MembershipRole::Manager,
    'member' => MembershipRole::Member,
]);

test('it matches an invitation to the users email whatever the case or whitespace', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    DB::table('users')->where('id', $user->id)->update(['email' => ' Mixed.Case@Example.test ']);
    PromotionInvitation::factory()->for($promotion)->forEmail('MIXED.case@example.test')->withRole(MembershipRole::Manager)->create();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user->refresh());

    // Assert
    expect($accepted)->toBe(MembershipRole::Manager)
        ->and(promotionHasMemberWithRole($promotion, $user, MembershipRole::Manager))->toBeTrue();
});

test('it does nothing when there is no invitation for the users email', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'me@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('someone.else@example.test')->create();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($accepted)->toBeNull()
        ->and($promotion->memberships()->count())->toBe(0)
        ->and($promotion->invitations()->count())->toBe(1);
});

test('it never uses an invitation of another promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'me@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($otherPromotion)->forEmail('me@example.test')->create();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($accepted)->toBeNull()
        ->and(promotionHasActiveMember($promotion, $user))->toBeFalse()
        ->and($otherPromotion->invitations()->count())->toBe(1);
});

test('an invitation never changes an existing membership and is deleted', function (MembershipStatus $status, MembershipRole $role) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);
    PromotionInvitation::factory()->for($promotion)->forEmail('member@example.test')->withRole(MembershipRole::Owner)->create();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    $membership = $promotion->memberships()->sole();

    expect($accepted)->toBeNull()
        ->and($membership->status)->toBe($status)
        ->and($membership->role)->toBe($role)
        ->and($promotion->invitations()->count())->toBe(0);
})->with([
    'suspended member stays suspended' => [MembershipStatus::Suspended, MembershipRole::Member],
    'active member keeps their role' => [MembershipStatus::Active, MembershipRole::Manager],
]);

test('it cannot be accepted twice', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'once@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('once@example.test')->create();
    app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Act
    $second = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($second)->toBeNull()
        ->and($promotion->memberships()->count())->toBe(1);
});

test('it locks the promotion and then the invitation before it writes anything', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'lock@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('lock@example.test')->create();

    // Act
    $statements = recordStatements(fn () => app(AcceptPromotionInvitationAction::class)->handle($promotion, $user));

    // Assert
    $promotionLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "promotions"'));
    $invitationLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "promotion_invitations"'));
    $firstWrite = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert') || str_starts_with($statement['sql'], 'delete'));

    expect($promotionLock)->toBeLessThan($invitationLock)
        ->and($invitationLock)->toBeLessThan($firstWrite);
});

test('it forgets the memoised memberships and invitations so access is reflected immediately', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'memo@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('memo@example.test')->create();
    $context = app(PromotionContextService::class);
    $roleBefore = $context->membershipRole($user, $promotion);
    $invitationsBefore = $context->pendingInvitationsFor($user);
    $activeBefore = $context->activePromotionsFor($user);

    // Act
    app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($roleBefore)->toBeNull()
        ->and($invitationsBefore)->toHaveCount(1)
        ->and($activeBefore)->toBeEmpty()
        ->and($context->membershipRole($user, $promotion))->toBe(MembershipRole::Member)
        ->and($context->pendingInvitationsFor($user))->toBeEmpty()
        ->and($context->activePromotionsFor($user)->modelKeys())->toBe([$promotion->id]);
});

test('it accepts an invitation one second before it expires', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'invitee@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('invitee@example.test')->create();
    travel(30 * 86400 - 1)->seconds();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($accepted)->toBe(MembershipRole::Member)
        ->and(promotionHasActiveMember($promotion, $user))->toBeTrue();
});

test('it refuses an expired invitation and creates no membership', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'invitee@example.test', 'status' => UserStatus::Active]);
    PromotionInvitation::factory()->for($promotion)->forEmail('invitee@example.test')->create();
    travel(30)->days();

    // Act
    $accepted = app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($accepted)->toBeNull()
        ->and($promotion->memberships()->count())->toBe(0);
});

test('it forgets the memoised invitations when it deletes an invitation of an existing member', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Suspended]);
    PromotionInvitation::factory()->for($promotion)->forEmail('member@example.test')->create();
    $context = app(PromotionContextService::class);
    $invitationsBefore = $context->pendingInvitationsFor($user);

    // Act
    app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);

    // Assert
    expect($invitationsBefore)->toHaveCount(1)
        ->and($context->pendingInvitationsFor($user))->toBeEmpty();
});
