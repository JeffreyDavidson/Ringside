<?php

declare(strict_types=1);

use App\Collections\MatchCompetitorsCollection;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Unit tests for MatchCompetitor model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the MatchCompetitor model is properly configured
 * and structured according to the data layer requirements.
 */
describe('MatchCompetitor Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $MatchCompetitor = new MatchCompetitor();

            expect($MatchCompetitor->getFillable())->toEqual([
                'match_id',
                'competitor_id',
                'competitor_type',
                'side_number',
            ]);
        });

        test('has correct casts configuration', function () {
            $MatchCompetitor = new MatchCompetitor();
            $casts = $MatchCompetitor->getCasts();

            expect($casts)->toBeArray();
            // MorphPivot models don't explicitly define 'id' cast
        });

        test('uses correct table name', function () {
            $MatchCompetitor = new MatchCompetitor();

            expect($MatchCompetitor->getTable())->toBe('events_matches_competitors');
        });

        test('has correct default values', function () {
            $MatchCompetitor = new MatchCompetitor();

            // Model has no custom default values
            expect($MatchCompetitor)->toBeInstanceOf(MatchCompetitor::class);
        });

        test('has custom eloquent builder', function () {
            $MatchCompetitor = new MatchCompetitor();

            // Model has no custom builder
            expect($MatchCompetitor->query())->toBeObject();
        });

        test('extends MorphPivot base class', function () {
            $MatchCompetitor = new MatchCompetitor();

            expect($MatchCompetitor)->toBeInstanceOf(MorphPivot::class);
        });

        test('uses custom collection class', function () {
            expect(MatchCompetitor::make()->newCollection())->toBeInstanceOf(MatchCompetitorsCollection::class);
        });
    });

    describe('trait integration', function () {
        test('extends MorphPivot class', function () {
            $MatchCompetitor = new MatchCompetitor();
            expect($MatchCompetitor)->toBeInstanceOf(MorphPivot::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(MatchCompetitor::class);

            // Model implements no specific interfaces beyond base MorphPivot
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(MatchCompetitor::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === MatchCompetitor::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has competitor relationship method', function () {
            $MatchCompetitor = new MatchCompetitor();

            expect(method_exists($MatchCompetitor, 'competitor'))->toBeTrue();
        });

        test('has getCompetitor business method', function () {
            $MatchCompetitor = new MatchCompetitor();

            expect(method_exists($MatchCompetitor, 'getCompetitor'))->toBeTrue();
        });
    });
});
