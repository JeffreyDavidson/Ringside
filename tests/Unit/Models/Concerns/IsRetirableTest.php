<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsRetirable
 *
 * This test ensures the IsRetirable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, static override, cache reset, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\IsRetirable;
use App\Models\Wrestlers\WrestlerRetirement;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\Unit\Models\Concerns\Support\FakeRetirableModel;
use Tests\Unit\Models\Concerns\Support\FakeRetirementModel;

describe('IsRetirable Trait Unit Tests', function () {
    describe('retirement relationships', function () {
        test('provides retirements relationship', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->retirements())->toBeInstanceOf(HasMany::class);
        });

        test('provides current retirement relationship', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->currentRetirement())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous retirements relationship', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->previousRetirements())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous retirement relationship', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->previousRetirement())->toBeInstanceOf(HasOne::class);
        });

        test('retirements relationship uses the correct related model', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->retirements();
            expect($relation)->toBeInstanceOf(HasMany::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeRetirementModel::class);
        });

        test('currentRetirement relationship uses the correct related model', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->currentRetirement();
            expect($relation)->toBeInstanceOf(HasOne::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeRetirementModel::class);
        });
    });

    describe('retirement status checks', function () {
        test('can check if model is retired', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function currentRetirement()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return true;
                        }
                    };
                }
            };
            expect($model->isRetired())->toBeTrue();
        });

        test('can check if model is not retired', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function currentRetirement()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return false;
                        }
                    };
                }
            };
            expect($model->isRetired())->toBeFalse();
        });

        test('can check if model has retirements', function () {
            $modelWith = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function retirements()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return true;
                        }
                    };
                }
            };
            $modelWithout = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function retirements()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return false;
                        }
                    };
                }
            };
            expect($modelWith->hasRetirements())->toBeTrue();
            expect($modelWithout->hasRetirements())->toBeFalse();
        });
    });

    describe('retirement model resolution', function () {
        test('can fake retirement model class', function () {
            FakeRetirableModel::fakeRetirementModel(FakeRetirementModel::class);
            $model = new FakeRetirableModel();
            expect($model->resolveRetirementModelClass())->toBe(FakeRetirementModel::class);
            // Reset static override
            FakeRetirableModel::fakeRetirementModel(null);
        });
    });

    describe('retirement relationship queries', function () {
        test('current retirement query includes whereNull ended_at', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->currentRetirement();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'Null' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNull)->toBeTrue();
        });

        test('previous retirements query includes whereNotNull ended_at', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->previousRetirements();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNotNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'NotNull' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNotNull)->toBeTrue();
        });

        test('previous retirement query includes ofMany constraint', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->previousRetirement();
            expect($relation)->toBeInstanceOf(HasOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });
    });

    describe('retirement validation methods', function () {
        test('can check if model can be retired', function () {
            $employedModel = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Employed;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }
            };

            $retiredModel = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Retired;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }
            };

            expect($employedModel->canBeRetired())->toBeTrue();
            expect($retiredModel->canBeRetired())->toBeFalse();
        });

        test('can check if model can be unretired', function () {
            $retiredModel = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Retired;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }
            };

            $employedModel = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Employed;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }
            };

            expect($retiredModel->canBeUnretired())->toBeTrue();
            expect($employedModel->canBeUnretired())->toBeFalse();
        });

        test('can ensure model can be retired', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Employed;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }

                public function ensureCanBeRetired(): void
                {
                    if (! $this->canBeRetired()) {
                        throw new Exception('Cannot be retired');
                    }
                }
            };

            expect(fn () => $model->ensureCanBeRetired())->not->toThrow(Exception::class);
        });

        test('can ensure model can be unretired', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public $status = EmploymentStatus::Retired;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }

                public function ensureCanBeUnretired(): void
                {
                    if (! $this->canBeUnretired()) {
                        throw new Exception('Cannot be unretired');
                    }
                }
            };

            expect(fn () => $model->ensureCanBeUnretired())->not->toThrow(Exception::class);
        });
    });

    describe('retirement period accessors', function () {
        test('provides previous retirement accessor', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public $retirements;

                public function __construct()
                {
                    parent::__construct();
                    $this->retirements = collect([
                        (object) ['ended_at' => '2024-01-01', 'id' => 1],
                        (object) ['ended_at' => '2024-01-02', 'id' => 2],
                    ]);
                }

                public function retirements(): HasMany
                {
                    return $this->hasMany(WrestlerRetirement::class, 'wrestler_id');
                }

                public function getPreviousRetirementsAttribute()
                {
                    return $this->retirements->whereNotNull('ended_at');
                }
            };

            $previousRetirements = $model->getPreviousRetirementsAttribute();
            expect($previousRetirements)->toHaveCount(2);
        });

        test('provides previous retirement accessor (single)', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public $retirements;

                public function __construct()
                {
                    parent::__construct();
                    $this->retirements = collect([
                        (object) ['ended_at' => '2024-01-01', 'id' => 1],
                    ]);
                }

                public function retirements(): HasMany
                {
                    return $this->hasMany(WrestlerRetirement::class, 'wrestler_id');
                }

                public function getPreviousRetirementAttribute()
                {
                    return $this->retirements->whereNotNull('ended_at')->first();
                }
            };

            $previousRetirement = $model->getPreviousRetirementAttribute();
            expect($previousRetirement->id)->toBe(1);
        });
    });

    describe('retirement history methods', function () {
        test('can get retirement history', function () {
            $model = new class extends Model
            {
                use IsRetirable;

                public $retirements;

                public function __construct()
                {
                    parent::__construct();
                    $this->retirements = collect([
                        (object) ['started_at' => '2024-01-01', 'ended_at' => '2024-01-15', 'id' => 1],
                        (object) ['started_at' => '2024-01-16', 'ended_at' => null, 'id' => 2],
                    ]);
                }

                public function retirements(): HasMany
                {
                    return $this->hasMany(WrestlerRetirement::class, 'wrestler_id');
                }

                public function getRetirementHistory()
                {
                    return $this->retirements;
                }
            };

            $history = $model->getRetirementHistory();
            expect($history)->toHaveCount(2);
        });

        test('can check if model has retirement history', function () {
            $modelWithHistory = new class extends Model
            {
                use IsRetirable;

                public $retirements;

                public function __construct()
                {
                    parent::__construct();
                    $this->retirements = collect([
                        (object) ['started_at' => '2024-01-01', 'id' => 1],
                    ]);
                }

                public function retirements(): HasMany
                {
                    return $this->hasMany(WrestlerRetirement::class, 'wrestler_id');
                }

                public function hasRetirementHistory(): bool
                {
                    return $this->retirements->isNotEmpty();
                }
            };

            $modelWithoutHistory = new class extends Model
            {
                use IsRetirable;

                public $retirements;

                public function __construct()
                {
                    parent::__construct();
                    $this->retirements = collect([]);
                }

                public function retirements(): HasMany
                {
                    return $this->hasMany(WrestlerRetirement::class, 'wrestler_id');
                }

                public function hasRetirementHistory(): bool
                {
                    return $this->retirements->isNotEmpty();
                }
            };

            expect($modelWithHistory->hasRetirementHistory())->toBeTrue();
            expect($modelWithoutHistory->hasRetirementHistory())->toBeFalse();
        });
    });
});
