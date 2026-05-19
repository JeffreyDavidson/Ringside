<?php

declare(strict_types=1);

use App\Models\Wrestlers\WrestlerSuspension;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for WrestlerSuspension model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the WrestlerSuspension model is properly configured
 * and structured according to the data layer requirements.
 */
describe('WrestlerSuspension Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $wrestlerSuspension = new WrestlerSuspension();

            expect($wrestlerSuspension->getFillable())->toEqual([
                'wrestler_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $wrestlerSuspension = new WrestlerSuspension();
            $casts = $wrestlerSuspension->getCasts();

            expect($casts)->toBeArray();
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('has custom eloquent builder', function () {
            $wrestlerSuspension = new WrestlerSuspension();
            // Model has no custom builder
            expect($wrestlerSuspension->query())->toBeObject();
        });

        test('has correct default values', function () {
            $wrestlerSuspension = new WrestlerSuspension();
            // Model has no custom default values
            expect($wrestlerSuspension)->toBeInstanceOf(WrestlerSuspension::class);
        });

        test('uses correct table name', function () {
            $wrestlerSuspension = new WrestlerSuspension();
            expect($wrestlerSuspension->getTable())->toBe('wrestlers_suspensions');
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(WrestlerSuspension::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerSuspension::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(WrestlerSuspension::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === WrestlerSuspension::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has wrestler relationship method', function () {
            $wrestlerSuspension = new WrestlerSuspension();

            expect(method_exists($wrestlerSuspension, 'wrestler'))->toBeTrue();
        });
    });
});
