<?php

declare(strict_types=1);

use App\Actions\Referees\ClearFromInjuryAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReinstateAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RestoreAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Enums\Roster\RosterLifecycleAction;
use App\Livewire\Referees\Components\Actions;
use App\Models\Roster\Referees\Referee;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Matching\Argument;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('referee actions component', function (): void {
    test('it renders with the referee mounted', function (): void {
        // Arrange
        $referee = Referee::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Referee',
        ]);

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['referee' => $referee]);

        // Assert
        $component->assertOk();
        expect($component->get('referee'))->toEqual($referee);
    });

    test('it delegates lifecycle actions and dispatches referee feedback', function (
        string $method,
        string $actionClass,
        DoubleInterface $action,
        string $message,
    ): void {
        // Arrange
        $referee = Referee::factory()->create();
        $action->expects('handle')->with(
            Argument::satisfies(fn (mixed $actual): bool => $actual instanceof Referee && $actual->is($referee)),
        );
        app()->instance($actionClass, $action);

        actingAs(administrator());
        $component = livewire(Actions::class, ['referee' => $referee]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertDispatched('referee-updated')
            ->assertDispatched('flash-message', type: 'status', message: $message);
        $action->verify();
    })->with([
        'employ' => ['employ', EmployAction::class, Double::for(EmployAction::class), 'Referee has been hired.'],
        'release' => ['release', ReleaseAction::class, Double::for(ReleaseAction::class), 'Contract has been terminated.'],
        'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Referee has been retired.'],
        'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Referee has been brought out of retirement.'],
        'suspend' => ['suspend', SuspendAction::class, Double::for(SuspendAction::class), 'Referee has been suspended.'],
        'reinstate' => ['reinstate', ReinstateAction::class, Double::for(ReinstateAction::class), 'Referee has been reinstated.'],
        'injure' => ['injure', InjureAction::class, Double::for(InjureAction::class), 'Injury has been recorded.'],
        'clear from injury' => ['clearFromInjury', ClearFromInjuryAction::class, Double::for(ClearFromInjuryAction::class), 'Referee has been cleared from injury.'],
        'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Referee has been restored.'],
    ]);

    test('it forbids lifecycle actions for unauthorized users without success feedback', function (string $method): void {
        // Arrange
        $referee = Referee::factory()->unemployed()->create();

        actingAs(basicUser());
        $component = livewire(Actions::class, ['referee' => $referee]);

        // Act
        $component->call($method);

        // Assert
        $component->assertForbidden();
        $component
            ->assertNotDispatched('referee-updated')
            ->assertNotDispatched('flash-message');
        expect(session()->has('status'))->toBeFalse()
            ->and($referee->currentEmployment()->exists())->toBeFalse();
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

    test('it only shows the lifecycle buttons that fit the referee state', function (
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $referee = Referee::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['referee' => $referee]);

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
        $referee = Referee::factory()->unemployed()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['referee' => $referee]);

        // Act
        $component->call('employ');

        // Assert
        $component
            ->assertDispatched('referee-updated')
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
        $referee = Referee::factory()->unemployed()->create();

        actingAs(basicUser());

        // Act
        $component = livewire(Actions::class, ['referee' => $referee]);

        // Assert
        expect($component->instance()->canPerform(RosterLifecycleAction::Employ))->toBeFalse();
        $component->assertDontSeeHtml('wire:click="employ"');
    });
});
