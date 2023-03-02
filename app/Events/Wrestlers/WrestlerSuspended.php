<?php

namespace App\Events\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Foundation\Events\Dispatchable;

class WrestlerSuspended
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Wrestler $wrestler)
    {
    }
}
