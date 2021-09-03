<?php

namespace App\Http\Controllers\Titles;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\UnretireRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;

class UnretireController extends Controller
{
    /**
     * Unretires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\UnretireRequest $request
     * @param  \App\Repositories\TitleRepository $titleRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, UnretireRequest $request, TitleRepository $titleRepository)
    {
        throw_unless($title->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = now()->toDateTimeString();

        $titleRepository->unretire($title, $unretiredDate);
        $titleRepository->activate($title, $unretiredDate);
        $title->updateStatus()->save();

        return redirect()->route('titles.index');
    }
}
