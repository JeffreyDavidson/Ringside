<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\DefinesManagedAliases;
use App\Models\Concerns\ManagesEntities;
use ReflectionClass;

/**
 * Unit tests for DefinesManagedAliases trait.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship method definitions
 * - Trait integration with other traits
 * - Trait method existence and accessibility
 *
 * These tests verify that the DefinesManagedAliases trait correctly provides
 * relationship methods for managers to access their managed wrestlers and tag teams.
 */
describe('DefinesManagedAliases Trait Unit Tests', function () {
    describe('trait integration', function () {
        test('trait uses ManagesEntities trait', function () {
            expect(DefinesManagedAliases::class)->usesTrait(ManagesEntities::class);
        });
    });

    describe('trait method definitions', function () {
        test('trait provides wrestler relationship methods', function () {
            $reflection = new ReflectionClass(DefinesManagedAliases::class);

            expect($reflection->hasMethod('wrestlers'))->toBeTrue();
            expect($reflection->hasMethod('currentWrestlers'))->toBeTrue();
            expect($reflection->hasMethod('previousWrestlers'))->toBeTrue();
        });

        test('trait provides tag team relationship methods', function () {
            $reflection = new ReflectionClass(DefinesManagedAliases::class);

            expect($reflection->hasMethod('tagTeams'))->toBeTrue();
            expect($reflection->hasMethod('currentTagTeams'))->toBeTrue();
            expect($reflection->hasMethod('previousTagTeams'))->toBeTrue();
        });

        test('wrestler methods are public', function () {
            $reflection = new ReflectionClass(DefinesManagedAliases::class);

            expect($reflection->getMethod('wrestlers')->isPublic())->toBeTrue();
            expect($reflection->getMethod('currentWrestlers')->isPublic())->toBeTrue();
            expect($reflection->getMethod('previousWrestlers')->isPublic())->toBeTrue();
        });

        test('tag team methods are public', function () {
            $reflection = new ReflectionClass(DefinesManagedAliases::class);

            expect($reflection->getMethod('tagTeams')->isPublic())->toBeTrue();
            expect($reflection->getMethod('currentTagTeams')->isPublic())->toBeTrue();
            expect($reflection->getMethod('previousTagTeams')->isPublic())->toBeTrue();
        });
    });
});
