<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Titles\Tables\TitleHistory;
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

describe('TitleHistory configuration', function (): void {
    it('requires a title', function (): void {
        // Act & Assert
        expect(fn () => (new TitleHistory)->builder())
            ->toThrow(LogicException::class, 'A title was not provided.');
    });

    it('displays championship reign length from its dates', function (): void {
        // Arrange
        $championship = new TitleChampionship([
            'won_at' => '2025-01-01',
            'lost_at' => '2025-01-11',
        ]);
        $table = new TitleHistory;
        $table->boot(app(RosterResourceRouteResolver::class));

        // Act
        $reignLength = $table->columns()[2]->resolveValue($championship);

        // Assert
        expect($reignLength)->toBe('10');
    });
});

describe('TitleHistory query', function (): void {
    it('returns every reign for the selected title with the newest reign first', function (): void {
        // Arrange
        $olderChampionship = TitleChampionship::factory()->for($this->title)->ended()->create([
            'won_at' => Date::parse('2022-01-01'),
            'lost_at' => Date::parse('2023-01-01'),
        ]);
        $latestChampionship = TitleChampionship::factory()->for($this->title)->ended()->create([
            'won_at' => Date::parse('2024-01-01'),
            'lost_at' => Date::parse('2025-01-01'),
        ]);
        $currentChampionship = TitleChampionship::factory()->for($this->title)->current()->create([
            'won_at' => Date::parse('2025-01-01'),
        ]);
        TitleChampionship::factory()->ended()->create();
        $table = new TitleHistory;
        $table->titleId = $this->title->id;

        // Act
        $championships = $table->builder()->get();

        // Assert
        expect($championships->modelKeys())->toBe([
            $currentChampionship->id,
            $latestChampionship->id,
            $olderChampionship->id,
        ]);
    });
});

describe('TitleHistory rendering', function (): void {
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
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search title reigns"')
            ->assertSee('First Champion')
            ->assertSee('New Champions')
            ->assertSeeHtml(route('wrestlers.show', $previousChampion))
            ->assertSeeHtml(route('tag-teams.show', $newChampion))
            ->assertSee('2024-06-01 - 2025-01-01')
            ->assertSee('Champion')
            ->assertDontSee('Previous Champion')
            ->assertDontSee('N/A');
    });

    it('shows the current champion at the top of the history', function (): void {
        // Arrange
        $formerChampion = Wrestler::factory()->create(['name' => 'Former Champion']);
        $currentChampion = Wrestler::factory()->create(['name' => 'Reigning Champion']);
        TitleChampionship::factory()
            ->for($this->title)
            ->forWrestler($formerChampion)
            ->wonOn('2024-01-01')
            ->lostOn('2025-01-01')
            ->create();
        TitleChampionship::factory()
            ->for($this->title)
            ->forWrestler($currentChampion)
            ->wonOn('2025-01-01')
            ->create();

        // Act
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeInOrder(['Reigning Champion', '2025-01-01 - Current', 'Former Champion', '2024-01-01 - 2025-01-01']);
    });

    it('searches reigns by wrestler and tag team champion names', function (
        string $search,
        string $visibleChampion,
        string $visibleDates,
        string $hiddenDates,
    ): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Historic Wrestler']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Legendary Tag Team']);
        TitleChampionship::factory()
            ->for(Title::factory()->singles())
            ->forWrestler($wrestler)
            ->wonOn('2022-02-01')
            ->lostOn('2022-03-01')
            ->create();
        TitleChampionship::factory()
            ->for(Title::factory()->tagTeam())
            ->forTagTeam($tagTeam)
            ->wonOn('2022-04-01')
            ->lostOn('2022-05-01')
            ->create();
        TitleChampionship::factory()
            ->for($this->title)
            ->forWrestler($wrestler)
            ->wonOn('2023-01-01')
            ->lostOn('2023-05-01')
            ->create();
        TitleChampionship::factory()
            ->for($this->title)
            ->forTagTeam($tagTeam)
            ->wonOn('2024-06-01')
            ->lostOn('2025-01-01')
            ->create();

        // Act
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);
        $table->set('search', $search);

        // Assert
        $table
            ->assertSee($visibleChampion)
            ->assertSee($visibleDates)
            ->assertDontSee($hiddenDates)
            ->assertDontSee('2022-02-01 - 2022-03-01')
            ->assertDontSee('2022-04-01 - 2022-05-01');
    })->with([
        'wrestler champion' => ['Historic', 'Historic Wrestler', '2023-01-01 - 2023-05-01', '2024-06-01 - 2025-01-01'],
        'tag team champion' => ['Legendary', 'Legendary Tag Team', '2024-06-01 - 2025-01-01', '2023-01-01 - 2023-05-01'],
    ]);

    it('renders an empty state when the title has no reigns', function (): void {
        // Act
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('Title reigns')
            ->assertSee('No title reigns yet.')
            ->assertDontSeeHtml('placeholder="Search title reigns"');
    });
});

describe('TitleHistory authorization', function (): void {
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
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);

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
        $table = livewire(TitleHistory::class, ['titleId' => $this->title->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
