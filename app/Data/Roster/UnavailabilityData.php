<?php

declare(strict_types=1);

namespace App\Data\Roster;

/** The days and optional reason of one unavailability entry that a save writes. */
readonly class UnavailabilityData
{
    /**
     * @param  string  $startsOn  first unavailable day as Y-m-d
     * @param  string  $endsOn  last unavailable day as Y-m-d, inclusive
     */
    public function __construct(
        public string $startsOn,
        public string $endsOn,
        public ?string $reason,
    ) {}
}
