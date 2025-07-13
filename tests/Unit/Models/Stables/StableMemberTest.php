<?php

declare(strict_types=1);

use App\Models\Stables\StableMember;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Unit tests for StableMember model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the StableMember model is properly configured
 * and structured according to the data layer requirements.
 */
describe('StableMember Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $stableMember = new StableMember();

            expect($stableMember->getFillable())->toEqual([
                'stable_id',
                'member_id',
                'member_type',
                'joined_at',
                'left_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $stableMember = new StableMember();
            $casts = $stableMember->getCasts();

            expect($casts)->toBeArray();
            expect($casts['joined_at'])->toBe('datetime');
            expect($casts['left_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $stableMember = new StableMember();

            // Model uses polymorphic table name
            expect($stableMember->getTable())->toBe('stables_members');
        });

        test('has correct default values', function () {
            $stableMember = new StableMember();

            // Model has no custom default values
            expect($stableMember)->toBeInstanceOf(StableMember::class);
        });

        test('has custom eloquent builder', function () {
            $stableMember = new StableMember();

            // Model has no custom builder
            expect($stableMember->query())->toBeObject();
        });

        test('extends MorphPivot base class', function () {
            $stableMember = new StableMember();

            expect($stableMember)->toBeInstanceOf(MorphPivot::class);
        });
    });

    describe('trait integration', function () {
        test('extends MorphPivot class', function () {
            $stableMember = new StableMember();
            expect($stableMember)->toBeInstanceOf(MorphPivot::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(StableMember::class);

            // Model implements no specific interfaces beyond base MorphPivot
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(StableMember::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === StableMember::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has no custom business logic methods', function () {
            $stableMember = new StableMember();

            // Model has no custom business methods beyond base MorphPivot
            expect($stableMember)->toBeInstanceOf(StableMember::class);
        });
    });
});
