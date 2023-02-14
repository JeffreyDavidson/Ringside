<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\CreateAction;
use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\UpdateAction;
use App\Data\TitleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TitlesController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewList', Title::class);

        return view('titles.index');
    }

    public function create(Title $title): View
    {
        $this->authorize('create', Title::class);

        return view('titles.create', [
            'title' => $title,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(TitleData::fromStoreRequest($request));

        return to_route('titles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Title $title): View
    {
        $this->authorize('view', Title::class);

        $title->load('championships');

        return view('titles.show', compact('title'));
    }

    public function edit(Title $title): View
    {
        $this->authorize('update', Title::class);

        return view('titles.edit', [
            'title' => $title,
        ]);
    }

    public function update(UpdateRequest $request, Title $title): RedirectResponse
    {
        UpdateAction::run($title, TitleData::fromUpdateRequest($request));

        return to_route('titles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Title $title): RedirectResponse
    {
        $this->authorize('delete', $title);

        DeleteAction::run($title);

        return to_route('titles.index');
    }
}
