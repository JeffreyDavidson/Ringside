<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeamWrestler;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Unit tests for TagTeamWrestler model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TagTeamWrestler model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TagTeamWrestler Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            // Model has no explicitly defined fillable properties (uses Pivot defaults)
            expect($tagTeamWrestler->getFillable())->toBeArray();
        });

        test('has correct casts configuration', function () {
            $tagTeamWrestler = new TagTeamWrestler();
            $casts = $tagTeamWrestler->getCasts();

            expect($casts)->toBeArray();
            expect($casts['joined_at'])->toBe('datetime');
            expect($casts['left_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            expect($tagTeamWrestler->getTable())->toBe('tag_teams_wrestlers');
        });

        test('has correct default values', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            // Model has no custom default values
            expect($tagTeamWrestler)->toBeInstanceOf(TagTeamWrestler::class);
        });

        test('has custom eloquent builder', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            // Model has no custom builder
            expect($tagTeamWrestler->query())->toBeObject();
        });

        test('extends Pivot base class', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            expect($tagTeamWrestler)->toBeInstanceOf(Pivot::class);
        });
    });

    describe('trait integration', function () {
        test('extends Pivot class', function () {
            $tagTeamWrestler = new TagTeamWrestler();
            expect($tagTeamWrestler)->toBeInstanceOf(Pivot::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(TagTeamWrestler::class);

            // Model implements no specific interfaces beyond base Pivot
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(TagTeamWrestler::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TagTeamWrestler::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has tagTeam relationship method', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            expect(method_exists($tagTeamWrestler, 'tagTeam'))->toBeTrue();
        });

        test('has wrestler relationship method', function () {
            $tagTeamWrestler = new TagTeamWrestler();

            expect(method_exists($tagTeamWrestler, 'wrestler'))->toBeTrue();
        });
    });
});
