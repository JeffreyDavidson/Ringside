<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Models\Stables\Stable;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Stable $stable): View
    {
        return view('stables.show', [
            'stable' => $stable,
        ]);
    }
}
