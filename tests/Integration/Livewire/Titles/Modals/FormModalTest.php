<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Livewire\Titles\Modals\FormModal;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized title form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the title fields and type choices', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.titles.modals.form-modal');
        $modal
            ->assertPropertyWired('form.name')
            ->assertPropertyWired('form.type')
            ->assertPropertyWired('form.start_date')
            ->assertSee(TitleType::Singles->label())
            ->assertSee(TitleType::TagTeam->label());
    });

    it('opens an empty form for creating a title', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', '')
            ->assertSet('form.type', '')
            ->assertSet('form.start_date', '')
            ->assertSee('Create Title');
    });

    it('loads an existing title for editing', function () {
        $title = Title::factory()->singles()->create(['name' => 'World Championship Title']);
        $title->activityPeriods()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', 'World Championship Title')
            ->assertSet('form.type', TitleType::Singles->value)
            ->assertSet('form.start_date', '2024-01-15')
            ->assertSee('Edit Title');
    });

    it('propagates a missing title failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates and debuts a singles title', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'World Championship Title',
            'form.type' => TitleType::Singles->value,
            'form.start_date' => '2024-02-01',
        ]);
        $modal->call('save');

        $title = Title::query()->whereName('World Championship Title')->firstOrFail();
        expect($title->type)->toBe(TitleType::Singles)
            ->and($title->firstActivityPeriod?->started_at->toDateString())->toBe('2024-02-01');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates an undebuted tag team title', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'World Tag Team Titles',
            'form.type' => TitleType::TagTeam->value,
        ]);
        $modal->call('save');

        $title = Title::query()->whereName('World Tag Team Titles')->firstOrFail();
        expect($title->type)->toBe(TitleType::TagTeam)
            ->and($title->activityPeriods()->doesntExist())->toBeTrue();
        $modal->assertHasNoErrors();
    });

    it('updates a title while preserving its existing activity period', function () {
        $title = Title::factory()->singles()->create(['name' => 'Original Championship Title']);
        $activityPeriod = $title->activityPeriods()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);
        $modal->set([
            'form.name' => 'Updated Tag Team Titles',
            'form.type' => TitleType::TagTeam->value,
        ]);
        $modal->call('save');

        $title->refresh();
        expect($title->name)->toBe('Updated Tag Team Titles')
            ->and($title->type)->toBe(TitleType::TagTeam)
            ->and($title->activityPeriods()->count())->toBe(1)
            ->and($title->currentActivityPeriod()->firstOrFail()->is($activityPeriod))->toBeTrue();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false);
    });

    it('requires a title name and type', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.name' => 'required',
                'form.type' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(Title::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid title field values', function (string $case) {
        [$field, $value, $rule] = match ($case) {
            'long name' => ['form.name', str_repeat('a', 256).' Title', 'max'],
            'invalid suffix' => ['form.name', 'World Championship', 'ends_with'],
            'invalid type' => ['form.type', 'trios', null],
            'invalid debut date' => ['form.start_date', 'not-a-date', 'date'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Valid Championship Title',
            'form.type' => TitleType::Singles->value,
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        if ($rule === null) {
            $modal->assertHasErrors([$field]);
        } else {
            $modal->assertHasErrors([$field => $rule]);
        }
        expect(Title::query()->doesntExist())->toBeTrue();
    })->with([
        'long name',
        'invalid suffix',
        'invalid type',
        'invalid debut date',
    ]);

    it('rejects a title name already used by another title', function () {
        Title::factory()->singles()->create(['name' => 'Existing Championship Title']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Existing Championship Title',
            'form.type' => TitleType::Singles->value,
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.name' => 'unique']);
        expect(Title::query()->count())->toBe(1);
    });

    it('allows a title to retain its current name', function () {
        $title = Title::factory()->singles()->create(['name' => 'Current Championship Title']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect($title->refresh()->name)->toBe('Current Championship Title');
    });

    it('rejects another title name while editing', function () {
        Title::factory()->singles()->create(['name' => 'Existing Championship Title']);
        $title = Title::factory()->singles()->create(['name' => 'Current Championship Title']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);
        $modal->set('form.name', 'Existing Championship Title');
        $modal->call('save');

        $modal->assertHasErrors(['form.name' => 'unique']);
        expect($title->refresh()->name)->toBe('Current Championship Title');
    });

    it('prevents changing the debut date of an active title', function () {
        $title = Title::factory()->singles()->create(['name' => 'Active Championship Title']);
        $title->activityPeriods()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);
        $modal->set('form.start_date', '2024-02-01');
        $modal->call('save');

        $modal->assertHasErrors(['form.start_date']);
        expect($title->firstActivityPeriod?->started_at->toDateString())->toBe('2024-01-15');
    });

    it('resets edited title data when reopening in create mode', function () {
        $title = Title::factory()->active()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $title->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.type', '')
            ->assertSet('form.start_date', '');
    });

    it('generates valid dummy data that can create a title', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
        expect(Title::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the title form', function (string $actor, string $operation) {
    $title = $operation === 'update' ? Title::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $title?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
