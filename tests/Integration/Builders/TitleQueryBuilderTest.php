<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

describe('TitleBuilder Query Scopes', function () {
    beforeEach(function () {
        $this->undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
        $this->activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
        $this->inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
        $this->retiredTitle = Title::factory()->retired()->create(['name' => 'Retired Title']);
        $this->futureDebutTitle = Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);
    });

    describe('activity state scopes', function () {
        test('future activated titles can be retrieved', function () {
            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();

            $futureActivatedTitles = Title::query()->withPendingDebut()->get();

            expect($futureActivatedTitles->pluck('id'))->toContain($futureActivatedTitle->id);
        });

        test('retired titles can be retrieved', function () {
            $retiredTitle = Title::factory()->retired()->create();

            $retiredTitles = Title::query()->retired()->get();

            expect($retiredTitles->pluck('id'))->toContain($retiredTitle->id);
        });
    });

    describe('basic activity scopes', function () {
        test('undebuted scope returns titles without activity periods', function () {
            $undebutedTitles = Title::query()->undebuted()->get();

            expect($undebutedTitles->pluck('id'))->toContain($this->undebutedTitle->id);
            expect($undebutedTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($undebutedTitles->pluck('id'))->not->toContain($this->inactiveTitle->id);
            expect($undebutedTitles->pluck('id'))->not->toContain($this->futureDebutTitle->id);
        });

        test('active scope returns titles with current activity periods', function () {
            $activeTitles = Title::query()->active()->get();

            expect($activeTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($activeTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
            expect($activeTitles->pluck('id'))->not->toContain($this->inactiveTitle->id);
        });

        test('inactive scope returns titles with past but no current activity', function () {
            $inactiveTitles = Title::query()->inactive()->get();

            expect($inactiveTitles->pluck('id'))->toContain($this->inactiveTitle->id);
            expect($inactiveTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($inactiveTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('withPendingDebut scope returns titles with future activity', function () {
            $pendingTitles = Title::query()->withPendingDebut()->get();

            expect($pendingTitles->pluck('id'))->toContain($this->futureDebutTitle->id);
            expect($pendingTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($pendingTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });
    });

    describe('retirement scopes', function () {
        test('retired scope returns titles with current retirement', function () {
            $retiredTitles = Title::query()->retired()->get();

            expect($retiredTitles->pluck('id'))->toContain($this->retiredTitle->id);
            expect($retiredTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($retiredTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

    });

    describe('scope method chaining', function () {
        test('can chain scopes with additional filters', function () {
            $filteredTitles = Title::query()
                ->active()
                ->where('name', 'like', '%Active%')
                ->get();

            expect($filteredTitles->pluck('id'))->toContain($this->activeTitle->id);
        });
    });

    describe('scope performance and optimization', function () {
        test('undebuted scope uses efficient exists query', function () {
            $query = Title::query()->undebuted();
            $sql = $query->toSql();

            expect($sql)->toContain('not exists');
            expect($sql)->toContain('activity_periods');
        });

        test('active scope uses exists query for performance', function () {
            $query = Title::query()->active();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('activity_periods');
        });

    });

    describe('scope edge cases', function () {
        test('scopes work with soft deleted titles', function () {
            $deletedTitle = Title::factory()->active()->create();
            $deletedTitle->delete();

            $activeTitles = Title::query()->active()->get();
            expect($activeTitles->pluck('id'))->not->toContain($deletedTitle->id);

            $trashedActiveTitles = Title::onlyTrashed()->active()->get();
            expect($trashedActiveTitles->pluck('id'))->toContain($deletedTitle->id);
        });

        test('scopes handle empty database gracefully', function () {
            Title::query()->delete();

            expect(Title::query()->active()->count())->toBe(0);
            expect(Title::query()->undebuted()->count())->toBe(0);
        });
    });

    describe('scope return types and fluency', function () {
        test('all scopes return static for proper chaining', function () {
            $builder = Title::query();

            expect($builder->undebuted())->toBeInstanceOf(get_class($builder));
            expect($builder->active())->toBeInstanceOf(get_class($builder));
            expect($builder->inactive())->toBeInstanceOf(get_class($builder));
            expect($builder->withPendingDebut())->toBeInstanceOf(get_class($builder));
        });

        test('scopes maintain query builder functionality', function () {
            $query = Title::query()
                ->active()
                ->select('id', 'name')
                ->orderBy('name')
                ->limit(10);

            expect($query)->toBeInstanceOf(Builder::class);
            expect($query->toSql())->toContain('select');
            expect($query->toSql())->toContain('order by');
            expect($query->toSql())->toContain('limit');
        });
    });
});
