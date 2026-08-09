<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Models\Contracts\Employable;
use Illuminate\Support\Carbon;

final class EmploymentPeriodManager
{
    /**
     * @param  Employable<*, *>  $employable
     */
    public function start(Employable $employable, Carbon $date): void
    {
        $employable->employments()->create([
            'started_at' => $date,
            'ended_at' => null,
        ]);
    }

    /**
     * @param  Employable<*, *>  $employable
     */
    public function end(Employable $employable, Carbon $date): void
    {
        $employable->currentEmployment()->update([
            'ended_at' => $date,
        ]);
    }
}
