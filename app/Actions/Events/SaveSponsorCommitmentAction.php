<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\SponsorCommitmentData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Lifecycle\Events\SponsorCommitmentRequirements;
use App\Lifecycle\Events\SponsorCommitmentStateMachine;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Sponsors\Sponsor;
use App\Models\Users\User;
use App\Services\Events\SponsorCommitmentContextService;
use Illuminate\Support\Facades\DB;

class SaveSponsorCommitmentAction
{
    public function __construct(
        private readonly SponsorCommitmentContextService $context,
        private readonly PersistSponsorCommitmentAction $persist,
        private readonly SponsorCommitmentRequirements $requirements,
        private readonly SponsorCommitmentStateMachine $machine,
    ) {}

    public function handle(
        Event $event,
        ?int $id,
        int $version,
        string $submissionKey,
        SponsorCommitmentData $data,
        User $actor,
        ?string $reason = null,
        bool $reviewed = false,
        bool $refreshSponsorName = false,
    ): EventSponsorCommitment {
        return DB::transaction(function () use ($event, $id, $version, $submissionKey, $data, $actor, $reason, $reviewed, $refreshSponsorName): EventSponsorCommitment {
            $event = Event::query()
                ->withTrashed()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->requirements->ensureEventAvailable($event);

            $sponsor = Sponsor::query()
                ->where('promotion_id', $event->promotion_id)
                ->whereKey($data->sponsorId)
                ->lockForUpdate()
                ->firstOrFail();

            $commitment = null;

            if ($id !== null) {
                $commitment = EventSponsorCommitment::query()
                    ->forEventId($event->id)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $this->requirements->ensureCurrentSubmission(
                $commitment,
                $version,
                $submissionKey,
                fn (): bool => EventSponsorCommitment::query()->forEventId($event->id)->where('submission_key', $submissionKey)->exists(),
            );
            $this->requirements->ensureCanSelect($event, $sponsor, $commitment);
            $this->machine->ensureCanMove($commitment, $data->status, $reason);
            $this->machine->ensureCanComplete($data->status, $data->evidence, $data->completedAt);
            $commitment ??= new EventSponsorCommitment;
            $isNew = ! $commitment->exists;
            $commitment->version = $version;
            $commitment->promotion_id = $sponsor->promotion_id;
            $commitment->event_id = $event->id;

            if ($isNew || $commitment->sponsor_id !== $sponsor->id || $refreshSponsorName) {
                $commitment->sponsor_name = $sponsor->name;
            }

            $commitment->sponsor_id = $sponsor->id;
            $commitment->submission_key = $isNew ? $submissionKey : $commitment->submission_key;
            $commitment->fill([
                'type' => $data->type,
                'description' => mb_trim($data->description),
                'responsible' => $data->owner,
                'deadline_at' => $data->deadline?->copy()->utc(),
                'timezone' => $data->timezone,
                'notes' => $data->notes,
                'agreement_reference' => $data->agreement,
                'evidence_note' => $data->status === SponsorCommitmentStatus::Completed ? $data->evidence : null,
                'evidence_url' => $data->status === SponsorCommitmentStatus::Completed ? $data->evidenceUrl : null,
                'instruction_label' => $data->instructionLabel,
                'instruction' => $data->instruction,
                'instruction_owner' => $data->instructionOwner,
            ]);
            $commitment->status = $data->status;
            $commitment->completed_at = $data->status === SponsorCommitmentStatus::Completed ? ($data->completedAt ?? $commitment->completed_at ?? now()) : null;

            if ($isNew || $reviewed) {
                $commitment->event_context = $this->context->get($event);
            }

            $this->persist->handle($commitment, $reviewed ? 'review' : 'save', $reason, $actor);

            return $commitment;
        }, attempts: 3);
    }
}
