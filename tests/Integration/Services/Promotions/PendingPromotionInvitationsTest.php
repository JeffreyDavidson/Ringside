<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travel;

test('it lists the invitations for the users email with their promotion, oldest first and by id on a tie', function () {
    // Arrange
    $user = User::factory()->create(['email' => 'invitee@example.test']);
    [$first, $second, $third] = Promotion::factory()->count(3)->create()->all();
    $newest = PromotionInvitation::factory()->for($third)->forEmail('Invitee@Example.test')->create();
    travel(-1)->day();
    $oldestLowerId = PromotionInvitation::factory()->for($first)->forEmail('invitee@example.test')->create();
    $oldestHigherId = PromotionInvitation::factory()->for($second)->forEmail('invitee@example.test')->create();
    PromotionInvitation::factory()->for($first)->forEmail('someone.else@example.test')->create();

    // Act
    $invitations = app(PromotionContextService::class)->pendingInvitationsFor($user);

    // Assert
    expect($invitations->modelKeys())->toBe([$oldestLowerId->id, $oldestHigherId->id, $newest->id])
        ->and($invitations->every(fn (PromotionInvitation $invitation): bool => $invitation->relationLoaded('promotion')))->toBeTrue()
        ->and($invitations->first()?->role)->toBe(MembershipRole::Member);
});

test('it matches a user whose stored email still has mixed case', function () {
    // Arrange
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['email' => ' Legacy@Example.TEST ']);
    $invitation = PromotionInvitation::factory()->forEmail('legacy@example.test')->create();

    // Act
    $invitations = app(PromotionContextService::class)->pendingInvitationsFor($user->refresh());

    // Assert
    expect($invitations->modelKeys())->toBe([$invitation->id]);
});

test('it keeps invitations apart from memberships of the same promotion', function (MembershipStatus $status) {
    // Arrange
    $user = User::factory()->create(['email' => 'both@example.test']);
    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => $status]);
    PromotionInvitation::factory()->for($promotion)->forEmail('both@example.test')->create();
    $context = app(PromotionContextService::class);

    // Act
    $invitations = $context->pendingInvitationsFor($user);
    $active = $context->activePromotionsFor($user);

    // Assert
    expect($invitations)->toHaveCount(1)
        ->and($active->modelKeys())->toBe($status === MembershipStatus::Active ? [$promotion->id] : []);
})->with([
    'active member' => MembershipStatus::Active,
    'suspended member' => MembershipStatus::Suspended,
]);

test('it reads the invitations once per request until memberships are forgotten', function () {
    // Arrange
    $user = User::factory()->create(['email' => 'memo@example.test']);
    $context = app(PromotionContextService::class);
    $empty = $context->pendingInvitationsFor($user);
    PromotionInvitation::factory()->forEmail('memo@example.test')->create();

    // Act
    $stale = $context->pendingInvitationsFor($user);
    $context->forgetMemberships();
    $fresh = $context->pendingInvitationsFor($user);

    // Assert
    expect($empty)->toBeEmpty()
        ->and($stale)->toBeEmpty()
        ->and($fresh)->toHaveCount(1);
});

test('it hides an invitation from the second it expires and not before', function () {
    // Arrange
    $user = User::factory()->create(['email' => 'invitee@example.test']);
    $invitation = PromotionInvitation::factory()->forEmail('invitee@example.test')->create();
    travel(30 * 86400 - 1)->seconds();

    // Act
    $beforeExpiry = app(PromotionContextService::class)->pendingInvitationsFor($user)->modelKeys();
    travel(1)->seconds();
    app(PromotionContextService::class)->forgetMemberships();
    $atExpiry = app(PromotionContextService::class)->pendingInvitationsFor($user)->modelKeys();

    // Assert
    expect($beforeExpiry)->toBe([$invitation->id])
        ->and($atExpiry)->toBeEmpty();
});
