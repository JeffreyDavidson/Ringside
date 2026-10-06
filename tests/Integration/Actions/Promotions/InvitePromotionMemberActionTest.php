<?php

declare(strict_types=1);

use App\Actions\Promotions\InvitePromotionMemberAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Promotions\PromotionInvitationOutcome;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travel;

test('it saves a pending invitation for an email without an account and grants no access', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'Nobody.Yet@Example.test ', MembershipRole::Manager);

    // Assert
    $invitation = $promotion->invitations()->sole();

    expect($outcome)->toBe(PromotionInvitationOutcome::Invited)
        ->and($invitation->email)->toBe('nobody.yet@example.test')
        ->and($invitation->role)->toBe(MembershipRole::Manager)
        ->and($promotion->memberships()->count())->toBe(0);
});

test('it saves the same invitation whatever the state of the account behind the email', function (?UserStatus $status) {
    // Arrange
    $promotion = Promotion::factory()->create();

    if ($status instanceof UserStatus) {
        User::factory()->create(['email' => 'known@example.test', 'status' => $status]);
    }

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'known@example.test', MembershipRole::Member);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::Invited)
        ->and($promotion->invitations()->sole()->email)->toBe('known@example.test')
        ->and($promotion->memberships()->count())->toBe(0);
})->with([
    'no account' => [null],
    'active account' => [UserStatus::Active],
    'inactive account' => [UserStatus::Inactive],
    'unverified account' => [UserStatus::Unverified],
]);

test('it ignores a membership of another promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $elsewhere = User::factory()->create(['email' => 'elsewhere@example.test', 'status' => UserStatus::Active]);
    $otherPromotion->users()->attach($elsewhere, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    // Act
    $elsewhereOutcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'elsewhere@example.test', MembershipRole::Member);

    // Assert
    expect($elsewhereOutcome)->toBe(PromotionInvitationOutcome::Invited)
        ->and($promotion->invitations()->count())->toBe(1);
});

test('it reports an invitation that is already pending without changing it, whatever the email spelling', function (string $email) {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('pending@example.test')->withRole(MembershipRole::Member)->create();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, $email, MembershipRole::Owner);

    // Assert
    $invitation = $promotion->invitations()->sole();

    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyInvited)
        ->and($invitation->role)->toBe(MembershipRole::Member);
})->with([
    'same email' => ['pending@example.test'],
    'other case' => ['Pending@Example.TEST'],
    'whitespace' => ['  pending@example.test '],
]);

test('it invites the same email to another promotion', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($otherPromotion)->forEmail('both@example.test')->create();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'both@example.test', MembershipRole::Member);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::Invited)
        ->and(PromotionInvitation::query()->count())->toBe(2);
});

test('it reports an email that already has a membership and never invites it', function (MembershipStatus $status, string $email) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => $status]);

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, $email, MembershipRole::Owner);

    // Assert
    $membership = $promotion->memberships()->sole();

    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyMember)
        ->and($promotion->invitations()->count())->toBe(0)
        ->and($membership->role)->toBe(MembershipRole::Member)
        ->and($membership->status)->toBe($status);
})->with([
    'active member' => [MembershipStatus::Active, 'member@example.test'],
    'suspended member' => [MembershipStatus::Suspended, 'member@example.test'],
    'active member in other case' => [MembershipStatus::Active, ' Member@Example.TEST'],
    'suspended member in other case' => [MembershipStatus::Suspended, 'MEMBER@example.test '],
]);

test('it finds a member stored with a mixed case email', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    DB::table('users')->where('id', $member->id)->update(['email' => 'Legacy.Member@Example.test']);

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'legacy.member@example.test', MembershipRole::Member);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyMember);
});

test('it never lets a wildcard in the email match another account', function (string $email) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, $email, MembershipRole::Member);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::Invited);
})->with([
    'percent' => ['%@example.test'],
    'underscore' => ['membe_@example.test'],
]);

test('it locks the promotion before it reads or writes invitations', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $statements = recordStatements(fn () => app(InvitePromotionMemberAction::class)->handle($promotion, 'locked@example.test', MembershipRole::Member));

    // Assert
    $lock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "promotions"'));
    $firstInvitationStatement = statementPosition($statements, fn (array $statement): bool => str_contains($statement['sql'], '"promotion_invitations"'));

    expect(lockedRowIds($statements, 'promotions'))->toBe([$promotion->id])
        ->and($lock)->toBeLessThan($firstInvitationStatement);
});

test('it forgets the memoised invitations of the user', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'memo@example.test', 'status' => UserStatus::Active]);
    $context = app(PromotionContextService::class);
    $before = $context->pendingInvitationsFor($user);

    // Act
    app(InvitePromotionMemberAction::class)->handle($promotion, 'memo@example.test', MembershipRole::Member);

    // Assert
    expect($before)->toBeEmpty()
        ->and($context->pendingInvitationsFor($user))->toHaveCount(1);
});

test('it replaces an expired invitation with a fresh one for the new role', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('again@example.test')->withRole(MembershipRole::Member)->expired()->create();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'Again@example.test', MembershipRole::Manager);

    // Assert
    $invitation = $promotion->invitations()->sole();

    expect($outcome)->toBe(PromotionInvitationOutcome::Invited)
        ->and($invitation->role)->toBe(MembershipRole::Manager)
        ->and($invitation->expires_at->toDateTimeString())->toBe(now()->addDays(30)->toDateTimeString());
});

test('it keeps an invitation that has not expired yet', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('again@example.test')->withRole(MembershipRole::Member)->create();
    travel(30 * 86400 - 1)->seconds();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'again@example.test', MembershipRole::Owner);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyInvited)
        ->and($promotion->invitations()->sole()->role)->toBe(MembershipRole::Member);
});

test('it leaves an expired invitation alone when the email already belongs to a member', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    $expired = PromotionInvitation::factory()->for($promotion)->forEmail('member@example.test')->expired()->create();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'member@example.test', MembershipRole::Owner);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyMember)
        ->and($expired->fresh())->not->toBeNull();
});

test('it counts a soft-deleted account that still has a membership as already a member', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'gone@example.test', 'status' => UserStatus::Active]);
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    $user->delete();

    // Act
    $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, 'Gone@Example.test', MembershipRole::Manager);

    // Assert
    expect($outcome)->toBe(PromotionInvitationOutcome::AlreadyMember)
        ->and($promotion->invitations()->count())->toBe(0);
});
