<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Models\Title;

class RestoreController extends Controller
{
    /**
     * Restores a title.
     *
     * @param  int $titleId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($titleId)
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        $this->authorize('restore', $title);

        $title->restore();

        return redirect()->route('titles.index');
    }
}
