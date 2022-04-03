<?php

namespace App\Http\Controllers\Referees;

use App\DataTransferObjects\RefereeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;
use App\Services\RefereeService;

class RefereesController extends Controller
{
    private RefereeService $refereeService;

    /**
     * Create a new referees controller instance.
     *
     * @param \App\Services\RefereeService $refereeService
     */
    public function __construct(RefereeService $refereeService)
    {
        $this->refereeService = $refereeService;
    }

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
     * @param  \App\Models\Referee $referee
     * @return \Illuminate\View\View
     */
    public function create(Referee $referee)
    {
        $this->authorize('create', Referee::class);

        return view('referees.create', [
            'referee' => $referee,
        ]);
    }

    /**
     * Create a new referee.
     *
     * @param  \App\Http\Requests\Referees\StoreRequest  $request
     * @param  \App\DataTransferObjects\RefereeData  $refereeData
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, RefereeData $refereeData)
    {
        $this->refereeService->create($refereeData->fromStoreRequest($request));

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

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }

    /**
     * Show the form for editing a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\View\View
     */
    public function edit(Referee $referee)
    {
        $this->authorize('update', $referee);

        return view('referees.edit', [
            'referee' => $referee,
        ]);
    }

    /**
     * Update a given referee.
     *
     * @param  \App\Http\Requests\Referees\UpdateRequest  $request
     * @param  \App\Models\Referee  $referee
     * @param  \App\DataTransferObjects\RefereeData  $refereeData
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Referee $referee, RefereeData $refereeData)
    {
        $this->refereeService->update($referee, $refereeData->fromUpdateRequest($request));

        return redirect()->route('referees.index');
    }

    /**
     * Delete a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('delete', $referee);

        $this->refereeService->delete($referee);

        return redirect()->route('referees.index');
    }
}
