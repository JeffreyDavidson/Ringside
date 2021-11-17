<?php

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Http\Requests\Titles\DeactivateRequest  $request
     * @param  \App\Actions\Titles\DeactivateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, DeactivateRequest $request, DeactivateAction $action)
    {
        throw_unless($title->canBeDeactivated(), new CannotBeDeactivatedException);

        $action->handle($title);

        return redirect()->route('titles.index');
    }
}
