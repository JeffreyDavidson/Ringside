<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\DeactivateAction;
use App\Http\Controllers\Controller;
use App\Models\Title;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     *
     * @param  \App\Models\Title  $title
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('deactivate', $title);

        DeactivateAction::run($title);

        return to_route('titles.index');
    }
}
