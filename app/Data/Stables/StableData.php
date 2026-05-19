<?php

declare(strict_types=1);

namespace App\Data\Stables;

use Illuminate\Support\Carbon;

readonly class StableData
{
    /**
     * Create a new stable data instance.
     */
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public StableMembershipData $members,
    ) {}

    /**
     * Get the join date for members, defaulting to now if no start date.
     */
    public function getJoinDate(): Carbon
    {
        return $this->start_date ?? now();
    }

    /**
     * Check if a start date has been provided.
     */
    public function hasStartDate(): bool
    {
        return $this->start_date !== null;
    }

    /**
     * Check if the stable should be immediately established.
     */
    public function shouldEstablish(): bool
    {
        return $this->hasStartDate();
    }

    /**
     * Get a trimmed version of the stable name.
     */
    public function getTrimmedName(): string
    {
        return mb_trim($this->name);
    }
}
