<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Table;

use App\Enums\Users\Role;
use App\Models\Users\User;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Livewire\livewire;

describe('data table component', function (): void {
    test('components can declare additional columns through the base extension point', function (): void {
        User::factory()->create();

        // Act
        $component = livewire(TestDataTableComponent::class);

        // Assert
        $component->assertSee('Created At');
    });

    test('sorting accepts only declared sortable columns', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Zulu', 'email' => 'a@example.com']);
        User::factory()->create(['first_name' => 'Alpha', 'email' => 'z@example.com']);
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->call('sort', 'email');

        // Assert
        $component
            ->assertSet('sortField', '')
            ->assertSee('Zulu')
            ->assertSee('Alpha');

        // Act
        $component->call('sort', 'first_name');

        // Assert
        $component
            ->assertSet('sortField', 'first_name')
            ->assertSet('sortDirection', 'asc')
            ->assertSeeInOrder(['Alpha', 'Zulu']);

        // Act
        $component->call('sort', 'first_name');

        // Assert
        $component
            ->assertSet('sortDirection', 'desc')
            ->assertSeeInOrder(['Zulu', 'Alpha']);
    });

    test('sortable headers announce their sort state', function (): void {
        // Arrange
        User::factory()->create();
        $component = livewire(TestDataTableComponent::class);
        $headers = function () use ($component): array {
            $document = HTMLDocument::createFromString("<!DOCTYPE html><body>{$component->html()}</body>", LIBXML_NOERROR);
            $nameHeader = $document->querySelector('th[aria-sort]');

            return [
                'sort' => $nameHeader?->getAttribute('aria-sort'),
                'buttonType' => $nameHeader?->querySelector('button')?->getAttribute('type'),
                'decorativeIcons' => count($nameHeader?->querySelectorAll('svg:not([aria-hidden="true"])') ?? []),
                'sortableHeaders' => count($document->querySelectorAll('th[aria-sort]')),
            ];
        };

        // Assert
        expect($headers())->toBe(['sort' => 'none', 'buttonType' => 'button', 'decorativeIcons' => 0, 'sortableHeaders' => 1]);

        // Act
        $component->call('sort', 'first_name');

        // Assert
        expect($headers())->toBe(['sort' => 'ascending', 'buttonType' => 'button', 'decorativeIcons' => 0, 'sortableHeaders' => 1]);

        // Act
        $component->call('sort', 'first_name');

        // Assert
        expect($headers())->toBe(['sort' => 'descending', 'buttonType' => 'button', 'decorativeIcons' => 0, 'sortableHeaders' => 1]);
    });

    test('the loading toast is a translated status message', function (): void {
        // Act
        $component = livewire(TestDataTableComponent::class);

        // Assert
        $document = HTMLDocument::createFromString("<!DOCTYPE html><body>{$component->html()}</body>", LIBXML_NOERROR);
        $toast = $document->querySelector('[data-test=table-updating-status]');

        expect($toast?->getAttribute('role'))->toBe('status')
            ->and(trim((string) $toast?->textContent))->toBe(__('core.updating'));
    });

    test('hydrated sorting state is normalized before querying', function (): void {
        // Arrange
        $user = User::factory()->create(['first_name' => 'Surviving User']);
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->set('sortField', 'first_name; drop table users');

        // Assert
        $component
            ->assertSet('sortField', '')
            ->assertSet('sortDirection', 'asc')
            ->assertSee('Surviving User');
        $this->assertModelExists($user);
    });

    test('per page values are restricted to configured options', function (): void {
        // Arrange
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->set('perPage', 999);

        // Assert
        $component->assertSet('perPage', 5);

        // Act
        $component->set('perPage', 25);

        // Assert
        $component->assertSet('perPage', 25);
    });
});

describe('data table pagination', function (): void {
    beforeEach(function (): void {
        User::factory()->count(6)->sequence(
            fn (Sequence $sequence): array => ['first_name' => "Member {$sequence->index}"],
        )->create();
    });

    test('page navigation renders only the selected rows', function (): void {
        // Arrange
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->call('sort', 'first_name');
        $component->set('perPage', 5);

        // Assert
        $component
            ->assertSee('Member 0')
            ->assertSee('Member 4')
            ->assertDontSee('Member 5');

        // Act
        $component->call('setPage', 2);

        // Assert
        $component
            ->assertSee('Member 5')
            ->assertDontSee('Member 0')
            ->assertDontSee('Member 4');
    });

    test('changing page size returns to the first page', function (): void {
        // Arrange
        $component = livewire(TestDataTableComponent::class);
        $component->call('sort', 'first_name');
        $component->set('perPage', 5);
        $component->call('setPage', 2);
        $component->assertSet('paginators.page', 2);

        // Act
        $component->set('perPage', 10);

        // Assert
        $component
            ->assertSet('paginators.page', 1)
            ->assertSee('Member 0')
            ->assertSee('Member 5');
    });

    test('searching returns to the first page of matching rows', function (): void {
        // Arrange
        $component = livewire(TestDataTableComponent::class);
        $component->call('sort', 'first_name');
        $component->set('perPage', 5);
        $component->call('setPage', 2);
        $component->assertSet('paginators.page', 2);

        // Act
        $component->set('search', 'Member 5');

        // Assert
        $component
            ->assertSet('paginators.page', 1)
            ->assertSee('Member 5')
            ->assertDontSee('Member 0');
    });
});

