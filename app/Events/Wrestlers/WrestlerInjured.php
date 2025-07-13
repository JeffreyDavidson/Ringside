<?php

declare(strict_types=1);

namespace App\Events\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;

class WrestlerInjured
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Wrestler $wrestler, public Carbon $injureDate) {}
}
