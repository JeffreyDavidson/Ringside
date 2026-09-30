<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('wrestlers table', function (): void {
    it('renders the configured table controls and wrestler attributes', function (): void {
        // Arrange
        Wrestler::factory()->create([
            'name' => 'Big Wrestler',
            'height' => 78,
            'weight' => 300,
            'hometown' => 'Test City, TX',
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Add Wrestler')
            ->assertSeeHtml('placeholder="Search wrestlers"')
            ->assertSee('Big Wrestler')
            ->assertSee('6\'6"')
            ->assertSee('300')
            ->assertSee('Test City, TX');
    });

    it('filters wrestlers by name and clears the search', function (): void {
        // Arrange
        Wrestler::factory()->create(['name' => 'John Cena']);
        Wrestler::factory()->create(['name' => 'The Rock']);

        $component = livewire(Main::class);

        // Act
        $component->set('search', 'John');

        // Assert
        $component
            ->assertSee('John Cena')
            ->assertDontSee('The Rock');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('John Cena')
            ->assertSee('The Rock');
    });

    it('filters wrestlers by employment status', function (EmploymentStatus $status): void {
        // Arrange
        $visibleWrestler = match ($status) {
            EmploymentStatus::Employed => Wrestler::factory()->employed()->create(['name' => 'Matching Wrestler']),
            EmploymentStatus::Released => Wrestler::factory()->released()->create(['name' => 'Matching Wrestler']),
            EmploymentStatus::Unemployed => Wrestler::factory()->unemployed()->create(['name' => 'Matching Wrestler']),
            EmploymentStatus::Retired => Wrestler::factory()->retired()->create(['name' => 'Matching Wrestler']),
            EmploymentStatus::FutureEmployment => Wrestler::factory()->withFutureEmployment()->create(['name' => 'Matching Wrestler']),
        };
        $hiddenWrestler = $status === EmploymentStatus::Employed
            ? Wrestler::factory()->released()->create(['name' => 'Hidden Wrestler'])
            : Wrestler::factory()->employed()->create(['name' => 'Hidden Wrestler']);

        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee($visibleWrestler->name)
            ->assertDontSee($hiddenWrestler->name);
    })->with(EmploymentStatus::cases());

    it('loads the employment state used by the table', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->employed()->create();

        // Act
        $loadedWrestler = app(Main::class)->builder()->findOrFail($wrestler->id);

        // Assert
        expect($loadedWrestler->relationLoaded('firstEmployment'))->toBeTrue()
            ->and($loadedWrestler->status)->toBe(EmploymentStatus::Employed);
    });

    it('renders updated wrestler data after a refresh', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Original Wrestler']);
        $component = livewire(Main::class);
        $component->assertSee('Original Wrestler');
        $wrestler->update(['name' => 'Updated Wrestler']);

        // Act
        $component->call('$refresh');

        // Assert
        $component
            ->assertSee('Updated Wrestler')
            ->assertDontSee('Original Wrestler');
    });

    it('forbids users without wrestler access', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});

describe('wrestlers table metadata', function (): void {
    it('counts derived employment statuses through the table status filter', function (): void {
        // Arrange
        Wrestler::factory()->employed()->create();
        Wrestler::factory()->released()->create();
        Wrestler::factory()->unemployed()->create();

        Wrestler::factory()->employed()->trashed()->create();

        $table = new Main;

        // Act
        $metadata = $table->metadata();

        // Assert
        $statuses = collect($metadata['statuses'])->keyBy('value');

        expect($metadata['total'])->toBe(3)
            ->and($statuses['employed'])->toBe([
                'value' => 'employed',
                'label' => 'Employed',
                'count' => 1,
            ])
            ->and($statuses['released'])->toBe([
                'value' => 'released',
                'label' => 'Released',
                'count' => 1,
            ])
            ->and($statuses['unemployed'])->toBe([
                'value' => 'unemployed',
                'label' => 'Unemployed',
                'count' => 1,
            ]);
    });

    it('labels injured and suspended wrestlers without changing their employment status', function (bool $injured, bool $suspended): void {
        // Arrange
        $wrestler = Wrestler::factory()->employed()->create(['name' => 'Availability Wrestler']);

        if ($injured) {
            Injury::factory()->for($wrestler, 'injurable')->create();
        }

        if ($suspended) {
            Suspension::factory()->for($wrestler, 'suspendable')->create();
        }

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSee('Employed');

        expect($component->html())
            ->when($injured, fn ($html) => $html->toContain('data-test="availability-injured"'))
            ->unless($injured, fn ($html) => $html->not->toContain('data-test="availability-injured"'))
            ->when($suspended, fn ($html) => $html->toContain('data-test="availability-suspended"'))
            ->unless($suspended, fn ($html) => $html->not->toContain('data-test="availability-suspended"'));
    })->with([
        'injured' => [true, false],
        'suspended' => [false, true],
        'injured and suspended' => [true, true],
        'available' => [false, false],
    ]);

    it('does not query availability per wrestler row', function (): void {
        // Arrange
        Wrestler::factory()->employed()->count(3)->create()->each(function (Wrestler $wrestler): void {
            Injury::factory()->for($wrestler, 'injurable')->create();
            Suspension::factory()->for($wrestler, 'suspendable')->create();
        });

        $wrestlers = (new Main)->builder()->withAvailabilityState()->get();

        // Act
        DB::enableQueryLog();
        $wrestlers->each(fn (Wrestler $wrestler): bool => $wrestler->isInjured() && $wrestler->isSuspended());
        $queries = DB::getQueryLog();

        // Assert
        expect($queries)->toBeEmpty();
    });
});
