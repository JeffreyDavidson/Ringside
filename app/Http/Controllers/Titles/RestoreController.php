<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Title;

class RestoreController extends Controller
{
    /**
     * Restores a title.
     *
     * @param  int  $titleId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke($titleId)
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        $this->authorize('restore', $title);

        RestoreAction::run($title);

        return to_route('titles.index');
    }
}
