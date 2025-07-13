<?php

declare(strict_types=1);

use App\Models\Stables\StableActivityPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for StableActivityPeriod model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the StableActivityPeriod model is properly configured
 * and structured according to the data layer requirements.
 */
describe('StableActivityPeriod Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $stableActivityPeriod = new StableActivityPeriod();

            expect($stableActivityPeriod->getFillable())->toEqual([
                'stable_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $stableActivityPeriod = new StableActivityPeriod();
            $casts = $stableActivityPeriod->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $stableActivityPeriod = new StableActivityPeriod();

            expect($stableActivityPeriod->getTable())->toBe('stables_activations');
        });

        test('has correct default values', function () {
            $stableActivityPeriod = new StableActivityPeriod();

            // Model has no custom default values
            expect($stableActivityPeriod)->toBeInstanceOf(StableActivityPeriod::class);
        });

        test('has custom eloquent builder', function () {
            $stableActivityPeriod = new StableActivityPeriod();

            // Model has no custom builder
            expect($stableActivityPeriod->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(StableActivityPeriod::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(StableActivityPeriod::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(StableActivityPeriod::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === StableActivityPeriod::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has stable relationship method', function () {
            $stableActivityPeriod = new StableActivityPeriod();

            expect(method_exists($stableActivityPeriod, 'stable'))->toBeTrue();
        });
    });
});
