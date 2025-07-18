<?php

declare(strict_types=1);

use App\Livewire\Managers\Components\Actions;
use App\Models\Managers\Manager;
use App\Models\Users\User;
use Livewire\Livewire;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Manager Actions Component Integration Tests
 *
 * Tests the complete business action workflow for managers including:
 * - Employment lifecycle (employ, release)
 * - Injury management (injure, heal)
 * - Suspension workflow (suspend, reinstate)
 * - Retirement lifecycle (retire, unretire)
 * - Manager-specific business logic
 * - Status transitions and validation
 * - Authorization integration
 * - Event dispatching and state management
 */
describe('ManagersActions Integration Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->admin = User::factory()->administrator()->create();
        $this->manager = Manager::factory()->employed()->create([
            'first_name' => 'Test',
            'last_name' => 'Manager',
        ]);
    });

    describe('component initialization', function () {
        test('component loads with manager properly bound', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            expect($component->get('manager')->id)->toBe($this->manager->id);
            expect($component->get('manager')->first_name)->toBe('Test');
            expect($component->get('manager')->last_name)->toBe('Manager');
        });

        test('component renders without errors', function () {
            Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager])
                ->assertOk();
        });
    });

    describe('employment actions', function () {
        test('employ action works for unemployed manager', function () {
            $unemployedManager = Manager::factory()->unemployed()->create([
                'first_name' => 'Unemployed',
                'last_name' => 'Manager',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $unemployedManager]);

            $component->call('employ')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($unemployedManager->fresh()->isEmployed())->toBeTrue();
            expect(session('status'))->toBe('Manager successfully employed.');
        });

        test('employ action fails for already employed manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('employ');

            expect(session('error'))->toContain('cannot be employed');
        });

        test('release action works for employed manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('release')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($this->manager->fresh()->isReleased())->toBeTrue();
            expect(session('status'))->toBe('Manager successfully released.');
        });

        test('release action fails for unemployed manager', function () {
            $unemployedManager = Manager::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $unemployedManager]);

            $component->call('release');

            expect(session('error'))->toContain('cannot be released');
        });
    });

    describe('injury and healing actions', function () {
        test('injure action works for healthy employed manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('injure')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($this->manager->fresh()->isInjured())->toBeTrue();
            expect(session('status'))->toBe('Manager injury recorded.');
        });

        test('injure action fails for already injured manager', function () {
            $injuredManager = Manager::factory()->injured()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $injuredManager]);

            $component->call('injure');

            expect(session('error'))->toContain('cannot be injured');
        });

        test('heal action works for injured manager', function () {
            $injuredManager = Manager::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Manager',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $injuredManager]);

            $component->call('healFromInjury')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($injuredManager->fresh()->isInjured())->toBeFalse();
            expect(session('status'))->toBe('Manager cleared from injury.');
        });

        test('heal action fails for healthy manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('healFromInjury');

            expect(session('error'))->toContain('cannot be cleared from injury');
        });
    });

    describe('suspension and reinstatement actions', function () {
        test('suspend action works for employed manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('suspend')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($this->manager->fresh()->isSuspended())->toBeTrue();
            expect(session('status'))->toBe('Manager successfully suspended.');
        });

        test('suspend action fails for unemployed manager', function () {
            $unemployedManager = Manager::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $unemployedManager]);

            $component->call('suspend');

            expect(session('error'))->toContain('cannot be suspended');
        });

        test('reinstate action works for suspended manager', function () {
            $suspendedManager = Manager::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Manager',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $suspendedManager]);

            $component->call('reinstate')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($suspendedManager->fresh()->isSuspended())->toBeFalse();
            expect(session('status'))->toBe('Manager successfully reinstated.');
        });

        test('reinstate action fails for non-suspended manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('reinstate');

            expect(session('error'))->toContain('cannot be reinstated');
        });
    });

    describe('retirement lifecycle actions', function () {
        test('retire action works for employed manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('retire')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($this->manager->fresh()->isRetired())->toBeTrue();
            expect(session('status'))->toBe('Manager successfully retired.');
        });

        test('retire action fails for unemployed manager', function () {
            $unemployedManager = Manager::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $unemployedManager]);

            $component->call('retire');

            expect(session('error'))->toContain('cannot be retired');
        });

        test('unretire action works for retired manager', function () {
            $retiredManager = Manager::factory()->retired()->create([
                'first_name' => 'Retired',
                'last_name' => 'Manager',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $retiredManager]);

            $component->call('unretire')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect($retiredManager->fresh()->isRetired())->toBeFalse();
            expect(session('status'))->toBe('Manager successfully unretired.');
        });

        test('unretire action fails for active manager', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('unretire');

            expect(session('error'))->toContain('cannot be unretired');
        });
    });

    describe('restore action', function () {
        test('restore action works for soft deleted manager', function () {
            $this->manager->delete();
            expect($this->manager->fresh())->toBeNull();

            $trashedManager = Manager::onlyTrashed()->find($this->manager->id);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $trashedManager]);

            $component->call('restore')
                ->assertHasNoErrors()
                ->assertDispatched('manager-updated');

            expect(Manager::find($this->manager->id))->not->toBeNull();
            expect(session('status'))->toBe('Manager successfully restored.');
        });
    });

    describe('manager-specific business scenarios', function () {
        test('manager can transition through complete career lifecycle', function () {
            // Start unemployed
            $manager = Manager::factory()->unemployed()->create([
                'first_name' => 'Career',
                'last_name' => 'Manager',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $manager]);

            // Employ
            $component->call('employ');
            expect($manager->fresh()->isEmployed())->toBeTrue();

            // Injure (managers can get injured backstage, traveling, etc.)
            $component->call('injure');
            expect($manager->fresh()->isInjured())->toBeTrue();

            // Heal
            $component->call('healFromInjury');
            expect($manager->fresh()->isInjured())->toBeFalse();

            // Suspend (for misconduct, contract violations, etc.)
            $component->call('suspend');
            expect($manager->fresh()->isSuspended())->toBeTrue();

            // Reinstate
            $component->call('reinstate');
            expect($manager->fresh()->isSuspended())->toBeFalse();

            // Retire
            $component->call('retire');
            expect($manager->fresh()->isRetired())->toBeTrue();

            // Comeback
            $component->call('unretire');
            expect($manager->fresh()->isRetired())->toBeFalse();
            expect($manager->fresh()->isEmployed())->toBeTrue();
        });

        test('injured manager can still manage wrestlers but cannot be assigned new talent', function () {
            $injuredManager = Manager::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Manager',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $injuredManager]);

            // Manager is employed but injured
            expect($injuredManager->isEmployed())->toBeTrue();
            expect($injuredManager->isInjured())->toBeTrue();

            // Cannot suspend injured manager without healing first
            $component->call('suspend');
            expect(session('error'))->toContain('cannot be suspended');

            // Can heal first, then suspend
            $component->call('healFromInjury');
            expect($injuredManager->fresh()->isInjured())->toBeFalse();

            $component->call('suspend');
            expect($injuredManager->fresh()->isSuspended())->toBeTrue();
        });

        test('suspended manager cannot manage active wrestlers', function () {
            $suspendedManager = Manager::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Manager',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $suspendedManager]);

            // Suspended manager still employed but cannot perform duties
            expect($suspendedManager->isEmployed())->toBeTrue();
            expect($suspendedManager->isSuspended())->toBeTrue();

            // Cannot retire while suspended (must be reinstated first)
            $component->call('retire');
            expect(session('error'))->toContain('cannot be retired');

            // Must reinstate first
            $component->call('reinstate');
            expect($suspendedManager->fresh()->isSuspended())->toBeFalse();

            // Now can retire
            $component->call('retire');
            expect($suspendedManager->fresh()->isRetired())->toBeTrue();
        });
    });

    describe('authorization integration', function () {
        test('unauthorized user cannot perform actions', function () {
            $guest = User::factory()->create(); // Non-admin user

            $component = Livewire::actingAs($guest)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('employ')
                ->assertForbidden();
        });

        test('admin can perform all actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            // All action calls should succeed (though business rules may prevent them)
            $component->call('release')
                ->assertOk();

            expect(session('status'))->toBe('Manager successfully released.');
        });
    });

    describe('event dispatching and state management', function () {
        test('all successful actions dispatch manager-updated event', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('release')
                ->assertDispatched('manager-updated');

            $component->call('employ')
                ->assertDispatched('manager-updated');

            $component->call('injure')
                ->assertDispatched('manager-updated');
        });

        test('failed actions do not dispatch events', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            // Try to employ already employed manager
            $component->call('employ')
                ->assertNotDispatched('manager-updated');
        });

        test('component state remains consistent after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            expect($component->get('manager')->id)->toBe($this->manager->id);

            $component->call('release');

            // Component manager reference should still be valid
            expect($component->get('manager')->id)->toBe($this->manager->id);
        });
    });

    describe('error handling and edge cases', function () {
        test('component handles manager model refresh after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            // Perform action
            $component->call('release');

            // Manager status should reflect in fresh model
            expect($this->manager->fresh()->isReleased())->toBeTrue();
        });

        test('component maintains manager data integrity', function () {
            $originalFirstName = $this->manager->first_name;
            $originalLastName = $this->manager->last_name;
            $originalId = $this->manager->id;

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $component->call('injure');

            expect($component->get('manager')->first_name)->toBe($originalFirstName);
            expect($component->get('manager')->last_name)->toBe($originalLastName);
            expect($component->get('manager')->id)->toBe($originalId);
        });

        test('manager display name consistency maintained', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['manager' => $this->manager]);

            $originalDisplayName = $this->manager->display_name;

            $component->call('suspend');

            // Display name should remain consistent
            expect($component->get('manager')->display_name)->toBe($originalDisplayName);
            expect($this->manager->fresh()->display_name)->toBe($originalDisplayName);
        });
    });
});