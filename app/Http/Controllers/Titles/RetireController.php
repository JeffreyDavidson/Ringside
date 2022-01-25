<?php

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Title;

class RetireController extends Controller
{
    /**
     * Retires a title.
     *
     * @param  \App\Models\Title $title
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('retire', $title);

        throw_unless($title->canBeRetired(), new CannotBeRetiredException);

        RetireAction::run($title);

        return redirect()->route('titles.index');
    }
}
