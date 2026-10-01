<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Lifecycle\Periods\OpenPeriodEnder;
use App\Models\Contracts\Manageable;
use Illuminate\Support\Carbon;

class EndManagerAssignmentsAction
{
    /** @param  Manageable<*, *>  $manageable */
    public function handle(Manageable $manageable, Carbon $date): void
    {
        OpenPeriodEnder::end($manageable->managers()->newPivotQuery(), 'hired_at', 'fired_at', $date);
    }
}
