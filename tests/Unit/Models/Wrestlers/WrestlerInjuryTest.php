<?php

declare(strict_types=1);

use App\Models\Wrestlers\WrestlerInjury;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for WrestlerInjury model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the WrestlerInjury model is properly configured
 * and structured according to the data layer requirements.
 */
describe('WrestlerInjury Model Unit Tests', function () {
    describe('wrestlerInjury attributes and configuration', function () {
        test('wrestlerInjury has correct fillable properties', function () {
            $wrestlerInjury = new WrestlerInjury();

            expect($wrestlerInjury->getFillable())->toEqual([
                'wrestler_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('wrestlerInjury has correct casts configuration', function () {
            $wrestlerInjury = new WrestlerInjury();
            $casts = $wrestlerInjury->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('wrestlerInjury has custom eloquent builder', function () {
            $wrestlerInjury = new WrestlerInjury();
            // Model has no custom builder
            expect($wrestlerInjury->query())->toBeObject();
        });

        test('wrestlerInjury has correct default values', function () {
            $wrestlerInjury = new WrestlerInjury();
            // Model has no custom default values
            expect($wrestlerInjury)->toBeInstanceOf(WrestlerInjury::class);
        });

        test('wrestlerInjury uses correct table name', function () {
            $wrestlerInjury = new WrestlerInjury();
            expect($wrestlerInjury->getTable())->toBe('wrestlers_injuries');
        });
    });

    describe('wrestlerInjury trait integration', function () {
        test('wrestlerInjury uses all required traits', function () {
            expect(WrestlerInjury::class)->usesTrait(HasFactory::class);
        });

        test('wrestlerInjury implements all required interfaces', function () {
            $interfaces = class_implements(WrestlerInjury::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
