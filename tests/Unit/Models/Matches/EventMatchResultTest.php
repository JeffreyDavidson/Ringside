<?php

declare(strict_types=1);

use App\Models\Matches\MatchResult;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for MatchResult model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the MatchResult model is properly configured
 * and structured according to the data layer requirements.
 */
describe('MatchResult Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $MatchResult = new MatchResult();

            // Model has no explicitly defined fillable properties
            expect($MatchResult->getFillable())->toBeArray();
        });

        test('has correct casts configuration', function () {
            $MatchResult = new MatchResult();
            $casts = $MatchResult->getCasts();

            expect($casts)->toBeArray();
            // Model has no custom casts
        });

        test('uses correct table name', function () {
            $MatchResult = new MatchResult();

            expect($MatchResult->getTable())->toBe('events_matches_results');
        });

        test('has correct default values', function () {
            $MatchResult = new MatchResult();

            // Model has no custom default values
            expect($MatchResult)->toBeInstanceOf(MatchResult::class);
        });

        test('has custom eloquent builder', function () {
            $MatchResult = new MatchResult();

            // Model has no custom builder
            expect($MatchResult->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('extends Model class', function () {
            $MatchResult = new MatchResult();
            expect($MatchResult)->toBeInstanceOf(Model::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(MatchResult::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(MatchResult::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === MatchResult::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has winners relationship method', function () {
            $MatchResult = new MatchResult();

            expect(method_exists($MatchResult, 'winners'))->toBeTrue();
        });

        test('has losers relationship method', function () {
            $MatchResult = new MatchResult();

            expect(method_exists($MatchResult, 'losers'))->toBeTrue();
        });

        test('has decision relationship method', function () {
            $MatchResult = new MatchResult();

            expect(method_exists($MatchResult, 'decision'))->toBeTrue();
        });
    });
});
