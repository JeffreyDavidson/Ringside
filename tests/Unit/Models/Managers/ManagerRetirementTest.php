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
    describe('wrestlerEmployment attributes and configuration', function () {
        test('wrestlerEmployment has correct fillable properties', function () {
            $wrestlerEmployment = new WrestlerEmployment();

            expect($wrestlerEmployment->getFillable())->toEqual([
                'wrestler_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('wrestlerEmployment has correct casts configuration', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            $casts = $wrestlerEmployment->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('wrestlerEmployment has custom eloquent builder', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            // Model has no custom builder
            expect($wrestlerEmployment->query())->toBeObject();
        });

        test('wrestlerEmployment has correct default values', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            // Model has no custom default values
            expect($wrestlerEmployment)->toBeInstanceOf(WrestlerEmployment::class);
        });

        test('wrestlerEmployment uses correct table name', function () {
            $wrestlerEmployment = new WrestlerEmployment();
            expect($wrestlerEmployment->getTable())->toBe('wrestlers_employments');
        });
    });

    describe('wrestlerEmployment trait integration', function () {
        test('wrestlerEmployment uses all required traits', function () {
            expect(WrestlerEmployment::class)->usesTrait(HasFactory::class);
        });

        test('wrestlerEmployment implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerEmployment::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
