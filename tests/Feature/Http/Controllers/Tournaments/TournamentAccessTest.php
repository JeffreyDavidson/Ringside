<?php

declare(strict_types=1);
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Tournaments\TournamentData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Tournaments\EntrantKind;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('keeps foreign tournament bindings indistinguishable from missing records', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $entrants = Wrestler::factory()
        ->employed()
        ->for($promotion)
        ->count(4)
        ->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($entrants->map(fn (Wrestler $entrant): int => $entrant->id)
        ->all())));
    $other = Promotion::factory()
        ->create();

    $user = basicUser();
    $other->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    $baselineResponse = get(route('tournaments.show', PHP_INT_MAX));

    // Act
    $response = get(route('tournaments.show', $tournament));

    // Assert
    $response
        ->assertNotFound();

    $baselineResponse
        ->assertNotFound();

    expect(Tournament::query()
        ->withoutGlobalScopes()
        ->count())->toBe(1);
});

it('owners and managers see tournament operation controls', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $entrants = Wrestler::factory()
        ->employed()
        ->for($promotion)
        ->count(4)
        ->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($entrants->map(fn (Wrestler $entrant): int => $entrant->id)
        ->all())));

    actingAsPromotionMember($promotion, $role);

    // Act
    $response = get(route('tournaments.show', $tournament));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Autumn Cup')
        ->assertSee('Save draft');
})->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

it('members cannot see tournament operation controls', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $entrants = Wrestler::factory()
        ->employed()
        ->for($promotion)
        ->count(4)
        ->create();
    $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($entrants->map(fn (Wrestler $entrant): int => $entrant->id)
        ->all())));

    actingAsPromotionMember($promotion, MembershipRole::Member);

    // Act
    $response = get(route('tournaments.show', $tournament));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Autumn Cup')
        ->assertDontSee('Save draft')
        ->assertDontSee('Cancel tournament');
});

it('requires login for the tournament index', function (): void {
    // Arrange

    // Act
    $response = get(route('tournaments.index'));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

it('requires login for a tournament profile', function (): void {
    // Arrange
    $tournament = Tournament::factory()
        ->create();

    // Act
    $response = get(route('tournaments.show', $tournament));

    // Assert
    $response
        ->assertRedirect(route('login'));
});
