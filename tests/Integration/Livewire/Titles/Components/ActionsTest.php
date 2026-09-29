<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Livewire\Titles\Components\Actions;
use App\Models\Titles\Title;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Matching\Argument;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('title actions component', function (): void {
    test('it renders with the title mounted', function (): void {
        // Arrange
        $title = Title::factory()->create(['name' => 'Test Championship Title']);

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['title' => $title]);

        // Assert
        $component
            ->assertOk()
            ->assertViewIs('livewire.titles.components.actions');
        expect($component->get('title'))->toEqual($title);
    });

    test('it delegates lifecycle actions and dispatches title feedback', function (
        string $method,
        string $actionClass,
        DoubleInterface $action,
        string $message,
    ): void {
        // Arrange
        $title = Title::factory()->create();
        $action->expects('handle')->with(
            Argument::satisfies(fn (mixed $actual): bool => $actual instanceof Title && $actual->is($title)),
        );
        app()->instance($actionClass, $action);

        actingAs(administrator());
        $component = livewire(Actions::class, ['title' => $title]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertDispatched('title-updated')
            ->assertDispatched('flash-message', type: 'status', message: $message);
        $action->verify();
    })->with([
        'debut' => ['debut', DebutAction::class, Double::for(DebutAction::class), 'Title successfully debuted.'],
        'retire' => ['retire', RetireAction::class, Double::for(RetireAction::class), 'Title successfully retired.'],
        'unretire' => ['unretire', UnretireAction::class, Double::for(UnretireAction::class), 'Title successfully unretired.'],
        'deactivate' => ['deactivate', PullAction::class, Double::for(PullAction::class), 'Title successfully pulled.'],
        'reinstate' => ['reinstate', ReinstateAction::class, Double::for(ReinstateAction::class), 'Title successfully reinstated.'],
        'restore' => ['restore', RestoreAction::class, Double::for(RestoreAction::class), 'Title successfully restored.'],
    ]);

    test('it forbids lifecycle actions for unauthorized users without success feedback', function (string $method): void {
        // Arrange
        $title = Title::factory()->undebuted()->create();

        actingAs(basicUser());
        $component = livewire(Actions::class, ['title' => $title]);

        // Act
        $component->call($method);

        // Assert
        $component->assertForbidden();
        $component
            ->assertNotDispatched('title-updated')
            ->assertNotDispatched('flash-message');
        expect(session()->has('status'))->toBeFalse()
            ->and($title->currentActivityPeriod()->exists())->toBeFalse();
    })->with([
        'debut',
        'retire',
        'unretire',
        'deactivate',
        'reinstate',
        'restore',
    ]);

    test('it only shows the lifecycle buttons that fit the title state', function (
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $title = Title::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['title' => $title]);

        // Assert
        foreach ($visible as $method) {
            $component->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $component->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with([
        'undebuted' => ['undebuted', ['debut'], ['retire', 'unretire', 'deactivate', 'reinstate', 'restore']],
        'active' => ['active', ['retire', 'deactivate'], ['debut', 'unretire', 'reinstate', 'restore']],
        'inactive' => ['inactive', ['retire', 'reinstate'], ['debut', 'unretire', 'deactivate', 'restore']],
        'retired' => ['retired', ['unretire'], ['debut', 'retire', 'deactivate', 'reinstate', 'restore']],
    ]);

    test('it shows the buttons for the new state after a lifecycle action succeeds', function (): void {
        // Arrange
        $title = Title::factory()->undebuted()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['title' => $title]);

        // Act
        $component->call('debut');

        // Assert
        $component
            ->assertDispatched('title-updated')
            ->assertSeeHtml('wire:click="deactivate"')
            ->assertDontSeeHtml('wire:click="debut"');
    });

    test('it hides eligible lifecycle actions from users who are not authorized', function (): void {
        // Arrange
        $title = Title::factory()->undebuted()->create();

        actingAs(basicUser());

        // Act
        $component = livewire(Actions::class, ['title' => $title]);

        // Assert
        expect($component->instance()->canPerform(TitleLifecycleTransition::Debut))->toBeFalse();
        $component->assertDontSeeHtml('wire:click="debut"');
    });
});
