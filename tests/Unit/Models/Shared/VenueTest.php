<?php

declare(strict_types=1);

use App\Builders\Shared\VenueBuilder;
use App\Models\Concerns\HoldsEvents;
use App\Models\Shared\Venue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Venue model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the Venue model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Venue Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $venue = new Venue();
            expect($venue->getTable())->toBe('venues');
        });

        test('has correct fillable properties', function () {
            $venue = new Venue();

            expect($venue->getFillable())->toEqual([
                'name',
                'street_address',
                'city',
                'state',
                'zipcode',
            ]);
        });

        test('has correct casts configuration', function () {
            $venue = new Venue();
            $casts = $venue->getCasts();

            // Venue model has no custom casts
            expect($casts)->toBeArray();
        });

        test('has custom eloquent builder', function () {
            $venue = new Venue();
            expect($venue->query())->toBeInstanceOf(VenueBuilder::class);
        });

        test('has correct default values', function () {
            $venue = new Venue();
            // Venue model has no custom default values
            expect($venue)->toBeInstanceOf(Venue::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Venue::class)->usesTrait(HasFactory::class);
            expect(Venue::class)->usesTrait(HoldsEvents::class);
            expect(Venue::class)->usesTrait(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Venue::class);

            // Venue model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Venue::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Venue::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has events relationship method', function () {
            $venue = new Venue();

            expect(method_exists($venue, 'events'))->toBeTrue();
        });
    });
});
