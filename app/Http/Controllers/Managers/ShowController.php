<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Models\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(Manager $manager): View
    {
        Gate::authorize('view', $manager);

        return view('managers.show', [
            'manager' => $manager,
        ]);
    }
}
