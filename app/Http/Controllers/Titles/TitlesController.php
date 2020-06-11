<?php

namespace App\Http\Controllers\Titles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;

class TitlesController extends Controller
{
    public function index()
    {
        $this->authorize('viewList', Title::class);

        return view('titles.index');
    }

    public function create(Title $title)
    {
        $this->authorize('create', Title::class);

        return view('titles.create', compact('title'));
    }

    public function store(StoreRequest $request)
    {
        $title = Title::create($request->validatedExcept('activated_at'));

        if ($request->filled('activated_at')) {
            $title->activate($request->input('activated_at'));
        }

        return redirect()->route('titles.index');
    }

    public function show(Title $title)
    {
        $this->authorize('view', Title::class);

        return view('titles.show', compact('title'));
    }

    public function edit(Title $title)
    {
        $this->authorize('update', Title::class);

        return view('titles.edit', compact('title'));
    }

    public function update(UpdateRequest $request, Title $title)
    {
        $title->update($request->validatedExcept('activated_at'));

        if ($request->filled('activated_at')) {
            $title->activate($request->input('activated_at'));
        }

        return redirect()->route('titles.index');
    }

    public function destroy(Title $title)
    {
        $this->authorize('delete', $title);

        $title->delete();

        return redirect()->route('titles.index');
    }
}
