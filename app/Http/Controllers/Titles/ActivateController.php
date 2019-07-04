<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate and title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('activate', $title);

        $title->activate();

        return redirect()->route('titles.index');
    }
}
