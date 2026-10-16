<?php

declare(strict_types=1);

namespace App\Services\Events;

use DateTimeZone;
use Illuminate\Support\Carbon;

class LiveShowTimingService
{
    /** A repeated or skipped wall-clock hour needs an explicit valid UTC offset; unknown planned times stay unknown. */
    public function wallTime(?string $value, string $zone, ?string $offset = null): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        $wall = Carbon::createFromFormat('!Y-m-d\TH:i', $value, 'UTC');
        if (! $wall instanceof Carbon || $wall->format('Y-m-d\TH:i') !== $value) {
            return null;
        }
        $candidates = [];
        $transitions = new DateTimeZone($zone)->getTransitions($wall->getTimestamp() - 86400, $wall->getTimestamp() + 86400);
        foreach ($transitions ?: [['offset' => Carbon::parse($value, $zone)->offset]] as $transition) {
            $instant = $wall->copy()->subSeconds($transition['offset'])->setTimezone($zone);
            if ($instant->format('Y-m-d\TH:i') === $value && ($offset === null || $instant->format('P') === $offset)) {
                $candidates[$instant->getTimestamp()] = $instant->utc();
            }
        }

        return count($candidates) === 1 ? array_first($candidates) : null;
    }
}
