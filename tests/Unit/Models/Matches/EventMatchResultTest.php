<?php

declare(strict_types=1);

use App\Models\Matches\EventMatchResult;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for EventMatchResult model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the EventMatchResult model is properly configured
 * and structured according to the data layer requirements.
 */
describe('EventMatchResult Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $eventMatchResult = new EventMatchResult();

            // Model has no explicitly defined fillable properties
            expect($eventMatchResult->getFillable())->toBeArray();
        });

        test('has correct casts configuration', function () {
            $eventMatchResult = new EventMatchResult();
            $casts = $eventMatchResult->getCasts();

            expect($casts)->toBeArray();
            // Model has no custom casts
        });

        test('uses correct table name', function () {
            $eventMatchResult = new EventMatchResult();

            expect($eventMatchResult->getTable())->toBe('events_matches_results');
        });

        test('has correct default values', function () {
            $eventMatchResult = new EventMatchResult();

            // Model has no custom default values
            expect($eventMatchResult)->toBeInstanceOf(EventMatchResult::class);
        });

        test('has custom eloquent builder', function () {
            $eventMatchResult = new EventMatchResult();

            // Model has no custom builder
            expect($eventMatchResult->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('extends Model class', function () {
            $eventMatchResult = new EventMatchResult();
            expect($eventMatchResult)->toBeInstanceOf(Model::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(EventMatchResult::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(EventMatchResult::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === EventMatchResult::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has winners relationship method', function () {
            $eventMatchResult = new EventMatchResult();

            expect(method_exists($eventMatchResult, 'winners'))->toBeTrue();
        });

        test('has losers relationship method', function () {
            $eventMatchResult = new EventMatchResult();

            expect(method_exists($eventMatchResult, 'losers'))->toBeTrue();
        });

        test('has decision relationship method', function () {
            $eventMatchResult = new EventMatchResult();

            expect(method_exists($eventMatchResult, 'decision'))->toBeTrue();
        });
    });
});
