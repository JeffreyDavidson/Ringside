<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the calendar requires authentication', function (): void {
    // Act
    $response = get(route('availability.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('the calendar shows only the current promotions unavailable people and reasons', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $foreignPromotion = Promotion::factory()->create();
    $wrestler = Wrestler::factory()->for($promotion)->create(['name' => 'Harbor Talent']);
    $foreignWrestler = Wrestler::factory()->for($foreignPromotion)->create(['name' => 'Foreign Talent']);

    $actor = administrator();
    resolve(CreateAction::class)->handle($wrestler, '2026-10-17', '2026-10-17', 'Local commitment', $actor);
    resolve(CreateAction::class)->handle($foreignWrestler, '2026-10-17', '2026-10-17', 'FOREIGN-PRIVATE-REASON', $actor);
    $member = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($member, [
        'role' => $role,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($member);

    // Act
    $response = get(route('availability.index', ['date' => '2026-10-17']));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('roster.availability')
        ->assertSee('Harbor Talent')
        ->assertSee('Local commitment')
        ->assertSee('America/New_York')
        ->assertDontSee('Foreign Talent')
        ->assertDontSee('FOREIGN-PRIVATE-REASON');
})->with([
    'owner' => MembershipRole::Owner,
    'manager' => MembershipRole::Manager,
    'member' => MembershipRole::Member,
]);

test('a global administrator must choose a promotion for the calendar', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('availability.index'));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Choose a promotion');
});

test('malformed initial calendar filters show a useful error rather than throwing', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();

    $member = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($member, [
        'role' => MembershipRole::Member,
        'status' => MembershipStatus::Active,
    ]);

    actingAs($member);

    // Act
    $response = get(route('availability.index', [
        'date' => 'not-a-date',
        'kind' => 'unknown',
    ]));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Enter a valid calendar date');
});
