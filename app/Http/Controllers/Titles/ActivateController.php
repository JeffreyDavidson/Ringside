<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Models\Title;

class ActivateController extends Controller
{
    /**
     * Activates a title.
     *
     * @param  \App\Models\Title  $title
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title)
    {
        $this->authorize('activate', $title);

        throw_unless($title->canBeActivated(), CannotBeActivatedException::class);

        ActivateAction::run($title);

        return to_route('titles.index');
    }
}
