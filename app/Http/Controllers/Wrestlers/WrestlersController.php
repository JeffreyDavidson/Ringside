<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Http\Request;
use App\Filters\WrestlerFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWrestlerRequest;
use App\Http\Requests\UpdateWrestlerRequest;

class WrestlersController extends Controller
{
    /**
     * View a list of wrestlers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, WrestlerFilters $requestFilter)
    {
        $this->authorize('viewList', Wrestler::class);

        if ($request->ajax()) {
            $query = Wrestler::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'wrestlers.partials.action-cell')
                ->editColumn('hired_at', function (Wrestler $wrestler) {
                    return $wrestler->hired_at->format('Y-m-d H:s');
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }

        return view('wrestlers.index');
    }

    /**
     * Show the form for creating a new wrestler.
     *
     * @return \Illuminate\View\View
     */
    public function create(Wrestler $wrestler)
    {
        $this->authorize('create', Wrestler::class);

        return view('wrestlers.create', compact('wrestler'));
    }

    /**
     * Create a new wrestler.
     *
     * @param  \App\Http\Requests\StoreWrestlerRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreWrestlerRequest $request)
    {
        Wrestler::create($request->all());

        return redirect()->route('wrestlers.index');
    }

    /**
     * Show the profile of a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function show(Wrestler $wrestler)
    {
        $this->authorize('view', $wrestler);

        return view('wrestlers.show', compact('wrestler'));
    }

    /**
     * Show the form for editing a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function edit(Wrestler $wrestler)
    {
        $this->authorize('update', $wrestler);

        return view('wrestlers.edit', compact('wrestler'));
    }

    /**
     * Update a given wrestler.
     *
     * @param  \App\Http\Requests\UpdateWrestlerRequest  $request
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateWrestlerRequest $request, Wrestler $wrestler)
    {
        $wrestler->update($request->all());

        return redirect()->route('wrestlers.index');
    }

    /**
     * Delete a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('delete', $wrestler);

        $wrestler->delete();

        return redirect()->route('wrestlers.index');
    }
}
