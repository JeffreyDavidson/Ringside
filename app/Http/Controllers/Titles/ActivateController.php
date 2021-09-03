<?php

namespace App\Http\Controllers\Titles;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;

class ActivateController extends Controller
{
    /**
     * Activates a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\ActivateRequest $request
     * @param  \App\Repositories\TitleRepository $titleRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, ActivateRequest $request, TitleRepository $titleRepository)
    {
        throw_unless($title->canBeActivated(), new CannotBeActivatedException);

        $activationDate = now()->toDateTimeString();

        $titleRepository->activate($title, $activationDate);
        $title->updateStatus()->save();

        return redirect()->route('titles.index');
    }
}
