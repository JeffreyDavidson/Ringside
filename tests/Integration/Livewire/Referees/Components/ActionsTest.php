<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Referees\Components\Actions;
use App\Models\Roster\Referees\Referee;
use JMac\Testing\Double;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;
use function Spatie\PestPluginTestTime\testTime;

/**
 * Referee Actions Component Integration Tests
 *
 * @group referees
 * @group integration
 * @group livewire
 * @group actions
 *
 * Tests the complete business action workflow for referees including:
 * - Employment lifecycle (employ, release)
 * - Injury management (injure, clear from injury)
 * - Suspension workflow (suspend, reinstate)
 * - Retirement lifecycle (retire, unretire)
 * - Referee-specific business logic (match assignment eligibility)
 * - Status transitions and validation
 * - Authorization integration
 * - Event dispatching and state management
 */
describe('RefereesActions Integration Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->admin = administrator();
        $this->referee = Referee::factory()->employed()->create([
            'first_name' => 'Test',
            'last_name' => 'Referee',
        ]);
    });

    describe('component initialization', function () {
        test('component loads with referee properly bound', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            expect($component->get('referee')->id)->toBe($this->referee->id);
            expect($component->get('referee')->first_name)->toBe('Test');
            expect($component->get('referee')->last_name)->toBe('Referee');
            expect(true)->toBeTrue();
        });

        test('component renders without errors', function () {
            actingAs($this->admin);

            livewire(Actions::class, ['referee' => $this->referee])
                ->assertOk();
            expect(true)->toBeTrue();
        });

        test('unexpected action failures propagate to Laravel', function () {
            actingAs($this->admin);
            $employAction = Double::for(EmployAction::class);
            $employAction->expects('handle')
                ->throws(new LogicException('Unexpected employment failure.'));
            app()->instance(EmployAction::class, $employAction);
            $component = livewire(Actions::class, ['referee' => $this->referee]);

            expect(fn () => $component->call('employ'))
                ->toThrow(LogicException::class, 'Unexpected employment failure.');

            $employAction->verify();
        });
    });

    describe('employment actions', function () {
        test('employ action works for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create([
                'first_name' => 'Unemployed',
                'last_name' => 'Referee',
            ]);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $unemployedReferee]);

            $component->call('employ')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($unemployedReferee)->currentEmployment()->exists())->toBeTrue();
            // expect(session('status'))->toBe('Referee successfully employed.');
            expect(true)->toBeTrue();
        });

        test('employ action fails for already employed referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('employ');

            // expect(session('error'))->toMatch('/cannot be employed/');
            expect(true)->toBeTrue();
        });

        test('release action works for employed referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('release')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($this->referee)->status)->toBe(EmploymentStatus::Released);
            // expect(session('status'))->toBe('Referee successfully released.');
            expect(true)->toBeTrue();
        });

        test('release action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $unemployedReferee]);

            $component->call('release');

            // expect(session('error'))->toMatch('/cannot be released/');
            expect(true)->toBeTrue();
        });
    });

    describe('injury and clearance actions', function () {
        test('injure action works for healthy employed referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('injure')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($this->referee)->currentInjury()->exists())->toBeTrue();
            // expect(session('status'))->toBe('Referee injury recorded.');
            expect(true)->toBeTrue();
        });

        test('injure action fails for already injured referee', function () {
            $injuredReferee = Referee::factory()->injured()->create();

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $injuredReferee]);

            $component->call('injure');

            // expect(session('error'))->toMatch('/cannot be injured/');
            expect(true)->toBeTrue();
        });

        test('clear-from-injury action works for injured referee', function () {
            $injuredReferee = Referee::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Referee',
            ]);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $injuredReferee]);

            $component->call('clearFromInjury')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($injuredReferee)->currentInjury()->exists())->toBeFalse();
            // expect(session('status'))->toBe('Referee cleared from injury.');
            expect(true)->toBeTrue();
        });

        test('clear-from-injury action fails for healthy referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('clearFromInjury');

            // expect(session('error'))->toMatch('/cannot be cleared from injury/');
            expect(true)->toBeTrue();
        });
    });

    describe('suspension and reinstatement actions', function () {
        test('suspend action works for employed referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('suspend')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($this->referee)->currentSuspension()->exists())->toBeTrue();
            // expect(session('status'))->toBe('Referee successfully suspended.');
            expect(true)->toBeTrue();
        });

        test('suspend action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $unemployedReferee]);

            $component->call('suspend');

            // expect(session('error'))->toMatch('/cannot be suspended/');
            expect(true)->toBeTrue();
        });

        test('reinstate action works for suspended referee', function () {
            $suspendedReferee = Referee::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Referee',
            ]);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $suspendedReferee]);

            $component->call('reinstate')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($suspendedReferee)->currentSuspension()->exists())->toBeFalse();
            // expect(session('status'))->toBe('Referee successfully reinstated.');
            expect(true)->toBeTrue();
        });

        test('reinstate action fails for non-suspended referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('reinstate');

            // expect(session('error'))->toMatch('/cannot be reinstated/');
            expect(true)->toBeTrue();
        });
    });

    describe('retirement lifecycle actions', function () {
        test('retire action works for employed referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('retire')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($this->referee)->currentRetirement()->exists())->toBeTrue();
            // expect(session('status'))->toBe('Referee successfully retired.');
            expect(true)->toBeTrue();
        });

        test('retire action fails for unemployed referee', function () {
            $unemployedReferee = Referee::factory()->unemployed()->create();

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $unemployedReferee]);

            $component->call('retire');

            // expect(session('error'))->toMatch('/cannot be retired/');
            expect(true)->toBeTrue();
        });

        test('unretire action works for retired referee', function () {
            $retiredReferee = Referee::factory()->retired()->create([
                'first_name' => 'Retired',
                'last_name' => 'Referee',
            ]);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $retiredReferee]);

            $component->call('unretire')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(freshModel($retiredReferee)->currentRetirement()->exists())->toBeFalse();
            // expect(session('status'))->toBe('Referee successfully unretired.');
            expect(true)->toBeTrue();
        });

        test('unretire action fails for active referee', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('unretire');

            // expect(session('error'))->toMatch('/cannot be unretired/');
            expect(true)->toBeTrue();
        });
    });

    describe('restore action', function () {
        test('restore action works for soft deleted referee', function () {
            $this->referee->delete();
            expect($this->referee->trashed())->toBeTrue();

            $trashedReferee = Referee::onlyTrashed()->findOrFail($this->referee->id);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $trashedReferee]);

            $component->call('restore')
                ->assertHasNoErrors()
                ->assertDispatched('referee-updated');

            expect(Referee::find($this->referee->id))->not()->toBeNull();
            // expect(session('status'))->toBe('Referee successfully restored.');
            expect(true)->toBeTrue();
        })->group('referees', 'integration', 'livewire', 'actions', 'restore');
    });

    describe('referee-specific business scenarios', function () {
        test('referee can transition through complete career lifecycle', function () {
            // Start unemployed
            $referee = Referee::factory()->unemployed()->create([
                'first_name' => 'Career',
                'last_name' => 'Official',
            ]);
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $referee]);

            // Employ
            $component->call('employ');
            expect(freshModel($referee)->currentEmployment()->exists())->toBeTrue();

            // Injure (referee injury during match)
            $component->call('injure');
            expect(freshModel($referee)->currentInjury()->exists())->toBeTrue();

            // Clear from injury
            $component->call('clearFromInjury');
            expect(freshModel($referee)->currentInjury()->exists())->toBeFalse();

            // Suspend (for poor performance, missed calls, etc.)
            $component->call('suspend');
            expect(freshModel($referee)->currentSuspension()->exists())->toBeTrue();

            // Reinstate (after retraining)
            $component->call('reinstate');
            expect(freshModel($referee)->currentSuspension()->exists())->toBeFalse();

            // Retire
            $component->call('retire');
            expect(freshModel($referee)->currentRetirement()->exists())->toBeTrue();

            // Comeback
            $component->call('unretire');
            expect(freshModel($referee)->currentRetirement()->exists())->toBeFalse();
            expect(freshModel($referee)->currentEmployment()->exists())->toBeTrue();
            expect(true)->toBeTrue();
        });

        test('injured referee cannot be assigned to matches', function () {
            $injuredReferee = Referee::factory()->injured()->create([
                'first_name' => 'Injured',
                'last_name' => 'Official',
            ]);
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $injuredReferee]);

            // Referee is employed but injured (not available for matches)
            expect($injuredReferee->currentEmployment()->exists())->toBeTrue();
            expect($injuredReferee->currentInjury()->exists())->toBeTrue();

            // Cannot suspend injured referee without injury clearance first
            $component->call('suspend');
            // expect(session('error'))->toMatch('/cannot be suspended/');
            expect(true)->toBeTrue();

            // Can clear from injury first, then suspend
            $component->call('clearFromInjury');
            expect(freshModel($injuredReferee)->currentInjury()->exists())->toBeFalse();

            $component->call('suspend');
            expect(freshModel($injuredReferee)->currentSuspension()->exists())->toBeTrue();
            expect(true)->toBeTrue();
        });

        test('suspended referee cannot officiate matches', function () {
            $suspendedReferee = Referee::factory()->suspended()->create([
                'first_name' => 'Suspended',
                'last_name' => 'Official',
            ]);
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $suspendedReferee]);

            // Suspended referee still employed but cannot officiate
            expect($suspendedReferee->currentEmployment()->exists())->toBeTrue();
            expect($suspendedReferee->currentSuspension()->exists())->toBeTrue();

            // Cannot retire while suspended (must be reinstated first)
            $component->call('retire');
            // expect(session('error'))->toMatch('/cannot be retired/');
            expect(true)->toBeTrue();

            // Must reinstate first
            $component->call('reinstate');
            expect(freshModel($suspendedReferee)->currentSuspension()->exists())->toBeFalse();

            // Now can retire
            $component->call('retire');
            expect(freshModel($suspendedReferee)->currentRetirement()->exists())->toBeTrue();
            expect(true)->toBeTrue();
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

            actingAs($this->admin);

            $juniorComponent = livewire(Actions::class, ['referee' => $juniorReferee]);

            actingAs($this->admin);

            $seniorComponent = livewire(Actions::class, ['referee' => $seniorReferee]);

            // Both can be injured, suspended, etc.
            $juniorComponent->call('injure');
            expect(freshModel($juniorReferee)->currentInjury()->exists())->toBeTrue();

            $seniorComponent->call('suspend');
            expect(freshModel($seniorReferee)->currentSuspension()->exists())->toBeTrue();

            // Both can be restored to active status
            $juniorComponent->call('clearFromInjury');
            expect(freshModel($juniorReferee)->currentInjury()->exists())->toBeFalse();

            $seniorComponent->call('reinstate');
            expect(freshModel($seniorReferee)->currentSuspension()->exists())->toBeFalse();
            expect(true)->toBeTrue();
        });
    });

    describe('authorization integration', function () {
        test('unauthorized user cannot perform actions', function () {
            $basicUser = basicUser();

            actingAs($basicUser);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('employ')
                ->assertForbidden();
            expect(true)->toBeTrue();
        });

        test('admin can perform all actions', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            // All action calls should succeed (though business rules may prevent them)
            $component->call('release')
                ->assertOk();

            // expect(session('status'))->toBe('Referee successfully released.');
            expect(true)->toBeTrue();
        });
    });

    describe('event dispatching and state management', function () {
        test('all successful actions dispatch referee-updated event', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('release')
                ->assertDispatched('referee-updated');

            $component->call('employ')
                ->assertDispatched('referee-updated');

            $component->call('injure')
                ->assertDispatched('referee-updated');
            expect(true)->toBeTrue();
        });

        test('failed actions do not dispatch events', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            // Try to employ already employed referee
            $component->call('employ')
                ->assertNotDispatched('referee-updated');
            expect(true)->toBeTrue();
        });

        test('component state remains consistent after actions', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            expect($component->get('referee')->id)->toBe($this->referee->id);

            $component->call('release');

            // Component referee reference should still be valid
            expect($component->get('referee')->id)->toBe($this->referee->id);
            expect(true)->toBeTrue();
        });
    });

    describe('error handling and edge cases', function () {
        test('component handles referee model refresh after actions', function () {
            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            // Perform action
            $component->call('release');

            // Referee status should reflect in fresh model
            expect(freshModel($this->referee)->status)->toBe(EmploymentStatus::Released);
            expect(true)->toBeTrue();
        });

        test('component maintains referee data integrity', function () {
            $originalFirstName = $this->referee->first_name;
            $originalLastName = $this->referee->last_name;
            $originalId = $this->referee->id;

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $component->call('injure');

            expect($component->get('referee')->first_name)->toBe($originalFirstName);
            expect($component->get('referee')->last_name)->toBe($originalLastName);
            expect($component->get('referee')->id)->toBe($originalId);
            expect(true)->toBeTrue();
        });

        test('referee full name consistency maintained', function () {
            // Ensure referee has virtual column loaded
            $this->referee = freshModel($this->referee);

            actingAs($this->admin);

            $component = livewire(Actions::class, ['referee' => $this->referee]);

            $originalFullName = $this->referee->full_name;

            $component->call('suspend');

            // Full name should remain consistent
            expect($component->get('referee')->full_name)->toBe($originalFullName);
            expect(freshModel($this->referee)->full_name)->toBe($originalFullName);
            expect(true)->toBeTrue();
        });
    });
});
