<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\EventSponsorCommitment;
use App\Models\Events\EventSponsorCommitmentRevision;
use App\Models\Users\User;

class PersistSponsorCommitmentAction
{
    /** Called inside the owning event transaction, after the commitment lock/version check. */
    public function handle(EventSponsorCommitment $commitment, string $operation, ?string $reason, User $actor): void
    {
        $commitment->version++;
        $commitment->save();
        $revision = new EventSponsorCommitmentRevision;
        $revision->promotion_id = $commitment->promotion_id;
        $revision->commitment_id = $commitment->id;
        $revision->version = $commitment->version;
        $revision->actor_id = $actor->id;
        $revision->actor_name = "{$actor->first_name} {$actor->last_name}";
        $revision->operation = $operation;
        $revision->reason = $reason;
        $revision->snapshot = $commitment->attributesToArray();
        $revision->save();
    }
}
