<?php

declare(strict_types=1);

use App\Actions\Managers\UpdateAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Livewire\Managers\Modals\FormModal;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

describe('authorized manager form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the manager form fields', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.managers.modals.form-modal');
        $modal
            ->assertPropertyWired('form.first_name')
            ->assertPropertyWired('form.last_name')
            ->assertPropertyWired('form.employment_date')
            ->assertSee('First Name')
            ->assertSee('Last Name');
    });

    it('opens an empty form for creating a manager', function () {
        $modal = livewire(FormModal::class);

        $modal
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', null)
            ->assertSee('Add Manager');
    });

    it('loads an existing manager for editing', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);
        $manager->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        $modal->set('form.first_name', 'Bobby');

        $modal
            ->assertSet('form.first_name', 'Bobby')
            ->assertSet('form.last_name', 'Heenan')
            ->assertSet('form.employment_date', null)
            ->assertSet('form.hasEmploymentHistory', true)
            ->assertDontSeeHtml('wire:model="form.employment_date"')
            ->assertSee('Edit Bobby Heenan');
    });

    it('responds not found when opening a missing manager', function () {
        livewire(FormModal::class, ['modelId' => PHP_INT_MAX])
            ->assertNotFound();
    });

    it('creates an employed manager', function () {
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.first_name' => 'Paul',
            'form.last_name' => 'Dangerously',
            'form.employment_date' => '2024-02-01',
        ]);
        $modal->call('save');

        $manager = Manager::query()
            ->where('first_name', 'Paul')
            ->where('last_name', 'Dangerously')
            ->firstOrFail();
        expect($manager->firstEmployment?->started_at?->toDateString())->toBe('2024-02-01');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
    });

    it('creates a manager without optional employment data', function () {
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.first_name' => 'Jimmy',
            'form.last_name' => 'Hart',
        ]);
        $modal->call('save');

        $manager = Manager::query()
            ->where('first_name', 'Jimmy')
            ->where('last_name', 'Hart')
            ->firstOrFail();
        expect($manager->firstEmployment)->toBeNull();
        $modal->assertHasNoErrors();
    });

    it('updates a manager without replacing current employment', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'James',
            'last_name' => 'Dillon',
        ]);
        $employment = $manager->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        $modal->set([
            'form.first_name' => 'J. J.',
            'form.last_name' => 'Dillon',
        ]);
        $modal->call('save');

        $manager->refresh();
        expect($manager->first_name)->toBe('J. J.')
            ->and($manager->last_name)->toBe('Dillon')
            ->and($manager->employments()->count())->toBe(1)
            ->and($manager->currentEmployment()->firstOrFail()->is($employment))->toBeTrue();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
    });

    it('rejects changing an active manager employment date', function () {
        $manager = Manager::factory()->create();
        $manager->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        $modal->set('form.employment_date', '2024-01-01');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.employment_date'])
            ->assertNotDispatched('closeModal');
    });

    it('requires both manager names', function () {
        $modal = livewire(FormModal::class);

        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.first_name' => 'required',
                'form.last_name' => 'required',
            ])
            ->assertNotDispatched('closeModal');
        expect(Manager::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid manager field values', function (string $case) {
        [$field, $value, $rule] = match ($case) {
            'long first name' => ['form.first_name', str_repeat('a', 256), 'max'],
            'long last name' => ['form.last_name', str_repeat('a', 256), 'max'],
            'invalid employment date' => ['form.employment_date', 'not-a-date', 'date'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.first_name' => 'Valid',
            'form.last_name' => 'Manager',
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$field => $rule]);
        expect(Manager::query()->doesntExist())->toBeTrue();
    })->with([
        'long first name',
        'long last name',
        'invalid employment date',
    ]);

    it('generates valid dummy data that can create a manager', function () {
        $modal = livewire(FormModal::class);

        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
        expect(Manager::query()->count())->toBe(1);
    });
});

describe('Manager form employment history', function () {
    beforeEach(function () {
        actingAs(administrator());
        travelTo(Date::parse('2024-06-01 12:00:00'));
    });

    it('keeps the employment history of a released, retired or future-employed manager when only the name changes', function (
        string $state,
        ?string $submittedDate,
    ) {
        // Arrange
        $manager = Manager::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);
        giveEmploymentHistory($manager, $state);
        $employmentsBefore = employmentSnapshot($manager);
        $statusBefore = $manager->fresh()?->status;
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        // Act
        $modal->set('form.first_name', 'Renamed');
        $modal->set('form.employment_date', $submittedDate);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('closeModal');
        $manager->refresh();
        expect($manager->first_name)->toBe('Renamed')
            ->and(employmentSnapshot($manager))->toBe($employmentsBefore)
            ->and($manager->status)->toBe($statusBefore);
    })->with([
        'released' => ['released', null],
        'retired' => ['retired', null],
        'future-employed' => ['future', null],
        'released with a submitted date' => ['released', '2024-04-01'],
        'retired with a submitted date' => ['retired', '2024-04-01'],
        'future-employed with a submitted date' => ['future', '2024-04-01'],
    ]);

    it('does not offer the employment date once the manager has an employment history', function (string $state) {
        // Arrange
        $manager = Manager::factory()->create();
        giveEmploymentHistory($manager, $state);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        // Act

        // Assert
        $modal
            ->assertSet('form.hasEmploymentHistory', true)
            ->assertSet('form.employment_date', null)
            ->assertDontSeeHtml('wire:model="form.employment_date"');
    })->with(['released', 'retired', 'future']);

    it('still employs a never-employed manager from the submitted date', function () {
        // Arrange
        $manager = Manager::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        // Act
        $modal->set('form.first_name', 'Renamed');
        $modal->set('form.employment_date', '2024-02-01');
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        $manager->refresh();
        expect($manager->first_name)->toBe('Renamed')
            ->and($manager->status)->toBe(EmploymentStatus::Employed)
            ->and(employmentSnapshot($manager))->toBe([[
                'id' => $manager->employments()->firstOrFail()->id,
                'started_at' => '2024-02-01',
                'ended_at' => null,
            ]]);
    });

    it('shows a business rule failure on the first name field instead of failing the request', function () {
        // Arrange
        $manager = Manager::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);
        $action = Double::for(UpdateAction::class);
        $action->expects('handle')->throws(CannotBeEmployedException::retired($manager));
        app()->instance(UpdateAction::class, $action);
        $modal = livewire(FormModal::class, ['modelId' => $manager->id]);

        // Act
        $modal->set('form.first_name', 'Renamed');
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.first_name'])
            ->assertNotDispatched('closeModal')
            ->assertNotDispatched('refreshDatatable');
        expect($manager->fresh()?->first_name)->toBe('Original');
        $action->verify();
    });
});

it('forbids users without administrative access from opening the manager form', function (string $actor, string $operation, int $status) {
    $manager = $operation === 'update' ? Manager::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    livewire(FormModal::class, ['modelId' => $manager?->id])
        ->assertStatus($status);
})->with([
    'guest creating' => ['guest', 'create', 403],
    'basic user creating' => ['basic user', 'create', 403],
    'guest updating' => ['guest', 'update', 403],
    'basic user updating' => ['basic user', 'update', 404],
]);
