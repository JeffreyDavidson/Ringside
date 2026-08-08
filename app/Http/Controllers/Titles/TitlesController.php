<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;

class TitlesController
{
    public function index(): View
    {
        return view('titles.index');
    }

    public function show(Title $title): View
    {
        return view('titles.show', ['title' => $title]);
    }
}
