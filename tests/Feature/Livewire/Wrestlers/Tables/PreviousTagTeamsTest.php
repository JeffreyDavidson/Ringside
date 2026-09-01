<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Wrestlers\Tables\PreviousTagTeams;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousTagTeamsTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousTagTeams())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('databaseTableName', 'tag_teams_wrestlers');
    });
});

describe('PreviousTagTeamsTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousTagTeams::class), fn (PreviousTagTeams $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('where "wrestler_id" = ?');
        expect($builder->toSql())->toContain('and "left_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousTagTeams::class), fn (PreviousTagTeams $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('only shows relationships that have ended', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousTagTeams::class), fn (PreviousTagTeams $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('and "left_at" is not null');
    });
});

describe('PreviousTagTeamsTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no tag team relationships', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousTagTeams::class), fn (PreviousTagTeams $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });

    it('renders the overlapping historical partner without additional queries', function () {
        $tagTeam = TagTeam::factory()->create();
        $partner = Wrestler::factory()->create(['name' => "Louisa O'Hara"]);
        $nonOverlappingWrestler = Wrestler::factory()->create();
        $joinedAt = now()->subYear();
        $leftAt = now()->subMonths(6);

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

        $partnerColumn = $table->columns()[1];
        $renderedPartner = $partnerColumn->resolveValue($membership);

        expect($renderedPartner)
            ->toContain(e($partner->name))
            ->toContain(route('wrestlers.show', $partner))
            ->not->toContain($nonOverlappingWrestler->name)
            ->and(DB::getQueryLog())->toBeEmpty();
    });
});

describe('PreviousTagTeamsTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = livewire(PreviousTagTeams::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
