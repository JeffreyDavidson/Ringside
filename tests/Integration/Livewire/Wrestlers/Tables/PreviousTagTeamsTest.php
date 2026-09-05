<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousTagTeamsTable Configuration', function () {
    it('requires wrestler id to be set', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousTagTeams())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function (): void {
        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('uses the tag team membership table', function (): void {
        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('databaseTableName', 'tag_teams_wrestlers');
    });
});

describe('PreviousTagTeamsTable Query Building', function () {
    it('returns the wrestler previous tag team memberships', function (): void {
        // Arrange
        $formerMembership = TagTeamWrestler::factory()->create([
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Date::parse('2024-01-01'),
            'left_at' => Date::parse('2024-06-01'),
        ]);

        // Act
        $memberships = tap(app(PreviousTagTeams::class), function (PreviousTagTeams $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($memberships->pluck('tag_team_id')->all())->toBe([$formerMembership->tag_team_id]);
    });

    it('excludes previous memberships belonging to another wrestler', function (): void {
        // Arrange
        $otherMembership = TagTeamWrestler::factory()->ended()->create();

        // Act
        $memberships = tap(app(PreviousTagTeams::class), function (PreviousTagTeams $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($memberships->pluck('tag_team_id'))->not->toContain($otherMembership->tag_team_id);
    });

    it('excludes current tag team memberships', function (): void {
        // Arrange
        $currentMembership = TagTeamWrestler::factory()->current()->create([
            'wrestler_id' => $this->wrestler->id,
        ]);

        // Act
        $memberships = tap(app(PreviousTagTeams::class), function (PreviousTagTeams $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($memberships->pluck('tag_team_id'))->not->toContain($currentMembership->tag_team_id);
    });
});

describe('PreviousTagTeamsTable Rendering', function () {
    it('renders the tag team history search control', function (): void {
        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search tag teams"');
    });

    it('renders when the wrestler has no previous tag team memberships', function (): void {
        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });

    it('renders previous tag team membership details', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create(['name' => 'Historic Partners']);
        $partner = Wrestler::factory()->create(['name' => 'Historic Partner']);
        $joinedAt = Date::parse('2024-01-15');
        $leftAt = Date::parse('2024-06-30');

        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $partner->id,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);

        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Historic Partners')
            ->assertSee('Historic Partner')
            ->assertSee('2024-01-15')
            ->assertSee('2024-06-30')
            ->assertSeeHtml(route('tag-teams.show', $tagTeam))
            ->assertSeeHtml(route('wrestlers.show', $partner));
    });

    it('renders the overlapping historical partner without additional queries', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->create();
        $partner = Wrestler::factory()->create(['name' => "Louisa O'Hara"]);
        $nonOverlappingWrestler = Wrestler::factory()->create();
        $joinedAt = Date::parse('2024-01-01');
        $leftAt = Date::parse('2024-06-30');

        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $partner->id,
            'joined_at' => $joinedAt->copy()->addMonth(),
            'left_at' => $leftAt->copy()->subMonth(),
        ]);
        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'wrestler_id' => $nonOverlappingWrestler->id,
            'joined_at' => $leftAt->copy()->addMonth(),
            'left_at' => null,
        ]);

        $table = app(PreviousTagTeams::class);
        $table->wrestlerId = $this->wrestler->id;
        $table->boot(app(RosterResourceRouteResolver::class));
        $membership = $table->builder()->firstOrFail();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $partnerColumn = $table->columns()[1];
        $renderedPartner = $partnerColumn->resolveValue($membership);

        // Assert
        expect($renderedPartner)
            ->toContain(e($partner->name))
            ->toContain(route('wrestlers.show', $partner))
            ->not->toContain($nonOverlappingWrestler->name)
            ->and(DB::getQueryLog())->toBeEmpty();
    });
});

describe('PreviousTagTeamsTable Authorization', function () {
    it('allows access to administrators', function (): void {
        // Act
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

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
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
