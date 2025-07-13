<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unit tests for TitleBuilder query scopes.
 *
 * UNIT TEST SCOPE:
 * - Query scope methods in isolation
 * - Filter logic validation
 * - Builder method chaining
 * - Activity period and championship query optimization
 * - Title availability and competition status filtering
 *
 * These tests verify that the TitleBuilder correctly filters
 * titles based on their activity periods, championship status,
 * and retirement state without executing complex database operations.
 */
describe('TitleBuilder Query Scopes', function () {
    beforeEach(function () {
        // Create titles with different states
        $this->undebutedTitle = Title::factory()->undebuted()->create(['name' => 'Undebuted Title']);
        $this->activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
        $this->inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
        $this->retiredTitle = Title::factory()->retired()->create(['name' => 'Retired Title']);
        $this->futureDebutTitle = Title::factory()->withFutureDebut()->create(['name' => 'Future Debut Title']);

        // Create titles with championships
        $this->titleWithChampion = Title::factory()->withCurrentChampion()->create(['name' => 'Defended Title']);
        $this->vacantTitle = Title::factory()->active()->create(['name' => 'Vacant Title']);
        $this->newTitle = Title::factory()->active()->create(['name' => 'New Title']);
    });

    describe('legacy scope tests', function () {
        test('active titles can be retrieved', function () {
            $activeTitle = Title::factory()->active()->create();
            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
            $inactiveTitle = Title::factory()->inactive()->create();
            $retiredTitle = Title::factory()->retired()->create();

            $activeTitles = Title::currentlyActive()->get();

            expect($activeTitles->pluck('id'))->toContain($activeTitle->id);
        });

        test('future activated titles can be retrieved', function () {
            $activeTitle = Title::factory()->active()->create();
            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
            $inactiveTitle = Title::factory()->inactive()->create();
            $retiredTitle = Title::factory()->retired()->create();

            $futureActivatedTitles = Title::query()->withPendingDebut()->get();

            expect($futureActivatedTitles->pluck('id'))->toContain($futureActivatedTitle->id);
        });

        test('inactive titles can be retrieved', function () {
            $activeTitle = Title::factory()->active()->create();
            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
            $inactiveTitle = Title::factory()->inactive()->create();
            $retiredTitle = Title::factory()->retired()->create();

            $inactiveTitles = Title::currentlyInactive()->get();

            expect($inactiveTitles->pluck('id'))->toContain($inactiveTitle->id);
        });

        test('retired titles can be retrieved', function () {
            $activeTitle = Title::factory()->active()->create();
            $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
            $inactiveTitle = Title::factory()->inactive()->create();
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
            expect($activeTitles->pluck('id'))->toContain($this->titleWithChampion->id);
            expect($activeTitles->pluck('id'))->toContain($this->vacantTitle->id);
            expect($activeTitles->pluck('id'))->toContain($this->newTitle->id);
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

    describe('availability scopes', function () {
        test('available scope returns active titles', function () {
            $availableTitles = Title::query()->available()->get();

            expect($availableTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($availableTitles->pluck('id'))->toContain($this->titleWithChampion->id);
            expect($availableTitles->pluck('id'))->toContain($this->vacantTitle->id);
            expect($availableTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
            expect($availableTitles->pluck('id'))->not->toContain($this->inactiveTitle->id);
            expect($availableTitles->pluck('id'))->not->toContain($this->retiredTitle->id);
        });

        test('unavailable scope returns non-active titles', function () {
            $unavailableTitles = Title::query()->unavailable()->get();

            expect($unavailableTitles->pluck('id'))->toContain($this->undebutedTitle->id);
            expect($unavailableTitles->pluck('id'))->toContain($this->inactiveTitle->id);
            expect($unavailableTitles->pluck('id'))->toContain($this->retiredTitle->id);
            expect($unavailableTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($unavailableTitles->pluck('id'))->not->toContain($this->titleWithChampion->id);
        });

        test('competable scope returns active titles', function () {
            $competableTitles = Title::query()->competable()->get();

            expect($competableTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($competableTitles->pluck('id'))->toContain($this->titleWithChampion->id);
            expect($competableTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
            expect($competableTitles->pluck('id'))->not->toContain($this->retiredTitle->id);
        });
    });

    describe('championship scopes', function () {
        test('vacant scope returns active titles without current champions', function () {
            $vacantTitles = Title::query()->vacant()->get();

            expect($vacantTitles->pluck('id'))->toContain($this->vacantTitle->id);
            expect($vacantTitles->pluck('id'))->toContain($this->newTitle->id);
            expect($vacantTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($vacantTitles->pluck('id'))->not->toContain($this->titleWithChampion->id);
            expect($vacantTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('defended scope returns titles with championship history', function () {
            $defendedTitles = Title::query()->defended()->get();

            expect($defendedTitles->pluck('id'))->toContain($this->titleWithChampion->id);
            expect($defendedTitles->pluck('id'))->not->toContain($this->newTitle->id);
            expect($defendedTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('newTitles scope returns titles without championship history', function () {
            $newTitles = Title::query()->newTitles()->get();

            expect($newTitles->pluck('id'))->toContain($this->newTitle->id);
            expect($newTitles->pluck('id'))->toContain($this->vacantTitle->id);
            expect($newTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($newTitles->pluck('id'))->toContain($this->undebutedTitle->id);
            expect($newTitles->pluck('id'))->not->toContain($this->titleWithChampion->id);
        });
    });

    describe('retirement scopes', function () {
        test('retired scope returns titles with current retirement', function () {
            $retiredTitles = Title::query()->retired()->get();

            expect($retiredTitles->pluck('id'))->toContain($this->retiredTitle->id);
            expect($retiredTitles->pluck('id'))->not->toContain($this->activeTitle->id);
            expect($retiredTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('unretired scope returns titles without current retirement', function () {
            $unretiredTitles = Title::query()->unretired()->get();

            expect($unretiredTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($unretiredTitles->pluck('id'))->toContain($this->undebutedTitle->id);
            expect($unretiredTitles->pluck('id'))->toContain($this->inactiveTitle->id);
            expect($unretiredTitles->pluck('id'))->not->toContain($this->retiredTitle->id);
        });
    });

    describe('scope method chaining', function () {
        test('can chain active and vacant scopes', function () {
            $activeVacantTitles = Title::query()
                ->active()
                ->vacant()
                ->get();

            expect($activeVacantTitles->pluck('id'))->toContain($this->vacantTitle->id);
            expect($activeVacantTitles->pluck('id'))->toContain($this->newTitle->id);
            expect($activeVacantTitles->pluck('id'))->not->toContain($this->titleWithChampion->id);
            expect($activeVacantTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('can chain available and defended scopes', function () {
            $availableDefendedTitles = Title::query()
                ->available()
                ->defended()
                ->get();

            expect($availableDefendedTitles->pluck('id'))->toContain($this->titleWithChampion->id);
            expect($availableDefendedTitles->pluck('id'))->not->toContain($this->newTitle->id);
            expect($availableDefendedTitles->pluck('id'))->not->toContain($this->undebutedTitle->id);
        });

        test('can chain scopes with additional filters', function () {
            $filteredTitles = Title::query()
                ->active()
                ->where('name', 'like', '%Active%')
                ->get();

            expect($filteredTitles->pluck('id'))->toContain($this->activeTitle->id);
            expect($filteredTitles->pluck('id'))->not->toContain($this->titleWithChampion->id);
        });
    });

    describe('scope performance and optimization', function () {
        test('undebuted scope uses efficient exists query', function () {
            $query = Title::query()->undebuted();
            $sql = $query->toSql();

            expect($sql)->toContain('not exists');
            expect($sql)->toContain('titles_activations');
        });

        test('active scope uses exists query for performance', function () {
            $query = Title::query()->active();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('titles_activations');
        });

        test('vacant scope combines active and championship filters', function () {
            $query = Title::query()->vacant();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('not exists');
            expect($sql)->toContain('championships');
        });
    });

    describe('scope edge cases', function () {
        test('scopes work with soft deleted titles', function () {
            $deletedTitle = Title::factory()->active()->create();
            $deletedTitle->delete();

            // Should not include soft deleted titles by default
            $activeTitles = Title::query()->active()->get();
            expect($activeTitles->pluck('id'))->not->toContain($deletedTitle->id);

            // Should include when specifically querying trashed
            $trashedActiveTitles = Title::onlyTrashed()->active()->get();
            expect($trashedActiveTitles->pluck('id'))->toContain($deletedTitle->id);
        });

        test('scopes handle empty database gracefully', function () {
            // Clear all titles
            Title::query()->delete();

            expect(Title::query()->active()->count())->toBe(0);
            expect(Title::query()->undebuted()->count())->toBe(0);
            expect(Title::query()->vacant()->count())->toBe(0);
            expect(Title::query()->available()->count())->toBe(0);
        });
    });

    describe('scope return types and fluency', function () {
        test('all scopes return static for proper chaining', function () {
            $builder = Title::query();

            expect($builder->undebuted())->toBeInstanceOf(get_class($builder));
            expect($builder->active())->toBeInstanceOf(get_class($builder));
            expect($builder->inactive())->toBeInstanceOf(get_class($builder));
            expect($builder->available())->toBeInstanceOf(get_class($builder));
            expect($builder->unavailable())->toBeInstanceOf(get_class($builder));
            expect($builder->competable())->toBeInstanceOf(get_class($builder));
            expect($builder->vacant())->toBeInstanceOf(get_class($builder));
            expect($builder->defended())->toBeInstanceOf(get_class($builder));
            expect($builder->newTitles())->toBeInstanceOf(get_class($builder));
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
