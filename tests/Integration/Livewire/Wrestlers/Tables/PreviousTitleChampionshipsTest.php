<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousTitleChampionshipsTable Configuration', function () {
    it('requires wrestler id to be set', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTitleChampionships())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

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

    it('renders the title championship history search control', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search title championships"');
    });

    it('renders when the wrestler has no championship history', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousTitleChampionshipsTable Authorization', function () {
    it('allows access to administrators', function (): void {
        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSuccessful();
    });

    it('forbids users without access to the wrestler', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
