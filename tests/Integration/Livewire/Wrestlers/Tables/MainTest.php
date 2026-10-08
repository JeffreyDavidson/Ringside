<?php

declare(strict_types=1);

use App\Builders\Roster\IndividualBuilder;
use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Wrestlers\Wrestler;
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
            ->assertSeeHtml('wire:confirm="Remove Big Wrestler?"')
            ->assertSee('6\'6"')
            ->assertSee('300')
            ->assertSee('Test City, TX');
    });

    it('paginates wrestlers in a stable order with disjoint pages', function (): void {
        // Arrange
        foreach (range(1, 25) as $number) {
            Wrestler::factory()->create(['name' => sprintf('Wrestler %02d', $number)]);
        }
        DB::enableQueryLog();

        // Act
        $component = livewire(Main::class)->set('perPage', 10);
        $pageQueries = array_filter(
            array_column(DB::getQueryLog(), 'query'),
            fn (string $query): bool => str_contains($query, 'limit 10'),
        );
        $orderedPageQueries = array_filter(
            $pageQueries,
            fn (string $query): bool => str_contains($query, 'order by'),
        );

        // Assert
        expect($pageQueries)->not->toBeEmpty()
            ->and($orderedPageQueries)->toBe($pageQueries);
        $component
            ->assertSeeInOrder(['Wrestler 01', 'Wrestler 02', 'Wrestler 10'])
            ->assertDontSee('Wrestler 11');

        // Act
        $component->call('setPage', 2);

        // Assert
        $component
            ->assertSeeInOrder(['Wrestler 11', 'Wrestler 12', 'Wrestler 20'])
            ->assertDontSee('Wrestler 10')
            ->assertDontSee('Wrestler 21');
    });
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

    it('does not query availability per wrestler row', function (): void {
        // Arrange
        Wrestler::factory()->employed()->count(3)->create()->each(function (Wrestler $wrestler): void {
            Injury::factory()->for($wrestler, 'injurable')->create();
            Suspension::factory()->for($wrestler, 'suspendable')->create();
        });

        $wrestlers = (new Main)->builder()->withExists(IndividualBuilder::AVAILABILITY_STATE)->get();

        // Act
        DB::enableQueryLog();
        $wrestlers->each(fn (Wrestler $wrestler): bool => $wrestler->isInjured() && $wrestler->isSuspended());
        $queries = DB::getQueryLog();

        // Assert
        expect($queries)->toBeEmpty();
    });
});
