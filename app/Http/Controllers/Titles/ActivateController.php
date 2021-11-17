<?php

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;

class ActivateController extends Controller
{
    /**
     * Activates a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\ActivateRequest $request
     * @param  \App\Actions\Titles\ActivateAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, ActivateRequest $request, ActivateAction $action)
    {
        throw_unless($title->canBeActivated(), new CannotBeActivatedException);

        $action->handle($title);

        return redirect()->route('titles.index');
    }
}
