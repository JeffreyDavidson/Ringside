<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storylines;

use App\Models\Storylines\Storyline;
use Illuminate\Contracts\View\View;

class StorylinesController
{
    public function index(): View
    {
        return view('storylines.index');
    }

    public function show(Storyline $storyline): View
    {
        return view('storylines.show', ['storyline' => $storyline]);
    }
}
