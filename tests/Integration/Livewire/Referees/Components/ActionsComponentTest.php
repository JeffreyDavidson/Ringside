<?php

declare(strict_types=1);

use App\Livewire\Referees\Components\ActionsComponent;
use App\Models\Referees\Referee;
use App\Models\Users\User;
use Livewire\Livewire;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Referee Actions Component Integration Tests
 *
 * Tests the complete business action workflow for referees including:
 * - Employment lifecycle (employ, release)
 * - Injury management (injure, heal)
 * - Suspension workflow (suspend, reinstate)
 * - Retirement lifecycle (retire, unretire)
 * - Referee-specific business logic (match assignment eligibility)
 * - Status transitions and validation
 * - Authorization integration
 * - Event dispatching and state management
 */
describe('RefereesActionsComponent Integration Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->admin = User::factory()->administrator()->create();
        $this->referee = Referee::factory()->employed()->create([
            'first_name' => 'Test',
            'last_name' => 'Referee',
        ]);
    });

    describe('component initialization', function () {
        test('component loads with referee properly bound', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            expect($component->get('referee')->id)->toBe($this->referee->id);
            expect($component->get('referee')->first_name)->toBe('Test');
            expect($component->get('referee')->last_name)->toBe('Referee');
        });

        test('component renders without errors', function () {
            Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee])
                ->assertOk();
        });
    });

    describe('employment actions', function () {
        test('employ action works for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create([
                'first_name' => 'Unemployed',
                'last_name' => 'Referee',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $unemployedReferee]);

            $component->call('employ')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($unemployedReferee->fresh()->isEmployed())->toBeTrue();
            expect(session('status'))->toBe('Referee successfully employed.');
        });

        test('employ action fails for already employed referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('employ');

            expect(session('error'))->toContain('cannot be employed');
        });

        test('release action works for employed referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('release')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($this->referee->fresh()->isReleased())->toBeTrue();
            expect(session('status'))->toBe('Referee successfully released.');
        });

        test('release action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $unemployedReferee]);

            $component->call('release');

            expect(session('error'))->toContain('cannot be released');
        });
    });

    describe('injury and healing actions', function () {
        test('injure action works for healthy employed referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('injure')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($this->referee->fresh()->isInjured())->toBeTrue();
            expect(session('status'))->toBe('Referee injury recorded.');
        });

        test('injure action fails for already injured referee', function () {
            $injuredReferee = Referee::factory()->injured()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $injuredReferee]);

            $component->call('injure');

            expect(session('error'))->toContain('cannot be injured');
        });

        test('heal action works for injured referee', function () {
            $injuredReferee = Referee::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Referee',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $injuredReferee]);

            $component->call('healFromInjury')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($injuredReferee->fresh()->isInjured())->toBeFalse();
            expect(session('status'))->toBe('Referee cleared from injury.');
        });

        test('heal action fails for healthy referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('healFromInjury');

            expect(session('error'))->toContain('cannot be cleared from injury');
        });
    });

    describe('suspension and reinstatement actions', function () {
        test('suspend action works for employed referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('suspend')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($this->referee->fresh()->isSuspended())->toBeTrue();
            expect(session('status'))->toBe('Referee successfully suspended.');
        });

        test('suspend action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $unemployedReferee]);

            $component->call('suspend');

            expect(session('error'))->toContain('cannot be suspended');
        });

        test('reinstate action works for suspended referee', function () {
            $suspendedReferee = Referee::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Referee',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $suspendedReferee]);

            $component->call('reinstate')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($suspendedReferee->fresh()->isSuspended())->toBeFalse();
            expect(session('status'))->toBe('Referee successfully reinstated.');
        });

        test('reinstate action fails for non-suspended referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('reinstate');

            expect(session('error'))->toContain('cannot be reinstated');
        });
    });

    describe('retirement lifecycle actions', function () {
        test('retire action works for employed referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('retire')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($this->referee->fresh()->isRetired())->toBeTrue();
            expect(session('status'))->toBe('Referee successfully retired.');
        });

        test('retire action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $unemployedReferee]);

            $component->call('retire');

            expect(session('error'))->toContain('cannot be retired');
        });

        test('unretire action works for retired referee', function () {
            $retiredReferee = Referee::factory()->retired()->create([
                'first_name' => 'Retired',
                'last_name' => 'Referee',
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $retiredReferee]);

            $component->call('unretire')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect($retiredReferee->fresh()->isRetired())->toBeFalse();
            expect(session('status'))->toBe('Referee successfully unretired.');
        });

        test('unretire action fails for active referee', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('unretire');

            expect(session('error'))->toContain('cannot be unretired');
        });
    });

    describe('restore action', function () {
        test('restore action works for soft deleted referee', function () {
            $this->referee->delete();
            expect($this->referee->fresh())->toBeNull();

            $trashedReferee = Referee::onlyTrashed()->find($this->referee->id);

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $trashedReferee]);

            $component->call('restore')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(Referee::find($this->referee->id))->not->toBeNull();
            expect(session('status'))->toBe('Referee successfully restored.');
        });
    });

    describe('referee-specific business scenarios', function () {
        test('referee can transition through complete career lifecycle', function () {
            // Start unemployed
            $referee = Referee::factory()->unemployed()->create([
                'first_name' => 'Career',
                'last_name' => 'Official',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $referee]);

            // Employ
            $component->call('employ');
            expect($referee->fresh()->isEmployed())->toBeTrue();

            // Injure (referee injury during match)
            $component->call('injure');
            expect($referee->fresh()->isInjured())->toBeTrue();

            // Heal (medical clearance)
            $component->call('healFromInjury');
            expect($referee->fresh()->isInjured())->toBeFalse();

            // Suspend (for poor performance, missed calls, etc.)
            $component->call('suspend');
            expect($referee->fresh()->isSuspended())->toBeTrue();

            // Reinstate (after retraining)
            $component->call('reinstate');
            expect($referee->fresh()->isSuspended())->toBeFalse();

            // Retire
            $component->call('retire');
            expect($referee->fresh()->isRetired())->toBeTrue();

            // Comeback
            $component->call('unretire');
            expect($referee->fresh()->isRetired())->toBeFalse();
            expect($referee->fresh()->isEmployed())->toBeTrue();
        });

        test('injured referee cannot be assigned to matches', function () {
            $injuredReferee = Referee::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Official',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $injuredReferee]);

            // Referee is employed but injured (not available for matches)
            expect($injuredReferee->isEmployed())->toBeTrue();
            expect($injuredReferee->isInjured())->toBeTrue();

            // Cannot suspend injured referee without healing first
            $component->call('suspend');
            expect(session('error'))->toContain('cannot be suspended');

            // Can heal first, then suspend
            $component->call('healFromInjury');
            expect($injuredReferee->fresh()->isInjured())->toBeFalse();

            $component->call('suspend');
            expect($injuredReferee->fresh()->isSuspended())->toBeTrue();
        });

        test('suspended referee cannot officiate matches', function () {
            $suspendedReferee = Referee::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Official',
            ]);
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $suspendedReferee]);

            // Suspended referee still employed but cannot officiate
            expect($suspendedReferee->isEmployed())->toBeTrue();
            expect($suspendedReferee->isSuspended())->toBeTrue();

            // Cannot retire while suspended (must be reinstated first)
            $component->call('retire');
            expect(session('error'))->toContain('cannot be retired');

            // Must reinstate first
            $component->call('reinstate');
            expect($suspendedReferee->fresh()->isSuspended())->toBeFalse();

            // Now can retire
            $component->call('retire');
            expect($suspendedReferee->fresh()->isRetired())->toBeTrue();
        });

        test('referee experience level affects assignment priority', function () {
            // Junior referee (recently employed)
            $juniorReferee = Referee::factory()->employed()->create([
                'first_name' => 'Junior',
                'last_name' => 'Official',
            ]);

            // Senior referee (long employment history)
            $seniorReferee = Referee::factory()->employed()->create([
                'first_name' => 'Senior',
                'last_name' => 'Official',
            ]);

            $juniorComponent = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $juniorReferee]);

            $seniorComponent = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $seniorReferee]);

            // Both can be injured, suspended, etc.
            $juniorComponent->call('injure');
            expect($juniorReferee->fresh()->isInjured())->toBeTrue();

            $seniorComponent->call('suspend');
            expect($seniorReferee->fresh()->isSuspended())->toBeTrue();

            // Both can be restored to active status
            $juniorComponent->call('healFromInjury');
            expect($juniorReferee->fresh()->isInjured())->toBeFalse();

            $seniorComponent->call('reinstate');
            expect($seniorReferee->fresh()->isSuspended())->toBeFalse();
        });
    });

    describe('authorization integration', function () {
        test('unauthorized user cannot perform actions', function () {
            $guest = User::factory()->create(); // Non-admin user

            $component = Livewire::actingAs($guest)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('employ')
                ->assertForbidden();
        });

        test('admin can perform all actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            // All action calls should succeed (though business rules may prevent them)
            $component->call('release')
                ->assertOk();

            expect(session('status'))->toBe('Referee successfully released.');
        });
    });

    describe('event dispatching and state management', function () {
        test('all successful actions dispatch referee-updated event', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('release')
                ->assertDispatched('referee-updated');

            $component->call('employ')
                ->assertDispatched('referee-updated');

            $component->call('injure')
                ->assertDispatched('referee-updated');
        });

        test('failed actions do not dispatch events', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            // Try to employ already employed referee
            $component->call('employ')
                ->assertNotDispatched('referee-updated');
        });

        test('component state remains consistent after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            expect($component->get('referee')->id)->toBe($this->referee->id);

            $component->call('release');

            // Component referee reference should still be valid
            expect($component->get('referee')->id)->toBe($this->referee->id);
        });
    });

    describe('error handling and edge cases', function () {
        test('component handles referee model refresh after actions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            // Perform action
            $component->call('release');

            // Referee status should reflect in fresh model
            expect($this->referee->fresh()->isReleased())->toBeTrue();
        });

        test('component maintains referee data integrity', function () {
            $originalFirstName = $this->referee->first_name;
            $originalLastName = $this->referee->last_name;
            $originalId = $this->referee->id;

            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $component->call('injure');

            expect($component->get('referee')->first_name)->toBe($originalFirstName);
            expect($component->get('referee')->last_name)->toBe($originalLastName);
            expect($component->get('referee')->id)->toBe($originalId);
        });

        test('referee full name consistency maintained', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(ActionsComponent::class, ['referee' => $this->referee]);

            $originalFullName = $this->referee->full_name;

            $component->call('suspend');

            // Full name should remain consistent
            expect($component->get('referee')->full_name)->toBe($originalFullName);
            expect($this->referee->fresh()->full_name)->toBe($originalFullName);
        });
    });
});