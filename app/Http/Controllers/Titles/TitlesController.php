<?php

namespace App\Http\Controllers\Titles;

use App\Models\Title;
use Illuminate\Http\Request;
use App\Filters\TitleFilters;
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
    public function index(Request $request, DataTables $table, TitleFilters $requestFilter)
    {
        $this->authorize('viewList', Title::class);

        if ($request->ajax()) {
            $query = Title::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'titles.partials.action-cell')
                ->editColumn('introduced_at', function (Title $title) {
                    return $title->introduced_at->format('Y-m-d H:s');
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
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
     * @param  \App\Http\Requests\StoreTitleRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTitleRequest $request)
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
