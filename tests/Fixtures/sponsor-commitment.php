<?php

declare(strict_types=1);

use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Data\Events\SponsorCommitmentData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/** @return array{event: Event, sponsor: Sponsor, promise: EventSponsorCommitment} */
function sponsorFixture(): array
{
    actingAs(administrator());
    $event = Event::factory()->for(Promotion::factory())->create(['name' => 'Harbor Showcase', 'date' => now()]);
    $sponsor = Sponsor::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'Harbor Hardware']);
    $promise = app(SaveSponsorCommitmentAction::class)->handle($event, null, 0, (string) Str::uuid(),
        new SponsorCommitmentData($sponsor->id, SponsorCommitmentType::Banner, 'Entrance banner'), signedInUser());

    return ['event' => $event, 'sponsor' => $sponsor, 'promise' => $promise];
}

/**
 * @param array{sponsorId?: int, type?: SponsorCommitmentType, description?: string, owner?: ?string,
 * deadline?: ?Carbon, timezone?: string, notes?: ?string, agreement?: ?string,
 * status?: SponsorCommitmentStatus, evidence?: ?string, evidenceUrl?: ?string,
 * completedAt?: ?Carbon, instructionLabel?: ?string, instruction?: ?string, instructionOwner?: ?string} $changes
 */
function sponsorPromiseData(EventSponsorCommitment $promise, array $changes = []): SponsorCommitmentData
{
    return new SponsorCommitmentData(...[
        'sponsorId' => $promise->sponsor_id, 'type' => $promise->type, 'description' => $promise->description,
        'owner' => $promise->responsible, 'deadline' => $promise->deadline_at, 'timezone' => $promise->timezone,
        'notes' => $promise->notes, 'agreement' => $promise->agreement_reference, 'status' => $promise->status,
        'evidence' => $promise->evidence_note, 'evidenceUrl' => $promise->evidence_url, 'completedAt' => $promise->completed_at,
        'instructionLabel' => $promise->instruction_label, 'instruction' => $promise->instruction, 'instructionOwner' => $promise->instruction_owner,
        ...$changes,
    ]);
}
