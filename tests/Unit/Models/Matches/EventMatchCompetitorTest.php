<?php

declare(strict_types=1);

use App\Collections\EventMatchCompetitorsCollection;
use App\Models\Matches\EventMatchCompetitor;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * Unit tests for EventMatchCompetitor model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the EventMatchCompetitor model is properly configured
 * and structured according to the data layer requirements.
 */
describe('EventMatchCompetitor Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            expect($eventMatchCompetitor->getFillable())->toEqual([
                'event_match_id',
                'competitor_id',
                'competitor_type',
                'side_number',
            ]);
        });

        test('has correct casts configuration', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();
            $casts = $eventMatchCompetitor->getCasts();

            expect($casts)->toBeArray();
            // MorphPivot models don't explicitly define 'id' cast
        });

        test('uses correct table name', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            expect($eventMatchCompetitor->getTable())->toBe('events_matches_competitors');
        });

        test('has correct default values', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            // Model has no custom default values
            expect($eventMatchCompetitor)->toBeInstanceOf(EventMatchCompetitor::class);
        });

        test('has custom eloquent builder', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            // Model has no custom builder
            expect($eventMatchCompetitor->query())->toBeObject();
        });

        test('extends MorphPivot base class', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            expect($eventMatchCompetitor)->toBeInstanceOf(MorphPivot::class);
        });

        test('uses custom collection class', function () {
            expect(EventMatchCompetitor::make()->newCollection())->toBeInstanceOf(EventMatchCompetitorsCollection::class);
        });
    });

    describe('trait integration', function () {
        test('extends MorphPivot class', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();
            expect($eventMatchCompetitor)->toBeInstanceOf(MorphPivot::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(EventMatchCompetitor::class);

            // Model implements no specific interfaces beyond base MorphPivot
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(EventMatchCompetitor::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === EventMatchCompetitor::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has competitor relationship method', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            expect(method_exists($eventMatchCompetitor, 'competitor'))->toBeTrue();
        });

        test('has getCompetitor business method', function () {
            $eventMatchCompetitor = new EventMatchCompetitor();

            expect(method_exists($eventMatchCompetitor, 'getCompetitor'))->toBeTrue();
        });
    });
});
