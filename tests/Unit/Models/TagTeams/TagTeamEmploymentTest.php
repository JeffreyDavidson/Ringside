<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeamEmployment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for TagTeamEmployment model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TagTeamEmployment model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TagTeamEmployment Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            expect($tagTeamEmployment->getFillable())->toEqual([
                'tag_team_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $tagTeamEmployment = new TagTeamEmployment();
            $casts = $tagTeamEmployment->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            expect($tagTeamEmployment->getTable())->toBe('tag_teams_employments');
        });

        test('has correct default values', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            // Model has no custom default values
            expect($tagTeamEmployment)->toBeInstanceOf(TagTeamEmployment::class);
        });

        test('has custom eloquent builder', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            // Model has no custom builder
            expect($tagTeamEmployment->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(TagTeamEmployment::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(TagTeamEmployment::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(TagTeamEmployment::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TagTeamEmployment::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has tagTeam relationship method', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            expect(method_exists($tagTeamEmployment, 'tagTeam'))->toBeTrue();
        });

        test('has startedBefore business method', function () {
            $tagTeamEmployment = new TagTeamEmployment();

            expect(method_exists($tagTeamEmployment, 'startedBefore'))->toBeTrue();
        });
    });
});
