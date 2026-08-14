<?php

declare(strict_types=1);

use App\Builders\Roster\TagTeamBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\HasChampionshipReigns;
use App\Models\Concerns\HasManagerAssignments;
use App\Models\Concerns\HasMatchParticipations;
use App\Models\Concerns\HasStableMemberships;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for TagTeam model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the TagTeam model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TagTeam Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $tagTeam = new TagTeam();
            expect($tagTeam->getTable())->toBe('tag_teams');
        });

        test('has correct fillable properties', function () {
            $tagTeam = new TagTeam();

            expect($tagTeam->getFillable())->toEqual([
                'name',
                'signature_move',
            ]);
        });

        test('has correct casts configuration', function () {
            $tagTeam = new TagTeam();
            $casts = $tagTeam->getCasts();

            // Status is computed attribute, no cast needed
            expect(array_key_exists('status', $casts))->toBeFalse();
        });

        test('has custom eloquent builder', function () {
            $tagTeam = new TagTeam();
            expect($tagTeam->query())->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('has correct default values', function () {
            $tagTeam = new TagTeam();

            // Test specific status default value
            expect($tagTeam->status)->toBe(EmploymentStatus::Unemployed);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(class_uses(TagTeam::class))->toContain(HasManagerAssignments::class);
            expect(class_uses(TagTeam::class))->toContain(HasStableMemberships::class);
            expect(class_uses(TagTeam::class))->toContain(HasChampionshipReigns::class);
            expect(class_uses(TagTeam::class))->toContain(HasFactory::class);
            expect(class_uses(TagTeam::class))->toContain(HasMatchParticipations::class);
            expect(class_uses(TagTeam::class))->toContain(IsEmployable::class);
            expect(class_uses(TagTeam::class))->toContain(IsRetirable::class);
            expect(class_uses(TagTeam::class))->toContain(IsSuspendable::class);
            expect(class_uses(TagTeam::class))->toContain(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            expect(TagTeam::class)->toImplement(CanBeAStableMember::class);
            expect(TagTeam::class)->toImplement(CanBeChampion::class);
            expect(TagTeam::class)->toImplement(Employable::class);
            expect(TagTeam::class)->toImplement(Manageable::class);
            expect(TagTeam::class)->toImplement(Retirable::class);
            expect(TagTeam::class)->toImplement(Suspendable::class);
        });
    });

    describe('model constants', function () {
        test('has required constants defined', function () {
            $reflection = new ReflectionClass(TagTeam::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === TagTeam::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toHaveKey('NUMBER_OF_WRESTLERS_ON_TEAM');
            expect($modelConstants['NUMBER_OF_WRESTLERS_ON_TEAM'])->toBe(2);
        });
    });

});
