<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roster;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ShowRosterAvailabilityController extends Controller
{
    public function __invoke(): View
    {
        return view('roster.availability');
    }
}
