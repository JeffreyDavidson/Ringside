<?php

declare(strict_types=1);

use App\Builders\Roster\RefereeBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Referees\Referee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Referee model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Referee model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Referee Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $referee = new Referee();
            expect($referee->getTable())->toBe('referees');
        });

        test('has correct fillable properties', function () {
            $referee = new Referee();

            expect($referee->getFillable())->toEqual([
                'first_name',
                'last_name',
            ]);
        });

        test('has correct casts configuration', function () {
            $referee = new Referee();
            $casts = $referee->getCasts();

            expect($casts)->not->toHaveKey('status');
        });

        test('has custom eloquent builder', function () {
            $referee = new Referee();
            expect($referee->query())->toBeInstanceOf(RefereeBuilder::class);
        });

        test('has correct default values', function () {
            $referee = new Referee();
            expect($referee->status)->toBe(EmploymentStatus::Unemployed);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(class_uses(Referee::class))->toContain(HasFactory::class);
            expect(class_uses(Referee::class))->toContain(IsEmployable::class);
            expect(class_uses(Referee::class))->toContain(IsInjurable::class);
            expect(class_uses(Referee::class))->toContain(IsRetirable::class);
            expect(class_uses(Referee::class))->toContain(IsSuspendable::class);
            expect(class_uses(Referee::class))->toContain(ProvidesDisplayName::class);
            expect(class_uses(Referee::class))->toContain(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            expect(Referee::class)->toImplement(Employable::class);
            expect(Referee::class)->toImplement(Injurable::class);
            expect(Referee::class)->toImplement(Retirable::class);
            expect(Referee::class)->toImplement(Suspendable::class);
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Referee::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Referee::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('match relationships', function () {
        test('officiates matches through the referee pivot', function () {
            $referee = new Referee();

            $matches = $referee->matches();
            $previousMatches = $referee->previousMatches();

            expect($matches)
                ->toBeInstanceOf(BelongsToMany::class)
                ->and($matches->getTable())
                ->toBe('events_matches_referees')
                ->and($previousMatches)
                ->toBeInstanceOf(BelongsToMany::class);
        });
    });
});
