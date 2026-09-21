<?php

declare(strict_types=1);

use App\Data\Events\EventData;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('event create and edit form', function (): void {
    it('maps scheduled event fields to typed application data', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $date = Date::now()->addMonth()->startOfMinute();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'Summer Showcase';
        $form->date = $date->toDateTimeString();
        $form->venue_id = $venue->id;
        $form->preview = 'A championship showcase.';

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(EventData::class)
            ->and($data->name)->toBe('Summer Showcase')
            ->and($data->date?->equalTo($date))->toBeTrue()
            ->and($data->venue?->is($venue))->toBeTrue()
            ->and($data->preview)->toBe('A championship showcase.');
    });

    it('maps blank optional fields to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'Future Announcement';
        $form->date = '';
        $form->venue_id = 0;
        $form->preview = '';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->date)->toBeNull()
            ->and($data->venue)->toBeNull()
            ->and($data->preview)->toBeNull();
    });

    it('resolves the event selected for editing', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->setModel($event);

        // Act
        $selectedEvent = $form->event();

        // Assert
        expect($selectedEvent->is($event))->toBeTrue();
    });
});
