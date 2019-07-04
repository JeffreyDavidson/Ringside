<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use App\Http\Controllers\Controller;

class RetireController extends Controller
{
    /**
     * Retire a title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('retire', $title);

        $title->retire();

        return redirect()->route('titles.index');
    }
}
