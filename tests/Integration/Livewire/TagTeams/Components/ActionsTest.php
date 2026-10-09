<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Livewire\TagTeams\Components\Actions;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('tag team actions component', function (): void {
    test('it names the deleted partner when the team cannot be unretired', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->employed()->create();
        resolve(RetireAction::class)->handle($tagTeam, now()->subDays(2));
        $partner = $tagTeam->currentWrestlers()->firstOrFail();
        $partner->delete();

        actingAs(administrator());
        $component = livewire(Actions::class, ['tagTeam' => $tagTeam->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched(
            'flash-message',
            type: 'error',
            message: "{$partner->name} was deleted, so this tag team can't come back without them.",
        );
        expect($tagTeam->refresh()->currentRetirement()->exists())->toBeTrue();
    });

    test('it names the partner and their new team when the team cannot be unretired', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->employed()->create();
        resolve(RetireAction::class)->handle($tagTeam, now()->subDays(2));
        $partner = $tagTeam->currentWrestlers()->firstOrFail();
        resolve(UnretireAction::class)->handle($partner);
        $otherTeam = TagTeam::factory()->create();
        $otherTeam->wrestlers()->attach($partner, ['joined_at' => now()]);

        actingAs(administrator());
        $component = livewire(Actions::class, ['tagTeam' => $tagTeam->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched(
            'flash-message',
            type: 'error',
            message: "{$partner->name} is now on {$otherTeam->name}, so this tag team can't come back without them.",
        );
        expect($tagTeam->refresh()->currentRetirement()->exists())->toBeTrue();
    });
});
