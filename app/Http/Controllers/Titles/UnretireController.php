<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use App\Http\Controllers\Controller;

class UnretireController extends Controller
{
    /**
     * Unretire a title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('unretire', $title);

        $title->unretire();

        return redirect()->route('titles.index');
    }
}
