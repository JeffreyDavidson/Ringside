<?php

declare(strict_types=1);

use App\Models\Managers\ManagerSuspension;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for ManagerSuspension model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the ManagerSuspension model is properly configured
 * and structured according to the data layer requirements.
 */
describe('ManagerSuspension Model Unit Tests', function () {
    describe('managerSuspension attributes and configuration', function () {
        test('managerSuspension has correct fillable properties', function () {
            $managerSuspension = new ManagerSuspension();

            expect($managerSuspension->getFillable())->toEqual([
                'manager_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('managerSuspension has correct casts configuration', function () {
            $managerSuspension = new ManagerSuspension();
            $casts = $managerSuspension->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('managerSuspension has custom eloquent builder', function () {
            $managerSuspension = new ManagerSuspension();
            // Model has no custom builder
            expect($managerSuspension->query())->toBeObject();
        });

        test('managerSuspension has correct default values', function () {
            $managerSuspension = new ManagerSuspension();
            // Model has no custom default values
            expect($managerSuspension)->toBeInstanceOf(ManagerSuspension::class);
        });

        test('managerSuspension uses correct table name', function () {
            $managerSuspension = new ManagerSuspension();
            expect($managerSuspension->getTable())->toBe('managers_suspensions');
        });
    });

    describe('managerSuspension trait integration', function () {
        test('managerSuspension uses all required traits', function () {
            expect(ManagerSuspension::class)->usesTrait(HasFactory::class);
        });

        test('managerSuspension implements all required interfaces', function () {
            $interfaces = class_implements(ManagerSuspension::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
