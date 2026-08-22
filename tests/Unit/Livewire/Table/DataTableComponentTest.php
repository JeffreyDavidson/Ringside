<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Table;

use function Pest\Livewire\livewire;

test('components can declare additional columns through the base extension point', function () {
    livewire(TestDataTableComponent::class)
        ->assertSee('Created At');
});

test('sorting accepts only declared sortable columns', function () {
    livewire(TestDataTableComponent::class)
        ->call('sort', 'email')
        ->assertSet('sortField', '')
        ->call('sort', 'name')
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc');
});

test('hydrated sorting state is normalized before querying', function () {
    livewire(TestDataTableComponent::class)
        ->set('sortField', 'name; drop table users')
        ->assertSet('sortField', '')
        ->assertSet('sortDirection', 'asc');
});

test('per page values are restricted to configured options', function () {
    livewire(TestDataTableComponent::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 5)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});
