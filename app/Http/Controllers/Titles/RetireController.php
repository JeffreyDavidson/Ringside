<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;
use App\Services\TitleService;

class RetireController extends Controller
{
    /**
     * Retires a title.
     *
     * @param  \App\Models\Title $title
     * @param  \App\Http\Requests\Titles\RetireRequest $request
     * @param  \App\Services\TitleService $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Title $title, RetireRequest $request, TitleService $titleService)
    {
        $titleService->retire($title);

        return redirect()->route('titles.index');
    }
}
