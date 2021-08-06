<?php

namespace App\Http\Controllers\Referees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class RefereesController extends Controller
{
    /**
     * View a list of referees.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Referee::class);

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
     * @param  \App\Http\Requests\Referees\StoreRequest  $request
     * @param  \App\Services\RefereeService  $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, RefereeService $refereeService)
    {
        $refereeService->create($request->validated());

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
     * @param  \App\Http\Requests\Referees\UpdateRequest  $request
     * @param  \App\Models\Referee  $referee
     * @param  \App\Services\RefereeService  $refereeService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Referee $referee, RefereeService $refereeService)
    {
        $refereeService->update($referee, $request->validated());

        return redirect()->route('referees.index');
    }

    /**
     * Delete a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee, RefereeService $refereeService)
    {
        $this->authorize('delete', $referee);

        $refereeService->delete($referee);

        return redirect()->route('referees.index');
    }
}