describe('data table filtering', function (): void {
    test('empty search results explain the query and can be cleared', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Searchable User']);
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->set('search', 'no matching record');

        // Assert
        $component
            ->assertSeeHtml('data-test="records-empty-state"')
            ->assertSee(__('core.no_results_title'))
            ->assertSee(__('core.no_results_description'))
            ->assertSee('Clear search')
            ->assertDontSee('Searchable User');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Searchable User')
            ->assertDontSee('Clear search');
    });

    test('matches either searchable column without bypassing the selected filter', function (): void {
        // Arrange
        User::factory()->administrator()->create([
            'first_name' => 'Needle Administrator',
            'email' => 'name-match@example.com',
        ]);
        User::factory()->administrator()->create([
            'first_name' => 'Email Administrator',
            'email' => 'needle-admin@example.com',
        ]);
        User::factory()->basicUser()->create([
            'first_name' => 'Needle Basic',
            'email' => 'basic-name@example.com',
        ]);
        User::factory()->basicUser()->create([
            'first_name' => 'Email Basic',
            'email' => 'needle-basic@example.com',
        ]);
        User::factory()->administrator()->create([
            'first_name' => 'Unrelated Administrator',
            'email' => 'unrelated@example.com',
        ]);
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->set('filterValues.role', Role::Administrator->value);
        $component->set('search', 'needle');

        // Assert
        $component
            ->assertSee('Needle Administrator')
            ->assertSee('Email Administrator')
            ->assertDontSee('Needle Basic')
            ->assertDontSee('Email Basic')
            ->assertDontSee('Unrelated Administrator');
    });

    test('filters combine with search and sorting', function (): void {
        // Arrange
        User::factory()->administrator()->create(['first_name' => 'Matching Zulu']);
        User::factory()->administrator()->create(['first_name' => 'Matching Alpha']);
        User::factory()->administrator()->create(['first_name' => 'Unrelated Administrator']);
        User::factory()->basicUser()->create(['first_name' => 'Matching Basic']);
        $component = livewire(TestDataTableComponent::class);

        // Act
        $component->set('search', 'Matching');
        $component->set('filterValues.role', Role::Administrator->value);
        $component->call('sort', 'first_name');

        // Assert
        $component
            ->assertSeeInOrder(['Matching Alpha', 'Matching Zulu'])
            ->assertDontSee('Matching Basic')
            ->assertDontSee('Unrelated Administrator');
    });

    test('changing a filter returns to the first page of matching rows', function (): void {
        // Arrange
        User::factory()->basicUser()->count(5)->sequence(
            fn (Sequence $sequence): array => ['first_name' => "Basic {$sequence->index}"],
        )->create();
        User::factory()->administrator()->create(['first_name' => 'Zulu Administrator']);
        $component = livewire(TestDataTableComponent::class);
        $component->call('sort', 'first_name');
        $component->set('perPage', 5);
        $component->call('setPage', 2);
        $component->assertSet('paginators.page', 2);

        // Act
        $component->set('filterValues.role', Role::Administrator->value);

        // Assert
        $component
            ->assertSet('paginators.page', 1)
            ->assertSee('Zulu Administrator')
            ->assertDontSee('Basic 0');
    });

    test('clearing a filter restores matching rows without clearing the search', function (): void {
        // Arrange
        User::factory()->administrator()->create(['first_name' => 'Matching Administrator']);
        User::factory()->basicUser()->create(['first_name' => 'Matching Basic']);
        User::factory()->basicUser()->create(['first_name' => 'Unrelated Basic']);
        $component = livewire(TestDataTableComponent::class);
        $component->set('search', 'Matching');
        $component->set('filterValues.role', Role::Administrator->value);
        $component->assertDontSee('Matching Basic');

        // Act
        $component->set('filterValues.role', '');

        // Assert
        $component
            ->assertSet('search', 'Matching')
            ->assertSee('Matching Administrator')
            ->assertSee('Matching Basic')
            ->assertDontSee('Unrelated Basic');
    });
});

describe('data table without searchable columns', function (): void {
    test('a search term leaves the rows unfiltered', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Alpha']);
        User::factory()->create(['first_name' => 'Zulu']);
        $component = livewire(UnsearchableDataTableComponent::class);

        // Act
        $component->set('search', 'no such name');

        // Assert
        $component
            ->assertSee('Alpha')
            ->assertSee('Zulu')
            ->assertDontSee('No records found.');
    });

    test('a default page size outside the accepted options falls back to the first option', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Alpha']);
        $component = livewire(UnsearchableDataTableComponent::class);

        // Assert
        $component
            ->assertSet('perPage', 25)
            ->assertSee('Alpha');
    });
});

describe('data table refreshing', function (): void {
    test('the refresh event re-renders the table with newly created rows', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Alpha']);
        $component = livewire(TestDataTableComponent::class);
        $component->assertDontSee('Latecomer');
        User::factory()->create(['first_name' => 'Latecomer']);

        // Act
        $component->dispatch('refreshDatatable');

        // Assert
        $component->assertSee('Latecomer');
    });
});
