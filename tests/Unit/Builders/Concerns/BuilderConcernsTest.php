<?php

declare(strict_types=1);

use App\Builders\Concerns\HasAvailabilityScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Roster\WrestlerBuilder;
use App\Builders\TagTeamBuilder;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit tests for Builder Concerns/Traits.
 *
 * UNIT TEST SCOPE:
 * - Trait method functionality in isolation
 * - Trait integration with concrete builders
 * - Protected method accessibility and behavior
 * - Query building logic and SQL generation
 * - Method chaining and fluent interface patterns
 *
 * These tests verify that Builder concerns/traits provide consistent
 * shared functionality across different builder implementations.
 * Tests focus on trait functionality rather than business logic outcomes.
 *
 * @see \App\Builders\Concerns\HasAvailabilityScopes
 * @see \App\Builders\Concerns\HasRetirementScopes
 */
describe('Builder Concerns Unit Tests', function () {
    describe('HasAvailabilityScopes trait functionality', function () {
        test('trait is used by builders or base classes', function () {
            // Act & Assert - Verify trait usage (directly or through inheritance)
            // SingleRosterMemberBuilder uses the trait, and other builders inherit from it
            expect(\App\Builders\Roster\SingleRosterMemberBuilder::class)->usesTrait(HasAvailabilityScopes::class);
            expect(\App\Builders\TagTeamBuilder::class)->usesTrait(HasAvailabilityScopes::class);
            
            // Verify that methods are available on concrete builders
            $builder = Wrestler::query();
            $reflection = new \ReflectionClass($builder);
            expect($reflection->hasMethod('whereNotRetired'))->toBeTrue();
            expect($reflection->hasMethod('whereEmployed'))->toBeTrue();
        });

        test('whereNotRetired method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act - Access protected method via reflection for unit testing
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('whereNotRetired');
            $method->setAccessible(true);
            $method->invoke($builder);

            // Assert
            $sql = $builder->toSql();
            expect($sql)->toContain('not exists');
            expect($sql)->toContain('wrestlers_retirements');
            expect($sql)->toContain('ended_at" is null');
        });

        test('whereNotSuspended method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act - Access protected method via reflection
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('whereNotSuspended');
            $method->setAccessible(true);
            $method->invoke($builder);

            // Assert
            $sql = $builder->toSql();
            expect($sql)->toContain('not exists');
            expect($sql)->toContain('wrestlers_suspensions');
            expect($sql)->toContain('ended_at" is null');
        });

        test('whereNotInjured method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act - Access protected method via reflection
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('whereNotInjured');
            $method->setAccessible(true);
            $method->invoke($builder);

            // Assert
            $sql = $builder->toSql();
            expect($sql)->toContain('not exists');
            expect($sql)->toContain('wrestlers_injuries');
            expect($sql)->toContain('ended_at" is null');
        });

        test('whereEmployed method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act - Access protected method via reflection
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('whereEmployed');
            $method->setAccessible(true);
            $method->invoke($builder);

            // Assert
            $sql = $builder->toSql();
            $bindings = $builder->getBindings();
            
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('wrestlers_employments');
            expect($sql)->toContain('started_at" <= ?');
            expect($sql)->toContain('ended_at" is null');
            expect($bindings)->toBeArray()->not->toBeEmpty();
        });

        test('whereBasicUnavailabilityConditions method generates correct OR logic', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act - Access protected method via reflection
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('whereBasicUnavailabilityConditions');
            $method->setAccessible(true);
            $method->invoke($builder);

            // Assert
            $sql = $builder->toSql();
            expect($sql)->toContain('where (');
            expect($sql)->toContain('not exists');
            expect($sql)->toContain(' or ');
            expect($sql)->toContain('wrestlers_employments');
            expect($sql)->toContain('wrestlers_suspensions');
            expect($sql)->toContain('wrestlers_retirements');
        });

        test('trait methods return builder instance for method chaining', function () {
            // Arrange
            $builder = Wrestler::query();
            $reflection = new \ReflectionClass($builder);

            // Act & Assert - Test each protected method returns static
            $methods = ['whereNotRetired', 'whereNotSuspended', 'whereNotInjured', 'whereEmployed', 'whereBasicUnavailabilityConditions'];
            
            foreach ($methods as $methodName) {
                $method = $reflection->getMethod($methodName);
                $method->setAccessible(true);
                $result = $method->invoke($builder);
                
                expect($result)->toBeInstanceOf(WrestlerBuilder::class);
            }
        });

        test('trait methods work with different entity types', function () {
            // Arrange - Test with TagTeam which uses different table names
            $tagTeamBuilder = TagTeam::query();
            $wrestlerBuilder = Wrestler::query();

            // Act - Apply whereNotRetired to both builders
            $reflection1 = new \ReflectionClass($tagTeamBuilder);
            $method1 = $reflection1->getMethod('whereNotRetired');
            $method1->setAccessible(true);
            $method1->invoke($tagTeamBuilder);

            $reflection2 = new \ReflectionClass($wrestlerBuilder);
            $method2 = $reflection2->getMethod('whereNotRetired');
            $method2->setAccessible(true);
            $method2->invoke($wrestlerBuilder);

            // Assert - Different table names but same relationship logic
            $tagTeamSql = $tagTeamBuilder->toSql();
            $wrestlerSql = $wrestlerBuilder->toSql();

            expect($tagTeamSql)->toContain('tag_teams_retirements');
            expect($wrestlerSql)->toContain('wrestlers_retirements');
            expect($tagTeamSql)->toContain('not exists');
            expect($wrestlerSql)->toContain('not exists');
        });
    });

    describe('HasRetirementScopes trait functionality', function () {
        test('trait is used by builders or base classes', function () {
            // Act & Assert - Verify trait usage (directly or through inheritance)
            expect(\App\Builders\Roster\SingleRosterMemberBuilder::class)->usesTrait(HasRetirementScopes::class);
            expect(\App\Builders\TagTeamBuilder::class)->usesTrait(HasRetirementScopes::class);
            expect(\App\Builders\Titles\TitleBuilder::class)->usesTrait(HasRetirementScopes::class);
            
            // Verify that methods are available on concrete builders
            $builder = Wrestler::query();
            expect(method_exists($builder, 'retired'))->toBeTrue();
        });

        test('retired method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act
            $retiredBuilder = $builder->retired();

            // Assert
            $sql = $retiredBuilder->toSql();
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('wrestlers_retirements');
            expect($sql)->toContain('ended_at" is null');
        });

        test('retired method returns correct builder instance', function () {
            // Arrange
            $wrestlerBuilder = Wrestler::query();
            $tagTeamBuilder = TagTeam::query();

            // Act
            $retiredWrestlerBuilder = $wrestlerBuilder->retired();
            $retiredTagTeamBuilder = $tagTeamBuilder->retired();

            // Assert
            expect($retiredWrestlerBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($retiredTagTeamBuilder)->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('retired method works polymorphically across entity types', function () {
            // Arrange
            $builders = [
                ['builder' => Wrestler::query(), 'table' => 'wrestlers_retirements'],
                ['builder' => TagTeam::query(), 'table' => 'tag_teams_retirements'],
            ];

            // Act & Assert
            foreach ($builders as $builderData) {
                $builder = $builderData['builder'];
                $expectedTable = $builderData['table'];
                
                $retiredBuilder = $builder->retired();
                $sql = $retiredBuilder->toSql();
                
                expect($sql)->toContain('where exists');
                expect($sql)->toContain($expectedTable);
                expect($sql)->toContain('ended_at" is null');
            }
        });

        test('trait methods can be chained with other builder methods', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act
            $chainedBuilder = $builder
                ->retired()
                ->where('name', 'like', '%Test%')
                ->orderBy('created_at', 'desc');

            // Assert
            expect($chainedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            
            $sql = $chainedBuilder->toSql();
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('wrestlers_retirements');
            expect($sql)->toContain('"name" like ?');
            expect($sql)->toContain('order by "created_at" desc');
        });
    });

    describe('trait integration and consistency', function () {
        test('traits provide consistent method signatures across builders', function () {
            // Arrange
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                // Test HasRetirementScopes trait methods
                expect(method_exists($builder, 'retired'))->toBeTrue();
                
                // Test HasAvailabilityScopes trait protected methods
                $reflection = new \ReflectionClass($builder);
                expect($reflection->hasMethod('whereNotRetired'))->toBeTrue();
                expect($reflection->hasMethod('whereNotSuspended'))->toBeTrue();
                expect($reflection->hasMethod('whereEmployed'))->toBeTrue();
                expect($reflection->hasMethod('whereBasicUnavailabilityConditions'))->toBeTrue();
                
                $method = $reflection->getMethod('whereNotRetired');
                expect($method->isProtected())->toBeTrue();
            }
        });

        test('trait methods maintain proper visibility levels', function () {
            // Arrange
            $builder = Wrestler::query();
            $reflection = new \ReflectionClass($builder);

            // Act & Assert - Test protected method visibility
            $protectedMethods = [
                'whereNotRetired',
                'whereNotSuspended', 
                'whereNotInjured',
                'whereEmployed',
                'whereBasicUnavailabilityConditions'
            ];

            foreach ($protectedMethods as $methodName) {
                $method = $reflection->getMethod($methodName);
                expect($method->isProtected())->toBeTrue();
                expect($method->isPublic())->toBeFalse();
            }

            // Test public method visibility
            $publicMethods = ['retired'];
            foreach ($publicMethods as $methodName) {
                $method = $reflection->getMethod($methodName);
                expect($method->isPublic())->toBeTrue();
            }
        });

        test('trait methods generate SQL compatible with all supported databases', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act
            $retiredBuilder = $builder->retired();
            
            // Assert - Test that generated SQL uses standard patterns
            $sql = $retiredBuilder->toSql();
            
            // Should use standard SQL patterns that work across databases
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('is null');
            expect($sql)->not->toContain('LIMIT'); // No database-specific syntax
            expect($sql)->not->toContain('ISNULL'); // No MySQL-specific functions
        });
    });
});