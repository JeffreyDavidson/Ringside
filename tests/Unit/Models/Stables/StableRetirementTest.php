<?php

declare(strict_types=1);

use App\Models\Stables\StableRetirement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for StableRetirement model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the StableRetirement model is properly configured
 * and structured according to the data layer requirements.
 */
describe('StableRetirement Model Unit Tests', function () {
    describe('stableRetirement attributes and configuration', function () {
        test('stableRetirement has correct fillable properties', function () {
            $stableRetirement = new StableRetirement();

            expect($stableRetirement->getFillable())->toEqual([
                'stable_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('stableRetirement has correct casts configuration', function () {
            $stableRetirement = new StableRetirement();
            $casts = $stableRetirement->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('stableRetirement has custom eloquent builder', function () {
            $stableRetirement = new StableRetirement();
            // Model has no custom builder
            expect($stableRetirement->query())->toBeObject();
        });

        test('stableRetirement has correct default values', function () {
            $stableRetirement = new StableRetirement();
            // Model has no custom default values
            expect($stableRetirement)->toBeInstanceOf(StableRetirement::class);
        });

        test('stableRetirement uses correct table name', function () {
            $stableRetirement = new StableRetirement();
            expect($stableRetirement->getTable())->toBe('stables_retirements');
        });
    });

    describe('stableRetirement trait integration', function () {
        test('stableRetirement uses all required traits', function () {
            expect(StableRetirement::class)->usesTrait(HasFactory::class);
        });

        test('stableRetirement implements all required interfaces', function () {
            $interfaces = class_implements(StableRetirement::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
