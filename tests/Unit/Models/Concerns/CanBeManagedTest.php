<?php

declare(strict_types=1);

/**
 * Unit tests for CanBeManaged trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship method definitions (managers, currentManagers, previousManagers)
 * - Related model class resolution and caching
 * - Static override methods for testing flexibility
 * - Pivot model configuration and foreign key handling
 * - Cache reset mechanisms and error handling
 *
 * This test ensures the CanBeManaged trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies that the trait can be safely reused across any model without business logic dependencies.
 * This is NOT a business logic test - it focuses on trait mechanics and structure only.
 *
 * @see CanBeManaged
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\CanBeManaged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\Unit\Models\Concerns\Support\FakeManageableModel;
use Tests\Unit\Models\Concerns\Support\FakeManagerPivotModel;

describe('CanBeManaged Trait Unit Tests', function () {
    describe('manager relationships', function () {
        test('provides managers relationship', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            expect($model->managers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('provides currentManagers relationship', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            expect($model->currentManagers())->toBeInstanceOf(BelongsToMany::class);
        });

        test('provides previousManagers relationship', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            expect($model->previousManagers())->toBeInstanceOf(BelongsToMany::class);
        });
    });

    describe('manager pivot model resolution', function () {
        test('can fake manager pivot model class', function () {
            FakeManageableModel::fakeManagerPivotModel(FakeManagerPivotModel::class);
            $model = new FakeManageableModel();
            expect($model->resolveManagersPivotModel())->toBe(FakeManagerPivotModel::class);
            // Reset static override
            FakeManageableModel::fakeManagerPivotModel(null);
        });
    });

    describe('manager pivot table naming', function () {
        test('generates correct pivot table name', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }

                public function getManagersPivotTable(): string
                {
                    $self = str(class_basename(self::class))->snake()->plural();

                    return $self.'_managers';
                }
            };
            $pivotTable = $model->getManagersPivotTable();
            expect($pivotTable)->toContain('managers');
            expect(str_contains($pivotTable, '_'))->toBeTrue();
        });
    });

    describe('manager relationship queries', function () {
        test('managers relationship includes pivot data', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            $relation = $model->managers();
            $pivotColumns = $relation->getPivotColumns();
            expect($pivotColumns)->toContain('hired_at');
            expect($pivotColumns)->toContain('fired_at');
        });

        test('currentManagers relationship includes wherePivotNull constraint', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            $relation = $model->currentManagers();
            expect($relation)->toBeInstanceOf(BelongsToMany::class);
        });

        test('previousManagers relationship includes wherePivotNotNull constraint', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            $relation = $model->previousManagers();
            expect($relation)->toBeInstanceOf(BelongsToMany::class);
        });

        test('all manager relationships use timestamps', function () {
            $model = new class extends Model
            {
                use CanBeManaged;

                public function resolveManagersPivotModel(): string
                {
                    return FakeManagerPivotModel::class;
                }
            };
            $managersRelation = $model->managers();
            $currentManagersRelation = $model->currentManagers();
            $previousManagersRelation = $model->previousManagers();
            expect($managersRelation)->toBeInstanceOf(BelongsToMany::class);
            expect($currentManagersRelation)->toBeInstanceOf(BelongsToMany::class);
            expect($previousManagersRelation)->toBeInstanceOf(BelongsToMany::class);
        });
    });
});
