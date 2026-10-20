<?php

declare(strict_types=1);

use App\Data\Roster\UnavailabilityData;
use App\Livewire\Roster\Availability\Components\Profile;
use App\Livewire\Roster\Availability\Forms\UnavailabilityForm;
use Illuminate\Validation\ValidationException;

test('builds the data object from validated fields', function (): void {
    // Arrange
    $form = new UnavailabilityForm(new Profile, 'form');
    $form->startsOn = '2026-10-17';
    $form->endsOn = '2026-10-19';
    $form->reason = 'Outside commitment';

    // Act
    $data = $form->toData();

    // Assert
    expect($data)->toEqual(new UnavailabilityData('2026-10-17', '2026-10-19', 'Outside commitment'));
});

test('an empty reason becomes null', function (): void {
    // Arrange
    $form = new UnavailabilityForm(new Profile, 'form');
    $form->startsOn = '2026-10-17';
    $form->endsOn = '2026-10-17';

    // Act
    $data = $form->toData();

    // Assert
    expect($data->reason)->toBeNull();
});

test('refuses an end date before the start date', function (): void {
    // Arrange
    $form = new UnavailabilityForm(new Profile, 'form');
    $form->startsOn = '2026-10-19';
    $form->endsOn = '2026-10-17';

    // Act
    $attempt = fn () => $form->toData();

    // Assert
    expect($attempt)->toThrow(ValidationException::class);
});
