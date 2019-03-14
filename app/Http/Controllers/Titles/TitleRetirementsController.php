<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use App\Http\Controllers\Controller;

class TitleRetirementsController extends Controller
{
    /**
     * Retire a title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Title $title)
    {
        $this->authorize('retire', $title);

        $title->retire();

        return redirect()->route('titles.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Title $title)
    {
        $this->authorize('unretire', $title);

        $title->unretire();

        return redirect()->route('titles.index');
    }
}
