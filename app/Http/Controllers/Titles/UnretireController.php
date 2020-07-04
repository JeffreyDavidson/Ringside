<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\UnretireRequest;
use App\Models\Title;

class UnretireController extends Controller
{
    /**
     * Unretires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\UnretireRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, UnretireRequest $request)
    {
        $title->unretire();

        return redirect()->route('titles.index');
    }
}
