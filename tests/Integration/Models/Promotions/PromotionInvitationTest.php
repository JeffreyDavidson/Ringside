<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('it stores the email trimmed and lowercase', function (string $email) {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $invitation = PromotionInvitation::factory()->for($promotion)->create(['email' => $email]);

    // Assert
    expect($invitation->refresh()->email)->toBe('new.wrestler@example.test');
})->with([
    'already normalized' => ['new.wrestler@example.test'],
    'mixed case' => ['New.Wrestler@Example.TEST'],
    'surrounding whitespace' => ["  new.wrestler@example.test\t"],
]);

test('it casts the role to the membership role enum', function () {
    // Arrange
    $invitation = PromotionInvitation::factory()->withRole(MembershipRole::Manager)->create();

    // Act
    $role = $invitation->refresh()->role;

    // Assert
    expect($role)->toBe(MembershipRole::Manager);
});

test('it defaults the role to member', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $id = DB::table('promotion_invitations')->insertGetId([
        'promotion_id' => $promotion->id,
        'email' => 'default.role@example.test',
    ]);

    // Assert
    expect(PromotionInvitation::query()->findOrFail($id)->role)->toBe(MembershipRole::Member);
});

test('it belongs to its promotion and the promotion lists its invitations', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $invitation = PromotionInvitation::factory()->for($promotion)->create();
    PromotionInvitation::factory()->for($otherPromotion)->create();

    // Act
    $owner = $invitation->promotion()->first();
    $invitations = $promotion->invitations()->get();

    // Assert
    expect($owner?->is($promotion))->toBeTrue()
        ->and($invitations->modelKeys())->toBe([$invitation->id]);
});

test('the database allows one invitation per promotion and email', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->forEmail('twice@example.test')->create();

    // Act
    $otherPromotionInvitation = PromotionInvitation::factory()->for($otherPromotion)->forEmail('twice@example.test')->create();
    $duplicate = fn () => DB::transaction(
        fn () => PromotionInvitation::factory()->for($promotion)->forEmail('Twice@Example.test')->create(),
    );

    // Assert
    expect($otherPromotionInvitation->exists)->toBeTrue()
        ->and($duplicate)->toThrow(QueryException::class);
});

test('deleting a promotion deletes its invitations', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->create();
    $kept = PromotionInvitation::factory()->create();

    // Act
    $promotion->delete();

    // Assert
    expect(PromotionInvitation::query()->pluck('id')->all())->toBe([$kept->id]);
});
