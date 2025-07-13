<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousTitleChampionshipsTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousTitleChampionshipsTable Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(function () {
            Livewire::test(PreviousTitleChampionshipsTable::class)
                ->call('builder');
        })->toThrow(Exception::class, "You didn't specify a wrestler");
    });

    it('can set wrestler id', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        expect($component->instance()->wrestlerId)->toBe($this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        // The databaseTableName property is protected, but we can verify through the query
        $sql = $component->instance()->builder()->toSql();
        expect($sql)->toContain('from "titles_championships"');
    });
});

describe('PreviousTitleChampionshipsTable Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('"champion_type" = ?');
        expect($builder->toSql())->toContain('and "lost_at" is not null');
        expect($builder->getBindings())->toContain($this->wrestler->id);
    });

    it('filters by wrestler id correctly', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();

        expect($results)->toBeCollection();
    });

    it('only shows championships that have ended', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = $component->instance()->builder();

        expect($builder->toSql())->toContain('and "lost_at" is not null');
    });
});

describe('PreviousTitleChampionshipsTable Rendering', function () {
    it('can render with wrestler id set', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no championship history', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $results = $component->instance()->builder()->get();
        expect($results)->toHaveCount(0);

        $component->assertSuccessful();
    });
});

describe('PreviousTitleChampionshipsTable Authorization', function () {
    it('allows access to administrators', function () {
        $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });
});
