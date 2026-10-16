<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\SponsorCommitmentData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Sponsors\Sponsor;
use App\Queries\Events\SponsorCommitmentContextQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveSponsorCommitmentAction
{
    public function __construct(private readonly SponsorCommitmentContextQuery $context, private readonly PersistSponsorCommitmentAction $persist) {}

    public function handle(Event $event, ?int $id, int $version, string $submissionKey, SponsorCommitmentData $data,
        ?string $reason = null, bool $reviewed = false, bool $refreshSponsorName = false): EventSponsorCommitment
    {
        return DB::transaction(function () use ($event, $id, $version, $submissionKey, $data, $reason, $reviewed, $refreshSponsorName): EventSponsorCommitment {
            $event = Event::query()->withTrashed()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('manageSponsors', $event);
            if ($event->promotion_id === null) {
                throw SponsorCommitmentException::eventUnavailable();
            }
            $sponsor = Sponsor::query()->where('promotion_id', $event->promotion_id)->whereKey($data->sponsorId)->lockForUpdate()->firstOrFail();
            $commitment = $id === null ? null : EventSponsorCommitment::query()->where('event_id', $event->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            if (! Str::isUuid($submissionKey)) {
                throw RecordChangedException::changedKeepingInput();
            }
            if (($commitment->version ?? 0) !== $version || ($commitment === null && EventSponsorCommitment::query()->where('event_id', $event->id)->where('submission_key', $submissionKey)->exists())) {
                throw RecordChangedException::changedKeepingInput();
            }
            if (($commitment === null && $event->trashed()) || ($sponsor->archived_at !== null && $commitment?->sponsor_id !== $sponsor->id)) {
                throw SponsorCommitmentException::archived();
            }
            if (($data->status === SponsorCommitmentStatus::Cancelled || ($commitment !== null && $commitment->status !== SponsorCommitmentStatus::Pending)) && mb_trim($reason ?? '') === '') {
                throw SponsorCommitmentException::reasonRequired();
            }
            if ($data->status === SponsorCommitmentStatus::Completed && (mb_trim($data->evidence ?? '') === '' || $data->completedAt?->isFuture())) {
                throw SponsorCommitmentException::evidenceRequired();
            }
            $commitment ??= new EventSponsorCommitment;
            $isNew = ! $commitment->exists;
            $commitment->version = $version;
            $commitment->promotion_id = $event->promotion_id;
            $commitment->event_id = $event->id;
            if ($isNew || $commitment->sponsor_id !== $sponsor->id || $refreshSponsorName) {
                $commitment->sponsor_name = $sponsor->name;
            }
            $commitment->sponsor_id = $sponsor->id;
            $commitment->submission_key = $isNew ? $submissionKey : $commitment->submission_key;
            $commitment->type = $data->type;
            $commitment->description = mb_trim($data->description);
            $commitment->responsible = $data->owner;
            $commitment->deadline_at = $data->deadline?->copy()->utc();
            $commitment->timezone = $data->timezone;
            $commitment->notes = $data->notes;
            $commitment->agreement_reference = $data->agreement;
            $commitment->status = $data->status;
            $commitment->evidence_note = $data->status === SponsorCommitmentStatus::Completed ? $data->evidence : null;
            $commitment->evidence_url = $data->status === SponsorCommitmentStatus::Completed ? $data->evidenceUrl : null;
            $commitment->completed_at = $data->status === SponsorCommitmentStatus::Completed ? ($data->completedAt ?? $commitment->completed_at ?? now()) : null;
            $commitment->instruction_label = $data->instructionLabel;
            $commitment->instruction = $data->instruction;
            $commitment->instruction_owner = $data->instructionOwner;
            if ($isNew || $reviewed) {
                $commitment->event_context = $this->context->get($event);
            }
            $this->persist->handle($commitment, $reviewed ? 'review' : 'save', $reason);

            return $commitment;
        }, attempts: 3);
    }
}
