<?php

declare(strict_types=1);

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Models\Crew\CrewContact;

test('archiving a contact keeps its private details and bumps its version', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create(['notes' => 'Retain this note']);
    $action = resolve(ArchiveCrewContactAction::class);

    // Act
    $action->handle($contact, 1);

    // Assert
    expect($contact->refresh()->archived_at)->not->toBeNull()
        ->and($contact->notes)->toBe('Retain this note')
        ->and($contact->version)->toBe(2);
});
