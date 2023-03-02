<?php

namespace App\Events\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

class WrestlerReleased
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Wrestler $wrestler, public Carbon $releaseDate)
    {
    }
}
