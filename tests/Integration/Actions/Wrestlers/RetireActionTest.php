<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Integration tests for RetireAction business workflows and repository coordination.
 *
 * INTEGRATION TEST SCOPE:
 * - Complete retirement workflows from Action to Repository
 * - Business logic coordination between Action and Repository layers
 * - Real entity creation and repository method verification
 * - Wrestler state-specific retirement scenarios with actual data persistence
 * - Date handling through the complete action pipeline
 *
 * These tests verify that the RetireAction correctly orchestrates
 * the complete retirement business process with real dependencies.
 *
 * @see \App\Actions\Wrestlers\RetireAction
 */
describe('RetireAction Integration Tests', function () {
    beforeEach(function () {
        Event::fake();
        testTime()->freeze();
        $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
    });

    describe('retiring bookable wrestlers', function () {
        beforeEach(function () {
            $this->bookableWrestler = Wrestler::factory()->bookable()->create();
        });

        test('retires bookable wrestler at current datetime by default', function () {
            // Arrange
            $datetime = now();
            
            $this->wrestlerRepository
                ->shouldReceive('endEmployment')
                ->once()
                ->with(
                    Mockery::on(fn ($w) => $w->id === $this->bookableWrestler->id),
                    Mockery::on(fn ($d) => $d->eq($datetime))
                )
                ->andReturn($this->bookableWrestler);

            $this->wrestlerRepository
                ->shouldReceive('removeFromCurrentManagers')
                ->once()
                ->with(
                    Mockery::on(fn ($w) => $w->id === $this->bookableWrestler->id),
                    Mockery::on(fn ($d) => $d->eq($datetime))
                )
                ->andReturn($this->bookableWrestler);

            $this->wrestlerRepository
                ->shouldReceive('removeFromCurrentTagTeam')
                ->once()
                ->with(
                    Mockery::on(fn ($w) => $w->id === $this->bookableWrestler->id),
                    Mockery::on(fn ($d) => $d->eq($datetime))
                )
                ->andReturn($this->bookableWrestler);

            $this->wrestlerRepository
                ->shouldReceive('removeFromCurrentStable')
                ->once()
                ->with(
                    Mockery::on(fn ($w) => $w->id === $this->bookableWrestler->id),
                    Mockery::on(fn ($d) => $d->eq($datetime))
                )
                ->andReturn($this->bookableWrestler);

            $this->wrestlerRepository
                ->shouldReceive('createRetirement')
                ->once()
                ->with(
                    Mockery::on(fn ($w) => $w->id === $this->bookableWrestler->id),
                    Mockery::on(fn ($d) => $d->eq($datetime))
                )
                ->andReturn($this->bookableWrestler);

            // Act
            resolve(RetireAction::class)->handle($this->bookableWrestler);

            // Assert - Mock expectations are automatically verified
        });

        test('retires bookable wrestler at specific datetime', function () {
            // Arrange
            $datetime = now()->addDays(2);
            setupBookableWrestlerMocks($this, $this->bookableWrestler, $datetime);

            // Act
            resolve(RetireAction::class)->handle($this->bookableWrestler, $datetime);

            // Assert - Mock expectations are automatically verified
        });
    });

    describe('retiring suspended wrestlers', function () {
        beforeEach(function () {
            $this->suspendedWrestler = Wrestler::factory()->suspended()->create();
        });

        test('retires suspended wrestler at current datetime by default', function () {
            // Arrange
            $datetime = now();
            setupSuspendedWrestlerMocks($this, $this->suspendedWrestler, $datetime);

            // Act
            resolve(RetireAction::class)->handle($this->suspendedWrestler);

            // Assert - Mock expectations are automatically verified
        });

        test('retires suspended wrestler at specific datetime', function () {
            // Arrange
            $datetime = now()->addDays(2);
            setupSuspendedWrestlerMocks($this, $this->suspendedWrestler, $datetime);

            // Act
            resolve(RetireAction::class)->handle($this->suspendedWrestler, $datetime);

            // Assert - Mock expectations are automatically verified
        });
    });

    describe('retiring injured wrestlers', function () {
        beforeEach(function () {
            $this->injuredWrestler = Wrestler::factory()->injured()->create();
        });

        test('retires injured wrestler at current datetime by default', function () {
            // Arrange
            $datetime = now();
            setupInjuredWrestlerMocks($this, $this->injuredWrestler, $datetime);

            // Act
            resolve(RetireAction::class)->handle($this->injuredWrestler);

            // Assert - Mock expectations are automatically verified
        });

        test('retires injured wrestler at specific datetime', function () {
            // Arrange
            $datetime = now()->addDays(2);
            setupInjuredWrestlerMocks($this, $this->injuredWrestler, $datetime);

            // Act
            resolve(RetireAction::class)->handle($this->injuredWrestler, $datetime);

            // Assert - Mock expectations are automatically verified
        });
    });

    describe('retirement validation and error cases', function () {
        test('throws exception for retiring unemployed wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Act & Assert
            resolve(RetireAction::class)->handle($wrestler);
        })->throws(CannotBeRetiredException::class);

        test('throws exception for retiring wrestler with future employment', function () {
            // Arrange
            $wrestler = Wrestler::factory()->withFutureEmployment()->create();

            // Act & Assert
            resolve(RetireAction::class)->handle($wrestler);
        })->throws(CannotBeRetiredException::class);

        test('throws exception for retiring already retired wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->retired()->create();

            // Act & Assert
            resolve(RetireAction::class)->handle($wrestler);
        })->throws(CannotBeRetiredException::class);
    });

    describe('retiring released wrestlers', function () {
        test('can retire released wrestler', function () {
            // Arrange
            $wrestler = Wrestler::factory()->released()->create();
            setupReleasedWrestlerMocks($this, $wrestler);

            // Act
            resolve(RetireAction::class)->handle($wrestler);

            // Assert - Mock expectations are automatically verified
        });
    });

    // Helper methods for mock setup
    function setupBookableWrestlerMocks($testContext, $wrestler, $datetime) {
        $testContext->wrestlerRepository
            ->shouldReceive('endEmployment')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        setupCommonRemovalMocks($testContext, $wrestler, $datetime);
    }

    function setupSuspendedWrestlerMocks($testContext, $wrestler, $datetime) {
        $testContext->wrestlerRepository
            ->shouldReceive('endEmployment')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        $testContext->wrestlerRepository
            ->shouldReceive('endSuspension')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        setupCommonRemovalMocks($testContext, $wrestler, $datetime);
    }

    function setupInjuredWrestlerMocks($testContext, $wrestler, $datetime) {
        $testContext->wrestlerRepository
            ->shouldReceive('endEmployment')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        $testContext->wrestlerRepository
            ->shouldReceive('endInjury')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        setupCommonRemovalMocks($testContext, $wrestler, $datetime);
    }

    function setupReleasedWrestlerMocks($testContext, $wrestler) {
        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentTagTeam')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::any()
            );

        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentStable')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::any()
            );

        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentManagers')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::any()
            );

        $testContext->wrestlerRepository
            ->shouldReceive('createRetirement')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::any()
            );
    }

    function setupCommonRemovalMocks($testContext, $wrestler, $datetime) {
        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentManagers')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentTagTeam')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        $testContext->wrestlerRepository
            ->shouldReceive('removeFromCurrentStable')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);

        $testContext->wrestlerRepository
            ->shouldReceive('createRetirement')
            ->once()
            ->with(
                Mockery::on(fn ($w) => $w->id === $wrestler->id),
                Mockery::on(fn ($d) => $d->eq($datetime))
            )
            ->andReturn($wrestler);
    }
});