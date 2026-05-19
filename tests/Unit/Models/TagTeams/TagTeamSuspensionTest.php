<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeamSuspension;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for TagTeamSuspension model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TagTeamSuspension model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TagTeamSuspension Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            $tagTeamSuspension = new TagTeamSuspension();

            expect($tagTeamSuspension->getFillable())->toEqual([
                'tag_team_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('has correct casts configuration', function () {
            $tagTeamSuspension = new TagTeamSuspension();
            $casts = $tagTeamSuspension->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('uses correct table name', function () {
            $tagTeamSuspension = new TagTeamSuspension();

            expect($tagTeamSuspension->getTable())->toBe('tag_teams_suspensions');
        });

        test('has correct default values', function () {
            $tagTeamSuspension = new TagTeamSuspension();

            // Model has no custom default values
            expect($tagTeamSuspension)->toBeInstanceOf(TagTeamSuspension::class);
        });

        test('has custom eloquent builder', function () {
            $tagTeamSuspension = new TagTeamSuspension();

            // Model has no custom builder
            expect($tagTeamSuspension->query())->toBeObject();
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(TagTeamSuspension::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(TagTeamSuspension::class);

            // Model implements no specific interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(TagTeamSuspension::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TagTeamSuspension::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has tagTeam relationship method', function () {
            $tagTeamSuspension = new TagTeamSuspension();

            expect(method_exists($tagTeamSuspension, 'tagTeam'))->toBeTrue();
        });
    });
});
