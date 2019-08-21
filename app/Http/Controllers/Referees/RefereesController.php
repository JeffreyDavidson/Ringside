<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use Illuminate\Http\Request;
use App\Filters\RefereeFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefereeRequest;
use App\Http\Requests\UpdateRefereeRequest;

class RefereesController extends Controller
{
    /**
     * View a list of wrestlers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, RefereeFilters $requestFilter)
    {
        $this->authorize('viewList', Referee::class);

        if ($request->ajax()) {
            $query = Referee::with('employment');
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'referees.partials.action-cell')
                ->filterColumn('name', function ($query, $keyword) {
                    $sql = "CONCAT(referees.first_name, ' ', referees.last_name)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }

        return view('referees.index');
    }

    /**
     * Show the form for creating a new referee.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Referee $referee)
    {
        $this->authorize('create', Referee::class);

        return view('referees.create', compact('referee'));
    }

    /**
     * Create a new referee.
     *
     * @param  \App\Http\Requests\StoreRefereeRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRefereeRequest $request)
    {
        $referee = Referee::create($request->except('started_at'));

        if (!is_null($request->input('started_at'))) {
            $referee->employments()->create($request->only('started_at'));
        }

        return redirect()->route('referees.index');
    }

    /**
     * Show the profile of a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\View\View
     */
    public function show(Referee $referee)
    {
        $this->authorize('view', $referee);

        return view('referees.show', compact('referee'));
    }

    /**
     * Show the form for editing a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\Response
     */
    public function edit(Referee $referee)
    {
        $this->authorize('update', $referee);

        return view('referees.edit', compact('referee'));
    }

    /**
     * Update a given referee.
     *
     * @param  \App\Http\Requests\UpdateRefereeRequest  $request
     * @param  \App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateRefereeRequest $request, Referee $referee)
    {
        $referee->update($request->except('started_at'));

        if ($referee->employments()->exists() && !is_null($request->input('started_at'))) {
            if ($referee->employment->started_at != $request->input('started_at')) {
                $referee->employment()->update($request->only('started_at'));
            }
        } else {
            $referee->employments()->create($request->only('started_at'));
        }

        return redirect()->route('referees.index');
    }

    /**
     * Delete a referee.
     *
     * @param  App\Models\Referee  $referee
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('delete', $referee);

        $referee->delete();

        return redirect()->route('referees.index');
    }
}
