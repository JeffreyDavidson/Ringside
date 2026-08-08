<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;

class RefereesController
{
    public function index(): View
    {
        return view('referees.index');
    }

    public function show(Referee $referee): View
    {
        return view('referees.show', ['referee' => $referee]);
    }
}
