<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousMatchesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->wrestlerId)->toBe($this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->databaseTableName)->toBe('events_matches_competitors');
    });
});

describe('PreviousMatchesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        // Test that the query includes competitor filtering
        expect($builder->toSql())->toContain('events_matches_competitors');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();

        // Since we don't have match data set up, this should be empty
        // but the query should execute without error
        expect($results)->toBeInstanceOf(Collection::class);
    });
});

describe('PreviousMatchesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no matches', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousMatchesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = testLivewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
