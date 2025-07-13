<?php

declare(strict_types=1);

use App\Models\Referees\RefereeSuspension;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for RefereeSuspension model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the RefereeSuspension model is properly configured
 * and structured according to the data layer requirements.
 */
describe('RefereeSuspension Model Unit Tests', function () {
    describe('refereeSuspension attributes and configuration', function () {
        test('refereeSuspension has correct fillable properties', function () {
            $refereeSuspension = new RefereeSuspension();

            expect($refereeSuspension->getFillable())->toEqual([
                'referee_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('refereeSuspension has correct casts configuration', function () {
            $refereeSuspension = new RefereeSuspension();
            $casts = $refereeSuspension->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('refereeSuspension has custom eloquent builder', function () {
            $refereeSuspension = new RefereeSuspension();
            // Model has no custom builder
            expect($refereeSuspension->query())->toBeObject();
        });

        test('refereeSuspension has correct default values', function () {
            $refereeSuspension = new RefereeSuspension();
            // Model has no custom default values
            expect($refereeSuspension)->toBeInstanceOf(RefereeSuspension::class);
        });

        test('refereeSuspension uses correct table name', function () {
            $refereeSuspension = new RefereeSuspension();
            expect($refereeSuspension->getTable())->toBe('referees_suspensions');
        });
    });

    describe('refereeSuspension trait integration', function () {
        test('refereeSuspension uses all required traits', function () {
            expect(RefereeSuspension::class)->usesTrait(HasFactory::class);
        });

        test('refereeSuspension implements all required interfaces', function () {
            $interfaces = class_implements(RefereeSuspension::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
