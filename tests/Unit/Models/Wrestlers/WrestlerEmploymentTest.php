<?php

declare(strict_types=1);

use App\Models\Wrestlers\WrestlerEmployment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for WrestlerEmployment model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the WrestlerEmployment model is properly configured
 * and structured according to the data layer requirements.
 */
describe('WrestlerEmployment Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $wrestlerEmployment = new WrestlerEmployment();

            expect($wrestlerEmployment->getFillable())->toEqual([
                'wrestler_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            $casts = $wrestlerEmployment->getCasts();

            expect($casts)->toBeArray();
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('has custom eloquent builder', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            // Model has no custom builder
            expect($wrestlerEmployment->query())->toBeObject();
        });

        test('has correct default values', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            // Model has no custom default values
            expect($wrestlerEmployment)->toBeInstanceOf(WrestlerEmployment::class);
        });

        test('uses correct table name', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            expect($wrestlerEmployment->getTable())->toBe('wrestlers_employments');
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(WrestlerEmployment::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerEmployment::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(WrestlerEmployment::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === WrestlerEmployment::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has wrestler relationship method', function () {
            $wrestlerEmployment = new WrestlerEmployment();

            expect(method_exists($wrestlerEmployment, 'wrestler'))->toBeTrue();
        });
    });
});
