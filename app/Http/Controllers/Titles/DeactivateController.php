<?php

namespace App\Http\Controllers\Titles;

use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;

class DeactivateController extends Controller
{
    /**
     * Deactivates a title.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Http\Requests\Titles\DeactivateRequest  $request
     * @param  \App\Repositories\TitleRepository  $titleRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, DeactivateRequest $request, TitleRepository $titleRepository)
    {
        throw_unless($title->canBeDeactivated(), new CannotBeDeactivatedException);

        $deactivationDate = now()->toDateTimeString();

        $titleRepository->deactivate($title, $deactivationDate);
        $title->updateStatus()->save();

        return redirect()->route('titles.index');
    }
}
