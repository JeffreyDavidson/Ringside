<?php

declare(strict_types=1);

use App\Actions\Crew\ArchiveCrewContactAction;
use App\Models\Crew\CrewContact;

use function Pest\Laravel\actingAs;

test('archiving a contact keeps its private details and bumps its version', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create(['notes' => 'Retain this note']);
    $action = resolve(ArchiveCrewContactAction::class);

    // Act
    $action->handle($contact, 1, administrator());

    // Assert
    expect($contact->refresh()->archived_at)->not->toBeNull()
        ->and($contact->notes)->toBe('Retain this note')
        ->and($contact->version)->toBe(2);
});

test('archiving a contact names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $contact = CrewContact::factory()->create();
    $since = lastActivityId();

    // Act
    resolve(ArchiveCrewContactAction::class)->handle($contact, 1, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'production'))->toBe(['contact-archive|'.causerTag($actor)]);
});
