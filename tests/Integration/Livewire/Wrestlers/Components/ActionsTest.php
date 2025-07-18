<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Wrestler Actions Component Integration Tests
 *
 * Tests the complete business action workflow for wrestlers including:
 * - Employment lifecycle (employ, release)
 * - Injury management (injure, heal)
 * - Suspension workflow (suspend, reinstate)
 * - Retirement lifecycle (retire, unretire)
 * - Status transitions and validation
 * - Authorization integration
 * - Event dispatching and state management
 */
describe('WrestlersActions Integration Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->admin = User::factory()->administrator()->create();
        $this->wrestler = Wrestler::factory()->employed()->create(['name' => 'Test Wrestler']);
    });

    describe('component initialization', function () {
        test('component loads with wrestler properly bound', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            expect($component->get('wrestler')->id)->toBe($this->wrestler->id);
            expect($component->get('wrestler')->name)->toBe('Test Wrestler');
        });

        test('component renders without errors', function () {
            Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler])
                ->assertOk();
        });
    });

    describe('employment actions', function () {
        test('employ action works for unemployed wrestler', function () {
            $unemployedWrestler = Wrestler::factory()->unemployed()->create(['name' => 'Unemployed Wrestler']);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $unemployedWrestler]);

            $component->call('employ')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($unemployedWrestler->fresh()->isEmployed())->toBeTrue();
            expect(session('status'))->toBe('Wrestler successfully employed.');
        });

        test('employ action fails for already employed wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('employ');

            expect(session('error'))->toContain('cannot be employed');
        });

        test('release action works for employed wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('release')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($this->wrestler->fresh()->isReleased())->toBeTrue();
            expect(session('status'))->toBe('Wrestler successfully released.');
        });

        test('release action fails for unemployed wrestler', function () {
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $unemployedWrestler]);

            $component->call('release');

            expect(session('error'))->toContain('cannot be released');
        });
    });

    describe('injury and healing actions', function () {
        test('injure action works for healthy employed wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('injure')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($this->wrestler->fresh()->isInjured())->toBeTrue();
            expect(session('status'))->toBe('Wrestler injury recorded.');
        });

        test('injure action fails for already injured wrestler', function () {
            $injuredWrestler = Wrestler::factory()->injured()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $injuredWrestler]);

            $component->call('injure');

            expect(session('error'))->toContain('cannot be injured');
        });

        test('heal action works for injured wrestler', function () {
            $injuredWrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $injuredWrestler]);

            $component->call('healFromInjury')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($injuredWrestler->fresh()->isInjured())->toBeFalse();
            expect(session('status'))->toBe('Wrestler cleared from injury.');
        });

        test('heal action fails for healthy wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('healFromInjury');

            expect(session('error'))->toContain('cannot be cleared from injury');
        });
    });

    describe('suspension and reinstatement actions', function () {
        test('suspend action works for employed wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('suspend')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($this->wrestler->fresh()->isSuspended())->toBeTrue();
            expect(session('status'))->toBe('Wrestler successfully suspended.');
        });

        test('suspend action fails for unemployed wrestler', function () {
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $unemployedWrestler]);

            $component->call('suspend');

            expect(session('error'))->toContain('cannot be suspended');
        });

        test('reinstate action works for suspended wrestler', function () {
            $suspendedWrestler = Wrestler::factory()->suspended()->create(['name' => 'Suspended Wrestler']);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $suspendedWrestler]);

            $component->call('reinstate')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($suspendedWrestler->fresh()->isSuspended())->toBeFalse();
            expect(session('status'))->toBe('Wrestler successfully reinstated.');
        });

        test('reinstate action fails for non-suspended wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('reinstate');

            expect(session('error'))->toContain('cannot be reinstated');
        });
    });

    describe('retirement lifecycle actions', function () {
        test('retire action works for employed wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('retire')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($this->wrestler->fresh()->isRetired())->toBeTrue();
            expect(session('status'))->toBe('Wrestler successfully retired.');
        });

        test('retire action fails for unemployed wrestler', function () {
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $unemployedWrestler]);

            $component->call('retire');

            expect(session('error'))->toContain('cannot be retired');
        });

        test('unretire action works for retired wrestler', function () {
            $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $retiredWrestler]);

            $component->call('unretire')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect($retiredWrestler->fresh()->isRetired())->toBeFalse();
            expect(session('status'))->toBe('Wrestler successfully unretired.');
        });

        test('unretire action fails for active wrestler', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('unretire');

            expect(session('error'))->toContain('cannot be unretired');
        });
    });

    describe('restore action', function () {
        test('restore action works for soft deleted wrestler', function () {
            $this->wrestler->delete();
            expect($this->wrestler->fresh())->toBeNull();

            $trashedWrestler = Wrestler::onlyTrashed()->find($this->wrestler->id);

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $trashedWrestler]);

            $component->call('restore')
                ->assertHasNoErrors()
                ->assertDispatched('wrestler-updated');

            expect(Wrestler::find($this->wrestler->id))->not->toBeNull();
            expect(session('status'))->toBe('Wrestler successfully restored.');
        });
    });

    describe('complex status transition scenarios', function () {
        test('wrestler can transition through complete career lifecycle', function () {
            // Start unemployed
            $wrestler = Wrestler::factory()->unemployed()->create(['name' => 'Career Wrestler']);
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $wrestler]);

            // Employ
            $component->call('employ');
            expect($wrestler->fresh()->isEmployed())->toBeTrue();

            // Injure
            $component->call('injure');
            expect($wrestler->fresh()->isInjured())->toBeTrue();

            // Heal
            $component->call('healFromInjury');
            expect($wrestler->fresh()->isInjured())->toBeFalse();

            // Suspend
            $component->call('suspend');
            expect($wrestler->fresh()->isSuspended())->toBeTrue();

            // Reinstate
            $component->call('reinstate');
            expect($wrestler->fresh()->isSuspended())->toBeFalse();

            // Retire
            $component->call('retire');
            expect($wrestler->fresh()->isRetired())->toBeTrue();

            // Comeback
            $component->call('unretire');
            expect($wrestler->fresh()->isRetired())->toBeFalse();
            expect($wrestler->fresh()->isEmployed())->toBeTrue();
        });

        test('action availability changes based on current status', function () {
            $injuredWrestler = Wrestler::factory()->injured()->create();
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $injuredWrestler]);

            // Cannot employ injured wrestler
            $component->call('employ');
            expect(session('error'))->toContain('cannot be employed');

            // Cannot injure already injured wrestler
            $component->call('injure');
            expect(session('error'))->toContain('cannot be injured');

            // Can heal injured wrestler
            $component->call('healFromInjury');
            expect($injuredWrestler->fresh()->isInjured())->toBeFalse();
        });
    });

    describe('authorization integration', function () {
        test('unauthorized user cannot perform actions', function () {
            $guest = User::factory()->create(); // Non-admin user

            $component = Livewire::actingAs($guest)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('employ')
                ->assertForbidden();
        });

        test('admin can perform all actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            // All action calls should succeed (though business rules may prevent them)
            $component->call('release')
                ->assertOk();

            expect(session('status'))->toBe('Wrestler successfully released.');
        });
    });

    describe('event dispatching and state management', function () {
        test('all successful actions dispatch wrestler-updated event', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('release')
                ->assertDispatched('wrestler-updated');

            $component->call('employ')
                ->assertDispatched('wrestler-updated');

            $component->call('injure')
                ->assertDispatched('wrestler-updated');
        });

        test('failed actions do not dispatch events', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            // Try to employ already employed wrestler
            $component->call('employ')
                ->assertNotDispatched('wrestler-updated');
        });

        test('component state remains consistent after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            expect($component->get('wrestler')->id)->toBe($this->wrestler->id);

            $component->call('release');

            // Component wrestler reference should still be valid
            expect($component->get('wrestler')->id)->toBe($this->wrestler->id);
        });
    });

    describe('error handling and edge cases', function () {
        test('component handles wrestler model refresh after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            // Perform action
            $component->call('release');

            // Wrestler status should reflect in fresh model
            expect($this->wrestler->fresh()->isReleased())->toBeTrue();
        });

        test('component maintains wrestler data integrity', function () {
            $originalName = $this->wrestler->name;
            $originalId = $this->wrestler->id;

            $component = Livewire::actingAs($this->admin)
                ->test(Actions::class, ['wrestler' => $this->wrestler]);

            $component->call('injure');

            expect($component->get('wrestler')->name)->toBe($originalName);
            expect($component->get('wrestler')->id)->toBe($originalId);
        });
    });
});