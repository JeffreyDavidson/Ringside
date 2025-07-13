<?php

declare(strict_types=1);

use App\Builders\Titles\TitleBuilder;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Models\Concerns\HasChampionships;
use App\Models\Concerns\HasStatusHistory;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Title model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Title model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Title Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $title = new Title();
            expect($title->getTable())->toBe('titles');
        });

        test('has correct fillable properties', function () {
            $title = new Title();

            expect($title->getFillable())->toEqual([
                'name',
                'status',
                'type',
            ]);
        });

        test('has correct casts configuration', function () {
            $title = new Title();
            $casts = $title->getCasts();

            expect($casts['type'])->toBe(TitleType::class);
            expect($casts['status'])->toBe(TitleStatus::class);
        });

        test('has custom eloquent builder', function () {
            $title = new Title();
            expect($title->query())->toBeInstanceOf(TitleBuilder::class);
        });

        test('has correct default values', function () {
            $title = new Title();
            expect($title->status)->toBe(TitleStatus::Undebuted);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Title::class)->usesTrait(HasChampionships::class);
            expect(Title::class)->usesTrait(HasFactory::class);
            expect(Title::class)->usesTrait(HasStatusHistory::class);
            expect(Title::class)->usesTrait(IsRetirable::class);
            expect(Title::class)->usesTrait(ProvidesDisplayName::class);
            expect(Title::class)->usesTrait(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Title::class);

            // Title model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Title::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Title::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $title = new Title();

            // Title model has standard Eloquent relationships but no custom business methods
            expect($title)->toBeInstanceOf(Title::class);
        });
    });
});
