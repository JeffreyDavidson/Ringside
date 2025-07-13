<?php

declare(strict_types=1);

use App\Builders\Contracts\HasAvailability;
use App\Builders\Contracts\HasBooking;
use App\Builders\Contracts\HasEmployment;
use App\Builders\Contracts\HasRetirement;
use App\Builders\Contracts\HasSuspension;
use App\Builders\Roster\ManagerBuilder;
use App\Builders\Roster\RefereeBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Builders\TagTeamBuilder;
use App\Builders\Titles\TitleBuilder;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Carbon\Carbon;

/**
 * Unit tests for Builder Contract interfaces.
 *
 * UNIT TEST SCOPE:
 * - Contract interface method signatures and return types
 * - Contract implementation verification across all builders
 * - Polymorphic behavior consistency for contract methods
 * - Method chaining functionality for contract methods
 * - Interface compliance validation
 *
 * These tests verify that all builder contracts are properly implemented
 * across different entity types and provide consistent polymorphic behavior.
 * Tests focus on interface compliance rather than business logic details.
 *
 * @see \App\Builders\Contracts\HasAvailability
 * @see \App\Builders\Contracts\HasBooking
 * @see \App\Builders\Contracts\HasEmployment
 * @see \App\Builders\Contracts\HasRetirement
 * @see \App\Builders\Contracts\HasSuspension
 */
