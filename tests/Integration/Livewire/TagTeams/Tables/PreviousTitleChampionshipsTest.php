<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

describe('PreviousTitleChampionships configuration', function (): void {
    it('requires a tag team', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTitleChampionships())->builder())
            ->toThrow(LogicException::class, 'A tag team was not provided.');
    });
});

describe('PreviousTitleChampionships query', function (): void {
    it('returns only previous championships for the requested tag team in newest-first order', function (): void {
        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $title = Title::factory()->tagTeam()->create();
        $olderChampionship = TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($this->tagTeam)
            ->wonOn(Date::now()->subYears(4)->toDateString())
            ->lostOn(Date::now()->subYears(3)->toDateString())
            ->create();
        $recentChampionship = TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($this->tagTeam)
            ->wonOn(Date::now()->subYears(2)->toDateString())
            ->lostOn(Date::now()->subYear()->toDateString())
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($this->tagTeam)
            ->current()
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($otherTagTeam)
            ->ended()
            ->create();
        $table = new PreviousTitleChampionships();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $championships = $table->builder()->get();

        // Assert
        expect($championships->modelKeys())->toBe([
            $recentChampionship->id,
            $olderChampionship->id,
        ])->and($championships->every->relationLoaded('title'))->toBeTrue();
    });
});

describe('PreviousTitleChampionships rendering', function (): void {
    it('renders title history with the previous champion, dates, links, and search control', function (): void {
        // Arrange
        $title = Title::factory()->tagTeam()->create(['name' => 'Historic Tag Team Titles']);
        $previousChampion = TagTeam::factory()->create(['name' => 'Previous Champions']);
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($previousChampion)
            ->wonOn('2020-01-01')
            ->lostOn('2020-06-01')
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($this->tagTeam)
            ->wonOn('2020-06-01')
            ->lostOn('2021-01-01')
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search title championships"')
            ->assertSee('Historic Tag Team Titles')
            ->assertSee('Previous Champions')
            ->assertSee('2020-06-01')
            ->assertSee('2021-01-01')
            ->assertSeeHtml(route('tag-teams.show', $previousChampion))
            ->assertSeeHtml(route('titles.show', $title));
    });

    it('searches previous championships by title name', function (): void {
        // Arrange
        foreach (['Historic Tag Team Titles', 'Former Tag Team Titles'] as $offset => $name) {
            $title = Title::factory()->tagTeam()->create(['name' => $name]);
            TitleChampionship::factory()
                ->for($title)
                ->forTagTeam($this->tagTeam)
                ->wonOn(Date::now()->subMonths($offset + 3)->toDateString())
                ->lostOn(Date::now()->subMonths($offset + 1)->toDateString())
                ->create();
        }

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);
        $component->set('search', 'Historic');

        // Assert
        $component
            ->assertSee('Historic Tag Team Titles')
            ->assertDontSee('Former Tag Team Titles');
    });

    it('renders an empty state when the tag team has no previous championships', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousTitleChampionships authorization', function (): void {
    it('allows administrators to view tag team title history', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component->assertSuccessful();
    });

    it('forbids users without access to the tag team', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
