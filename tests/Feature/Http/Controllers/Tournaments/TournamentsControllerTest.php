<?php

declare(strict_types=1);
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the bracket and readable rounds to an authorized promoter', function (): void {
    // Arrange
    $world = tournamentFixture();
    actingAs(administrator());
    // Act
    $response = get(route('tournaments.show', $world['tournament']));

    // Assert
    $response->assertOk()->assertViewIs('tournaments.show')->assertViewHas('tournament', $world['tournament'])->assertSee('Autumn Cup')->assertSee('Rounds')->assertSee('Bracket');
});

it('renders the tournament listing for an authorized operator', function (): void {
    // Arrange
    $world = tournamentFixture();
    \Pest\Laravel\actingAs(administrator());
    // Act
    $response = get(route('tournaments.index'));

    // Assert
    $response->assertOk()->assertViewIs('tournaments.index')->assertSee('Autumn Cup');
});
