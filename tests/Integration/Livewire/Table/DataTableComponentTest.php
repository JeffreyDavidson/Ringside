<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Table;

use App\Enums\Users\Role;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Livewire\livewire;

describe('data table component', function (): void {
    test('components can declare additional columns through the base extension point', function (): void {
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
