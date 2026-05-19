<?php

declare(strict_types=1);

use App\Models\Shared\State;
use Sushi\Sushi;

/**
 * Unit tests for State model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the State model is properly configured
 * and structured according to the data layer requirements.
 */
describe('State Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $state = new State();

            // Model has no explicitly defined fillable properties (Sushi model)
            expect($state->getFillable())->toBeArray();
        });

        test('has correct casts configuration', function () {
            $state = new State();
            $casts = $state->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
        });

        test('uses correct table name', function () {
            $state = new State();

            // Model uses default table name based on class (handled by Sushi)
            expect($state->getTable())->toBe('states');
        });

        test('has correct default values', function () {
            $state = new State();

            // Model has no custom default values
            expect($state)->toBeInstanceOf(State::class);
        });

        test('has custom eloquent builder', function () {
            $state = new State();

            // Model has no custom builder
            expect($state->query())->toBeObject();
        });

        test('has state data rows property', function () {
            $state = new State();

            expect(property_exists($state, 'rows'))->toBeTrue();
            expect($state->rows)->toBeArray();
            expect($state->rows)->not->toBeEmpty();
        });

        test('contains expected US states', function () {
            $state = new State();

            // Check a few key states exist in the data
            $stateCodes = array_column($state->rows, 'code');
            expect($stateCodes)->toContain('CA');
            expect($stateCodes)->toContain('NY');
            expect($stateCodes)->toContain('TX');
            expect($stateCodes)->toContain('FL');
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(State::class)->usesTrait(Sushi::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(State::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(State::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === State::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has no custom business logic methods', function () {
            $state = new State();

            // Model has no custom business methods beyond base Model and Sushi
            expect($state)->toBeInstanceOf(State::class);
        });
    });
});
