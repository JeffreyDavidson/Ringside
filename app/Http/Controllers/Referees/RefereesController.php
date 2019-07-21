<?php

namespace App\Http\Controllers\Referees;

use App\Models\Referee;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefereeRequest;
use App\Http\Requests\UpdateRefereeRequest;

class RefereesController extends Controller
{
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
        $referee->employments()->create($request->only('started_at'));

        return redirect()->route('referees.index');
    }

    /**
     * Show the form for editing a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\Response
     */
    public function edit(Referee $referee)
    {
        $this->authorize('update', Referee::class);

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
        $referee->employment()->update($request->only('started_at'));

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
        $this->authorize('delete', Referee::class);

        $referee->delete();

        return redirect()->route('referees.index');
    }
}
