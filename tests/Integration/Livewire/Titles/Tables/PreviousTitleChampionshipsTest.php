<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->title = Title::factory()->create();
    actingAs(administrator());
});

describe('PreviousTitleChampionships configuration', function (): void {
    it('requires a title', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTitleChampionships())->builder())
            ->toThrow(LogicException::class, 'A title was not provided.');
    });

    it('displays championship reign length from its dates', function (): void {
        // Arrange
        $championship = new TitleChampionship([
            'won_at' => '2025-01-01',
            'lost_at' => '2025-01-11',
        ]);
        $table = new PreviousTitleChampionships();
        $table->boot(app(RosterResourceRouteResolver::class));

        // Act
        $reignLength = $table->columns()[3]->resolveValue($championship);

        // Assert
        expect($reignLength)->toBe('10');
    });
});

describe('PreviousTitleChampionships query', function (): void {
    it('returns only previous championships for the selected title in reverse chronological order', function (): void {
        // Arrange
        $olderChampionship = TitleChampionship::factory()->for($this->title)->ended()->create([
            'won_at' => Date::parse('2022-01-01'),
            'lost_at' => Date::parse('2023-01-01'),
        ]);
        $latestChampionship = TitleChampionship::factory()->for($this->title)->ended()->create([
            'won_at' => Date::parse('2024-01-01'),
            'lost_at' => Date::parse('2025-01-01'),
        ]);
        TitleChampionship::factory()->for($this->title)->current()->create();
        TitleChampionship::factory()->ended()->create();
        $table = new PreviousTitleChampionships();
        $table->titleId = $this->title->id;

        // Act
        $championships = $table->builder()->get();

        // Assert
        expect($championships->modelKeys())->toBe([
            $latestChampionship->id,
            $olderChampionship->id,
        ]);
    });
});

describe('PreviousTitleChampionships rendering', function (): void {
    it('renders championship history from reign relationships and dates', function (): void {
        // Arrange
        $previousChampion = Wrestler::factory()->create(['name' => 'First Champion']);
        $newChampion = TagTeam::factory()->create(['name' => 'New Champions']);
        TitleChampionship::factory()
            ->for($this->title)
            ->forWrestler($previousChampion)
            ->wonOn('2024-01-01')
            ->lostOn('2024-06-01')
            ->create();
        TitleChampionship::factory()
            ->for($this->title)
            ->forTagTeam($newChampion)
            ->wonOn('2024-06-01')
            ->lostOn('2025-01-01')
            ->create();

        // Act
        $table = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search title championships"')
            ->assertSee('First Champion')
            ->assertSee('New Champions')
            ->assertSeeHtml(route('wrestlers.show', $previousChampion))
            ->assertSeeHtml(route('tag-teams.show', $newChampion))
            ->assertSee('2024-06-01 - 2025-01-01');
    });

    it('renders an empty state when the title has no previous championships', function (): void {
        // Act
        $table = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousTitleChampionships authorization', function (): void {
    it('authorizes the selected title instance', function (): void {
        // Arrange
        $authorizedTitle = null;

        Gate::before(function (mixed $user, string $ability, array $arguments) use (&$authorizedTitle): ?bool {
            if ($ability !== 'view' || ! ($arguments[0] ?? null) instanceof Title) {
                return null;
            }

            $authorizedTitle = $arguments[0];

            return true;
        });
        actingAs(basicUser());

        // Act
        $table = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $table->assertSuccessful();
        expect($authorizedTitle?->is($this->title))->toBeTrue();
    });

    it('forbids users without access to the title', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousTitleChampionships::class, ['titleId' => $this->title->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
