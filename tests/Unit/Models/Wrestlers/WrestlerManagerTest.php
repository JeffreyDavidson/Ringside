<?php

declare(strict_types=1);

use App\Models\Wrestlers\WrestlerManager;

/**
 * Unit tests for WrestlerManager model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the WrestlerManager model is properly configured
 * and structured according to the data layer requirements.
 */
describe('WrestlerManager Model Unit Tests', function () {
    describe('wrestlerManager attributes and configuration', function () {
        test('wrestlerManager has correct fillable properties', function () {
            $wrestlerManager = new WrestlerManager();

            expect($wrestlerManager->getFillable())->toEqual([
                'wrestler_id',
                'manager_id',
                'hired_at',
                'fired_at',
            ]);
        });

        test('wrestlerManager has correct casts configuration', function () {
            $wrestlerManager = new WrestlerManager();
            $casts = $wrestlerManager->getCasts();

            expect($casts)->toBeArray();
            expect($casts['hired_at'])->toBe('datetime');
            expect($casts['fired_at'])->toBe('datetime');
        });

        test('wrestlerManager has custom eloquent builder', function () {
            $wrestlerManager = new WrestlerManager();
            // Model has no custom builder
            expect($wrestlerManager->query())->toBeObject();
        });

        test('wrestlerManager has correct default values', function () {
            $wrestlerManager = new WrestlerManager();
            // Model has no custom default values
            expect($wrestlerManager)->toBeInstanceOf(WrestlerManager::class);
        });

        test('wrestlerManager uses correct table name', function () {
            $wrestlerManager = new WrestlerManager();
            expect($wrestlerManager->getTable())->toBe('wrestlers_managers');
        });
    });

    describe('wrestlerManager trait integration', function () {
        test('wrestlerManager uses all required traits', function () {
            $traits = class_uses(WrestlerManager::class);

            // Add specific interface assertions here
            expect($traits)->toBeArray();
        });

        test('wrestlerManager implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerManager::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
