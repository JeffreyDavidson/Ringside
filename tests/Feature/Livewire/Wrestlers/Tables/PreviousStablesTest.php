<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = administrator();
    $this->wrestler = Wrestler::factory()->create();
    actingAs($this->admin);
});

describe('PreviousStablesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousStables())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('databaseTableName', 'stables_wrestlers');
    });
});

describe('PreviousStablesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('where "stables_wrestlers"."wrestler_id" = ?');
        expect($builder->toSql())->toContain('"stables_wrestlers"."left_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('only shows relationships that have ended', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->toSql())->toContain('"left_at" is not null');
    });
});

describe('PreviousStablesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no stable relationships', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousStables::class), fn (PreviousStables $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousStablesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
