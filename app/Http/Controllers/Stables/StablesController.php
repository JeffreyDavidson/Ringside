<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Models\Stables\Stable;
use Illuminate\Contracts\View\View;

class StablesController
{
    public function index(): View
    {
        return view('stables.index');
    }

    public function show(Stable $stable): View
    {
        return view('stables.show', ['stable' => $stable]);
    }
}