describe('Builder Contracts Unit Tests', function () {
    describe('HasAvailability contract implementation', function () {
        test('wrestler builder implements HasAvailability contract', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('tag team builder implements HasAvailability contract', function () {
            // Arrange
            $builder = TagTeam::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('title builder implements HasAvailability contract', function () {
            // Arrange
            $builder = Title::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(TitleBuilder::class);
        });

        test('manager builder implements HasAvailability contract', function () {
            // Arrange
            $builder = Manager::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(ManagerBuilder::class);
        });

        test('referee builder implements HasAvailability contract', function () {
            // Arrange
            $builder = Referee::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(RefereeBuilder::class);
        });

        test('availability contract methods exist and return builder instance', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act & Assert
            expect(method_exists($builder, 'available'))->toBeTrue();
            expect(method_exists($builder, 'unavailable'))->toBeTrue();

            $availableBuilder = $builder->available();
            $unavailableBuilder = $builder->unavailable();

            expect($availableBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($unavailableBuilder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('availability methods work polymorphically across entities', function () {
            // Arrange
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
                Manager::query(),
                Referee::query(),
                Title::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->toBeInstanceOf(HasAvailability::class);
                
                $availableBuilder = $builder->available();
                $unavailableBuilder = $builder->unavailable();
                
                expect($availableBuilder)->toBeInstanceOf(HasAvailability::class);
                expect($unavailableBuilder)->toBeInstanceOf(HasAvailability::class);
            }
        });
    });

    describe('HasBooking contract implementation', function () {
        test('wrestler builder has booking methods but does not implement HasBooking contract', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert - WrestlerBuilder has booking methods but doesn't implement the interface
            expect($builder)->not->toBeInstanceOf(HasBooking::class);
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect(method_exists($builder, 'bookable'))->toBeTrue();
            expect(method_exists($builder, 'availableOn'))->toBeTrue();
            expect(method_exists($builder, 'notBookedOn'))->toBeTrue();
        });

        test('tag team builder implements HasBooking contract', function () {
            // Arrange
            $builder = TagTeam::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasBooking::class);
            expect($builder)->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('booking contract methods exist and return builder instance', function () {
            // Arrange
            $builder = Wrestler::query();
            $testDate = Carbon::parse('2024-12-31');

            // Act & Assert
            expect(method_exists($builder, 'bookable'))->toBeTrue();
            expect(method_exists($builder, 'availableOn'))->toBeTrue();
            expect(method_exists($builder, 'notBookedOn'))->toBeTrue();

            $bookableBuilder = $builder->bookable();
            expect($bookableBuilder)->toBeInstanceOf(WrestlerBuilder::class);

            // Note: availableOn and notBookedOn require database tables for match relationships
            // so we only test method existence for unit test scope
            expect(method_exists($builder, 'availableOn'))->toBeTrue();
            expect(method_exists($builder, 'notBookedOn'))->toBeTrue();
        });

        test('tag team builder implements HasBooking contract fully', function () {
            // Arrange - Only TagTeam builder properly implements HasBooking interface
            $builder = TagTeam::query();

            // Act & Assert
            expect($builder)->toBeInstanceOf(HasBooking::class);
            
            $bookableBuilder = $builder->bookable();
            expect($bookableBuilder)->toBeInstanceOf(HasBooking::class);
        });

        test('most entities do not implement HasBooking contract', function () {
            // Arrange - Most builders don't implement HasBooking interface
            $builders = [
                Wrestler::query(), // Has booking methods but no interface
                Manager::query(),
                Referee::query(),
                Title::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->not->toBeInstanceOf(HasBooking::class);
            }
        });
    });

    describe('HasEmployment contract implementation', function () {
        test('wrestler builder implements HasEmployment contract', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasEmployment::class);
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('tag team builder implements HasEmployment contract', function () {
            // Arrange
            $builder = TagTeam::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasEmployment::class);
            expect($builder)->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('manager builder implements HasEmployment contract', function () {
            // Arrange
            $builder = Manager::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasEmployment::class);
            expect($builder)->toBeInstanceOf(ManagerBuilder::class);
        });

        test('referee builder implements HasEmployment contract', function () {
            // Arrange
            $builder = Referee::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasEmployment::class);
            expect($builder)->toBeInstanceOf(RefereeBuilder::class);
        });

        test('employment contract methods exist and return builder instance', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act & Assert
            expect(method_exists($builder, 'unemployed'))->toBeTrue();
            expect(method_exists($builder, 'employed'))->toBeTrue();
            expect(method_exists($builder, 'released'))->toBeTrue();
            expect(method_exists($builder, 'futureEmployed'))->toBeTrue();

            $unemployedBuilder = $builder->unemployed();
            $employedBuilder = $builder->employed();
            $releasedBuilder = $builder->released();
            $futureEmployedBuilder = $builder->futureEmployed();

            expect($unemployedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($employedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($releasedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($futureEmployedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('employment methods work polymorphically across employable entities', function () {
            // Arrange - Wrestlers, TagTeams, Managers, and Referees can be employed
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
                Manager::query(),
                Referee::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->toBeInstanceOf(HasEmployment::class);
                
                $unemployedBuilder = $builder->unemployed();
                $employedBuilder = $builder->employed();
                $releasedBuilder = $builder->released();
                $futureEmployedBuilder = $builder->futureEmployed();
                
                expect($unemployedBuilder)->toBeInstanceOf(HasEmployment::class);
                expect($employedBuilder)->toBeInstanceOf(HasEmployment::class);
                expect($releasedBuilder)->toBeInstanceOf(HasEmployment::class);
                expect($futureEmployedBuilder)->toBeInstanceOf(HasEmployment::class);
            }
        });

        test('non-employable entities do not implement HasEmployment contract', function () {
            // Arrange - Titles cannot be employed
            $builder = Title::query();

            // Act & Assert
            expect($builder)->not->toBeInstanceOf(HasEmployment::class);
        });
    });

    describe('HasRetirement contract implementation', function () {
        test('most builders implement HasRetirement contract', function () {
            // Arrange - Most entities implement HasRetirement interface
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
                Manager::query(),
                Referee::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->toBeInstanceOf(HasRetirement::class);
            }
        });

        test('title builder has retirement methods but does not implement HasRetirement contract', function () {
            // Arrange
            $builder = Title::query();

            // Assert - TitleBuilder has retirement methods but doesn't implement the interface
            expect($builder)->not->toBeInstanceOf(HasRetirement::class);
            expect($builder)->toBeInstanceOf(TitleBuilder::class);
            expect(method_exists($builder, 'retired'))->toBeTrue();
            expect(method_exists($builder, 'unretired'))->toBeTrue();
        });

        test('retirement contract methods exist and return builder instance', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act & Assert
            expect(method_exists($builder, 'retired'))->toBeTrue();

            $retiredBuilder = $builder->retired();
            expect($retiredBuilder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('retirement methods work polymorphically across contract implementers', function () {
            // Arrange - Only builders that implement HasRetirement interface
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
                Manager::query(),
                Referee::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->toBeInstanceOf(HasRetirement::class);
                
                $retiredBuilder = $builder->retired();
                expect($retiredBuilder)->toBeInstanceOf(HasRetirement::class);
            }
        });
    });

    describe('HasSuspension contract implementation', function () {
        test('wrestler builder implements HasSuspension contract', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasSuspension::class);
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('tag team builder implements HasSuspension contract', function () {
            // Arrange
            $builder = TagTeam::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasSuspension::class);
            expect($builder)->toBeInstanceOf(TagTeamBuilder::class);
        });

        test('manager builder implements HasSuspension contract', function () {
            // Arrange
            $builder = Manager::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasSuspension::class);
            expect($builder)->toBeInstanceOf(ManagerBuilder::class);
        });

        test('referee builder implements HasSuspension contract', function () {
            // Arrange
            $builder = Referee::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasSuspension::class);
            expect($builder)->toBeInstanceOf(RefereeBuilder::class);
        });

        test('suspension contract methods exist and return builder instance', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act & Assert
            expect(method_exists($builder, 'suspended'))->toBeTrue();

            $suspendedBuilder = $builder->suspended();
            expect($suspendedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
        });

        test('suspension methods work polymorphically across suspendable entities', function () {
            // Arrange - Wrestlers, TagTeams, Managers, and Referees can be suspended
            $builders = [
                Wrestler::query(),
                TagTeam::query(),
                Manager::query(),
                Referee::query(),
            ];

            // Act & Assert
            foreach ($builders as $builder) {
                expect($builder)->toBeInstanceOf(HasSuspension::class);
                
                $suspendedBuilder = $builder->suspended();
                expect($suspendedBuilder)->toBeInstanceOf(HasSuspension::class);
            }
        });

        test('non-suspendable entities do not implement HasSuspension contract', function () {
            // Arrange - Titles cannot be suspended
            $builder = Title::query();

            // Act & Assert
            expect($builder)->not->toBeInstanceOf(HasSuspension::class);
        });
    });

    describe('contract method chaining and consistency', function () {
        test('contract methods can be chained together', function () {
            // Arrange
            $builder = Wrestler::query();

            // Act
            $chainedBuilder = $builder
                ->available()
                ->employed()
                ->retired();

            // Assert
            expect($chainedBuilder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($chainedBuilder)->toBeInstanceOf(HasAvailability::class);
            expect($chainedBuilder)->toBeInstanceOf(HasEmployment::class);
            expect($chainedBuilder)->toBeInstanceOf(HasRetirement::class);
        });

        test('contract implementations maintain fluent interface', function () {
            // Arrange - Test builders that properly implement contracts
            $builders = [
                ['builder' => Wrestler::query(), 'class' => WrestlerBuilder::class],
                ['builder' => TagTeam::query(), 'class' => TagTeamBuilder::class],
                ['builder' => Manager::query(), 'class' => ManagerBuilder::class],
                ['builder' => Referee::query(), 'class' => RefereeBuilder::class],
            ];

            // Act & Assert
            foreach ($builders as $builderData) {
                $builder = $builderData['builder'];
                $expectedClass = $builderData['class'];

                // Test that all contract methods return the same builder type
                if ($builder instanceof HasAvailability) {
                    expect($builder->available())->toBeInstanceOf($expectedClass);
                    expect($builder->unavailable())->toBeInstanceOf($expectedClass);
                }

                if ($builder instanceof HasEmployment) {
                    expect($builder->unemployed())->toBeInstanceOf($expectedClass);
                    expect($builder->employed())->toBeInstanceOf($expectedClass);
                    expect($builder->released())->toBeInstanceOf($expectedClass);
                    expect($builder->futureEmployed())->toBeInstanceOf($expectedClass);
                }

                if ($builder instanceof HasRetirement) {
                    expect($builder->retired())->toBeInstanceOf($expectedClass);
                }

                if ($builder instanceof HasSuspension) {
                    expect($builder->suspended())->toBeInstanceOf($expectedClass);
                }

                if ($builder instanceof HasBooking) {
                    expect($builder->bookable())->toBeInstanceOf($expectedClass);
                }
            }
        });

        test('title builder maintains fluent interface despite limited contract implementation', function () {
            // Arrange - TitleBuilder has methods but doesn't implement all contracts
            $builder = Title::query();

            // Act & Assert - Test method chaining works even without formal contracts
            expect($builder->available())->toBeInstanceOf(TitleBuilder::class);
            expect($builder->unavailable())->toBeInstanceOf(TitleBuilder::class);
            expect($builder->retired())->toBeInstanceOf(TitleBuilder::class);
            expect($builder->unretired())->toBeInstanceOf(TitleBuilder::class);
        });
    });
});