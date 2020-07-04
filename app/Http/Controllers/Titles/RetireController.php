<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;

class RetireController extends Controller
{
    /**
     * Retires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\RetireRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, RetireRequest $request)
    {
        $title->retire();

        return redirect()->route('titles.index');
    }
}
