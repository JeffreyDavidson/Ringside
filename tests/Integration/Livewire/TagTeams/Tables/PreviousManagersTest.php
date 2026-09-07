<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

describe('PreviousManagers configuration', function (): void {
    it('requires a tag team', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousManagers())->builder())
            ->toThrow(LogicException::class, 'A tag team was not provided.');
    });
});

describe('PreviousManagers query', function (): void {
    it('returns only ended manager assignments for the requested tag team in newest-first order', function (): void {
        // Arrange
        $otherTagTeam = TagTeam::factory()->create();
        $recentManager = Manager::factory()->create();
        $olderManager = Manager::factory()->create();
        $currentManager = Manager::factory()->create();
        $otherManager = Manager::factory()->create();

        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $olderManager->id,
            'hired_at' => Date::now()->subMonths(3),
            'fired_at' => Date::now()->subMonths(2),
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $recentManager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $currentManager->id,
            'hired_at' => Date::now()->subDays(3),
            'fired_at' => null,
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $otherTagTeam->id,
            'manager_id' => $otherManager->id,
            'hired_at' => Date::now()->subDays(2),
            'fired_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousManagers();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments->pluck('manager_id')->all())->toBe([
            $recentManager->id,
            $olderManager->id,
        ])->and($assignments->every->relationLoaded('manager'))->toBeTrue();
    });

    it('keeps separate historical assignments for a returning manager', function (): void {
        // Arrange
        $manager = Manager::factory()->create();
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => Date::now()->subMonths(4),
            'fired_at' => Date::now()->subMonths(3),
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => Date::now()->subMonths(2),
            'fired_at' => Date::now()->subMonth(),
        ]);
        $table = new PreviousManagers();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toHaveCount(2)
            ->and($assignments->pluck('manager_id')->all())->toBe([
                $manager->id,
                $manager->id,
            ]);
    });

    it('omits deleted managers', function (): void {
        // Arrange
        $manager = Manager::factory()->create();
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        $manager->delete();
        $table = new PreviousManagers();
        $table->tagTeamId = $this->tagTeam->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toBeEmpty();
    });

    it('resolves eager-loaded managers without additional queries', function (): void {
        // Arrange
        $manager = Manager::factory()->create()->refresh();
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $manager->id,
            'hired_at' => Date::now()->subYear(),
            'fired_at' => Date::now()->subMonth(),
        ]);
        $table = new PreviousManagers();
        $table->tagTeamId = $this->tagTeam->id;
        $assignment = $table->builder()->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $renderedManager = $table->columns()[0]->resolveValue($assignment);

        // Assert
        expect($renderedManager)->toBe($manager->full_name)
            ->and($assignment->relationLoaded('manager'))->toBeTrue()
            ->and(DB::getQueryLog())->toBeEmpty();
    });
});

describe('PreviousManagers rendering', function (): void {
    it('renders previous manager names, dates, and search controls', function (): void {
        // Arrange
        $previousManager = Manager::factory()->create([
            'first_name' => 'Previous',
            'last_name' => 'Manager',
        ]);
        $currentManager = Manager::factory()->create([
            'first_name' => 'Current',
            'last_name' => 'Manager',
        ]);
        $hiredAt = Date::now()->subMonth();
        $firedAt = Date::now()->subWeek();
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $previousManager->id,
            'hired_at' => $hiredAt,
            'fired_at' => $firedAt,
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $this->tagTeam->id,
            'manager_id' => $currentManager->id,
            'hired_at' => Date::now()->subDay(),
            'fired_at' => null,
        ]);

        // Act
        $table = livewire(PreviousManagers::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search managers"')
            ->assertSee('Previous Manager')
            ->assertSee($hiredAt->format('Y-m-d'))
            ->assertSee($firedAt->format('Y-m-d'))
            ->assertDontSee('Current Manager');
    });

    it('searches previous managers by name', function (string $search, string $visibleManager, string $hiddenManager): void {
        // Arrange
        $historicManager = Manager::factory()->create([
            'first_name' => 'Historic',
            'last_name' => 'Manager',
        ]);
        $formerManager = Manager::factory()->create([
            'first_name' => 'Former',
            'last_name' => 'Advisor',
        ]);
        foreach ([$historicManager, $formerManager] as $offset => $manager) {
            TagTeamManager::query()->create([
                'tag_team_id' => $this->tagTeam->id,
                'manager_id' => $manager->id,
                'hired_at' => Date::now()->subMonths($offset + 3),
                'fired_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $table = livewire(PreviousManagers::class, ['tagTeamId' => $this->tagTeam->id]);
        $table->set('search', $search);

        // Assert
        $table
            ->assertSee($visibleManager)
            ->assertDontSee($hiddenManager);
    })->with([
        'first name' => ['Historic', 'Historic Manager', 'Former Advisor'],
        'last name' => ['Advisor', 'Former Advisor', 'Historic Manager'],
        'full name' => ['Historic Manager', 'Historic Manager', 'Former Advisor'],
    ]);

    it('renders an empty state when the tag team has no previous managers', function (): void {
        // Act
        $table = livewire(PreviousManagers::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousManagers authorization', function (): void {
    it('allows administrators to view tag team manager history', function (): void {
        // Act
        $table = livewire(PreviousManagers::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the tag team', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousManagers::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
