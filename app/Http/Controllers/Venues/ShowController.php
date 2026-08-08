<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Models\Events\Venue;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Venue $venue): View
    {
        return view('venues.show', [
            'venue' => $venue,
        ]);
    }
}
