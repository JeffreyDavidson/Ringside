<?php

declare(strict_types=1);

use App\Actions\Managers\ClearFromInjuryAction;
use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\RestoreAction;
use App\Actions\Managers\RetireAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Managers\Components\Actions;
use App\Models\Roster\Managers\Manager;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Matching\Argument;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('manager actions component', function (): void {
    test('it renders with the manager mounted', function (): void {
        // Arrange
        $manager = Manager::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Manager',
        ]);

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Assert
        $component->assertOk();
        expect($component->get('manager'))->toEqual($manager);
    });

    test('it delegates lifecycle actions and dispatches manager feedback', function (
        string $method,
        string $actionClass,
        DoubleInterface $action,
        string $message,
    ): void {
        // Arrange
        $manager = Manager::factory()->create();
        $action->expects('handle')->with(
            Argument::satisfies(fn (mixed $actual): bool => $actual instanceof Manager && $actual->is($manager)),
        );
        app()->instance($actionClass, $action);

        actingAs(administrator());
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertDispatched('manager-updated')
            ->assertDispatched('flash-message', type: 'status', message: $message);
        $action->verify();
    })->with([
        'employ' => ['employ', EmployAction::class, Double::for(EmployAction::class), 'Manager has been hired.'],
        'release' => ['release', ReleaseAction::class, Double::for(ReleaseAction::class), 'Manager contract has been terminated.'],
        'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Manager has been retired.'],
        'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Manager has been brought out of retirement.'],
        'suspend' => ['suspend', SuspendAction::class, Double::for(SuspendAction::class), 'Manager has been suspended.'],
        'injure' => ['injure', InjureAction::class, Double::for(InjureAction::class), 'Manager injury has been recorded.'],
        'clear from injury' => ['clearFromInjury', ClearFromInjuryAction::class, Double::for(ClearFromInjuryAction::class), 'Manager has been cleared from injury.'],
        'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Manager has been restored.'],
    ]);

    test('it reinstates a suspended manager and dispatches feedback', function (): void {
        // Arrange
        $manager = Manager::factory()->suspended()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Act
        $component->call('reinstate');

        // Assert
        $component
            ->assertDispatched('manager-updated')
            ->assertDispatched('flash-message', type: 'status', message: 'Manager has been reinstated.');
        expect($manager->currentSuspension()->exists())->toBeFalse();
    });

    test('it forbids lifecycle actions for unauthorized users without success feedback', function (string $method): void {
        // Arrange
        $manager = Manager::factory()->unemployed()->create();

        actingAs(basicUser());
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Act
        $component->call($method);

        // Assert
        $component->assertForbidden();
        $component
            ->assertNotDispatched('manager-updated')
            ->assertNotDispatched('flash-message');
        expect(session()->has('status'))->toBeFalse()
            ->and($manager->currentEmployment()->exists())->toBeFalse();
    })->with([
        'employ',
        'release',
        'retire',
        'unretire',
        'suspend',
        'reinstate',
        'injure',
        'clearFromInjury',
        'restore',
    ]);

    test('it only shows the lifecycle buttons that fit the manager state', function (
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $manager = Manager::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Assert
        foreach ($visible as $method) {
            $component->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $component->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with([
        'unemployed' => ['unemployed', ['employ'], ['release', 'suspend', 'reinstate', 'injure', 'clearFromInjury', 'retire', 'unretire', 'restore']],
        'employed' => ['employed', ['release', 'suspend', 'injure', 'retire'], ['employ', 'reinstate', 'clearFromInjury', 'unretire', 'restore']],
        'suspended' => ['suspended', ['release', 'reinstate', 'retire'], ['employ', 'suspend', 'injure', 'clearFromInjury', 'unretire', 'restore']],
        'injured' => ['injured', ['release', 'clearFromInjury', 'retire'], ['employ', 'suspend', 'reinstate', 'injure', 'unretire', 'restore']],
        'retired' => ['retired', ['unretire'], ['employ', 'release', 'suspend', 'reinstate', 'injure', 'clearFromInjury', 'retire', 'restore']],
    ]);

    test('it shows the buttons for the new state after a lifecycle action succeeds', function (): void {
        // Arrange
        $manager = Manager::factory()->unemployed()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Act
        $component->call('employ');

        // Assert
        $component
            ->assertDispatched('manager-updated')
            ->assertSeeHtml('wire:click="retire"')
            ->assertDontSeeHtml('wire:click="employ"');

        // Act
        $component->call('retire');

        // Assert
        $component
            ->assertSeeHtml('wire:click="unretire"')
            ->assertDontSeeHtml('wire:click="retire"');
    });

    test('it hides eligible lifecycle actions from users who are not authorized', function (): void {
        // Arrange
        $manager = Manager::factory()->unemployed()->create();

        actingAs(basicUser());

        // Act
        $component = livewire(Actions::class, ['manager' => $manager]);

        // Assert
        expect($component->instance()->canPerform(RosterLifecycleAction::Employ))->toBeFalse();
        $component->assertDontSeeHtml('wire:click="employ"');
    });
});
