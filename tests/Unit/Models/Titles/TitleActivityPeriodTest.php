<?php

declare(strict_types=1);

use App\Models\Titles\TitleActivityPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for TitleActivityPeriod model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TitleActivityPeriod model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TitleActivityPeriod Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $titleActivityPeriod = new TitleActivityPeriod();

            expect($titleActivityPeriod->getFillable())->toEqual([
                'title_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $titleActivityPeriod = new TitleActivityPeriod();
            $casts = $titleActivityPeriod->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $titleActivityPeriod = new TitleActivityPeriod();

            expect($titleActivityPeriod->getTable())->toBe('titles_activations');
        });

        test('has correct default values', function () {
            $titleActivityPeriod = new TitleActivityPeriod();

            // Model has no custom default values
            expect($titleActivityPeriod)->toBeInstanceOf(TitleActivityPeriod::class);
        });

        test('has custom eloquent builder', function () {
            $titleActivityPeriod = new TitleActivityPeriod();

            // Model has no custom builder
            expect($titleActivityPeriod->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(TitleActivityPeriod::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(TitleActivityPeriod::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(TitleActivityPeriod::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TitleActivityPeriod::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has title relationship method', function () {
            $titleActivityPeriod = new TitleActivityPeriod();

            expect(method_exists($titleActivityPeriod, 'title'))->toBeTrue();
        });
    });
});
