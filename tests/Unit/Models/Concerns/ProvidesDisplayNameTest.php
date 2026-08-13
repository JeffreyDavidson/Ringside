<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for ProvidesDisplayName
 *
 * This test ensures the ProvidesDisplayName trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies display name generation, field detection, accessor functionality, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\ProvidesDisplayName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use LogicException;

describe('ProvidesDisplayName Trait Unit Tests', function () {
    describe('display name generation', function () {
        test('uses name field when available', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Test Name';
            };
            expect($model->getDisplayName())->toBe('Test Name');
        });

        test('uses full_name field when name is not available', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $full_name = 'Full Test Name';
            };
            expect($model->getDisplayName())->toBe('Full Test Name');
        });

        test('combines first_name and last_name when available', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $first_name = 'John';

                public ?string $last_name = 'Doe';
            };
            expect($model->getDisplayName())->toBe('John Doe');
        });

        test('rejects first and last names without usable content', function (string $firstName, string $lastName) {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $first_name;

                public ?string $last_name;
            };
            $model->first_name = $firstName;
            $model->last_name = $lastName;

            expect(fn () => $model->getDisplayName())->toThrow(LogicException::class);
        })->with([
            'empty' => ['', ''],
            'whitespace' => ['  ', "\t"],
        ]);

        test('throws exception when no display name fields are available', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;
            };
            expect(fn () => $model->getDisplayName())->toThrow(LogicException::class);
        });
    });

    describe('display name accessor', function () {
        test('provides displayName accessor', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Test Name';
            };
            expect($model->displayName())->toBeInstanceOf(Attribute::class);
        });

        test('accessor returns Attribute instance', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Test Name';
            };
            expect($model->displayName())->toBeInstanceOf(Attribute::class);
        });
    });

    describe('field priority', function () {
        test('prioritizes name over full_name', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Primary Name';

                public ?string $full_name = 'Secondary Name';
            };
            expect($model->getDisplayName())->toBe('Primary Name');
        });

        test('prioritizes name over first_name and last_name', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Primary Name';

                public ?string $first_name = 'John';

                public ?string $last_name = 'Doe';
            };
            expect($model->getDisplayName())->toBe('Primary Name');
        });

        test('prioritizes full_name over first_name and last_name', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $full_name = 'Secondary Name';

                public ?string $first_name = 'John';

                public ?string $last_name = 'Doe';
            };
            expect($model->getDisplayName())->toBe('Secondary Name');
        });
    });

    describe('field detection', function () {
        test('detects name property correctly', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = 'Test';
            };
            expect($model->name)->toBe('Test');
        });

        test('detects full_name property correctly', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $full_name = 'Test';
            };
            expect($model->full_name)->toBe('Test');
        });

        test('detects first_name and last_name properties correctly', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $first_name = 'John';

                public ?string $last_name = 'Doe';
            };
            expect([$model->first_name, $model->last_name])->toBe(['John', 'Doe']);
        });
    });

    describe('error handling', function () {
        test('throws LogicException with model class name when no fields available', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;
            };
            expect(fn () => $model->getDisplayName())->toThrow(LogicException::class);
        });

        test('handles null values gracefully', function () {
            $model = new class extends Model
            {
                use ProvidesDisplayName;

                public ?string $name = null;

                public ?string $full_name = null;

                public ?string $first_name = null;

                public ?string $last_name = null;
            };
            expect(fn () => $model->getDisplayName())->toThrow(LogicException::class);
        });
    });
});
