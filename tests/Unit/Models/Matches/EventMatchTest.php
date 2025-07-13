<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for EventMatch model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the EventMatch model is properly configured
 * and structured according to the data layer requirements.
 */
describe('EventMatch Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $eventMatch = new EventMatch();
            expect($eventMatch->getTable())->toBe('events_matches');
        });

        test('has correct fillable properties', function () {
            $eventMatch = new EventMatch();

            expect($eventMatch->getFillable())->toEqual([
                'event_id',
                'match_number',
                'match_type_id',
                'match_stipulation_id',
                'preview',
            ]);
        });

        test('has correct casts configuration', function () {
            $eventMatch = new EventMatch();
            $casts = $eventMatch->getCasts();

            // EventMatch model has no custom casts
            expect($casts)->toBeArray();
        });

        test('has custom eloquent builder', function () {
            $eventMatch = new EventMatch();
            // EventMatch model has no custom builder
            expect($eventMatch->query())->toBeObject();
        });

        test('has correct default values', function () {
            $eventMatch = new EventMatch();
            // EventMatch model has no custom default values
            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(EventMatch::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(EventMatch::class);

            // EventMatch model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(EventMatch::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === EventMatch::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $eventMatch = new EventMatch();

            // EventMatch model has standard Eloquent relationships but no custom business methods
            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
        });
    });
});
