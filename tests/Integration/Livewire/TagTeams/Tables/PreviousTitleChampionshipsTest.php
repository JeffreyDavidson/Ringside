<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousTitleChampionships query', function (): void {
    it('returns only previous championships for the requested tag team in newest-first order', function (): void {
        $tagTeam = TagTeam::factory()->create();

        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $title = Title::factory()->tagTeam()->create();
        $olderChampionship = TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($tagTeam)
            ->wonOn(Date::now()->subYears(4)->toDateString())
            ->lostOn(Date::now()->subYears(3)->toDateString())
            ->create();
        $recentChampionship = TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($tagTeam)
            ->wonOn(Date::now()->subYears(2)->toDateString())
            ->lostOn(Date::now()->subYear()->toDateString())
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($tagTeam)
            ->current()
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($otherTagTeam)
            ->ended()
            ->create();
        $table = new PreviousTitleChampionships;
        $table->tagTeamId = $tagTeam->id;

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
        $tagTeam = TagTeam::factory()->create();

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
            ->forTagTeam($tagTeam)
            ->wonOn('2020-06-01')
            ->lostOn('2021-01-01')
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $tagTeam->id]);

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
        $tagTeam = TagTeam::factory()->create();

        // Arrange
        foreach (['Historic Tag Team Titles', 'Former Tag Team Titles'] as $offset => $name) {
            $title = Title::factory()->tagTeam()->create(['name' => $name]);
            TitleChampionship::factory()
                ->for($title)
                ->forTagTeam($tagTeam)
                ->wonOn(Date::now()->subMonths($offset + 3)->toDateString())
                ->lostOn(Date::now()->subMonths($offset + 1)->toDateString())
                ->create();
        }

        TitleChampionship::factory()
            ->for(Title::factory()->tagTeam()->create(['name' => 'Historic Unrelated Tag Team Titles']))
            ->forTagTeam()
            ->wonOn('2020-01-01')
            ->lostOn('2021-01-01')
            ->create();
        TitleChampionship::factory()
            ->for(Title::factory()->tagTeam()->create(['name' => 'Historic Current Tag Team Titles']))
            ->forTagTeam($tagTeam)
            ->wonOn('2025-01-01')
            ->current()
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $tagTeam->id]);
        $component->set('search', 'Historic');

        // Assert
        $component
            ->assertSee('Historic Tag Team Titles')
            ->assertDontSee('Former Tag Team Titles')
            ->assertDontSee('Historic Unrelated Tag Team Titles')
            ->assertDontSee('Historic Current Tag Team Titles');
    });
});
