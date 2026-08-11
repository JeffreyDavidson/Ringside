<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousStablesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousStables())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->wrestlerId)->toBe($this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->databaseTableName)->toBe('stables_wrestlers');
    });
});

describe('PreviousStablesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('where "stables_wrestlers"."wrestler_id" = ?');
        expect($builder->toSql())->toContain('"stables_wrestlers"."left_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('only shows relationships that have ended', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('"left_at" is not null');
    });
});

describe('PreviousStablesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no stable relationships', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousStablesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = testLivewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
