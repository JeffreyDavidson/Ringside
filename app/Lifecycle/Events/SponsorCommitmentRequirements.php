<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Sponsors\Sponsor;
use Illuminate\Support\Str;

/**
 * A sponsor promise can only be saved on an event that belongs to a promotion, from a form that is still current,
 * and with a sponsor the promise may use. It refuses; SaveSponsorCommitmentAction locks the rows and passes in what it read.
 */
final readonly class SponsorCommitmentRequirements
{
    /** @throws SponsorCommitmentException */
    public function ensureEventAvailable(Event $event): void
    {
        if ($event->promotion_id === null) {
            throw SponsorCommitmentException::eventUnavailable();
        }
    }

    /**
     * Refuse a submission whose key is not a UUID, whose version is no longer the saved one, or that already created a promise.
     *
     * @param  bool  $keyAlreadyUsed  whether another promise on the event was created with this key (only read for a new promise)
     *
     * @throws RecordChangedException
     */
    public function ensureCurrentSubmission(?EventSponsorCommitment $commitment, int $version, string $submissionKey, bool $keyAlreadyUsed): void
    {
        if (! Str::isUuid($submissionKey)) {
            throw RecordChangedException::changed();
        }

        if (($commitment->version ?? 0) !== $version || ($commitment === null && $keyAlreadyUsed)) {
            throw RecordChangedException::changed();
        }
    }

    /**
     * Refuse a new promise on an archived event, and a promise that picks an archived sponsor it does not already use.
     *
     * @throws SponsorCommitmentException
     */
    public function ensureCanSelect(Event $event, Sponsor $sponsor, ?EventSponsorCommitment $commitment): void
    {
        if (($commitment === null && $event->trashed()) || ($sponsor->archived_at !== null && $commitment?->sponsor_id !== $sponsor->id)) {
            throw SponsorCommitmentException::archived();
        }
    }
}
