<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousMatchesTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousMatchesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(function () {
            Livewire::test(PreviousMatchesTable::class)
                ->call('builder');
        })->toThrow(Exception::class, "You didn't specify a wrestler");
    });

    it('can set wrestler id', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->wrestlerId)->toBe($this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->databaseTableName)->toBe('events_matches_competitors');
    });
});

describe('PreviousMatchesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        // Test that the query includes competitor filtering
        expect($builder->toSql())->toContain('events_matches_competitors');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();

        // Since we don't have match data set up, this should be empty
        // but the query should execute without error
        expect($results)->toBeCollection();
    });
});

describe('PreviousMatchesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no matches', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousMatchesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = Livewire::test(PreviousMatchesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
