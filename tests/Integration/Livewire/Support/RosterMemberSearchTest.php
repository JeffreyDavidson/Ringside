<?php

declare(strict_types=1);

use App\Enums\Roster\RosterMemberKind;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\Support\RosterMemberSearch;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Auth\Access\AuthorizationException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('searching roster members', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    it('matches names case-insensitively, skips deleted records and orders by name', function (): void {
        // Arrange
        Wrestler::factory()->create(['name' => 'Ricky Steamboat']);
        Wrestler::factory()->create(['name' => 'Ricky Morton']);
        Wrestler::factory()->trashed()->create(['name' => 'Ricky Deleted']);
        Wrestler::factory()->create(['name' => 'Ted DiBiase']);

        // Act
        $options = resolve(RosterMemberSearch::class)->search(RosterMemberKind::Wrestlers, ' RICKY ', null);

        // Assert
        expect(array_column($options, 'name'))->toBe(['Ricky Morton', 'Ricky Steamboat']);
    });

    it('treats wildcard characters as plain text', function (): void {
        // Arrange
        Wrestler::factory()->create(['name' => 'Ricky Morton']);

        // Act
        $options = resolve(RosterMemberSearch::class)->search(RosterMemberKind::Wrestlers, '%', null);

        // Assert
        expect($options)->toHaveCount(1);
    });

    it('returns at most the limit of results', function (): void {
        // Arrange
        Wrestler::factory()->count(RosterMemberSearch::LIMIT + 5)->create();

        // Act
        $options = resolve(RosterMemberSearch::class)->search(RosterMemberKind::Wrestlers, '', null);

        // Assert
        expect($options)->toHaveCount(RosterMemberSearch::LIMIT);
    });

    it('searches managers by full name and tag teams by name', function (): void {
        // Arrange
        Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);
        TagTeam::factory()->create(['name' => 'The Rockers']);
        $search = resolve(RosterMemberSearch::class);

        // Act
        $managers = $search->search(RosterMemberKind::Managers, 'bobby heenan', null);
        $tagTeams = $search->search(RosterMemberKind::TagTeams, 'rockers', null);

        // Assert
        expect(array_column($managers, 'name'))->toBe(['Bobby Heenan'])
            ->and(array_column($tagTeams, 'name'))->toBe(['The Rockers']);
    });
});

describe('resolving selected labels', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
    });

    it('labels selected ids including deleted records and ignores junk ids', function (): void {
        // Arrange
        $active = Wrestler::factory()->create(['name' => 'Active Wrestler']);
        $deleted = Wrestler::factory()->trashed()->create(['name' => 'Deleted Wrestler']);
        Wrestler::factory()->create(['name' => 'Other Wrestler']);

        // Act
        $labels = resolve(RosterMemberSearch::class)->labels(
            RosterMemberKind::Wrestlers,
            [$deleted->id, $active->id, $active->id, 'junk', null],
            null,
        );

        // Assert
        expect(array_column($labels, 'name'))->toBe(['Active Wrestler', 'Deleted Wrestler']);
    });

    it('returns no labels for no ids', function (): void {
        // Act
        $labels = resolve(RosterMemberSearch::class)->labels(RosterMemberKind::Wrestlers, [], null);

        // Assert
        expect($labels)->toBeEmpty();
    });
});

describe('authorizing the roster search', function (): void {
    it('refuses users who cannot create tag teams', function (): void {
        // Arrange
        actingAs(administrator());
        $component = livewire(TagTeamFormModal::class);
        actingAs(basicUser());

        // Act
        $search = fn (): array => $component->instance()->searchRoster('wrestlers', '');

        // Assert
        expect($search)->toThrow(AuthorizationException::class);
    });

    it('refuses users who cannot create stables', function (): void {
        // Arrange
        actingAs(administrator());
        $component = livewire(StableFormModal::class);
        actingAs(basicUser());

        // Act
        $search = fn (): array => $component->instance()->searchRoster('wrestlers', '');

        // Assert
        expect($search)->toThrow(AuthorizationException::class);
    });
});
