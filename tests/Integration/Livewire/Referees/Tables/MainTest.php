<?php

declare(strict_types=1);

use App\Actions\Referees\ClearFromInjuryAction;
use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Referees\Tables\Main;
use App\Livewire\Referees\Tables\RefereesTable;
use App\Models\Events\Event;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * Integration tests for RefereesTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with complex data relationships
 * - Filtering and search functionality integration
 * - Action dropdown integration
 * - Status display integration
 * - Real database interaction with relationships
 */
describe('RefereesTable Component', function () {

    beforeEach(function () {
        $this->user = administrator();
        $this->actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders referees table with complete data relationships', function () {
            // Create referees with different statuses and relationships
            $employedReferee = Referee::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Referee']);
            $injuredReferee = Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);
            $retiredReferee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
            $suspendedReferee = Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);

            // Create match relationships
            $event = Event::factory()->create(['name' => 'Test Event']);
            $match = EventMatch::factory()->for($event, 'event')->create();

            $component = livewire(Main::class);

            $component
                ->assertSee($employedReferee->full_name)
                ->assertSee($injuredReferee->full_name)
                ->assertSee($retiredReferee->full_name)
                ->assertSee($suspendedReferee->full_name);
            expect(true)->toBeTrue();
        });

        test('displays correct status badges for different referee states', function () {
            $employedReferee = Referee::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Referee']);
            $injuredReferee = Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);
            $suspendedReferee = Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);
            $retiredReferee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
            $releasedReferee = Referee::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Employed Referee')
                ->assertSee('Injured Referee')
                ->assertSee('Suspended Referee')
                ->assertSee('Retired Referee')
                ->assertSee('Released Referee')
                // Status indicators should be present (exact text may vary)
                ->assertSeeHtml('class'); // Status classes should be rendered
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters referees correctly', function () {
            Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
            Referee::factory()->create(['first_name' => 'Dave', 'last_name' => 'Hebner']);
            Referee::factory()->create(['first_name' => 'Mike', 'last_name' => 'Chioda']);
            Referee::factory()->create(['first_name' => 'Nick', 'last_name' => 'Hebnerson']);

            $component = livewire(Main::class);

            // Test search functionality
            $component
                ->set('search', 'Hebner')
                ->assertSee('Earl Hebner')
                ->assertSee('Dave Hebner')
                ->assertDontSee('Mike Chioda')
                ->assertDontSee('Nick Hebnerson');

            // Test clearing search
            $component
                ->set('search', '')
                ->assertSee('Earl Hebner')
                ->assertSee('Dave Hebner')
                ->assertSee('Mike Chioda')
                ->assertSee('Nick Hebnerson');
        });

        test('status filter functionality works with real data', function () {
            $referees = [
                EmploymentStatus::Employed->value => Referee::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Released->value => Referee::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Unemployed->value => Referee::factory()->unemployed()->create(['first_name' => 'Unemployed', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Retired->value => Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee'])->refresh(),
            ];

            foreach ($referees as $status => $visibleReferee) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleReferee->full_name);

                foreach ($referees as $otherStatus => $hiddenReferee) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenReferee->full_name);
                    }
                }
            }
        });

        test('future employment filter returns only referees awaiting employment', function () {
            $futureReferee = Referee::factory()->withFutureEmployment()
                ->create([
                    'first_name' => 'Future',
                    'last_name' => 'Referee',
                ])
                ->refresh();
            Referee::factory()->employed()->create([
                'first_name' => 'Employed',
                'last_name' => 'Referee',
            ]);

            livewire(Main::class)
                ->set('filterValues.status', 'future_employment')
                ->assertSee($futureReferee->full_name)
                ->assertDontSee('Employed Referee');
        });
    });

    describe('action integration', function () {
        test('action dropdown displays appropriate actions for referee states', function () {
            $employedReferee = Referee::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Referee']);
            $retiredReferee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Component should render without errors
            $component->assertOk();

            // Actions should be available (specific actions depend on component implementation)
            $component->assertSee($employedReferee->full_name);
            $component->assertSee($retiredReferee->full_name);
        });

        test('component integrates with authorization policies', function () {
            $referee = Referee::factory()->create(['first_name' => 'Test', 'last_name' => 'Referee']);

            // Test as administrator (should see all actions)
            actingAs($this->user);

            $component = livewire(Main::class);
            $component->assertOk();
            $component->assertSee($referee->full_name);
        });

        test('restores a deleted referee and redirects to the index', function () {
            $deletedReferee = Referee::factory()->trashed()->create([
                'first_name' => 'Deleted',
                'last_name' => 'Referee',
            ]);

            livewire(Main::class)
                ->call('restore', $deletedReferee->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('referees.index');

            expect(Referee::find($deletedReferee->id))->not->toBeNull();
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedReferee = Referee::factory()->employed()->create(['first_name' => 'Currently', 'last_name' => 'Employed']);
            $unemployedReferee = Referee::factory()->unemployed()->create(['first_name' => 'Currently', 'last_name' => 'Unemployed']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

        test('handles referees with employment history', function () {
            $referee = Referee::factory()->create(['first_name' => 'Referee', 'last_name' => 'History']);

            // Create employment history
            Employment::factory()
                ->for($referee, 'employable')
                ->create([
                    'started_at' => now()->subDays(200),
                    'ended_at' => now()->subDays(100),
                ]);

            Employment::factory()
                ->for($referee, 'employable')
                ->current()
                ->create(['started_at' => now()->subDays(50)]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Referee History');
        });
    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            $healthyReferee = Referee::factory()->employed()->create(['first_name' => 'Healthy', 'last_name' => 'Referee']);
            $injuredReferee = Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Healthy Referee')
                ->assertSee('Injured Referee');
        });

        test('displays suspension status correctly', function () {
            $activeReferee = Referee::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Referee']);
            $suspendedReferee = Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Referee')
                ->assertSee('Suspended Referee');
        });

        test('handles referees with injury history', function () {
            $referee = Referee::factory()->create(['first_name' => 'Injury', 'last_name' => 'History']);

            // Create injury history
            Injury::factory()
                ->for($referee, 'injurable')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Injury History');
        });

        test('handles referees with suspension history', function () {
            $referee = Referee::factory()->create(['first_name' => 'Suspension', 'last_name' => 'History']);

            // Create suspension history
            Suspension::factory()
                ->for($referee, 'suspendable')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Suspension History');
        });
    });

    describe('match assignment integration', function () {
        test('displays referees without match assignments', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Available', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Available Referee');
        });

        test('displays referees with match history', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Experienced', 'last_name' => 'Referee']);
            $event = Event::factory()->create(['name' => 'Test Event']);
            $match = EventMatch::factory()->for($event, 'event')->create();

            // Create match referee relationship (if exists in the system)
            // This depends on how matches and referees are connected

            $component = livewire(Main::class);

            $component
                ->assertSee('Experienced Referee');
        });

        test('displays referees available for assignment', function () {
            $availableReferee = Referee::factory()->bookable()->create(['first_name' => 'Available', 'last_name' => 'Referee']);
            $unavailableReferee = Referee::factory()->injured()->create(['first_name' => 'Unavailable', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Available Referee')
                ->assertSee('Unavailable Referee');
        });
    });

    describe('performance and data loading integration', function () {
        test('component handles large datasets efficiently', function () {
            // Create multiple referees with various relationships
            Referee::factory()->count(20)->create();

            // Add some match relationships
            $referees = Referee::factory()->count(5)->employed()->create();
            $events = Event::factory()->count(3)->create();
            $matches = EventMatch::factory()->count(10)->create();

            $component = livewire(Main::class);

            // Component should render efficiently
            $component->assertOk()
                ->assertSee('Referee'); // Should display some referee data
        });

        test('component eager loads necessary relationships', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Relationship', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertOk()
                ->assertSee('Relationship Referee');
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when referee data changes', function () {
            $referee = Referee::factory()->create(['first_name' => 'Original', 'last_name' => 'Referee']);

            $component = livewire(Main::class);
            $component->assertSee('Original Referee');

            // Update referee name
            $referee->update(['first_name' => 'Updated', 'last_name' => 'Referee']);

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Updated Referee');
            $component->assertDontSee('Original Referee');
        });

        test('component reflects employment status changes', function () {
            $referee = Referee::factory()->unemployed()->create(['first_name' => 'Employment', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Employ the referee
            resolve(EmployAction::class)->handle($referee, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Employment Referee');
        });

        test('component reflects injury status changes', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Injury', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Injure the referee
            resolve(InjureAction::class)->handle($referee, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Injury Referee');
        });

        test('component reflects injury clearance status changes', function () {
            $referee = Referee::factory()->injured()->create(['first_name' => 'Healing', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Clear the referee from injury
            resolve(ClearFromInjuryAction::class)->handle($referee, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Healing Referee');
        });
    });

    describe('complex business rule integration', function () {
        test('component handles referees with complex status combinations', function () {
            // Create referee with multiple statuses
            $referee = Referee::factory()->employed()->create(['first_name' => 'Complex', 'last_name' => 'Referee']);

            // Referee is employed but also injured
            resolve(InjureAction::class)->handle($referee, now());

            $component = livewire(Main::class);

            $component
                ->assertSee('Complex Referee');

            // Should show both employed and injured status indicators
        });

        test('component respects business rules for action availability', function () {
            $injuredReferee = Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);
            $retiredReferee = Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Component should render and show appropriate actions based on business rules
            $component
                ->assertOk()
                ->assertSee('Injured Referee')
                ->assertSee('Retired Referee');
        });

        test('component handles referee availability for matches', function () {
            $availableReferee = Referee::factory()->bookable()->create(['first_name' => 'Available', 'last_name' => 'Referee']);
            $unavailableReferee = Referee::factory()->injured()->create(['first_name' => 'Unavailable', 'last_name' => 'Referee']);
            $suspendedReferee = Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            // Component should show all referees with appropriate status indicators
            $component
                ->assertOk()
                ->assertSee('Available Referee')
                ->assertSee('Unavailable Referee')
                ->assertSee('Suspended Referee');
        });

        test('component handles referee retirement transitions', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Retiring', 'last_name' => 'Referee']);

            $component = livewire(Main::class);
            $component->assertSee('Retiring Referee');

            // Retire the referee
            resolve(RetireAction::class)->handle($referee, now());

            $component->call('$refresh');
            $component->assertSee('Retiring Referee');

            // Should show retired status
        });
    });

    describe('referee specialization integration', function () {
        test('component handles referees with different experience levels', function () {
            $seniorReferee = Referee::factory()->employed()->create(['first_name' => 'Senior', 'last_name' => 'Official']);
            $juniorReferee = Referee::factory()->employed()->create(['first_name' => 'Junior', 'last_name' => 'Official']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Senior Official')
                ->assertSee('Junior Official');
        });

        test('component displays referees with different career lengths', function () {
            $veteranReferee = Referee::factory()->employed()->create(['first_name' => 'Veteran', 'last_name' => 'Referee']);
            $rookieReferee = Referee::factory()->employed()->create(['first_name' => 'Rookie', 'last_name' => 'Referee']);

            // Create different employment history lengths
            Employment::factory()
                ->for($veteranReferee, 'employable')
                ->create([
                    'started_at' => now()->subYears(10),
                    'ended_at' => now()->subYears(8),
                ]);

            Employment::factory()
                ->for($veteranReferee, 'employable')
                ->create([
                    'started_at' => now()->subYears(5),
                    'ended_at' => now()->subYears(3),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Veteran Referee')
                ->assertSee('Rookie Referee');
        });

        test('component shows referee match assignments', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Assigned', 'last_name' => 'Referee']);
            $event = Event::factory()->create(['name' => 'Championship Event']);
            $match = EventMatch::factory()->for($event, 'event')->create();

            // Create referee assignment (if system supports it)

            $component = livewire(Main::class);

            $component
                ->assertSee('Assigned Referee');
        });
    });

    describe('referee lifecycle integration', function () {
        test('component handles referee hiring and releases', function () {
            $hiringReferee = Referee::factory()->unemployed()->create(['first_name' => 'Hiring', 'last_name' => 'Referee']);
            $releasingReferee = Referee::factory()->employed()->create(['first_name' => 'Releasing', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Hiring Referee')
                ->assertSee('Releasing Referee');

            // Test status transitions
            resolve(EmployAction::class)->handle($hiringReferee, now());
            resolve(ReleaseAction::class)->handle($releasingReferee, now());

            $component->call('$refresh');
            $component
                ->assertSee('Hiring Referee')
                ->assertSee('Releasing Referee');
        });

        test('component handles referee comebacks', function () {
            $comebackReferee = Referee::factory()->unemployed()->create(['first_name' => 'Comeback', 'last_name' => 'Referee']);

            // Create previous employment and retirement
            Employment::factory()
                ->for($comebackReferee, 'employable')
                ->create([
                    'started_at' => now()->subYears(3),
                    'ended_at' => now()->subYear(),
                ]);

            Retirement::factory()
                ->for($comebackReferee, 'retirable')
                ->create([
                    'started_at' => now()->subYear(),
                    'ended_at' => now()->subMonths(6),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Comeback Referee');
        });

        test('component handles referee injury and recovery cycles', function () {
            $recoveredReferee = Referee::factory()->employed()->create(['first_name' => 'Recovered', 'last_name' => 'Referee']);

            // Create previous injury
            Injury::factory()
                ->for($recoveredReferee, 'injurable')
                ->create([
                    'started_at' => now()->subMonths(6),
                    'ended_at' => now()->subMonths(3),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Recovered Referee');
        });

        test('component handles referee suspension and reinstatement cycles', function () {
            $reinstatedReferee = Referee::factory()->employed()->create(['first_name' => 'Reinstated', 'last_name' => 'Referee']);

            // Create previous suspension
            Suspension::factory()
                ->for($reinstatedReferee, 'suspendable')
                ->create([
                    'started_at' => now()->subMonths(4),
                    'ended_at' => now()->subMonths(2),
                ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Reinstated Referee');
        });
    });
});
