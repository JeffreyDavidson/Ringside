<?php

namespace App\Http\Controllers\Titles;

use App\DataTables\TitlesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Titles\StoreRequest;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;
use Illuminate\Http\Request;

class TitlesController extends Controller
{
    /**
     * Retrieve titles of a specific state.
     *
     * @param  string  $state
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TitlesDataTable $dataTable)
    {
        $this->authorize('viewList', Title::class);

        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return view('titles.index');
    }

    /**
     * Show the form for creating a new title.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Title $title)
    {
        $this->authorize('create', Title::class);

        return view('titles.create', compact('title'));
    }

    /**
     * Create a new title.
     *
     * @param  App\Http\Requests\Titls\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        Title::create($request->all());

        return redirect()->route('titles.index');
    }

    /**
     * Show the title.
     *
     * @param  \App\Models\Title  $title
     * @return \Illuminate\Http\Response
     */
    public function show(Title $title)
    {
        $this->authorize('view', Title::class);

        return response()->view('titles.show', compact('title'));
    }

    /**
     * Show the form for editing a title.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Title $title)
    {
        $this->authorize('update', Title::class);

        return response()->view('titles.edit', compact('title'));
    }

    /**
     * Update an existing title.
     *
     * @param  \App\Http\Requests\UpdateRequest  $request
     * @param  \App\Models\Title  $title
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Title $title)
    {
        $title->update($request->all());

        return redirect()->route('titles.index');
    }

    /**
     * Delete a title.
     *
     * @param  App\Models\Title  $title
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Title $title)
    {
        $this->authorize('delete', $title);

        $title->delete();

        return redirect()->route('titles.index');
    }
}
