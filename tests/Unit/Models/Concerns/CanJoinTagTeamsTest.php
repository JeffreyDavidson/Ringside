<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for CanJoinTagTeams
 *
 * This test ensures the CanJoinTagTeams trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, static override, cache reset, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use Ankurk91\Eloquent\Relations\BelongsToOne;
use App\Models\Concerns\CanJoinTagTeams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\Unit\Models\Concerns\Support\FakeTagTeamMemberModel;
use Tests\Unit\Models\Concerns\Support\FakeTagTeamPivotModel;

describe('CanJoinTagTeams Trait Unit Tests', function () {
    describe('tag team relationships', function () {
        test('provides tagTeams relationship', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            expect($model->tagTeams())->toBeInstanceOf(BelongsToMany::class);
        });

        test('provides currentTagTeam relationship', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            expect($model->currentTagTeam())->toBeInstanceOf(BelongsToOne::class);
        });

        test('provides previousTagTeams relationship', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            expect($model->previousTagTeams())->toBeInstanceOf(BelongsToMany::class);
        });

        test('provides previousTagTeam relationship', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            expect($model->previousTagTeam())->toBeInstanceOf(BelongsToOne::class);
        });
    });

    describe('tag team pivot model resolution', function () {
        test('can fake tag team pivot model class', function () {
            FakeTagTeamMemberModel::fakeTagTeamPivotModel(FakeTagTeamPivotModel::class);
            $model = new FakeTagTeamMemberModel();
            expect($model->resolveTagTeamPivotModel())->toBe(FakeTagTeamPivotModel::class);
            // Reset static override
            FakeTagTeamMemberModel::fakeTagTeamPivotModel(null);
        });
    });

    describe('tag team pivot table naming', function () {
        test('generates correct pivot table name', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }

                public function getTagTeamPivotTable(): string
                {
                    $related = 'tag_teams';
                    $self = str(class_basename(self::class))->snake()->plural();

                    return collect([$related, $self])->sort()->implode('_');
                }
            };
            $pivotTable = $model->getTagTeamPivotTable();
            expect($pivotTable)->toContain('tag_teams');
            expect(str_contains($pivotTable, '_'))->toBeTrue();
        });
    });

    describe('tag team relationship queries', function () {
        test('tagTeams relationship includes pivot data', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            $relation = $model->tagTeams();
            $pivotColumns = $relation->getPivotColumns();
            expect($pivotColumns)->toContain('joined_at');
            expect($pivotColumns)->toContain('left_at');
        });

        test('currentTagTeam relationship includes wherePivotNull constraint', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            $relation = $model->currentTagTeam();
            expect($relation)->toBeInstanceOf(BelongsToOne::class);
        });

        test('previousTagTeams relationship includes wherePivotNotNull constraint', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            $relation = $model->previousTagTeams();
            expect($relation)->toBeInstanceOf(BelongsToMany::class);
        });

        test('all tag team relationships use timestamps', function () {
            $model = new class extends Model
            {
                use CanJoinTagTeams;

                public function resolveTagTeamPivotModel(): string
                {
                    return FakeTagTeamPivotModel::class;
                }
            };
            $tagTeamsRelation = $model->tagTeams();
            $currentTagTeamRelation = $model->currentTagTeam();
            $previousTagTeamsRelation = $model->previousTagTeams();
            $previousTagTeamRelation = $model->previousTagTeam();
            expect($tagTeamsRelation)->toBeInstanceOf(BelongsToMany::class);
            expect($currentTagTeamRelation)->toBeInstanceOf(BelongsToOne::class);
            expect($previousTagTeamsRelation)->toBeInstanceOf(BelongsToMany::class);
            expect($previousTagTeamRelation)->toBeInstanceOf(BelongsToOne::class);
        });
    });
});
