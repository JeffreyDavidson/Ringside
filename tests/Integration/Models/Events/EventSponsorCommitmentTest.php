<?php

declare(strict_types=1);

use App\Enums\Events\SponsorCommitmentType;
use App\Models\Events\EventSponsorCommitment;
use Illuminate\Support\Carbon;

test('mass assignment accepts the promise details and ignores ownership, status and system snapshots', function (): void {
    // Arrange
    $commitment = new EventSponsorCommitment;

    // Act
    $commitment->fill([
        'type' => SponsorCommitmentType::Announcement,
        'description' => 'Announce twice',
        'responsible' => 'Ring announcer',
        'deadline_at' => '2026-12-01 18:00:00',
        'timezone' => 'America/New_York',
        'notes' => 'Keep short',
        'agreement_reference' => 'AG-1',
        'evidence_note' => 'Clip',
        'evidence_url' => 'https://example.test/clip',
        'instruction_label' => 'Mention',
        'instruction' => 'Say the name',
        'instruction_owner' => 'Announcer',
        'promotion_id' => 99,
        'event_id' => 98,
        'sponsor_id' => 97,
        'sponsor_name' => 'Forged',
        'submission_key' => 'forged',
        'version' => 6,
        'status' => 'completed',
        'completed_at' => Carbon::parse('2026-01-01'),
        'pack_key' => 'forged',
        'pack_instruction_hash' => 'forged',
        'event_context' => ['forged' => true],
    ]);

    // Assert
    expect(array_keys($commitment->getAttributes()))->toEqualCanonicalizing([
        'type', 'description', 'responsible', 'deadline_at', 'timezone', 'notes', 'agreement_reference',
        'evidence_note', 'evidence_url', 'instruction_label', 'instruction', 'instruction_owner',
    ]);
});
