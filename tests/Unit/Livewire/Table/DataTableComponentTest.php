<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Table;

use Livewire\Livewire;

test('sorting accepts only declared sortable columns', function () {
    Livewire::test(TestDataTableComponent::class)
        ->call('sort', 'email')
        ->assertSet('sortField', '')
        ->call('sort', 'name')
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc');
});

test('hydrated sorting state is normalized before querying', function () {
    Livewire::test(TestDataTableComponent::class)
        ->set('sortField', 'name; drop table users')
        ->assertSet('sortField', '')
        ->assertSet('sortDirection', 'asc');
});

test('per page values are restricted to configured options', function () {
    Livewire::test(TestDataTableComponent::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 5)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});
