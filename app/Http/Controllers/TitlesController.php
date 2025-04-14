<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class TitlesController
{
    public function index(): View
    {
        Gate::authorize('viewList', Title::class);

        return view('titles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Title $title): View
    {
        Gate::authorize('view', Title::class);

        return view('titles.show', ['title' => $title]);
    }
}
