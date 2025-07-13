<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Livewire\Wrestlers\Components\WrestlerActionsComponent;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/**
 * Integration tests for WrestlerActionsComponent business actions and authorization.
 *
 * INTEGRATION TEST SCOPE:
 * - Business action integration with Action classes
 * - Authorization integration with Gate facade
 * - Component state management and event dispatching
 * - Business rule enforcement through actions
 * - Error handling and exception management
 * - Wrestler entity lifecycle management
 *
 * These tests verify that the WrestlerActionsComponent correctly implements
 * wrestler business operations with proper authorization and state management.
 *
 * @see WrestlerActionsComponent
 */
describe('WrestlerActionsComponent Integration Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        
        // Create test wrestlers in various states
        $this->availableWrestler = Wrestler::factory()->create([
            'name' => 'Available Wrestler',
        ]);
        
        $this->employedWrestler = Wrestler::factory()->employed()->create([
            'name' => 'Employed Wrestler',
        ]);
        
        $this->suspendedWrestler = Wrestler::factory()->suspended()->create([
            'name' => 'Suspended Wrestler',
        ]);
        
        $this->injuredWrestler = Wrestler::factory()->injured()->create([
            'name' => 'Injured Wrestler',
        ]);
        
        $this->retiredWrestler = Wrestler::factory()->retired()->create([
            'name' => 'Retired Wrestler',
        ]);
    });

    describe('component initialization and authorization', function () {
        test('renders successfully with wrestler entity', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->assertOk();
        });

        test('basic users cannot access wrestler actions', function () {
            $component = Livewire::actingAs($this->basicUser)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->assertForbidden();
        });

        test('guests cannot access wrestler actions', function () {
            $component = Livewire::test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->assertForbidden();
        });
    });

    describe('employment business actions', function () {
        test('can employ available wrestler', function () {
            // Mock the action
            $mockAction = mock(EmployAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->availableWrestler, \Mockery::type('string'));
            app()->instance(EmployAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('employ', $this->availableWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->call('employ', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });

        test('can release employed wrestler', function () {
            // Mock the action
            $mockAction = mock(ReleaseAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->employedWrestler, \Mockery::type('string'));
            app()->instance(ReleaseAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('release', $this->employedWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->employedWrestler]);

            $component->call('release', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });
    });

    describe('suspension business actions', function () {
        test('can suspend employed wrestler', function () {
            // Mock the action
            $mockAction = mock(SuspendAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->employedWrestler, \Mockery::type('string'));
            app()->instance(SuspendAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('suspend', $this->employedWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->employedWrestler]);

            $component->call('suspend', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });

        test('can reinstate suspended wrestler', function () {
            // Mock the action
            $mockAction = mock(ReinstateAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->suspendedWrestler, \Mockery::type('string'));
            app()->instance(ReinstateAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('reinstate', $this->suspendedWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->suspendedWrestler]);

            $component->call('reinstate', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });
    });

    describe('injury business actions', function () {
        test('can injure employed wrestler', function () {
            // Mock the action
            $mockAction = mock(InjureAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->employedWrestler, \Mockery::type('string'));
            app()->instance(InjureAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('injure', $this->employedWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->employedWrestler]);

            $component->call('injure', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });

        test('can clear injury from injured wrestler', function () {
            // Mock the action
            $mockAction = mock(ClearInjuryAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->injuredWrestler, \Mockery::type('string'));
            app()->instance(ClearInjuryAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('clearFromInjury', $this->injuredWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->injuredWrestler]);

            $component->call('clearFromInjury', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });
    });

    describe('retirement business actions', function () {
        test('can retire wrestler', function () {
            // Mock the action
            $mockAction = mock(RetireAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->availableWrestler, \Mockery::type('string'));
            app()->instance(RetireAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('retire', $this->availableWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->call('retire', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });

        test('can unretire retired wrestler', function () {
            // Mock the action
            $mockAction = mock(UnretireAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->retiredWrestler, \Mockery::type('string'));
            app()->instance(UnretireAction::class, $mockAction);

            // Mock authorization
            Gate::shouldReceive('authorize')
                ->once()
                ->with('unretire', $this->retiredWrestler);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->retiredWrestler]);

            $component->call('unretire', now()->format('Y-m-d'))
                ->assertDispatched('wrestler-updated');
        });
    });

    describe('authorization method integration', function () {
        test('canEmploy method integrates with Gate authorization', function () {
            Gate::shouldReceive('check')
                ->once()
                ->with('employ', $this->availableWrestler)
                ->andReturn(true);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            expect($component->instance()->canEmploy())->toBeTrue();
        });

        test('canRelease method integrates with Gate authorization', function () {
            Gate::shouldReceive('check')
                ->once()
                ->with('release', $this->employedWrestler)
                ->andReturn(true);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->employedWrestler]);

            expect($component->instance()->canRelease())->toBeTrue();
        });

        test('canRetire method integrates with Gate authorization', function () {
            Gate::shouldReceive('check')
                ->once()
                ->with('retire', $this->availableWrestler)
                ->andReturn(true);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            expect($component->instance()->canRetire())->toBeTrue();
        });
    });

    describe('event dispatching and component communication', function () {
        test('all action methods dispatch wrestler-updated event', function () {
            $actionMethods = [
                'employ', 'release', 'suspend', 'reinstate', 
                'injure', 'clearFromInjury', 'retire', 'unretire'
            ];

            foreach ($actionMethods as $method) {
                // Mock authorization and actions for each method
                Gate::shouldReceive('authorize')->once();
                $mockAction = mock();
                $mockAction->shouldReceive('handle')->once();
                
                // Map method to appropriate action class
                $actionClass = match($method) {
                    'employ' => EmployAction::class,
                    'release' => ReleaseAction::class,
                    'suspend' => SuspendAction::class,
                    'reinstate' => ReinstateAction::class,
                    'injure' => InjureAction::class,
                    'clearFromInjury' => ClearInjuryAction::class,
                    'retire' => RetireAction::class,
                    'unretire' => UnretireAction::class,
                };
                
                app()->instance($actionClass, $mockAction);

                $component = Livewire::actingAs($this->admin)
                    ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

                $component->call($method, now()->format('Y-m-d'))
                    ->assertDispatched('wrestler-updated');
            }
        });
    });

    describe('business rule enforcement', function () {
        test('actions enforce business rules through Action classes', function () {
            // Employment action should validate wrestler status
            $mockAction = mock(EmployAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->with($this->availableWrestler, \Mockery::type('string'));
            app()->instance(EmployAction::class, $mockAction);

            Gate::shouldReceive('authorize')->once();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->call('employ', now()->format('Y-m-d'))
                ->assertHasNoErrors();
        });

        test('component validates date parameters', function () {
            Gate::shouldReceive('authorize')->once();
            $mockAction = mock(EmployAction::class);
            $mockAction->shouldReceive('handle')->once();
            app()->instance(EmployAction::class, $mockAction);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            // Valid date format should work
            $component->call('employ', '2024-01-01')
                ->assertHasNoErrors();
        });
    });

    describe('error handling and exception management', function () {
        test('handles action exceptions gracefully', function () {
            // Mock action to throw exception
            $mockAction = mock(EmployAction::class);
            $mockAction->shouldReceive('handle')
                ->once()
                ->andThrow(new \Exception('Business rule violation'));
            app()->instance(EmployAction::class, $mockAction);

            Gate::shouldReceive('authorize')->once();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->call('employ', now()->format('Y-m-d'))
                ->assertHasNoErrors(); // Component should handle exception gracefully
        });

        test('handles authorization failures', function () {
            Gate::shouldReceive('authorize')
                ->once()
                ->with('employ', $this->availableWrestler)
                ->andThrow(new \Illuminate\Auth\Access\AuthorizationException('Unauthorized'));

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            expect(function () use ($component) {
                $component->call('employ', now()->format('Y-m-d'));
            })->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
        });
    });

    describe('component state management', function () {
        test('wrestler property is correctly managed', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            expect($component->instance()->wrestler->id)->toBe($this->availableWrestler->id);
            expect($component->instance()->wrestler->name)->toBe('Available Wrestler');
        });

        test('component maintains wrestler state throughout action calls', function () {
            Gate::shouldReceive('authorize')->once();
            $mockAction = mock(EmployAction::class);
            $mockAction->shouldReceive('handle')->once();
            app()->instance(EmployAction::class, $mockAction);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlerActionsComponent::class, ['wrestler' => $this->availableWrestler]);

            $component->call('employ', now()->format('Y-m-d'));

            // Wrestler should still be accessible after action
            expect($component->instance()->wrestler->id)->toBe($this->availableWrestler->id);
        });
    });
});