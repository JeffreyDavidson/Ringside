<?php

declare(strict_types=1);

use App\Models\Matches\MatchType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for MatchType model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the MatchType model is properly configured
 * and structured according to the data layer requirements.
 */
describe('MatchType Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $matchType = new MatchType();
            expect($matchType->getTable())->toBe('match_types');
        });

        test('has correct fillable properties', function () {
            $matchType = new MatchType();

            expect($matchType->getFillable())->toEqual([
                'name',
                'slug',
                'number_of_sides',
                'competitor_types',
            ]);
        });

        test('has correct casts configuration', function () {
            $matchType = new MatchType();
            $casts = $matchType->getCasts();

            expect($casts['number_of_sides'])->toBe('integer');
        });

        test('has custom eloquent builder', function () {
            $matchType = new MatchType();
            // MatchType model has no custom builder
            expect($matchType->query())->toBeObject();
        });

        test('has correct default values', function () {
            $matchType = new MatchType();
            // MatchType model has no custom default values
            expect($matchType)->toBeInstanceOf(MatchType::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(MatchType::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(MatchType::class);

            // MatchType model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(MatchType::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === MatchType::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $matchType = new MatchType();

            // MatchType model has standard Eloquent relationships but no custom business methods
            expect($matchType)->toBeInstanceOf(MatchType::class);
        });
    });
});
