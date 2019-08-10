<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use Illuminate\Http\Request;
use App\Filters\ManagerFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;

class ManagersController extends Controller
{
    /**
     * View a list of managers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @param  \App\Filters\ManagerFilters  $requestFilter
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, ManagerFilters $requestFilter)
    {
        $this->authorize('viewList', Manager::class);

        if ($request->ajax()) {
            $query = Manager::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'managers.partials.action-cell')
                ->editColumn('started_at', function (Manager $manager) {
                    return $manager->employment->started_at ?? null;
                })
                ->filterColumn('name', function ($query, $keyword) {
                    $sql = "CONCAT(managers.first_name, ' ', managers.last_name)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }

        return view('managers.index');
    }

    /**
     * Show the form for creating a manager.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Manager $manager)
    {
        $this->authorize('create', Manager::class);

        return view('managers.create', compact('manager'));
    }

    /**
     * Create a new manager.
     *
     * @param  \App\Http\Requests\StoreManagerRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreManagerRequest $request)
    {
        $manager = Manager::create($request->except('started_at'));

        if ($request->filled('started_at')) {
            $manager->employments()->create($request->only('started_at'));
        }

        return redirect()->route('managers.index');
    }

    /**
     * Show the profile of a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\Response
     */
    public function show(Manager $manager)
    {
        $this->authorize('view', $manager);

        return view('managers.show', compact('manager'));
    }

    /**
     * Show the form for editing a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\Response
     */
    public function edit(Manager $manager)
    {
        $this->authorize('update', $manager);

        return view('managers.edit', compact('manager'));
    }

    /**
     * Update a given manager.
     *
     * @param  \App\Http\Requests\UpdateManagerRequest  $request
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateManagerRequest $request, Manager $manager)
    {
        $manager->update($request->except('started_at'));

        if ($manager->employment()->exists() && !is_null($request->input('started_at'))) {
            if ($manager->employment->started_at != $request->input('started_at')) {
                $manager->employment()->update($request->only('started_at'));
            }
        } else {
            $manager->employments()->create($request->only('started_at'));
        }


        return redirect()->route('managers.index');
    }

    /**
     * Delete a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('delete', $manager);

        $manager->delete();

        return redirect()->route('managers.index');
    }
}
