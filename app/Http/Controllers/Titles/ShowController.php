<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(Title $title): View
    {
        return view('titles.show', [
            'title' => $title,
        ]);
    }
}
