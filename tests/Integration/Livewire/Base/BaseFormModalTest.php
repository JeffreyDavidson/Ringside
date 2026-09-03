<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('manages the shared modal lifecycle', function () {
    $modal = livewire(FormModal::class);

    $modal
        ->assertSet('isModalOpen', false)
        ->call('openModal')
        ->assertSet('isModalOpen', true)
        ->call('closeModal')
        ->assertSet('isModalOpen', false);
});

it('completes the shared form submission workflow', function () {
    $modal = livewire(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Shared Modal Event')
        ->set('form.venue_id', null);

    $modal->call('save');

    $modal
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false)
        ->assertDispatched('refreshDatatable')
        ->assertDispatched('closeModal')
        ->assertDispatched('form-submitted');

    $this->assertDatabaseHas('events', [
        'name' => 'Shared Modal Event',
    ]);
});
