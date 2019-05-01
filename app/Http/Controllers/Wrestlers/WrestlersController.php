<?php

namespace App\Http\Controllers\Wrestlers;

use App\Models\Wrestler;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRosterRequest;
use App\Http\Requests\StoreWrestlerRequest;
use App\Http\Requests\UpdateWrestlerRequest;

class WrestlersController extends Controller
{
    /**
     * Retrieve wrestles of a specific state.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(IndexRosterRequest $request)
    {
        $this->authorize('viewList', Wrestler::class);

        $state = $request->input('state', 'active');
        $wrestlers = Wrestler::hasState($state)->get();

        if ($request->ajax()) {
            return $wrestlers->toJson();
        }

        return response()->view('wrestlers.index', compact('wrestlers', 'state'));
    }

    /**
     * Show the form for creating a new wrestler.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Wrestler $wrestler)
    {
        $this->authorize('create', Wrestler::class);

        return response()->view('wrestlers.create', compact('wrestler'));
    }

    /**
     * Create a new wrestler.
     *
     * @param  \App\Http\Requests\StoreWrestlerRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreWrestlerRequest $request)
    {
        $request->merge(['height' => ($request->input('feet') * 12) + $request->input('inches')]);

        $wrestler = Wrestler::create($request->except(['feet', 'inches']));

        return redirect()->route('wrestlers.index');
    }

    /**
     * Show the profile of a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\Response
     */
    public function show(Wrestler $wrestler)
    {
        $this->authorize('view', $wrestler);

        return response()->view('wrestlers.show', compact('wrestler'));
    }

    /**
     * Edit a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \lluminate\Http\Response
     */
    public function edit(Wrestler $wrestler)
    {
        $this->authorize('update', $wrestler);

        return response()->view('wrestlers.edit', compact('wrestler'));
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
        $request->merge(['height' => ($request->input('feet') * 12) + $request->input('inches')]);

        $wrestler->update($request->except(['feet', 'inches']));

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

    /**
     * Restore a deleted wrestler.
     *
     * @param  int  $wrestlerId
     * @return \lluminate\Http\RedirectResponse
     */
    public function restore($wrestlerId)
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        $this->authorize('restore', Wrestler::class);

        $wrestler->restore();

        return redirect()->route('wrestlers.index');
    }
}
