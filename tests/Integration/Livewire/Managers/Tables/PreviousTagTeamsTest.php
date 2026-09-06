<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousTagTeams;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->manager = Manager::factory()->create();
    actingAs(administrator());
});

describe('PreviousTagTeams configuration', function (): void {
    it('requires a manager', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTagTeams())->builder())
            ->toThrow(LogicException::class, 'A manager was not provided.');
    });
});

describe('PreviousTagTeams query', function (): void {
    it('returns only ended tag team assignments for the requested manager in newest-first order', function (): void {
        // Arrange
        $otherManager = Manager::factory()->create();
        $recentTagTeam = TagTeam::factory()->create();
        $olderTagTeam = TagTeam::factory()->create();
        $currentTagTeam = TagTeam::factory()->create();
        $otherTagTeam = TagTeam::factory()->create();
        TagTeamManager::query()->create([
            'tag_team_id' => $olderTagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonths(3),
            'fired_at' => Date::now()->subMonths(2),
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $recentTagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $currentTagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subDays(3),
            'fired_at' => null,
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $otherTagTeam->id,
            'manager_id' => $otherManager->id,
            'hired_at' => Date::now()->subDays(2),
            'fired_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousTagTeams();
        $table->managerId = $this->manager->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments->pluck('tag_team_id')->all())->toBe([
            $recentTagTeam->id,
            $olderTagTeam->id,
        ])->and($assignments->every->relationLoaded('tagTeam'))->toBeTrue();
    });

    it('omits deleted tag teams', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create();
        TagTeamManager::query()->create([
            'tag_team_id' => $tagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        $tagTeam->delete();
        $table = new PreviousTagTeams();
        $table->managerId = $this->manager->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toBeEmpty();
    });

    it('resolves eager-loaded tag teams without additional queries', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create();
        TagTeamManager::query()->create([
            'tag_team_id' => $tagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subYear(),
            'fired_at' => Date::now()->subMonth(),
        ]);
        $table = new PreviousTagTeams();
        $table->managerId = $this->manager->id;
        $assignment = $table->builder()->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $renderedTagTeam = $table->columns()[0]->resolveValue($assignment);

        // Assert
        expect($renderedTagTeam)->toBe($tagTeam->name)
            ->and($assignment->relationLoaded('tagTeam'))->toBeTrue()
            ->and(DB::getQueryLog())->toBeEmpty();
    });
});

describe('PreviousTagTeams rendering', function (): void {
    it('renders previous tag team names, dates, and search controls', function (): void {
        // Arrange
        $previousTagTeam = TagTeam::factory()->create(['name' => 'Historic Tag Team']);
        $currentTagTeam = TagTeam::factory()->create(['name' => 'Current Tag Team']);
        $hiredAt = Date::now()->subMonth();
        $firedAt = Date::now()->subWeek();
        TagTeamManager::query()->create([
            'tag_team_id' => $previousTagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => $hiredAt,
            'fired_at' => $firedAt,
        ]);
        TagTeamManager::query()->create([
            'tag_team_id' => $currentTagTeam->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subDay(),
            'fired_at' => null,
        ]);

        // Act
        $table = livewire(PreviousTagTeams::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search tag teams"')
            ->assertSee('Historic Tag Team')
            ->assertSee($hiredAt->format('Y-m-d'))
            ->assertSee($firedAt->format('Y-m-d'))
            ->assertDontSee('Current Tag Team');
    });

    it('searches previous tag teams by name', function (): void {
        // Arrange
        foreach (['Historic Tag Team', 'Former Tag Team'] as $offset => $name) {
            $tagTeam = TagTeam::factory()->create(['name' => $name]);
            TagTeamManager::query()->create([
                'tag_team_id' => $tagTeam->id,
                'manager_id' => $this->manager->id,
                'hired_at' => Date::now()->subMonths($offset + 3),
                'fired_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $table = livewire(PreviousTagTeams::class, ['managerId' => $this->manager->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Tag Team')
            ->assertDontSee('Former Tag Team');
    });

    it('renders an empty state when the manager has no previous tag teams', function (): void {
        // Act
        $table = livewire(PreviousTagTeams::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousTagTeams authorization', function (): void {
    it('allows administrators to view manager tag team history', function (): void {
        // Act
        $table = livewire(PreviousTagTeams::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the manager', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousTagTeams::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
