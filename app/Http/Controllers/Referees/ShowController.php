<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Referee $referee): View
    {
        return view('referees.show', [
            'referee' => $referee,
        ]);
    }
}
