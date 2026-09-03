<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Integration tests for ActivityPeriod model structure and configuration.
 *
 * INTEGRATION TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the ActivityPeriod model is properly configured
 * and structured according to the data layer requirements.
 */
describe('ActivityPeriod Model Integration Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $activityPeriod = new ActivityPeriod();

            expect($activityPeriod->getFillable())->toEqual([
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $activityPeriod = new ActivityPeriod();
            $casts = $activityPeriod->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $activityPeriod = new ActivityPeriod();

            expect($activityPeriod->getTable())->toBe('activity_periods');
        });

        test('has correct default values', function () {
            $activityPeriod = new ActivityPeriod();

            // Model has no custom default values
            expect($activityPeriod)->toBeInstanceOf(ActivityPeriod::class);
        });

        test('has custom eloquent builder', function () {
            $activityPeriod = new ActivityPeriod();

            // Model has no custom builder
            expect($activityPeriod->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(class_uses(ActivityPeriod::class))->toContain(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(ActivityPeriod::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(ActivityPeriod::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === ActivityPeriod::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

});
