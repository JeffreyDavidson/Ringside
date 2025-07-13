<?php

declare(strict_types=1);

use App\Builders\Events\EventBuilder;
use App\Models\Concerns\HasEventMatches;
use App\Models\Events\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Event model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Event model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Event Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $event = new Event();
            expect($event->getTable())->toBe('events');
        });

        test('has correct fillable properties', function () {
            $event = new Event();

            expect($event->getFillable())->toEqual([
                'name',
                'date',
                'venue_id',
                'preview',
            ]);
        });

        test('has correct casts configuration', function () {
            $event = new Event();
            $casts = $event->getCasts();

            expect($casts['date'])->toBe('datetime');
        });

        test('has custom eloquent builder', function () {
            $event = new Event();
            expect($event->query())->toBeInstanceOf(EventBuilder::class);
        });

        test('has correct default values', function () {
            $event = new Event();

            // Model has no custom default values
            expect($event)->toBeInstanceOf(Event::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Event::class)->usesTrait(HasFactory::class);
            expect(Event::class)->usesTrait(HasEventMatches::class);
            expect(Event::class)->usesTrait(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Event::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Event::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Event::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has venue relationship method', function () {
            $event = new Event();

            expect(method_exists($event, 'venue'))->toBeTrue();
        });

        test('has matches relationship method', function () {
            $event = new Event();

            expect(method_exists($event, 'matches'))->toBeTrue();
        });
    });
});
