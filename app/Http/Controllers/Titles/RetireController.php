<?php

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;

class RetireController extends Controller
{
    /**
     * Retires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\RetireRequest $request
     * @param  \App\Actions\Titles\RetireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, RetireRequest $request, RetireAction $action)
    {
        throw_unless($title->canBeRetired(), new CannotBeRetiredException);

        $action->handle($title);

        return redirect()->route('titles.index');
    }
}
