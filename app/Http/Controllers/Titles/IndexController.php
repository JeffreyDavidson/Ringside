<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', Title::class);

        return view('titles.index');
    }
}