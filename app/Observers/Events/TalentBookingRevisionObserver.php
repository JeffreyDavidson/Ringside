<?php

declare(strict_types=1);

namespace App\Observers\Events;

use App\Models\Events\EventTalentBookingRevision;
use LogicException;

class TalentBookingRevisionObserver
{
    public function updating(EventTalentBookingRevision $revision): never
    {
        throw new LogicException('Talent booking revisions are immutable.');
    }

    public function deleting(EventTalentBookingRevision $revision): never
    {
        throw new LogicException('Talent booking revisions are immutable.');
    }
}
