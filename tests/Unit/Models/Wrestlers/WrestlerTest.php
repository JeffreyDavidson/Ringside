<?php

declare(strict_types=1);

use App\Builders\Roster\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\CanJoinTagTeams;
use App\Models\Concerns\CanWinTitles;
use App\Models\Concerns\HasEnumStatus;
use App\Models\Concerns\HasMatches;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Wrestler model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 * - Model constants and business methods
 *
 * These tests verify that the Wrestler model is properly configured
 * and structured according to the data layer requirements.
 *
 * @see Wrestler
 */
describe('Wrestler Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $wrestler = new Wrestler();
            expect($wrestler->getTable())->toBe('wrestlers');
        });

        test('has correct fillable properties', function () {
            $wrestler = new Wrestler();

            expect($wrestler->getFillable())->toEqual([
                'name',
                'height',
                'weight',
                'hometown',
                'signature_move',
                'status',
            ]);
        });

        test('has correct casts configuration', function () {
            $wrestler = new Wrestler();
            $casts = $wrestler->getCasts();

            expect($casts['height'])->toBe(HeightCast::class);
            expect($casts['status'])->toBe(EmploymentStatus::class);
        });

        test('has custom eloquent builder', function () {
            $wrestler = new Wrestler();
            expect($wrestler->query())->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('has correct default values', function () {
            $wrestler = new Wrestler();
            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Wrestler::class)->usesTrait(BelongsToUser::class);
            expect(Wrestler::class)->usesTrait(CanBeManaged::class);
            expect(Wrestler::class)->usesTrait(CanJoinStables::class);
            expect(Wrestler::class)->usesTrait(CanJoinTagTeams::class);
            expect(Wrestler::class)->usesTrait(CanWinTitles::class);
            expect(Wrestler::class)->usesTrait(HasEnumStatus::class);
            expect(Wrestler::class)->usesTrait(HasFactory::class);
            expect(Wrestler::class)->usesTrait(HasMatches::class);
            expect(Wrestler::class)->usesTrait(IsEmployable::class);
            expect(Wrestler::class)->usesTrait(IsInjurable::class);
            expect(Wrestler::class)->usesTrait(IsRetirable::class);
            expect(Wrestler::class)->usesTrait(IsSuspendable::class);
            expect(Wrestler::class)->usesTrait(ProvidesDisplayName::class);
            expect(Wrestler::class)->usesTrait(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Wrestler::class);

            expect($interfaces)->toContain('App\Models\Contracts\Bookable');
            expect($interfaces)->toContain('App\Models\Contracts\CanBeAStableMember');
            expect($interfaces)->toContain('App\Models\Contracts\CanBeATagTeamMember');
            expect($interfaces)->toContain('App\Models\Contracts\CanBeChampion');
            expect($interfaces)->toContain('App\Models\Contracts\Employable');
            expect($interfaces)->toContain('App\Models\Contracts\HasDisplayName');
            expect($interfaces)->toContain('App\Models\Contracts\Injurable');
            expect($interfaces)->toContain('App\Models\Contracts\Manageable');
            expect($interfaces)->toContain('App\Models\Contracts\Retirable');
            expect($interfaces)->toContain('App\Models\Contracts\Suspendable');
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Wrestler::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Wrestler::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has isBookable business method', function () {
            $wrestler = new Wrestler();

            expect(method_exists($wrestler, 'isBookable'))->toBeTrue();
        });
    });
});
