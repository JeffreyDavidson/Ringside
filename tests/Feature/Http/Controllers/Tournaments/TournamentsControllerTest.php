<?php

declare(strict_types=1);
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the bracket and readable rounds to an authorized promoter', function (): void {
    $world = tournamentFixture();
    actingAs(administrator());
    get(route('tournaments.show', $world['tournament']))->assertOk()->assertSee('Autumn Cup')->assertSee('Rounds')->assertSee('Bracket');
});

it('renders the tournament listing for an authorized operator', function (): void {
    $world = tournamentFixture();
    \Pest\Laravel\actingAs(administrator());
    \Pest\Laravel\get(route('tournaments.index'))->assertOk()->assertSee('Autumn Cup');
});
