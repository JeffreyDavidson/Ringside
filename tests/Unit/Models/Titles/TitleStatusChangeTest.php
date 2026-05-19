<?php

declare(strict_types=1);

use App\Enums\Shared\ActivationStatus;
use App\Models\Titles\TitleStatusChange;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for TitleStatusChange model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TitleStatusChange model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TitleStatusChange Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $titleStatusChange = new TitleStatusChange();

            expect($titleStatusChange->getFillable())->toEqual([
                'title_id',
                'status',
                'changed_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $titleStatusChange = new TitleStatusChange();
            $casts = $titleStatusChange->getCasts();

            expect($casts)->toBeArray();
            expect($casts['status'])->toBe(ActivationStatus::class);
            expect($casts['changed_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $titleStatusChange = new TitleStatusChange();

            expect($titleStatusChange->getTable())->toBe('titles_status_changes');
        });

        test('has correct default values', function () {
            $titleStatusChange = new TitleStatusChange();

            // Model has no custom default values
            expect($titleStatusChange)->toBeInstanceOf(TitleStatusChange::class);
        });

        test('has custom eloquent builder', function () {
            $titleStatusChange = new TitleStatusChange();

            // Model has no custom builder
            expect($titleStatusChange->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('extends Model class', function () {
            $titleStatusChange = new TitleStatusChange();
            expect($titleStatusChange)->toBeInstanceOf(Model::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(TitleStatusChange::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(TitleStatusChange::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TitleStatusChange::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has title relationship method', function () {
            $titleStatusChange = new TitleStatusChange();

            expect(method_exists($titleStatusChange, 'title'))->toBeTrue();
        });
    });
});
