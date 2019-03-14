<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use App\Http\Controllers\Controller;

class TitleActivationsController extends Controller
{
    /**
     * Activate and inactive title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Title $title)
    {
        $this->authorize('activate', $title);

        $title->activate();

        return redirect()->route('titles.index');
    }

    /**
     * Deactivate an active title.
     *
     * @param  \App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Title $title)
    {
        $this->authorize('deactivate', $title);

        $title->deactivate();

        return redirect()->route('titles.index', ['state' => 'inactive']);
    }
}
