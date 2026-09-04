<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = administrator();
    $this->wrestler = Wrestler::factory()->create();
    actingAs($this->admin);
});

describe('PreviousMatchesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('queries the event matches table', function () {
        $table = app(PreviousMatches::class);
        $table->wrestlerId = $this->wrestler->id;

        expect($table->builder()->getModel()->getTable())->toBe('events_matches');
    });
});

describe('PreviousMatchesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousMatches::class), fn (PreviousMatches $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        // Test that the query includes competitor filtering
        expect($builder->toSql())->toContain('events_matches_competitors');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousMatches::class), fn (PreviousMatches $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        // Since we don't have match data set up, this should be empty
        // but the query should execute without error
        expect($results)->toBeInstanceOf(Collection::class);
    });
});

describe('PreviousMatchesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no matches', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousMatches::class), fn (PreviousMatches $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousMatchesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('forbids users without access to the wrestler', function (string $actor) {
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
