<?php

namespace App\Http\Controllers\Titles;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;

class RetireController extends Controller
{
    /**
     * Retires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\RetireRequest $request
     * @param  \App\Repositories\TitleRepository $titleRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, RetireRequest $request, TitleRepository $titleRepository)
    {
        throw_unless($title->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = now()->toDateTimeString();

        $titleRepository->deactivate($title, $retirementDate);
        $titleRepository->retire($title, $retirementDate);
        $title->updateStatus()->save();

        return redirect()->route('titles.index');
    }
}
