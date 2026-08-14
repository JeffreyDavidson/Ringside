<?php

declare(strict_types=1);

use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\TagTeamBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Builders\Titles\TitleBuilder;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unit tests for Builder Concerns/Traits.
 *
 * UNIT TEST SCOPE:
 * - Trait integration with concrete builders
 * - Query building logic and SQL generation
 * - Method chaining and fluent interface patterns
 *
 * These tests verify that Builder concerns/traits provide consistent
 * shared public query functionality across different builder implementations.
 *
 * @see HasRetirementScopes
 */
describe('Builder Concerns Unit Tests', function () {
    describe('HasRetirementScopes trait functionality', function () {
        test('trait is used by builders or base classes', function () {
            // Act & Assert - Verify trait usage (directly or through inheritance)
            expect(class_uses(IndividualBuilder::class))->toContain(HasRetirementScopes::class);
            expect(class_uses(TagTeamBuilder::class))->toContain(HasRetirementScopes::class);
            expect(class_uses(TitleBuilder::class))->toContain(HasRetirementScopes::class);

        });

        test('retired method generates correct query conditions', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act
            $retiredBuilder = $builder->retired();

            // Assert
            $sql = $retiredBuilder->toSql();
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('retirements');
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
                ['builder' => Wrestler::query(), 'table' => 'retirements'],
                ['builder' => TagTeam::query(), 'table' => 'retirements'],
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
            expect($sql)->toContain('retirements');
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
            }
        });

        test('trait methods maintain public visibility', function () {
            // Arrange
            $builder = Wrestler::query();
            $reflection = new ReflectionClass($builder);

            $method = $reflection->getMethod('retired');

            expect($method->isPublic())->toBeTrue();
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
