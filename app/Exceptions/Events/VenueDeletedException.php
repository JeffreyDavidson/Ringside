<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Venue;

final class VenueDeletedException extends BaseBusinessException
{
    public static function cannotBeChosen(Venue $venue): static
    {
        return new self(__('events.validation.venue_deleted', ['venue' => $venue->name]));
    }
}
