<?php

declare(strict_types=1);
use App\Actions\Tournaments\SaveTournamentAction;
use App\Data\Tournaments\TournamentData;
use App\Enums\Tournaments\EntrantKind;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Tournaments\Tournament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('renders the tournament listing for an authorized operator', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $entrants = Wrestler::factory()
            ->employed()
            ->for($promotion)
            ->count(4)
            ->create();
        $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($entrants->map(fn (Wrestler $entrant): int => $entrant->id)
            ->all())), administrator());
        actingAs(administrator());

        // Act
        $response = get(route('tournaments.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('tournaments.index')
            ->assertSee('Autumn Cup');
    });

    test('a user without memberships cannot open the tournament index', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('tournaments.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

});

describe('show', function (): void {
    test('shows the bracket and readable rounds to an authorized promoter', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $entrants = Wrestler::factory()
            ->employed()
            ->for($promotion)
            ->count(4)
            ->create();
        $tournament = resolve(SaveTournamentAction::class)->handle($promotion, new TournamentData('Autumn Cup', null, EntrantKind::Wrestler, 4, true, array_values($entrants->map(fn (Wrestler $entrant): int => $entrant->id)
            ->all())), administrator());

        actingAs(administrator());

        // Act
        $response = get(route('tournaments.show', $tournament));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('tournaments.show')
            ->assertViewHas('tournament', $tournament)
            ->assertSee('Autumn Cup')
            ->assertSee('Rounds')
            ->assertSee('Bracket');
    });

    test('a user without memberships cannot open a tournament profile', function (): void {
        // Arrange
        $tournament = Tournament::factory()
            ->create();
        actingAs(basicUser());

        // Act
        $response = get(route('tournaments.show', $tournament));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

});
