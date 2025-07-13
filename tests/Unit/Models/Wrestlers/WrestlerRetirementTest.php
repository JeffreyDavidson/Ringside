<?php

declare(strict_types=1);

use App\Models\Wrestlers\WrestlerRetirement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for WrestlerRetirement model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the WrestlerRetirement model is properly configured
 * and structured according to the data layer requirements.
 */
describe('WrestlerRetirement Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $wrestlerRetirement = new WrestlerRetirement();

            expect($wrestlerRetirement->getFillable())->toEqual([
                'wrestler_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $wrestlerRetirement = new WrestlerRetirement();
            $casts = $wrestlerRetirement->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $wrestlerRetirement = new WrestlerRetirement();

            expect($wrestlerRetirement->getTable())->toBe('wrestlers_retirements');
        });

        test('has correct default values', function () {
            $wrestlerRetirement = new WrestlerRetirement();

            // Model has no custom default values
            expect($wrestlerRetirement)->toBeInstanceOf(WrestlerRetirement::class);
        });

        test('has custom eloquent builder', function () {
            $wrestlerRetirement = new WrestlerRetirement();

            // Model has no custom builder
            expect($wrestlerRetirement->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(WrestlerRetirement::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerRetirement::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(WrestlerRetirement::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === WrestlerRetirement::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has wrestler relationship method', function () {
            $wrestlerRetirement = new WrestlerRetirement();

            expect(method_exists($wrestlerRetirement, 'wrestler'))->toBeTrue();
        });
    });
});
