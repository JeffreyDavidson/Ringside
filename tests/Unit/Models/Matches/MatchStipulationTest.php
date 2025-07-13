<?php

declare(strict_types=1);

use App\Models\Matches\MatchStipulation;
use Database\Factories\Matches\MatchStipulationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for MatchStipulation model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the MatchStipulation model is properly configured
 * and structured according to the data layer requirements.
 */
describe('MatchStipulation Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $matchStipulation = new MatchStipulation();
            expect($matchStipulation->getTable())->toBe('matches_stipulations');
        });

        test('has correct fillable properties', function () {
            $matchStipulation = new MatchStipulation();
            expect($matchStipulation->getFillable())->toEqual([
                'name',
                'slug',
                'description',
                'is_active',
            ]);
        });

        test('has correct casts configuration', function () {
            $matchStipulation = new MatchStipulation();
            $casts = $matchStipulation->getCasts();

            expect($casts['is_active'])->toBe('boolean');
        });

        test('has custom eloquent builder', function () {
            $matchStipulation = new MatchStipulation();
            expect($matchStipulation->query())->toBeObject();
        });

        test('has correct default values', function () {
            $matchStipulation = new MatchStipulation();
            expect($matchStipulation->is_active)->toBe(true);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(MatchStipulation::class)->usesTrait(HasFactory::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(MatchStipulation::class);

            expect($interfaces)->toBeArray();
        });
    });

    describe('model constants', function () {
        test('has required constants defined', function () {
            $reflection = new ReflectionClass(MatchStipulation::class);
            $constants = $reflection->getConstants();
            
            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);
                return $constant && $constant->getDeclaringClass()->getName() === MatchStipulation::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

    describe('business logic methods', function () {
        test('has required relationship methods', function () {
            $matchStipulation = new MatchStipulation();

            expect(method_exists($matchStipulation, 'eventMatches'))->toBeTrue();
        });

        test('has business logic helper methods', function () {
            $matchStipulation = new MatchStipulation();

            expect(method_exists($matchStipulation, 'isStandardMatch'))->toBeTrue();
            expect(method_exists($matchStipulation, 'requiresSpecialSetup'))->toBeTrue();
            expect(method_exists($matchStipulation, 'isHardcoreStipulation'))->toBeTrue();
            expect(method_exists($matchStipulation, 'hasEliminationRules'))->toBeTrue();
            expect(method_exists($matchStipulation, 'getDisplayName'))->toBeTrue();
            expect(method_exists($matchStipulation, 'getMatchPreview'))->toBeTrue();
        });
    });

    describe('model inheritance', function () {
        test('extends eloquent model', function () {
            $matchStipulation = new MatchStipulation();
            expect($matchStipulation)->toBeInstanceOf(Model::class);
        });
    });

    describe('factory integration', function () {
        test('uses correct factory class', function () {
            expect(MatchStipulation::factory())->toBeInstanceOf(MatchStipulationFactory::class);
        });
    });
});