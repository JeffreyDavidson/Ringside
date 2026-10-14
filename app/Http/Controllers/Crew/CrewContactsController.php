<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crew;

use Illuminate\Contracts\View\View;

class CrewContactsController
{
    public function index(): View
    {
        return view('crew.index');
    }
}
