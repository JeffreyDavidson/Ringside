<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class StablesController
{
    /**
     * View a list of stables.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Stable::class);

        return view('stables.index');
    }

    /**
     * Show the profile of a tag team.
     */
    public function show(Stable $stable): View
    {
        Gate::authorize('view', $stable);

        return view('stables.show', [
            'stable' => $stable,
        ]);
    }
}
