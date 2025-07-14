<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(Title $title): View
    {
        Gate::authorize('view', $title);

        return view('titles.show', [
            'title' => $title,
        ]);
    }
}