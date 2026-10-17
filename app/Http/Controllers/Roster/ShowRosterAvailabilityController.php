<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roster;

use Illuminate\View\View;

class ShowRosterAvailabilityController
{
    public function __invoke(): View
    {
        return view('roster.availability');
    }
}
