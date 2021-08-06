<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use App\Services\TitleService;

class TitlesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $this->authorize('viewList', Title::class);

        return view('titles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(Title $title)
    {
        $this->authorize('create', Title::class);

        return view('titles.create', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Titles\StoreRequest  $request
     * @param  \App\Services\TitleService $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, TitleService $titleService)
    {
        $titleService->create($request->validated());

        return redirect()->route('titles.index');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function show(Title $title)
    {
        $this->authorize('view', Title::class);

        return view('titles.show', compact('title'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Title  $title
     * @return \Illuminate\View\View
     */
    public function edit(Title $title)
    {
        $this->authorize('update', Title::class);

        return view('titles.edit', compact('title'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Titles\UpdateRequest  $request
     * @param  \App\Models\Title  $title
     * @param  \App\Services\TitleService $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Title $title, TitleService $titleService)
    {
        $titleService->update($title, $request->validated());

        return redirect()->route('titles.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Title  $title
     * @param  \App\Services\TitleService  $titleService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Title $title, TitleService $titleService)
    {
        $this->authorize('delete', $title);

        $titleService->delete($title);

        return redirect()->route('titles.index');
    }
}
