<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Contracts\Manageable;
use Illuminate\Support\Carbon;

class EndManagerAssignmentsAction
{
    /** @param  Manageable<*, *>  $manageable */
    public function handle(Manageable $manageable, Carbon $date): void
    {
        $manageable->managers()->newPivotQuery()
            ->whereNull('fired_at')
            ->update(['fired_at' => $date]);
    }
}
