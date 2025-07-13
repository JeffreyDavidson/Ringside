<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Models\Shared\Venue;
use Illuminate\Support\Carbon;

readonly class EventData
{
    /**
     * Create a new event data instance.
     */
    public function __construct(
        public string $name,
        public ?Carbon $date,
        public ?Venue $venue,
        public ?string $preview
    ) {}
}
