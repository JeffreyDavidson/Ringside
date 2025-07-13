<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousStablesTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousStablesTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(function () {
            Livewire::test(PreviousStablesTable::class)
                ->call('builder');
        })->toThrow(Exception::class, "You didn't specify a wrestler");
    });

    it('can set wrestler id', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->wrestlerId)->toBe($this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->databaseTableName)->toBe('stables_members');
    });
});

describe('PreviousStablesTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('where "member_id" = ? and "member_type" = ?');
        expect($builder->toSql())->toContain('"left_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
        expect($builder->getBindings())->toContain('wrestler');
    });

    it('filters by wrestler id correctly', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();

        expect($results)->toBeCollection();
    });

    it('only shows relationships that have ended', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('"left_at" is not null');
    });
});

describe('PreviousStablesTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no stable relationships', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousStablesTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = Livewire::test(PreviousStablesTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
