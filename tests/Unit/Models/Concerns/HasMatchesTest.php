<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasMatches;
use ReflectionClass;

/**
 * Unit tests for HasMatches trait.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship method definitions
 * - Trait integration with model functionality
 * - Event match relationship configurations
 *
 * These tests verify that the HasMatches trait correctly provides
 * relationship methods for accessing event matches and related data.
 */
describe('HasMatches Trait Unit Tests', function () {
    describe('trait method definitions', function () {
        test('trait provides event match relationship methods', function () {
            $reflection = new ReflectionClass(HasMatches::class);

            expect($reflection->hasMethod('matches'))->toBeTrue();
            expect($reflection->hasMethod('getMatchesRelation'))->toBeTrue();
        });

        test('matches method is public', function () {
            $reflection = new ReflectionClass(HasMatches::class);

            expect($reflection->getMethod('matches')->isPublic())->toBeTrue();
        });

        test('getMatchesRelation method is protected', function () {
            $reflection = new ReflectionClass(HasMatches::class);

            expect($reflection->getMethod('getMatchesRelation')->isProtected())->toBeTrue();
        });
    });
});
