<?php

declare(strict_types=1);

use App\Models\Managers\ManagerEmployment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for ManagerEmployment model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the ManagerEmployment model is properly configured
 * and structured according to the data layer requirements.
 */
describe('ManagerEmployment Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $managerEmployment = new ManagerEmployment();

            expect($managerEmployment->getFillable())->toEqual([
                'manager_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $managerEmployment = new ManagerEmployment();
            $casts = $managerEmployment->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $managerEmployment = new ManagerEmployment();

            expect($managerEmployment->getTable())->toBe('managers_employments');
        });

        test('has correct default values', function () {
            $managerEmployment = new ManagerEmployment();

            // Model has no custom default values
            expect($managerEmployment)->toBeInstanceOf(ManagerEmployment::class);
        });

        test('has custom eloquent builder', function () {
            $managerEmployment = new ManagerEmployment();

            // Model has no custom builder
            expect($managerEmployment->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(ManagerEmployment::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(ManagerEmployment::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(ManagerEmployment::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === ManagerEmployment::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has manager relationship method', function () {
            $managerEmployment = new ManagerEmployment();

            expect(method_exists($managerEmployment, 'manager'))->toBeTrue();
        });
    });
});
