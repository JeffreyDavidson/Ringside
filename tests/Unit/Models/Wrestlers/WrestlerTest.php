<?php

declare(strict_types=1);

use App\Builders\Roster\WrestlerBuilder;
use App\Casts\HeightCast;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\CanBeManaged;
use App\Models\Concerns\HasChampionshipReigns;
use App\Models\Concerns\HasMatchParticipations;
use App\Models\Concerns\HasStableMemberships;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\CanBeChampion;
use App\Models\Contracts\Employable;
use App\Models\Contracts\HasDisplayName;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Manageable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
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
            ]);
        });

        test('has correct casts configuration', function () {
            $wrestler = new Wrestler();
            $casts = $wrestler->getCasts();

            expect($casts['height'])->toBe(HeightCast::class);
            // Status is computed attribute, no cast needed
            expect(array_key_exists('status', $casts))->toBeFalse();
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
            expect(class_uses(Wrestler::class))->toContain(BelongsToUser::class);
            expect(class_uses(Wrestler::class))->toContain(CanBeManaged::class);
            expect(class_uses(Wrestler::class))->toContain(HasStableMemberships::class);
            expect(class_uses(Wrestler::class))->toContain(HasChampionshipReigns::class);
            expect(class_uses(Wrestler::class))->toContain(HasFactory::class);
            expect(class_uses(Wrestler::class))->toContain(HasMatchParticipations::class);
            expect(class_uses(Wrestler::class))->toContain(IsEmployable::class);
            expect(class_uses(Wrestler::class))->toContain(IsInjurable::class);
            expect(class_uses(Wrestler::class))->toContain(IsRetirable::class);
            expect(class_uses(Wrestler::class))->toContain(IsSuspendable::class);
            expect(class_uses(Wrestler::class))->toContain(ProvidesDisplayName::class);
            expect(class_uses(Wrestler::class))->toContain(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Wrestler::class);

            expect($interfaces)->toContain(CanBeAStableMember::class);
            expect($interfaces)->toContain(CanBeChampion::class);
            expect($interfaces)->toContain(Employable::class);
            expect($interfaces)->toContain(HasDisplayName::class);
            expect($interfaces)->toContain(Injurable::class);
            expect($interfaces)->toContain(Manageable::class);
            expect($interfaces)->toContain(Retirable::class);
            expect($interfaces)->toContain(Suspendable::class);
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

});
