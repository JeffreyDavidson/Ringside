<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousTitleChampionshipsTable Configuration', function () {
    it('uses the title championship table', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('databaseTableName', 'titles_championships');
    });
});

describe('PreviousTitleChampionshipsTable Query Building', function () {
    it('returns the wrestler previous title championships', function (): void {
        // Arrange
        $formerChampionship = TitleChampionship::factory()
            ->forWrestler($this->wrestler)
            ->wonOn('2024-01-01')
            ->lostOn('2024-06-01')
            ->create();

        // Act
        $championships = tap(app(PreviousTitleChampionships::class), function (PreviousTitleChampionships $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($championships->modelKeys())->toBe([$formerChampionship->id]);
    });

    it('excludes previous championships belonging to another wrestler', function (): void {
        // Arrange
        $otherChampionship = TitleChampionship::factory()
            ->forWrestler()
            ->ended()
            ->create();

        // Act
        $championships = tap(app(PreviousTitleChampionships::class), function (PreviousTitleChampionships $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($championships->modelKeys())->not->toContain($otherChampionship->id);
    });

    it('excludes current title championships', function (): void {
        // Arrange
        $currentChampionship = TitleChampionship::factory()
            ->forWrestler($this->wrestler)
            ->current()
            ->create();

        // Act
        $championships = tap(app(PreviousTitleChampionships::class), function (PreviousTitleChampionships $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($championships->modelKeys())->not->toContain($currentChampionship->id);
    });
});

describe('PreviousTitleChampionshipsTable Rendering', function () {
    it('links to the champion who held the title before the wrestler', function (): void {
        // Arrange
        $title = Title::factory()->create(['name' => 'Historic Championship']);
        $previousChampion = TagTeam::factory()->create(['name' => 'Previous Champions']);
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($previousChampion)
            ->wonOn('2020-01-01')
            ->lostOn('2020-06-01')
            ->create();
        TitleChampionship::factory()
            ->for($title)
            ->forWrestler($this->wrestler)
            ->wonOn('2020-06-01')
            ->lostOn('2021-01-01')
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Historic Championship')
            ->assertSee('Previous Champions')
            ->assertSee('2020-06-01')
            ->assertSee('2021-01-01')
            ->assertSeeHtml(route('tag-teams.show', $previousChampion))
            ->assertSeeHtml(route('titles.show', $title));
    });

    it('names a deleted previous champion as plain text', function (
        string $championClass,
        string $showRoute,
    ): void {
        // Arrange
        $title = Title::factory()->create();
        $previousChampion = $championClass::factory()->create(['name' => 'Deleted Champion']);
        TitleChampionship::factory()->for($title)->create([
            'champion_type' => $previousChampion->getMorphClass(),
            'champion_id' => $previousChampion->id,
            'won_at' => '2020-01-01',
            'lost_at' => '2020-06-01',
        ]);
        TitleChampionship::factory()
            ->for($title)
            ->forWrestler($this->wrestler)
            ->wonOn('2020-06-01')
            ->lostOn('2021-01-01')
            ->create();
        $previousChampion->delete();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Deleted Champion')
            ->assertDontSeeHtml(route($showRoute, $previousChampion));
    })->with([
        'wrestler' => [Wrestler::class, 'wrestlers.show'],
        'tag team' => [TagTeam::class, 'tag-teams.show'],
    ]);

    it('renders the title championship history search control', function (): void {
        // Arrange
        TitleChampionship::factory()
            ->for(Title::factory()->singles())
            ->forWrestler($this->wrestler)
            ->wonOn(now()->subMonths(3)->toDateString())
            ->lostOn(now()->subMonth()->toDateString())
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search title championships"');
    });

    it('searches previous championships by title name', function (): void {
        // Arrange
        foreach (['Historic Singles Title', 'Former Singles Title'] as $offset => $name) {
            $title = Title::factory()->singles()->create(['name' => $name]);
            TitleChampionship::factory()
                ->for($title)
                ->forWrestler($this->wrestler)
                ->wonOn(now()->subMonths($offset + 3)->toDateString())
                ->lostOn(now()->subMonths($offset + 1)->toDateString())
                ->create();
        }

        TitleChampionship::factory()
            ->for(Title::factory()->singles()->create(['name' => 'Historic Unrelated Singles Title']))
            ->forWrestler()
            ->wonOn('2020-01-01')
            ->lostOn('2021-01-01')
            ->create();
        TitleChampionship::factory()
            ->for(Title::factory()->singles()->create(['name' => 'Historic Current Singles Title']))
            ->forWrestler($this->wrestler)
            ->wonOn('2025-01-01')
            ->current()
            ->create();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);
        $component->set('search', 'Historic');

        // Assert
        $component
            ->assertSee('Historic Singles Title')
            ->assertDontSee('Former Singles Title')
            ->assertDontSee('Historic Unrelated Singles Title')
            ->assertDontSee('Historic Current Singles Title');
    });
});

describe('PreviousTitleChampionshipsTable Reign Dates', function () {
    it('shows reign dates as the day in the title promotion time zone', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        TitleChampionship::factory()
            ->for(Title::factory()->for($promotion, 'promotion'))
            ->forWrestler($wrestler)
            ->create([
                'won_at' => Date::parse('2026-03-02 03:00:00', 'UTC'),
                'lost_at' => Date::parse('2026-06-11 02:00:00', 'UTC'),
            ]);

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $wrestler->id]);

        // Assert
        $component
            ->assertSee('2026-03-01')
            ->assertSee('2026-06-10')
            ->assertDontSee('2026-03-02')
            ->assertDontSee('2026-06-11');
    });
});

describe('PreviousTitleChampionshipsTable Deleted Titles', function () {
    it('renders a championship without a link when its title was deleted', function (): void {
        // Arrange
        $title = Title::factory()->singles()->create(['name' => 'Vanished Singles Title']);
        TitleChampionship::factory()
            ->for($title)
            ->forWrestler($this->wrestler)
            ->wonOn('2024-01-01')
            ->lostOn('2024-06-01')
            ->create();
        $title->delete();

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('2024-01-01')
            ->assertDontSee('Vanished Singles Title')
            ->assertDontSeeHtml(route('titles.show', $title));
    });
});
