<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

const MOVE_INVITATIONS_MIGRATION = 'migrations/2026_10_06_024541_move_pending_promotion_invitations_to_promotion_invitations.php';

function insertPivot(Promotion $promotion, User $user, string $status, MembershipRole $role = MembershipRole::Member): void
{
    DB::table('promotion_user')->insert([
        'promotion_id' => $promotion->id,
        'user_id' => $user->id,
        'role' => $role->value,
        'status' => $status,
        'created_at' => '2026-01-02 03:04:05',
        'updated_at' => '2026-01-03 04:05:06',
    ]);
}

function runMoveInvitationsMigration(): void
{
    $migration = require database_path(MOVE_INVITATIONS_MIGRATION);
    $migration->up();
}

test('it converts every invited membership into an invitation for the invited email and deletes the membership', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.test']);
    $legacy = User::factory()->create();
    DB::table('users')->where('id', $legacy->id)->update(['email' => '  Legacy.Mixed@Example.TEST ']);
    insertPivot($promotion, $invitee, 'invited', MembershipRole::Manager);
    insertPivot($otherPromotion, $invitee, 'invited', MembershipRole::Owner);
    insertPivot($promotion, $legacy, 'invited');

    // Act
    runMoveInvitationsMigration();

    // Assert
    $invitations = DB::table('promotion_invitations')->orderBy('promotion_id')->orderBy('email')->get();

    expect($invitations->map(fn (stdClass $row): array => [$row->promotion_id, $row->email, $row->role])->all())
        ->toBe([
            [$promotion->id, 'invitee@example.test', 'manager'],
            [$promotion->id, 'legacy.mixed@example.test', 'member'],
            [$otherPromotion->id, 'invitee@example.test', 'owner'],
        ])
        ->and($invitations->pluck('created_at')->unique()->all())->toBe(['2026-01-02 03:04:05'])
        ->and($invitations->pluck('updated_at')->unique()->all())->toBe(['2026-01-03 04:05:06'])
        ->and(DB::table('promotion_user')->count())->toBe(0);
});

test('it leaves active and suspended memberships alone', function (string $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $member = User::factory()->create();
    insertPivot($promotion, $member, $status, MembershipRole::Owner);

    // Act
    runMoveInvitationsMigration();

    // Assert
    expect(DB::table('promotion_invitations')->count())->toBe(0)
        ->and(DB::table('promotion_user')->where('status', $status)->count())->toBe(1);
})->with(['active', 'suspended']);

test('it keeps an invitation that already exists for the email and still removes the membership', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create(['email' => 'twice@example.test']);
    insertPivot($promotion, $user, 'invited', MembershipRole::Owner);
    DB::table('promotion_invitations')->insert([
        'promotion_id' => $promotion->id,
        'email' => 'twice@example.test',
        'role' => 'member',
    ]);

    // Act
    runMoveInvitationsMigration();

    // Assert
    expect(DB::table('promotion_invitations')->pluck('role')->all())->toBe(['member'])
        ->and(DB::table('promotion_user')->count())->toBe(0);
});

test('it converts more invited memberships than fit in one batch', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $users = User::factory()->count(501)->create();
    DB::table('promotion_user')->insert($users->map(fn (User $user): array => [
        'promotion_id' => $promotion->id,
        'user_id' => $user->id,
        'role' => 'member',
        'status' => 'invited',
    ])->all());

    // Act
    runMoveInvitationsMigration();

    // Assert
    expect(DB::table('promotion_invitations')->count())->toBe(501)
        ->and(DB::table('promotion_user')->count())->toBe(0);
});

test('a membership written without a status is active and a member can still only join a promotion once', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    DB::table('promotion_user')->insert(['promotion_id' => $promotion->id, 'user_id' => $user->id]);

    // Act
    $duplicate = fn () => DB::transaction(
        fn () => DB::table('promotion_user')->insert(['promotion_id' => $promotion->id, 'user_id' => $user->id]),
    );

    // Assert
    expect(DB::table('promotion_user')->value('status'))->toBe('active')
        ->and($duplicate)->toThrow(QueryException::class);
});

test('changing the default status keeps the membership foreign keys cascading', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = User::factory()->create();
    $promotion->users()->attach($user);

    // Act
    $promotion->delete();

    // Assert
    expect(DB::table('promotion_user')->count())->toBe(0);
});
