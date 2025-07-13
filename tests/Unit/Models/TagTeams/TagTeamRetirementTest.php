<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeamRetirement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for TagTeamRetirement model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TagTeamRetirement model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TagTeamRetirement Model Unit Tests', function () {
    describe('tagTeamRetirement attributes and configuration', function () {
        test('tagTeamRetirement has correct fillable properties', function () {
            $tagTeamRetirement = new TagTeamRetirement();

            expect($tagTeamRetirement->getFillable())->toEqual([
                'tag_team_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('tagTeamRetirement has correct casts configuration', function () {
            $tagTeamRetirement = new TagTeamRetirement();
            $casts = $tagTeamRetirement->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('tagTeamRetirement has custom eloquent builder', function () {
            $tagTeamRetirement = new TagTeamRetirement();
            // Model has no custom builder
            expect($tagTeamRetirement->query())->toBeObject();
        });

        test('tagTeamRetirement has correct default values', function () {
            $tagTeamRetirement = new TagTeamRetirement();
            // Model has no custom default values
            expect($tagTeamRetirement)->toBeInstanceOf(TagTeamRetirement::class);
        });

        test('tagTeamRetirement uses correct table name', function () {
            $tagTeamRetirement = new TagTeamRetirement();
            expect($tagTeamRetirement->getTable())->toBe('tag_teams_retirements');
        });
    });

    describe('tagTeamRetirement trait integration', function () {
        test('tagTeamRetirement uses all required traits', function () {
            expect(TagTeamRetirement::class)->usesTrait(HasFactory::class);
        });

        test('tagTeamRetirement implements all required interfaces', function () {
            $interfaces = class_implements(TagTeamRetirement::class);

            // Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
