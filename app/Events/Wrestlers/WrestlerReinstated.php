<?php

declare(strict_types=1);

namespace App\Events\Wrestlers;

use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;

class WrestlerReinstated
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Wrestler $wrestler, public Carbon $reinstatementDate) {}
}
