<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class TitlesController extends Controller
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

        $title->load('championships');

        return view('titles.show', compact('title'));
    }
}
