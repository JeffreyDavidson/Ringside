<?php

declare(strict_types=1);

namespace App\Observers\Events;

use App\Models\Events\EventSponsorCommitmentRevision;
use LogicException;

class SponsorCommitmentRevisionObserver
{
    public function updating(EventSponsorCommitmentRevision $revision): never
    {
        throw new LogicException('Sponsor commitment history is immutable.');
    }

    public function deleting(EventSponsorCommitmentRevision $revision): never
    {
        throw new LogicException('Sponsor commitment history is immutable.');
    }
}
