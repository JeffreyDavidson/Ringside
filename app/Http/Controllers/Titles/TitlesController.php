<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTitleRequest;
use App\Http\Requests\UpdateTitleRequest;

class TitlesController extends Controller
{
    /**
     * Retrieve titles of a specific state.
     *
     * @param  string  $state
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, DataTables $table)
    {
        $this->authorize('viewList', Title::class);

        if ($request->ajax()) {
            $query = Title::query();

            return $table->eloquent($query)->addColumn('action', 'titles.partials.action-cell')->toJson();
        }

        return view('titles.index');
    }

    /**
     * Show the form for creating a new title.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Title::class);

        return response()->view('titles.create');
    }

    /**
     * Create a new title.
     *
     * @param  \App\Http\Requests\StoreTitleRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTitleRequest $request)
    {
        $title = Title::create($request->all());

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
     * Create a new title.
     *
     * @param  \App\Http\Requests\UpdateTitleRequest  $request
     * @param  \App\Models\Title  $title
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateTitleRequest $request, Title $title)
    {
        $title->update($request->all());

        return redirect()->route('titles.index');
    }

    /**
     * Delete a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Title $title)
    {
        $this->authorize('delete', Title::class);

        $title->delete();

        return redirect()->route('titles.index');
    }

    /**
     * Restore a deleted title.
     *
     * @param  int  $titleId
     * @return \lluminate\Http\RedirectResponse
     */
    public function restore($titleId)
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        $this->authorize('restore', Title::class);

        $title->restore();

        return redirect()->route('titles.index');
    }
}
