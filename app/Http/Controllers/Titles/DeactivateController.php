<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\DeactivateRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, DeactivateRequest $request)
    {
        $title->deactivate();

        return redirect()->route('titles.index');
    }
}
