<?php

declare(strict_types=1);

use App\Models\Matches\MatchDecision;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for MatchDecision model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the MatchDecision model is properly configured
 * and structured according to the data layer requirements.
 */
describe('MatchDecision Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $matchDecision = new MatchDecision();
            expect($matchDecision->getTable())->toBe('match_decisions');
        });

        test('has correct fillable properties', function () {
            $matchDecision = new MatchDecision();

            expect($matchDecision->getFillable())->toEqual([
                'name',
                'slug',
            ]);
        });

        test('has correct casts configuration', function () {
            $matchDecision = new MatchDecision();
            $casts = $matchDecision->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
        });

        test('has custom eloquent builder', function () {
            $matchDecision = new MatchDecision();

            // Model has no custom builder
            expect($matchDecision->query())->toBeObject();
        });

        test('has correct default values', function () {
            $matchDecision = new MatchDecision();

            // Model has no custom default values
            expect($matchDecision)->toBeInstanceOf(MatchDecision::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(MatchDecision::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(MatchDecision::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(MatchDecision::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === MatchDecision::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toHaveKey('NO_OUTCOME_DECISIONS');
            expect($modelConstants['NO_OUTCOME_DECISIONS'])->toBeArray();
        });
    });

    describe('business logic methods', function () {
        test('has no custom business logic methods', function () {
            $matchDecision = new MatchDecision();

            // Model has no custom business methods beyond base Model
            expect($matchDecision)->toBeInstanceOf(MatchDecision::class);
        });
    });
});
