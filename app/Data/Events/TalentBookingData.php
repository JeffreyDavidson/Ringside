<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Events\TalentBookingStatus;
use Illuminate\Support\Carbon;

readonly class TalentBookingData
{
    public function __construct(
        public TalentBookingStatus $status,
        public ?int $feeMinor,
        public ?Carbon $arrival,
        public string $arrivalTimezone,
        public ?string $travelNotes,
        public bool $attest = false,
        public ?string $reason = null,
    ) {}
}
