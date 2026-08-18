<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Users\User;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousTitleChampionshipsTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousTitleChampionships())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        // The databaseTableName property is protected, but we can verify through the query
        $sql = tap(app(PreviousTitleChampionships::class), fn (PreviousTitleChampionships $table) => $table->wrestlerId = $this->wrestler->id)->builder()->toSql();
        expect($sql)->toContain('from "titles_championships"');
    });
});

describe('PreviousTitleChampionshipsTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousTitleChampionships::class), fn (PreviousTitleChampionships $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('"champion_type" = ?');
        expect($builder->toSql())->toContain('and "lost_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousTitleChampionships::class), fn (PreviousTitleChampionships $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('only shows championships that have ended', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousTitleChampionships::class), fn (PreviousTitleChampionships $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('and "lost_at" is not null');
    });
});

describe('PreviousTitleChampionshipsTable Rendering', function () {
    it('links to the champion who held the title before the wrestler', function () {
        $title = Title::factory()->create();
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

        livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id])
            ->assertSee('Previous Champions')
            ->assertSeeHtml(route('tag-teams.show', $previousChampion));
    });

    it('can render with wrestler id set', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no championship history', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousTitleChampionships::class), fn (PreviousTitleChampionships $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousTitleChampionshipsTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = livewire(PreviousTitleChampionships::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
