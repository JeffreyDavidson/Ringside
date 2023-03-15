<?php

namespace App\Events\Managers;

use App\Models\Manager;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

class ManagerReleased
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Manager $manager, public Carbon $releaseDate)
    {
    }
}
